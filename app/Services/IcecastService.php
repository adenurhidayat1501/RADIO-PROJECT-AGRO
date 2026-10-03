<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\StreamStat;
use App\Models\NowPlaying;
use App\Helpers\Logger;

class IcecastService
{
    private string $host;
    private int $port;
    private string $mountpoint;
    private string $adminUser;
    private string $adminPassword;

    public function __construct()
    {
        $this->host = config('radio.icecast.host', '127.0.0.1');
        $this->port = (int) config('radio.icecast.port', 8000);
        $rawMount = config('radio.icecast.mountpoint', '/live');
        $this->mountpoint = '/' . ltrim(trim((string) $rawMount), '/');
        if ($this->mountpoint === '/' || $this->mountpoint === '/letsgo') {
            $this->mountpoint = '/live';
        }
        $this->adminUser = config('radio.icecast.admin_user', 'admin');
        $this->adminPassword = config('radio.icecast.admin_password', 'hackme_admin');
    }

    /**
     * Get live status from Icecast /status-json.xsl
     */
    public function getStatus(): array
    {
        $url = "http://{$this->host}:{$this->port}/status-json.xsl";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 2,
            CURLOPT_CONNECTTIMEOUT => 1,
            CURLOPT_USERAGENT => 'RadioPlatform-Monitor/1.0',
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $defaultState = [
            'online' => false,
            'server_online' => false,
            'listeners' => 0,
            'peak' => 0,
            'bitrate' => config('radio.stream.bitrate', 128),
            'title' => 'Broadcast Offline',
            'artist' => '',
            'genre' => config('radio.station.genre', 'Various'),
            'mount' => $this->mountpoint,
        ];

        if ($httpCode !== 200 || !$response) {
            return $defaultState;
        }

        $data = json_decode($response, true);
        if (!$data || !isset($data['icestats'])) {
            return $defaultState;
        }

        $defaultState['server_online'] = true;
        $sources = $data['icestats']['source'] ?? [];

        // If source is a single associative array, wrap in list
        if (isset($sources['listenurl'])) {
            $sources = [$sources];
        }

        // Find our mountpoint
        foreach ($sources as $src) {
            $listenUrl = $src['listenurl'] ?? '';
            if (str_ends_with($listenUrl, $this->mountpoint) || str_ends_with($listenUrl, '/live')) {
                $rawTitle = $src['title'] ?? ($src['yp_currently_playing'] ?? 'Radio Agro Live');
                $parts = explode(' - ', $rawTitle, 2);

                return [
                    'online' => true,
                    'server_online' => true,
                    'listeners' => (int) ($src['listeners'] ?? 0),
                    'peak' => (int) ($src['listener_peak'] ?? 0),
                    'bitrate' => (int) ($src['bitrate'] ?? config('radio.stream.bitrate', 128)),
                    'title' => count($parts) === 2 ? trim($parts[1]) : trim($rawTitle),
                    'artist' => count($parts) === 2 ? trim($parts[0]) : 'Radio Agro',
                    'genre' => $src['genre'] ?? config('radio.station.genre', 'Various'),
                    'mount' => $this->mountpoint,
                    'server_type' => $src['server_type'] ?? 'audio/mpeg',
                ];
            }
        }

        // If exact mount not matched but Icecast has an active source, adopt the active source
        if (!empty($sources) && isset($sources[0]['listenurl'])) {
            $src = $sources[0];
            $rawTitle = $src['title'] ?? ($src['yp_currently_playing'] ?? 'Radio Agro Live');
            $parts = explode(' - ', $rawTitle, 2);
            $parsedPath = parse_url($src['listenurl'], PHP_URL_PATH) ?: $this->mountpoint;

            return [
                'online' => true,
                'server_online' => true,
                'listeners' => (int) ($src['listeners'] ?? 0),
                'peak' => (int) ($src['listener_peak'] ?? 0),
                'bitrate' => (int) ($src['bitrate'] ?? config('radio.stream.bitrate', 128)),
                'title' => count($parts) === 2 ? trim($parts[1]) : trim($rawTitle),
                'artist' => count($parts) === 2 ? trim($parts[0]) : 'Radio Agro',
                'genre' => $src['genre'] ?? config('radio.station.genre', 'Various'),
                'mount' => $parsedPath,
                'server_type' => $src['server_type'] ?? 'audio/mpeg',
            ];
        }

        return $defaultState;
    }

    /**
     * Poll Icecast and record stream statistics in MongoDB
     */
    public function syncStats(): array
    {
        $status = $this->getStatus();

        if ($status['online']) {
            StreamStat::record(
                $status['listeners'],
                $status['listeners'],
                $status['peak']
            );

            // Update NowPlaying if title changed and not empty
            if (!empty($status['title']) && $status['title'] !== 'Broadcast Offline') {
                $current = NowPlaying::getCurrent();
                if (($current['title'] ?? '') !== $status['title']) {
                    NowPlaying::setCurrent([
                        'title' => $status['title'],
                        'artist' => $status['artist'],
                        'source' => 'stream',
                    ]);
                }
            }
        }

        return $status;
    }
}
