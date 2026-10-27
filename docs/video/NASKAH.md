# Naskah Video Semifinal, Storyboard, Shot List, dan Caption Reels — Juru Harga

**Kompetisi:** UINIC 8.0 Web Development Competition  
**Tema:** *Engineering Interconnected Web Solutions to Empower Digital Ecosystems*  
**Subtema:** AI & Teknologi Digital (SDG 1, 2, 8, 10)  
**Target Durasi Video:** 3 Menit (180 Detik) — Sesuai rentang ketentuan panitia (2–4 menit)  
**Bobot Penilaian:** Inovasi (30%), Kreativitas (25%), Kesesuaian Tema (25%), Penyampaian (20%)  
**Karakter Utama / Presenter:** Ari (Pengembang sistem, anak peternak ayam petelur)  
**Lokasi Utama:** Kandang ayam petelur di Tabanan, Bali & Meja kerja laptop

---

## 1. Konsep & Strategi Narasi Video

1. **Menonjolkan Cerita Lapangan Autentik (Bukan Kasus Fiktif):**
   Video dibuka dari realitas nyata di kandang ayam petelur rakyat di Bali, bukan dari ruang ber-AC. Juri langsung melihat bahwa sistem ini lahir dari keresahan nyata seorang anak peternak yang melihat bapaknya dan peternak tetangga dipermainkan harga oleh perantara.
2. **Kesesuaian Tema (*Engineering Interconnected Web Solutions*):**
   Menunjukkan satu alur data utuh yang saling terhubung (*interconnected*):
   $\text{Peternak (WhatsApp)} \longrightarrow \text{Web Server \& Mesin Pengurai Hibrida} \longrightarrow \text{Papan Harga Publik} \longleftrightarrow \text{Panel Pengepul (Peta \& Tawaran)} \longrightarrow \text{Open REST API untuk Dinas/Koperasi}$.
3. **Unsur Inovasi & Kreativitas:**
   * Peternak tidak perlu menginstal aplikasi baru apa pun (*zero-install barrier*).
   * Lapor cukup satu chat natural: *"telur 2 ton 26rb"*.
   * Sistem otomatis memberi peringatan bahaya di WhatsApp peternak bila ada pengepul menawar jauh di bawah harga pasar.
   * Negosiasi dilakukan cukup dengan membalas angka `1`, `2`, atau `3`.
4. **Kepatuhan Aturan Data:**
   Tidak menggunakan klaim palsu mengenai jumlah pengguna atau skor kepuasan. Seluruh data uji pengguna ditandai dengan **[ANGKA ASLI]** sesuai pelaksanaan uji lapangan tanggal 29 Oktober.

---

## 2. Tabel Naskah & Storyboard per Adegan (Total: 180 Detik)

