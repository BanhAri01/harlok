# DRAF PROPOSAL — UINIC 8.0 Web Development Competition

> Draf untuk disalin ke template Google Doc panitia. Bagian bertanda **[ISI ARI]** wajib diisi dengan data asli. Jangan mengarang hasil uji pengguna.
> Nama file PDF: `UINIC8#2026_WEBDEV_[Nama_Tim]_[Nama Instansi].pdf` (maks. 30 halaman, logo UINIC di sampul).

---

## Sampul
- Logo UINIC 8.0 + logo kampus
- Nama tim: **[ISI ARI]**
- Judul: **Harlok: Sistem Lapor Stok dan Harga Komoditas lewat WhatsApp untuk Posisi Tawar Peternak dan Petani Kecil**
- Subtema: AI & Teknologi Digital

## 1. Anggota
| Nama | NIM | Keterangan |
|---|---|---|
| I Dewa Made Ari Santika **[cek ejaan]** | **[ISI ARI]** | Ketua (individu): analisis, desain, pengembangan, pengujian |

## 2. Judul
**Harlok** adalah sistem web yang mengubah chat WhatsApp biasa dari peternak dan petani ("telur 2 ton 26rb") menjadi data harga pasar per wilayah, peta stok untuk pengepul, dan API data terbuka. Nama "Harlok" adalah singkatan dari *harga lokal*: harga yang lahir dari laporan peternak di wilayahnya sendiri, bukan harga yang ditentukan dari jauh. [ISI ARI: bila ada, cerita singkat kenapa memilih nama ini.]

## 3. Latar Belakang
Peternak dan petani kecil menjual hasil panennya kepada pengepul tanpa pegangan harga pasar hari itu. Informasi harga beredar dari mulut ke mulut dan dari satu obrolan WhatsApp ke obrolan lain, sehingga pihak yang paling banyak tahu harga, yaitu pembeli, memegang posisi tawar paling kuat. Penulis tumbuh di keluarga peternak ayam petelur di Bali dan melihat langsung bagaimana harga telur sering ditentukan sepihak. **[ISI ARI: 2–3 kalimat cerita nyata keluarga, misal selisih harga yang pernah dialami]**

Masalahnya bukan hanya ketiadaan data, tetapi juga **pintu masuk**. Kebanyakan aplikasi pertanian mewajibkan instal aplikasi, mendaftar dengan email, dan belajar menu baru. Bagi peternak yang sudah berumur, hambatan ini cukup untuk membuat aplikasi tidak pernah dipakai. Sebaliknya, WhatsApp sudah dipakai setiap hari.
**[ISI ARI: tambahkan 2–3 data resmi + sumber, misalnya jumlah rumah tangga usaha pertanian (Sensus Pertanian BPS 2023), penetrasi WhatsApp/internet di Indonesia (APJII), dan rantai pasok/margin perdagangan komoditas (BPS). Cantumkan di Daftar Pustaka.]**

Harlok dibangun dengan prinsip: peternak tidak perlu belajar apa pun selain mengirim chat seperti biasa.

## 4. Tujuan dan Manfaat
**Tujuan**
1. Mengumpulkan harga jual komoditas dari peternak/petani langsung lewat WhatsApp, tanpa aplikasi tambahan.
2. Menyajikan harga rata-rata per wilayah yang dapat dipercaya (hanya dari peternak terverifikasi, harga janggal ditahan).
3. Mempertemukan stok peternak dengan pengepul melalui peta dan tawaran yang transparan terhadap harga pasar.
4. Membuka data harga gabungan melalui API untuk dinas, koperasi, dan aplikasi lain.

**Manfaat**
- Peternak: tahu harga pasar sebelum menjual, mendapat peringatan saat tawaran jauh di bawah pasar, bisa menawar balik.
- Pengepul: menemukan stok terdekat tanpa banyak telepon, tawaran tercatat rapi.
- Dinas/koperasi/peneliti: data harga harian per wilayah yang dapat diambil otomatis.
- Keterkaitan SDGs: SDG 1 (tanpa kemiskinan), SDG 2 (tanpa kelaparan, rantai pasok pangan), SDG 8 (pekerjaan layak dan pertumbuhan ekonomi), SDG 10 (berkurangnya kesenjangan informasi).

