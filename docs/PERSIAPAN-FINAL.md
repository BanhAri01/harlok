# Persiapan Presentasi & Tanya Jawab — Juru Harga

Tanya jawab bernilai **40%** di final. Pelajari jawaban di bawah sampai bisa diucapkan dengan kata-kata sendiri, bukan dihafal. Jawaban yang baik: **jawab langsung dalam satu kalimat → beri bukti (kode/data/demo) → akui batasannya bila ada.** Bagian bertanda [ISI] diisi setelah uji pengguna dan deploy.

## A. Kerangka presentasi 10 menit (PPT dikumpulkan 25 Nov)

| Menit | Slide | Isi |
|---|---|---|
| 0:00 | 1. Pembuka | Foto bapak di kandang + satu kalimat: "Bapak saya menjual telur tanpa tahu harga pasar hari itu." |
| 0:45 | 2. Masalah | 16,89 juta RTUP gurem (BPS 2023); MPP telur 20,19%; aplikasi pertanian butuh instal, sedangkan WhatsApp dipakai 90,9% pengguna internet. |
| 1:45 | 3. Ide dalam satu kalimat | "Satu chat WhatsApp menjadi harga yang adil untuk satu wilayah." |
| 2:15 | 4. **Demo langsung** (3 menit) | Juri/penonton mengirim WA "telur 1 ton 25rb" ke nomor Juru Harga → papan harga di layar berubah → Ari mengirim tawaran dari panel pengepul → HP juri menerima peringatan → juri balas 3. Cadangan: video rekaman bila sinyal buruk. |
| 5:15 | 5. Ekosistem terhubung | Diagram arsitektur: WhatsApp ↔ web pengepul ↔ panel koordinator ↔ API/widget/CSV untuk dinas & koperasi. |
| 6:15 | 6. Data dapat dipercaya | Verifikasi peternak, rentang harga wajar, tahan >30%, konfirmasi YA, jejak audit. |
| 7:00 | 7. Hasil uji pengguna | [ISI: tingkat keberhasilan, SUS, 2 kutipan, perbaikan yang dilakukan, jumlah laporan nyata]. |
| 8:00 | 8. Teknis & keamanan | Laravel 13, 51+ tes otomatis + CI, webhook rahasia, peran, Sanctum, rate limit, privasi. |
| 8:45 | 9. Rencana ke depan | Perluasan wilayah lewat kelompok ternak/koperasi, WhatsApp Cloud API resmi, model keberlanjutan. |
| 9:30 | 10. Penutup | Kembali ke bapak: "[ISI: kutipan bapak setelah memakai]". Tampilkan QR nomor WA. |

Latihan: rekam diri sendiri minimal 5 kali; potong bagian yang lewat waktu. Selalu siapkan demo cadangan (video + akun demo + simulator `/coba`).

## B. Bank pertanyaan juri

### Inovasi & masalah
1. **Apa bedanya dengan Panel Harga Bapanas / SP2KP / PIHPS?**
   Mereka mencatat harga pasar lewat petugas, satu arah, di tingkat kota/provinsi. Juru Harga mengambil harga langsung dari peternak lewat WhatsApp, di tingkat kabupaten, dan dua arah: pengepul bisa menawar, peternak menjawab 1/2/3. Data kami bahkan bisa melengkapi panel pemerintah lewat API.
2. **Kenapa WhatsApp, bukan aplikasi Android?**
   Peternak berumur tidak mau instal aplikasi baru; WhatsApp sudah ada di HP mereka dan dipakai 90,9% pengguna internet. Pengepul dan koordinator yang perlu fitur banyak tetap memakai web.
3. **Apa yang benar-benar baru di sini?**
   Gabungan tiga hal: lapor cukup dengan kalimat bebas (pola + AI cadangan + konfirmasi), peringatan otomatis saat tawaran jauh di bawah pasar, dan data yang langsung terbuka untuk ekosistem (API, widget, CSV).
