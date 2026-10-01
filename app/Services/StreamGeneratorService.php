<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Station;

class StreamGeneratorService
{
    public static function getStreamUrl(?string $customMount = null): string
    {
        $station = Station::getPrimary();
        $stream = $station['stream'] ?? [];
        $mount = $customMount ?? ($stream['mountpoint'] ?? config('radio.icecast.mountpoint', '/live'));
        $mount = '/' . ltrim($mount, '/');

        $publicUrl = config('radio.icecast.public_url');
        if (!empty($publicUrl)) {
            $parsed = parse_url($publicUrl);
            $scheme = $parsed['scheme'] ?? 'http';
            $host = $parsed['host'] ?? '127.0.0.1';
            $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
            return "{$scheme}://{$host}{$port}{$mount}";
        }

        $host = config('radio.icecast.host', '127.0.0.1');
        $port = config('radio.icecast.port', 8000);
        return "http://{$host}:{$port}{$mount}";
    }

    public static function getM3uUrl(): string
    {
        return base_url('stream.m3u');
    }

    public static function getPlsUrl(): string
    {
        return base_url('stream.pls');
    }

    public static function generateM3uContent(): string
    {
        $station = Station::getPrimary();
        $streamUrl = self::getStreamUrl();
        $name = $station['name'] ?? 'Radio Agro';

        return "#EXTM3U\n#EXTINF:-1,{$name}\n{$streamUrl}\n";
    }

    public static function generatePlsContent(): string
    {
        $station = Station::getPrimary();
        $streamUrl = self::getStreamUrl();
        $name = $station['name'] ?? 'Radio Agro';

        $pls = "[playlist]\n";
        $pls .= "NumberOfEntries=1\n";
        $pls .= "File1={$streamUrl}\n";
        $pls .= "Title1={$name}\n";
        $pls .= "Length1=-1\n";
        $pls .= "Version=2\n";

        return $pls;
    }
}
