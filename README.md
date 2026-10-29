# Harlok

Harlok mengumpulkan harga jual dari peternak dan petani kecil lewat WhatsApp, lalu mengolahnya menjadi harga rata-rata per wilayah yang bisa dilihat siapa saja.

Peternak tidak perlu memasang aplikasi. Mereka cukup chat seperti biasa, misalnya `telur 2 ton 26rb`, `taluh 1 ton, 25 ribu a kilo`, atau bertanya `ajin tabia jani kuda?`. Asisten AI di dalam Harlok memahami maksudnya, menanyakan ulang supaya tidak salah catat, lalu membalas dengan harga rata-rata di wilayah si peternak. Laporan yang sama muncul di peta untuk pengepul, dan angka gabungannya bisa diambil dinas atau koperasi lewat API.

Aplikasinya bisa dibuka di https://harlok.arisantika.tech. Kalau tidak mau memakai WhatsApp, botnya bisa dicoba langsung di https://harlok.arisantika.tech/coba. Penjelasan soal teknologi dan AI yang dipakai ada di [docs/DISCLOSURE.md](docs/DISCLOSURE.md).

## Masalah yang mau diselesaikan

Saya tumbuh di keluarga peternak ayam petelur di Bali. Pertanyaan yang terus berulang adalah harga telur hari ini berapa, dan apakah tawaran pengepul ini wajar. Jawabannya biasanya dicari dengan bertanya ke beberapa kenalan. Peternak yang tidak punya banyak kenalan akhirnya menerima harga apa saja yang ditawarkan.

Aplikasi pertanian yang ada umumnya meminta peternak memasang aplikasi, membuat akun, dan belajar menu baru. Kenyataannya banyak peternak, terutama yang sudah berumur, hanya memakai WhatsApp. Tulisannya penuh singkatan dan sering bercampur bahasa Bali. Akibatnya data harga hanya beredar di obrolan pribadi dan tidak pernah terkumpul jadi informasi bersama. Pengepul juga sulit menemukan stok terdekat, dan dinas tidak punya data harga di tingkat petani yang cepat.

Harlok dibuat untuk empat kelompok: peternak dan petani kecil yang ingin tahu harga wajar sebelum menjual, pengepul yang mencari stok, koordinator kelompok ternak atau koperasi yang menjaga datanya tetap jujur, dan pihak luar seperti dinas atau situs desa yang butuh harga acuan.

## Yang bisa dilakukan

### Peternak, lewat WhatsApp

Pendaftaran dimulai dengan mengirim `HARLOK`, lalu menjawab nama, wilayah, dan komoditas. Setelah itu peternak bisa langsung menulis stok dan harganya dengan bahasa sehari-hari. Harlok membaca jumlah dan harga per kilogram (termasuk satuan seperti peti, tray, kwintal, atau ton), menampilkan apa yang dicatat, dan baru menyimpannya setelah peternak membalas `YA`. Balasannya berisi harga rata-rata di wilayah itu dan seberapa jauh harga si peternak dari rata-rata.

Pertanyaan bebas seperti `kapan sebaiknya saya jual?` atau `harga cabai di Gianyar naik?` dijawab singkat oleh AI dari data laporan Harlok, dan selalu diberi keterangan bahwa jawabannya dibuat AI.

Saat ada tawaran dari pengepul, peternak menerima pesan berisi harga tawaran dan perbandingannya dengan harga pasar. Peternak membalas `1` untuk terima, `2` untuk tolak, atau `3` untuk tawar balik. Kalimat seperti `oke saya ambil` atau `26.5 lah` juga dipahami, tetapi menerima tawaran tetap harus dikonfirmasi dengan balasan `1`.

Kata perintah lain yang bisa dipakai: `HARGA`, `STOK`, `HABIS`, `TAWARAN`, `MULAI` untuk ringkasan harga setiap pagi jam 06.00, `STOP` agar bot berhenti membalas sampai peternak mengetik `HARLOK` lagi, dan `KELUAR` untuk menghapus semua datanya.

