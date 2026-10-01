<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Song;
use App\Models\Playlist;
use App\Models\SongRequest;
use App\Models\DjAccount;
use App\Models\NowPlaying;
use App\Models\StreamStat;
use App\Models\RadioEvent;
use App\Models\Station;
use App\Services\IcecastService;
use App\Services\StreamGeneratorService;

class DashboardController extends BaseController
{
    private IcecastService $icecast;

    public function __construct()
    {
        $this->icecast = new IcecastService();
    }

    public function index(): void
    {
        // 1. Live stream metrics from Icecast
        $status = $this->icecast->getStatus();
        $nowPlaying = NowPlaying::getCurrent();
        $station = Station::getPrimary();

        // 2. Aggregate counts
        $songCount = Song::count(['enabled' => true]);
        $playlistCount = Playlist::count(['status' => 'active']);
        $pendingRequests = SongRequest::count(['status' => 'pending']);
        $djCount = DjAccount::count(['status' => 'active']);

        // 3. Listener history data for Chart.js
        $period = (string) ($_GET['period'] ?? 'today');
        $chartData = StreamStat::getTimeline($period);

        // 4. Up next preview
        $allSongs = Song::find(['enabled' => true], ['limit' => 3]);
        $nextSong = !empty($allSongs) ? $allSongs[0] : null;

        // 5. Recent events
        $events = RadioEvent::getRecent(8);

        $this->view('admin.dashboard', [
            'status' => $status,
            'nowPlaying' => $nowPlaying,
            'station' => $station,
            'songCount' => $songCount,
            'playlistCount' => $playlistCount,
            'pendingRequests' => $pendingRequests,
            'djCount' => $djCount,
            'chartData' => $chartData,
            'period' => $period,
            'nextSong' => $nextSong,
            'events' => $events,
            'streamUrl' => StreamGeneratorService::getStreamUrl(),
        ]);
    }
}
