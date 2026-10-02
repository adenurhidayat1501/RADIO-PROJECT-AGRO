#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Services\Database;
use App\Models\User;
use App\Models\Station;

echo "=== INITIALIZING MONGODB INDEXES & BASE DATA ===\n";

try {
    $db = Database::getDatabase();

    // 1. users
    echo "Creating indexes for 'users'...\n";
    $db->selectCollection('users')->createIndexes([
        ['key' => ['username' => 1], 'unique' => true, 'name' => 'idx_users_username'],
        ['key' => ['email' => 1], 'unique' => true, 'sparse' => true, 'name' => 'idx_users_email'],
        ['key' => ['role' => 1], 'name' => 'idx_users_role'],
        ['key' => ['status' => 1], 'name' => 'idx_users_status'],
    ]);

    // 2. dj_accounts
    echo "Creating indexes for 'dj_accounts'...\n";
    $db->selectCollection('dj_accounts')->createIndexes([
        ['key' => ['icecast_username' => 1], 'unique' => true, 'name' => 'idx_dj_username'],
        ['key' => ['user_id' => 1], 'name' => 'idx_dj_user_id'],
        ['key' => ['status' => 1], 'name' => 'idx_dj_status'],
    ]);

    // 3. songs
    echo "Creating indexes for 'songs'...\n";
    $db->selectCollection('songs')->createIndexes([
        ['key' => ['title' => 1], 'name' => 'idx_songs_title'],
        ['key' => ['artist' => 1], 'name' => 'idx_songs_artist'],
        ['key' => ['genre' => 1], 'name' => 'idx_songs_genre'],
        ['key' => ['enabled' => 1], 'name' => 'idx_songs_enabled'],
        ['key' => ['play_count' => -1], 'name' => 'idx_songs_play_count'],
    ]);

    // 4. playlists & playlist_items
    echo "Creating indexes for 'playlists' and 'playlist_items'...\n";
    $db->selectCollection('playlists')->createIndexes([
        ['key' => ['status' => 1], 'name' => 'idx_playlists_status'],
    ]);
    $db->selectCollection('playlist_items')->createIndexes([
        ['key' => ['playlist_id' => 1, 'position' => 1], 'name' => 'idx_playlist_pos'],
        ['key' => ['song_id' => 1], 'name' => 'idx_playlist_song'],
    ]);

    // 5. schedules & programs
    echo "Creating indexes for 'schedules'...\n";
    $db->selectCollection('schedules')->createIndexes([
        ['key' => ['day' => 1, 'start_time' => 1], 'name' => 'idx_schedules_day_time'],
        ['key' => ['enabled' => 1], 'name' => 'idx_schedules_enabled'],
    ]);

    // 6. song_requests
    echo "Creating indexes for 'song_requests'...\n";
    $db->selectCollection('song_requests')->createIndexes([
        ['key' => ['status' => 1], 'name' => 'idx_requests_status'],
        ['key' => ['created_at' => -1], 'name' => 'idx_requests_created'],
    ]);

    // 7. stream_stats
    echo "Creating indexes for 'stream_stats'...\n";
    $db->selectCollection('stream_stats')->createIndexes([
        ['key' => ['timestamp' => -1], 'name' => 'idx_stats_timestamp'],
    ]);

    // 8. listener_sessions
    echo "Creating indexes for 'listener_sessions'...\n";
    $db->selectCollection('listener_sessions')->createIndexes([
        ['key' => ['connected_at' => -1], 'name' => 'idx_sessions_connected'],
        ['key' => ['ip_hash' => 1], 'name' => 'idx_sessions_ip_hash'],
    ]);

    // 9. radio_events
    echo "Creating indexes for 'radio_events'...\n";
    $db->selectCollection('radio_events')->createIndexes([
        ['key' => ['timestamp' => -1], 'name' => 'idx_events_timestamp'],
        ['key' => ['type' => 1], 'name' => 'idx_events_type'],
    ]);

    // 10. settings
    echo "Creating indexes for 'settings'...\n";
    $db->selectCollection('settings')->createIndexes([
        ['key' => ['key' => 1], 'unique' => true, 'name' => 'idx_settings_key'],
    ]);

    // Seed default station if not exists
    Station::getPrimary();

    echo ">>> All MongoDB indexes and default station initialized successfully!\n";

} catch (\Throwable $e) {
    echo "ERROR during MongoDB initialization: " . $e->getMessage() . "\n";
    exit(1);
}