### Pengepul, lewat web

Pengepul masuk ke web dan melihat stok di peta. Warna titiknya menunjukkan apakah harga stok itu dekat, di bawah, atau di atas rata-rata wilayah, dan abu-abu kalau pelapornya belum diverifikasi. Dari situ pengepul bisa mengirim tawaran. Status tawarannya bisa dipantau di halaman Tawaran Saya. Nomor peternak baru dibuka setelah kedua pihak sepakat.

### Koordinator

Koordinator memverifikasi peternak baru. Hanya laporan dari peternak yang sudah diverifikasi yang ikut dihitung di rata-rata. Laporan yang harganya terlalu jauh dari pasar ditahan dulu, dan AI memberi penilaian apakah itu salah ketik, salah satuan, atau patut dicurigai. Keputusan akhirnya tetap di tangan koordinator. Di dasbor juga ada catatan pemakaian AI (jumlah panggilan, yang berhasil, yang ditangani cadangan, dan waktu jawab), pengelolaan akun pengepul dan mitra API, log WhatsApp, serta jejak audit.

### Publik dan mitra

Halaman depan menampilkan daftar harga hari ini per wilayah, grafik 14 hari, dan ringkasan singkat pasar. Datanya diperbarui sendiri setiap 12 detik. Dinas atau koperasi bisa memasang widget di situs mereka (`/widget/harga`), mengunduh CSV (`/data/harga.csv`), atau memakai API dengan token. Spesifikasi API ada di `docs/openapi.yaml`.

## Teknologi

Backend memakai PHP 8.3 dan Laravel 13, dengan Laravel Sanctum untuk token API. Tampilan dibuat dengan Blade, Alpine.js, dan Tailwind CSS 4, peta memakai Leaflet dengan ubin OpenStreetMap, grafiknya Chart.js, dan semuanya dibangun dengan Vite. Di server dipakai MySQL/MariaDB, sedangkan tes memakai SQLite.

Untuk AI, Harlok bisa memakai Claude Haiku 5.5 lewat Anthropic PHP SDK atau model yang kompatibel dengan API OpenAI seperti DeepSeek. Pilihannya diatur di `.env`. Pesan WhatsApp masuk dan keluar lewat Fonnte. Aplikasi berjalan di hosting cPanel dengan cron untuk tugas terjadwal, dan tesnya ditulis dengan Pest serta dijalankan di GitHub Actions.

## Bagaimana AI dipakai

AI dipakai di tiga tempat. Semuanya lewat satu pintu, `app/Ai/AiGateway.php`, yang memilih penyedia, membatasi kuota, memeriksa jawaban, dan mencatat setiap panggilan.

Yang pertama adalah asisten WhatsApp (`app/Chat/AiAssistant.php`). Pesan peternak tidak punya pola tetap: singkatan, salah ketik, bahasa Bali, satuan lokal. Pembaca berbasis aturan tidak mungkin menangkap semua variasinya, apalagi menjawab pertanyaan. AI menerima teks pesan, daftar komoditas dan wilayah, ringkasan harga, serta jumlah stok dan tawaran si pengirim. Jawabannya berupa JSON berisi maksud pesan (12 kemungkinan), komoditas, jumlah, satuan, harga, wilayah, dan jawaban singkat kalau pesannya berupa pertanyaan.

Yang kedua adalah penjaga harga (`app/Services/AiPriceGuard.php`). Aturan statistik menandai laporan yang menyimpang lebih dari 30% dari rata-rata. Untuk laporan seperti itu, AI menilai pesan aslinya (dengan deretan angka panjang disamarkan) dan memberi dugaan apakah itu wajar, salah ketik, salah satuan, atau mencurigakan, lengkap dengan dugaan harga yang dimaksud dan saran untuk peternak.

Yang ketiga adalah ringkasan pasar (`app/Services/MarketBrief.php`). AI hanya menerima angka gabungan: rata-rata per wilayah dan tren 7 hari. Hasilnya dua kalimat yang dipakai di halaman depan, di balasan perintah `HARGA`, dan di ringkasan pagi.

