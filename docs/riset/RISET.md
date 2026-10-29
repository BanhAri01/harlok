# Riset Pasar, Komparasi Layanan, dan Data Latar Belakang — Harlok

Dokumen ini disusun sebagai landasan riset empiris untuk proposal dan pengembangan sistem **Harlok** pada ajang *AI Web Innovation Challenge Bali DigiFest 2026*. Seluruh data numerik dan regulasi yang dicantumkan bersumber langsung dari publikasi resmi lembaga negara (BPS, Bapanas, Kemendag, Bank Indonesia) serta laporan riset industri (APJII, DataReportal/We Are Social).

---

## A. Tabel Pembanding Layanan Sejenis di Indonesia

Tabel berikut membandingkan sistem pemantauan harga, pasar pangan, dan ekosistem komoditas yang saat ini beroperasi di Indonesia:

| Layanan / Platform | Pemilik / Pengelola | Cara Data Masuk | Tingkat Wilayah | Ada Tawar-Menawar? | Perlu Instal Aplikasi? | API Terbuka? | Kekurangan Utama |
|---|---|---|---|---|---|---|---|
| **Panel Harga Pangan** (`panelharga.badanpangan.go.id`) | Badan Pangan Nasional (Bapanas) | Input manual terjadwal oleh enumerator resmi/petugas dinas di pasar sampel | Nasional, Provinsi, hingga Kabupaten/Kota | **Tidak ada** (hanya statistik harga agregat satu arah) | Tidak (Web portal) | Tidak (API publik resmi tidak dibuka bebas untuk umum) | Hanya bersifat pemantauan pasif pascapanen/pasar; tidak mengakomodasi transaksi peternak kecil; tidak ada peta ketersediaan stok riil. |
| **SP2KP** (`sp2kp.kemendag.go.id`) | Kementerian Perdagangan (Kemendag) | Survei harian dan entri oleh petugas/enumerator pemda di pasar pantau | Nasional, Provinsi, Kabupaten/Kota | **Tidak ada** (informasi rujukan pemerintah) | Tidak (Web portal dengan login enumerator) | Tidak (Akses API tertutup antarkementerian/instansi) | Fokus pada barang kebutuhan pokok di pasar grosir/eceran perkotaan; peternak di hulu tidak terlibat langsung; pembaruan data bergantung kedisiplinan petugas. |
| **PIHPS Nasional** (`hargapangan.id` / `bi.go.id/hargapangan`) | Bank Indonesia | Survei berkala petugas BI dari pasar tradisional & modern di kota-kota sampel IHK | Nasional, Provinsi, 82+ Kota sampel IHK | **Tidak ada** (sarana pengendali inflasi pangan / *volatile food*) | Ya (jika lewat aplikasi *Harga Pangan*) / Tidak (Web) | Tidak resmi terbuka (tidak ada portal OpenAPI publik ber-token bagi umum) | Titik sampel hanya di kota besar/pasar konsumen; tidak memotret disparitas harga di tingkat kandang (*farm gate*); bukan platform dagang. |
| **Marketplace Agri B2B/B2C** (mis. TaniHub, Sayurbox, EdenFarm) | Perusahaan Swasta / Startup AgriTech | Entri katalog oleh merchant/mitra supplier melalui form aplikasi atau kontrak korporasi | Kota-kota metropolitan & wilayah sentra kemitraan | **Tidak ada** (harga ditetapkan sistem/katalog terpusat) | **Ya** (aplikasi Android/iOS atau registrasi web kompleks) | Terbatas/Tertutup (hanya untuk sistem internal atau mitra korporasi) | Syarat sertifikasi dan kuota pasokan terlalu berat bagi peternak gurem; antarmuka aplikasi rumit; tidak ramah bagi peternak berumur; alur pembayaran perantara menahan arus kas. |
| **Aplikasi Agritech Peternakan** (mis. Chickin App, Pitik Digital) | Startup Agritech Peternakan Unggas | Sensor IoT otomatis di kandang tertutup (*closed house*) + entri logbook harian oleh peternak | Terbatas pada kandang mitra peternak kontrak | **Tidak ada** (harga ayam/sapronak terikat kontrak kemitraan) | **Ya** (Wajib instal aplikasi mobile & perangkat keras IoT) | Tertutup (ekosistem tertutup perusahaan mitra) | Dikhususkan untuk ayam pedaging (*broiler*) kandang modern bertaraf industri; tidak mendukung peternak mandiri ayam petelur gurem; biaya perangkat keras dan hambatan teknis tinggi. |
| **Grup WhatsApp Komunitas / Paguyuban** (mis. Paguyuban Peternak Rakyat / PINSAR) | Komunitas peternak lokal / asosiasi independen | Pesan teks manual tanpa format baku dari peternak & pengepul | Komunitas per kabupaten atau jejaring daerah | **Ada secara parsial** (obrolan bebas via japri/grup, tanpa aturan sistem) | Tidak perlu aplikasi baru (cukup WhatsApp yang sudah ada) | **Tidak ada sama sekali** (data tercecer di pesan teks privat) | Data harga tenggelam di antara obrolan obrolan lain; format pesan tidak standar; rentan manipulasi rumor harga oleh oknum tengkulak; tidak ada rekaman riwayat terstruktur. |
| **Harlok** *(Solusi yang Diajukan)* | **Karya mahasiswa (individu)** | **Pesan teks WhatsApp natural** ("telur 2 ton 26rb") dibaca otomatis via *Regex Parser* + cadangan *AI LLM* | **Kabupaten/Kota** (langsung dari peternak) | **Ada** (Pengepul menawar via web; peternak cukup balas angka **1 / 2 / 3** via WhatsApp) | **TIDAK PERLU** bagi peternak (cukup WhatsApp); Web responsif ringan untuk pengepul & koordinator | **YA**: REST API ber-token untuk mitra, plus widget dan CSV terbuka tanpa token | Membutuhkan gateway WhatsApp pihak ketiga (Fonnte/Cloud API); bergantung pada keberadaan koordinator kelompok ternak untuk verifikasi awal. |

