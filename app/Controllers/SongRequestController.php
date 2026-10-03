<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\SongRequest;
use App\Models\Song;
use App\Services\LiquidsoapService;

class SongRequestController extends BaseController
{
    public function index(): void
    {
        $status = $_GET['status'] ?? 'pending';
        $filter = !empty($status) && $status !== 'all' ? ['status' => $status] : [];

        $requests = SongRequest::find($filter, ['sort' => ['created_at' => -1], 'limit' => 100]);

        // Hydrate song details
        foreach ($requests as &$req) {
            if (!empty($req['song_id'])) {
                $req['song'] = Song::findById($req['song_id']);
            }
        }

        $this->view('admin.requests.index', [
            'requests' => $requests,
            'currentStatus' => $status,
        ]);
    }

    public function approve(string $id): void
    {
        SongRequest::update($id, ['status' => 'approved']);
        $this->redirect('admin/requests', ['success' => 'Request approved.']);
    }

    public function reject(string $id): void
    {
        SongRequest::update($id, ['status' => 'rejected']);
        $this->redirect('admin/requests', ['success' => 'Request rejected.']);
    }

    public function playNext(string $id): void
    {
        $request = SongRequest::findById($id);
        if ($request && !empty($request['song_id'])) {
            $song = Song::findById($request['song_id']);
            if ($song && !empty($song['filepath'])) {
                try {
                    $liq = new LiquidsoapService();
                    // Push song to liquidsoap queue via telnet
                    $escaped = escapeshellarg($song['filepath']);
                    $liq->sendTelnet("autodj.push {$escaped}");
                } catch (\Throwable $e) {}
            }

            SongRequest::markPlayed($id);
            $this->redirect('admin/requests', ['success' => 'Track queued for immediate / next playback!']);
        }

        $this->redirect('admin/requests', ['error' => 'Could not queue song.']);
    }

    public function delete(string $id): void
    {
        SongRequest::delete($id);
        $this->redirect('admin/requests', ['success' => 'Request deleted.']);
    }
}
