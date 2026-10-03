<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Song;
use App\Models\Station;
use App\Models\Playlist;
use App\Helpers\Logger;

class DiagnosticService
{
    /**
     * Run all system diagnostic tests and return categorized results
     */
    public function runDiagnostics(): array
    {
        $checks = [];

        // 1. PHP Runtime & Extensions
        $checks = array_merge($checks, $this->checkPhpEnvironment());

        // 2. MongoDB Database
        $checks = array_merge($checks, $this->checkDatabase());

        // 3. Icecast2 Streaming Server
        $checks = array_merge($checks, $this->checkIcecast());

        // 4. Liquidsoap Broadcast & AutoDJ Engine
        $checks = array_merge($checks, $this->checkLiquidsoap());

        // 5. Audio Storage & Playlists
        $checks = array_merge($checks, $this->checkStorageAndAudio());

        // 6. Web Server & Reverse Proxy
        $checks = array_merge($checks, $this->checkWebAndProxy());

        // Calculate health score & metrics
        $total = count($checks);
        $passed = count(array_filter($checks, fn($c) => $c['status'] === 'ok'));
        $warnings = count(array_filter($checks, fn($c) => $c['status'] === 'warning'));
        $errors = count(array_filter($checks, fn($c) => $c['status'] === 'error'));

        $healthScore = $total > 0 ? (int) round(($passed / $total) * 100) : 0;

        return [
            'summary' => [
                'total' => $total,
                'passed' => $passed,
                'warnings' => $warnings,
                'errors' => $errors,
                'health_score' => $healthScore,
                'status' => $errors > 0 ? 'error' : ($warnings > 0 ? 'warning' : 'healthy'),
                'timestamp' => date('Y-m-d H:i:s'),
            ],
            'checks' => $checks,
            'logs' => $this->getRecentLogs(),
        ];
    }

    /**
     * 1. Check PHP Version and Extensions
     */
    private function checkPhpEnvironment(): array
    {
        $checks = [];

        // PHP Version
        $phpVer = PHP_VERSION;
        $isVerOk = version_compare($phpVer, '8.2.0', '>=');
        $checks[] = [
            'key' => 'php_version',
            'category' => 'PHP Runtime',
            'name' => 'PHP Version',
            'status' => $isVerOk ? 'ok' : 'error',
            'message' => "PHP {$phpVer} terpasang." . ($isVerOk ? ' (Memenuhi syarat PHP 8.2+)' : ' (Wajib PHP 8.2 ke atas)'),
            'remedy' => $isVerOk ? null : 'sudo apt install php8.3 php8.3-fpm php8.3-cli',
        ];

        // Required Extensions
        $requiredExts = [
            'mongodb' => 'Koneksi database NoSQL MongoDB',
            'curl' => 'Komunikasi HTTP Icecast & Webhook API',
            'fileinfo' => 'Validasi MIME type audio saat upload',
            'mbstring' => 'Pemrosesan string UTF-8 ID3 tags',
            'sockets' => 'Komunikasi socket Telnet Liquidsoap',
            'json' => 'Parsing API & Telemetri Icecast',
        ];

        foreach ($requiredExts as $ext => $desc) {
            $loaded = extension_loaded($ext);
            $checks[] = [
                'key' => "ext_{$ext}",
                'category' => 'PHP Runtime',
                'name' => "Ekstensi PHP: {$ext}",
                'status' => $loaded ? 'ok' : 'error',
                'message' => $loaded ? "Ekstensi '{$ext}' aktif ({$desc})." : "Ekstensi '{$ext}' TIDAK DITEMUKAN. Wajib untuk {$desc}.",
                'remedy' => $loaded ? null : "sudo apt install php8.3-{$ext} || sudo pecl install {$ext}",
            ];
        }

        // Memory Limit & Upload Max Size
        $memLimit = ini_get('memory_limit');
        $uploadMax = ini_get('upload_max_filesize');
        $postMax = ini_get('post_max_size');

        $checks[] = [
            'key' => 'php_upload_limits',
            'category' => 'PHP Runtime',
            'name' => 'Kapasitas Upload Audio (upload_max_filesize)',
            'status' => 'ok',
            'message' => "Memory Limit: {$memLimit}, Upload Max: {$uploadMax}, Post Max: {$postMax}",
            'remedy' => null,
        ];

        return $checks;
    }

