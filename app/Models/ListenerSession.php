<?php

declare(strict_types=1);

namespace App\Models;

use MongoDB\BSON\UTCDateTime;

class ListenerSession extends BaseModel
{
    protected static string $collection = 'listener_sessions';

    public static function logSession(string $ip, string $userAgent, string $mountpoint, int $duration = 0): array
    {
        // Anonymize IP by hashing with salt
        $ipHash = hash('sha256', $ip . config('app.secret'));

        return static::create([
            'ip_hash' => substr($ipHash, 0, 16),
            'user_agent' => substr(htmlspecialchars($userAgent, ENT_QUOTES, 'UTF-8'), 0, 255),
            'mountpoint' => $mountpoint,
            'duration' => $duration,
            'connected_at' => new UTCDateTime(),
        ]);
    }
}
