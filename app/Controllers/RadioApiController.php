<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\NowPlaying;
use App\Models\Song;
use App\Models\SongRequest;
use App\Models\Schedule;
use App\Models\Station;
use App\Services\IcecastService;
use App\Services\StreamGeneratorService;

class RadioApiController extends BaseController
{
    private IcecastService $icecast;

    public function __construct()
    {
        $this->icecast = new IcecastService();
    }

    /**
     * GET /api/radio/status
     */
    public function status(): void
    {
        $status = $this->icecast->getStatus();
        $nowPlaying = NowPlaying::getCurrent();

        $this->json([
            'online' => $status['online'],
            'server_online' => $status['server_online'],
            'mode' => $nowPlaying['source'] ?? 'auto_dj',
            'listeners' => $status['listeners'],
            'peak' => $status['peak'],
            'bitrate' => $status['bitrate'],
            'now_playing' => [
                'artist' => $nowPlaying['artist'] ?? $status['artist'],
                'title' => $nowPlaying['title'] ?? $status['title'],
                'album' => $nowPlaying['album'] ?? '',
                'duration' => $nowPlaying['duration'] ?? 0,
            ],
            'stream_url' => StreamGeneratorService::getStreamUrl(),
        ]);
    }

    /**
     * GET /api/radio/now-playing
     * Standard response according to Section 13
     */
    public function nowPlaying(): void
    {
        $status = $this->icecast->getStatus();
        $now = NowPlaying::getCurrent();

        $artist = !empty($now['artist']) ? $now['artist'] : ($status['artist'] ?: 'Radio Agro');
        $title = !empty($now['title']) ? $now['title'] : ($status['title'] ?: 'Live Broadcast');

        $this->json([
            'status' => $status['online'] ? 'online' : 'offline',
            'mode' => $now['source'] ?? 'auto_dj',
            'listeners' => $status['listeners'],
            'now_playing' => [
                'artist' => $artist,
                'title' => $title,
                'album' => $now['album'] ?? '',
                'duration' => $now['duration'] ?? 0,
                'started_at' => $now['started_at'] ?? '',
            ]
        ]);
    }

    /**
     * GET /api/radio/listeners
     */
    public function listeners(): void
    {
        $status = $this->icecast->getStatus();
        $this->json([
            'listeners' => $status['listeners'],
            'peak' => $status['peak'],
            'timestamp' => date('c'),
        ]);
    }

    /**
     * GET /api/radio/next-song
     */
    public function nextSong(): void
    {
        // Sample random active song from music library to preview up next
        $songs = Song::find(['enabled' => true], ['limit' => 5]);
        $next = !empty($songs) ? $songs[array_rand($songs)] : null;

        $this->json([
            'next_song' => $next ? [
                'title' => $next['title'],
                'artist' => $next['artist'],
                'album' => $next['album'] ?? '',
                'duration' => $next['duration'] ?? 0,
            ] : null,
        ]);
    }

    /**
     * GET /api/songs?q=keyword
     */
    public function songs(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        if (strlen($q) < 2) {
            $songs = Song::find(['enabled' => true], ['limit' => 20, 'sort' => ['title' => 1]]);
        } else {
            $songs = Song::search($q, 30);
        }

        $formatted = array_map(function ($s) {
            return [
                'id' => $s['_id'],
                'title' => $s['title'],
                'artist' => $s['artist'],
                'album' => $s['album'] ?? '',
                'duration' => format_duration($s['duration'] ?? 0),
            ];
        }, $songs);

        $this->json(['songs' => $formatted]);
    }

    /**
     * POST /api/song-request
     */
    public function submitSongRequest(): void
    {
        $name = trim((string) ($_POST['name'] ?? ''));
        $songId = trim((string) ($_POST['song_id'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));

        if (empty($name)) {
            $this->json(['error' => 'Please enter your name.'], 400);
        }

        if (empty($songId)) {
            $this->json(['error' => 'Please select a valid song.'], 400);
        }

        $song = Song::findById($songId);
        if (!$song || !($song['enabled'] ?? true)) {
            $this->json(['error' => 'The selected song is not available in the library.'], 400);
        }

        // Rate limit: 1 request every 5 minutes per IP
        $recent = SongRequest::findOne([
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'created_at' => ['$gte' => new \MongoDB\BSON\UTCDateTime((time() - 300) * 1000)],
        ]);

        if ($recent) {
            $this->json(['error' => 'You can only request one song every 5 minutes. Please wait.'], 429);
        }

        $request = SongRequest::createRequest($name, $songId, $message);

        $this->json([
            'success' => true,
            'message' => 'Song request submitted successfully! Our DJ / Auto DJ will review it shortly.',
            'request_id' => $request['_id'],
        ]);
    }

    /**
     * GET /api/schedule
     */
    public function schedule(): void
    {
        $grid = Schedule::getWeeklyGrid();
        $this->json(['schedule' => $grid]);
    }
}
