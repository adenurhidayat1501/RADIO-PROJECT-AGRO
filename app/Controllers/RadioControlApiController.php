<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\LiquidsoapService;
use App\Models\NowPlaying;
use App\Models\RadioEvent;
use App\Models\Song;

class RadioControlApiController extends BaseController
{
    private LiquidsoapService $liquidsoap;

    public function __construct()
    {
        $this->liquidsoap = new LiquidsoapService();
    }

    /**
     * POST /api/admin/radio/skip
     */
    public function skip(): void
    {
        $success = $this->liquidsoap->skipTrack();
        log_activity('radio_skip_track');
        RadioEvent::logEvent('song_skipped', 'admin');

        $this->json([
            'success' => $success,
            'message' => $success ? 'Track skipped successfully.' : 'Failed to communicate with Liquidsoap telnet interface.'
        ]);
    }

    /**
     * POST /api/admin/radio/auto-dj
     */
    public function toggleAutoDj(): void
    {
        $action = trim((string) ($_POST['action'] ?? 'restart'));

        if ($action === 'stop') {
            $this->liquidsoap->sendTelnet('autodj.stop');
            RadioEvent::logEvent('auto_dj_stopped', 'admin');
            $msg = 'Auto DJ paused.';
        } elseif ($action === 'start') {
            $this->liquidsoap->sendTelnet('autodj.start');
            RadioEvent::logEvent('auto_dj_started', 'admin');
            $msg = 'Auto DJ resumed.';
        } else {
            $this->liquidsoap->reloadService();
            RadioEvent::logEvent('auto_dj_reloaded', 'admin');
            $msg = 'Auto DJ reloaded with refreshed playlists.';
        }

        $this->json(['success' => true, 'message' => $msg]);
    }

    /**
     * POST /api/admin/radio/live
     */
    public function toggleLiveMode(): void
    {
        $action = trim((string) ($_POST['action'] ?? 'status'));
        $res = $this->liquidsoap->sendTelnet('live_dj.status');

        $this->json([
            'success' => true,
            'harbor_status' => $res,
        ]);
    }

    /**
     * GET/POST /api/internal/liquidsoap/on-track
     * Callback triggered by Liquidsoap notify_metadata hook
     */
    public function onTrack(): void
    {
        $artist = trim((string) ($_GET['artist'] ?? ($_POST['artist'] ?? '')));
        $title = trim((string) ($_GET['title'] ?? ($_POST['title'] ?? '')));

        if (!empty($title)) {
            // Find song in library to increment play_count and get album
            $matchedSong = Song::findOne([
                'title' => $title,
                'artist' => $artist,
            ]);

            if ($matchedSong) {
                Song::incrementPlayCount((string) $matchedSong['_id']);
            }

            NowPlaying::setCurrent([
                'title' => $title,
                'artist' => !empty($artist) ? $artist : 'Radio Agro',
                'album' => $matchedSong['album'] ?? 'Single',
                'duration' => $matchedSong['duration'] ?? 0,
                'song_id' => $matchedSong ? $matchedSong['_id'] : null,
                'source' => 'auto_dj',
            ]);

            RadioEvent::logEvent('song_started', 'liquidsoap', [
                'artist' => $artist,
                'title' => $title,
            ]);
        }

        $this->json(['success' => true]);
    }

    /**
     * GET /api/internal/liquidsoap/on-live-connect
     */
    public function onLiveConnect(): void
    {
        NowPlaying::setCurrent([
            'source' => 'live_dj',
            'title' => 'Live Studio Broadcast',
            'artist' => 'Live DJ On Air',
            'album' => 'Live Studio',
        ]);

        RadioEvent::logEvent('live_started', 'harbor');

        $this->json(['success' => true]);
    }

    /**
     * GET /api/internal/liquidsoap/on-live-disconnect
     */
    public function onLiveDisconnect(): void
    {
        NowPlaying::setCurrent([
            'source' => 'auto_dj',
            'title' => 'Auto DJ Resumed',
            'artist' => 'Radio Agro',
        ]);

        RadioEvent::logEvent('live_stopped', 'harbor');

        $this->json(['success' => true]);
    }
}
