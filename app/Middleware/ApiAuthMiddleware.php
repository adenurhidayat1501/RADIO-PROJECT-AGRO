<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\Auth;
use App\Helpers\Response;

class ApiAuthMiddleware
{
    public static function handle(): void
    {
        // First check session auth
        if (Auth::check() && Auth::hasRole('admin', 'manager', 'dj')) {
            return;
        }

        // Check Bearer Token or X-API-KEY header
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';

        $secret = config('app.secret');
        if ($apiKey === $secret) {
            return;
        }

        if (str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7);
            if ($token === $secret) {
                return;
            }
        }

        Response::json([
            'success' => false,
            'error' => 'Unauthorized API Access'
        ], 401);
    }
}
