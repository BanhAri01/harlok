# Arsitektur Sistem — Harlok

Diagram ini memetakan arsitektur perangkat lunak **Harlok** secara menyeluruh, mulai dari kanal komunikasi di tingkat petani (*WhatsApp via Fonnte Gateway*), lapisan keamanan dan pengendali antrean, mesin percakapan berbasis *state machine*, mesin pengurai hibrida (*Regex Rule Parser* + cadangan *Claude AI*), lapisan layanan bisnis, basis data relasional, hingga berbagai kanal diseminasi data (papan publik, antarmuka web peran, dan REST API terbuka).

---

## Diagram Arsitektur (Mermaid)

```mermaid
%%{init: {'theme': 'base', 'themeVariables': { 'primaryColor': '#163326', 'primaryTextColor': '#F1F4EE', 'primaryBorderColor': '#F5B700', 'lineColor': '#163326', 'secondaryColor': '#F1F4EE', 'tertiaryColor': '#F5B700'}}}%%
flowchart TD
    subgraph KANAL_PENGGUNA["1. Kanal Pengguna & Aktor"]
        Peternak["👨‍🌾 Peternak / Petani Kecil<br/>(WhatsApp Tanpa Instal Aplikasi)"]
        Pengepul["🚚 Pengepul / Pedagang Pasar<br/>(Web Desktop & Mobile)"]
        Koordinator["🧑‍💼 Koordinator / Admin Koperasi<br/>(Web Panel Kontrol)"]
        Publik["👥 Masyarakat Umum<br/>(Papan Harga Publik & Widget)"]
        Mitra["🏛️ Mitra Sistem<br/>(Dinas Pangan, Koperasi, Peneliti)"]
    end

    subgraph GERBANG_KOMUNIKASI["2. Gerbang Masuk & Keamanan Jaringan"]
        Fonnte["📱 Fonnte WhatsApp Gateway API<br/>(Webhooks Forwarding)"]
        WebhookCtrl["🛡️ WhatsAppWebhookController<br/>• Verifikasi Rahasia (hash_equals)<br/>• Tolak Pesan Grup & Cegah Duplikasi<br/>• Rate Limit (240 req/menit)"]
        WebCtrl["🌐 Web & Auth Controllers<br/>• CSRF Protection & Throttle<br/>• Middleware Peran (Buyer, Admin)<br/>• Sanctum Token Auth (API)"]
    end

    subgraph LAPISAN_PEMROSESAN["3. Antrean, Status Percakapan & Pengurai Pesan"]
        InboundProc["⚡ InboundProcessor<br/>(Lock per nomor: chat:{phone})"]
        ChatBot["🤖 ChatBot (Finite State Machine)<br/>reg_name ➔ reg_region ➔ reg_commodity<br/>idle ➔ confirm_report ➔ offer_reply ➔ counter_price"]
        
        subgraph PENGURAI_PESAN["Mesin Pengurai Pesan Hibrida"]
            ReportParser["🔍 ReportParser (Pola Cepat & Gratis)<br/>Deteksi: ton, kw, kg, peti, tray, karung<br/>Angka: 26rb, 26.500, 26,5k, rp26000"]
            AiParser["🧠 AiReportParser (Cadangan Claude Haiku)<br/>JSON Schema Enforced<br/>Batas kuota harian per nomor"]
        end
    end

    subgraph LAYANAN_BISNIS["4. Layanan Bisnis Inti (Application Services)"]
        ReportSvc["📦 ReportService<br/>• Simpan Laporan Pending<br/>• Deteksi Harga Ekstrem (Flag >30%)<br/>• Verifikasi & Tutup Stok Lama<br/>• Notifikasi Pengepul Terdekat"]
        OfferSvc["🤝 OfferService<br/>• DB Transaction & lockForUpdate<br/>• Cegah Stok Terjual Ganda<br/>• Peringatan Tawaran Murah (⚠️ <10%)<br/>• Alur Terima (1), Tolak (2), Tawar Balik (3)"]
        PriceBoard["📊 PriceBoard & BoardData<br/>• Hitung Rata-rata, Median, Min, Max<br/>• Agregasi Harian per Wilayah (daily_prices)<br/>• Deteksi Deviasi Pasar"]
        Messenger["✉️ Messenger Service<br/>• Catat Log Masuk & Keluar<br/>• Batas Harian (800 pesan/hari)<br/>• Isolasi Nomor Simulator/Demo"]
    end

    subgraph BASIS_DATA["5. Basis Data Relasional (MariaDB / MySQL / SQLite)"]
        DB_Master[("Tabel Master:<br/>• regions<br/>• commodities<br/>• users")]
        DB_Trans[("Tabel Transaksi:<br/>• farmers<br/>• conversations<br/>• stock_reports<br/>• offers<br/>• daily_prices")]
        DB_Logs[("Tabel Pesan & Audit:<br/>• inbound_messages<br/>• outbound_messages<br/>• audit_logs<br/>• personal_access_tokens")]
    end

    subgraph KANAL_KELUARAN["6. Antarmuka Keluaran & Ekosistem Terhubung"]
        PapanPublik["🖥️ Papan Harga Pasar Publik<br/>• Pembaruan Otomatis 12 Detik<br/>• Grafik Tren 14 Hari (Chart.js)<br/>• Simulator Chat /coba"]
        PanelPengepul["🗺️ Panel Pengepul (/pengepul)<br/>• Peta Sebaran Stok (Leaflet OSM)<br/>• Form Penawaran Harga & Volume<br/>• Pantauan Tawaran Saya"]
        PanelAdmin["📋 Panel Koordinator (/admin)<br/>• Verifikasi Peternak Baru<br/>• Tinjau Laporan Janggal (Flagged)<br/>• Manajemen Token Mitra API"]
        RestApi["🔌 RESTful API v1 (/api/v1)<br/>• GET /api/v1/harga<br/>• GET /api/v1/harga/riwayat<br/>• GET /api/v1/wilayah & komoditas<br/>• Iframe Widget & Open CSV"]
    end

    %% Hubungan Alur Peternak
    Peternak -->|"Kirim Pesan WhatsApp<br/>contoh: telur 2 ton 26rb"| Fonnte
    Fonnte -->|"POST /webhook/wa/{secret}"| WebhookCtrl
    WebhookCtrl -->|"Simpan InboundMessage"| InboundProc
    InboundProc -->|"Handle State"| ChatBot
    ChatBot -->|"Penguraian Pola"| ReportParser
    ReportParser -.->|"Gagal Baca Pola"| AiParser
    AiParser -.->|"JSON Parsed"| ChatBot
    ChatBot -->|"Kelola Laporan"| ReportSvc
    ChatBot -->|"Kelola Tawaran"| OfferSvc
    ChatBot -->|"Ambil Data Rata-rata"| PriceBoard
    ReportSvc -->|"Hitung Ulang Harga"| PriceBoard

    %% Hubungan Layanan ke DB
    ReportSvc --> DB_Trans
    OfferSvc --> DB_Trans
    PriceBoard --> DB_Trans
    ChatBot --> DB_Trans
    InboundProc --> DB_Logs
    Messenger --> DB_Logs
    WebCtrl --> DB_Master

    %% Hubungan Balasan ke Peternak
    ReportSvc -->|"Kirim Notifikasi"| Messenger
    OfferSvc -->|"Kirim Notifikasi Tawaran"| Messenger
    ChatBot -->|"Balasan Percakapan"| Messenger
    Messenger -->|"HTTP POST Balas Chat"| Fonnte
    Fonnte -->|"Pesan Diterima Peternak"| Peternak

    %% Hubungan Web & Aktor Lain
    Pengepul <-->|"Akses Web, Lihat Peta & Tawar"| WebCtrl
    Koordinator <-->|"Akses Panel Admin & Verifikasi"| WebCtrl
    Publik <-->|"Akses Papan Terbuka"| WebCtrl
    Mitra <-->|"Panggilan API Ber-token"| WebCtrl

    %% Hubungan Controller ke Layanan & Kanal
    WebCtrl --> PanelPengepul
    WebCtrl --> PanelAdmin
    WebCtrl --> PapanPublik
    WebCtrl --> RestApi
    PanelPengepul --> OfferSvc
    PanelAdmin --> ReportSvc
    PapanPublik --> PriceBoard
    RestApi --> PriceBoard

    classDef darkGreen fill:#163326,stroke:#F5B700,stroke-width:2px,color:#F1F4EE;
    classDef goldAccent fill:#F5B700,stroke:#163326,stroke-width:2px,color:#14201A;
    classDef paperLight fill:#F1F4EE,stroke:#163326,stroke-width:2px,color:#14201A;
    
    class Peternak,Pengepul,Koordinator,Publik,Mitra goldAccent;
    class Fonnte,WebhookCtrl,WebCtrl paperLight;
    class InboundProc,ChatBot,ReportParser,AiParser,ReportSvc,OfferSvc,PriceBoard,Messenger darkGreen;
    class DB_Master,DB_Trans,DB_Logs paperLight;
    class PapanPublik,PanelPengepul,PanelAdmin,RestApi goldAccent;
```

