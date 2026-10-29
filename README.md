# Harlok

**Harga pasar yang adil, cukup lewat WhatsApp.**

Harlok adalah aplikasi web dengan asisten AI di WhatsApp yang menghubungkan peternak dan petani kecil, pengepul, serta pihak lain (dinas, koperasi, aplikasi lain) dalam satu ekosistem data harga. Peternak cukup mengirim chat WhatsApp seperti biasa, misalnya `telur 2 ton 26rb`, `taluh 1 ton, 25 ribu a kilo`, atau `ajin tabia jani kuda?`. Asisten AI memahami maksudnya, meminta konfirmasi, lalu pesan itu berubah menjadi:

1. **harga rata-rata per wilayah** yang langsung dikirim balik ke peternak,
2. **titik stok di peta** yang bisa ditawar pengepul dari web,
3. **ringkasan pasar** dan **data terbuka lewat API** untuk siapa pun yang membutuhkan.

- **Aplikasi:** https://harlok.arisantika.tech
- **Coba tanpa WhatsApp:** https://harlok.arisantika.tech/coba
- **Pengungkapan AI & teknologi:** [docs/DISCLOSURE.md](docs/DISCLOSURE.md)

---

## Daftar isi

- [Masalah dan pengguna](#masalah-dan-pengguna)
- [Fitur utama](#fitur-utama)
- [Teknologi](#teknologi)
- [Implementasi AI](#implementasi-ai)
- [Arsitektur dan alur data](#arsitektur-dan-alur-data)
- [Menjalankan di komputer sendiri](#menjalankan-di-komputer-sendiri)
- [Alamat aplikasi dan akun demo](#alamat-aplikasi-dan-akun-demo)
- [Panduan penggunaan](#panduan-penggunaan)
- [API data harga](#api-data-harga)
- [Keamanan dan privasi](#keamanan-dan-privasi)
- [Pengujian](#pengujian)
- [Keterbatasan saat ini](#keterbatasan-saat-ini)
- [Sumber pihak ketiga](#sumber-pihak-ketiga)
- [Tim](#tim)

## Masalah dan pengguna

- Peternak dan petani kecil tidak tahu harga pasar hari ini, sehingga posisi tawarnya lemah di depan pembeli.
- Aplikasi pertanian umumnya mewajibkan instal aplikasi, mendaftar, dan belajar menu baru. Banyak peternak, terutama yang sudah berumur, hanya memakai WhatsApp, menulis dengan singkatan, dan bercampur bahasa daerah.
- Data harga tersebar di obrolan pribadi dan tidak pernah terkumpul menjadi informasi bersama. Pengepul kesulitan menemukan stok terdekat, dan dinas tidak punya data harga tingkat petani yang cepat.

| Pengguna | Kebutuhan |
|---|---|
| Peternak dan petani kecil | Tahu harga wajar sebelum menjual, menawarkan stok tanpa aplikasi baru |
| Pengepul | Menemukan stok terdekat dan menawar langsung |
| Koordinator (kelompok ternak, koperasi) | Menjaga data harga tetap jujur |
| Publik, dinas, aplikasi lain | Harga acuan terbuka lewat papan, widget, CSV, dan API |

## Fitur utama

### Inti: asisten WhatsApp untuk peternak
| Pesan | Hasil |
|---|---|
| `HARLOK` | pendaftaran 3 langkah: nama, wilayah, komoditas |
| chat bebas: `telur 2 ton 26rb`, `20 peti 390rb/peti`, `taluh 1 ton, 25 ribu a kilo` | AI membaca jumlah (kg) dan harga per kg, lalu meminta konfirmasi **YA** / **UBAH** |
| `YA` | laporan disimpan, peternak dibalas harga rata-rata wilayahnya dan selisih harganya |
| `HARGA`, `ajin tabia jani kuda?` | harga rata-rata per wilayah, naik/turun, dan ringkasan pasar AI |
| pertanyaan bebas: `kapan sebaiknya saya jual?` | jawaban AI singkat berdasarkan data laporan Harlok, diberi penanda AI |
| `STOK`, `HABIS`, `TAWARAN` | stok aktif, tandai terjual, tawaran pembeli |
| `1` / `2` / `3`, atau `oke saya ambil`, `26.5 lah` | terima / tolak / tawar balik. Menerima tawaran **selalu** menunggu balasan `1` dari peternak |
| `MULAI` | ringkasan harga setiap pukul 06.00 |
| `STOP` | bot diam total sampai peternak mengetik `HARLOK` lagi |
| `KELUAR` | hapus data peternak setelah konfirmasi |

### Pengepul (web)
- Peta stok dengan warna: sekitar rata-rata, di bawah, di atas, atau belum terverifikasi.
- Kirim tawaran dengan perhitungan selisih dari rata-rata wilayah. Peternak diperingatkan bila tawaran lebih dari 10% di bawah pasar.
- Halaman **Tawaran Saya**: menunggu, ditawar balik, sepakat (kontak peternak terbuka), ditolak, dibatalkan.

### Koordinator
- Verifikasi peternak. Hanya laporan peternak terverifikasi yang dihitung dalam rata-rata.
- **Laporan janggal** disertai **penilaian AI** (salah ketik, salah satuan, atau mencurigakan) beserta dugaan harga yang dimaksud.
- **Panel pemantau AI**: jumlah panggilan, tingkat keberhasilan, panggilan yang ditangani cadangan, dan waktu jawab.
- Kelola akun pengepul dan mitra API, log WhatsApp, dan jejak audit.

### Publik dan mitra
- **Papan harga** yang memperbarui diri tiap 12 detik, grafik 14 hari, dan **ringkasan pasar AI**.
- **Simulator chat** (`/coba`): bot yang sama tanpa WhatsApp.
- **API harga** ber-token, **widget** untuk situs koperasi/desa (`/widget/harga`), dan **data terbuka CSV** (`/data/harga.csv`). Spesifikasi: `docs/openapi.yaml`.

## Teknologi

| Lapisan | Teknologi |
|---|---|
| Frontend | Blade, Alpine.js, Tailwind CSS 4, Leaflet + OpenStreetMap, Chart.js, Vite |
| Backend | PHP 8.3+, Laravel 13, Laravel Sanctum (token API) |
| Database | MySQL/MariaDB di produksi, SQLite saat tes |
| AI | Anthropic PHP SDK (Claude Haiku 5.5) atau API kompatibel OpenAI (mis. DeepSeek), dipilih lewat `.env` |
| WhatsApp | Fonnte (gateway WhatsApp) lewat webhook |
| Hosting | cPanel (LiteSpeed, PHP 8.x, MariaDB), cron untuk jadwal |
| Pengujian | Pest 4, GitHub Actions |

## Implementasi AI

AI dipakai di tiga titik. Ketiganya melewati satu gerbang, `app/Ai/AiGateway.php`, yang memilih penyedia, membatasi kuota, memvalidasi keluaran, dan mencatat setiap panggilan.

| Fitur | Masukan ke AI | Keluaran AI | Mengapa butuh AI |
|---|---|---|---|
| **Asisten WhatsApp** (`app/Chat/AiAssistant.php`) | teks pesan, daftar komoditas/wilayah, ringkasan harga wilayah, jumlah stok dan tawaran milik pengirim | JSON: `intent` (12 pilihan), komoditas, jumlah, satuan, harga, wilayah, `answer` | Pesan peternak tidak berpola: singkatan, salah ketik, bahasa Bali, satuan lokal. Pola tetap tidak bisa menangkap semua variasi atau menjawab pertanyaan bebas |
| **Penjaga harga** (`app/Services/AiPriceGuard.php`) | laporan yang sudah ditandai janggal oleh aturan statistik, pesan asli (nomor disamarkan), rentang wajar, harga pembanding wilayah | JSON: `verdict` (wajar / salah ketik / salah satuan / mencurigakan), dugaan harga, catatan koordinator, saran untuk peternak | Membedakan salah ketik dari upaya manipulasi butuh pemahaman konteks pesan dan satuan |
| **Ringkasan pasar** (`app/Services/MarketBrief.php`) | rata-rata harga per wilayah dan tren 7 hari (angka gabungan saja) | JSON: ringkasan 2 kalimat dan arah harga | Mengubah tabel angka menjadi kalimat yang mudah dipahami di layar HP |

**Model dan penyedia.** Bawaan memakai **Claude Haiku 5.5** lewat Anthropic PHP SDK dengan *structured output* (skema JSON). Lewat `LAPOR_AI_DRIVER=openai` aplikasi bisa memakai penyedia lain yang kompatibel dengan OpenAI, misalnya DeepSeek (`deepseek-chat`), dengan `response_format: json_object`. Model kecil dipilih karena tugasnya pendek dan terstruktur, murah, dan cepat untuk chat.

**Pengaman yang dibangun di sekitar AI:**
- **Keluaran divalidasi.** Kunci wajib dan nilai `enum` dicek. Jawaban di luar skema dibuang, lalu bot memakai cadangan.
- **AI tidak pernah mengambil keputusan sendiri.** Laporan baru tersimpan setelah peternak membalas `YA`, menerima tawaran tetap harus dibalas `1`, dan laporan janggal tetap diputuskan koordinator. AI hanya memahami, menjelaskan, dan menyarankan.
- **Selalu ada cadangan.** Bila AI mati, kuota habis, waktu habis, ditolak, atau jawabannya tidak valid, bot memakai pembaca pola (`ReportParser`) dan ringkasan pasar memakai versi aturan. Kegagalan ringkasan disimpan 15 menit agar tidak membanjiri AI.
- **Privasi.** Nama dan nomor peternak tidak dikirim ke AI. Deretan angka panjang di pesan asli disamarkan sebelum dikirim. Tabel `ai_calls` hanya mencatat jenis, status, model, dan lama panggilan, tanpa isi pesan.
- **Kuota.** Ada batas per nomor (30 per hari), per hari untuk semua nomor, dan kuota terpisah untuk simulator.
- **Isi pesan diperlakukan sebagai data.** Prompt meminta model mengabaikan perintah di dalam pesan. Jawaban bebas dipotong, tautan dihapus, dan diberi penanda "Dijawab AI… cek kembali sebelum memutuskan".

Konfigurasi `.env`:
```
LAPOR_AI_DRIVER=anthropic        # atau openai
LAPOR_AI_KEY=<kunci API>
LAPOR_AI_MODEL=                  # kosong = claude-haiku-5-5 / deepseek-chat
LAPOR_AI_BASE_URL=               # hanya untuk openai, bawaan https://api.deepseek.com
```

## Arsitektur dan alur data

```
 Peternak (WhatsApp)                                        Pengepul / Publik (Web)
      │ "taluh 1 ton, 25 ribu a kilo"                               ▲
      ▼                                                             │
 ┌────────────┐ webhook ┌────────────────────────────────────────┐  │ papan harga, peta, tawaran
 │ Fonnte (WA)│───────▶ │ Laravel 13                             │──┤ (Alpine, Leaflet, Chart.js)
 └────────────┘         │  WhatsAppWebhookController             │  │
      ▲                 │   └ cek rahasia, grup, ganda, banjir   │  ▼
      │ balasan         │  InboundProcessor → ChatBot (status)   │ API /api/v1 · widget · CSV
      │                 │   ├ AiAssistant ──┐                    │
      │                 │   └ ReportParser  │ cadangan           │
      │                 │  ReportService → AiPriceGuard ──┐      │
      │                 │  PriceBoard → MarketBrief ──────┤      │
      └──────────────── │  OfferService · Messenger       ▼      │
                        │                    AiGateway ──────────┼──▶ Claude API / DeepSeek API
                        └───────────────┬────────────────────────┘
                                        ▼
                         MySQL/MariaDB: farmers, stock_reports, offers,
                         daily_prices, ai_calls, audit_logs, ...
```

1. Fonnte meneruskan pesan ke `POST /webhook/wa/{rahasia}`. Pesan disimpan di `inbound_messages` lalu diproses **setelah respons dikirim**, dengan kunci per nomor.
2. `ChatBot` menjalankan mesin status percakapan per nomor. Perintah pasti (`YA`, `1`, `HARGA`) ditangani langsung. Pesan bebas dikirim ke `AiAssistant`, lalu hasilnya dijalankan oleh kode yang sama dengan perintah biasa.
3. `ReportService` menyimpan laporan. Laporan yang menyimpang lebih dari 30% dari rata-rata ditahan dan dinilai `AiPriceGuard`.
4. `PriceBoard` menghitung rata-rata, median, minimum, dan maksimum harian per komoditas per wilayah. `MarketBrief` merangkumnya untuk papan, perintah HARGA, dan ringkasan pagi.
5. `OfferService` mengelola tawaran dengan transaksi dan `lockForUpdate`. `Messenger` mengirim semua pesan keluar dengan batas harian.

Diagram lengkap: `docs/diagram/` (arsitektur, ERD, alur pengguna).

## Menjalankan di komputer sendiri

Kebutuhan: PHP 8.3+ (pdo_sqlite atau pdo_mysql, mbstring, intl), Composer 2, Node.js 20+.

```bash
git clone https://github.com/BanhAri01/harlok.git && cd harlok
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan db:seed --class=SampleDataSeeder
php artisan lapor:admin admin@contoh.id "Koordinator"
php artisan serve
```

- `migrate --seed` mengisi 14 wilayah (Bali dan DI Yogyakarta) dan 5 komoditas beserta sebutan lokalnya (mis. *taluh*, *tabia*, *baas*).
- `SampleDataSeeder` (opsional) mengisi data contoh 14 hari dan akun demo pengepul. Data contoh ditandai di layar dan bisa dihapus dengan `php artisan lapor:hapus-contoh`.
- Tanpa kunci AI, aplikasi tetap berjalan dengan pembaca pola. Tanpa token Fonnte (`LAPOR_WA_DRIVER=log`), balasan hanya dicatat, jadi gunakan `/coba`.
- Jadwal (cron `* * * * * php artisan schedule:run`): `lapor:ringkasan-pagi` pukul 06.00 dan `lapor:rapikan` setiap 30 menit.

**WhatsApp (Fonnte):** isi `LAPOR_WA_DRIVER=fonnte`, `FONNTE_TOKEN`, `FONNTE_DEVICE`, `LAPOR_WEBHOOK_SECRET`, lalu di Fonnte → Device → Webhook isi `https://<domain>/webhook/wa/<LAPOR_WEBHOOK_SECRET>`. Panduan server: `docs/DEPLOY.md`.

## Alamat aplikasi dan akun demo

- **Aplikasi:** https://harlok.arisantika.tech
- **Simulator peternak (tanpa login):** https://harlok.arisantika.tech/coba
- **Akun demo pengepul dan koordinator:** dikirim lewat kanal/form panitia, tidak disimpan di repositori ini.

## Panduan penggunaan

**Peternak**
1. Kirim `HARLOK` ke nomor Harlok. Nomor yang belum terdaftar hanya dilayani bila pesannya memuat kata kunci ini, sehingga chat pribadi lain ke nomor tersebut tidak dibalas bot.
2. Jawab nama, pilih nomor wilayah dan komoditas.
3. Setiap ada stok, kirim dengan bahasa sehari-hari, periksa ringkasannya, lalu balas `YA`.
4. Saat ada tawaran, balas `1` (terima), `2` (tolak), atau `3` (tawar balik).
5. Tanya apa saja soal harga, misalnya `harga cabai di Gianyar naik?`.

**Pengepul**
1. Masuk di `/masuk`, buka **Cari Stok**, pilih komoditas, lalu klik titik di peta.
2. Isi harga dan jumlah, lalu **Kirim tawaran**. Pantau di **Tawaran Saya**.

**Koordinator**
1. Verifikasi peternak baru.
2. Tinjau laporan janggal beserta penilaian AI, lalu **Tampilkan** atau **Tolak**.
3. Pantau panel AI, kelola akun pengepul, mitra, dan token API.

## API data harga

Semua endpoint membutuhkan `Authorization: Bearer <token>` dari koordinator (izin `prices:read`, maks. 60 permintaan per menit). Dokumentasi: `/dokumentasi-api`.

| Endpoint | Isi |
|---|---|
| `GET /api/v1/harga?komoditas=telur[&wilayah=tabanan]` | harga terbaru per wilayah |
| `GET /api/v1/harga/riwayat?komoditas=telur&wilayah=tabanan&hari=30` | riwayat harian (maks. 90 hari) |
| `GET /api/v1/komoditas`, `GET /api/v1/wilayah` | daftar kode |

API hanya mengeluarkan angka gabungan. Nama dan nomor peternak tidak pernah dibagikan.

## Keamanan dan privasi

- **Webhook:** alamat rahasia dicek dengan `hash_equals`, pesan grup dan ID privasi (`@lid`) diabaikan, pesan ganda ditolak, maksimal 12 pesan per menit per nomor.
- **Integritas harga:** rentang harga wajar, penahanan laporan menyimpang dengan penilaian AI, hanya peternak terverifikasi yang dihitung, konfirmasi `YA` sebelum disimpan.
- **Hak akses:** middleware peran (koordinator, pengepul, mitra). Pengepul hanya bisa mengubah tawarannya sendiri. Akun demo hanya bisa menawar stok contoh.
- **Kendali peternak:** `STOP` menghentikan semua pesan, `KELUAR` menghapus data peternak beserta riwayat chatnya.
- **Privasi:** papan publik, API, dan AI tidak menerima nama atau nomor peternak. Nomor peternak baru dibuka ke pembeli setelah sepakat.
- **Web:** CSRF, validasi input, pembatasan percobaan login, header keamanan, keluaran di-*escape*. Rahasia hanya di `.env` (lihat `.env.example`).
- **Jejak audit:** pendaftaran, laporan, verifikasi, tawaran, login, dan token tercatat bersama pelaku dan IP.

## Pengujian

```bash
php vendor/bin/pest
```

89 tes otomatis (341 asersi), juga dijalankan GitHub Actions setiap push. Cakupannya meliputi pembacaan pesan, pendaftaran, STOP/KELUAR, alur tawaran, keamanan webhook, hak akses, kebocoran data, API, dan **18 tes AI** dengan respons AI tiruan: pemahaman pesan bahasa Bali, data pribadi tidak terkirim ke AI, cadangan saat AI gagal atau jawabannya tidak valid, AI tidak bisa menerima tawaran sendiri, kuota, penjaga harga, dan ringkasan pasar.

## Keterbatasan saat ini

- Sebagian harga di papan masih **data contoh** untuk demonstrasi (ditandai di layar). Pengguna nyata masih sedikit.
- AI memakai API pihak ketiga yang berbayar per pemakaian dan butuh koneksi. Saat AI tidak tersedia, bot kembali ke pembaca pola yang lebih kaku.
- Pemahaman bahasa Bali terbatas pada kosakata perdagangan umum dan belum diuji luas dengan banyak peternak.
- Pesan suara dan foto belum dibaca. Hanya teks.
- Gateway WhatsApp memakai Fonnte (bukan WhatsApp Business API resmi), sehingga ada batas pesan dan risiko pemblokiran nomor.
- Cakupan baru 14 wilayah (Bali dan DI Yogyakarta) dan 5 komoditas. Verifikasi peternak masih manual oleh koordinator.
- Ringkasan pasar hanya merangkum data yang ada, bukan meramal harga.

## Sumber pihak ketiga

| Sumber | Lisensi | Kegunaan |
|---|---|---|
| Laravel 13, Laravel Sanctum | MIT | kerangka backend, token API |
| Anthropic PHP SDK | MIT | panggilan Claude API |
| Alpine.js, Tailwind CSS, Vite | MIT | antarmuka dan build |
| Leaflet | BSD-2-Clause | peta |
| OpenStreetMap | ODbL | ubin peta (© kontributor OpenStreetMap) |
| Chart.js | MIT | grafik harga |
| Pest, Pint | MIT | pengujian dan format kode |
| Bricolage Grotesque, Public Sans (lewat Bunny Fonts) | SIL OFL 1.1 | huruf |
| Fonnte | layanan berbayar | gateway WhatsApp |
| Claude API (Anthropic) / DeepSeek API | layanan berbayar | model AI di dalam produk |

Seluruh data harga berasal dari laporan pengguna Harlok dan data contoh buatan sendiri. Tidak ada dataset pihak ketiga.

## Tim

| Nama | Peran |
|---|---|
| I Dewa Made Arisantika | Pengembang tunggal: riset masalah, desain produk, backend, frontend, integrasi AI dan WhatsApp, deploy |

Pengembangan dibantu asisten AI untuk menulis kode (Claude Code dan Gemini). Rinciannya ada di [docs/DISCLOSURE.md](docs/DISCLOSURE.md).
