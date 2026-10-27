<?php

namespace App\Chat;

use App\Models\AuditLog;
use App\Models\Commodity;
use App\Models\Conversation;
use App\Models\Farmer;
use App\Models\Offer;
use App\Models\Region;
use App\Models\StockReport;
use App\Services\OfferService;
use App\Services\PriceBoard;
use App\Services\ReportService;
use App\Support\Phone;
use App\Support\Rupiah;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ChatBot
{
    public const REG_NAME = 'reg_name';

    public const REG_REGION = 'reg_region';

    public const REG_COMMODITY = 'reg_commodity';

    public const CONFIRM_REPORT = 'confirm_report';

    public const COUNTER_PRICE = 'counter_price';

    private const YES = ['ya', 'y', 'iya', 'yes', 'ok', 'oke', 'benar', 'betul', 'setuju', 'simpan'];

    private const NO = ['ubah', 'tidak', 'salah', 'no', 'bukan', 'ganti'];

    public function __construct(
        private ReportParser $parser,
        private AiReportParser $ai,
        private ReportService $reports,
        private OfferService $offers,
        private PriceBoard $prices,
    ) {}

    public function handle(string $phone, string $text): array
    {
        $text = trim(mb_substr($text, 0, 500));
        $command = $this->command($text);
        $conversation = Conversation::for($phone);
        $farmer = Farmer::query()->with('region', 'commodity')->where('phone', $phone)->first();

        if ($farmer) {
            $farmer->forceFill(['last_message_at' => now()])->save();
        }

        if ($command === 'batal') {
            return [$this->cancel($conversation, $farmer)];
        }

        if (! $farmer) {
            return $this->register($conversation, $phone, $text, $command);
        }

        if (in_array($command, ['menu', 'bantuan', 'help', 'halo', 'hai', 'p', 'info'], true)) {
            $conversation->reset();

            return [$this->help($farmer)];
        }

        return match ($conversation->state) {
            self::CONFIRM_REPORT => $this->confirmReport($conversation, $farmer, $text, $command),
            OfferService::STATE_REPLY => $this->offerReply($conversation, $farmer, $text, $command),
            self::COUNTER_PRICE => $this->counterPrice($conversation, $farmer, $text),
            default => $this->idle($conversation, $farmer, $text, $command),
        };
    }

    private function register(Conversation $conversation, string $phone, string $text, string $command): array
    {
        $appName = config('app.name');

        return match ($conversation->state) {
            self::REG_NAME => $this->registerName($conversation, $text),
            self::REG_REGION => $this->registerRegion($conversation, $command),
            self::REG_COMMODITY => $this->registerCommodity($conversation, $phone, $command),
            default => tap(
                ["Selamat datang di *{$appName}* 🐔🌾\nLapor stok & cek harga pasar cukup lewat chat ini.\n\nSiapa nama Bapak/Ibu?"],
                fn () => $conversation->moveTo(self::REG_NAME),
            ),
        };
    }

    private function registerName(Conversation $conversation, string $text): array
    {
        $name = Str::title(Str::squish(preg_replace('/[^\pL\s\'.]/u', '', $text)));

        if (mb_strlen($name) < 2 || mb_strlen($name) > 60) {
            return ['Mohon tulis nama Bapak/Ibu (huruf saja), contoh: *Made Sukarta*'];
        }

        $conversation->moveTo(self::REG_REGION, ['name' => $name]);

        return ["Salam kenal, {$name} 🙏\n\nLokasi kandang/kebun di mana? Balas nomornya:\n".$this->numberedList(Region::ordered()->pluck('name'))];
    }

    private function registerRegion(Conversation $conversation, string $command): array
    {
        $region = $this->pick(Region::ordered(), $command, fn (Region $region) => $this->parser->regionAliases($region));

        if (! $region) {
            return ["Nomor tidak dikenali. Balas nomornya saja:\n".$this->numberedList(Region::ordered()->pluck('name'))];
        }

        $conversation->moveTo(self::REG_COMMODITY, [...$conversation->data, 'region_id' => $region->id]);

        return ["📍 {$region->name}\n\nKomoditas utama? Balas nomornya:\n".$this->numberedList(Commodity::active()->pluck('name'))];
    }

    private function registerCommodity(Conversation $conversation, string $phone, string $command): array
    {
        $commodity = $this->pick(Commodity::active(), $command, fn (Commodity $commodity) => [$commodity->slug, ...$commodity->aliases]);

        if (! $commodity) {
            return ["Nomor tidak dikenali. Balas nomornya saja:\n".$this->numberedList(Commodity::active()->pluck('name'))];
        }

        $farmer = Farmer::query()->create([
            'name' => $conversation->get('name'),
            'phone' => $phone,
            'region_id' => $conversation->get('region_id'),
            'commodity_id' => $commodity->id,
            'channel' => Phone::channel($phone),
            'last_message_at' => now(),
        ]);
        $conversation->reset();
        AuditLog::record('wa:'.$phone, 'farmer.registered', $farmer);

        $example = $this->example($commodity);

        return ["✅ *Terdaftar!* Terima kasih {$farmer->firstName()}.\n\n"
            ."Kapan pun ada stok, kirim saja seperti ini:\n_{$example}_\n\n"
            ."Ketik *HARGA* untuk cek harga pasar, *MENU* untuk bantuan.\n\n"
            .'ℹ️ Koordinator akan memverifikasi nomor Anda agar harga yang Anda laporkan ikut dihitung di rata-rata wilayah.'];
    }

    private function idle(Conversation $conversation, Farmer $farmer, string $text, string $command): array
    {
        $word = strtok($command, ' ');

        return match (true) {
            $word === 'harga' => [$this->priceSummary($farmer, $command)],
            in_array($command, ['stok', 'stokku', 'stok saya'], true) => [$this->myStock($farmer)],
            in_array($word, ['habis', 'terjual', 'laku'], true) => [$this->closeStock($farmer)],
            $word === 'tawaran' => $this->listOffers($conversation, $farmer),
            in_array($command, ['mulai', 'langganan'], true) => [$this->digest($farmer, true)],
            in_array($command, ['stop', 'berhenti'], true) => [$this->digest($farmer, false)],
            in_array($command, ['1', '2', '3'], true) => ['Saat ini tidak ada tawaran yang menunggu jawaban. Ketik *TAWARAN* untuk melihat daftar tawaran.'],
            default => $this->report($conversation, $farmer, $text),
        };
    }

    private function report(Conversation $conversation, Farmer $farmer, string $text): array
    {
        $parsed = $this->parser->parse($text, $farmer->commodity, $farmer->region);

        if (! $parsed->isComplete()) {
            $aiParsed = $this->ai->parse($farmer->phone, $text, $farmer->commodity, $farmer->region);
            if ($aiParsed) {
                $parsed = $aiParsed->isComplete() ? $aiParsed : $parsed->fillFrom($aiParsed);
            }
        }

        if (! $parsed->hasAnything()) {
            return ["Maaf, pesan belum saya pahami 🙏\n\nUntuk lapor stok ketik seperti:\n_".$this->example($farmer->commodity)."_\n\nAtau ketik *MENU*."];
        }

        if (! $parsed->isComplete()) {
            return ['Data belum lengkap: *'.implode(', ', $parsed->missing())."* belum terbaca.\nContoh: _".$this->example($parsed->commodity ?? $farmer->commodity).'_'];
        }

        if (! $parsed->commodity->priceIsPlausible($parsed->pricePerKg)) {
            return ['Harga '.Rupiah::format($parsed->pricePerKg)."/kg untuk {$parsed->commodity->name} tampak tidak wajar "
                .'(wajar: '.Rupiah::format($parsed->commodity->min_price).'–'.Rupiah::format($parsed->commodity->max_price)."/kg).\nMohon cek lagi lalu kirim ulang ya 🙏"];
        }

        $report = $this->reports->createPending($farmer, $parsed, $text);
        $conversation->moveTo(self::CONFIRM_REPORT, ['report_id' => $report->id], (int) config('lapor.market.confirm_minutes'));

        return ["Mohon cek:\n"
            ."📦 {$parsed->commodity->name} — ".Rupiah::kg($parsed->quantityKg)."\n"
            .'💰 '.Rupiah::format($parsed->pricePerKg)."/kg\n"
            .'📍 '.($parsed->region ?? $farmer->region)->name."\n\n"
            .'Balas *YA* untuk simpan, atau *UBAH* bila salah.'];
    }

    private function confirmReport(Conversation $conversation, Farmer $farmer, string $text, string $command): array
    {
        $report = StockReport::query()
            ->with('commodity', 'region', 'farmer')
            ->where('farmer_id', $farmer->id)
            ->where('status', StockReport::PENDING)
            ->find($conversation->get('report_id'));

        if (! $report) {
            $conversation->reset();

            return $this->idle($conversation, $farmer, $text, $command);
        }

        if (in_array($command, self::NO, true)) {
            $report->forceFill(['status' => StockReport::CANCELLED, 'closed_at' => now()])->save();
            $conversation->reset();

            return ["Baik, laporan dibatalkan. Silakan kirim ulang, contoh:\n_".$this->example($report->commodity).'_'];
        }

        if (! in_array($command, self::YES, true)) {
            $conversation->reset();

            return $this->idle($conversation, $farmer, $text, $command);
        }

        $result = $this->reports->confirm($report);
        $conversation->reset();

        return [$this->confirmationMessage($report->refresh(), $farmer, $result['market'], $result['deviation'])];
    }

    private function confirmationMessage(StockReport $report, Farmer $farmer, ?array $market, ?float $deviation): string
    {
        $region = $report->region->name;

        if ($report->status === StockReport::FLAGGED) {
            return "⏳ Laporan diterima, tapi harganya jauh dari rata-rata {$region} (".Rupiah::format($market['price'])."/kg).\nKoordinator akan mengecek dulu sebelum tampil ke pengepul.";
        }

        $lines = ["✅ *Tersimpan* & tampil ke pengepul di {$region}."];

        if ($market) {
            $scope = $market['scope'] === 'region' ? $region : 'semua wilayah';
            $lines[] = "📊 Rata-rata {$scope}: ".Rupiah::format($market['price']).'/kg';
            $lines[] = match (true) {
                abs($deviation) < 0.5 => 'Harga Anda setara rata-rata.',
                $deviation > 0 => 'Harga Anda '.Rupiah::percent($deviation).' di atas rata-rata.',
                default => 'Harga Anda '.Rupiah::percent($deviation).' di bawah rata-rata.',
            };
        } else {
            $lines[] = "📊 Anda pelapor pertama {$report->commodity->name} di {$region} minggu ini 👍";
        }

        if (! $farmer->isVerified()) {
            $lines[] = "\nℹ️ Nomor Anda belum diverifikasi koordinator, jadi harga Anda belum ikut dihitung di rata-rata.";
        }

        $lines[] = "\nKetik *HABIS* bila stok sudah terjual.";

        return implode("\n", $lines);
    }

    private function offerReply(Conversation $conversation, Farmer $farmer, string $text, string $command): array
    {
        $offer = $this->openOffer($farmer, (int) $conversation->get('offer_id'));

        if (! $offer || ! in_array($command, ['1', '2', '3', 'terima', 'tolak', 'tawar'], true)) {
            if (! $offer) {
                $conversation->reset();
            }

            return $this->idle($conversation, $farmer, $text, $command);
        }

        $actor = 'wa:'.$farmer->phone;

        try {
            return match ($command) {
                '1', 'terima' => tap([], fn () => $this->offers->accept($offer, $actor)),
                '2', 'tolak' => tap(['Baik, tawaran ditolak. Pembeli sudah diberi tahu.'], fn () => $this->offers->reject($offer, $actor)),
                default => tap(
                    ['Berapa harga yang Anda minta per kg? Contoh: *27rb*'],
                    fn () => $conversation->moveTo(self::COUNTER_PRICE, ['offer_id' => $offer->id], 60),
                ),
            };
        } catch (ValidationException) {
            $conversation->reset();

            return ['Tawaran ini sudah tidak berlaku.'];
        }
    }

    private function counterPrice(Conversation $conversation, Farmer $farmer, string $text): array
    {
        $offer = $this->openOffer($farmer, (int) $conversation->get('offer_id'));

        if (! $offer) {
            $conversation->reset();

            return ['Tawaran ini sudah tidak berlaku.'];
        }

        $price = $this->parser->parse('harga '.$text, $offer->report->commodity)->pricePerKg;

        if (! $price || ! $offer->report->commodity->priceIsPlausible($price)) {
            return ['Harga belum terbaca/tidak wajar. Tulis seperti *27rb* atau *27000*. Ketik *BATAL* untuk membatalkan.'];
        }

        try {
            $this->offers->counter($offer, $price, 'wa:'.$farmer->phone);
        } catch (ValidationException) {
            $conversation->reset();

            return ['Tawaran ini sudah tidak berlaku.'];
        }

        return ['✅ Tawaran balik '.Rupiah::format($price).'/kg sudah dikirim ke pembeli. Anda akan dikabari bila pembeli setuju.'];
    }

    private function listOffers(Conversation $conversation, Farmer $farmer): array
    {
        $offers = Offer::query()
            ->with('buyer', 'report.commodity', 'report.region')
            ->where('status', Offer::SENT)
            ->whereHas('report', fn ($query) => $query->where('farmer_id', $farmer->id)->where('status', StockReport::CONFIRMED))
            ->latest('id')
            ->limit(3)
            ->get();

        if ($offers->isEmpty()) {
            return ['Belum ada tawaran yang menunggu jawaban.'];
        }

        $latest = $offers->first();
        $conversation->moveTo(OfferService::STATE_REPLY, ['offer_id' => $latest->id], 60 * 24);

        $note = $offers->count() > 1 ? "\n\n(Ada {$offers->count()} tawaran. Jawab yang ini dulu, lalu ketik *TAWARAN* lagi.)" : '';

        return [$this->offers->offerMessage($latest).$note];
    }

    private function openOffer(Farmer $farmer, int $offerId): ?Offer
    {
        return Offer::query()
            ->with('report.commodity', 'report.region', 'report.farmer', 'buyer')
            ->where('status', Offer::SENT)
            ->whereHas('report', fn ($query) => $query->where('farmer_id', $farmer->id))
            ->find($offerId);
    }

    private function priceSummary(Farmer $farmer, string $command): string
    {
        $commodity = $this->parser->findCommodity($command) ?? $farmer->commodity ?? Commodity::active()->first();
        $rows = $this->prices->regionSummary($commodity);

        if ($rows->isEmpty()) {
            return "Belum ada laporan harga {$commodity->name} minggu ini. Jadilah yang pertama melapor 👍";
        }

        $home = $farmer->region;
        $rows = $rows->sortBy(fn (array $row) => [
            $row['region']?->id === $home?->id ? 0 : ($row['region']?->province === $home?->province ? 1 : 2),
            $row['region']?->sort,
        ])->values();

        $lines = $rows->take(10)->map(function (array $row) {
            $arrow = match (true) {
                $row['change'] === null => '',
                $row['change'] > 0 => ' ▲'.number_format($row['change'], 0, ',', '.'),
                $row['change'] < 0 => ' ▼'.number_format(abs($row['change']), 0, ',', '.'),
                default => ' ▬',
            };
            $stale = $row['date']->isToday() ? '' : ' ('.$row['date']->translatedFormat('j M').')';

            return "• {$row['region']->name}: ".Rupiah::format($row['avg']).$arrow.$stale;
        });

        return "📊 *Harga {$commodity->name}* (per kg)\n".$lines->implode("\n")."\n\nSumber: laporan peternak terverifikasi.";
    }

    private function myStock(Farmer $farmer): string
    {
        $reports = $farmer->reports()->with('commodity')->available()->latest('id')->get();

        if ($reports->isEmpty()) {
            return 'Anda belum punya stok aktif. Kirim laporan seperti: _'.$this->example($farmer->commodity).'_';
        }

        $lines = $reports->map(function (StockReport $report) {
            $offers = $report->offers()->where('status', Offer::SENT)->count();

            return "• {$report->commodity->name} ".Rupiah::kg($report->quantity_kg).' @ '.Rupiah::format($report->price_per_kg)
                .($offers ? " — {$offers} tawaran" : '');
        });

        return "📦 *Stok aktif Anda*\n".$lines->implode("\n")."\n\nKetik *HABIS* bila sudah terjual.";
    }

    private function closeStock(Farmer $farmer): string
    {
        $report = $farmer->reports()->with('commodity')->available()->latest('id')->first();

        if (! $report) {
            return 'Tidak ada stok aktif yang perlu ditutup.';
        }

        $this->reports->close($report, 'wa:'.$farmer->phone);

        return "✅ Stok {$report->commodity->name} ".Rupiah::kg($report->quantity_kg).' ditandai sudah habis. Terima kasih!';
    }

    private function digest(Farmer $farmer, bool $optIn): string
    {
        $farmer->forceFill(['digest_opt_in' => $optIn])->save();

        return $optIn
            ? '✅ Anda akan menerima ringkasan harga setiap pagi pukul 06.00. Ketik *STOP* untuk berhenti.'
            : 'Baik, ringkasan pagi dihentikan. Ketik *MULAI* untuk berlangganan lagi.';
    }

    private function cancel(Conversation $conversation, ?Farmer $farmer): string
    {
        if ($farmer && $conversation->state === self::CONFIRM_REPORT) {
            StockReport::query()
                ->where('farmer_id', $farmer->id)
                ->where('status', StockReport::PENDING)
                ->update(['status' => StockReport::CANCELLED, 'closed_at' => now()]);
        }

        $conversation->reset();

        return $farmer ? 'Dibatalkan. Ketik *MENU* untuk bantuan.' : 'Dibatalkan. Kirim pesan apa saja untuk mulai mendaftar.';
    }

    private function help(Farmer $farmer): string
    {
        return '*Menu '.config('app.name')."*\n\n"
            ."📦 *Lapor stok*: ketik seperti\n_".$this->example($farmer->commodity)."_\n\n"
            ."📊 *HARGA* — harga pasar hari ini\n"
            ."🗂️ *STOK* — stok aktif saya\n"
            ."✅ *HABIS* — stok terakhir sudah terjual\n"
            ."🔔 *TAWARAN* — tawaran dari pembeli\n"
            ."🌅 *MULAI* / *STOP* — ringkasan harga tiap pagi\n"
            .'↩️ *BATAL* — batalkan langkah';
    }

    private function example(?Commodity $commodity): string
    {
        return match ($commodity?->slug) {
            'telur' => 'telur 2 ton 26rb',
            'ayam-broiler' => 'ayam 500 kg 21rb',
            'cabai-rawit' => 'cabai rawit 300 kg 45rb',
            'beras' => 'beras 1 ton 13rb',
            default => ($commodity ? Str::lower($commodity->name) : 'telur').' 100 kg 20rb',
        };
    }

    private function numberedList($names): string
    {
        return $names->values()->map(fn ($name, $index) => ($index + 1).". {$name}")->implode("\n");
    }

    private function pick($items, string $command, callable $aliases): mixed
    {
        if (ctype_digit($command)) {
            return $items->values()->get((int) $command - 1);
        }

        return $items->first(fn ($item) => in_array($command, array_map('strtolower', $aliases($item)), true));
    }

    private function command(string $text): string
    {
        return Str::squish(Str::lower(preg_replace('/[^\pL\pN\s]/u', ' ', $text)));
    }
}