Model kecil dipilih karena tugasnya pendek dan terstruktur, jadi murah dan cukup cepat untuk chat. Supaya AI tetap aman dipakai:

- Jawaban AI diperiksa terhadap skema. Kalau ada kunci yang hilang atau nilai di luar pilihan, jawabannya dibuang.
- AI tidak pernah memutuskan sendiri. Laporan baru tersimpan setelah peternak membalas `YA`, tawaran hanya diterima lewat balasan `1`, dan laporan janggal tetap diputuskan koordinator.
- Selalu ada cadangan. Kalau AI mati, kuota habis, waktunya lewat 12 detik, permintaannya ditolak, atau jawabannya tidak valid, bot kembali ke pembaca aturan (`ReportParser`) dan ringkasan pasar memakai versi aturan.
- Nama dan nomor peternak tidak pernah dikirim ke AI. Tabel `ai_calls` hanya mencatat jenis, status, model, dan lama panggilan, tanpa isi pesan.
- Ada kuota per nomor (30 per hari), kuota harian untuk semua nomor, dan kuota terpisah untuk simulator.
- Isi pesan diperlakukan sebagai data, bukan perintah. Jawaban bebas dipotong, tautan dibuang, dan diberi keterangan bahwa jawaban itu dibuat AI.

Pengaturannya di `.env`:

```
LAPOR_AI_DRIVER=anthropic        # atau openai
LAPOR_AI_KEY=<kunci API>
LAPOR_AI_MODEL=                  # kosong = claude-haiku-5-5 / deepseek-chat
LAPOR_AI_BASE_URL=               # hanya untuk openai, bawaan https://api.deepseek.com
```

## Alur data

```
 Peternak (WhatsApp)                                        Pengepul / Publik (Web)
      │ "taluh 1 ton, 25 ribu a kilo"                               ▲
      ▼                                                             │
 ┌────────────┐ webhook ┌────────────────────────────────────────┐  │ halaman harga, peta, tawaran
 │ Fonnte (WA)│───────▶ │ Laravel 13                             │──┤ (Alpine, Leaflet, Chart.js)
 └────────────┘         │  WhatsAppWebhookController             │  │
      ▲                 │   └ cek rahasia, grup, ganda, banjir   │  ▼
      │ balasan         │  InboundProcessor → ChatBot (status)   │ API /api/v1, widget, CSV
      │                 │   ├ AiAssistant ──┐                    │
      │                 │   └ ReportParser  │ cadangan           │
      │                 │  ReportService → AiPriceGuard ──┐      │
      │                 │  PriceBoard → MarketBrief ──────┤      │
      └──────────────── │  OfferService, Messenger        ▼      │
                        │                    AiGateway ──────────┼──▶ Claude API / DeepSeek API
                        └───────────────┬────────────────────────┘
                                        ▼
                         MySQL/MariaDB: farmers, stock_reports, offers,
                         daily_prices, ai_calls, audit_logs, ...
```

Fonnte meneruskan setiap pesan ke `POST /webhook/wa/{rahasia}`. Pesan disimpan dulu di `inbound_messages`, lalu diproses setelah respons dikirim, dengan kunci per nomor supaya pesan dari satu orang tidak diproses bersamaan. `ChatBot` menyimpan status percakapan tiap nomor. Perintah yang pasti seperti `YA`, `1`, atau `HARGA` ditangani langsung, sedangkan pesan bebas dikirim ke `AiAssistant` dan hasilnya dijalankan oleh kode yang sama dengan perintah biasa.

`ReportService` menyimpan laporan dan menahan yang menyimpang. `PriceBoard` menghitung rata-rata, median, minimum, dan maksimum harian per komoditas dan wilayah. `OfferService` mengurus tawaran di dalam transaksi database dengan `lockForUpdate`, dan `Messenger` mengirim semua pesan keluar dengan batas harian. Diagram arsitektur, ERD, dan alur pengguna ada di `docs/diagram/`.