| Detik | Adegan / Visual Rekaman | Narasi Lisan (Diucapkan oleh Ari) | Teks di Layar (On-Screen Text) |
|---|---|---|---|
| **00:00 – 00:15** (15 dtk) | **Shot 1 (Kandang, Medium Shot):**<br/>Ari berdiri di depan lorong kandang baterai ayam petelur di Tabanan, memegang baki telur. Suara latar ayam petelur terdengar alami.<br/>Kamera perlahan mendekat (*slow zoom-in*). | *"Halo dewan juri UINIC 8.0, saya Ari. Saya tumbuh besar di kandang ayam petelur ini di Bali. Setiap hari, peternak seperti bapak saya bekerja keras dari fajar, tetapi saat panen tiba, kami tidak pernah tahu berapa harga pasar hari ini."* | **JURU HARGA**<br/>Harga Pasar Adil, Cukup Lewat WhatsApp.<br/>*I Dewa Made Ari Santika — Web Development UINIC 8.0* |
| **00:15 – 00:30** (15 dtk) | **Shot 2 (Kandang, Close-up):**<br/>Bapak Ari memegang telepon genggam lama dengan layar WhatsApp. Transisi ke grafis data BPS (16,89 juta rumah tangga petani gurem; MPP telur 20,19%). | *"Peternak kecil menjual tanpa pegangan data. Pembeli menentukan harga sepihak, dan sekitar seperlima harga di tingkat konsumen habis di rantai perantara. Mau pakai aplikasi pertanian modern? Peternak kami yang berumur kesulitan instal aplikasi dan hafalkan menu baru."* | **Masalah Nyata di Hulu:**<br/>• 16,89 Juta Petani Gurem (BPS 2023)<br/>• Hambatan Literasi & Memori HP<br/>• Asimetri Informasi Harga |
| **00:30 – 00:50** (20 dtk) | **Shot 3 (Demo Nyata: Chat WhatsApp):**<br/>Layar ponsel bapak Ari. Tangan kasar peternak mengetik pesan singkat di WhatsApp resmi Juru Harga: `telur 2 ton 26rb` lalu tekan kirim.<br/>Dalam 2 detik, bot membalas ringkasan terstruktur dan meminta konfirmasi. | *"Solusi kami sederhana tapi mendasar: jangan paksa peternak berpindah aplikasi. Di Juru Harga, peternak cukup mengirim chat WhatsApp biasa: 'telur 2 ton 26rb'. Mesin pengurai regex dan AI membaca pesan ini, mengonversi satuan lokal, lalu meminta konfirmasi."* | **Pintu Masuk Tanpa Aplikasi:**<br/>Chat Masuk: `telur 2 ton 26rb`<br/>➔ Terbaca: 2.000 kg @ Rp26.000/kg<br/>*Balas YA untuk simpan* |
| **00:50 – 01:10** (20 dtk) | **Shot 4 (Split Screen: HP & Laptop):**<br/>Peternak membalas `YA`.<br/>Seketika di layar laptop sebelah kanan, Papan Harga Pasar Terbuka (`/`) terbarui otomatis: angka rata-rata Tabanan terhitung ulang dan titik stok baru muncul di peta Leaflet. | *"Cukup balas 'YA'. Detik itu juga, data divalidasi. Laporan langsung memperbarui papan harga pasar real-time per wilayah, dan peternak seketika menerima informasi rata-rata pasar wilayahnya."* | **Real-Time Price Engine:**<br/>• Validasi Kewajaran Harga<br/>• Rata-rata Wilayah Terbarui Otomatis<br/>• Peringatan Deviasi >30% (Anti-Hoaks) |
| **01:10 – 01:30** (20 dtk) | **Shot 5 (Web Panel Pengepul):**<br/>Ari mendemonstrasikan antarmuka web pengepul (`/pengepul`). Layar menampilkan peta interaktif stok se-Bali. Pengepul mengklik titik stok Pak Made di Tabanan, lalu memasukkan penawaran harga Rp24.500/kg (di bawah pasar). | *"Di sisi pembeli, pengepul mengakses web tanpa repot telepon ke banyak tempat. Pengepul melihat sebaran stok di peta. Ketika pengepul mencoba menawar di bawah pasar, sistem kami menghitung deviasinya secara transparan."* | **Panel Web Pengepul:**<br/>• Peta Sebaran Stok (Leaflet OSM)<br/>• Transparansi Deviasi Pasar<br/>• Penawaran Terkunci Database ACID |
| **01:30 – 01:50** (20 dtk) | **Shot 6 (HP Peternak: Peringatan Otomatis):**<br/>Kembali ke layar WhatsApp peternak. Masuk pesan notifikasi tawaran dengan simbol tanda seru kuning ⚠️.<br/>Peternak membalas `3` (tawar balik), lalu mengetik `25500`. | *"Peternak langsung menerima notifikasi WhatsApp dengan peringatan pintar: '⚠️ Tawaran ini tiga belas persen di bawah rata-rata Tabanan'. Posisi tawar peternak terlindungi! Peternak cukup membalas angka tiga untuk tawar balik ke dua puluh lima ribu lima ratus."* | **Perlindungan Otomatis:**<br/>⚠️ Peringatan Tawaran Murah (≥10% di bawah pasar)<br/>Negosiasi Mudah via Chat:<br/>*Balas 1=Terima, 2=Tolak, 3=Tawar Balik* |
| **01:50 – 02:10** (20 dtk) | **Shot 7 (Kesepakatan & Kontak Terbuka):**<br/>Di web pengepul, muncul notifikasi tawar balik. Pengepul mengklik 'Setujui'. Seketika di WhatsApp peternak dan layar web pengepul muncul ucapan '🤝 Sepakat!' beserta kontak telepon yang kini terbuka. | *"Pengepul menyetujui di web, transaksi terkunci secara ACID tanpa risiko stok terjual ganda. Kontak kedua pihak baru dibuka setelah harga disepakati. Adil, aman, dan privasi peternak tetap terjaga."* | **Transparan & Aman:**<br/>• Kunci Baris (lockForUpdate)<br/>• Kontak Dibuka Hanya Setelah Sepakat<br/>• Privasi Terlindungi Sejak Awal |
| **02:10 – 02:35** (25 dtk) | **Shot 8 (Tema: Interconnected Digital Ecosystem):**<br/>Kamera beralih ke layar kode dan dokumentasi API (`/dokumentasi-api`). Ari memperlihatkan: (1) Widget iframe terpasang di situs web koperasi desa, (2) Unduhan Open Data CSV, dan (3) Pemanggilan curl `GET /api/v1/harga` mengembalikan data JSON terstruktur. | *"Inilah perwujudan tema 'Engineering Interconnected Web Solutions': Juru Harga menghubungkan WhatsApp peternak, dashboard web pengepul, widget website desa, hingga RESTful API ber-token Sanctum yang siap dikonsumsi dinas pertanian untuk pemantauan inflasi pangan."* | **Interconnected Web Solutions:**<br/>• WhatsApp Gateway (Fonnte)<br/>• Web Platform (Laravel 13 & Alpine.js)<br/>• Embeddable Price Widget<br/>• Open RESTful API v1 (Sanctum) |
| **02:35 – 03:00** (25 dtk) | **Shot 9 (Penutup di Kandang bersama Peternak):**<br/>Ari berdiri berdampingan bersama bapaknya di kandang ayam. Bapaknya tersenyum sambil memegang telur segar. Kamera melakukan *pan-out* memperlihatkan kandang rakyat yang asri. | *"Dari sebuah kandang ayam di Tabanan untuk ketahanan pangan Indonesia. Juru Harga membuktikan teknologi digital tidak harus rumit untuk memberdayakan mereka yang paling berjasa memberi kita makan. Harga pasar yang adil, cukup lewat WhatsApp. Terima kasih!"* | **JURU HARGA**<br/>Memberdayakan Peternak Rakyat.<br/>Menjaga Ketahanan Pangan Nasional.<br/>*(Mendukung SDG 1, 2, 8, dan 10)* |

