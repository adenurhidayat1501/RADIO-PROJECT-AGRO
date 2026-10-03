<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Database;
use MongoDB\BSON\UTCDateTime;

class SongRequest extends BaseModel
{
    protected static string $collection = 'song_requests';

    public static function getPending(): array
    {
        return static::find(['status' => 'pending'], ['sort' => ['created_at' => -1]]);
    }

    public static function createRequest(string $name, string $songId, string $message = ''): array
    {
        return static::create([
            'name' => trim($name),
            'song_id' => Database::toObjectId($songId),
            'message' => trim($message),
            'status' => 'pending',
            'played_at' => null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        ]);
    }

    public static function markPlayed(string $id): bool
    {
        return static::update($id, [
            'status' => 'played',
            'played_at' => new UTCDateTime(),
        ]);
    }

    public static function getRecentPublic(int $limit = 10): array
    {
        $requests = static::find(
            ['status' => ['$in' => ['approved', 'played']]],
            ['sort' => ['created_at' => -1], 'limit' => $limit]
        );

        foreach ($requests as &$req) {
            if (!empty($req['song_id'])) {
                $song = Song::findById($req['song_id']);
                $req['song'] = $song;
            }
        }

        return $requests;
    }
}