---

## Deskripsi Rantai Komponen Utama

1. **WhatsApp Ingress (Fonnte Gateway & Webhook):**
   * Endpoint `POST /webhook/wa/{secret}` diamankan menggunakan pemeriksaan waktu konstan `hash_equals` pada parameter URL rahasia.
   * Pesan grup diabaikan secara tegas; duplikasi pesan ditangkal lewat pengecekan keunikan `external_id` Fonnte.
   * Respons HTTP 200 dikembalikan seketika ke Fonnte, sementara badan pesan diproses secara asinkron/terkunci lewat `InboundProcessor` untuk mencegah lonjakan waktu tunggu (*timeout*).

2. **Mesin Pengurai Pesan Hibrida (*Hybrid Report Parsing*):**
   * **Lapisan 1 (ReportParser):** Memanfaatkan ekspresi reguler (*regex*) deterministik yang sangat cepat dan bebas biaya. Mampu mengenali konversi multi-satuan lokal (1 ton = 1.000 kg, 1 kwintal = 100 kg, 1 peti telur = 15 kg, 1 tray telur = 1,9 kg) dan notasi harga informal (`26rb`, `26.500`, `26,5k`, `rp26000`).
   * **Lapisan 2 (AiReportParser):** Menjadi jaring cadangan menggunakan LLM Anthropic Claude Haiku. Format respons dipaksa menggunakan JSON Schema terstruktur dan dibatasi kuota harian per nomor telepon demi menjaga kehematan biaya operasional.
   * **Prinsip Validasi Ketat:** Hasil penguraian apa pun wajib melewati validasi batas harga wajar per komoditas (`min_price` dan `max_price`), serta **wajib dikonfirmasi ulang ke peternak ("YA/UBAH")** sebelum masuk ke basis data.

