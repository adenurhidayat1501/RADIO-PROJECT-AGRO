<?php

return [
    'driver' => 'mongodb',
    'uri' => env('MONGODB_URI', 'mongodb://127.0.0.1:27017'),
    'database' => env('MONGODB_DATABASE', 'radio_platform'),
    'options' => [
        'connectTimeoutMS' => 5000,
        'serverSelectionTimeoutMS' => 5000,
    ],
];
