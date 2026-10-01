<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Jingle;
use App\Helpers\Id3TagReader;
use App\Services\LiquidsoapService;

class JingleController extends BaseController
{
    private string $storagePath;

    public function __construct()
    {
        $this->storagePath = config('radio.paths.jingles', '/var/lib/radio/jingles');
        if (!is_dir($this->storagePath)) {
            @mkdir($this->storagePath, 0755, true);
        }
    }

    public function index(): void
    {
        $jingles = Jingle::find([], ['sort' => ['title' => 1]]);
        $this->view('admin.jingles.index', ['jingles' => $jingles]);
    }

    public function upload(): void
    {
        if (empty($_FILES['audio_file'])) {
            $this->redirect('admin/jingles', ['error' => 'No audio file selected.']);
        }

        $file = $_FILES['audio_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['mp3', 'wav', 'ogg'], true)) {
            $this->redirect('admin/jingles', ['error' => 'Allowed formats: MP3, WAV, OGG.']);
        }

        $title = trim((string) ($_POST['title'] ?? pathinfo($file['name'], PATHINFO_FILENAME)));
        $safeBasename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $title);
        $targetFilename = 'jingle_' . $safeBasename . '_' . time() . '.' . $ext;
        $targetPath = $this->storagePath . '/' . $targetFilename;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $meta = Id3TagReader::read($targetPath);
            Jingle::create([
                'title' => $title,
                'filename' => $targetFilename,
                'filepath' => $targetPath,
                'duration' => $meta['duration'] ?? 0,
                'enabled' => true,
            ]);

            try {
                $liq = new LiquidsoapService();
                $liq->syncPlaylistFiles();
            } catch (\Throwable $e) {}

            $this->redirect('admin/jingles', ['success' => 'Station Jingle uploaded successfully!']);
        }

        $this->redirect('admin/jingles', ['error' => 'Failed to store jingle.']);
    }

    public function toggle(string $id): void
    {
        $jingle = Jingle::findById($id);
        if ($jingle) {
            $newStatus = !($jingle['enabled'] ?? true);
            Jingle::update($id, ['enabled' => $newStatus]);
            try {
                $liq = new LiquidsoapService();
                $liq->syncPlaylistFiles();
            } catch (\Throwable $e) {}
        }
        $this->redirect('admin/jingles');
    }

    public function delete(string $id): void
    {
        $jingle = Jingle::findById($id);
        if ($jingle) {
            if (!empty($jingle['filepath']) && file_exists($jingle['filepath'])) {
                @unlink($jingle['filepath']);
            }
            Jingle::delete($id);
            try {
                $liq = new LiquidsoapService();
                $liq->syncPlaylistFiles();
            } catch (\Throwable $e) {}
        }
        $this->redirect('admin/jingles', ['success' => 'Jingle deleted.']);
    }
}
