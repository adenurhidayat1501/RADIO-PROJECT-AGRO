<?php

declare(strict_types=1);

namespace App\Helpers;

class Logger
{
    private static string $logDir = __DIR__ . '/../../storage/logs';

    public static function log(string $level, string $message, array $context = [], string $channel = 'app'): void
    {
        if (!is_dir(self::$logDir)) {
            @mkdir(self::$logDir, 0775, true);
        }

        $filename = self::$logDir . '/' . preg_replace('/[^a-z0-9_-]/i', '', $channel) . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';
        $line = sprintf("[%s] [%s] %s%s\n", $timestamp, strtoupper($level), $message, $contextStr);

        @file_put_contents($filename, $line, FILE_APPEND | LOCK_EX);
        @chmod($filename, 0664);
    }

    public static function info(string $message, array $context = [], string $channel = 'app'): void
    {
        self::log('INFO', $message, $context, $channel);
    }

    public static function warning(string $message, array $context = [], string $channel = 'app'): void
    {
        self::log('WARNING', $message, $context, $channel);
    }

    public static function error(string $message, array $context = [], string $channel = 'app'): void
    {
        self::log('ERROR', $message, $context, $channel);
    }

    public static function getLines(string $channel = 'app', int $lines = 100): array
    {
        $filename = self::$logDir . '/' . preg_replace('/[^a-z0-9_-]/i', '', $channel) . '.log';
        if (!file_exists($filename)) {
            return [];
        }

        $content = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($content === false) {
            return [];
        }

        return array_slice($content, -$lines);
    }
}
