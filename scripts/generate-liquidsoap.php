<?php

declare(strict_types=1);

require_once __DIR__ . '/../public/index.php';

use App\Services\LiquidsoapService;
use App\Helpers\Logger;

echo "=== REGENERATING LIQUIDSOAP CONFIGURATION & PLAYLISTS ===\n";

try {
    $service = new LiquidsoapService();
    $playlists = $service->syncPlaylistFiles();
    echo "Playlists synced: " . count($playlists) . " files written.\n";

    $config = $service->generateConfig();
    echo "Liquidsoap configuration generated successfully at " . config('radio.paths.generated_config') . "\n";
    Logger::info('Liquidsoap configuration regenerated via CLI script');
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    Logger::error('Failed to regenerate Liquidsoap config: ' . $e->getMessage());
    exit(1);
}