    /**
     * 2. Check MongoDB Connectivity & Data Counts
     */
    private function checkDatabase(): array
    {
        $checks = [];

        try {
            $db = Database::getDatabase();
            // Ping command to verify alive
            $ping = $db->command(['ping' => 1]);
            $isPingOk = isset($ping->toArray()[0]['ok']) && (float) $ping->toArray()[0]['ok'] === 1.0;

            $checks[] = [
                'key' => 'mongodb_connection',
                'category' => 'Database',
                'name' => 'Koneksi Server MongoDB',
                'status' => $isPingOk ? 'ok' : 'error',
                'message' => $isPingOk ? 'MongoDB 127.0.0.1:27017 terhubung dan merespons PING.' : 'Gagal PING database MongoDB.',
                'remedy' => $isPingOk ? null : 'sudo systemctl restart mongod',
            ];

            // Songs count
            $songCount = Song::getCollection()->countDocuments();
            $enabledCount = Song::getCollection()->countDocuments([
                '$or' => [
                    ['enabled' => true],
                    ['enabled' => 1],
                    ['enabled' => '1'],
                    ['enabled' => ['$exists' => false]],
                ]
            ]);

            if ($songCount === 0) {
                $status = 'warning';
                $msg = 'Belum ada lagu yang terdaftar di database MongoDB.';
                $remedy = 'Upload lagu MP3 melalui menu Music Library di Admin Panel.';
            } else {
                $status = 'ok';
                $msg = "Terdaftar {$songCount} total lagu ({$enabledCount} lagu aktif siap siar).";
                $remedy = null;
            }

            $checks[] = [
                'key' => 'mongodb_songs',
                'category' => 'Database',
                'name' => 'Koleksi Lagu (Songs Collection)',
                'status' => $status,
                'message' => $msg,
                'remedy' => $remedy,
            ];

            // Station Profile
            $station = Station::getPrimary();
            $hasStation = !empty($station['name']);
            $checks[] = [
                'key' => 'mongodb_station',
                'category' => 'Database',
                'name' => 'Profil Stasiun Radio',
                'status' => $hasStation ? 'ok' : 'warning',
                'message' => $hasStation ? "Stasiun: '{$station['name']}' ({$station['genre']})" : 'Profil stasiun belum dikonfigurasi.',
                'remedy' => $hasStation ? null : 'Atur profil stasiun di menu Station Profile.',
            ];

        } catch (\Throwable $e) {
            $checks[] = [
                'key' => 'mongodb_connection',
                'category' => 'Database',
                'name' => 'Koneksi Server MongoDB',
                'status' => 'error',
                'message' => 'Gagal menghubungkan ke MongoDB: ' . $e->getMessage(),
                'remedy' => 'Jalankan: sudo systemctl start mongod && sudo systemctl status mongod',
            ];
        }

        return $checks;
    }

    /**
     * 3. Check Icecast2 Streaming Server
     */
    private function checkIcecast(): array
    {
        $checks = [];

        $host = config('radio.icecast.host', '127.0.0.1');
        $port = (int) config('radio.icecast.port', 8000);
        $mount = config('radio.icecast.mountpoint', '/live');

        // Check if port is open
        $fp = @fsockopen($host, $port, $errno, $errstr, 2);
        if (!$fp) {
            $checks[] = [
                'key' => 'icecast_daemon',
                'category' => 'Broadcast Engine',
                'name' => 'Layanan Icecast2 (Port 8000)',
                'status' => 'error',
                'message' => "Icecast2 TIDAK AKTIF di {$host}:{$port} ({$errstr})",
                'remedy' => 'sudo systemctl restart icecast2 && sudo systemctl status icecast2',
            ];
            return $checks;
        }
        fclose($fp);

        $checks[] = [
            'key' => 'icecast_daemon',
            'category' => 'Broadcast Engine',
            'name' => 'Layanan Icecast2 (Port 8000)',
            'status' => 'ok',
            'message' => "Port {$port} Icecast2 terbuka dan merespons koneksi.",
            'remedy' => null,
        ];

        // Check status-json.xsl
        $icecastService = new IcecastService();
        $status = $icecastService->getStatus();

        if ($status['server_online']) {
            $checks[] = [
                'key' => 'icecast_status_json',
                'category' => 'Broadcast Engine',
                'name' => 'API Telemetri Icecast (/status-json.xsl)',
                'status' => 'ok',
                'message' => 'Endpoint status-json.xsl merespons valid.',
                'remedy' => null,
            ];

            // Check Mountpoint
            if ($status['online']) {
                $checks[] = [
                    'key' => 'icecast_mountpoint',
                    'category' => 'Broadcast Engine',
                    'name' => "Mountpoint Siaran ({$mount})",
                    'status' => 'ok',
                    'message' => "🔴 Siaran ON AIR! Sedang mengudara: '{$status['artist']} - {$status['title']}' ({$status['listeners']} pendengar, {$status['bitrate']} kbps).",
                    'remedy' => null,
                ];
            } else {
                $checks[] = [
                    'key' => 'icecast_mountpoint',
                    'category' => 'Broadcast Engine',
                    'name' => "Mountpoint Siaran ({$mount})",
                    'status' => 'error',
                    'message' => "Icecast berjalan tetapi mountpoint '{$mount}' belum menerima input stream audio dari Liquidsoap.",
                    'remedy' => 'Restart Liquidsoap: sudo systemctl restart radio-liquidsoap',
                ];
            }
        } else {
            $checks[] = [
                'key' => 'icecast_status_json',
                'category' => 'Broadcast Engine',
                'name' => 'API Telemetri Icecast (/status-json.xsl)',
                'status' => 'warning',
                'message' => 'Icecast aktif namun tidak memberikan data status-json.xsl.',
                'remedy' => 'Periksa file konfigurasi /etc/icecast2/icecast.xml',
            ];
        }

        return $checks;
    }