---

## 3. Daftar Pengambilan Gambar (Shot List) untuk Ari

Panduan teknis bagi Ari saat perekaman video di lapangan (menggunakan kamera ponsel cerdas):

| No | Tipe Shot & Lokasi | Sudut & Pergerakan | Alat & Properti | Aktor / Yang Tampil | Catatan Teknis Audio / Visual |
|---|---|---|---|---|---|
| **S1** | *Medium Close-Up*<br/>Lorong kandang ayam petelur (pagi hari) | *Eye-level*, kamera perlahan bergerak maju (*slow push-in*) | HP resolusi 1080p 60fps, mikrofon clip-on nirkabel, baki telur isi telur cokelat segar | Ari (pakaian rapi: kemeja kasual atau polo shirt polos gelap) | Pastikan suara vokal Ari jelas, gunakan *windshield mic* untuk meredam deru angin atau suara kipas kandang. |
| **S2** | *Close-Up B-Roll*<br/>Tangan peternak & ayam petelur | Sudut miring atas (*over-the-shoulder*) | HP, kandang baterai ayam, telur yang baru menggelinding | Bapak Ari (peternak) | Tangkap ekspresi wajah fokus peternak dan tangan pekerja yang memegang ponsel. |
| **S3** | *Screen Recording & Over-the-Shoulder*<br/>Layar HP Peternak | *Macro shot* ke layar ponsel peternak | HP peternak dengan aplikasi WhatsApp resmi | Tangan Bapak Ari mengetik | Tampilkan ketukan jari asli mengetik `telur 2 ton 26rb` dan balasan bot yang cepat. |
| **S4** | *Screen Capture + Live Reaction*<br/>Meja kerja di dekat kandang/rumah | Sudut samping (*side profile*) Ari di depan laptop dengan HP di tangan | Laptop membuka web Papan Harga (`localhost:8000` / domain demo), HP peternak | Ari | Rekam momen ketika tombol `YA` dikirim di HP dan layar web laptop langsung berganti angka tanpa *reload* layar penuh. |
| **S5** | *Screen Recording Desktop*<br/>Tangkapan layar monitor tajam | Tangkapan layar murni (*screen recording OBS / CleanShot*) | Laptop, browser Chrome layar penuh (1920×1080) | Kursor mouse pengepul | Alur pengepul memilih pin peta Leaflet dan mengisi form tawaran harga. |
| **S6** | *Close-Up HP*<br/>Notifikasi WhatsApp masuk | *Static close-up* fokus ke notifikasi | HP peternak di atas meja kayu | Ari / Bapak Ari | Perlihatkan teks tanda peringatan ⚠️ dan pengetikan angka balasan `3` lalu `25500`. |
| **S7** | *Screen Recording Desktop & HP Bersamaan* | Tampilan ganda (*side-by-side*) | Software perekam layar | Demo sistem | Tombol 'Setujui' diklik di web dan pesan 'Sepakat!' masuk serentak di WhatsApp. |
| **S8** | *B-Roll Teknis*<br/>Meja kerja Ari | Kamera bergerak menyamping (*slide track*) | Laptop menampilkan dokumentasi `/dokumentasi-api` dan terminal curl API | Ari | Memberi kesan teknis yang matang, bersih, dan profesional. |
| **S9** | *Wide Shot Penutup*<br/>Depan kandang ayam petelur | *Wide eye-level*, perlahan mundur (*slow zoom-out*) | HP, mikrofon, baki telur | Ari berdampingan dengan Bapak Ari | Senyum ramah, gestur percaya diri, salam penutup hangat ke dewan juri. |

