<?php

declare(strict_types=1);

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Response;
use App\Models\ActivityLog;

/**
 * Get environment variable with fallback
 */
if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed {
        $value = $_ENV[$key] ?? getenv($key);
        if ($value === false || $value === null) {
            return $default;
        }

        return match (strtolower((string) $value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'empty', '(empty)' => '',
            'null', '(null)' => null,
            default => $value,
        };
    }
}

/**
 * Get config variable using dot notation
 */
if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed {
        static $configs = [];

        $parts = explode('.', $key);
        $file = array_shift($parts);

        if (!isset($configs[$file])) {
            $filePath = __DIR__ . '/../../config/' . $file . '.php';
            if (file_exists($filePath)) {
                $configs[$file] = require $filePath;
            } else {
                $configs[$file] = [];
            }
        }

        $current = $configs[$file];
        foreach ($parts as $part) {
            if (is_array($current) && array_key_exists($part, $current)) {
                $current = $current[$part];
            } else {
                return $default;
            }
        }

        return $current;
    }
}

/**
 * Escape HTML
 */
if (!function_exists('e')) {
    function e(mixed $value): string {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Base URL generator
 */
if (!function_exists('base_url')) {
    function base_url(string $path = ''): string {
        $baseUrl = rtrim((string) config('app.url', 'http://localhost'), '/');
        $path = ltrim($path, '/');
        return $path === '' ? $baseUrl : $baseUrl . '/' . $path;
    }
}

/**
 * Public asset URL generator with automatic cache-busting
 */
if (!function_exists('asset')) {
    function asset(string $path): string {
        $cleanPath = ltrim($path, '/');
        $realFile = __DIR__ . '/../../public/assets/' . $cleanPath;
        $ver = file_exists($realFile) ? filemtime($realFile) : '2.2.0';
        return base_url('assets/' . $cleanPath) . '?v=' . $ver;
    }
}

/**
 * Render a view file
 */
if (!function_exists('view')) {
    function view(string $viewPath, array $data = []): void {
        Response::view($viewPath, $data);
    }
}

/**
 * Return JSON response
 */
if (!function_exists('json_response')) {
    function json_response(mixed $data, int $status = 200): void {
        Response::json($data, $status);
    }
}

/**
 * Redirect to path
 */
if (!function_exists('redirect')) {
    function redirect(string $path, array $flash = []): void {
        Response::redirect($path, $flash);
    }
}

/**
 * Flash message helper
 */
if (!function_exists('flash')) {
    function flash(?string $key = null, mixed $value = null): mixed {
        if (!isset($_SESSION)) {
            return null;
        }

        if ($key === null) {
            $flashes = $_SESSION['_flash'] ?? [];
            unset($_SESSION['_flash']);
            return $flashes;
        }

        if ($value !== null) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }

        $val = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $val;
    }
}

/**
 * CSRF token string
 */
if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        return Csrf::getToken();
    }
}

/**
 * CSRF hidden HTML input
 */
if (!function_exists('csrf_field')) {
    function csrf_field(): string {
        return Csrf::field();
    }
}

/**
 * Auth user instance
 */
if (!function_exists('auth_user')) {
    function auth_user(): ?array {
        return Auth::user();
    }
}

/**
 * Check if logged in
 */
if (!function_exists('auth_check')) {
    function auth_check(): bool {
        return Auth::check();
    }
}

/**
 * Check user role hierarchy
 */
if (!function_exists('has_role')) {
    function has_role(string ...$allowedRoles): bool {
        return Auth::hasRole(...$allowedRoles);
    }
}

/**
 * Format bytes to readable string
 */
if (!function_exists('format_bytes')) {
    function format_bytes(int $bytes, int $precision = 2): string {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

/**
 * Format seconds to mm:ss or hh:mm:ss
 */
if (!function_exists('format_duration')) {
    function format_duration(int|float $seconds): string {
        $seconds = (int) round($seconds);
        $hours = floor($seconds / 3600);
        $mins = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
        }
        return sprintf('%02d:%02d', $mins, $secs);
    }
}

/**
 * Record activity log in MongoDB
 */
if (!function_exists('log_activity')) {
    function log_activity(string $action, array|string $details = [], ?string $userId = null): void {
        try {
            $user = auth_user();
            $logData = [
                'user_id' => $userId ?? ($user['_id'] ?? null),
                'username' => $user['username'] ?? 'system',
                'action' => $action,
                'details' => is_string($details) ? ['message' => $details] : $details,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'CLI/System',
                'created_at' => new \MongoDB\BSON\UTCDateTime(),
            ];

            ActivityLog::create($logData);
        } catch (\Throwable $e) {
            // Silently fallback to file log if DB write fails
            \App\Helpers\Logger::error("Failed to log activity [{$action}]: " . $e->getMessage());
        }
    }
}