## 5. Analisis
**Target pengguna**
| Pengguna | Kebutuhan | Kanal |
|---|---|---|
| Peternak/petani kecil | tahu harga, menjual dengan harga wajar, tidak repot | WhatsApp |
| Pengepul/pedagang | menemukan stok, menawar, mencatat transaksi | Web (desktop/HP) |
| Koordinator (kelompok ternak, koperasi) | memverifikasi anggota, menjaga mutu data | Web |
| Mitra data (dinas, aplikasi lain) | data harga terstruktur | API |
| Masyarakat umum | melihat harga pasar | Papan harga publik |

**Batasan sistem**
- Wilayah awal: 9 kabupaten/kota di Bali dan 5 di DI Yogyakarta. Komoditas awal: telur, ayam broiler, cabai rawit, beras, jagung. Keduanya dapat ditambah lewat seeder/database.
- Sistem tidak memproses pembayaran. Transaksi diselesaikan langsung setelah kontak dibuka.
- Harga rata-rata hanya sebaik laporan yang masuk. Mutu dijaga dengan verifikasi peternak, rentang harga wajar, dan penahanan harga menyimpang lebih dari 30%.
- Pengiriman WhatsApp memakai gateway pihak ketiga (Fonnte) dengan batas pesan harian.

**Platform**
- Peternak: WhatsApp (semua jenis HP). Simulator web `/coba` untuk demo.
- Pengepul, koordinator, publik: web responsif (Chrome/Edge/Firefox, desktop dan HP).
- Server: Laravel 13 (PHP 8.5), MariaDB, cPanel. API REST JSON dengan token Sanctum.

## 6. Metode Penelitian (Design Thinking)
1. **Understand** — pengalaman langsung sebagai anak peternak dan perantara penjualan telur; pengamatan cara peternak bertanya harga (telepon, grup WA).
2. **Research** — wawancara **[ISI ARI: siapa, kapan, berapa orang — misal bapak (peternak petelur) dan 2 pengepul]**; kajian aplikasi sejenis **[ISI ARI: sebutkan 2–3 aplikasi/papan harga yang dibandingkan, mis. panel harga pemerintah, marketplace tani]** dan kekurangannya (harus instal aplikasi, data tidak per wilayah kecil, tidak ada jalur tawar).
3. **Analyze** — temuan utama: (a) peternak hanya mau memakai WhatsApp; (b) format penulisan harga sangat beragam ("26rb", "26.500", "390rb/peti"); (c) pembeli butuh stok dekat dan jumlah pasti; (d) data harga harus bisa dipercaya.
4. **Design** — bot WhatsApp dengan pengurai pola + cadangan AI dan konfirmasi YA; papan harga bergaya papan kapur pasar; peta stok; balasan tawaran cukup angka 1/2/3.
5. **Test** — 45 tes otomatis (Pest) untuk alur bisnis dan keamanan; uji pengguna **[ISI ARI]** (lihat bab 10).

## 7. Arsitektur Sistem
(Tempel diagram dari README bagian *Arsitektur*, atau gambar ulang di draw.io.)

