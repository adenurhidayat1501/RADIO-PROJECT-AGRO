<?php

declare(strict_types=1);

use App\Services\Router;
use App\Middleware\ApiAuthMiddleware;
use App\Controllers\RadioApiController;
use App\Controllers\RadioControlApiController;

// ==========================================
// PUBLIC REST API ENDPOINTS
// ==========================================
Router::get('/api/radio/status', [RadioApiController::class, 'status']);
Router::get('/api/radio/now-playing', [RadioApiController::class, 'nowPlaying']);
Router::get('/api/radio/listeners', [RadioApiController::class, 'listeners']);
Router::get('/api/radio/next-song', [RadioApiController::class, 'nextSong']);
Router::get('/api/songs', [RadioApiController::class, 'songs']);
Router::post('/api/song-request', [RadioApiController::class, 'submitSongRequest']);
Router::get('/api/schedule', [RadioApiController::class, 'schedule']);

// ==========================================
// ADMIN CONTROL API ENDPOINTS (AUTHENTICATED)
// ==========================================
Router::post('/api/admin/radio/skip', [RadioControlApiController::class, 'skip'], [ApiAuthMiddleware::class]);
Router::post('/api/admin/radio/auto-dj', [RadioControlApiController::class, 'toggleAutoDj'], [ApiAuthMiddleware::class]);
Router::post('/api/admin/radio/live', [RadioControlApiController::class, 'toggleLiveMode'], [ApiAuthMiddleware::class]);

// ==========================================
// LIQUIDSOAP LOCAL DAEMON EVENT HOOKS
// ==========================================
Router::get('/api/internal/liquidsoap/on-track', [RadioControlApiController::class, 'onTrack']);
Router::post('/api/internal/liquidsoap/on-track', [RadioControlApiController::class, 'onTrack']);
Router::get('/api/internal/liquidsoap/on-live-connect', [RadioControlApiController::class, 'onLiveConnect']);
Router::get('/api/internal/liquidsoap/on-live-disconnect', [RadioControlApiController::class, 'onLiveDisconnect']);
