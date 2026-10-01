<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\Auth;
use App\Helpers\Response;

class AuthMiddleware
{
    /**
     * Handle middleware check
     * @param string ...$allowedRoles Optional required roles (e.g. 'admin', 'manager')
     */
    public static function handle(string ...$allowedRoles): void
    {
        if (!Auth::check()) {
            // If AJAX / JSON request
            if (self::isJsonRequest()) {
                Response::json(['error' => 'Unauthenticated', 'status' => 401], 401);
            }
            Response::redirect('admin/login', ['error' => 'Please log in to access this page.']);
        }

        if (!empty($allowedRoles) && !Auth::hasRole(...$allowedRoles)) {
            if (self::isJsonRequest()) {
                Response::json(['error' => 'Forbidden - Insufficient permissions', 'status' => 403], 403);
            }
            Response::abort(403, 'You do not have permission to access this resource.');
        }
    }

    private static function isJsonRequest(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return str_contains($accept, 'application/json') || str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/');
    }
}