## Menjalankan di komputer sendiri

Yang dibutuhkan: PHP 8.3 atau lebih baru (dengan pdo_sqlite atau pdo_mysql, mbstring, intl), Composer 2, dan Node.js 20 atau lebih baru.

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

`migrate --seed` mengisi 14 wilayah di Bali dan DI Yogyakarta serta 5 komoditas beserta sebutan lokalnya, misalnya taluh, tabia, dan baas. `SampleDataSeeder` boleh dilewati. Isinya data contoh 14 hari dan akun demo pengepul, yang ditandai di layar dan bisa dihapus dengan `php artisan lapor:hapus-contoh`.

Tanpa kunci AI, aplikasi tetap jalan memakai pembaca aturan. Tanpa token Fonnte (`LAPOR_WA_DRIVER=log`), balasan bot hanya dicatat di log, jadi coba lewat `/coba`. Tugas terjadwal butuh cron `* * * * * php artisan schedule:run`, yang menjalankan `lapor:ringkasan-pagi` jam 06.00 dan `lapor:rapikan` setiap 30 menit.

Untuk WhatsApp sungguhan, isi `LAPOR_WA_DRIVER=fonnte`, `FONNTE_TOKEN`, `FONNTE_DEVICE`, dan `LAPOR_WEBHOOK_SECRET`, lalu di pengaturan webhook perangkat Fonnte isi `https://<domain>/webhook/wa/<LAPOR_WEBHOOK_SECRET>`. Panduan memasang di server ada di `docs/DEPLOY.md`.

## Alamat dan akun demo

Aplikasi: https://harlok.arisantika.tech. Simulator peternak tanpa login: https://harlok.arisantika.tech/coba. Akun demo pengepul dan koordinator dikirim lewat kanal panitia dan tidak disimpan di repositori ini.

## Cara memakai

Peternak mengirim `HARLOK` ke nomor Harlok. Nomor yang belum terdaftar hanya dilayani kalau pesannya memuat kata itu, supaya chat pribadi lain ke nomor tersebut tidak dibalas bot. Setelah menjawab nama, wilayah, dan komoditas, peternak tinggal mengirim stok setiap kali ada, memeriksa catatan dari bot, lalu membalas `YA`. Tawaran dijawab dengan `1`, `2`, atau `3`, dan pertanyaan soal harga boleh ditulis bebas.

Pengepul masuk di `/masuk`, membuka Cari Stok, memilih komoditas, lalu mengklik titik di peta untuk mengirim tawaran. Perkembangannya bisa dilihat di Tawaran Saya.

Koordinator memverifikasi peternak baru, meninjau laporan janggal beserta penilaian AI lalu memilih Tampilkan atau Tolak, dan mengelola akun pengepul, mitra, serta token API.

## API data harga

Setiap permintaan butuh `Authorization: Bearer <token>` yang dibuat koordinator. Token hanya bisa membaca harga (`prices:read`) dan dibatasi 60 permintaan per menit. Ada tiga endpoint:

- `GET /api/v1/harga?komoditas=telur[&wilayah=tabanan]` untuk harga terbaru per wilayah,
- `GET /api/v1/harga/riwayat?komoditas=telur&wilayah=tabanan&hari=30` untuk riwayat harian sampai 90 hari,
- `GET /api/v1/komoditas` dan `GET /api/v1/wilayah` untuk daftar kodenya.

API hanya mengeluarkan angka gabungan. Nama dan nomor peternak tidak pernah ikut. Penjelasan lengkap ada di halaman `/dokumentasi-api`.

## Keamanan dan privasi

Alamat webhook memakai rahasia yang dicek dengan `hash_equals`. Pesan dari grup dan ID privasi (`@lid`) diabaikan, pesan ganda ditolak, dan satu nomor dibatasi 12 pesan per menit.

