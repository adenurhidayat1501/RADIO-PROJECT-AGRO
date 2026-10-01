<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Song;
use App\Helpers\Id3TagReader;
use App\Services\LiquidsoapService;

class MusicLibraryController extends BaseController
{
    private string $storagePath;

    public function __construct()
    {
        $this->storagePath = config('radio.paths.music', '/var/lib/radio/music');
        if (!is_dir($this->storagePath)) {
            @mkdir($this->storagePath, 0755, true);
        }
    }

    public function index(): void
    {
        $page = (int) ($_GET['page'] ?? 1);
        $search = trim((string) ($_GET['q'] ?? ''));

        $filter = [];
        if (!empty($search)) {
            $regex = new \MongoDB\BSON\Regex(preg_quote($search), 'i');
            $filter = [
                '$or' => [
                    ['title' => $regex],
                    ['artist' => $regex],
                    ['album' => $regex],
                    ['genre' => $regex],
                ]
            ];
        }

        $pagination = Song::paginate($filter, $page, 20, ['created_at' => -1]);

        $this->view('admin.music.index', [
            'songs' => $pagination['data'],
            'pagination' => $pagination,
            'search' => $search,
            'storagePath' => $this->storagePath,
        ]);
    }

    public function upload(): void
    {
        if (empty($_FILES['audio_file'])) {
            $this->redirect('admin/music', ['error' => 'No audio file was selected for upload.']);
        }

        $file = $_FILES['audio_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->redirect('admin/music', ['error' => 'File upload error code: ' . $file['error']]);
        }

        // Validate extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['mp3', 'wav', 'ogg', 'm4a', 'aac'], true)) {
            $this->redirect('admin/music', ['error' => 'Invalid audio format. Allowed: MP3, WAV, OGG, M4A, AAC.']);
        }

        // Validate MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = [
            'audio/mpeg', 'audio/mp3', 'audio/x-wav', 'audio/wav',
            'audio/ogg', 'audio/x-m4a', 'audio/aac', 'application/octet-stream'
        ];

        if (!in_array($mime, $allowedMimes, true)) {
            $this->redirect('admin/music', ['error' => "Disallowed MIME type: {$mime}"]);
        }

        // Generate sanitized unique filename
        $safeBasename = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
        $targetFilename = $safeBasename . '_' . time() . '.' . $ext;
        $targetPath = $this->storagePath . '/' . $targetFilename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            $this->redirect('admin/music', ['error' => 'Failed to store file in audio storage directory.']);
        }

        @chmod($targetPath, 0644);

        // Read ID3 metadata
        $meta = Id3TagReader::read($targetPath);

        // Save metadata into MongoDB
        $song = Song::create([
            'title' => !empty($meta['title']) ? $meta['title'] : $safeBasename,
            'artist' => !empty($meta['artist']) ? $meta['artist'] : 'Unknown Artist',
            'album' => !empty($meta['album']) ? $meta['album'] : 'Single',
            'genre' => !empty($meta['genre']) ? $meta['genre'] : 'Various',
            'year' => (int) ($meta['year'] ?? date('Y')),
            'duration' => (int) ($meta['duration'] ?? 0),
            'filename' => $targetFilename,
            'filepath' => $targetPath,
            'filesize' => (int) ($meta['filesize'] ?? filesize($targetPath)),
            'mime_type' => $mime,
            'cover' => '',
            'enabled' => true,
            'play_count' => 0,
        ]);

        log_activity('upload_song', ['title' => $song['title'], 'artist' => $song['artist']]);

        // Refresh AutoDJ playlists
        try {
            $liq = new LiquidsoapService();
            $liq->syncPlaylistFiles();
        } catch (\Throwable $e) {
            // Non-blocking
        }

        $this->redirect('admin/music', ['success' => "Track '{$song['artist']} - {$song['title']}' uploaded successfully!"]);
    }

    public function edit(string $id): void
    {
        $song = Song::findById($id);
        if (!$song) {
            $this->redirect('admin/music', ['error' => 'Track not found.']);
        }

        $this->view('admin.music.edit', ['song' => $song]);
    }

    public function update(string $id): void
    {
        $song = Song::findById($id);
        if (!$song) {
            $this->redirect('admin/music', ['error' => 'Track not found.']);
        }

        $data = [
            'title' => trim((string) ($_POST['title'] ?? $song['title'])),
            'artist' => trim((string) ($_POST['artist'] ?? $song['artist'])),
            'album' => trim((string) ($_POST['album'] ?? $song['album'])),
            'genre' => trim((string) ($_POST['genre'] ?? $song['genre'])),
            'year' => (int) ($_POST['year'] ?? $song['year']),
            'enabled' => isset($_POST['enabled']),
        ];

        Song::update($id, $data);
        log_activity('update_song', ['id' => $id, 'title' => $data['title']]);

        $this->redirect('admin/music', ['success' => 'Track metadata updated successfully!']);
    }

    public function delete(string $id): void
    {
        $song = Song::findById($id);
        if (!$song) {
            $this->redirect('admin/music', ['error' => 'Track not found.']);
        }

        // Delete audio file from VPS filesystem if exists
        if (!empty($song['filepath']) && file_exists($song['filepath'])) {
            @unlink($song['filepath']);
        }

        Song::delete($id);
        log_activity('delete_song', ['id' => $id, 'title' => $song['title']]);

        // Sync playlists
        try {
            $liq = new LiquidsoapService();
            $liq->syncPlaylistFiles();
        } catch (\Throwable $e) {}

        $this->redirect('admin/music', ['success' => 'Track removed from library and storage.']);
    }
}
