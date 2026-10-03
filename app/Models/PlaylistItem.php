<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Database;

class PlaylistItem extends BaseModel
{
    protected static string $collection = 'playlist_items';

    public static function findByPlaylistId(string $playlistId): array
    {
        if (!Database::isValidObjectId($playlistId)) {
            return [];
        }

        return static::find(
            ['playlist_id' => Database::toObjectId($playlistId)],
            ['sort' => ['position' => 1]]
        );
    }

    public static function addItem(string $playlistId, string $songId): array
    {
        $pId = Database::toObjectId($playlistId);
        $sId = Database::toObjectId($songId);

        // Get max position
        $highest = static::find(
            ['playlist_id' => $pId],
            ['sort' => ['position' => -1], 'limit' => 1]
        );
        $position = !empty($highest) ? ($highest[0]['position'] + 1) : 1;

        return static::create([
            'playlist_id' => $pId,
            'song_id' => $sId,
            'position' => $position,
            'enabled' => true,
        ]);
    }

    public static function reorder(string $playlistId, array $itemIds): bool
    {
        if (!Database::isValidObjectId($playlistId)) {
            return false;
        }

        $pos = 1;
        foreach ($itemIds as $id) {
            if (Database::isValidObjectId($id)) {
                static::update($id, ['position' => $pos++]);
            }
        }

        return true;
    }

    public static function deleteByPlaylistId(string $playlistId): int
    {
        if (!Database::isValidObjectId($playlistId)) {
            return 0;
        }

        $res = static::getCollection()->deleteMany([
            'playlist_id' => Database::toObjectId($playlistId)
        ]);

        return $res->getDeletedCount();
    }

    public static function deleteBySongId(string $songId): int
    {
        if (!Database::isValidObjectId($songId)) {
            return 0;
        }

        $res = static::getCollection()->deleteMany([
            'song_id' => Database::toObjectId($songId)
        ]);

        return $res->getDeletedCount();
    }
}
