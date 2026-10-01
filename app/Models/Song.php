<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Database;

class Song extends BaseModel
{
    protected static string $collection = 'songs';

    public static function search(string $query, int $limit = 50): array
    {
        $regex = new \MongoDB\BSON\Regex(preg_quote($query), 'i');
        return static::find([
            'enabled' => true,
            '$or' => [
                ['title' => $regex],
                ['artist' => $regex],
                ['album' => $regex],
                ['genre' => $regex],
            ]
        ], [
            'limit' => $limit,
            'sort' => ['title' => 1]
        ]);
    }

    public static function incrementPlayCount(string $id): void
    {
        if (!Database::isValidObjectId($id)) {
            return;
        }

        $objId = Database::toObjectId($id);
        static::getCollection()->updateOne(
            ['_id' => $objId],
            [
                '$inc' => ['play_count' => 1],
                '$set' => ['last_played_at' => new \MongoDB\BSON\UTCDateTime()]
            ]
        );
    }

    public static function getActiveSongs(): array
    {
        return static::find(['enabled' => true], ['sort' => ['artist' => 1, 'title' => 1]]);
    }
}
