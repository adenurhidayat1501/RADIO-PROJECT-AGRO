# SELF-HOSTED INTERNET RADIO PLATFORM (UBUNTU 24.04)

Platform radio internet / web streaming mandiri 24/7 self-hosted dengan arsitektur **PHP 8.2+**, **MongoDB**, **Icecast 2**, **Liquidsoap 2.x**, dan **FFmpeg**.

---

## 1. Arsitektur Penyiaran

```text
                         INTERNET
                            |
                            v
                       NGINX REVERSE PROXY
                      /                   \
                     /                     \
                    v                       v
             WEB APPLICATION            AUDIO STREAM
          (PHP-FPM + MongoDB)            (ICECAST 2)
                    |                         |
                    |                    LIQUIDSOAP
                    |                   /          \
                    |              AUTO DJ       LIVE DJ
                    |             (PLAYLIST)   (MIXXX/BUTT/OBS)
                    |                   \          /
                    |                    \        /
                    |                   STREAM OUT
                    |                         |
                    +-------------------------+
                                 |
                             LISTENERS
```

* **MongoDB**: Database utama dan satu-satunya untuk metadata lagu, playlist, jadwal siaran, user RBAC, akun DJ, atensi request lagu, telemetry statistik pendengar, dan pengaturan platform.
* **Icecast 2**: Streaming server engine yang menangani koneksi pendengar publik (`/live`).
* **Liquidsoap 2.x**: Automation engine untuk Auto DJ, smart crossfade, transisi otomatis antara Auto DJ dan Live DJ via input harbor (`/live-dj`), rotasi jingle/iklan, serta emergency fallback jika seluruh audio berhenti.
* **FFmpeg**: Pemeriksa integritas audio dan ekstraksi metadata ID3 tag.
* **Nginx**: Web server terpadu yang melayani antarmuka web, panel AdminLTE 4, dan mem-proxy stream audio live tanpa buffering lag.

---

## 2. Struktur Direktori Proyek

```text
radio-platform/
├── app/
│   ├── Controllers/       # Controller untuk Public, Admin & REST API
│   ├── Models/            # MongoDB BaseModel & 17 Model Collections
│   ├── Services/          # Icecast, Liquidsoap, Scheduler, Backup & DB Services
│   ├── Middleware/        # AuthMiddleware (RBAC), CsrfMiddleware, ApiAuthMiddleware
│   └── Helpers/           # Auth, Csrf, Logger, Id3TagReader, Response, functions
├── config/
│   ├── app.php            # Konfigurasi aplikasi, timezone & session
│   ├── database.php       # Konfigurasi koneksi MongoDB resmi
│   └── radio.php          # Konfigurasi Icecast, Liquidsoap & storage paths
├── database/
│   └── indexes.php        # Script pembuatan index MongoDB (compound & unique)
├── nginx/
│   └── radio.conf         # Konfigurasi Nginx VirtualHost & Reverse Proxy
├── public/
│   ├── assets/            # CSS, JS, dan Gambar (Player JS, Admin CSS)
│   ├── uploads/           # Direktori upload publik
│   └── index.php          # Front controller entrypoint
├── routes/
│   ├── web.php            # Rute antarmuka Web & Panel Admin
│   └── api.php            # Rute REST API & Liquidsoap internal hooks
├── scripts/
│   ├── generate-liquidsoap.php # Script CLI kompilasi konfigurasi radio.liq
│   ├── scheduler.php           # Worker daemon jadwal siaran per menit
│   └── sync-radio.php          # Worker daemon sinkronisasi statistik Icecast
├── storage/
│   ├── backups/           # Arsip backup mongodump (.tar.gz)
│   └── logs/              # Log sistem (app.log, stream.log, scheduler.log)
├── systemd/
│   ├── radio-liquidsoap.service # Unit service Liquidsoap AutoDJ
│   ├── radio-scheduler.service  # Unit service scheduler timetable
│   └── radio-sync.service       # Unit service listener monitor
├── views/
│   ├── admin/             # Tampilan panel AdminLTE 4 & Bootstrap 5
│   └── public/            # Tampilan website publik pendengar
├── .env.example
├── composer.json
└── install.sh             # Script installer otomatis untuk Ubuntu 24.04 VPS
```

