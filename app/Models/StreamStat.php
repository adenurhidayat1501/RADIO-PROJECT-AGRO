<?php

declare(strict_types=1);

namespace App\Models;

use MongoDB\BSON\UTCDateTime;

class StreamStat extends BaseModel
{
    protected static string $collection = 'stream_stats';

    public static function record(int $listeners, int $connections, int $peak): array
    {
        return static::create([
            'timestamp' => new UTCDateTime(),
            'listeners' => $listeners,
            'connections' => $connections,
            'peak' => $peak,
        ]);
    }

    /**
     * Get chart points for today, this week, or this month
     */
    public static function getTimeline(string $period = 'today'): array
    {
        $timezone = new \DateTimeZone(config('app.timezone', 'Asia/Jakarta'));
        $now = new \DateTime('now', $timezone);

        $startDate = match ($period) {
            'month' => (clone $now)->modify('-30 days')->setTime(0, 0, 0),
            'week' => (clone $now)->modify('-7 days')->setTime(0, 0, 0),
            default => (clone $now)->setTime(0, 0, 0), // Today from midnight
        };

        $startUtc = new UTCDateTime($startDate->getTimestamp() * 1000);

        $cursor = static::getCollection()->find(
            ['timestamp' => ['$gte' => $startUtc]],
            ['sort' => ['timestamp' => 1], 'limit' => 500]
        );

        $labels = [];
        $listeners = [];
        $peaks = [];

        foreach ($cursor as $doc) {
            $raw = (array) $doc;
            if (isset($raw['timestamp']) && $raw['timestamp'] instanceof UTCDateTime) {
                $dt = $raw['timestamp']->toDateTime()->setTimezone($timezone);
                $label = ($period === 'today') ? $dt->format('H:i') : $dt->format('d M H:i');
                $labels[] = $label;
                $listeners[] = (int) ($raw['listeners'] ?? 0);
                $peaks[] = (int) ($raw['peak'] ?? 0);
            }
        }

        return [
            'labels' => $labels,
            'listeners' => $listeners,
            'peaks' => $peaks,
        ];
    }
}
