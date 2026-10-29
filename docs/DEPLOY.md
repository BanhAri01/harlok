# Deploy Harlok ke harlok.hermesworkss.com

Deploy hanya dijalankan atas perintah Ari. Server: cPanel Rapidplex, `ssh -p 64000 hermeswo@gavia.id.rapidplex.com`, PHP 8.5, MariaDB, DNS di Cloudflare.

## Persiapan sekali (oleh Ari)
1. Cloudflare: tambah record A `harlok` → `202.155.137.38` (awan abu-abu).
2. cPanel → Domains: buat subdomain `harlok.hermesworkss.com` dengan docroot `/home/hermeswo/harlok.hermesworkss.com/public`. Aktifkan SSL (AutoSSL).
3. cPanel → MySQL: buat database `hermeswo_harlok` dan user khusus dengan hak penuh hanya ke database itu.
4. Buat repo GitHub publik `harlok`, lalu `git remote add origin <url>` dan `git push origin main`.

## Pasang pertama kali (SSH)
```bash
cd ~ && git clone https://github.com/<akun>/harlok.git harlok.hermesworkss.com
cd ~/harlok.hermesworkss.com
/usr/local/bin/composer install --no-dev --optimize-autoloader
cp .env.example .env && php artisan key:generate
```

Isi `.env` (pakai editor di server, jangan kirim isinya ke chat):
```
APP_NAME="Harlok"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://harlok.hermesworkss.com
APP_TIMEZONE=Asia/Makassar
DB_CONNECTION=mysql
DB_DATABASE=hermeswo_harlok
DB_USERNAME=...
DB_PASSWORD=...
SESSION_SECURE_COOKIE=true
LAPOR_WA_DRIVER=fonnte
FONNTE_TOKEN=...
FONNTE_DEVICE=62...
LAPOR_WEBHOOK_SECRET=<openssl rand -hex 24>
ANTHROPIC_API_KEY=...
LAPOR_DEMO_PASSWORD=<sandi demo untuk juri>
```

Aset front-end dibangun di laptop (`npm run build`), lalu folder `public/build` diunggah karena server tidak punya Node:
```bash
scp -P 64000 -r public/build hermeswo@gavia.id.rapidplex.com:~/harlok.hermesworkss.com/public/
```

Lanjutkan di server:
```bash
php artisan migrate --force --seed
php artisan db:seed --class=SampleDataSeeder --force
php artisan lapor:admin <email-ari> "Ari"
php artisan storage:link
php artisan optimize
```

Cron: tambahkan baris berikut ke file crontab yang sudah ada (salin dulu `crontab -l > ~/crontab-sebelum-harlok-<tanggal>.txt`, edit, lalu `crontab ~/file.txt`; **jangan** memakai `( crontab -l ... ) | crontab -`):
```
* * * * * cd /home/hermeswo/harlok.hermesworkss.com && /usr/local/bin/php artisan schedule:run >> /home/hermeswo/cron-harlok.log 2>&1
```

Fonnte → Device → Webhook: `https://harlok.hermesworkss.com/webhook/wa/<LAPOR_WEBHOOK_SECRET>`.

## Pembaruan berikutnya
```bash
mkdir -p ~/backups/harlok
cd ~/harlok.hermesworkss.com
php artisan down
mysqldump hermeswo_harlok | gzip > ~/backups/harlok/db-$(date +%Y%m%d-%H%M%S).sql.gz
git pull --ff-only origin main
/usr/local/bin/composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear && php artisan optimize
php artisan up
```
Bila `public/build` berubah, unggah ulang dengan `scp` seperti di atas.

## Pemeriksaan setelah deploy
- `curl -sk --resolve harlok.hermesworkss.com:443:127.0.0.1 https://harlok.hermesworkss.com/` → 200.
- `/coba` bisa dipakai, papan harga tampil, `/api/v1/harga` tanpa token → 401.
- Kirim `halo` ke nomor WA Harlok, pastikan dibalas.
- `.env` tidak bisa diakses dari luar (docroot hanya folder `public`).

## Sebelum final
Hapus data contoh bila sudah ada data nyata cukup: `php artisan lapor:hapus-contoh`.
