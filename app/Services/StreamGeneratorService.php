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

        $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            || (isset($_SERVER['HTTP_CF_VISITOR']) && str_contains($_SERVER['HTTP_CF_VISITOR'], 'https'))
            || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

        $publicUrl = config('radio.icecast.public_url');
        // If an explicit external public URL is configured (not 127.0.0.1 or localhost):
        if (!empty($publicUrl)) {
            $parsed = parse_url($publicUrl);
            $host = $parsed['host'] ?? '';
            if (!empty($host) && $host !== '127.0.0.1' && $host !== 'localhost') {
                $scheme = $parsed['scheme'] ?? ($isHttps ? 'https' : 'http');
                $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
                return "{$scheme}://{$host}{$port}{$mount}";
            }
        }

        // If accessed through web server (e.g. https://radio.dadofy.xyz), use current host + proxy mount
        if (!empty($_SERVER['HTTP_HOST'])) {
            $scheme = $isHttps ? 'https' : 'http';
            return "{$scheme}://{$_SERVER['HTTP_HOST']}{$mount}";
        }

        return base_url(ltrim($mount, '/'));
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
