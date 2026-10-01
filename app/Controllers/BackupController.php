<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\BackupService;

class BackupController extends BaseController
{
    public function index(): void
    {
        $backups = BackupService::listBackups();
        $this->view('admin.backup.index', ['backups' => $backups]);
    }

    public function create(): void
    {
        $res = BackupService::createBackup();
        if ($res['success']) {
            log_activity('create_backup', ['file' => $res['filename']]);
            $this->redirect('admin/backup', ['success' => "Backup archive created: {$res['filename']} (" . format_bytes($res['size']) . ")"]);
        }

        $this->redirect('admin/backup', ['error' => 'Failed to create backup: ' . ($res['error'] ?? 'Unknown error')]);
    }

    public function download(string $filename): void
    {
        $safeFilename = basename($filename);
        $filePath = __DIR__ . '/../../storage/backups/' . $safeFilename;

        if (!file_exists($filePath)) {
            $this->redirect('admin/backup', ['error' => 'Backup file not found.']);
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }

    public function restore(): void
    {
        if (empty($_FILES['backup_file'])) {
            $this->redirect('admin/backup', ['error' => 'No backup archive provided for restore.']);
        }

        $file = $_FILES['backup_file'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $tempPath = __DIR__ . '/../../storage/backups/temp_restore_' . time() . '.' . $ext;

        if (move_uploaded_file($file['tmp_name'], $tempPath)) {
            $success = BackupService::restoreBackup($tempPath);
            @unlink($tempPath);

            if ($success) {
                log_activity('restore_backup', ['filename' => $file['name']]);
                $this->redirect('admin/backup', ['success' => 'Database successfully restored from backup!']);
            }
        }

        $this->redirect('admin/backup', ['error' => 'Restore failed. Please verify the archive format.']);
    }

    public function delete(string $filename): void
    {
        $safeFilename = basename($filename);
        $filePath = __DIR__ . '/../../storage/backups/' . $safeFilename;

        if (file_exists($filePath)) {
            @unlink($filePath);
            $this->redirect('admin/backup', ['success' => 'Backup file deleted.']);
        }

        $this->redirect('admin/backup', ['error' => 'File not found.']);
    }
}