---

## 4. Tiga Pilihan Caption Reels Instagram (Sesuai Template Panitia UINIC 8.0)

Berikut 3 opsi teks takarir (*caption*) Instagram Reels untuk babak penilaian *Best Favorite* dan publikasi karya:

### Pilihan 1 (Fokus Cerita Lapangan & Inovasi Akar Rumput — Direkomendasikan)
```text
[UINIC 8.0 WEB DEVELOPMENT COMPETITION]

"Bapak kerja keras di kandang dari subuh, tapi saat panen, kami nggak pernah tahu harga telur hari ini." 🐔🌾

Di Indonesia, ada 16,89 juta petani dan peternak gurem (BPS 2023). Sebagian besar menjual hasil panen tanpa pegangan data harga riil, membuat perantara bebas menentukan harga sepihak. Aplikasi pertanian modern? Terlalu rumit dan berat di memori HP peternak sepuh.

Kenalkan JURU HARGA — inovasi web karya Tim [ISI ARI: NAMA TIM] dari [ISI ARI: NAMA KAMPUS] untuk UINIC 8.0! 💡

Melalui Juru Harga, peternak TIDAK PERLU menginstal aplikasi apa pun. Cukup kirim satu chat WhatsApp biasa:
👉 "telur 2 ton 26rb"

Sistem membaca pesan secara otomatis, memperbarui papan harga pasar per wilayah secara real-time, menampilkan sebaran stok di peta untuk pengepul, dan melindungi posisi tawar peternak lewat peringatan otomatis jika ada tawaran murah!

Terhubung ke dinas dan koperasi melalui Open REST API terstruktur, Juru Harga mewujudkan ekosistem web yang inklusif dari hulu ke hilir.

Dukung karya kami dengan cara like, comment, dan share reels ini! ✨

#UINIC8 #WebDevUINIC #JuruHarga #InovasiDigital #PertanianIndonesia #SDG1 #SDG2 #SDG8 #SDG10 #PeternakRakyat #TeknologiTepatGuna
```

