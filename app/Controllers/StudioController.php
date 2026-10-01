<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\NowPlaying;
use App\Models\DjAccount;
use App\Services\IcecastService;
use App\Services\LiquidsoapService;
use App\Services\StreamGeneratorService;

class StudioController extends BaseController
{
    public function index(): void
    {
        $nowPlaying = NowPlaying::getCurrent();
        $icecast = new IcecastService();
        $status = $icecast->getStatus();

        $harborConfig = [
            'host' => config('radio.liquidsoap.host', '127.0.0.1'),
            'port' => config('radio.liquidsoap.harbor_port', 8005),
            'mount' => config('radio.liquidsoap.harbor_mount', '/live-dj'),
            'user' => config('radio.liquidsoap.harbor_user', 'source'),
            'password' => config('radio.liquidsoap.harbor_password', 'dj_live_harbor_pass'),
        ];

        $djs = DjAccount::find(['status' => 'active']);

        $this->view('admin.studio.index', [
            'nowPlaying' => $nowPlaying,
            'status' => $status,
            'harborConfig' => $harborConfig,
            'djs' => $djs,
            'streamUrl' => StreamGeneratorService::getStreamUrl(),
        ]);
    }
}
