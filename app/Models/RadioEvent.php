<?php

declare(strict_types=1);

namespace App\Models;

use MongoDB\BSON\UTCDateTime;

class RadioEvent extends BaseModel
{
    protected static string $collection = 'radio_events';

    public static function logEvent(string $type, string $source = 'system', array $metadata = []): array
    {
        return static::create([
            'type' => $type,
            'source' => $source,
            'metadata' => $metadata,
            'timestamp' => new UTCDateTime(),
        ]);
    }

    public static function getRecent(int $limit = 25): array
    {
        return static::find([], [
            'sort' => ['timestamp' => -1],
            'limit' => $limit,
        ]);
    }
}