Komponen:
- **Gerbang WhatsApp**: Fonnte → `POST /webhook/wa/{rahasia}`; cek rahasia (hash_equals), nomor device, abaikan grup, tolak pesan ganda, batas 12 pesan/menit/nomor. Pesan diproses setelah respons agar webhook cepat.
- **Mesin percakapan** (`ChatBot`): status per nomor — daftar (nama → wilayah → komoditas), lapor → konfirmasi, menjawab tawaran → tawar balik.
- **Pembaca pesan dua lapis**: `ReportParser` (pola: ton/kwintal/kg/peti/tray/karung, rb/ribu/k/jt, harga per satuan) lalu `AiReportParser` (Claude Haiku, keluaran dipaksa JSON schema). Lapis pertama membaca format umum tanpa biaya dan tanpa internet, dan sudah diuji otomatis. Lapis kedua, AI, hanya dipanggil bila pesan tidak terbaca, dengan kuota harian per nomor dan kuota keseluruhan. AI bersifat opsional: pada tahap uji coba ini AI dimatikan untuk menekan biaya, dan cukup diaktifkan dengan mengisi kunci API tanpa mengubah kode. Semua hasil, baik dari pola maupun AI, tetap dikonfirmasi peternak dengan balasan YA.
- **Layanan bisnis**: `ReportService` (simpan, tahan harga janggal, tutup stok lama, kabari pengepul), `OfferService` (transaksi + kunci baris, satu stok tidak terjual dua kali), `PriceBoard` (rata-rata, median, min, maks harian per wilayah), `Messenger` (semua pesan keluar tercatat, kuota harian).
- **Antarmuka**: papan harga publik + peta (Leaflet/OpenStreetMap) + grafik (Chart.js), panel pengepul, panel koordinator, dokumentasi API.
- **API terbuka**: `/api/v1/harga`, `/harga/riwayat`, `/komoditas`, `/wilayah` (token Sanctum, izin `prices:read`, 60 permintaan/menit, hanya angka gabungan).
- **Basis data**: regions, commodities, farmers, conversations, inbound/outbound_messages, stock_reports, offers, daily_prices, audit_logs, users.

## 8. Fitur Unggulan
1. **Lapor cukup satu chat**: "telur 2 ton 26rb" langsung dipahami, termasuk satuan lokal (peti, tray) dan berbagai cara menulis harga.
2. **Harga balik seketika**: setelah YA, peternak menerima harga rata-rata wilayahnya dan selisih harganya.
3. **Peringatan tawaran murah**: tawaran lebih dari 10% di bawah pasar diberi tanda ⚠️ di WA peternak.
4. **Tawar-menawar lewat angka**: balas 1/2/3, tanpa membuka aplikasi.
5. **Data yang bisa dipercaya**: verifikasi peternak, rentang harga wajar, penahanan harga janggal, jejak audit.
6. **Ekosistem terbuka**: API ber-token untuk dinas/koperasi/aplikasi lain, sesuai tema *interconnected web solutions*.
7. **Privasi**: nama dan nomor peternak tidak pernah tampil di publik/API; kontak dibuka hanya setelah sepakat.

## 9. Design Brief
- **Logo**: **[ISI ARI: buat/tempel logo]** — saran: papan harga kecil berbentuk gelembung chat dengan angka kuning.
- **Warna** (diambil dari papan kapur dan kapur kuning di pasar tradisional):
  | Warna | Kode | Makna |
  |---|---|---|
  | Hijau papan | `#163326` | papan harga pasar, ketenangan dan kepercayaan |
  | Kuning kapur | `#F5B700` | angka harga yang ditulis di papan, perhatian |
  | Kertas | `#F1F4EE` | latar terang yang lembut untuk dibaca lama |
  | Tinta | `#14201A` | teks utama, kontras tinggi |
  | Merah turun | `#B42318` | harga turun / di bawah pasar |
  | Hijau naik | `#1E7A46` | harga naik / sesuai pasar |
- **Huruf**: *Bricolage Grotesque* untuk judul dan angka harga (berkarakter, seperti tulisan papan), *Public Sans* untuk teks isi (jelas, netral, dibuat untuk layanan publik).

## 10. Skenario Pengguna
**Pak Made, peternak telur di Tabanan**
1. Mengirim "halo" ke nomor Harlok, menjawab nama, memilih nomor Tabanan dan nomor Telur dari daftar.
2. Pagi hari mengirim "telur 2 ton 26rb". Bot membalas ringkasan 2.000 kg × Rp26.000. Ia membalas YA.
3. Bot membalas: rata-rata Tabanan Rp26.400, harga Anda Rp400 di bawah rata-rata.
4. Siang hari masuk tawaran Rp23.000 dari UD Sumber Telur dengan tanda ⚠️ (13% di bawah rata-rata; peringatan muncul bila selisihnya 10% atau lebih). Ia membalas 3 lalu 26000.
5. Pengepul menyetujui; Pak Made menerima nomor pengepul.

**Bu Sri, pengepul di Tabanan**
1. Masuk ke web, membuka Cari Stok, memilih Telur. Titik hijau/merah di peta menunjukkan stok dan posisinya terhadap harga pasar.
2. Mengklik stok Pak Made, mengisi harga dan jumlah, melihat keterangan "13% di bawah rata-rata", lalu mengirim tawaran.
3. Di Tawaran Saya muncul status "ditawar balik Rp26.000"; ia mengklik Setujui dan kontak peternak tampil.

