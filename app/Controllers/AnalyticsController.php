<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\StreamStat;
use App\Models\ListenerSession;
use App\Services\IcecastService;

class AnalyticsController extends BaseController
{
    public function index(): void
    {
        $period = $_GET['period'] ?? 'today';
        $chartData = StreamStat::getTimeline($period);

        $icecast = new IcecastService();
        $liveStatus = $icecast->getStatus();

        $recentSessions = ListenerSession::find([], [
            'sort' => ['connected_at' => -1],
            'limit' => 30
        ]);

        $this->view('admin.analytics.index', [
            'period' => $period,
            'chartData' => $chartData,
            'liveStatus' => $liveStatus,
            'recentSessions' => $recentSessions,
        ]);
    }
}
