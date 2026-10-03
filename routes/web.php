<?php

declare(strict_types=1);

use App\Services\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;

use App\Controllers\HomeController;
use App\Controllers\StreamController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\StationController;
use App\Controllers\StreamAdminController;
use App\Controllers\StudioController;
use App\Controllers\MusicLibraryController;
use App\Controllers\PlaylistController;
use App\Controllers\JingleController;
use App\Controllers\AdvertisementController;
use App\Controllers\ScheduleController;
use App\Controllers\ProgramController;
use App\Controllers\SongRequestController;
use App\Controllers\AnalyticsController;
use App\Controllers\DjAccountController;
use App\Controllers\UserController;
use App\Controllers\BackupController;
use App\Controllers\LogController;
use App\Controllers\SettingController;
use App\Controllers\DiagnosticController;

// ==========================================
// PUBLIC LISTENER PORTAL & PLAYLIST ROUTES
// ==========================================
Router::get('/', [HomeController::class, 'index']);
Router::get('/stream', [StreamController::class, 'direct']);
Router::get('/stream.m3u', [StreamController::class, 'm3u']);
Router::get('/stream.pls', [StreamController::class, 'pls']);

// ==========================================
// AUTHENTICATION ROUTES
// ==========================================
Router::get('/admin/login', [AuthController::class, 'showLoginForm']);
Router::post('/admin/login', [AuthController::class, 'login'], [CsrfMiddleware::class]);
Router::get('/admin/logout', [AuthController::class, 'logout']);
Router::post('/admin/logout', [AuthController::class, 'logout']);

// ==========================================
// ADMIN DASHBOARD & RADIO CONTROL (PROTECTED)
// ==========================================
$adminMw = [AuthMiddleware::class];
$adminPostMw = [AuthMiddleware::class, CsrfMiddleware::class];

Router::get('/admin', [DashboardController::class, 'index'], $adminMw);

// Radio identity & stream generator
Router::get('/admin/station', [StationController::class, 'index'], $adminMw);
Router::post('/admin/station', [StationController::class, 'update'], $adminPostMw);
Router::get('/admin/stream', [StreamAdminController::class, 'index'], $adminMw);

// Live Studio & broadcast monitoring
Router::get('/admin/studio', [StudioController::class, 'index'], $adminMw);

// Music Library
Router::get('/admin/music', [MusicLibraryController::class, 'index'], $adminMw);
Router::post('/admin/music/upload', [MusicLibraryController::class, 'upload'], $adminPostMw);
Router::get('/admin/music/{id}/edit', [MusicLibraryController::class, 'edit'], $adminMw);
Router::post('/admin/music/{id}/edit', [MusicLibraryController::class, 'update'], $adminPostMw);
Router::post('/admin/music/{id}/delete', [MusicLibraryController::class, 'delete'], $adminPostMw);

// Playlists
Router::get('/admin/playlists', [PlaylistController::class, 'index'], $adminMw);
Router::get('/admin/playlists/create', [PlaylistController::class, 'create'], $adminMw);
Router::post('/admin/playlists/create', [PlaylistController::class, 'store'], $adminPostMw);
Router::get('/admin/playlists/{id}/manage', [PlaylistController::class, 'manage'], $adminMw);
Router::post('/admin/playlists/{id}/add-song', [PlaylistController::class, 'addSong'], $adminPostMw);
Router::post('/admin/playlists/{id}/remove/{itemId}', [PlaylistController::class, 'removeSong'], $adminPostMw);
Router::post('/admin/playlists/{id}/reorder', [PlaylistController::class, 'reorder'], $adminPostMw);
Router::post('/admin/playlists/{id}/delete', [PlaylistController::class, 'delete'], $adminPostMw);

// Jingles & Ads
Router::get('/admin/jingles', [JingleController::class, 'index'], $adminMw);
Router::post('/admin/jingles/upload', [JingleController::class, 'upload'], $adminPostMw);
Router::post('/admin/jingles/{id}/toggle', [JingleController::class, 'toggle'], $adminPostMw);
Router::post('/admin/jingles/{id}/delete', [JingleController::class, 'delete'], $adminPostMw);

