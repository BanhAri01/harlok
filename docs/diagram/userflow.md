# Alur Pengguna (User Flow) 4 Peran — Juru Harga

Dokumen ini memodelkan diagram alur pengguna (*User Flow*) untuk empat aktor utama sistem **Juru Harga**, yang dibangun secara tepat mengikuti logika kode sumber pada `app/Chat/ChatBot.php`, `app/Services/OfferService.php`, `app/Http/Controllers/BuyerController.php`, `app/Http/Controllers/AdminController.php`, dan `app/Http/Controllers/Api/PriceApiController.php`.

---

## 1. Alur Peternak (WhatsApp Finite State Machine)

Alur percakapan WhatsApp peternak dikontrol oleh mesin status (*finite state machine*) pada tabel `conversations` dengan status: `reg_name` $\rightarrow$ `reg_region` $\rightarrow$ `reg_commodity` $\rightarrow$ `idle` $\rightarrow$ `confirm_report` $\rightarrow$ `offer_reply` $\rightarrow$ `counter_price`.

```mermaid
%%{init: {'theme': 'base', 'themeVariables': { 'primaryColor': '#163326', 'primaryTextColor': '#F1F4EE', 'primaryBorderColor': '#F5B700', 'lineColor': '#163326', 'secondaryColor': '#F1F4EE', 'tertiaryColor': '#F5B700'}}}%%
flowchart TD
    Start([📱 Peternak Kirim Chat WhatsApp]) --> CheckFarmer{Nomor Terdaftar<br/>di Tabel Farmers?}

    %% Sub-alur Pendaftaran
    subgraph ALUR_PENDAFTARAN["Alur Pendaftaran (3 Langkah Mudah)"]
        CheckFarmer -- "Belum Terdaftar" --> S_RegStart["Bot: 'Selamat datang! Siapa nama Bapak/Ibu?'<br/>(State: reg_name)"]
        S_RegStart --> InputName["Peternak balas: Nama<br/>(mis. Made Sukarta)"]
        InputName --> ValidName{Nama Valid?<br/>2-60 Karakter}
        ValidName -- "Tidak" --> S_RegStart
        ValidName -- "Ya" --> S_RegRegion["Bot: 'Lokasi di mana? Balas nomornya:'<br/>(Daftar 14 Wilayah Bali & DIY)<br/>(State: reg_region)"]
        S_RegRegion --> InputRegion["Peternak balas: Nomor / Nama Wilayah<br/>(nomor sesuai daftar)"]
        InputRegion --> ValidRegion{Nomor Valid?}
        ValidRegion -- "Tidak" --> S_RegRegion
        ValidRegion -- "Ya" --> S_RegCommodity["Bot: 'Komoditas utama? Balas nomornya:'<br/>(Telur, Broiler, Cabai, Beras, Jagung)<br/>(State: reg_commodity)"]
        S_RegCommodity --> InputCommodity["Peternak balas: Nomor Komoditas"]
        InputCommodity --> ValidCommodity{Nomor Valid?}
        ValidCommodity -- "Tidak" --> S_RegCommodity
        ValidCommodity -- "Ya" --> SaveFarmer["✅ Simpan Peternak Baru<br/>Reset State ke 'idle'"]
        SaveFarmer --> BotWelcome["Bot: 'Terdaftar! Kapan pun ada stok, kirim saja: telur 2 ton 26rb'"]
    end

    %% State Idle & Perintah Umum
    subgraph ALUR_IDLE["Alur Menu & Pelaporan (State: idle)"]
        CheckFarmer -- "Sudah Terdaftar" --> S_Idle["State: idle<br/>(Siap Menerima Pesan)"]
        BotWelcome --> S_Idle

        S_Idle --> UserInput{"Teks Pesan Peternak"}

        UserInput -- "MENU / BANTUAN" --> CmdMenu["Tampilkan Daftar Bantuan & Format"]
        UserInput -- "HARGA / HARGA CABAI" --> CmdHarga["Tampilkan Ringkasan Rata-rata Wilayah<br/>dan Tren Naik/Turun (▲/▼)"]
        UserInput -- "STOK" --> CmdStok["Tampilkan Daftar Stok Aktif Peternak"]
        UserInput -- "HABIS / LAKU" --> CmdHabis["Tutup & Arsipkan Stok Aktif Terakhir"]
        UserInput -- "MULAI / STOP" --> CmdDigest["Langganan Ringkasan Pagi (Pukul 06.00)"]
        UserInput -- "TAWARAN" --> CmdTawaran{Ada Tawaran<br/>Menunggu?}
        UserInput -- "Lapor Stok Biasa<br/>(mis. telur 2 ton 26rb)" --> ParseReport["Proses Penguraian (Parser):<br/>1. Regex ReportParser<br/>2. Cadangan AiReportParser"]

        CmdTawaran -- "Tidak Ada" --> S_Idle
        CmdTawaran -- "Ada" --> MoveToOfferReply["Pindah State ke 'offer_reply'"]
    end

    %% Sub-alur Validasi & Konfirmasi Laporan
    subgraph ALUR_KONFIRMASI_LAPORAN["Alur Konfirmasi Laporan Stok (State: confirm_report)"]
        ParseReport --> CheckComplete{Data Lengkap?<br/>Komoditas, Qty, Harga}
        CheckComplete -- "Tidak Lengkap" --> AskMissing["Bot minta bagian yang kurang"] --> S_Idle
        CheckComplete -- "Lengkap" --> CheckPlausible{Harga Masuk Akal?<br/>min_price s/d max_price}
        CheckPlausible -- "Tidak Wajar" --> RejectPlausible["Bot: 'Harga tampak tidak wajar, mohon cek lagi'"] --> S_Idle
        CheckPlausible -- "Wajar" --> CreatePending["Simpan ke DB: stock_reports<br/>(Status: pending)<br/>Pindah ke State: confirm_report"]

        CreatePending --> BotAskConfirm["Bot: 'Mohon cek: Telur 2.000 kg @ Rp26.000/kg Tabanan.<br/>Balas YA untuk simpan, UBAH bila salah.'"]
        BotAskConfirm --> ReplyConfirm{"Jawaban Peternak"}

        ReplyConfirm -- "UBAH / TIDAK" --> CancelReport["Laporan Dibatalkan (status: cancelled)<br/>Reset State ke 'idle'"] --> S_Idle
        ReplyConfirm -- "YA / OKE" --> ConfirmLogic["Eksekusi ReportService.confirm():<br/>1. Tutup stok lama komoditas sama<br/>2. Bandingkan dengan rata-rata wilayah"]

        ConfirmLogic --> CheckOutlier{Deviasi > 30%<br/>dari Pasar?}
        CheckOutlier -- "Ya (Janggal)" --> FlagReport["Status: FLAGGED<br/>Bot: 'Ditahan dulu untuk cek koordinator'"]
        CheckOutlier -- "Tidak (Wajar)" --> SaveConfirmed["Status: CONFIRMED<br/>Hitung ulang daily_prices<br/>Kirim WA Notifikasi ke Pengepul Terdekat"]
        SaveConfirmed --> BotSaved["Bot: '✅ Tersimpan! Rata-rata Tabanan: Rp26.400/kg.<br/>Harga Anda Rp400 di bawah rata-rata.'"]
        FlagReport --> ResetToIdle1["Reset State ke 'idle'"] --> S_Idle
        BotSaved --> ResetToIdle1
    end

    %% Sub-alur Menjawab Tawaran Pembeli
    subgraph ALUR_MENJAWAB_TAWARAN["Alur Menjawab Tawaran Pengepul (State: offer_reply & counter_price)"]
        MoveToOfferReply --> DisplayOffer["Bot kirim detail tawaran:<br/>'UD Sumber Telur menawar 1.000 kg @ Rp23.000/kg<br/>⚠️ Tawaran 13% di bawah rata-rata Tabanan.<br/>Balas: 1=Terima, 2=Tolak, 3=Tawar balik'"]
        DisplayOffer --> OfferChoice{"Pilihan Peternak"}

        OfferChoice -- "1 (Terima)" --> AcceptOffer["Status Offer: ACCEPTED<br/>Status Stok: SOLD (Stok Terkunci)<br/>Tawaran lain otomatis REJECTED<br/>Kontak Dibuka via WA: '🤝 Sepakat!'"] --> ResetOfferIdle["Reset ke 'idle'"] --> S_Idle
        OfferChoice -- "2 (Tolak)" --> RejectOffer["Status Offer: REJECTED<br/>Bot kabari pengepul via web/WA<br/>Stok tetap aktif"] --> ResetOfferIdle
        OfferChoice -- "3 (Tawar Balik)" --> AskCounter["Bot: 'Berapa harga yang Anda minta per kg?<br/>Contoh: 27rb'<br/>Pindah ke State: counter_price"]

        AskCounter --> InputCounter["Peternak balas: 27rb"]
        InputCounter --> CheckCounterPrice{Harga Terbaca &<br/>Wajar?}
        CheckCounterPrice -- "Salah / Batal" --> AskCounter
        CheckCounterPrice -- "Valid" --> SaveCounter["Status Offer: COUNTERED<br/>counter_price: Rp27.000<br/>Notifikasi masuk ke web Pengepul<br/>Bot: '✅ Tawaran balik terkirim'"] --> ResetOfferIdle
    end

    classDef darkGreen fill:#163326,stroke:#F5B700,stroke-width:2px,color:#F1F4EE;
    classDef goldAccent fill:#F5B700,stroke:#163326,stroke-width:2px,color:#14201A;
    classDef paperLight fill:#F1F4EE,stroke:#163326,stroke-width:2px,color:#14201A;
    
    class S_RegStart,S_RegRegion,S_RegCommodity,SaveFarmer,S_Idle,CreatePending,SaveConfirmed,AcceptOffer,SaveCounter darkGreen;
    class Start,UserInput,ReplyConfirm,OfferChoice,CheckFarmer,ValidName,ValidRegion,ValidCommodity,CheckComplete,CheckPlausible,CheckOutlier,CheckCounterPrice goldAccent;
    class InputName,InputRegion,InputCommodity,CmdMenu,CmdHarga,CmdStok,CmdHabis,CmdDigest,ParseReport,BotAskConfirm,DisplayOffer,AskCounter paperLight;
```

