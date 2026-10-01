<?php

declare(strict_types=1);

namespace App\Models;

use MongoDB\BSON\UTCDateTime;

class NowPlaying extends BaseModel
{
    protected static string $collection = 'now_playing';

    public static function getCurrent(): array
    {
        $doc = static::getCollection()->findOne(['_id' => 'current']);
        if (!$doc) {
            $default = [
                '_id' => 'current',
                'song_id' => null,
                'title' => 'Radio Agro Broadcast',
                'artist' => 'Radio Agro',
                'album' => 'Live On Air',
                'duration' => 0,
                'started_at' => new UTCDateTime(),
                'source' => 'auto_dj',
                'dj' => null,
                'updated_at' => new UTCDateTime(),
            ];
            static::getCollection()->insertOne($default);
            return static::normalizeDoc($default);
        }

        return static::normalizeDoc((array) $doc);
    }

    public static function setCurrent(array $data): void
    {
        $data['updated_at'] = new UTCDateTime();
        if (!isset($data['started_at'])) {
            $data['started_at'] = new UTCDateTime();
        }

        static::getCollection()->updateOne(
            ['_id' => 'current'],
            ['$set' => $data],
            ['upsert' => true]
        );
    }
}
