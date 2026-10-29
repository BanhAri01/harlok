<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Conversation;
use App\Models\Offer;
use App\Models\StockReport;
use App\Models\User;
use App\Support\Phone;
use App\Support\Rupiah;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OfferService
{
    public const STATE_REPLY = 'offer_reply';

    public function __construct(private PriceBoard $prices, private Messenger $messenger) {}

    public function create(User $buyer, StockReport $report, int $pricePerKg, float $quantityKg): Offer
    {
        $offer = DB::transaction(function () use ($buyer, $report, $pricePerKg, $quantityKg) {
            $report = StockReport::query()->with('farmer:id,channel')->lockForUpdate()->findOrFail($report->id);

            if ($buyer->is_demo && $report->farmer->channel === 'wa') {
                throw ValidationException::withMessages(['price_per_kg' => 'Akun demo hanya bisa menawar stok contoh atau stok dari simulator.']);
            }

            if (! $buyer->is_demo && ! $buyer->isAdmin() && $report->farmer->channel !== 'wa') {
                throw ValidationException::withMessages(['price_per_kg' => 'Stok ini berasal dari data contoh atau simulator, jadi tidak bisa ditawar dengan akun pengepul sungguhan.']);
            }

            if (! $report->isAvailable()) {
                throw ValidationException::withMessages(['price_per_kg' => 'Stok ini sudah tidak tersedia.']);
            }

            if ($quantityKg > $report->quantity_kg) {
                throw ValidationException::withMessages(['quantity_kg' => 'Jumlah melebihi stok yang dilaporkan.']);
            }

            $alreadyOpen = Offer::query()
                ->where('stock_report_id', $report->id)
                ->where('buyer_id', $buyer->id)
                ->whereIn('status', Offer::OPEN)
                ->exists();

            if ($alreadyOpen) {
                throw ValidationException::withMessages(['price_per_kg' => 'Anda masih punya tawaran yang menunggu jawaban untuk stok ini.']);
            }

            return Offer::query()->create([
                'stock_report_id' => $report->id,
                'buyer_id' => $buyer->id,
                'price_per_kg' => $pricePerKg,
                'quantity_kg' => $quantityKg,
                'market_avg' => $this->prices->marketAverage($report->commodity_id, $report->region_id)['price'] ?? null,
                'status' => Offer::SENT,
            ]);
        });

        $offer->load('report.farmer', 'report.commodity', 'report.region', 'buyer');
        AuditLog::record('user:'.$buyer->id, 'offer.sent', $offer, ['price' => $pricePerKg]);

        $farmer = $offer->report->farmer;
        Conversation::for($farmer->phone)->moveTo(self::STATE_REPLY, ['offer_id' => $offer->id], 60 * 24);
        $this->messenger->send($farmer->phone, $this->offerMessage($offer));

        return $offer;
    }

    public function offerMessage(Offer $offer): string
    {
        $report = $offer->report;

        return "🔔 *Tawaran masuk!*\n"
            ."{$offer->buyer->displayName()} menawar {$report->commodity->name} Anda\n"
            .Rupiah::kg($offer->quantity_kg).' @ *'.Rupiah::format($offer->price_per_kg)."/kg*\n"
            .$this->marketLine($offer)."\n\n"
            ."Balas:\n*1* = Terima\n*2* = Tolak\n*3* = Tawar balik";
    }

    public function marketLine(Offer $offer): string
    {
        $region = $offer->report->region->name;
        $percent = $offer->percentFromMarket();

        if ($percent === null) {
            return "Belum ada harga pembanding di {$region}.";
        }

        $average = Rupiah::format($offer->market_avg);

        if ($percent <= -(float) config('lapor.market.warn_below_percent')) {
            return '⚠️ Tawaran ini *'.Rupiah::percent($percent)." DI BAWAH* rata-rata {$region} ({$average}). Pertimbangkan dulu.";
        }

        if ($percent >= 0) {
            return "✅ Di atas/setara rata-rata {$region} ({$average}).";
        }

        return "Sedikit di bawah rata-rata {$region} ({$average}).";
    }

    public function accept(Offer $offer, string $actor): Offer
    {
        $accepted = DB::transaction(function () use ($offer) {
            $offer = Offer::query()->lockForUpdate()->findOrFail($offer->id);
            $report = StockReport::query()->lockForUpdate()->findOrFail($offer->stock_report_id);

            if (! $offer->isOpen() || ! $report->isAvailable()) {
                return null;
            }

            $offer->forceFill([
                'status' => Offer::ACCEPTED,
                'price_per_kg' => $offer->status === Offer::COUNTERED ? $offer->counter_price : $offer->price_per_kg,
                'responded_at' => now(),
            ])->save();

            $report->forceFill(['status' => StockReport::SOLD, 'closed_at' => now()])->save();

            Offer::query()
                ->where('stock_report_id', $report->id)
                ->whereKeyNot($offer->id)
                ->whereIn('status', Offer::OPEN)
                ->update(['status' => Offer::REJECTED, 'responded_at' => now()]);

            return $offer;
        });

        if (! $accepted) {
            throw ValidationException::withMessages(['offer' => 'Tawaran ini sudah tidak berlaku.']);
        }

        $accepted->load('report.farmer', 'report.commodity', 'report.region', 'buyer');
        AuditLog::record($actor, 'offer.accepted', $accepted, ['price' => $accepted->price_per_kg]);
        $this->resetFarmerConversation($accepted);

        $report = $accepted->report;
        $summary = "{$report->commodity->name} ".Rupiah::kg($accepted->quantity_kg).' @ '.Rupiah::format($accepted->price_per_kg).'/kg';
        $farmerContact = Phone::display($report->farmer->phone);
        $buyerContact = $accepted->buyer->phone ? Phone::display($accepted->buyer->phone) : '-';

        $this->messenger->send($report->farmer->phone, "🤝 *Sepakat!* {$summary}\nPembeli: {$accepted->buyer->displayName()}\nKontak: {$buyerContact}\nSilakan atur pengambilan & pembayaran langsung.");

        if ($accepted->buyer->phone) {
            $this->messenger->send($accepted->buyer->phone, "🤝 *Sepakat!* {$summary}\nPeternak: {$report->farmer->name} ({$report->region->name})\nKontak: {$farmerContact}");
        }

        return $accepted;
    }

    public function reject(Offer $offer, string $actor): void
    {
        $this->transition($offer, Offer::SENT, ['status' => Offer::REJECTED, 'responded_at' => now()]);
        AuditLog::record($actor, 'offer.rejected', $offer);
        $this->resetFarmerConversation($offer);
        $this->notifyBuyer($offer, "❌ Tawaran {$offer->report->commodity->name} ".Rupiah::format($offer->price_per_kg).'/kg ditolak peternak.');
    }

    public function counter(Offer $offer, int $price, string $actor): void
    {
        $this->transition($offer, Offer::SENT, ['status' => Offer::COUNTERED, 'counter_price' => $price, 'responded_at' => now()]);
        AuditLog::record($actor, 'offer.countered', $offer, ['price' => $price]);
        $this->resetFarmerConversation($offer);
        $this->notifyBuyer($offer, "↩️ Peternak menawar balik {$offer->report->commodity->name}: ".Rupiah::format($price).'/kg. Buka: '.route('buyer.offers'));
    }

    public function cancel(Offer $offer, User $buyer): void
    {
        $this->transition($offer, null, ['status' => Offer::CANCELLED, 'responded_at' => now()]);
        AuditLog::record('user:'.$buyer->id, 'offer.cancelled', $offer);
        $this->resetFarmerConversation($offer);
        $this->messenger->send($offer->report->farmer->phone, "ℹ️ Tawaran dari {$buyer->displayName()} untuk {$offer->report->commodity->name} dibatalkan pembeli.");
    }

    private function transition(Offer $offer, ?string $requiredStatus, array $changes): void
    {
        $updated = Offer::query()
            ->whereKey($offer->id)
            ->when($requiredStatus, fn ($query) => $query->where('status', $requiredStatus), fn ($query) => $query->whereIn('status', Offer::OPEN))
            ->update($changes);

        if (! $updated) {
            throw ValidationException::withMessages(['offer' => 'Tawaran ini sudah tidak berlaku.']);
        }

        $offer->refresh()->load('report.farmer', 'report.commodity', 'buyer');
    }

    private function notifyBuyer(Offer $offer, string $body): void
    {
        if ($offer->buyer->phone) {
            $this->messenger->send($offer->buyer->phone, $body);
        }
    }

    private function resetFarmerConversation(Offer $offer): void
    {
        $conversation = Conversation::query()->where('phone', $offer->report->farmer->phone)->first();

        if ($conversation && in_array($conversation->state, [self::STATE_REPLY, 'counter_price'], true) && (int) $conversation->get('offer_id') === $offer->id) {
            $conversation->reset();
        }
    }
}
