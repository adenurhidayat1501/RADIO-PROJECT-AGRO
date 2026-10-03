<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Logger;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

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

        // mongodump command excluding heavy audio binary files
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

            self::deleteDirectory($tempDumpDir);

            if (file_exists($archivePath) && filesize($archivePath) > 0) {
                Logger::info("Backup created successfully: {$filename}");

                return [
                    'success' => true,
                    'filename' => $filename,
                    'path' => $archivePath,
                    'size' => filesize($archivePath),
                    'created_at' => date('Y-m-d H:i:s'),
                ];
            }
        }

        // Fallback: JSON dumps of collections
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
                $docs = [];
                foreach ($cursor as $doc) {
                    $docs[] = self::encodeDocForExport((array) $doc);
                }
                $export[$col] = $docs;
            }

            $jsonContent = json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
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

        // Check if archive is .tar.gz or ends with .gz / .tar
        if (str_ends_with($filePath, '.tar.gz') || str_ends_with($filePath, '.gz') || str_ends_with($filePath, '.tar')) {
            $tempDir = self::$backupDir . '/restore_temp_' . time();
            @mkdir($tempDir, 0755, true);
            shell_exec(sprintf('tar -xzf %s -C %s 2>&1', escapeshellarg($filePath), escapeshellarg($tempDir)));

            // Check if dump was nested in dbName subfolder or root
            $restoreSource = is_dir($tempDir . '/' . $dbName) ? ($tempDir . '/' . $dbName) : $tempDir;

            $cmd = sprintf(
                'mongorestore --uri=%s --db=%s --drop %s 2>&1',
                escapeshellarg($mongoUri),
                escapeshellarg($dbName),
                escapeshellarg($restoreSource)
            );
            $output = shell_exec($cmd);
            self::deleteDirectory($tempDir);

            Logger::info("Database restored from archive: {$filePath}");
            return true;
        }

        // Check if JSON backup
        if (str_ends_with($filePath, '.json')) {
            $content = file_get_contents($filePath);
            $data = json_decode($content, true);
            if (!is_array($data)) return false;

            $db = Database::getDatabase();
            foreach ($data as $col => $docs) {
                if (!empty($docs) && is_array($docs)) {
                    $collection = $db->selectCollection($col);
                    $collection->drop();
                    $preparedDocs = [];
                    foreach ($docs as $doc) {
                        $preparedDocs[] = self::decodeDocForImport((array) $doc);
                    }
                    if (!empty($preparedDocs)) {
                        $collection->insertMany($preparedDocs);
                    }
                }
            }

            Logger::info("Database restored from JSON backup: {$filePath}");
            return true;
        }

        return false;
    }

    private static function encodeDocForExport(array $doc): array
    {
        foreach ($doc as $k => $v) {
            if ($v instanceof ObjectId) {
                $doc[$k] = ['$oid' => (string) $v];
            } elseif ($v instanceof UTCDateTime) {
                $doc[$k] = ['$date' => (string) $v];
            } elseif (is_array($v)) {
                $doc[$k] = self::encodeDocForExport($v);
            }
        }
        return $doc;
    }

    private static function decodeDocForImport(array $doc): array
    {
        foreach ($doc as $k => $v) {
            if (is_array($v)) {
                if (isset($v['$oid']) && is_string($v['$oid']) && strlen($v['$oid']) === 24) {
                    $doc[$k] = new ObjectId($v['$oid']);
                } elseif (isset($v['$date'])) {
                    $doc[$k] = new UTCDateTime(is_numeric($v['$date']) ? (int) $v['$date'] : (int)(strtotime((string)$v['$date']) * 1000));
                } else {
                    $doc[$k] = self::decodeDocForImport($v);
                }
            }
        }
        return $doc;
    }

    private static function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;
        $items = scandir($dir);
        if ($items === false) return;
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                self::deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
