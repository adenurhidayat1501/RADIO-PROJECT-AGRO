<?php

declare(strict_types=1);

namespace App\Models;

class ActivityLog extends BaseModel
{
    protected static string $collection = 'activity_logs';

    public static function getRecent(int $limit = 50): array
    {
        return static::find([], [
            'sort' => ['created_at' => -1],
            'limit' => $limit,
        ]);
    }
}
