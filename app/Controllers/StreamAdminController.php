<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Station;
use App\Services\StreamGeneratorService;
use App\Services\IcecastService;

class StreamAdminController extends BaseController
{
    public function index(): void
    {
        $station = Station::getPrimary();
        $streamUrl = StreamGeneratorService::getStreamUrl();
        $m3uUrl = StreamGeneratorService::getM3uUrl();
        $plsUrl = StreamGeneratorService::getPlsUrl();

        $icecast = new IcecastService();
        $status = $icecast->getStatus();

        $this->view('admin.stream.index', [
            'station' => $station,
            'streamUrl' => $streamUrl,
            'm3uUrl' => $m3uUrl,
            'plsUrl' => $plsUrl,
            'status' => $status,
        ]);
    }
}
