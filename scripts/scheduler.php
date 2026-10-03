#!/usr/bin/env php
<?php

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    die("This script must be run from CLI.\n");
}

require_once __DIR__ . '/../bootstrap.php';

use App\Models\Schedule;
use App\Models\Playlist;
use App\Models\NowPlaying;
use App\Models\RadioEvent;
use App\Services\LiquidsoapService;
use App\Helpers\Logger;

$runLoop = in_array('--loop', $argv, true);

echo "=== RADIO AUTOMATION SCHEDULER STARTED ===\n";

do {
    try {
        $dayOfWeek = (int) date('N'); // 1 (Monday) to 7 (Sunday)
        $currentTime = date('H:i');

        $activeSlot = Schedule::getCurrentActiveSlot($dayOfWeek, $currentTime);

        if ($activeSlot) {
            echo sprintf("[%s] Active Show: '%s' (%s - %s) Mode: %s\n",
                date('Y-m-d H:i:s'),
                $activeSlot['name'],
                $activeSlot['start_time'],
                $activeSlot['end_time'],
                $activeSlot['mode']
            );

            // If scheduled mode is auto_dj with a specific playlist, ensure liquidsoap plays it
            if ($activeSlot['mode'] === 'auto_dj' && !empty($activeSlot['playlist_id'])) {
                $playlistsDir = config('radio.paths.playlists_dir', '/var/lib/radio/playlists');
                $pId = (string) $activeSlot['playlist_id'];
                $targetM3u = $playlistsDir . "/playlist_{$pId}.m3u";
                $defaultM3u = $playlistsDir . '/default.m3u';

                if (!file_exists($targetM3u) && !empty($activeSlot['playlist_name'])) {
                    $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) $activeSlot['playlist_name']);
                    $targetM3u = $playlistsDir . "/playlist_{$safeName}.m3u";
                }

                if (file_exists($targetM3u)) {
                    // Update default.m3u with fresh timestamp
                    @copy($targetM3u, $defaultM3u);
                    @touch($defaultM3u);

                    // Notify Liquidsoap to reload playlist
                    try {
                        $liq = new LiquidsoapService();
                        $liq->sendTelnet('autodj.reload');
                    } catch (\Throwable $e) {}
                }
            }

            Logger::info("Scheduler verified slot: '{$activeSlot['name']}'", [], 'scheduler');
        } else {
            echo sprintf("[%s] Normal Rotation (No dedicated timetable slot configured for %s)\n",
                date('Y-m-d H:i:s'),
                $currentTime
            );
        }
    } catch (\Throwable $e) {
        echo "Scheduler Error: " . $e->getMessage() . "\n";
        Logger::error("Scheduler cycle failure: " . $e->getMessage(), [], 'scheduler');
    }

    if ($runLoop) {
        sleep(60); // Check every minute in daemon mode
    }
} while ($runLoop);
