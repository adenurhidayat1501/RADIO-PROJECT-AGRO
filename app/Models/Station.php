<?php

declare(strict_types=1);

namespace App\Models;

class Station extends BaseModel
{
    protected static string $collection = 'stations';

    public static function getPrimary(): array
    {
        $station = static::findOne(['status' => 'active']);
        if (!$station) {
            // Seed default station
            $station = static::create([
                'name' => config('radio.station.name', 'Radio Agro'),
                'description' => config('radio.station.description', 'Radio Pertanian Mandiri & Musik 24/7'),
                'genre' => config('radio.station.genre', 'Agro, News & Music'),
                'language' => config('radio.station.language', 'id'),
                'country' => config('radio.station.country', 'ID'),
                'timezone' => config('radio.station.timezone', 'Asia/Jakarta'),
                'stream' => [
                    'mountpoint' => config('radio.icecast.mountpoint', '/live'),
                    'bitrate' => config('radio.stream.bitrate', 128),
                    'format' => config('radio.stream.format', 'mp3'),
                ],
                'status' => 'active',
            ]);
        }

        return $station;
    }
}
