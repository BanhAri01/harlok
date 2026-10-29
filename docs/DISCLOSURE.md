# AI & Technology Disclosure: Harlok

| Komponen | Isi |
|---|---|
| Nama produk | Harlok |
| Framework / Frontend | Blade, Alpine.js 3, Tailwind CSS 4, Leaflet 1.9, Chart.js 4, Vite |
| Backend / Runtime | PHP 8.3+, Laravel 13, Laravel Sanctum |
| Database / Storage | MySQL/MariaDB (produksi), SQLite (pengujian) |
| Hosting / Deployment | cPanel Rapidplex (LiteSpeed, MariaDB, cron), domain `harlok.arisantika.tech` lewat Cloudflare DNS |
| AI Model / API / Method | Claude Haiku 5.5 lewat Anthropic PHP SDK (bawaan), atau model kompatibel OpenAI seperti DeepSeek `deepseek-chat` lewat HTTP. Keduanya memakai keluaran JSON terstruktur yang divalidasi server. Penyedia yang aktif di server saat penjurian: **[diisi sebelum submission]** |
| Peran AI dalam produk | (1) Asisten WhatsApp: memahami pesan bebas peternak, termasuk singkatan dan bahasa Bali, menjadi 12 jenis maksud beserta komoditas, jumlah, harga, dan wilayah, serta menjawab pertanyaan pasar dari data Harlok. (2) Penjaga harga: menilai laporan yang ditandai janggal (salah ketik, salah satuan, atau mencurigakan) untuk koordinator. (3) Ringkasan pasar: merangkum harga per wilayah untuk papan publik, perintah HARGA, dan ringkasan pagi. Rinciannya ada di README bagian *Implementasi AI* |
| Dataset / Data Source | Laporan harga dari pengguna Harlok lewat WhatsApp dan simulator. Data contoh 14 hari dibuat sendiri oleh `SampleDataSeeder` dan ditandai di layar. Daftar wilayah (14 kabupaten/kota Bali dan DIY) dan komoditas disusun sendiri. Tidak ada dataset pihak ketiga |
| Open-source libraries | Laravel, Sanctum, Anthropic PHP SDK, Alpine.js, Tailwind CSS, Leaflet, Chart.js, Vite, Pest, Pint (lihat README bagian *Sumber pihak ketiga*) |
| Template / Boilerplate | Kerangka standar `laravel/laravel` 13. Tidak memakai template UI berbayar. Desain antarmuka dibuat sendiri |
| AI Coding Assistant | **Claude Code** (Anthropic) untuk menulis dan mereview sebagian besar kode, tes, dan dokumentasi atas arahan, pengujian, dan keputusan pengembang. **Gemini** (Google, lewat Antigravity) untuk membantu menyusun draf proposal. Seluruh kode diuji (89 tes otomatis) dan dipahami pengembang |
| Pre-existing Components | Lihat bagian di bawah |
| Third-party Assets / Credits | Ubin peta © kontributor OpenStreetMap (ODbL). Huruf Bricolage Grotesque dan Public Sans (SIL OFL) lewat Bunny Fonts. Gateway WhatsApp Fonnte. Logo Harlok dibuat sendiri |

## Bagian yang sudah ada sebelum kompetisi dan yang dikembangkan untuk kompetisi

Harlok mulai dibangun pada **28 Oktober 2026** (nama awal "Juru Harga"). Awalnya disiapkan untuk lomba Web Development UINIC 8.0, tetapi **tidak jadi diikutsertakan** di lomba tersebut maupun lomba lain. Harlok juga belum dikomersialkan. Riwayat lengkapnya bisa dicek di riwayat commit GitHub.

**Sudah ada sebelumnya** (sampai commit `e94b662`, 29 Oktober 2026):
- Bot WhatsApp lewat Fonnte: pendaftaran, lapor stok dengan pembaca pola, konfirmasi YA/UBAH, HARGA, STOK, HABIS, TAWARAN, STOP, KELUAR.
- Papan harga publik, peta stok, grafik, alur tawaran pengepul, panel koordinator, API harga, widget, CSV, simulator `/coba`.
- AI hanya sebagai **cadangan opsional** saat pembaca pola gagal membaca laporan, dan tidak aktif di server.

**Dikembangkan untuk AI Web Innovation Challenge** (mulai commit `9a1faf1`):
- Gerbang AI multi-penyedia (`app/Ai/`): Anthropic atau API kompatibel OpenAI, validasi skema, kuota per nomor/global/simulator, log `ai_calls` tanpa isi pesan, dan penanganan kegagalan.
- **Asisten AI WhatsApp** (`app/Chat/AiAssistant.php`) yang menjadi jalur utama untuk pesan bebas: 12 maksud, bahasa Bali, jawaban pertanyaan pasar yang berdasar data, serta tawar balik dan jawaban tawaran yang tetap meminta konfirmasi.
- **Penjaga harga AI** (`app/Services/AiPriceGuard.php`) dan tampilannya di panel koordinator.
- **Ringkasan pasar AI** (`app/Services/MarketBrief.php`) di papan publik, perintah HARGA, dan ringkasan pagi, dengan versi cadangan berbasis aturan.
- Panel pemantau AI di dasbor koordinator, contoh chat bahasa Bali di simulator, 18 tes AI, README, dan dokumen ini.

## Pernyataan

Kami menyatakan bahwa informasi di atas benar dan lengkap. Kami bertanggung jawab atas penggunaan teknologi, source code, data, library, API, model AI, dan aset yang digunakan pada produk.
