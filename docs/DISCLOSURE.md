# AI & Technology Disclosure: Harlok

| Komponen | Isi |
|---|---|
| Nama produk | Harlok |
| Framework / Frontend | Blade, Alpine.js 3, Tailwind CSS 4, Leaflet 1.9, Chart.js 4, Vite |
| Backend / Runtime | PHP 8.3+, Laravel 13, Laravel Sanctum |
| Database / Storage | MySQL/MariaDB di server, SQLite untuk pengujian |
| Hosting / Deployment | cPanel Rapidplex (LiteSpeed, MariaDB, cron), domain `harlok.arisantika.tech` lewat Cloudflare DNS |
| AI Model / API / Method | Claude Haiku 5.5 lewat Anthropic PHP SDK (bawaan), atau model yang kompatibel dengan API OpenAI seperti DeepSeek `deepseek-chat` lewat HTTP. Keduanya diminta menjawab dalam JSON yang kemudian diperiksa server. Penyedia yang aktif di server saat penjurian: [diisi sebelum submission] |
| Peran AI dalam produk | Memahami pesan WhatsApp peternak yang ditulis bebas (termasuk singkatan dan bahasa Bali) dan menjawab pertanyaan harga dari data Harlok; menilai laporan yang harganya janggal untuk koordinator; dan merangkum harga per wilayah untuk halaman depan, perintah HARGA, dan ringkasan pagi. Penjelasan lengkapnya ada di README bagian "Bagaimana AI dipakai" |
| Dataset / Data Source | Laporan harga dari pengguna Harlok lewat WhatsApp dan simulator. Data contoh 14 hari dibuat sendiri oleh `SampleDataSeeder` dan ditandai di layar. Daftar 14 wilayah (Bali dan DIY) dan komoditas disusun sendiri. Tidak ada dataset pihak ketiga |
| Open-source libraries | Laravel, Sanctum, Anthropic PHP SDK, Alpine.js, Tailwind CSS, Leaflet, Chart.js, Vite, Pest, Pint (daftar dan lisensinya di README bagian "Sumber pihak ketiga") |
| Template / Boilerplate | Kerangka standar `laravel/laravel` 13. Tidak memakai template UI berbayar. Tampilan dibuat sendiri |
| AI Coding Assistant | Claude Code (Anthropic) membantu menulis dan mereview sebagian besar kode, tes, dan dokumentasi, dengan arahan, pengujian, dan keputusan dari pengembang. Gemini (Google, lewat Antigravity) membantu menyusun draf proposal. Seluruh kode diuji dengan 89 tes otomatis dan dipahami pengembang |
| Pre-existing Components | Lihat bagian di bawah |
| Third-party Assets / Credits | Ubin peta © kontributor OpenStreetMap (ODbL). Huruf Atkinson Hyperlegible (SIL OFL) lewat Bunny Fonts. Gateway WhatsApp Fonnte. Logo Harlok dibuat sendiri |

## Yang sudah ada sebelum kompetisi dan yang dibuat untuk kompetisi

Harlok mulai dibangun pada 28 Oktober 2026 dengan nama awal "Juru Harga". Awalnya disiapkan untuk lomba Web Development UINIC 8.0, tetapi tidak jadi diikutsertakan di lomba itu maupun lomba lain, dan belum pernah dijual. Riwayat lengkapnya bisa dicek di riwayat commit GitHub.

Sampai commit `e94b662` (29 Oktober 2026), Harlok sudah punya bot WhatsApp lewat Fonnte untuk pendaftaran, lapor stok dengan pembaca berbasis aturan, konfirmasi YA/UBAH, serta perintah HARGA, STOK, HABIS, TAWARAN, STOP, dan KELUAR. Sudah ada juga halaman harga publik, peta stok, grafik, alur tawaran pengepul, panel koordinator, API harga, widget, CSV, dan simulator `/coba`. Waktu itu AI hanya cadangan opsional ketika pembaca aturan gagal, dan tidak aktif di server.

Mulai commit `9a1faf1`, bagian berikut dibuat untuk AI Web Innovation Challenge:

- Gerbang AI yang bisa memakai beberapa penyedia (`app/Ai/`), lengkap dengan pemeriksaan skema, kuota per nomor, harian, dan simulator, log `ai_calls` tanpa isi pesan, serta penanganan kegagalan.
- Asisten AI WhatsApp (`app/Chat/AiAssistant.php`) yang kini menjadi jalur utama untuk pesan bebas: 12 jenis maksud, bahasa Bali, jawaban pertanyaan pasar dari data, serta tawar balik dan jawaban tawaran yang tetap meminta konfirmasi.
- Penjaga harga AI (`app/Services/AiPriceGuard.php`) dan tampilannya di panel koordinator.
- Ringkasan pasar AI (`app/Services/MarketBrief.php`) di halaman depan, perintah HARGA, dan ringkasan pagi, dengan versi cadangan berbasis aturan.
- Catatan pemakaian AI di dasbor koordinator, contoh chat bahasa Bali di simulator, 18 tes AI, tampilan baru, README, dan dokumen ini.

## Pernyataan

Kami menyatakan bahwa informasi di atas benar dan lengkap. Kami bertanggung jawab atas penggunaan teknologi, source code, data, library, API, model AI, dan aset yang digunakan pada produk.
