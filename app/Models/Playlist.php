<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Database;

class Playlist extends BaseModel
{
    protected static string $collection = 'playlists';

    public static function getActivePlaylists(): array
    {
        return static::find(['status' => 'active'], ['sort' => ['name' => 1]]);
    }

    /**
     * Get all populated song items for this playlist
     */
    public static function getSongs(string $playlistId): array
    {
        if (!Database::isValidObjectId($playlistId)) {
            return [];
        }

        $items = PlaylistItem::findByPlaylistId($playlistId);
        $songs = [];

        foreach ($items as $item) {
            if (empty($item['song_id'])) continue;
            $song = Song::findById($item['song_id']);
            if ($song && ($song['enabled'] ?? true)) {
                $song['playlist_item_id'] = $item['_id'];
                $song['position'] = $item['position'];
                $song['item_enabled'] = $item['enabled'] ?? true;
                $songs[] = $song;
            }
        }

        return $songs;
    }
}
