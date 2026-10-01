<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\Csrf;
use App\Helpers\Response;

class CsrfMiddleware
{
    public static function handle(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        // Only validate state-changing requests
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            // Exclude public API endpoints if applicable
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            if (str_starts_with($uri, '/api/')) {
                return;
            }

            if (!Csrf::validate()) {
                Response::abort(403, 'CSRF verification failed. Session may have expired. Please refresh and try again.');
            }
        }
    }
}