---

## B. Data Latar Belakang Resmi, Sumber, dan Kutipan Proposal

Berikut adalah data statistik resmi hasil verifikasi penelusuran dokumen publikasi instansi negara untuk dicantumkan pada Bab **Latar Belakang** proposal:

### 1. Data Petani dan Peternak Gurem (Sensus Pertanian BPS 2023)
* **Angka Kunci:** Tercatat sebanyak **16.891.120 (16,89 juta) Rumah Tangga Usaha Pertanian (RTUP) Gurem** di Indonesia. Angka ini melonjak **18,54%** (bertambah 2,64 juta rumah tangga) dibanding Sensus Pertanian 2013 (14,25 juta). Di tingkat wilayah sentra, persentase petani gurem di **Provinsi Bali mencapai 69,32%** dan di **Provinsi DI Yogyakarta mencapai 87,75%** (tertinggi di Pulau Jawa).
* **Definisi Resmi:** Petani/peternak gurem adalah usaha pertanian perorangan yang menguasai atau mengusahakan lahan pertanian kurang dari 0,5 hektare (< 0,5 ha).
* **Tahun Data:** 2023 (Dipublikasikan 15 Desember 2023).
* **Tautan Sumber:** [Bisnis.com — BPS Catat Jumlah Petani Gurem Naik Jadi 16,89 Juta (4 Des 2023)](https://ekonomi.bisnis.com/read/20231204/9/1720611/miris-bps-catat-jumlah-petani-gurem-naik-jadi-1689-juta); publikasi resmi: *Hasil Pencacahan Lengkap Sensus Pertanian 2023 - Tahap I*, Badan Pusat Statistik (No. Publikasi: 05100.2318, No. Katalog: 5106043).
* **Kalimat Siap Tempel ke Proposal:**
  > *"Berdasarkan Hasil Pencacahan Lengkap Sensus Pertanian 2023 oleh Badan Pusat Statistik (BPS), sebanyak 16,89 juta rumah tangga usaha pertanian di Indonesia tergolong sebagai petani dan peternak gurem dengan kepemilikan lahan di bawah 0,5 hektare. Jumlah ini meningkat 18,54% dibandingkan tahun 2013. Persentase rumah tangga gurem mencapai 69,32% di Bali dan 87,75% di DI Yogyakarta, dua wilayah awal Harlok. Keterbatasan skala usaha gurem ini menyebabkan mereka berada dalam posisi tawar yang rentan dan sangat bergantung pada perantara pasar."*

---

### 2. Penetrasi Internet dan Dominasi Penggunaan WhatsApp di Indonesia
* **Angka Kunci:** 
  1. Penetrasi internet di Indonesia pada tahun 2024 mencapai **79,5%** dari total populasi, mencakup **221.563.479 jiwa** (APJII, 2024).
  2. Sebanyak **90,9% pengguna internet di Indonesia aktif menggunakan WhatsApp**, menjadikannya platform yang paling banyak digunakan, di atas Instagram (85,3%) (DataReportal / We Are Social, 2024; dikutip juga oleh Meltwater).
* **Tahun Data:** 2024.
* **Tautan Sumber Resmi:**
  1. Asosiasi Penyelenggara Jasa Internet Indonesia (APJII): [https://apjii.or.id](https://apjii.or.id) — *Laporan Survei Penetrasi Internet Indonesia 2024*.
  2. DataReportal (We Are Social & Meltwater): [https://datareportal.com/reports/digital-2024-indonesia](https://datareportal.com/reports/digital-2024-indonesia) — *Digital 2024: Indonesia*.
* **Kalimat Siap Tempel ke Proposal:**
  > *"Data Survei Penetrasi Internet APJII (2024) mencatat bahwa penetrasi internet nasional telah mencapai 79,5% dengan lebih dari 221,5 juta pengguna. Menariknya, laporan Digital 2024 oleh DataReportal mengungkapkan bahwa WhatsApp digunakan oleh 90,9% pengguna internet Indonesia, lebih banyak daripada platform lain mana pun. Fakta ini menegaskan bahwa solusi digital bagi masyarakat akar rumput tidak boleh menuntut penginstalan aplikasi baru yang membebani memori telepon seluler, melainkan harus hadir langsung di dalam ekosistem WhatsApp yang telah menjadi instrumen komunikasi harian mereka."*

---

### 3. Margin Perdagangan dan Pengangkutan (MPP) serta Rantai Pasok Pangan
* **Angka Kunci (Survei Pola Distribusi Perdagangan BPS):**
  1. **Telur Ayam Ras:** Pola utama distribusi nasional adalah Produsen (Peternak) → Pedagang Grosir → Pedagang Eceran → Konsumen Akhir, dengan **MPP total 20,19%** (BPS, publikasi Distribusi Perdagangan Komoditas Telur Ayam Ras, rilis 2021). Sebagai pembanding, BPS pernah mencatat MPP telur 26,80% (2017) dan 13,09% (2018), sehingga margin ini berubah-ubah dari tahun ke tahun.
  2. **Cabai dan beras:** angka MPP yang sebelumnya tercantum di sini **tidak dapat diverifikasi** dan sudah dihapus. Bila diperlukan, ambil langsung dari publikasi BPS "Distribusi Perdagangan Komoditas Cabai Merah/Beras" tahun terbaru dan cantumkan tahunnya.
* **Tautan Sumber:** [BPS — Distribusi Perdagangan Komoditas (2021)](https://www.bps.go.id/id/publication/2021/10/29/5ee6719906f5a741e1830cfc), [Bisnis.com — Margin Distribusi Telur Ayam Ras Turun (2 Jan 2020)](https://ekonomi.bisnis.com/read/20200102/99/1186347/margin-distribusi-telur-ayam-ras-turun). **Ari: buka tautan BPS dan pastikan judul publikasi serta angka 20,19% tertera di dalamnya sebelum dikutip.**
* **Kalimat Siap Tempel ke Proposal:**
  > *"Survei Pola Distribusi Perdagangan BPS mencatat bahwa telur ayam ras berpindah dari peternak ke konsumen melalui pedagang grosir dan pedagang eceran, dengan Margin Perdagangan dan Pengangkutan total sebesar 20,19%. Artinya, sekitar seperlima harga yang dibayar konsumen tidak sampai ke tangan peternak, sementara peternak sendiri jarang mengetahui harga pasar hari itu saat bernegosiasi."*

---

### 4. Selisih Harga Telur Ayam Ras di Tingkat Peternak vs Konsumen
* **Angka Kunci:** Berdasarkan **Peraturan Badan Pangan Nasional (Perbadan) Nomor 6 Tahun 2024**, Harga Acuan Pembelian (HAP) di tingkat produsen (peternak) ditetapkan sebesar **Rp26.500 per kg**, sedangkan Harga Acuan Penjualan (HAP) di tingkat konsumen ditetapkan sebesar **Rp30.000 per kg** (selisih acuan Rp3.500/kg). Sebagai gambaran pasar, pada pekan pertama Maret 2026 rata-rata harga telur di produsen tercatat **Rp27.083/kg** dan di konsumen **Rp32.650/kg** (selisih ±Rp5.500/kg atau ±17% dari harga konsumen), dikutip Bisnis.com (9 Mar 2026) dari data Bapanas.
* **Catatan verifikasi Claude:** klaim sebelumnya bahwa "peternak sering terpaksa menjual Rp20.000–Rp23.000/kg" dan "disparitas 25–35%" **tidak memiliki sumber dan sudah dihapus**. Bila Ari punya pengalaman nyata harga jatuh (catatan penjualan bapak), tulis sebagai cerita pribadi, bukan statistik.
* **Tahun Regulasi & Data:** 2024 (regulasi), 2026 (harga pasar).
* **Tautan Sumber Resmi:**
  1. Teks peraturan: [peraturan.go.id — Perbadan No. 6 Tahun 2024 (PDF)](https://peraturan.go.id/files/peraturan-bapanas-no-6-tahun-2024.pdf). **Ari: buka PDF dan pastikan angka Rp26.500 dan Rp30.000 tertera di lampirannya.**
  2. [Bisnis.com — Harga Ayam-Telur Turun di Hulu, Pemerintah Bidik Efisiensi Rantai Distribusi (9 Mar 2026)](https://ekonomi.bisnis.com/read/20260309/12/1958930/harga-ayam-telur-turun-di-hulu-pemerintah-bidik-efisiensi-rantai-distribusi).
  2. Portal Panel Harga Pangan: [https://panelharga.badanpangan.go.id](https://panelharga.badanpangan.go.id).
* **Kalimat Siap Tempel ke Proposal:**
  > *"Kendati Peraturan Badan Pangan Nasional (Perbadan) Nomor 6 Tahun 2024 telah menetapkan Harga Acuan Pembelian di tingkat peternak sebesar Rp26.500/kg dan harga konsumen sebesar Rp30.000/kg, harga acuan nasional itu tidak memberi tahu peternak berapa harga yang wajar di kabupatennya hari ini. Pada pekan pertama Maret 2026, misalnya, rata-rata harga produsen Rp27.083/kg sementara konsumen membayar Rp32.650/kg. Tanpa informasi harga lokal yang cepat, peternak kecil bernegosiasi tanpa pegangan."*

---

## C. Kenapa Harlok Berbeda: 5 Poin Pembeda Utama

Berdasarkan telaah komparasi terhadap seluruh sistem yang ada di Indonesia, **Harlok** menghadirkan pembaruan fundamental melalui 5 pilar keunggulan berikut:

1. **Hambatan Akses Nol bagi Peternak (*Zero-Friction Access via Ubiquitous WhatsApp*)**
   Seluruh platform rujukan (SP2KP, PIHPS, TaniHub, Chickin) mewajibkan antarmuka web login yang kaku atau instalasi aplikasi Android khusus yang memakan ruang penyimpanan ponsel dan memerlukan proses registrasi yang membingungkan. Harlok membalik paradigma tersebut: **peternak tidak perlu menginstal aplikasi apa pun**. Cukup dengan berkirim pesan WhatsApp biasa ("telur 2 ton 26rb"), peternak langsung terhubung ke jaringan pasar secara otomatis.

2. **Dua Arah & Negosiasi Nyata (*Two-Way Interactive Commerce vs Passive Observation*)**
   Situs pemerintah (Panel Harga Bapanas, PIHPS, SP2KP) hanya bersifat papan pengumuman pasif satu arah: pengguna hanya bisa membaca angka rata-rata tanpa tahu di mana barang tersedia dan tidak dapat melakukan transaksi. Sebaliknya, Harlok mengintegrasikan pelaporan harga sekaligus penawaran stok langsung. Pengepul dapat menemukan stok di peta dan mengirimkan penawaran harga, sementara peternak cukup membalas angka **1 (Terima), 2 (Tolak), atau 3 (Tawar Balik)** dari WhatsApp.

3. **Mesin Pengurai Pesan Hibrida (*Hybrid Natural Text Parsing + AI Fallback Engine*)**
   Peternak di lapangan menggunakan dialek, singkatan, dan satuan daerah yang beragam (seperti *peti, tray, kwintal, 26rb, 26.5k*). Harlok dilengkapi *Regex ReportParser* lokal berkecepatan tinggi yang menangani satuan lokal, dipadukan dengan *AiReportParser* (berbasis Claude Haiku) sebagai jaring pengaman bila kalimat peternak tidak berpola. Yang terpenting, integritas data tetap terjaga karena sistem **selalu meminta konfirmasi "YA/UBAH"** sebelum data dicatat.

4. **Sistem Proteksi Posisi Tawar Otomatis (*Automated Farmer Price Safeguard*)**
   Pada grup obrolan WhatsApp biasa, peternak sering menjadi korban perangkap harga murah oleh pedagang besar. Harlok memiliki kecerdasan built-in yang melindungi peternak: apabila pengepul mengirim tawaran $\ge 10\%$ di bawah harga pasar wilayah hari itu, sistem secara proaktif melampirkan label peringatan bahaya (⚠️ *Di Bawah Rata-Rata*) ke pesan WhatsApp peternak. Selain itu, laporan harga fiktif atau anomali yang menyimpang $\ge 30\%$ otomatis ditahan (*flagged*) untuk diaudit koordinator.

5. **Ekosistem Terbuka dan Saling Terhubung (*Interconnected Open Data Ecosystem*)**
   Selaras dengan tema kompetisi *"Engineering Interconnected Web Solutions to Empower Digital Ecosystems"*, Harlok tidak menjadi silo data tertutup. Data harga rata-rata wilayah yang telah dianonimkan (tanpa membuka identitas pribadi peternak) dipublikasikan secara transparan ke papan harga interaktif, disediakan dalam bentuk *embeddable widget* iframe untuk situs koperasi/desa, berkas *open data CSV*, serta **RESTful JSON API ber-token Sanctum** (`/api/v1/harga`) yang siap dikonsumsi oleh dinas ketahanan pangan, koperasi, perbankan, dan pengembang pihak ketiga.

---

## D. Daftar Pustaka (Format APA 7)

Asosiasi Penyelenggara Jasa Internet Indonesia. (2024). *Laporan survei penetrasi internet Indonesia 2024*. APJII. https://apjii.or.id

Badan Pangan Nasional. (2024). *Peraturan Badan Pangan Nasional Republik Indonesia Nomor 6 Tahun 2024 tentang perubahan atas Peraturan Badan Pangan Nasional Nomor 5 Tahun 2022 tentang harga acuan pembelian di tingkat produsen dan harga acuan penjualan di tingkat konsumen komoditas jagung, telur ayam ras, dan daging ayam ras*. Bapanas. https://badanpangan.go.id

Badan Pangan Nasional. (2024). *Panel harga pangan: Informasi harga rata-rata komoditas pangan pokok nasional*. https://panelharga.badanpangan.go.id

Badan Pusat Statistik. (2023). *Hasil pencacahan lengkap Sensus Pertanian 2023 - Tahap I: Ringkasan eksekutif* (No. Publikasi: 05100.2318, No. Katalog: 5106043). BPS Republik Indonesia. https://www.bps.go.id



Badan Pusat Statistik. (2022). *Distribusi perdagangan komoditas telur ayam ras di Indonesia 2022*. BPS Republik Indonesia. https://www.bps.go.id



Bank Indonesia. (2024). *Pusat Informasi Harga Pangan Strategis (PIHPS) Nasional*. Departemen Pengelolaan Moneter Bank Indonesia. https://www.bi.go.id/hargapangan

DataReportal, We Are Social, & Meltwater. (2024). *Digital 2024: Indonesia*. DataReportal. https://datareportal.com/reports/digital-2024-indonesia

Kementerian Perdagangan Republik Indonesia. (2024). *Sistem Pemantauan Pasar dan Kebutuhan Pokok (SP2KP)*. Kemendag RI. https://sp2kp.kemendag.go.id

Bisnis.com. (2023, 4 Desember). *Miris! BPS catat jumlah petani gurem naik jadi 16,89 juta*. https://ekonomi.bisnis.com/read/20231204/9/1720611/miris-bps-catat-jumlah-petani-gurem-naik-jadi-1689-juta

Bisnis.com. (2026, 9 Maret). *Harga ayam-telur turun di hulu, pemerintah bidik efisiensi rantai distribusi*. https://ekonomi.bisnis.com/read/20260309/12/1958930/harga-ayam-telur-turun-di-hulu-pemerintah-bidik-efisiensi-rantai-distribusi

---

## E. Catatan review Claude (28 Okt 2026)
Angka yang **terverifikasi** lewat pencarian: 16,89 juta RTUP gurem (+18,54%), Bali 69,32%, DIY 87,75%; HAP telur Rp26.500 (produsen) dan Rp30.000 (konsumen) menurut Perbadan 6/2024; MPP total telur 20,19%; WhatsApp dipakai 90,9% pengguna internet (DataReportal 2024).
Angka yang **dihapus karena tidak terverifikasi**: 60,84% gurem nasional; WhatsApp "favorit 34,8%" (sumber lain justru menyebut Instagram teratas); MPP telur 23,98%, cabai 43–61%, beras 10–15%; harga peternak Rp20–23 ribu dan disparitas 25–35%.
Angka APJII 79,5% / 221,56 juta jiwa sesuai rilis APJII 2024, tetapi tetap buka laporan APJII sebelum mengutip. Tabel pembanding (bagian A) adalah ringkasan umum; cek sekilas tiap situs sebelum dimasukkan ke proposal.
