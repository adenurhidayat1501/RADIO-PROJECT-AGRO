<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Advertisement;
use App\Helpers\Id3TagReader;

class AdvertisementController extends BaseController
{
    private string $storagePath;

    public function __construct()
    {
        $this->storagePath = config('radio.paths.ads', '/var/lib/radio/ads');
        if (!is_dir($this->storagePath)) {
            @mkdir($this->storagePath, 0755, true);
        }
    }

    public function index(): void
    {
        $ads = Advertisement::find([], ['sort' => ['title' => 1]]);
        $this->view('admin.advertisements.index', ['ads' => $ads]);
    }

    public function upload(): void
    {
        if (empty($_FILES['audio_file'])) {
            $this->redirect('admin/advertisements', ['error' => 'No audio file selected.']);
        }

        $file = $_FILES['audio_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac', 'mp4'], true)) {
            $this->redirect('admin/advertisements', ['error' => 'Allowed formats: MP3, WAV, OGG, M4A, AAC, FLAC, MP4.']);
        }

        $title = trim((string) ($_POST['title'] ?? pathinfo($file['name'], PATHINFO_FILENAME)));
        $sponsor = trim((string) ($_POST['sponsor'] ?? ''));
        $safeBasename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $title);
        $safeBasename = trim(preg_replace('/_+/', '_', $safeBasename), '_') ?: 'ad_' . time();
        $targetFilename = 'ad_' . $safeBasename . '_' . time() . '.' . $ext;
        $targetPath = $this->storagePath . '/' . $targetFilename;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            if (in_array($ext, ['mp4', 'm4v', 'm4a', 'aac'], true)) {
                $mp3Target = $this->storagePath . '/ad_' . $safeBasename . '_' . time() . '.mp3';
                @shell_exec('ffmpeg -y -i ' . escapeshellarg($targetPath) . ' -vn -acodec libmp3lame -b:a 192k ' . escapeshellarg($mp3Target) . ' 2>/dev/null');
                if (file_exists($mp3Target) && filesize($mp3Target) > 1024) {
                    @unlink($targetPath);
                    $targetPath = $mp3Target;
                    $targetFilename = basename($mp3Target);
                }
            }
            $meta = Id3TagReader::read($targetPath);
            Advertisement::create([
                'title' => $title,
                'sponsor' => $sponsor,
                'filename' => $targetFilename,
                'filepath' => $targetPath,
                'duration' => $meta['duration'] ?? 0,
                'enabled' => true,
            ]);

            $this->redirect('admin/advertisements', ['success' => 'Spot advertisement added!']);
        }

        $this->redirect('admin/advertisements', ['error' => 'Failed to store advertisement.']);
    }

    public function toggle(string $id): void
    {
        $ad = Advertisement::findById($id);
        if ($ad) {
            $newStatus = !($ad['enabled'] ?? true);
            Advertisement::update($id, ['enabled' => $newStatus]);
        }
        $this->redirect('admin/advertisements');
    }

    public function delete(string $id): void
    {
        $ad = Advertisement::findById($id);
        if ($ad) {
            if (!empty($ad['filepath']) && file_exists($ad['filepath'])) {
                @unlink($ad['filepath']);
            }
            Advertisement::delete($id);
        }
        $this->redirect('admin/advertisements', ['success' => 'Advertisement deleted.']);
    }
}
