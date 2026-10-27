# Rencana Juara — Juru Harga di UINIC 8.0

Dibuat 28 Okt 2026 dini hari. Pembagian kerja: **Ari** (pemilik, wajah tim, data lapangan), **Claude** (kode, deploy, keamanan, review), **Gemini** (riset, diagram, naskah). Tugas Claude/Gemini juga tercatat di `D:\Coding\PAPAN-TUGAS-AI.md`.

## 1. Cara juri menilai, dan di mana poin kita

| Tahap | Kriteria (bobot) | Kekuatan sekarang | Yang harus ditambah |
|---|---|---|---|
| Penyisihan (proposal + web) | Inovasi 30 | WA tanpa aplikasi, AI cadangan, tawar 1/2/3, API terbuka | bukti beda dari pesaing (tabel pembanding), fitur "interconnected" yang terlihat (widget, data terbuka) |
| | Kebutuhan pengguna 20 | cerita keluarga peternak | **uji pengguna nyata** + kutipan wawancara + data resmi BPS/APJII |
| | Teknis 20 | validasi, autentikasi, peran, 45 tes, keamanan webhook | CI GitHub Actions (badge tes lulus), spesifikasi OpenAPI, diagram ERD |
| | UI/UX 15 | papan kapur hijau-kuning, peta, grafik | rapikan tampilan HP, tangkapan layar rapi, audit aksesibilitas |
| | Dokumentasi 15 | README, dokumentasi API, panduan deploy | diagram arsitektur/user flow/ERD, proposal lengkap ≤30 hlm |
| Semifinal (video 2–4 mnt) | Inovasi 30, Kreativitas 25, Tema 25, Penyampaian 20 | — | cerita nyata di kandang + layar WA asli + papan harga berubah langsung |
| Final (presentasi) | Tanya jawab 40, Presentasi 25, Tim 20, PD 15 | Ari paham seluruh sistem | bank pertanyaan juri + latihan, demo langsung lewat WA juri |

**Pembeda utama yang harus selalu diulang:** *"Peternak tidak perlu instal apa pun. Satu chat WhatsApp menjadi data harga yang adil untuk satu wilayah."* Plus **bukti nyata**: nomor WA hidup yang bisa dicoba juri, dan peternak sungguhan (bapak Ari) yang sudah memakainya.

**Kelemahan yang harus diantisipasi:**
- Ikut sendiri → nilai "tim" di final. Jawaban: tunjukkan proses kerja yang tertata (papan tugas, tes otomatis, peran AI sebagai alat), dan bawa cerita pengguna nyata.
- Data masih contoh → ganti/lengkapi dengan laporan nyata sebelum semifinal. Jangan pernah menyebut data contoh sebagai data asli.
- Risiko nomor WA diblokir / biaya Fonnte → siapkan jawaban (batas pesan harian, nomor cadangan, bisa pindah ke WhatsApp Cloud API resmi).
- "Bedanya dengan Panel Harga pemerintah?" → milik pemerintah satu arah dan tingkat kota/provinsi; Juru Harga dua arah (lapor + tawar), tingkat kabupaten, langsung dari peternak, lewat WA.

## 2. Jadwal

### Rabu 28 Okt
- **Ari (pagi–siang, ±2 jam):**
  1. Beli kartu perdana khusus → daftar Fonnte → pindai QR. Isi `FONNTE_TOKEN`, `FONNTE_DEVICE` sendiri ke `.env` (jangan di chat).
  2. Buat `ANTHROPIC_API_KEY` (console.anthropic.com), isi ke `.env`.
  3. Buat repo GitHub publik `juru-harga` (kosong), kirim link-nya ke Claude.
  4. Daftar lewat bit.ly/PENDAFTARAN_WEBDEV_UINIC8 + bayar Rp65.000 (BSI 7373992407 a.n. Filza Nadira Yuksa). Simpan bukti.
  5. Cek nama "Juru Harga" di pdki-indonesia.dgip.go.id.
  6. Beri perintah deploy ke Claude.
- **Claude:** deploy ke `juruharga.hermesworkss.com` (setelah perintah + backup), sambungkan webhook, uji WA asli. Fitur penguat C2–C5 (lihat bagian 3).
- **Gemini:** G1 riset pesaing & data latar belakang, G2 diagram.