---

## 2. Alur Pengepul (Pedagang Pembeli di Web)

Pengepul menggunakan antarmuka web responsif (`/pengepul`) untuk mencari pasokan terdekat dan bertransaksi secara transparan:

```mermaid
%%{init: {'theme': 'base', 'themeVariables': { 'primaryColor': '#163326', 'primaryTextColor': '#F1F4EE', 'primaryBorderColor': '#F5B700', 'lineColor': '#163326', 'secondaryColor': '#F1F4EE', 'tertiaryColor': '#F5B700'}}}%%
flowchart TD
    LoginPengepul["🚚 Pengepul Masuk di /masuk<br/>(Akun Didaftarkan Koordinator)"] --> DashboardBuyer["Dashboard Cari Stok (/pengepul)<br/>Peta Interaktif Leaflet + Daftar Stok"]

    DashboardBuyer --> ActionBuyer{"Pilihan Aksi Pengepul"}

    %% Aksi Cari Stok
    ActionBuyer -- "Eksplorasi Stok" --> FilterStok["Filter Komoditas (Telur, Ayam, Cabai)<br/>& Pilih Wilayah"]
    FilterStok --> ViewPin["Periksa Warna Pin di Peta:<br/>🟢 Sekitar Rata-rata<br/>🔴 Di Bawah Pasar<br/>🔵 Di Atas Pasar<br/>⚪ Belum Terverifikasi"]
    ViewPin --> ClickStock["Klik Baris Stok / Pin Peta"]

    ClickStock --> FormTawar["Buka Modal Form Penawaran:<br/>• Isi Harga per kg yang Diajukan<br/>• Isi Jumlah kg yang Diminta<br/>• Sistem tampilkan selisih % dari pasar"]
    FormTawar --> SubmitOffer["Klik 'Kirim Tawaran'<br/>POST /pengepul/stok/{report}/tawar"]

    SubmitOffer --> ValOffer{Validasi Sistem:<br/>1. Stok Masih Tersedia?<br/>2. Qty <= Stok?<br/>3. Bukan Demo menawar WA asli?<br/>4. Belum ada tawaran gantung?}

    ValOffer -- "Gagal" --> ShowErr["Tampilkan Pesan Kesalahan"] --> FormTawar
    ValOffer -- "Lolos" --> CreateOfferDB["✅ Simpan DB offers (Status: sent)<br/>Kunci Transaksi (lockForUpdate)<br/>Messenger kirim WhatsApp ke Peternak"]

    %% Aksi Monitoring
    ActionBuyer -- "Pantau Transaksi" --> ViewOffers["Buka Halaman 'Tawaran Saya'<br/>(/pengepul/tawaran)"]
    CreateOfferDB --> ViewOffers

    ViewOffers --> OfferStatus{"Status Tawaran Peternak"}

    OfferStatus -- "Status: SENT" --> WaitFarmer["Menunggu Tanggapan Peternak via WA<br/>(Pengepul bisa klik 'Batal' bila berubah pikiran)"]
    OfferStatus -- "Status: REJECTED" --> OfferRejected["Ditolak Peternak<br/>(Transaksi Berakhir)"]
    OfferStatus -- "Status: COUNTERED" --> FarmerCountered["Peternak Menawar Balik<br/>(Muncul nominal harga baru & tombol 'Setujui')"]

    FarmerCountered --> AcceptCounter["Klik 'Setujui'<br/>POST /pengepul/tawaran/{offer}/terima"]
    AcceptCounter --> LockDeal["Eksekusi OfferService.accept():<br/>1. Status Offer ➔ ACCEPTED<br/>2. Status Stok ➔ SOLD (Terkunci)<br/>3. Tawaran pembeli lain otomatis ditolak"]

    OfferStatus -- "Status: ACCEPTED" --> DealDone["🤝 KESEPAKATAN TERCAPAI!<br/>• Kontak & Nama Peternak Terbuka di Layar<br/>• Peternak menerima kontak pengepul via WA<br/>• Transaksi fisik & pembayaran dilakukan langsung"]
    LockDeal --> DealDone

    classDef darkGreen fill:#163326,stroke:#F5B700,stroke-width:2px,color:#F1F4EE;
    classDef goldAccent fill:#F5B700,stroke:#163326,stroke-width:2px,color:#14201A;
    classDef paperLight fill:#F1F4EE,stroke:#163326,stroke-width:2px,color:#14201A;

    class LoginPengepul,DashboardBuyer,CreateOfferDB,LockDeal,DealDone darkGreen;
    class ActionBuyer,ValOffer,OfferStatus goldAccent;
    class FilterStok,ViewPin,ClickStock,FormTawar,SubmitOffer,ViewOffers,FarmerCountered,AcceptCounter paperLight;
```