4. **Bukankah pengepul justru rugi? Kenapa mereka mau ikut?**
   Pengepul mendapat stok terdekat tanpa banyak telepon dan riwayat tawaran yang rapi. Harga yang transparan juga mengurangi tawar-menawar yang berlarut. [ISI: pendapat pengepul saat uji.]
5. **Siapa yang sudah memakai?** [ISI: jumlah peternak & laporan nyata, sebutkan jujur.] Data contoh di demo ditandai jelas dan bisa dihapus dengan satu perintah.

### Data & kepercayaan
6. **Bagaimana kalau peternak berbohong soal harga?**
   Empat lapis: (1) hanya peternak terverifikasi koordinator yang dihitung; (2) harga di luar rentang wajar komoditas ditolak; (3) harga yang menyimpang lebih dari 30% dari rata-rata ditahan sampai ditinjau; (4) yang dipakai rata-rata dan median banyak laporan, jadi satu laporan tidak bisa menggeser harga jauh.
7. **Bagaimana kalau pengepul membuat akun palsu untuk menekan harga?**
   Pengepul tidak bisa melapor harga; papan hanya dihitung dari laporan peternak. Akun pengepul dibuat koordinator, bukan daftar bebas.
8. **Bagaimana kalau data sedikit di satu wilayah?**
   Papan menampilkan jumlah laporan dan tanggal di setiap angka, sehingga pengguna tahu seberapa kuat datanya. Harga lebih dari 7 hari tidak dipakai.
9. **Kenapa harus konfirmasi YA? Bukankah merepotkan?**
   Karena pesan bebas bisa salah dibaca, terutama oleh AI. Satu balasan "YA" jauh lebih murah daripada data harga yang salah.

### Teknis
10. **Bagaimana bot memahami "telur 2 ton 26rb"?**
    Pengurai pola (regex) mengenali satuan (ton, kwintal, kg, peti, tray, karung) dan cara menulis harga (26rb, 26.500, 26,5rb, rp26000, 5.6k), lalu mengubahnya ke kg dan rupiah/kg. Bila gagal, baru AI (Claude Haiku) dipanggil dengan keluaran dipaksa ke skema JSON, lalu dihitung ulang dengan aturan yang sama.
11. **Kenapa tidak semuanya pakai AI?**
    Lebih cepat, gratis, dan dapat diuji. AI hanya cadangan dan dibatasi 30 kali per nomor per hari supaya biaya terkendali.
12. **Bagaimana mencegah satu stok terjual ke dua pengepul?**
    Penerimaan tawaran berjalan dalam transaksi database dengan penguncian baris (`lockForUpdate`). Saat satu tawaran diterima, stok ditandai terjual dan tawaran lain otomatis ditolak.
13. **Bagaimana keamanan webhook WhatsApp?**
    Alamat webhook memuat kode rahasia yang dicek dengan `hash_equals`, nomor device dicocokkan, pesan grup diabaikan, pesan ganda ditolak lewat ID unik, dan ada batas 12 pesan per menit per nomor.
14. **Bagaimana kalau dua pesan dari nomor yang sama datang bersamaan?**
    Pemrosesan dikunci per nomor (cache lock), jadi status percakapan tidak tumpang tindih.
15. **Bagaimana kalau server lambat? Fonnte menunggu?**
    Webhook langsung menjawab, pesan diproses setelah respons dikirim.
16. **Bagaimana Anda menguji?**
    51+ tes otomatis (Pest) untuk pembacaan pesan, alur daftar/lapor/tawar, keamanan webhook, hak akses, kebocoran data, API, widget; dijalankan otomatis di GitHub Actions setiap push. Ditambah uji pengguna [ISI].
17. **Kenapa Laravel?**
    Matang untuk aplikasi data dan peran (autentikasi, validasi, antrean, penjadwal, Sanctum), dan saya sudah memakainya untuk sistem peternakan yang berjalan di produksi.