    /**
     * 4. Check Liquidsoap Daemon & Telnet & Harbor
     */
    private function checkLiquidsoap(): array
    {
        $checks = [];

        $host = config('radio.liquidsoap.host', '127.0.0.1');
        $telnetPort = (int) config('radio.liquidsoap.telnet_port', 1234);
        $harborPort = (int) config('radio.liquidsoap.harbor_port', 8005);
        $configPath = config('radio.paths.generated_config', '/etc/radio/radio.liq');

        // Check if config file exists
        if (file_exists($configPath)) {
            $checks[] = [
                'key' => 'liquidsoap_config',
                'category' => 'Auto DJ',
                'name' => 'Konfigurasi Script Liquidsoap (/etc/radio/radio.liq)',
                'status' => 'ok',
                'message' => "File konfigurasi ditemukan (" . round(filesize($configPath) / 1024, 1) . " KB).",
                'remedy' => null,
            ];
        } else {
            $checks[] = [
                'key' => 'liquidsoap_config',
                'category' => 'Auto DJ',
                'name' => 'Konfigurasi Script Liquidsoap (/etc/radio/radio.liq)',
                'status' => 'warning',
                'message' => "File {$configPath} belum dibuat.",
                'remedy' => 'Klik tombol Reload AutoDJ di Admin Dashboard atau jalankan php scripts/generate-liquidsoap.php',
            ];
        }

        // Check Telnet Socket
        $liqService = new LiquidsoapService();
        $telnetRes = $liqService->sendTelnet('version');

        if (str_contains($telnetRes, 'ERROR')) {
            $checks[] = [
                'key' => 'liquidsoap_telnet',
                'category' => 'Auto DJ',
                'name' => 'Liquidsoap Service & Telnet (Port 1234)',
                'status' => 'error',
                'message' => "Liquidsoap TIDAK MERESPONS pada port {$telnetPort}. AutoDJ dan transmisi live terhenti.",
                'remedy' => 'sudo systemctl restart radio-liquidsoap && sudo journalctl -u radio-liquidsoap -n 20',
            ];
        } else {
            $checks[] = [
                'key' => 'liquidsoap_telnet',
                'category' => 'Auto DJ',
                'name' => 'Liquidsoap Service & Telnet (Port 1234)',
                'status' => 'ok',
                'message' => "Liquidsoap aktif dan merespons perintah Telnet. (Respon: " . substr(trim($telnetRes), 0, 50) . ")",
                'remedy' => null,
            ];

            // Test AutoDJ status via Telnet
            $autodjRes = $liqService->sendTelnet('autodj.status');
            $checks[] = [
                'key' => 'liquidsoap_autodj_status',
                'category' => 'Auto DJ',
                'name' => 'Status Antrean AutoDJ',
                'status' => 'ok',
                'message' => "Status AutoDJ: " . (trim($autodjRes) ?: 'ready/playing'),
                'remedy' => null,
            ];
        }

        // Check Harbor Port (Live DJ input)
        $hFp = @fsockopen($host, $harborPort, $errno, $errstr, 1);
        if ($hFp) {
            fclose($hFp);
            $checks[] = [
                'key' => 'liquidsoap_harbor',
                'category' => 'Auto DJ',
                'name' => 'Port Harbor Live DJ (Port 8005)',
                'status' => 'ok',
                'message' => "Port {$harborPort} siap menerima koneksi siaran langsung DJ (Mixxx / BUTT / OBS).",
                'remedy' => null,
            ];
        } else {
            $checks[] = [
                'key' => 'liquidsoap_harbor',
                'category' => 'Auto DJ',
                'name' => 'Port Harbor Live DJ (Port 8005)',
                'status' => 'warning',
                'message' => "Port {$harborPort} belum terbuka. Siaran live eksternal belum bisa tersambung.",
                'remedy' => 'Pastikan service radio-liquidsoap berjalan normal.',
            ];
        }

        return $checks;
    }