---

## 3. Prasyarat Sistem & Server

Sebelum memulai instalasi, pastikan VPS Anda memenuhi kriteria berikut:

| Kebutuhan | Spesifikasi Minimal | Rekomendasi Produksi |
|---|---|---|
| **Sistem Operasi** | Ubuntu Server 24.04 LTS (64-bit) | Ubuntu Server 24.04 LTS (64-bit) |
| **CPU** | 1 Core vCPU | 2 Core vCPU atau lebih |
| **RAM** | 1 GB | 2 GB - 4 GB |
| **Penyimpanan** | 20 GB SSD | 50 GB+ SSD (tergantung koleksi file lagu MP3) |
| **Akses** | Akses `root` atau user dengan hak akses `sudo` | User `root` |
| **Domain (DNS)** | 1 Domain / Subdomain mengarah ke IP VPS | `radio.domain.com` (A Record -> IP VPS) |

### Port Firewall yang Harus Dibuka
Jika VPS Anda berada di balik firewall cloud (AWS Security Group, DigitalOcean Cloud Firewall, Oracle Cloud Security List, Linode, GCP, Contabo, dll), pastikan port berikut dibuka untuk inbound traffic:

* **Port 80 / TCP**: HTTP Web & Stream Proxy
* **Port 443 / TCP**: HTTPS SSL (Let's Encrypt)
* **Port 8000 / TCP**: Icecast Server Streaming Direct
* **Port 8005 / TCP**: Liquidsoap Live Harbor (Koneksi DJ Eksternal: Mixxx / BUTT / OBS)

---

## 4. Panduan Instalasi (Metode Otomatis - Direkomendasikan)

Script `install.sh` dirancang untuk menginstal dan mengonfigurasi seluruh stack secara otomatis dari sistem fresh Ubuntu 24.04.

### Langkah 4.1: Login ke VPS via SSH
Buka terminal di komputer Anda dan login ke server VPS:
```bash
ssh root@IP_SERVER_VPS_ANDA
```

### Langkah 4.2: Unduh / Unggah Source Code ke VPS
Pindahkan kode sumber platform radio ke folder `/var/www/radio-platform`:

**Opsi A: Menggunakan Git Clone**
```bash
git clone <URL_REPOSITORY_ANDA> /var/www/radio-platform
cd /var/www/radio-platform
```

**Opsi B: Menggunakan SCP / SFTP dari Komputer Lokal (PowerShell)**
```powershell
# Jalankan dari folder d:\RADIO AGRO di komputer lokal:
scp -r * root@IP_SERVER_VPS_ANDA:/var/www/radio-platform/
```

### Langkah 4.3: Jalankan Script Installer Otomatis
```bash
cd /var/www/radio-platform
chmod +x install.sh
sudo ./install.sh
```

### Langkah 4.4: Mengisi Parameter Interaktif Installer
Installer akan menampilkan prompt interaktif. Anda dapat menekan **ENTER** untuk menerima nilai default atau mengetikkan nilai kustom:

```text
Enter Radio Station Name [Radio Agro]: Radio Agro Nusantara
Enter Primary Domain Name [radio.example.com]: radio.domainanda.com
Enter Admin Initial Username [admin]: admin
Enter Admin Initial Password [leave blank for auto-generated]: PasswordRahasia123!
Enter Stream Mountpoint [/live]: /live
Enter Stream Bitrate (64, 128, 192, 256, 320) [128]: 128
Enter Icecast Source Password [auto-generated]: (Tekan Enter untuk acak)
Enter Icecast Admin Password [auto-generated]: (Tekan Enter untuk acak)
Enter DJ Harbor Password [auto-generated]: (Tekan Enter untuk acak)
```

Installer akan secara otomatis melakukan:
1. Pemasangan dependensi paket sistem Ubuntu.
2. Penambahan repositori resmi MongoDB 7.0/8.0 dan instalasi MongoDB Server.
3. Pemasangan Nginx, PHP 8.3/8.2 + FPM, Composer, Icecast2, Liquidsoap, FFmpeg, dan Certbot.
4. Pembuatan user sistem `radio` dan direktori audio `/var/lib/radio/music`, `/var/lib/radio/jingles`, `/var/lib/radio/fallback`.
5. Pembuatan file audio emergency fallback berformat MP3 128kbps via FFmpeg jika belum ada musik.
6. Pemasangan dependensi PHP PSR-4 via Composer (`composer install --no-dev -o`).
7. Pembuatan file `.env` produksi dengan enkripsi secret key 32-karakter.
8. Inisialisasi index database MongoDB (`database/indexes.php`) dan seeding akun admin.
9. Kompilasi konfigurasi Liquidsoap Auto DJ (`scripts/generate-liquidsoap.php`).
10. Pemasangan 3 unit systemd service:
    * `radio-liquidsoap.service`
    * `radio-scheduler.service`
    * `radio-sync.service`
11. Konfigurasi Nginx VirtualHost reverse proxy dan FastCGI.
12. Pembukaan port firewall UFW (80, 443, 8000, 8005).

---

## 5. Panduan Instalasi Manual (Langkah Demi Langkah)

Jika Anda ingin memahami alur instalasi atau melakukan kustomisasi arsitektur secara manual, ikuti tahapan berikut:

### Langkah 5.1: Update Sistem, Aktifkan Universe & Pasang Paket Dasar
```bash
sudo apt-get update -y && sudo apt-get upgrade -y
sudo apt-get install -y curl wget gnupg2 ca-certificates lsb-release apt-transport-https software-properties-common ufw git unzip

# Aktifkan repositori universe (wajib untuk icecast2 & liquidsoap di Ubuntu 24.04)
sudo add-apt-repository -y universe
sudo apt-get update -y
```

### Langkah 5.2: Pasang MongoDB 8.0 Resmi (Ubuntu 24.04 Noble)
```bash
# Tambahkan GPG Key resmi MongoDB 8.0
curl -fsSL https://pgp.mongodb.com/server-8.0.asc | sudo gpg --dearmor -o /usr/share/keyrings/mongodb-server-8.0.gpg --yes

# Tambahkan Repositori MongoDB 8.0 Noble
echo "deb [ arch=amd64,arm64 signed-by=/usr/share/keyrings/mongodb-server-8.0.gpg ] https://repo.mongodb.org/apt/ubuntu noble/mongodb-org/8.0 multiverse" | sudo tee /etc/apt/sources.list.d/mongodb-org-8.0.list

# Update & Pasang MongoDB Server & Tools
sudo apt-get update -y
sudo apt-get install -y mongodb-org mongodb-database-tools

# Nyalakan MongoDB Service
sudo systemctl daemon-reload
sudo systemctl enable mongod
sudo systemctl start mongod
```

### Langkah 5.3: Pasang Nginx, PHP 8.3 & Ekstensi
```bash
sudo apt-get install -y \
    nginx \
    php-fpm php-cli php-mongodb php-curl php-xml php-mbstring php-zip php-gd \
    icecast2 \
    liquidsoap \
    ffmpeg \
    certbot python3-certbot-nginx

# Optimasi batas upload PHP (untuk file audio hingga 128MB)
PHP_VER=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null || echo "8.3")
for ini in "/etc/php/${PHP_VER}/fpm/php.ini" "/etc/php/${PHP_VER}/cli/php.ini"; do
    if [ -f "$ini" ]; then
        sudo sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 128M/' "$ini"
        sudo sed -i 's/^post_max_size = .*/post_max_size = 128M/' "$ini"
        sudo sed -i 's/^memory_limit = .*/memory_limit = 256M/' "$ini"
        sudo sed -i 's/^max_execution_time = .*/max_execution_time = 300/' "$ini"
    fi
done
sudo systemctl restart "php${PHP_VER}-fpm"
```

### Langkah 5.4: Pasang Composer Secara Global
```bash
if ! command -v composer &> /dev/null; then
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
    sudo chmod +x /usr/local/bin/composer
fi
```

### Langkah 5.5: Buat User Sistem, Izin Akses & Direktori Audio
```bash
sudo useradd -r -s /usr/sbin/nologin -d /var/lib/radio radio 2>/dev/null || true

# Gabungkan user www-data dan radio agar berbagi akses file audio & log tanpa konflik
sudo usermod -a -G radio www-data
sudo usermod -a -G www-data radio

sudo mkdir -p /var/lib/radio/music
sudo mkdir -p /var/lib/radio/jingles
sudo mkdir -p /var/lib/radio/ads
sudo mkdir -p /var/lib/radio/fallback
sudo mkdir -p /var/lib/radio/playlists
sudo mkdir -p /etc/radio
sudo mkdir -p /var/log/radio
sudo mkdir -p /var/www/radio-platform

# Buat emergency fallback tone berdurasi 10 detik via FFmpeg
sudo ffmpeg -y -f lavfi -i "sine=frequency=440:duration=10" -c:a libmp3lame -b:a 128k /var/lib/radio/fallback/default.mp3

sudo chown -R radio:radio /var/lib/radio /var/log/radio /etc/radio
sudo chmod -R 2775 /var/lib/radio /var/log/radio /etc/radio
```

### Langkah 5.6: Setup Kode Sumber & Dependencies
```bash
cd /var/www/radio-platform

# Pasang dependensi composer
composer install --no-dev --optimize-autoloader

# Atur permissions
sudo chown -R www-data:www-data /var/www/radio-platform
sudo chown -R www-data:radio /var/www/radio-platform/storage /var/www/radio-platform/public/uploads
sudo chmod -R 2775 /var/www/radio-platform/storage /var/www/radio-platform/public/uploads

# Beri izin www-data untuk reload service Liquidsoap dari panel admin
echo 'www-data ALL=(ALL) NOPASSWD: /usr/bin/systemctl reload radio-liquidsoap, /usr/bin/systemctl restart radio-liquidsoap, /usr/bin/systemctl status radio-liquidsoap, /usr/bin/systemctl restart radio-scheduler, /usr/bin/systemctl restart radio-sync, /usr/bin/systemctl status radio-scheduler, /usr/bin/systemctl status radio-sync' | sudo tee /etc/sudoers.d/radio-platform
sudo chmod 440 /etc/sudoers.d/radio-platform
```

### Langkah 5.7: Konfigurasi File Lingkungan (`.env`)
Salin file `.env.example` ke `.env` lalu sesuaikan kredensial Anda:
```bash
cp .env.example .env
nano .env
```
Pastikan `APP_URL`, `MONGODB_URI`, `ICECAST_SOURCE_PASSWORD`, dan `LIQUIDSOAP_HARBOR_PASSWORD` telah disetel.

### Langkah 5.8: Konfigurasi Icecast 2
Edit `/etc/icecast2/icecast.xml` dan sesuaikan kata sandi:
```bash
sudo nano /etc/icecast2/icecast.xml
```
Pastikan kepemilikan dan `ENABLE=true` di `/etc/default/icecast2`:
```bash
sudo chown -R icecast2:icecast /etc/icecast2 /var/log/icecast2
sudo chmod 640 /etc/icecast2/icecast.xml
sudo sed -i 's/ENABLE=false/ENABLE=true/g' /etc/default/icecast2
sudo systemctl enable icecast2
sudo systemctl restart icecast2
```

### Langkah 5.9: Inisialisasi Database MongoDB & Index
Jalankan script pembentukan index dan data awal:
```bash
php database/indexes.php
```

Buat akun admin pertama melalui perintah one-liner PHP:
```bash
php -r "
require_once 'bootstrap.php';
use App\Models\User;
User::createUser([
    'username' => 'admin',
    'email' => 'admin@domainanda.com',
    'display_name' => 'Administrator',
    'password' => 'PasswordAdminAnda123!',
    'role' => 'admin',
    'status' => 'active'
]);
echo 'Admin siap digunakan.\n';
"
```

### Langkah 5.10: Kompilasi Konfigurasi Liquidsoap
```bash
php scripts/generate-liquidsoap.php
sudo chown radio:radio /etc/radio/radio.liq
sudo chmod 664 /etc/radio/radio.liq
```

### Langkah 5.11: Pasang Unit Service Systemd
Salin file service ke direktori `/etc/systemd/system/`:
```bash
sudo cp systemd/radio-liquidsoap.service /etc/systemd/system/
sudo cp systemd/radio-scheduler.service /etc/systemd/system/
sudo cp systemd/radio-sync.service /etc/systemd/system/

sudo systemctl daemon-reload
sudo systemctl enable radio-liquidsoap.service
sudo systemctl enable radio-scheduler.service
sudo systemctl enable radio-sync.service

sudo systemctl restart radio-liquidsoap.service
sudo systemctl restart radio-scheduler.service
sudo systemctl restart radio-sync.service
```

### Langkah 5.12: Konfigurasi Nginx
Salin konfigurasi Nginx dan sesuaikan nama domain:
```bash
sudo cp nginx/radio.conf /etc/nginx/sites-available/radio.conf
sudo sed -i 's/radio.yourdomain.com/radio.domainanda.com/g' /etc/nginx/sites-available/radio.conf
sudo ln -sf /etc/nginx/sites-available/radio.conf /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default

sudo nginx -t
sudo systemctl restart nginx
```

### Langkah 5.13: Konfigurasi Firewall UFW
```bash
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 8000/tcp
sudo ufw allow 8005/tcp
sudo ufw --force enable
```

---

## 6. Pemasangan Sertifikat SSL Gratis (HTTPS Let's Encrypt)

Agar audio streaming dan dashboard dapat diakses menggunakan protokol aman HTTPS (mencegah *mixed content error* pada browser Chrome, Firefox, dan Safari):

```bash
sudo certbot --nginx -d radio.domainanda.com
```

Certbot akan otomatis:
* Memvalidasi kepemilikan domain.
* Mengunduh sertifikat SSL gratis dari Let's Encrypt.
* Mengupdate file konfigurasi Nginx dengan SSL certificates dan redirect otomatis HTTP -> HTTPS.
* Memasang systemd timer untuk auto-renewal sertifikat setiap 90 hari.

---

## 7. Verifikasi & Pengujian Pasca-Instalasi

Setelah instalasi selesai, lakukan checklist verifikasi kesehatan sistem berikut:

### 1. Cek Status Service
Semua service berikut harus berstatus `active (running)`:
```bash
sudo systemctl status mongod
sudo systemctl status icecast2
sudo systemctl status radio-liquidsoap
sudo systemctl status radio-scheduler
sudo systemctl status radio-sync
sudo systemctl status nginx
```

### 2. Cek Port yang Sedang Listening
```bash
sudo ss -tulpn | grep -E '80|443|8000|8005|1234|27017'
```
Keterangan port yang aktif:
* `80` & `443`: Nginx Web Server
* `8000`: Icecast 2 Streaming
* `8005`: Liquidsoap Live Harbor (Input DJ)
* `1234`: Liquidsoap Telnet Control (Bind hanya ke `127.0.0.1`)
* `27017`: MongoDB Database (Bind hanya ke `127.0.0.1`)

### 3. Uji Pemutaran Stream di Browser
Buka browser dan akses:
* **Halaman Publik:** `http://radio.domainanda.com` (atau `https://` jika SSL aktif).
* Klik tombol **[LISTEN LIVE]**. Audio stream harus langsung berputar dengan indikator visualizer gelombang aktif.
* **Halaman Login Admin:** `http://radio.domainanda.com/admin/login`.
* Masuk menggunakan username dan password admin yang telah dibuat saat instalasi.

---

## 8. Panduan Siaran & Penggunaan Pertama Kali

### 1. Menambahkan Musik ke Music Library
1. Masuk ke Admin Panel (`/admin`).
2. Masuk ke menu **Media → Music Library**.
3. Klik tombol **Upload Audio Track**.
4. Pilih file audio berformat `.mp3` atau `.wav`.
5. Sistem akan menyimpan file ke direktori `/var/lib/radio/music/` pada VPS dan otomatis membaca ID3 tag (Judul, Artis, Album, Durasi) ke MongoDB.

### 2. Membuat Playlist & Mengaktifkan Rotasi
1. Buka menu **Media → Playlists**.
2. Klik **Create New Playlist** (misal: "Lagu Pagi Nusantara").
3. Aktifkan opsi **Randomize / Shuffle** dan **Smart Crossfade**.
4. Klik **Manage Tracks** lalu tambahkan lagu-lagu dari Music Library ke dalam playlist.
5. Klik menu **Settings** lalu klik tombol **Recompile Config & Reload Service** untuk menyegarkan Auto DJ Liquidsoap.

### 3. Mengatur Jadwal Siaran (Scheduler)
1. Buka menu **Broadcast Schedule → Weekly Schedule**.
2. Pilih hari siaran (Senin s/d Minggu).
3. Klik **Add Show Time Slot**.
4. Masukkan nama acara, jam mulai, jam selesai, dan tentukan mode:
   * **Auto DJ (Playlist Driven):** Memutar playlist tertentu pada jam tersebut.
   * **Live Studio (DJ on Air):** Mengalokasikan jam untuk siaran langsung penyiar.

---

## 9. Panduan Live DJ Broadcast (Mixxx / BUTT / OBS)

Ketika penyiar atau DJ ingin mengudara secara langsung (Live Studio):

1. **Buka Software Enkoder DJ** (Mixxx / BUTT / RadioBOSS / OBS).
2. **Konfigurasi Koneksi:**
   * **Server Type:** `Icecast 2`
   * **Host / Server:** IP atau domain VPS Anda (contoh: `radio.domainanda.com`)
   * **Port:** `8005` *(Port Harbor Liquidsoap)*
   * **Mount:** `/live-dj`
   * **User:** `source`
   * **Password:** *(Sesuai yang diatur pada `.env` atau menu DJ Accounts di panel)*
   * **Format / Bitrate:** MP3 128 kbps stereo, 44.1 kHz
3. **Mulai Siaran (Connect):**
   * Begitu tersambung, Liquidsoap secara otomatis melakukan **smart crossfade** dari Auto DJ ke siaran DJ Anda.
   * Saat DJ memutuskan koneksi (Disconnect), Auto DJ akan kembali memutar musik secara otomatis tanpa jeda keheningan (silence).

---

## 10. REST API Endpoints

| Endpoint | Method | Akses | Keterangan |
|---|---|---|---|
| `/api/radio/status` | GET | Publik | Status streaming, listener count, bitrate, current track |
| `/api/radio/now-playing` | GET | Publik | Standar payload now playing untuk website & aplikasi mobile |
| `/api/radio/listeners` | GET | Publik | Jumlah pendengar aktif dan peak saat ini |
| `/api/radio/next-song` | GET | Publik | Lagu berikutnya yang akan diputar oleh Auto DJ |
| `/api/songs?q=keyword` | GET | Publik | Pencarian katalog lagu untuk modal request lagu |
| `/api/song-request` | POST | Publik | Kirim atensi dan request lagu oleh pendengar |
| `/api/schedule` | GET | Publik | Jadwal siaran mingguan 7 hari |
| `/api/admin/radio/skip` | POST | Admin | Melewati (skip) lagu yang sedang diputar di Auto DJ |
| `/api/admin/radio/auto-dj` | POST | Admin | Mengontrol daemon Auto DJ (start/stop/reload) |
| `/api/admin/radio/live` | POST | Admin | Memeriksa dan mengontrol live harbor source |

---

## 11. Pemeliharaan, Backup & Troubleshooting

### Restart Seluruh Layanan Radio
```bash
sudo systemctl restart mongod icecast2 radio-liquidsoap radio-scheduler radio-sync nginx
```

### Memeriksa Log Real-Time
```bash
# Log Liquidsoap Engine
tail -f /var/log/radio/liquidsoap.log

# Log Sinkronisasi Icecast & Pendengar
tail -f /var/www/radio-platform/storage/logs/stream.log

# Log Scheduler Siaran
tail -f /var/www/radio-platform/storage/logs/scheduler.log

# Log Aplikasi Web
tail -f /var/www/radio-platform/storage/logs/app.log
```

### Membuat Backup Database MongoDB Manual
```bash
# Backup langsung via mongodump:
mongodump --uri="mongodb://127.0.0.1:27017" --db=radio_platform --out=/var/www/radio-platform/storage/backups/dump_$(date +%Y%m%d)

# Atau cukup gunakan menu "MongoDB Backup" di panel admin web untuk membuat dan mengunduh arsip .tar.gz secara instan.
```

### Merestore Database MongoDB dari Backup
```bash
mongorestore --uri="mongodb://127.0.0.1:27017" --db=radio_platform --drop /var/www/radio-platform/storage/backups/dump_FOLDER/radio_platform
```
