<?php

declare(strict_types=1);

namespace App\Helpers;

class Response
{
    public static function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function redirect(string $path, array $flash = []): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        foreach ($flash as $key => $value) {
            $_SESSION['_flash'][$key] = $value;
        }

        $url = (str_starts_with($path, 'http://') || str_starts_with($path, 'https://'))
            ? $path
            : base_url($path);

        header("Location: {$url}");
        exit;
    }

    public static function abort(int $status = 404, string $message = ''): void
    {
        http_response_code($status);

        if (empty($message)) {
            $message = match ($status) {
                400 => 'Bad Request',
                401 => 'Unauthorized',
                403 => 'Forbidden',
                404 => 'Page Not Found',
                500 => 'Internal Server Error',
                default => 'An error occurred',
            };
        }

        // Try to load error view if it exists
        $viewFile = __DIR__ . "/../../views/errors/{$status}.php";
        if (file_exists($viewFile)) {
            extract(['status' => $status, 'message' => $message]);
            require $viewFile;
            exit;
        }

        echo "<!DOCTYPE html><html><head><title>{$status} {$message}</title></head>";
        echo "<body style='font-family:sans-serif;text-align:center;padding:50px;'>";
        echo "<h1>{$status}</h1><p>{$message}</p>";
        echo "<a href='" . base_url() . "'>Return Home</a>";
        echo "</body></html>";
        exit;
    }

    public static function view(string $viewPath, array $data = []): void
    {
        $viewFile = __DIR__ . '/../../views/' . str_replace('.', '/', $viewPath) . '.php';

        if (!file_exists($viewFile)) {
            self::abort(500, "View [{$viewPath}] not found at {$viewFile}");
        }

        extract($data);
        require $viewFile;
        exit;
    }
}
