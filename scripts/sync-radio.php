#!/usr/bin/env php
<?php

declare(strict_types=1);

// CLI daemon/cron to sync Icecast statistics into MongoDB
if (php_sapi_name() !== 'cli') {
    die("This script must be run from CLI.\n");
}

require_once __DIR__ . '/../bootstrap.php';

use App\Services\IcecastService;
use App\Helpers\Logger;

$icecast = new IcecastService();

$runLoop = in_array('--loop', $argv, true);

do {
    try {
        $status = $icecast->syncStats();
        $listeners = $status['listeners'] ?? 0;
        $title = $status['title'] ?? 'Offline';
        $online = $status['online'] ? 'ONLINE' : 'OFFLINE';

        echo sprintf("[%s] Status: %s | Listeners: %d | Track: %s\n", date('Y-m-d H:i:s'), $online, $listeners, $title);
        Logger::info("Stream sync: {$online}, {$listeners} listeners, track: {$title}", [], 'stream');
    } catch (\Throwable $e) {
        echo "ERROR syncing radio status: " . $e->getMessage() . "\n";
        Logger::error("Stream sync error: " . $e->getMessage(), [], 'stream');
    }

    if ($runLoop) {
        sleep(10); // Poll every 10 seconds in daemon mode
    }
} while ($runLoop);
