<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Logger;

class BackupService
{
    private static string $backupDir = __DIR__ . '/../../storage/backups';

    public static function createBackup(): array
    {
        if (!is_dir(self::$backupDir)) {
            @mkdir(self::$backupDir, 0755, true);
        }

        $timestamp = date('Ymd_His');
        $filename = "backup_radio_{$timestamp}.tar.gz";
        $archivePath = self::$backupDir . '/' . $filename;
        $tempDumpDir = self::$backupDir . "/dump_{$timestamp}";

        $dbName = config('database.database', 'radio_platform');
        $mongoUri = config('database.uri', 'mongodb://127.0.0.1:27017');

        // mongodump command excluding heavy binary data (songs filepaths are saved, but audio files are not cloned)
        $dumpCmd = sprintf(
            'mongodump --uri=%s --db=%s --out=%s 2>&1',
            escapeshellarg($mongoUri),
            escapeshellarg($dbName),
            escapeshellarg($tempDumpDir)
        );

        $dumpOutput = @shell_exec($dumpCmd);

        // If mongodump succeeded or if temp folder was created, archive it
        if (is_dir($tempDumpDir)) {
            $tarCmd = sprintf(
                'tar -czf %s -C %s . 2>&1',
                escapeshellarg($archivePath),
                escapeshellarg($tempDumpDir)
            );
            @shell_exec($tarCmd);

            // Clean up temp dir
            @shell_exec('rm -rf ' . escapeshellarg($tempDumpDir));

            Logger::info("Backup created successfully: {$filename}");

            return [
                'success' => true,
                'filename' => $filename,
                'path' => $archivePath,
                'size' => file_exists($archivePath) ? filesize($archivePath) : 0,
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }

        // Fallback for Windows development or if mongodump CLI is not installed: JSON dumps of collections
        return self::createJsonFallbackBackup($filename, $archivePath);
    }

    private static function createJsonFallbackBackup(string $filename, string $archivePath): array
    {
        try {
            $db = Database::getDatabase();
            $collections = [
                'users', 'dj_accounts', 'stations', 'songs', 'playlists',
                'playlist_items', 'schedules', 'programs', 'jingles',
                'advertisements', 'settings'
            ];

            $export = [];
            foreach ($collections as $col) {
                $cursor = $db->selectCollection($col)->find();
                $export[$col] = iterator_to_array($cursor);
            }

            $jsonContent = json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $jsonFile = str_replace('.tar.gz', '.json', $archivePath);
            file_put_contents($jsonFile, $jsonContent);

            return [
                'success' => true,
                'filename' => basename($jsonFile),
                'path' => $jsonFile,
                'size' => filesize($jsonFile),
                'created_at' => date('Y-m-d H:i:s'),
            ];
        } catch (\Throwable $e) {
            Logger::error("Fallback backup failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public static function listBackups(): array
    {
        if (!is_dir(self::$backupDir)) {
            return [];
        }

        $files = scandir(self::$backupDir, SCANDIR_SORT_DESCENDING);
        $backups = [];

        foreach ($files as $f) {
            if ($f === '.' || $f === '..' || $f === '.gitkeep') continue;
            $fullPath = self::$backupDir . '/' . $f;
            if (is_file($fullPath)) {
                $backups[] = [
                    'filename' => $f,
                    'size' => filesize($fullPath),
                    'created_at' => date('Y-m-d H:i:s', filemtime($fullPath)),
                ];
            }
        }

        return $backups;
    }

    public static function restoreBackup(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $dbName = config('database.database', 'radio_platform');
        $mongoUri = config('database.uri', 'mongodb://127.0.0.1:27017');

        if (str_ends_with($filePath, '.tar.gz')) {
            $tempDir = self::$backupDir . '/restore_temp';
            @mkdir($tempDir, 0755, true);
            shell_exec(sprintf('tar -xzf %s -C %s', escapeshellarg($filePath), escapeshellarg($tempDir)));

            $cmd = sprintf(
                'mongorestore --uri=%s --db=%s --drop %s/%s 2>&1',
                escapeshellarg($mongoUri),
                escapeshellarg($dbName),
                escapeshellarg($tempDir),
                escapeshellarg($dbName)
            );
            $output = shell_exec($cmd);
            shell_exec('rm -rf ' . escapeshellarg($tempDir));
            return true;
        }

        if (str_ends_with($filePath, '.json')) {
            $content = file_get_contents($filePath);
            $data = json_decode($content, true);
            if (!is_array($data)) return false;

            $db = Database::getDatabase();
            foreach ($data as $col => $docs) {
                if (!empty($docs)) {
                    $collection = $db->selectCollection($col);
                    $collection->drop();
                    $collection->insertMany($docs);
                }
            }
            return true;
        }

        return false;
    }
}
