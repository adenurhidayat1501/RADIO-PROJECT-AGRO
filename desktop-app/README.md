# Radio Agro - Desktop Player & Studio Client (.EXE)

Aplikasi desktop native Windows (**RadioAgroPlayer.exe**) untuk mendengarkan siaran langsung radio, memantau metadata *Now Playing*, mengirim permohonan lagu (*Song Request*), dan membuka *Web Studio / Admin Panel* langsung dari komputer desktop.

---

## 1. Fitur Aplikasi Desktop

* **Live Audio Streaming:** Memutar siaran langsung MP3 berkecepatan tinggi dari server Icecast 2 dengan buffer anti-lag.
* **Now Playing Telemetry:** Otomatis mengambil judul lagu, nama artis, dan jumlah pendengar secara real-time setiap 5 detik melalui REST API.
* **Animated Spectrum Visualizer & Vinyl Disc:** Animasi grafis piringan hitam berputar dan bar visualizer frekuensi saat siaran berlangsung.
* **Interactive Controls:** Tombol Play/Stop, pengatur volume slider (0-100%), dan badge status koneksi (*LIVE ON AIR / CONNECTING / OFFLINE*).
* **Embedded Web Studio Tab:** Browser terintegrasi untuk mengakses Public Radio Website atau Admin Control Studio langsung dari dalam aplikasi tanpa membuka browser terpisah.
* **Direct Song Request Tab:** Form pengajuan atensi dan permohonan lagu langsung ke antrean siaran DJ.
* **System Tray Integration:** Dapat diminimalkan ke System Tray (dekat jam Windows) dengan menu klik kanan:
  * ▶ Play Stream
  * ⏹ Stop Stream
  * Open Radio Window
  * Web Portal
  * Admin Studio
  * Exit
* **Notifikasi Balon Windows:** Menampilkan notifikasi popup Windows otomatis ketika lagu berganti.
* **Kompak & Mandiri:** Ukuran file executable hanya **~28 KB**, tidak memerlukan instalasi browser besar seperti Electron (150MB+), dan langsung berjalan di Windows 10 & Windows 11.

---

## 2. Cara Menjalankan Aplikasi

Cukup **klik ganda (double-click)** pada file:
```text
RadioAgroPlayer.exe
```

Aplikasi akan membaca pengaturan dari file `config.json`.

---

## 3. Konfigurasi (`config.json`)

Anda dapat mengubah URL stream dan server radio melalui menu **Settings** di dalam aplikasi, atau mengedit langsung file `config.json`:

```json
{
  "station_name": "Radio Agro",
  "stream_url": "http://localhost:8000/live",
  "api_url": "http://localhost:8080",
  "auto_play": false,
  "minimize_to_tray": true,
  "volume": 85
}
```

* **station_name:** Nama stasiun radio yang tampil di judul aplikasi.
* **stream_url:** URL stream Icecast (contoh lokal: `http://localhost:8000/live` atau server VPS: `https://radio.domainanda.com/live`).
* **api_url:** URL web portal / REST API stasiun radio (contoh lokal: `http://localhost:8080` atau server VPS: `https://radio.domainanda.com`).
* **auto_play:** `true` jika ingin audio langsung otomatis berputar saat aplikasi dibuka.
* **minimize_to_tray:** `true` jika ingin tombol close (X) meminimalkan aplikasi ke system tray alih-alih menutupnya.
* **volume:** Volume awal (0 - 100).

---

## 4. Cara Melakukan Kompilasi Ulang (Rebuild)

Jika Anda memodifikasi kode sumber di folder `src/Program.cs`:

1. Klik ganda file:
   ```text
   build.bat
   ```
2. Script akan otomatis memanggil compiler C# bawaan Windows (`csc.exe`) dan menghasilkan file executable baru `RadioAgroPlayer.exe`.