**Koordinator kelompok ternak**: memverifikasi peternak baru, meninjau laporan harga Rp40.000 untuk telur yang ditahan otomatis, lalu menolaknya.

**Mitra (dinas peternakan)**: menerima token dari koordinator dan mengambil `GET /api/v1/harga?komoditas=telur` setiap pagi untuk laporan internal.

## 11. User Flow
(Gambar flowchart di draw.io/Figma, empat jalur:)
- Peternak: pesan pertama → nama → wilayah → komoditas → terdaftar → lapor → konfirmasi (YA/UBAH) → harga balik → [tawaran masuk → 1/2/3 → (3) harga balik → selesai].
- Pengepul: masuk → Cari Stok → pilih titik → isi tawaran → Tawaran Saya → (ditawar balik) Setujui → kontak terbuka.
- Koordinator: masuk → verifikasi peternak → tinjau laporan janggal → kelola pengepul/mitra → buat token.
- Mitra: token → panggil API → JSON.

## 12. UI/UX Preview
Tempel tangkapan layar (folder `docs/screenshots/`): papan harga (desktop + HP), peta stok, simulator chat (alur daftar dan lapor), panel pengepul (form tawaran), Tawaran Saya, panel koordinator (laporan janggal), dokumentasi API.

## 13. User Testing
**[ISI ARI — wajib data nyata]**
- Partisipan: **[mis. 3 peternak, 2 pengepul; usia, lama berusaha]**
- Tugas uji: (1) mendaftar lewat WA/simulator, (2) melaporkan stok dengan kalimat sendiri, (3) membaca harga, (4) pengepul mengirim tawaran, (5) peternak menjawab tawaran.
- Alat ukur: **SUS (System Usability Scale)** 10 butir untuk pengepul, dan untuk peternak cukup: tingkat keberhasilan tugas, waktu, serta jumlah pesan yang gagal dipahami bot. (UEQ juga boleh sesuai contoh template.)
- Hasil: **[tabel nilai asli]**
- Perbaikan dari hasil uji: **[apa yang diubah]**

Pengujian fungsional otomatis yang sudah ada: 45 tes Pest (158 asersi) — pembacaan 9 variasi pesan, alur pendaftaran dan laporan, penahanan harga janggal, alur tawaran, batas akun demo, keamanan webhook, hak akses, kebocoran data publik/API, validasi API. Semua lulus.

## 14. Kesimpulan
Harlok menunjukkan bahwa ekosistem data harga yang saling terhubung dapat dibangun tanpa memaksa peternak berpindah aplikasi. WhatsApp menjadi pintu masuk, web menjadi tempat pengepul dan koordinator bekerja, dan API membuka data bagi pihak lain. Pengembangan berikutnya: perluasan wilayah dan komoditas, pesan suara (ucapan ke teks), prediksi harga dari riwayat data, serta kerja sama dengan koperasi atau dinas setempat.

## 15. Daftar Pustaka
**[ISI ARI — sumber data latar belakang]** Contoh format:
- Badan Pusat Statistik. (2023). *Hasil Pencacahan Lengkap Sensus Pertanian 2023*. BPS.
- Asosiasi Penyelenggara Jasa Internet Indonesia. (2024). *Survei Penetrasi Internet Indonesia*. APJII.
- Laravel. (2026). *Laravel 13 Documentation*. https://laravel.com/docs
- Brooke, J. (1996). SUS: A "quick and dirty" usability scale. *Usability Evaluation in Industry*, 189–194.

## 16. Lampiran
- Link demo: `https://harlok.hermesworkss.com` **[setelah deploy]**
- Link GitHub: **[setelah repo dibuat]**
- Akun demo pengepul: `pengepul@contoh.id` / **[LAPOR_DEMO_PASSWORD]**
- Nomor WhatsApp Harlok: **[nomor Fonnte]**
- Dokumentasi: README, `/dokumentasi-api`
- Dokumentasi uji pengguna (foto/form) **[ISI ARI]**
