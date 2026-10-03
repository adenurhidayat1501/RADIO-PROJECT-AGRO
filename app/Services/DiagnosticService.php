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
            // Ping command to verify alive (read cursor via foreach to avoid rewind exception)
            $cursor = $db->command(['ping' => 1]);
            $pingDoc = null;
            foreach ($cursor as $doc) {
                $pingDoc = (array) $doc;
                break;
            }
            $isPingOk = !empty($pingDoc['ok']);

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
        $rawMount = config('radio.icecast.mountpoint', '/live');
        $mount = '/' . ltrim(trim((string) $rawMount), '/');
        if ($mount === '/' || $mount === '/letsgo') {
            $mount = '/live';
        }

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

        // Verify Icecast source password synchronization
        $iceXmlPaths = ['/etc/icecast2/icecast.xml', '/etc/icecast/icecast.xml'];
        $xmlSourcePass = null;
        $xmlMount = null;
        foreach ($iceXmlPaths as $xp) {
            if (file_exists($xp) && is_readable($xp)) {
                $xml = (string) @file_get_contents($xp);
                if (preg_match('/<source-password>(.*?)<\/source-password>/s', $xml, $m)) {
                    $xmlSourcePass = trim($m[1]);
                }
                if (preg_match('/<mount-name>(.*?)<\/mount-name>/s', $xml, $m)) {
                    $xmlMount = trim($m[1]);
                }
                break;
            }
        }

        $envSourcePass = config('radio.icecast.source_password', 'hackme_source');
        if ($xmlSourcePass !== null && $xmlSourcePass !== $envSourcePass) {
            $checks[] = [
                'key' => 'icecast_auth_mismatch',
                'category' => 'Broadcast Engine',
                'name' => 'Sinkronisasi Password Icecast Source',
                'status' => 'error',
                'message' => "Password sumber di .env TIDAK COCOK dengan <source-password> di /etc/icecast2/icecast.xml. Liquidsoap ditolak Icecast (HTTP 401 Unauthorized).",
                'remedy' => 'Jalankan: php scripts/diagnose.php --repair untuk sinkronisasi otomatis.',
            ];
        }

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
                $liqService = new LiquidsoapService();
                $outStatus = trim($liqService->sendTelnet('output_icecast.status'));
                $logHint = '';
                if (file_exists('/var/log/radio/liquidsoap.log')) {
                    $lastLines = @file('/var/log/radio/liquidsoap.log') ?: [];
                    foreach (array_reverse(array_slice($lastLines, -50)) as $ll) {
                        if (stripos($ll, 'output_icecast') !== false || stripos($ll, 'failed') !== false || stripos($ll, '401') !== false || stripos($ll, 'refused') !== false) {
                            $logHint = ' Log: ' . trim($ll);
                            break;
                        }
                    }
                }
                $extra = ($outStatus && !str_contains($outStatus, 'ERROR')) ? " [Telnet Output: {$outStatus}.{$logHint}]" : ($logHint ? " [{$logHint}]" : '');

                $checks[] = [
                    'key' => 'icecast_mountpoint',
                    'category' => 'Broadcast Engine',
                    'name' => "Mountpoint Siaran ({$mount})",
                    'status' => 'error',
                    'message' => "Icecast berjalan tetapi mountpoint '{$mount}' belum menerima input stream audio dari Liquidsoap.{$extra}",
                    'remedy' => 'Jalankan auto-repair: php scripts/diagnose.php --repair',
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
        $liqService = new LiquidsoapService();

        // Check if config file exists and validate syntax
        if (file_exists($configPath)) {
            $liqContent = (string) @file_get_contents($configPath);
            // Proactively auto-heal old on_metadata syntax
            if (str_contains($liqContent, 'on_metadata(notify_metadata,')) {
                $liqService->generateConfig();
                $configPath = config('radio.paths.generated_config', '/etc/radio/radio.liq');
            }

            // Syntax validation check
            $syntaxOutput = @shell_exec("liquidsoap --check " . escapeshellarg($configPath) . " 2>&1");
            $hasSyntaxError = $syntaxOutput && (str_contains($syntaxOutput, 'Error') || str_contains($syntaxOutput, 'exception'));

            $checks[] = [
                'key' => 'liquidsoap_config',
                'category' => 'Auto DJ',
                'name' => 'Konfigurasi Script Liquidsoap (/etc/radio/radio.liq)',
                'status' => $hasSyntaxError ? 'error' : 'ok',
                'message' => $hasSyntaxError 
                    ? "Kesalahan sintaks Liquidsoap: " . trim(explode("\n", trim($syntaxOutput))[0] ?? 'Syntax error') 
                    : "File konfigurasi valid & kompatibel dengan Liquidsoap 2.2 (" . round(filesize($configPath) / 1024, 1) . " KB).",
                'remedy' => $hasSyntaxError ? 'php scripts/generate-liquidsoap.php' : null,
            ];
        } else {
            $liqService->generateConfig();
            $checks[] = [
                'key' => 'liquidsoap_config',
                'category' => 'Auto DJ',
                'name' => 'Konfigurasi Script Liquidsoap (/etc/radio/radio.liq)',
                'status' => 'ok',
                'message' => "File konfigurasi baru saja dibuat otomatis.",
                'remedy' => null,
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
            $autodjRes = $liqService->sendTelnet('output_icecast.status');
            $tracksRemaining = $liqService->sendTelnet('autodj.remaining');
            $statusDesc = (trim($autodjRes) === 'on') ? 'ON (Mengudara)' : (trim($autodjRes) ?: 'siap');
            $remCount = is_numeric(trim($tracksRemaining)) ? trim($tracksRemaining) . ' lagu' : 'tersedia';

            $checks[] = [
                'key' => 'liquidsoap_autodj_status',
                'category' => 'Auto DJ',
                'name' => 'Status Transmisi & AutoDJ Telnet',
                'status' => 'ok',
                'message' => "Output Icecast: {$statusDesc} | Antrean AutoDJ: {$remCount}.",
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
            $scannedFiles = [];
            $entries = @scandir($musicDir) ?: [];
            foreach ($entries as $e) {
                if ($e === '.' || $e === '..') continue;
                $ext = strtolower(pathinfo($e, PATHINFO_EXTENSION));
                if (in_array($ext, ['mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac'], true)) {
                    $scannedFiles[] = $e;
                }
            }
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

        // 0. Synchronize Icecast XML credentials & .env
        $envPath = __DIR__ . '/../../.env';
        $iceXmlPath = file_exists('/etc/icecast2/icecast.xml') ? '/etc/icecast2/icecast.xml' : '/etc/icecast/icecast.xml';

        $xmlSourcePass = null;
        $xmlAdminPass = null;
        if (file_exists($iceXmlPath) && is_readable($iceXmlPath)) {
            $xmlContent = (string) file_get_contents($iceXmlPath);
            if (preg_match('/<source-password>(.*?)<\/source-password>/s', $xmlContent, $m)) {
                $xmlSourcePass = trim($m[1]);
            }
            if (preg_match('/<admin-password>(.*?)<\/admin-password>/s', $xmlContent, $m)) {
                $xmlAdminPass = trim($m[1]);
            }

            // Also check if icecast.xml has /letsgo and fix it to /live
            if (str_contains($xmlContent, '<mount-name>/letsgo</mount-name>')) {
                $fixedXml = str_replace('<mount-name>/letsgo</mount-name>', '<mount-name>/live</mount-name>', $xmlContent);
                @file_put_contents($iceXmlPath, $fixedXml);
                @shell_exec('systemctl restart icecast2 2>&1');
                $results[] = 'Mountpoint di /etc/icecast2/icecast.xml diperbaiki menjadi /live dan Icecast2 direstart.';
            }
        }

        if (file_exists($envPath) && is_writable($envPath)) {
            $envContent = (string) file_get_contents($envPath);
            $cleanEnv = preg_replace('/ICECAST_PUBLIC_URL=[^\r\n]*/', 'ICECAST_PUBLIC_URL=https://radio.dadofy.xyz/live', $envContent);
            $cleanEnv = preg_replace('/ICECAST_MOUNTPATH=[^\r\n]*/', 'ICECAST_MOUNTPATH=/live', $cleanEnv);
            $cleanEnv = preg_replace('/APP_URL=[^\r\n]*/', 'APP_URL=https://radio.dadofy.xyz', $cleanEnv);

            if (!empty($xmlSourcePass)) {
                $cleanEnv = preg_replace('/ICECAST_SOURCE_PASSWORD=[^\r\n]*/', 'ICECAST_SOURCE_PASSWORD=' . $xmlSourcePass, $cleanEnv);
                putenv('ICECAST_SOURCE_PASSWORD=' . $xmlSourcePass);
                $_ENV['ICECAST_SOURCE_PASSWORD'] = $xmlSourcePass;
            }
            if (!empty($xmlAdminPass)) {
                $cleanEnv = preg_replace('/ICECAST_ADMIN_PASSWORD=[^\r\n]*/', 'ICECAST_ADMIN_PASSWORD=' . $xmlAdminPass, $cleanEnv);
                putenv('ICECAST_ADMIN_PASSWORD=' . $xmlAdminPass);
                $_ENV['ICECAST_ADMIN_PASSWORD'] = $xmlAdminPass;
            }

            if ($cleanEnv !== $envContent) {
                @file_put_contents($envPath, $cleanEnv);
                $results[] = 'Konfigurasi .env (kredensial Icecast, URL siaran, & mountpoint) disinkronkan.';
            }
        }

        // 0b. Sanitize Station mountpoint in MongoDB
        try {
            $station = Station::getPrimary();
            if (!empty($station['_id'])) {
                $stMount = $station['stream']['mountpoint'] ?? '';
                if ($stMount === 'letsgo' || $stMount === '/letsgo' || empty($stMount)) {
                    Station::getCollection()->updateOne(
                        ['_id' => $station['_id']],
                        ['$set' => ['stream.mountpoint' => '/live']]
                    );
                    $results[] = 'Mountpoint station di database dinormalisasi ke /live.';
                }
            }
        } catch (\Throwable $e) {}

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

        // Ensure file permissions for daemon user radio
        @shell_exec('chown -R radio:radio /etc/radio 2>/dev/null; chmod -R 755 /etc/radio 2>/dev/null');
        @shell_exec('chown -R www-data:radio /var/lib/radio /var/log/radio 2>/dev/null; chmod -R 2775 /var/lib/radio /var/log/radio 2>/dev/null');

        // 5. Restart daemon directly (as root or with sudo)
        @shell_exec('systemctl restart radio-liquidsoap 2>&1 || sudo systemctl restart radio-liquidsoap 2>&1');
        sleep(2);

        // 6. Send instant Telnet start & reload
        $liqService->sendTelnet('output_icecast.start');
        $reloadRes = $liqService->sendTelnet('autodj.reload');
        $outStatus = trim($liqService->sendTelnet('output_icecast.status'));
        $results[] = "Layanan radio-liquidsoap direstart (Status output stream Icecast: {$outStatus}).";

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
