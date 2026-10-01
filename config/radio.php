<?php

return [
    'station' => [
        'name' => env('STATION_NAME', 'Radio Agro'),
        'description' => env('STATION_DESCRIPTION', 'Radio Pertanian Mandiri & Hiburan'),
        'genre' => env('STATION_GENRE', 'Agro, News, Music'),
        'language' => env('STATION_LANGUAGE', 'id'),
        'country' => env('STATION_COUNTRY', 'ID'),
        'timezone' => env('TIMEZONE', 'Asia/Jakarta'),
    ],

    'icecast' => [
        'host' => env('ICECAST_HOST', '127.0.0.1'),
        'port' => (int) env('ICECAST_PORT', 8000),
        'mountpoint' => env('ICECAST_MOUNTPATH', '/live'),
        'admin_user' => env('ICECAST_ADMIN_USER', 'admin'),
        'admin_password' => env('ICECAST_ADMIN_PASSWORD', 'hackme_admin'),
        'source_password' => env('ICECAST_SOURCE_PASSWORD', 'hackme_source'),
        'public_url' => env('ICECAST_PUBLIC_URL', 'http://127.0.0.1:8000/live'),
    ],

    'liquidsoap' => [
        'host' => env('LIQUIDSOAP_HOST', '127.0.0.1'),
        'telnet_port' => (int) env('LIQUIDSOAP_TELNET_PORT', 1234),
        'harbor_port' => (int) env('LIQUIDSOAP_HARBOR_PORT', 8005),
        'harbor_mount' => env('LIQUIDSOAP_HARBOR_MOUNT', '/live-dj'),
        'harbor_user' => env('LIQUIDSOAP_HARBOR_USER', 'source'),
        'harbor_password' => env('LIQUIDSOAP_HARBOR_PASSWORD', 'dj_live_harbor_pass'),
    ],

    'paths' => [
        'music' => env('AUDIO_STORAGE_PATH', '/var/lib/radio/music'),
        'jingles' => env('JINGLE_STORAGE_PATH', '/var/lib/radio/jingles'),
        'ads' => env('AD_STORAGE_PATH', '/var/lib/radio/ads'),
        'fallback' => env('FALLBACK_AUDIO_PATH', '/var/lib/radio/fallback/default.mp3'),
        'playlists_dir' => '/var/lib/radio/playlists',
        'generated_config' => '/etc/radio/radio.liq',
    ],

    'stream' => [
        'bitrate' => (int) env('RADIO_BITRATE', 128),
        'format' => env('RADIO_FORMAT', 'mp3'),
        'supported_formats' => ['mp3', 'aac', 'ogg'],
        'supported_bitrates' => [64, 96, 128, 192, 256, 320],
    ],
];