    /**
     * 5. Check Audio Storage, Files, and Playlists
     */
    private function checkStorageAndAudio(): array
    {
        $checks = [];

        $musicDir = config('radio.paths.music', '/var/lib/radio/music');
        $playlistsDir = config('radio.paths.playlists_dir', '/var/lib/radio/playlists');
        $fallbackPath = config('radio.paths.fallback', '/var/lib/radio/fallback/default.mp3');

        // Check Music Directory
        if (is_dir($musicDir)) {
            $isWritable = is_writable($musicDir);
            $scannedFiles = glob($musicDir . '/*.{mp3,wav,ogg,flac,m4a,aac}', GLOB_BRACE) ?: [];
            $totalAudio = count($scannedFiles);

            $status = $totalAudio > 0 ? 'ok' : 'warning';
            $msg = "Folder music ada ({$totalAudio} file audio ditemukan). " . ($isWritable ? 'Writable.' : 'PERINGATAN: Tidak ada izin tulis (read-only)!');

            $checks[] = [
                'key' => 'storage_music_dir',
                'category' => 'Storage & Music',
                'name' => "Direktori Musik ({$musicDir})",
                'status' => $status,
                'message' => $msg,
                'remedy' => $isWritable ? null : "sudo chown -R www-data:radio {$musicDir} && sudo chmod -R 775 {$musicDir}",
            ];
        } else {
            $checks[] = [
                'key' => 'storage_music_dir',
                'category' => 'Storage & Music',
                'name' => "Direktori Musik ({$musicDir})",
                'status' => 'error',
                'message' => "Folder {$musicDir} TIDAK DITEMUKAN di server VPS!",
                'remedy' => "sudo mkdir -p {$musicDir} && sudo chown -R www-data:radio {$musicDir} && sudo chmod -R 775 {$musicDir}",
            ];
        }

        // Check Default Playlist default.m3u
        $defaultM3u = $playlistsDir . '/default.m3u';
        if (file_exists($defaultM3u)) {
            $content = trim((string) file_get_contents($defaultM3u));
            $lines = array_filter(array_map('trim', explode("\n", $content)));
            $trackCount = count($lines);

            $checks[] = [
                'key' => 'playlist_default_m3u',
                'category' => 'Storage & Music',
                'name' => 'File Playlist AutoDJ (default.m3u)',
                'status' => $trackCount > 0 ? 'ok' : 'warning',
                'message' => "File default.m3u ditemukan dengan {$trackCount} track terdaftar untuk rotasi siaran.",
                'remedy' => $trackCount > 0 ? null : 'Sinkronkan playlist di Admin Dashboard atau upload lagu baru.',
            ];
        } else {
            $checks[] = [
                'key' => 'playlist_default_m3u',
                'category' => 'Storage & Music',
                'name' => 'File Playlist AutoDJ (default.m3u)',
                'status' => 'warning',
                'message' => "File playlist default.m3u belum dibuat di {$playlistsDir}.",
                'remedy' => 'Jalankan Auto-Repair atau upload lagu agar playlist dibuat otomatis.',
            ];
        }

        // Check Fallback Emergency Audio Track
        if (file_exists($fallbackPath) && filesize($fallbackPath) > 512) {
            $sizeKb = round(filesize($fallbackPath) / 1024, 1);
            $checks[] = [
                'key' => 'audio_fallback',
                'category' => 'Storage & Music',
                'name' => 'Audio Cadangan Darurat (Fallback Track)',
                'status' => 'ok',
                'message' => "Audio cadangan aktif ({$sizeKb} KB). Siaran terlindungi dari dead-air jika playlist habis.",
                'remedy' => null,
            ];
        } else {
            $checks[] = [
                'key' => 'audio_fallback',
                'category' => 'Storage & Music',
                'name' => 'Audio Cadangan Darurat (Fallback Track)',
                'status' => 'warning',
                'message' => "File audio darurat {$fallbackPath} tidak ditemukan atau kosong. Siaran berisiko hening jika playlist terputus.",
                'remedy' => "sudo mkdir -p $(dirname {$fallbackPath}) && ffmpeg -y -f lavfi -i 'sine=frequency=440:duration=10' -c:a libmp3lame -b:a 128k {$fallbackPath}",
            ];
        }

        return $checks;
    }

