<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Station;
use App\Services\LiquidsoapService;

class StationController extends BaseController
{
    public function index(): void
    {
        $station = Station::getPrimary();
        $this->view('admin.station.index', [
            'station' => $station,
        ]);
    }

    public function update(): void
    {
        $station = Station::getPrimary();

        $data = [
            'name' => trim((string) ($_POST['name'] ?? 'Radio Agro')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'genre' => trim((string) ($_POST['genre'] ?? 'Various')),
            'language' => trim((string) ($_POST['language'] ?? 'id')),
            'country' => trim((string) ($_POST['country'] ?? 'ID')),
            'timezone' => trim((string) ($_POST['timezone'] ?? 'Asia/Jakarta')),
            'stream' => [
                'mountpoint' => '/' . ltrim(trim((string) ($_POST['mountpoint'] ?? '/live')), '/'),
                'bitrate' => (int) ($_POST['bitrate'] ?? 128),
                'format' => trim((string) ($_POST['format'] ?? 'mp3')),
            ],
            'status' => 'active',
        ];

        Station::update($station['_id'], $data);
        log_activity('update_station_profile', ['name' => $data['name']]);

        // Trigger liquidsoap config regeneration
        try {
            $liq = new LiquidsoapService();
            $liq->generateConfig();
        } catch (\Throwable $e) {
            // Log warning
        }

        $this->redirect('admin/station', ['success' => 'Station identity and broadcast settings updated successfully!']);
    }
}