### Pilihan 2 (Fokus Kesesuaian Tema Teknis & Interconnected Ecosystem)
```text
[ENGINEERING INTERCONNECTED WEB SOLUTIONS TO EMPOWER DIGITAL ECOSYSTEMS]

Bagaimana menghubungkan peternak ayam di pelosok desa dengan ekosistem data pasar modern? Jawabannya bukan memaksa mereka belajar aplikasi baru, melainkan menghubungkan teknologi ke kebiasaan mereka! 🌐⚡

Tim [ISI ARI: NAMA TIM] ([ISI ARI: NAMA KAMPUS]) mempersembahkan: JURU HARGA 📊

Juru Harga mengintegrasikan 4 pilar solusi web yang saling terhubung:
1️⃣ WhatsApp Gateway & Hybrid Parsing (Regex + Claude AI) untuk peternak tanpa unduh aplikasi.
2️⃣ Web Platform Responsif (Laravel 13 & Alpine.js) dengan peta sebaran stok Leaflet untuk pengepul.
3️⃣ Papan Harga Publik & Embeddable Widget yang memperbarui diri tiap 12 detik.
4️⃣ RESTful Open API v1 ber-token Sanctum untuk dinas ketahanan pangan dan koperasi daerah.

Hasil pengujian [ANGKA ASLI] menunjukkan sistem ini memangkas asimetri informasi harga dan memberikan perlindungan posisi tawar langsung di genggaman peternak.

Mari bersama dukung digitalisasi pangan yang berkeadilan! Mohon doa dan dukungannya untuk karya kami di UINIC 8.0! 🙏🔥

#UINIC8 #WebDevelopment #InterconnectedWeb #OpenAPI #Laravel13 #AIforGood #SDGs2030 #KetahananPangan #SmartFarming #MahasiswaBerprestasi
```

### Pilihan 3 (Fokus Singkat, Emosional, & Aksi Sosial)
```text
[DARI KANDANG AYAM TABANAN UNTUK INDONESIA 🇮🇩]

Pernahkah kamu tahu kenapa harga telur di pasar mahal, tapi peternak kecil di desa sering gulung tikar karena harga anjlok?

Jawabannya adalah kesenjangan informasi harga di tangan tengkulak.

JURU HARGA hadir memutus rantai asimetri informasi tersebut. Cukup satu chat WhatsApp dari kandang, harga pasar yang adil tercipta untuk satu wilayah. Pengepul menawar transparan di web, data terbuka mengalir ke dinas lewat API, dan peternak terlindungi dari perangkap harga murah! 🤝✨

Karya inovasi Web Development Competition UINIC 8.0 oleh:
👤 I Dewa Made Ari Santika
🏛️ [ISI ARI: NAMA KAMPUS]

Bantu kami membawa suara peternak rakyat ke panggung juara dengan klik LIKE, berikan KOMENTAR terbaikmu, dan SHARE ke teman-temanmu! 🚀❤️

#UINIC8 #UINIC2026 #JuruHarga #AyoMajuPeternakIndonesia #TeknologiUntukRakyat #DigitalEcosystem #SDGsIndonesia #MahasiswaKreatif
```
