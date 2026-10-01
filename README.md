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

## 3. Instalasi Cepat pada VPS Ubuntu 24.04

### Langkah 1: Clone atau Unggah Source Code ke VPS
```bash
git clone <repository_url> /var/www/radio-platform
cd /var/www/radio-platform
```

### Langkah 2: Jalankan Script Installer Otomatis
```bash
chmod +x install.sh
sudo ./install.sh
```

Installer akan memandu Anda untuk mengisi:
1. **Station Name** (contoh: *Radio Agro*)
2. **Domain** (contoh: *radio.domainanda.com*)
3. **Admin Username & Password**
4. **Mountpoint** (default: `/live`)
5. **Bitrate** (default: `128` kbps)

Installer secara otomatis akan:
* Memasang Nginx, PHP 8.3/8.2 + FPM, Composer, MongoDB 7.0/8.0, Icecast2, Liquidsoap, FFmpeg, dan Certbot.
* Membuat user sistem `radio` dan direktori audio:
  * `/var/lib/radio/music`
  * `/var/lib/radio/jingles`
  * `/var/lib/radio/ads`
  * `/var/lib/radio/fallback`
* Menginisialisasi MongoDB indexes (`database/indexes.php`).
* Mengonfigurasi `/etc/icecast2/icecast.xml` dan `/etc/radio/radio.liq`.
* Memasang dan menyalakan systemd services:
  * `radio-liquidsoap.service`
  * `radio-scheduler.service`
  * `radio-sync.service`
  * `icecast2.service`
  * `mongod.service`
* Mengonfigurasi Nginx dan membuka port firewall UFW (80, 443, 8000, 8005).

---

## 4. Panduan Live DJ Broadcast (Mixxx / BUTT / OBS)

Ketika penyiar atau DJ ingin mengudara secara langsung (Live Studio):

1. **Buka Software Enkoder DJ** (Mixxx / BUTT / RadioBOSS / OBS).
2. **Konfigurasi Koneksi:**
   * **Server Type:** Icecast 2
   * **Host / Server:** IP atau domain VPS Anda
   * **Port:** `8005` *(Port Harbor Liquidsoap)*
   * **Mount:** `/live-dj`
   * **User:** `source`
   * **Password:** *(Sesuai yang diatur pada `.env` atau menu DJ Accounts di panel)*
3. **Mulai Siaran (Connect):**
   * Begitu tersambung, Liquidsoap secara otomatis melakukan **smart crossfade** dari Auto DJ ke siaran DJ Anda.
   * Saat DJ memutuskan koneksi (Disconnect), Auto DJ akan kembali memutar musik secara otomatis tanpa jeda keheningan (silence).

---

## 5. REST API Endpoints

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

## 6. Pemeliharaan & Troubleshooting

### Memeriksa Status Service
```bash
sudo systemctl status radio-liquidsoap
sudo systemctl status radio-scheduler
sudo systemctl status radio-sync
sudo systemctl status icecast2
sudo systemctl status mongod
```

### Memeriksa Log Real-Time
```bash
# Log Liquidsoap
tail -f /var/log/radio/liquidsoap.log

# Log Sinkronisasi Icecast
tail -f /var/www/radio-platform/storage/logs/stream.log

# Log Scheduler
tail -f /var/www/radio-platform/storage/logs/scheduler.log
```

### Mengonfigurasi SSL HTTPS (Let's Encrypt)
```bash
sudo certbot --nginx -d radio.domainanda.com
```