---

## 3. Alur Koordinator (Admin Koperasi & Validasi Data)

Koordinator memastikan keabsahan data sebelum memengaruhi harga pasar agregat wilayah:

```mermaid
%%{init: {'theme': 'base', 'themeVariables': { 'primaryColor': '#163326', 'primaryTextColor': '#F1F4EE', 'primaryBorderColor': '#F5B700', 'lineColor': '#163326', 'secondaryColor': '#F1F4EE', 'tertiaryColor': '#F5B700'}}}%%
flowchart TD
    LoginAdmin["🧑‍💼 Koordinator Masuk (/masuk)<br/>Peran: admin"] --> DashboardAdmin["Dashboard Koordinator (/admin)<br/>Ringkasan: Peternak Baru, Laporan Janggal, Log Pesan"]

    DashboardAdmin --> MenuAdmin{"Pilih Menu Pengelolaan"}

    %% Verifikasi Peternak
    MenuAdmin -- "1. Verifikasi Peternak" --> ListFarmers["Buka Menu Peternak (/admin/peternak)<br/>Daftar peternak berstatus verified_at = null"]
    ListFarmers --> CheckIdentity["Verifikasi Identitas Peternak<br/>(Telepon / Cek Anggota Kelompok Ternak)"]
    CheckIdentity --> ClickVerify["Klik 'Verifikasi'<br/>POST /admin/peternak/{farmer}/verifikasi"]
    ClickVerify --> VerifiedOK["✅ verified_at Terisi.<br/>Laporan harga dari peternak ini mulai dihitung<br/>pada pembentukan harga rata-rata wilayah"]

    %% Tinjau Laporan Janggal
    MenuAdmin -- "2. Tinjau Harga Janggal" --> ListFlagged["Dashboard: Daftar Laporan Flagged<br/>(Laporan dengan selisih >30% dari pasar)"]
    ListFlagged --> ReviewReport["Periksa Alasan Penahanan & Riwayat Peternak"]
    ReviewReport --> DecisionReport{"Keputusan Koordinator"}

    DecisionReport -- "Harga Sah / Wajar" --> ApproveReport["Klik 'Tampilkan' (Approve)<br/>POST /admin/laporan/{report}/setujui"]
    ApproveReport --> ApprExec["Status ➔ CONFIRMED<br/>Hitung ulang rata-rata pasar<br/>Notifikasi WA ke peternak & pengepul terdekat"]

    DecisionReport -- "Harga Salah / Fiktif" --> RejectReport["Klik 'Tolak' (Reject)<br/>POST /admin/laporan/{report}/tolak"]
    RejectReport --> RejExec["Status ➔ REJECTED<br/>Kirim pesan edukatif WA ke peternak<br/>Laporan tidak pernah masuk ke pasar"]

    %% Manajemen Pengguna & Token
    MenuAdmin -- "3. Pengguna & Token API" --> ManageUsers["Kelola Pengguna (/admin/pengguna)"]
    ManageUsers --> UserAction{"Tindakan"}
    UserAction -- "Buat Akun Pengepul/Mitra" --> CreateUserForm["Isi Nama, Email, Peran, No. HP, Wilayah"] --> SaveUser["Simpan User Baru"]
    UserAction -- "Terbitkan Token API" --> CreateTokenForm["Pilih Akun Mitra (Role: partner)<br/>POST /admin/pengguna/{user}/token"]
    CreateTokenForm --> GenerateSanctum["Terbitkan Personal Access Token Sanctum<br/>(Kemampuan: prices:read, kedaluwarsa: 6 bulan)"]
    GenerateSanctum --> ShareToken["Salin Token & Berikan ke Mitra Data"]

    %% Jejak Audit & Pesan
    MenuAdmin -- "4. Audit & Pengawasan" --> ViewLogs["Buka Log Pesan Masuk/Keluar (/admin/pesan)<br/>& Jejak Audit Transaksi (/admin/audit)"]

    classDef darkGreen fill:#163326,stroke:#F5B700,stroke-width:2px,color:#F1F4EE;
    classDef goldAccent fill:#F5B700,stroke:#163326,stroke-width:2px,color:#14201A;
    classDef paperLight fill:#F1F4EE,stroke:#163326,stroke-width:2px,color:#14201A;

    class LoginAdmin,DashboardAdmin,VerifiedOK,ApprExec,GenerateSanctum darkGreen;
    class MenuAdmin,DecisionReport,UserAction goldAccent;
    class ListFarmers,CheckIdentity,ClickVerify,ListFlagged,ReviewReport,ApproveReport,RejectReport,RejExec,ManageUsers,CreateUserForm,SaveUser,CreateTokenForm,ShareToken,ViewLogs paperLight;
```

