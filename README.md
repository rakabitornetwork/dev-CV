# CV Landing

Halaman CV yang isinya diubah dari panel admin. Tampilan sudah dibangun di komputer pengembangan dan ikut tersimpan di `public/build`, jadi VPS tidak menjalankan npm.

Repositori: `https://github.com/rakabitornetwork/dev-CV.git`  
Cabang: `main`

## Yang perlu ada di VPS

- PHP 8.3 atau lebih baru, plus ekstensi `mbstring`, `xml`, `curl`, `zip`, `sqlite3`, `bcmath`, dan `intl`
- Composer
- Git
- Nginx atau Apache, dengan document root mengarah ke folder `public`
- Node.js tidak diperlukan

Contoh pemasangan paket di Debian atau Ubuntu:

```bash
sudo apt update
sudo apt install -y git composer nginx php8.4-fpm php8.4-cli php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip php8.4-sqlite3 php8.4-bcmath php8.4-intl unzip
```

Sesuaikan `php8.4` dengan versi PHP yang terpasang.

## Pemasangan pertama

Perintah di bawah dijalankan sebagai user yang menjalankan PHP (`www-data`), supaya halaman **Pembaruan** di panel nanti bisa menarik kode dari GitHub.

Jika repositorinya privat, buat deploy key lalu tempelkan kunci publiknya di GitHub (akses read):

```bash
sudo mkdir -p /var/www/.ssh
sudo chown www-data:www-data /var/www/.ssh
sudo -u www-data ssh-keygen -t ed25519 -f /var/www/.ssh/id_ed25519 -N ""
sudo -u www-data cat /var/www/.ssh/id_ed25519.pub
```

Clone, lalu siapkan lingkungan produksi:

```bash
sudo mkdir -p /var/www
sudo chown www-data:www-data /var/www
sudo -u www-data git clone git@github.com:rakabitornetwork/dev-CV.git /var/www/dev-cv
cd /var/www/dev-cv
sudo -u www-data composer install --no-dev --optimize-autoloader --no-interaction
sudo -u www-data cp .env.example .env
sudo -u www-data php artisan key:generate
sudo -u www-data touch database/database.sqlite
```

Ubah `.env` ini sebelum migrasi:

```dotenv
APP_NAME="CV"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://dev.teslatech.my.id
APP_LOCALE=id
APP_FALLBACK_LOCALE=id

DB_CONNECTION=sqlite

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Lanjutkan pemasangan:

```bash
sudo -u www-data php artisan migrate --force --seed
sudo -u www-data php artisan storage:link
sudo -u www-data php artisan optimize
sudo chown -R www-data:www-data /var/www/dev-cv
sudo chmod -R ug+rwx storage bootstrap/cache database
```

`--seed` hanya untuk pemasangan pertama. Menjalankannya lagi menghapus isi CV yang sudah diubah dari panel.

Panel admin: `https://dev.teslatech.my.id/admin/login`  
Email `amon@teslatech.my.id`, kata sandi `gantengmax`.

Ganti kata sandi setelah masuk:

```bash
sudo -u www-data php artisan tinker --execute="App\Models\User::query()->where('email', 'amon@teslatech.my.id')->update(['password' => 'kata-sandi-baru']);"
```

## Nginx

Document root harus folder `public`.

```nginx
server {
    listen 80;
    server_name dev.teslatech.my.id;
    root /var/www/dev-cv/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
sudo nginx -t
sudo systemctl reload nginx
sudo certbot --nginx -d dev.teslatech.my.id
```

## Pembaruan berikutnya

1. Di komputer pengembangan, jalankan `npm run build` setiap kali tampilan berubah.
2. Commit hasilnya, termasuk isi `public/build`, lalu push ke cabang `main`.
3. Di VPS, buka `/admin/pembaruan`, pilih **Cek GitHub**, lalu **Pasang pembaruan**.

Tombol itu menjalankan `git pull`, `composer install`, dan migrasi. Npm tidak dijalankan. Berkas `.env`, database, dan unggahan tidak ikut tertimpa.

Perintah yang sama dari SSH:

```bash
cd /var/www/dev-cv
sudo -u www-data php artisan app:update
```

Jika folder `public/build` sudah ada di VPS dari percobaan npm sebelumnya, hapus folder itu sekali sebelum pull pertama. Git sekarang membawa folder tersebut.

## Pengembangan lokal

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan storage:link
npm install
npm run build
php artisan serve
```
