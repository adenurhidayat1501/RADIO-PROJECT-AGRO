<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

// Configure session and run dispatcher only for HTTP web requests
if (php_sapi_name() !== 'cli') {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    // Load Routes
    require_once __DIR__ . '/../routes/web.php';
    require_once __DIR__ . '/../routes/api.php';

    // Dispatch Request
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    App\Services\Router::dispatch($uri, $method);
}