    /**
     * 6. Check Web Server & Reverse Proxy
     */
    private function checkWebAndProxy(): array
    {
        $checks = [];

        // Check Nginx Proxy for /live
        $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1';
        $streamUrl = StreamGeneratorService::getStreamUrl();

        $checks[] = [
            'key' => 'web_stream_url',
            'category' => 'Web & Proxy',
            'name' => 'URL Stream Publik Player',
            'status' => (!str_contains($streamUrl, '127.0.0.1') && !str_contains($streamUrl, 'localhost')) ? 'ok' : 'warning',
            'message' => "URL Stream yang digunakan player: {$streamUrl}",
            'remedy' => null,
        ];

        // Storage Logs Directory
        $logsDir = __DIR__ . '/../../storage/logs';
        $isLogsWritable = is_dir($logsDir) && is_writable($logsDir);
        $checks[] = [
            'key' => 'storage_logs_dir',
            'category' => 'Web & Proxy',
            'name' => 'Izin Tulis Log Aplikasi (storage/logs)',
            'status' => $isLogsWritable ? 'ok' : 'error',
            'message' => $isLogsWritable ? 'Folder log dapat ditulisi oleh PHP-FPM.' : 'Folder storage/logs tidak writable oleh PHP!',
            'remedy' => $isLogsWritable ? null : "sudo chown -R www-data:www-data {$logsDir} && sudo chmod -R 775 {$logsDir}",
        ];

        return $checks;
    }

    /**
     * 7. Fetch Recent Logs for Debugging
     */
    public function getRecentLogs(): array
    {
        return [
            'liquidsoap' => $this->tailFile('/var/log/radio/liquidsoap.log', 35),
            'icecast' => $this->tailFile('/var/log/icecast2/error.log', 35) ?: $this->tailFile('/var/log/icecast/error.log', 35),
            'nginx_error' => $this->tailFile('/var/log/nginx/radio_error.log', 35) ?: $this->tailFile('/var/log/nginx/error.log', 35),
            'app_log' => $this->tailFile(__DIR__ . '/../../storage/logs/app.log', 35),
        ];
    }

    /**
     * Execute Auto-Repair for Common Disconnects / Playlist Mismatches
     */
    public function runAutoRepair(): array
    {
        $results = [];

        // 1. Ensure storage directories exist
        $dirs = [
            config('radio.paths.music', '/var/lib/radio/music'),
            config('radio.paths.playlists_dir', '/var/lib/radio/playlists'),
            dirname(config('radio.paths.fallback', '/var/lib/radio/fallback/default.mp3')),
            '/etc/radio',
            '/var/log/radio',
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
        }
        $results[] = 'Struktur direktori audio & config diverifikasi.';

        // 2. Ensure fallback audio exists
        $fallback = config('radio.paths.fallback', '/var/lib/radio/fallback/default.mp3');
        if (!file_exists($fallback) || filesize($fallback) < 512) {
            @shell_exec("ffmpeg -y -f lavfi -i 'sine=frequency=440:duration=10' -c:a libmp3lame -b:a 128k " . escapeshellarg($fallback) . " 2>/dev/null");
            $results[] = 'Audio fallback darurat (440Hz safe tone) dibuat.';
        }

        // 3. Sync all playlists from MongoDB + Music directory scan
        $liqService = new LiquidsoapService();
        $written = $liqService->syncPlaylistFiles();
        $results[] = 'Playlist default.m3u dan jingles disinkronkan (' . count($written) . ' file ditulis).';

        // 4. Generate Liquidsoap config
        $liqService->generateConfig();
        $results[] = 'File konfigurasi /etc/radio/radio.liq diperbarui.';

        // 5. Send instant Telnet reload & skip
        $reloadRes = $liqService->sendTelnet('autodj.reload');
        $skipRes = $liqService->sendTelnet('autodj.skip');
        $results[] = "Perintah Telnet dikirim ke Liquidsoap (reload: {$reloadRes}, skip: {$skipRes}).";

        // 6. Restart daemon if possible
        @shell_exec('sudo systemctl restart radio-liquidsoap 2>&1');

        return [
            'success' => true,
            'steps' => $results,
        ];
    }

    /**
     * Helper to read last N lines from a file
     */
    private function tailFile(string $filepath, int $lines = 30): array
    {
        if (!file_exists($filepath) || !is_readable($filepath)) {
            return [];
        }

        $f = @file($filepath);
        if (!$f) {
            return [];
        }

        $slice = array_slice($f, -$lines);
        return array_map('rtrim', $slice);
    }
}