3. **Layanan Bisnis Inti:**
   * **ReportService:** Mengelola siklus hidup laporan stok (`pending` $\rightarrow$ `confirmed` / `flagged` $\rightarrow$ `closed` / `sold`). Mengotomatiskan penahanan (*flagging*) jika harga yang dilaporkan menyimpang lebih dari 30% dari harga pasar wilayah.
   * **OfferService:** Menjamin keandalan transaksi tawar-menawar menggunakan isolasi transaksi basis data dan penguncian pesimis (`lockForUpdate`), sehingga tidak ada stok yang dapat terjual ganda kepada dua pengepul berbeda.
   * **PriceBoard:** Menghitung statistik harian per wilayah (rata-rata terbobot, median, minimum, maksimum, dan jumlah laporan) hanya dari laporan peternak yang telah terverifikasi oleh koordinator.
   * **Messenger:** Mengatur seluruh transmisi pesan keluar WhatsApp, menegakkan batas harian (maks. 800 pesan/hari), mencatat log pengiriman, dan mencegah pengiriman tak sengaja ke nomor simulator atau nomor uji.

4. **Basis Data & Keamanan Akses:**
   * Memisahkan data master referensi, entitas operasional peternakan, jejak audit aktivitas (`audit_logs`), dan log transmisi pesan.
   * Menjamin privasi nomor telepon dan nama asli peternak; data kontak baru dibuka ke pembeli ketika kesepakatan harga telah disetujui kedua belah pihak.
