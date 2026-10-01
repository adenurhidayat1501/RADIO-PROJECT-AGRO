<?php

declare(strict_types=1);

namespace App\Helpers;

class Id3TagReader
{
    /**
     * Read audio metadata from file (using ffprobe or pure PHP fallback)
     */
    public static function read(string $filePath): array
    {
        $metadata = [
            'title' => pathinfo($filePath, PATHINFO_FILENAME),
            'artist' => 'Unknown Artist',
            'album' => 'Unknown Album',
            'genre' => 'Various',
            'year' => (int) date('Y'),
            'duration' => 0,
            'bitrate' => 128,
            'filesize' => file_exists($filePath) ? filesize($filePath) : 0,
            'mime_type' => 'audio/mpeg',
        ];

        if (!file_exists($filePath)) {
            return $metadata;
        }

        // Try ffprobe first
        $ffprobeData = self::readWithFfprobe($filePath);
        if ($ffprobeData !== null) {
            return array_merge($metadata, $ffprobeData);
        }

        // Fallback to pure PHP ID3 parsing
        $phpData = self::readWithPhp($filePath);
        return array_merge($metadata, $phpData);
    }

    private static function readWithFfprobe(string $filePath): ?array
    {
        $escaped = escapeshellarg($filePath);
        $cmd = "ffprobe -v quiet -print_format json -show_format -show_streams {$escaped} 2>&1";
        
        $output = @shell_exec($cmd);
        if (!$output) {
            return null;
        }

        $json = json_decode($output, true);
        if (!is_array($json) || empty($json['format'])) {
            return null;
        }

        $fmt = $json['format'];
        $tags = $fmt['tags'] ?? [];

        // Normalize lowercase tag keys
        $cleanTags = [];
        foreach ($tags as $k => $v) {
            $cleanTags[strtolower($k)] = (string) $v;
        }

        $duration = isset($fmt['duration']) ? (int) round((float) $fmt['duration']) : 0;
        $bitrate = isset($fmt['bit_rate']) ? (int) round(((int) $fmt['bit_rate']) / 1000) : 128;

        return [
            'title' => $cleanTags['title'] ?? pathinfo($filePath, PATHINFO_FILENAME),
            'artist' => $cleanTags['artist'] ?? ($cleanTags['album_artist'] ?? 'Unknown Artist'),
            'album' => $cleanTags['album'] ?? 'Unknown Album',
            'genre' => $cleanTags['genre'] ?? 'Various',
            'year' => isset($cleanTags['date']) ? (int) substr($cleanTags['date'], 0, 4) : ((int) date('Y')),
            'duration' => $duration,
            'bitrate' => $bitrate,
            'filesize' => (int) ($fmt['size'] ?? filesize($filePath)),
        ];
    }

    private static function readWithPhp(string $filePath): array
    {
        $data = [];
        $filesize = filesize($filePath);
        $fp = @fopen($filePath, 'rb');
        if (!$fp) {
            return $data;
        }

        // Check for ID3v1 at last 128 bytes
        if ($filesize > 128) {
            fseek($fp, -128, SEEK_END);
            $id3v1 = fread($fp, 128);
            if (substr($id3v1, 0, 3) === 'TAG') {
                $title = trim(substr($id3v1, 3, 30));
                $artist = trim(substr($id3v1, 33, 30));
                $album = trim(substr($id3v1, 63, 30));
                $year = trim(substr($id3v1, 93, 4));

                if (!empty($title)) $data['title'] = $title;
                if (!empty($artist)) $data['artist'] = $artist;
                if (!empty($album)) $data['album'] = $album;
                if (!empty($year)) $data['year'] = (int) $year;
            }
        }

        // Estimate duration if unknown (assuming 128kbps = 16000 bytes/sec)
        if (empty($data['duration'])) {
            $data['duration'] = (int) round($filesize / 16000);
        }

        fclose($fp);
        return $data;
    }
}
