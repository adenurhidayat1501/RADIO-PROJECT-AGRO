<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Playlist;
use App\Models\PlaylistItem;
use App\Models\Song;
use App\Services\LiquidsoapService;

class PlaylistController extends BaseController
{
    public function index(): void
    {
        $playlists = Playlist::find([], ['sort' => ['name' => 1]]);

        // Calculate song count and duration for each playlist
        foreach ($playlists as &$pl) {
            $songs = Playlist::getSongs((string) $pl['_id']);
            $pl['song_count'] = count($songs);
            $totalSecs = array_sum(array_column($songs, 'duration'));
            $pl['total_duration'] = format_duration($totalSecs);
        }

        $this->view('admin.playlists.index', [
            'playlists' => $playlists,
        ]);
    }

    public function create(): void
    {
        $this->view('admin.playlists.create');
    }

    public function store(): void
    {
        $name = trim((string) ($_POST['name'] ?? ''));
        if (empty($name)) {
            $this->redirect('admin/playlists/create', ['error' => 'Playlist name is required.']);
        }

        $data = [
            'name' => $name,
            'description' => trim((string) ($_POST['description'] ?? '')),
            'type' => trim((string) ($_POST['type'] ?? 'music')),
            'status' => 'active',
            'shuffle' => isset($_POST['shuffle']),
            'crossfade' => isset($_POST['crossfade']),
        ];

        $playlist = Playlist::create($data);
        log_activity('create_playlist', ['name' => $name]);

        $this->redirect('admin/playlists/' . $playlist['_id'] . '/manage', ['success' => 'Playlist created! Now add songs to it.']);
    }

    public function manage(string $id): void
    {
        $playlist = Playlist::findById($id);
        if (!$playlist) {
            $this->redirect('admin/playlists', ['error' => 'Playlist not found.']);
        }

        $songs = Playlist::getSongs($id);
        $allSongs = Song::getActiveSongs();

        $this->view('admin.playlists.manage', [
            'playlist' => $playlist,
            'songs' => $songs,
            'allSongs' => $allSongs,
        ]);
    }

    public function addSong(string $id): void
    {
        $playlist = Playlist::findById($id);
        if (!$playlist) {
            $this->redirect('admin/playlists', ['error' => 'Playlist not found.']);
        }

        $songId = trim((string) ($_POST['song_id'] ?? ''));
        if (!empty($songId)) {
            PlaylistItem::addItem($id, $songId);
            $this->syncLiquidsoap();
        }

        $this->redirect('admin/playlists/' . $id . '/manage', ['success' => 'Track added to playlist.']);
    }

    public function removeSong(string $id, string $itemId): void
    {
        PlaylistItem::delete($itemId);
        $this->syncLiquidsoap();
        $this->redirect('admin/playlists/' . $id . '/manage', ['success' => 'Track removed from playlist.']);
    }

    public function reorder(string $id): void
    {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true) ?? [];
        $order = $_POST['order'] ?? ($json['order'] ?? []);

        if (is_array($order) && !empty($order)) {
            PlaylistItem::reorder($id, $order);
            $this->syncLiquidsoap();
            $this->json(['success' => true, 'message' => 'Playlist order saved successfully!']);
            return;
        }
        $this->json(['error' => 'Invalid order data'], 400);
    }

    public function delete(string $id): void
    {
        Playlist::delete($id);
        PlaylistItem::deleteByPlaylistId($id);
        $this->syncLiquidsoap();

        $this->redirect('admin/playlists', ['success' => 'Playlist deleted successfully.']);
    }

    private function syncLiquidsoap(): void
    {
        try {
            $liq = new LiquidsoapService();
            $liq->syncPlaylistFiles();
        } catch (\Throwable $e) {}
    }
}
