# Harlok

**Harga pasar yang adil, cukup lewat WhatsApp.**

Harlok adalah sistem web yang menghubungkan peternak dan petani kecil, pengepul, serta pihak lain (dinas, koperasi, aplikasi lain) dalam satu ekosistem data harga. Peternak cukup mengirim chat WhatsApp biasa seperti `telur 2 ton 26rb`. Pesan itu dibaca sistem, dikonfirmasi ulang, lalu berubah menjadi:

1. **harga rata-rata per wilayah** yang langsung dikirim balik ke peternak,
2. **titik stok di peta** yang bisa ditawar pengepul dari web,
3. **data terbuka lewat API** untuk dinas, koperasi, atau aplikasi lain.

Karya ini dibuat untuk Web Development Competition UINIC 8.0, tema *Engineering Interconnected Web Solutions to Empower Digital Ecosystems*, subtema **AI & Teknologi Digital** (SDG 1, 2, 8, 10).

---

## Daftar isi

- [Masalah yang diselesaikan](#masalah-yang-diselesaikan)
- [Fitur](#fitur)
- [Arsitektur](#arsitektur)
- [Menjalankan di komputer sendiri](#menjalankan-di-komputer-sendiri)
- [Konfigurasi WhatsApp (Fonnte)](#konfigurasi-whatsapp-fonnte)
- [Panduan penggunaan](#panduan-penggunaan)
- [API data harga](#api-data-harga)
- [Keamanan dan privasi](#keamanan-dan-privasi)
- [Pengujian](#pengujian)
- [Struktur kode](#struktur-kode)

## Masalah yang diselesaikan

- Peternak kecil tidak tahu harga pasar hari ini, sehingga posisi tawarnya lemah di depan pembeli.
- Aplikasi pertanian umumnya mewajibkan instal aplikasi, mendaftar, dan belajar menu baru. Banyak peternak, terutama yang sudah berumur, hanya memakai WhatsApp.
- Data harga tersebar di obrolan pribadi dan tidak pernah terkumpul menjadi informasi yang bisa dipakai bersama.

Harlok memindahkan "pintu masuk" ke aplikasi yang sudah dipakai peternak setiap hari, yaitu WhatsApp.

## Fitur

### Untuk peternak (cukup WhatsApp)
| Pesan | Hasil |
|---|---|
| pesan pertama apa pun | pendaftaran 3 langkah: nama, wilayah, komoditas (balas dengan angka) |
| `telur 2 ton 26rb`, `20 peti 390rb/peti`, `cabe rawit 3 kwintal 45.000` | dibaca menjadi jumlah (kg) dan harga per kg, lalu diminta konfirmasi **YA** / **UBAH** |
| `YA` | laporan disimpan, peternak dibalas harga rata-rata wilayahnya dan selisih harganya |
| `HARGA` / `harga cabai` | harga rata-rata per wilayah beserta naik/turunnya |
| `STOK`, `HABIS` | lihat stok aktif, tandai stok sudah terjual |
| `TAWARAN` | lihat tawaran pembeli yang belum dijawab |
| `1` / `2` / `3` | terima / tolak / tawar balik tawaran |
| `MULAI` / `STOP` | berlangganan ringkasan harga setiap pukul 06.00 |
| `BATAL`, `MENU` | batalkan langkah, tampilkan bantuan |

Pembacaan pesan memakai dua lapis:
1. **Pengurai pola** (cepat, gratis) untuk satuan ton, kwintal, kg, peti, tray, karung, ekor, dan penulisan harga seperti `26rb`, `26.500`, `26,5rb`, `rp26000`, `5.6k`.
2. **AI (Claude Haiku)** sebagai cadangan bila pola gagal. Keluaran AI dipaksa ke skema JSON, lalu dihitung ulang dengan aturan yang sama dan **tetap dikonfirmasi ke peternak** sebelum disimpan. Ada batas penggunaan per nomor per hari.

### Untuk pengepul (web)
- Peta stok (Bali dan DI Yogyakarta) dengan warna: sekitar rata-rata, di bawah rata-rata, di atas rata-rata, atau belum terverifikasi.
- Kirim tawaran. Sistem langsung menghitung selisihnya dari rata-rata wilayah, dan peternak mendapat **peringatan** bila tawaran lebih dari 10% di bawah pasar.
- Halaman **Tawaran Saya**: status menunggu, ditawar balik (setujui dari web), sepakat (kontak peternak terbuka), ditolak, dibatalkan.
- Opsional: notifikasi WhatsApp saat ada stok baru di wilayahnya.

### Untuk koordinator (admin)
- Verifikasi peternak. Hanya laporan dari peternak terverifikasi yang dihitung dalam rata-rata.
- Tinjau **laporan janggal**: harga yang menyimpang lebih dari 30% dari rata-rata ditahan sampai disetujui atau ditolak.
- Kelola akun pengepul dan mitra API, buat dan cabut token API.
- Log WhatsApp masuk/keluar dan **jejak audit** setiap perubahan penting.

### Untuk publik dan mitra
- **Papan harga** publik yang memperbarui diri tiap 12 detik, dilengkapi grafik 14 hari.
- **Simulator chat** (`/coba`): mencoba bot yang sama tanpa WhatsApp. Pesannya tidak pernah terkirim ke WhatsApp.
- **API harga** ber-token, **widget harga** yang bisa disematkan di situs koperasi/desa (`/widget/harga`), dan **data terbuka CSV** (`/data/harga.csv`). Spesifikasi lengkap: `docs/openapi.yaml`.

## Arsitektur

```
 Peternak (WhatsApp)                                    Pengepul (Web)
      │  "telur 2 ton 26rb"                                    ▲
      ▼                                                        │ stok & tawaran
 ┌──────────────┐  webhook   ┌──────────────────────────────┐  │
 │ Fonnte (WA)  │ ─────────▶ │ Laravel 13                   │──┤
 └──────────────┘            │  WhatsAppWebhookController   │  │
      ▲                      │   ├ cek rahasia, grup, ganda │  ▼
      │ balasan              │   └ batas pesan per nomor    │ Papan harga + peta (Alpine, Leaflet, Chart.js)
      │                      │  InboundProcessor (kunci per │
      │                      │   nomor) → ChatBot (state)   │──▶ API /api/v1 (Sanctum, token mitra)
      │                      │   ├ ReportParser (pola)      │     dinas · koperasi · aplikasi lain
      │                      │   └ AiReportParser (Claude)  │
      │                      │  ReportService · OfferService│
      └───────────────────── │  PriceBoard · Messenger      │
                             └──────────────┬───────────────┘
                                            ▼
                                  MySQL / MariaDB (SQLite saat tes)
```

Alur data utama:
1. Fonnte meneruskan pesan ke `POST /webhook/wa/{rahasia}`. Pesan disimpan di `inbound_messages` (ID unik dari Fonnte mencegah pesan ganda), lalu diproses **setelah respons dikirim** supaya webhook cepat.
2. `ChatBot` menjalankan **mesin status percakapan** per nomor (`conversations`): daftar → lapor → konfirmasi → menjawab tawaran → tawar balik.
3. `ReportService` menyimpan laporan, menutup stok lama, menandai laporan janggal, lalu `PriceBoard` menghitung ulang rata-rata, median, minimum, dan maksimum harian per komoditas per wilayah (`daily_prices`).
4. `OfferService` mengelola tawaran dengan transaksi database dan kunci baris (`lockForUpdate`), sehingga satu stok tidak bisa terjual dua kali.
5. Semua pesan keluar melewati `Messenger`, yang mencatat ke `outbound_messages`, memakai batas pesan harian, dan tidak pernah mengirim ke nomor simulator atau data contoh.

Tabel utama: `regions`, `commodities`, `farmers`, `conversations`, `inbound_messages`, `outbound_messages`, `stock_reports`, `offers`, `daily_prices`, `audit_logs`, `users`, `personal_access_tokens`.

## Menjalankan di komputer sendiri

Kebutuhan: PHP 8.3+ (ekstensi pdo_sqlite atau pdo_mysql, mbstring, intl), Composer 2, Node.js 20+.

```bash
git clone <repo> harlok && cd harlok
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan db:seed --class=SampleDataSeeder
php artisan lapor:admin admin@contoh.id "Koordinator"
php artisan serve
```

- `migrate --seed` mengisi 14 wilayah (Bali dan DI Yogyakarta) dan 5 komoditas.
- `SampleDataSeeder` (opsional) mengisi data contoh 14 hari dan akun demo `pengepul@contoh.id` (kata sandi dari `LAPOR_DEMO_PASSWORD`). Data contoh ditandai di layar dan dapat dihapus dengan `php artisan lapor:hapus-contoh`.
- `lapor:admin` mencetak kata sandi acak untuk akun koordinator.
- Tanpa token Fonnte (`LAPOR_WA_DRIVER=log`), semua balasan hanya dicatat. Gunakan halaman `/coba` untuk mencoba bot.

Jadwal (cron `* * * * * php artisan schedule:run`):
- `lapor:ringkasan-pagi` setiap 06.00: ringkasan harga ke peternak yang berlangganan.
- `lapor:rapikan` setiap 30 menit: menutup laporan dan stok yang kedaluwarsa.

## Konfigurasi WhatsApp (Fonnte)

1. Daftarkan nomor WhatsApp khusus di [fonnte.com](https://fonnte.com), lalu pindai QR.
2. Isi `.env`:
   ```
   LAPOR_WA_DRIVER=fonnte
   FONNTE_TOKEN=<token device>
   FONNTE_DEVICE=<nomor device, mis. 62812xxxx>
   LAPOR_WEBHOOK_SECRET=<teks acak panjang>
   LAPOR_DAILY_OUTBOUND_LIMIT=800
   ```
3. Di Fonnte → Device → Webhook, isi `https://<domain>/webhook/wa/<LAPOR_WEBHOOK_SECRET>`.
4. (Opsional) `ANTHROPIC_API_KEY=` untuk cadangan pembacaan pesan dengan AI. Model default `claude-haiku-5-5`.

## Panduan penggunaan

**Peternak**
1. Simpan nomor Harlok, lalu kirim `halo`.
2. Jawab nama, pilih nomor wilayah, pilih nomor komoditas.
3. Setiap ada stok, kirim misalnya `telur 2 ton 26rb`, periksa ringkasannya, lalu balas `YA`.
4. Saat ada tawaran, balas `1` (terima), `2` (tolak), atau `3` (tawar balik, lalu tulis harga).
5. Ketik `HABIS` bila stok sudah terjual di luar aplikasi.

**Pengepul**
1. Masuk di `/masuk` dengan akun dari koordinator.
2. Buka **Cari Stok**, pilih komoditas, klik titik di peta atau baris di daftar.
3. Isi harga dan jumlah, lalu klik **Kirim tawaran**. Perhatikan keterangan selisih dari rata-rata.
4. Pantau **Tawaran Saya**. Bila peternak menawar balik, klik **Setujui**. Setelah sepakat, kontak peternak tampil.

**Koordinator**
1. Masuk, lalu buka **Koordinator**.
2. Verifikasi peternak baru setelah memastikan identitasnya (misalnya lewat telepon atau kelompok ternak).
3. Tinjau laporan janggal: **Tampilkan** atau **Tolak**.
4. Di **Pengepul & Mitra API**, buat akun pengepul atau mitra, lalu buat token API untuk mitra.

## API data harga

Semua endpoint membutuhkan header `Authorization: Bearer <token>` dari koordinator (izin `prices:read`, berlaku 6 bulan, maks. 60 permintaan per menit). Dokumentasi lengkap tersedia di halaman `/dokumentasi-api`.

| Endpoint | Isi |
|---|---|
| `GET /api/v1/harga?komoditas=telur[&wilayah=tabanan]` | harga terbaru per wilayah (rata-rata, terendah, tertinggi, jumlah laporan, perubahan) |
| `GET /api/v1/harga/riwayat?komoditas=telur&wilayah=tabanan&hari=30` | riwayat harian (maks. 90 hari) |
| `GET /api/v1/komoditas` | daftar kode komoditas |
| `GET /api/v1/wilayah` | daftar wilayah dan koordinat |

API hanya mengeluarkan **angka gabungan**. Nama dan nomor peternak tidak pernah dibagikan.

## Keamanan dan privasi

- **Webhook**: alamat rahasia dicek dengan `hash_equals`, nomor device dicocokkan, pesan grup diabaikan, pesan ganda ditolak lewat ID unik, maksimal 12 pesan per menit per nomor.
- **Identitas**: peternak dikenali dari nomor WhatsApp pengirim, bukan dari isi pesan. Hasil AI tidak pernah menentukan siapa pengirimnya.
- **Integritas harga**: rentang harga wajar per komoditas, penahanan laporan yang menyimpang lebih dari 30%, hanya peternak terverifikasi yang dihitung dalam rata-rata, dan konfirmasi YA sebelum data disimpan.
- **Hak akses**: middleware peran (koordinator, pengepul, mitra). Mitra tidak bisa masuk ke web. Pengepul hanya bisa mengubah tawarannya sendiri. Akun demo hanya bisa menawar stok contoh atau simulator.
- **Privasi**: papan publik dan API tidak memuat nama atau nomor. Nomor peternak baru dibuka ke pembeli setelah sepakat.
- **Web**: CSRF, validasi seluruh input, pembatasan percobaan login (5 kali, lalu kunci 5 menit), regenerasi sesi, header keamanan (X-Frame-Options, nosniff, Referrer-Policy, Permissions-Policy, HSTS di produksi), keluaran Blade dan pesan chat di-*escape*.
- **Kuota**: batas pesan WhatsApp harian dan batas pemakaian AI per nomor.
- **Jejak audit**: pendaftaran, laporan, verifikasi, tawaran, login, dan token tercatat bersama pelaku dan IP.

## Pengujian

```bash
php vendor/bin/pest
```

62 tes otomatis (237 asersi, juga dijalankan GitHub Actions setiap push), meliputi: pembacaan 9 variasi penulisan pesan, pendaftaran, konfirmasi laporan, penahanan harga janggal, peternak belum terverifikasi tidak dihitung, alur tawaran (terima, tolak, tawar balik, pengepul lain tidak bisa mengubah), batas akun demo, keamanan webhook (rahasia, grup, ganda, banjir pesan), hak akses panel, kebocoran data di papan publik dan API, validasi API, widget dan data terbuka, serta regresi hasil audit keamanan (simulator terisolasi, rata-rata satu laporan per peternak, kuota AI, batas webhook per nomor, pembersihan data simulator).

## Struktur kode

```
app/Chat/            ChatBot (mesin status percakapan), ReportParser, AiReportParser, ParsedReport
app/Services/        Messenger, InboundProcessor, ReportService, OfferService, PriceBoard, BoardData
app/Http/Controllers WhatsAppWebhook, Simulator, Board, Buyer, Admin, Auth, Api/PriceApi
app/Models/          Farmer, StockReport, Offer, DailyPrice, Conversation, InboundMessage, OutboundMessage, AuditLog, Region, Commodity, User
resources/views/     board (papan harga), simulator, buyer/*, admin/*, api-docs
resources/js/app.js  komponen Alpine: papan (peta, grafik, tawaran) dan simulator
routes/console.php   lapor:ringkasan-pagi, lapor:rapikan, lapor:admin, lapor:hapus-contoh
tests/Feature/       tes Pest
```

Teknologi: Laravel 13, PHP 8.5, Sanctum, Anthropic PHP SDK, Blade + Alpine.js, Tailwind CSS 4, Leaflet + OpenStreetMap, Chart.js, Pest.
