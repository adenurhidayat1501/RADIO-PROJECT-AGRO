<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Song;
use App\Models\PlaylistItem;
use App\Helpers\Id3TagReader;
use App\Services\LiquidsoapService;

class MusicLibraryController extends BaseController
{
    private string $storagePath;

    public function __construct()
    {
        $this->storagePath = config('radio.paths.music', '/var/lib/radio/music');
        if (!is_dir($this->storagePath)) {
            @mkdir($this->storagePath, 0775, true);
        }
        @chmod($this->storagePath, 0775);
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

        // Auto-heal any tracks displaying 0 bytes or 00:00 duration
        foreach ($pagination['data'] as &$songItem) {
            if ((empty($songItem['filesize']) || empty($songItem['duration'])) && !empty($songItem['filepath'])) {
                clearstatcache(true, (string) $songItem['filepath']);
                if (file_exists((string) $songItem['filepath'])) {
                    $refreshed = Id3TagReader::read((string) $songItem['filepath']);
                    $calcSize = (int) ($refreshed['filesize'] ?: filesize((string) $songItem['filepath']));
                    $calcDur = (int) ($refreshed['duration'] ?: round(($calcSize * 8) / (128 * 1000)));

                    $updateData = [];
                    if (empty($songItem['filesize']) && $calcSize > 0) {
                        $updateData['filesize'] = $calcSize;
                        $songItem['filesize'] = $calcSize;
                    }
                    if (empty($songItem['duration']) && $calcDur > 0) {
                        $updateData['duration'] = $calcDur;
                        $songItem['duration'] = $calcDur;
                    }
                    if (!empty($updateData)) {
                        Song::update((string) $songItem['_id'], $updateData);
                    }
                }
            }
        }
        unset($songItem);

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
        $allowedExts = ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac', 'mp4', 'wma'];
        if (!in_array($ext, $allowedExts, true)) {
            $this->redirect('admin/music', ['error' => 'Invalid audio format. Allowed: MP3, WAV, OGG, M4A, AAC, FLAC, MP4.']);
        }

        // Validate MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = [
            'audio/mpeg', 'audio/mp3', 'audio/x-wav', 'audio/wav',
            'audio/ogg', 'application/ogg', 'audio/x-m4a', 'audio/m4a', 'audio/aac',
            'audio/x-aac', 'audio/mp4', 'video/mp4', 'video/x-m4v', 'audio/flac', 'audio/x-flac',
            'application/octet-stream', 'audio/webm', 'video/webm', 'audio/x-ms-wma'
        ];

        $isAudio = str_starts_with($mime, 'audio/') || in_array($mime, $allowedMimes, true);
        if (!$isAudio) {
            $this->redirect('admin/music', ['error' => "Disallowed file type: {$mime}"]);
        }

        // Generate sanitized unique filename
        $rawBasename = pathinfo($file['name'], PATHINFO_FILENAME);
        $safeBasename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $rawBasename);
        $safeBasename = trim(preg_replace('/_+/', '_', $safeBasename), '_');
        if (empty($safeBasename)) {
            $safeBasename = 'track_' . time();
        }

        $targetFilename = $safeBasename . '_' . time() . '.' . $ext;
        $targetPath = $this->storagePath . '/' . $targetFilename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            $this->redirect('admin/music', ['error' => 'Failed to store file in audio storage directory.']);
        }

        @chmod($targetPath, 0644);

        // If file contains video container (e.g. video/mp4, video/webm from YouTube/TikTok downloads),
        // extract and convert audio to clean pure 192k MP3 for seamless Liquidsoap & Icecast broadcasting
        if (in_array($mime, ['video/mp4', 'video/webm', 'video/x-m4v', 'application/octet-stream'], true) || in_array($ext, ['mp4', 'm4v'], true)) {
            $mp3Target = $this->storagePath . '/' . $safeBasename . '_' . time() . '.mp3';
            $escapedSrc = escapeshellarg($targetPath);
            $escapedDst = escapeshellarg($mp3Target);
            @shell_exec("ffmpeg -y -i {$escapedSrc} -vn -acodec libmp3lame -b:a 192k {$escapedDst} 2>/dev/null");
            if (file_exists($mp3Target) && filesize($mp3Target) > 1024) {
                @unlink($targetPath);
                $targetPath = $mp3Target;
                $targetFilename = basename($mp3Target);
                $ext = 'mp3';
                $mime = 'audio/mpeg';
            }
        }

        // Read ID3 metadata
        clearstatcache(true, $targetPath);
        $finalSize = file_exists($targetPath) ? (int) filesize($targetPath) : (int) ($file['size'] ?? 0);
        $meta = Id3TagReader::read($targetPath);
        $finalDuration = (int) ($meta['duration'] ?? 0);
        if ($finalDuration <= 0 && $finalSize > 0) {
            $finalDuration = (int) round(($finalSize * 8) / (128 * 1000));
        }

        // Save metadata into MongoDB
        $song = Song::create([
            'title' => !empty($meta['title']) ? $meta['title'] : $safeBasename,
            'artist' => !empty($meta['artist']) ? $meta['artist'] : 'Unknown Artist',
            'album' => !empty($meta['album']) ? $meta['album'] : 'Single',
            'genre' => !empty($meta['genre']) ? $meta['genre'] : 'Various',
            'year' => (int) ($meta['year'] ?? date('Y')),
            'duration' => $finalDuration,
            'filename' => $targetFilename,
            'filepath' => $targetPath,
            'filesize' => $finalSize ?: (int) ($meta['filesize'] ?? 0),
            'mime_type' => $mime,
            'cover' => '',
            'enabled' => true,
            'play_count' => 0,
        ]);

        log_activity('upload_song', ['title' => $song['title'], 'artist' => $song['artist']]);

        // Refresh AutoDJ playlists and trigger immediate stream transition
        try {
            $liq = new LiquidsoapService();
            $liq->syncPlaylistFiles();
            $liq->sendTelnet('autodj.reload');
            $liq->sendTelnet('autodj.skip');
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
        PlaylistItem::deleteBySongId($id);
        log_activity('delete_song', ['id' => $id, 'title' => $song['title']]);

        // Sync playlists and reload
        try {
            $liq = new LiquidsoapService();
            $liq->syncPlaylistFiles();
            $liq->sendTelnet('autodj.reload');
        } catch (\Throwable $e) {}

        $this->redirect('admin/music', ['success' => 'Track removed from library and storage.']);
    }
}
