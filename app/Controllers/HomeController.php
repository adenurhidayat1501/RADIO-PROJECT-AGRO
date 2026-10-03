<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Station;
use App\Models\NowPlaying;
use App\Models\Schedule;
use App\Models\Program;
use App\Models\SongRequest;
use App\Models\DjAccount;
use App\Services\StreamGeneratorService;
use App\Services\IcecastService;

class HomeController extends BaseController
{
    public function index(): void
    {
        $station = Station::getPrimary();
        $nowPlaying = NowPlaying::getCurrent();
        $streamUrl = StreamGeneratorService::getStreamUrl();

        // Get Icecast status for live listener count
        $icecast = new IcecastService();
        $status = $icecast->getStatus();

        // Today's schedule (1 = Monday, 7 = Sunday)
        $dayOfWeek = (int) date('N');
        $todaySchedules = Schedule::getScheduleForDay($dayOfWeek);

        // Programs list
        $programs = Program::getActivePrograms();

        // Recent played / approved song requests
        $recentRequests = SongRequest::getRecentPublic(6);

        // Active show right now
        $activeSlot = Schedule::getCurrentActiveSlot($dayOfWeek, date('H:i'));

        // Active DJs
        $djs = DjAccount::find(['status' => 'active'], ['limit' => 6]);

        $this->view('public.index', [
            'station' => $station,
            'nowPlaying' => $nowPlaying,
            'streamUrl' => $streamUrl,
            'status' => $status,
            'todaySchedules' => $todaySchedules,
            'activeSlot' => $activeSlot,
            'programs' => $programs,
            'recentRequests' => $recentRequests,
            'djs' => $djs,
        ]);
    }
}