---

## 4. Alur Mitra API (Konsumsi Data Terbuka / Interconnected Solutions)

Mewujudkan tema *"Engineering Interconnected Web Solutions"*, sistem membuka integrasi data harga tanpa membocorkan privasi individu:

```mermaid
%%{init: {'theme': 'base', 'themeVariables': { 'primaryColor': '#163326', 'primaryTextColor': '#F1F4EE', 'primaryBorderColor': '#F5B700', 'lineColor': '#163326', 'secondaryColor': '#F1F4EE', 'tertiaryColor': '#F5B700'}}}%%
flowchart TD
    MitraStart(["🏛️ Mitra Sistem<br/>(Dinas Ketahanan Pangan / Koperasi / Peneliti / App Lain)"]) --> GetToken["Dapatkan Sanctum Bearer Token<br/>dari Koordinator Juru Harga"]

    GetToken --> ReadDocs["Pelajari Spesifikasi Endpoint<br/>di /dokumentasi-api"]

    ReadDocs --> PrepareReq["Susun Permintaan HTTP REST Client:<br/>Header: Authorization: Bearer <TOKEN><br/>Header: Accept: application/json"]

    PrepareReq --> ChooseEndpoint{"Pilih Endpoint API"}

    %% Endpoint Wilayah & Komoditas
    ChooseEndpoint -- "Katalog Master" --> ReqMaster["Panggil:<br/>GET /api/v1/komoditas<br/>GET /api/v1/wilayah"]

    %% Endpoint Harga Terbaru
    ChooseEndpoint -- "Harga Pasar Terkini" --> ReqPrices["Panggil:<br/>GET /api/v1/harga?komoditas=telur&wilayah=tabanan"]

    %% Endpoint Riwayat Tren
    ChooseEndpoint -- "Riwayat Tren Deret Waktu" --> ReqHistory["Panggil:<br/>GET /api/v1/harga/riwayat?komoditas=telur&wilayah=tabanan&hari=30"]

    ReqMaster --> SecurityCheck
    ReqPrices --> SecurityCheck
    ReqHistory --> SecurityCheck

    subgraph KEAMANAN_API["Lapisan Keamanan & Middleware API"]
        SecurityCheck{Evaluasi Request}
        SecurityCheck -- "Token Hilang / Salah" --> Resp401["HTTP 401 Unauthorized"]
        SecurityCheck -- "Role Bukan Partner/Admin" --> Resp403["HTTP 403 Forbidden"]
        SecurityCheck -- "Melebihi 60 req/menit" --> Resp429["HTTP 429 Too Many Requests<br/>(Throttle: partner-api)"]
        SecurityCheck -- "Valid & Terautentikasi" --> ProcessQuery["Eksekusi PriceApiController<br/>Query dari Tabel daily_prices"]
    end

    ProcessQuery --> Resp200["HTTP 200 OK (JSON Payload Terstruktur):<br/>• komoditas & wilayah<br/>• avg_price (rata-rata), min, max, median<br/>• reports_count (jumlah sampel)<br/>• change (selisih dari hari sebelumnya)<br/>• date (tanggal data)"]

    Resp200 --> ConsumeData["Data Dimanfaatkan Mitra:<br/>1. Dasbor Ketahanan Pangan Daerah<br/>2. Sinkronisasi Harga di Aplikasi Koperasi<br/>3. Analisis Inflasi & Riset Akademik"]

    classDef darkGreen fill:#163326,stroke:#F5B700,stroke-width:2px,color:#F1F4EE;
    classDef goldAccent fill:#F5B700,stroke:#163326,stroke-width:2px,color:#14201A;
    classDef paperLight fill:#F1F4EE,stroke:#163326,stroke-width:2px,color:#14201A;

    class MitraStart,ProcessQuery,Resp200,ConsumeData darkGreen;
    class ChooseEndpoint,SecurityCheck goldAccent;
    class GetToken,ReadDocs,PrepareReq,ReqMaster,ReqPrices,ReqHistory,Resp401,Resp403,Resp429 paperLight;
```
