<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Response;

abstract class BaseController
{
    protected function view(string $viewPath, array $data = []): void
    {
        Response::view($viewPath, $data);
    }

    protected function json(mixed $data, int $status = 200): void
    {
        Response::json($data, $status);
    }

    protected function redirect(string $path, array $flash = []): void
    {
        Response::redirect($path, $flash);
    }
}