18. **Bisa menampung berapa pengguna?**
    Jujur: belum diuji beban besar. Desainnya siap ditingkatkan: rata-rata harian disimpan di tabel tersendiri (bukan dihitung ulang tiap kunjungan), papan memakai cache, dan pemrosesan pesan bisa dipindah ke antrean worker.
19. **API-nya terbuka untuk siapa?**
    Tiga jalur: widget dan CSV terbuka tanpa token (hanya angka gabungan), API JSON dengan token yang dibuat koordinator, berlaku 6 bulan, bisa dicabut, dan dibatasi 60 permintaan per menit.

### Privasi & etika
20. **Data pribadi peternak aman?**
    Papan publik, widget, CSV, dan API tidak memuat nama atau nomor. Pengepul baru melihat nomor peternak setelah keduanya sepakat. Isi pesan yang dikirim ke AI hanya teks laporan, bukan nomor HP.
21. **Sesuai UU Pelindungan Data Pribadi?**
    Kami mengumpulkan data seminimal mungkin (nama, wilayah, komoditas), tujuan jelas, peternak bisa berhenti menerima ringkasan dengan STOP, dan koordinator bisa menonaktifkan peternak. Penghapusan data atas permintaan peternak perlu ditambahkan sebagai fitur berikutnya. Kebijakan privasi tertulis termasuk rencana berikutnya.
22. **Apakah memakai AI dalam pembuatan karya?**
    Jawab jujur sesuai yang terjadi: alat bantu AI dipakai untuk mempercepat penulisan kode dan dokumen, sedangkan ide, keputusan desain, pengujian dengan pengguna, dan pemahaman sistem adalah milik saya. Tunjukkan bahwa Anda bisa menjelaskan kode mana pun yang ditanya.

### Bisnis & keberlanjutan
23. **Siapa yang membayar biaya server dan WhatsApp?**
    Untuk peternak selamanya gratis. Opsi pendanaan: langganan pengepul untuk fitur lanjutan (notifikasi stok, riwayat), kerja sama koperasi/dinas yang memakai data, dan sponsor CSR. [ISI: perkiraan biaya bulanan Fonnte + hosting setelah dicek.]
24. **Bagaimana kalau nomor WhatsApp diblokir?**
    Pesan keluar dibatasi (800/hari), hanya membalas orang yang menghubungi lebih dulu, dan siap pindah ke WhatsApp Cloud API resmi milik Meta karena pengiriman sudah dipisah di satu layanan (`Messenger`).
25. **Bagaimana menyebar ke peternak lain?**
    Lewat koordinator: kelompok ternak, koperasi, penyuluh. Satu koordinator memverifikasi anggota yang dikenalnya. Ringkasan harga pagi (MULAI) membuat peternak kembali setiap hari.
26. **Kenapa Bali dan Yogyakarta?**
    Bali adalah tempat asal saya dan usaha keluarga; Yogyakarta lokasi lomba dan memiliki persentase petani gurem tertinggi di Jawa (87,75%). Wilayah dan komoditas cukup ditambah di database.

### Tim (untuk peserta individu)
27. **Anda bekerja sendiri, bagaimana membagi pekerjaan?**
    Saya memegang semua peran, tetapi bekerja dengan proses: papan tugas, tes otomatis sebelum setiap perubahan, pemisahan lingkungan uji dan produksi, dan uji lapangan dengan pengguna asli. Tunjukkan papan tugas / riwayat commit bila diminta.
28. **Apa kesulitan terbesar?**
    [ISI dengan pengalaman nyata, misalnya: membaca cara menulis harga yang sangat beragam; contoh pesan dari uji pengguna yang awalnya gagal dibaca.]

## C. Kebiasaan menjawab
- Bila tidak tahu: "Itu belum saya uji / belum saya pertimbangkan. Cara saya akan mengujinya adalah …"
- Jangan menyebut angka yang tidak bisa ditunjukkan.
- Selalu kembalikan ke peternak: siapa yang diuntungkan dan buktinya.
