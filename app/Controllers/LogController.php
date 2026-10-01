<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Logger;

class LogController extends BaseController
{
    public function index(): void
    {
        $channel = $_GET['channel'] ?? 'app';
        if (!in_array($channel, ['app', 'radio', 'scheduler', 'stream'], true)) {
            $channel = 'app';
        }

        $lines = Logger::getLines($channel, 150);

        $this->view('admin.logs.index', [
            'currentChannel' => $channel,
            'lines' => $lines,
        ]);
    }

    public function clear(string $channel): void
    {
        if (in_array($channel, ['app', 'radio', 'scheduler', 'stream'], true)) {
            $path = __DIR__ . "/../../storage/logs/{$channel}.log";
            if (file_exists($path)) {
                @file_put_contents($path, '');
            }
        }

        $this->redirect('admin/logs?channel=' . $channel, ['success' => 'Log channel cleared.']);
    }
}
