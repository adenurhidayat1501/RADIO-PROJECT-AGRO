<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\StreamGeneratorService;

class StreamController extends BaseController
{
    public function direct(): void
    {
        $url = StreamGeneratorService::getStreamUrl();
        header("Location: {$url}");
        exit;
    }

    public function m3u(): void
    {
        $content = StreamGeneratorService::generateM3uContent();
        header('Content-Type: audio/x-mpegurl; charset=utf-8');
        header('Content-Disposition: inline; filename="stream.m3u"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        echo $content;
        exit;
    }

    public function pls(): void
    {
        $content = StreamGeneratorService::generatePlsContent();
        header('Content-Type: audio/x-scpls; charset=utf-8');
        header('Content-Disposition: inline; filename="stream.pls"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        echo $content;
        exit;
    }
}