Router::get('/admin/advertisements', [AdvertisementController::class, 'index'], $adminMw);
Router::post('/admin/advertisements/upload', [AdvertisementController::class, 'upload'], $adminPostMw);
Router::post('/admin/advertisements/{id}/toggle', [AdvertisementController::class, 'toggle'], $adminPostMw);
Router::post('/admin/advertisements/{id}/delete', [AdvertisementController::class, 'delete'], $adminPostMw);

// Scheduling & Programs
Router::get('/admin/schedules', [ScheduleController::class, 'index'], $adminMw);
Router::post('/admin/schedules', [ScheduleController::class, 'store'], $adminPostMw);
Router::post('/admin/schedules/{id}/delete', [ScheduleController::class, 'delete'], $adminPostMw);

Router::get('/admin/programs', [ProgramController::class, 'index'], $adminMw);
Router::post('/admin/programs', [ProgramController::class, 'store'], $adminPostMw);
Router::post('/admin/programs/{id}/delete', [ProgramController::class, 'delete'], $adminPostMw);

// Song Requests Moderation
Router::get('/admin/requests', [SongRequestController::class, 'index'], $adminMw);
Router::post('/admin/requests/{id}/approve', [SongRequestController::class, 'approve'], $adminPostMw);
Router::post('/admin/requests/{id}/reject', [SongRequestController::class, 'reject'], $adminPostMw);
Router::post('/admin/requests/{id}/play-next', [SongRequestController::class, 'playNext'], $adminPostMw);
Router::post('/admin/requests/{id}/delete', [SongRequestController::class, 'delete'], $adminPostMw);

// Analytics
Router::get('/admin/analytics', [AnalyticsController::class, 'index'], $adminMw);

// DJ Management
Router::get('/admin/djs', [DjAccountController::class, 'index'], $adminMw);
Router::get('/admin/djs/create', [DjAccountController::class, 'create'], $adminMw);
Router::post('/admin/djs/create', [DjAccountController::class, 'store'], $adminPostMw);
Router::post('/admin/djs/{id}/toggle', [DjAccountController::class, 'toggle'], $adminPostMw);
Router::post('/admin/djs/{id}/delete', [DjAccountController::class, 'delete'], $adminPostMw);

// User Accounts & RBAC (Admin only)
Router::get('/admin/users', [UserController::class, 'index'], [function () {
    AuthMiddleware::handle('admin');
}]);
Router::get('/admin/users/create', [UserController::class, 'create'], [function () {
    AuthMiddleware::handle('admin');
}]);
Router::post('/admin/users/create', [UserController::class, 'store'], [CsrfMiddleware::class, function () {
    AuthMiddleware::handle('admin');
}]);
Router::get('/admin/users/{id}/edit', [UserController::class, 'edit'], [function () {
    AuthMiddleware::handle('admin');
}]);
Router::post('/admin/users/{id}/edit', [UserController::class, 'update'], [CsrfMiddleware::class, function () {
    AuthMiddleware::handle('admin');
}]);
Router::post('/admin/users/{id}/delete', [UserController::class, 'delete'], [CsrfMiddleware::class, function () {
    AuthMiddleware::handle('admin');
}]);

// Backup Management
Router::get('/admin/backup', [BackupController::class, 'index'], $adminMw);
Router::post('/admin/backup/create', [BackupController::class, 'create'], $adminPostMw);
Router::get('/admin/backup/download/{filename}', [BackupController::class, 'download'], $adminMw);
Router::post('/admin/backup/restore', [BackupController::class, 'restore'], $adminPostMw);
Router::post('/admin/backup/delete/{filename}', [BackupController::class, 'delete'], $adminPostMw);

// Diagnostic Logs & Health Checks
Router::get('/admin/logs', [LogController::class, 'index'], $adminMw);
Router::post('/admin/logs/clear/{channel}', [LogController::class, 'clear'], $adminPostMw);
Router::get('/admin/diagnostics', [DiagnosticController::class, 'index'], $adminMw);
Router::post('/admin/diagnostics/repair', [DiagnosticController::class, 'repair'], $adminPostMw);

// System Settings
Router::get('/admin/settings', [SettingController::class, 'index'], $adminMw);
Router::post('/admin/settings', [SettingController::class, 'update'], $adminPostMw);
Router::post('/admin/settings/reload-liquidsoap', [SettingController::class, 'reloadLiquidsoap'], $adminPostMw);