### Kamis 29 Okt — hari data nyata
- **Ari:** uji pengguna dengan **bapak + 2–4 peternak tetangga + 2 pengepul** (pakai WA asli). Ikuti `docs/UJI-PENGGUNA.md` (dibuat Claude). Rekam/foto (izin dulu), catat kutipan, isi SUS. Minta tiap peternak melapor harga sungguhan → data nyata pertama di papan.
- **Claude:** perbaiki apa pun yang gagal saat uji (pesan yang tidak dipahami bot → tambah pola), tangkapan layar untuk proposal, olah hasil SUS.
- **Gemini:** G3 naskah video & storyboard.

### Jumat 30 Okt — kirim (tenggat 23.59 WIB = Sabtu 00.59 WITA)
- **Ari:** isi semua [ISI ARI] di proposal, salin ke template Google Doc, tempel diagram + screenshot, ekspor PDF (`UINIC8#2026_WEBDEV_[Nama_Tim]_[Nama Instansi].pdf`), surat orisinalitas bermaterai.
- **Claude:** review proposal final (konsistensi angka, link, ≤30 hlm), cek demo online & repo publik dari jendela penyamaran.
- **Target kirim: Jumat 20.00 WITA.** Sisa waktu hanya untuk cadangan.

### 31 Okt – 5 Nov (menunggu pengumuman)
- Ari: terus kumpulkan laporan nyata (target ≥10 peternak, ≥50 laporan). Ini bahan kuat untuk video dan final.
- Claude: fitur lanjutan bila diputuskan Ari (pesan suara, kartu harga), CI, rapikan kode.

### Semifinal (5–15 Nov)
- 6–10 Nov: syuting (kandang, chat WA, papan harga, pengepul menawar), sunting 2–4 menit. Unggah GDrive publik **paling lambat 12 Nov** (target 10 Nov).
- Reels kolaborasi Best Favorite: unggah 15 Nov sebelum 15.00, sebar ke keluarga/kampus/kelompok ternak.
- TM wajib 17 Nov.

### Final (17–27 Nov)
- PPT dikumpulkan 25 Nov. Latihan presentasi 10 menit minimal 5 kali (rekam, putar ulang).
- Latihan tanya jawab dari bank pertanyaan (40% nilai!).
- Demo langsung: juri diminta mengirim WA "telur 1 ton 25rb" ke nomor Juru Harga, layar menampilkan papan harga berubah. Siapkan video cadangan bila internet gedung bermasalah.

## 3. Daftar penguat (urut prioritas)

| Kode | Untuk | Isi | Menaikkan |
|---|---|---|---|
| C1 | Claude | Deploy + WA asli hidup + data contoh tetap bertanda | semua |
| C2 | Claude | **Widget harga** untuk disematkan di situs koperasi/desa (`<iframe>`), dan **data terbuka CSV** harga harian (angka gabungan saja) | Inovasi, Tema |
| C3 | Claude | CI GitHub Actions (tes otomatis tiap push + badge), spesifikasi OpenAPI `docs/openapi.yaml` | Teknis, Dokumentasi |
| C4 | Claude | Rapikan tampilan HP + audit aksesibilitas + tangkapan layar proposal | UI/UX |
| C5 | Claude | `docs/UJI-PENGGUNA.md`: skenario tugas, lembar SUS, cara hitung, formulir izin | Kebutuhan pengguna |
| C6 | Claude | Bank 40 pertanyaan juri + jawaban, kerangka PPT & naskah presentasi | Final |
| C7 | Claude (perlu keputusan Ari) | **Pesan suara → teks** dengan AI (peternak cukup bicara). Butuh layanan ucapan-ke-teks berbayar | Inovasi (besar) |
| C8 | Claude (opsional) | Kartu harga harian berbentuk gambar untuk diteruskan ke status/grup WA | Kreativitas, penyebaran |
| G1 | Gemini | Riset pesaing + data latar belakang bersumber resmi | Inovasi, Kebutuhan |
| G2 | Gemini | Diagram: arsitektur, user flow 4 peran, ERD (Mermaid/draw.io) | Dokumentasi |
| G3 | Gemini | Naskah video 3 menit + storyboard + caption reels | Video |

## 4. Aturan main
- Jangan mengarang: hasil uji, jumlah pengguna, kutipan, data harga. Semua angka di proposal harus bisa ditunjukkan.
- Data pelanggan/peternak asli tidak dikirim ke AI atau layanan luar selain alur bot sendiri. Kunci & token hanya di `.env`.
- Deploy hanya atas perintah Ari, dengan backup dulu.
