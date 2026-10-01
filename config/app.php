<?php

return [
    'name' => env('APP_NAME', 'Radio Agro Platform'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'secret' => env('APP_SECRET', 'radio_secret_key_change_in_production_32chars'),
    'timezone' => env('TIMEZONE', 'Asia/Jakarta'),
    'session_lifetime' => (int) env('SESSION_LIFETIME', 86400),
];