Supaya harga acuan tidak gampang dirusak, ada rentang harga wajar per komoditas, laporan menyimpang ditahan untuk dicek, hanya peternak terverifikasi yang dihitung, dan setiap laporan harus dikonfirmasi `YA` sebelum disimpan.

Hak akses dibagi per peran: koordinator, pengepul, dan mitra. Pengepul hanya bisa mengubah tawarannya sendiri, dan akun demo hanya bisa menawar stok contoh. Peternak bisa menghentikan semua pesan dengan `STOP` atau menghapus datanya beserta riwayat chat dengan `KELUAR`. Halaman publik, API, dan AI tidak pernah menerima nama atau nomor peternak.

Di sisi web ada CSRF, validasi input, pembatasan percobaan login, header keamanan, dan keluaran yang di-escape. Semua rahasia hanya disimpan di `.env` (contohnya di `.env.example`). Pendaftaran, laporan, verifikasi, tawaran, login, dan pembuatan token dicatat di jejak audit bersama pelaku dan alamat IP-nya.

## Pengujian

```bash
php vendor/bin/pest
```

Ada 89 tes otomatis dengan 341 asersi, dan semuanya juga dijalankan GitHub Actions setiap kali ada push. Tes ini mencakup pembacaan pesan, pendaftaran, STOP dan KELUAR, alur tawaran, keamanan webhook, hak akses, kebocoran data, dan API. Delapan belas di antaranya khusus untuk AI, memakai respons AI tiruan: memahami pesan berbahasa Bali, memastikan data pribadi tidak terkirim, kembali ke cadangan saat AI gagal atau jawabannya tidak valid, memastikan AI tidak bisa menerima tawaran sendiri, kuota, penjaga harga, dan ringkasan pasar.

## Keterbatasan

Sebagian harga di halaman depan masih data contoh untuk demonstrasi dan ditandai di layar. Pengguna nyatanya masih sedikit.

AI memakai layanan pihak ketiga yang dibayar per pemakaian dan butuh koneksi. Kalau AI tidak tersedia, bot kembali ke pembaca aturan yang lebih kaku. Pemahaman bahasa Bali baru sebatas kosakata jual beli sehari-hari dan belum diuji dengan banyak peternak. Pesan suara dan foto belum bisa dibaca. Ringkasan pasar hanya merangkum data yang ada, bukan meramal harga.

Gateway WhatsApp-nya Fonnte, bukan WhatsApp Business API resmi, jadi ada batas jumlah pesan dan risiko nomor diblokir. Wilayahnya baru 14 kabupaten/kota di Bali dan DI Yogyakarta dengan 5 komoditas, dan verifikasi peternak masih dilakukan manual oleh koordinator.

## Sumber pihak ketiga

- Laravel 13 dan Laravel Sanctum (MIT) untuk backend dan token API
- Anthropic PHP SDK (MIT) untuk memanggil Claude API
- Alpine.js, Tailwind CSS, dan Vite (MIT) untuk antarmuka dan build
- Leaflet (BSD-2-Clause) untuk peta, dengan ubin © kontributor OpenStreetMap (ODbL)
- Chart.js (MIT) untuk grafik harga
- Pest dan Pint (MIT) untuk pengujian dan format kode
- Huruf Atkinson Hyperlegible (SIL OFL 1.1) lewat Bunny Fonts
- Fonnte (layanan berbayar) sebagai gateway WhatsApp
- Claude API dari Anthropic atau DeepSeek API (layanan berbayar) sebagai model AI

Semua data harga berasal dari laporan pengguna Harlok dan data contoh yang dibuat sendiri. Tidak ada dataset pihak ketiga.

## Tim

Harlok dikerjakan sendiri oleh I Dewa Made Arisantika, mulai dari riset masalah, desain produk, backend, frontend, integrasi AI dan WhatsApp, sampai deploy. Selama pengembangan saya dibantu asisten AI untuk menulis kode (Claude Code dan Gemini). Rinciannya ada di [docs/DISCLOSURE.md](docs/DISCLOSURE.md).
