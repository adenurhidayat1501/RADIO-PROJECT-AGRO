<?php require __DIR__ . '/partials/header.php'; ?>
<?php require __DIR__ . '/partials/sidebar.php'; ?>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0 fw-bold">Radio Broadcast Dashboard</h3>
                    <p class="text-muted small mb-0">Real-time automation telemetry & streaming monitor</p>
                </div>
                <div class="col-sm-6 text-sm-end mt-2 mt-sm-0">
                    <button id="btn-skip-track" class="btn btn-outline-danger btn-sm me-2 shadow-sm">
                        <i class="bi bi-skip-forward-fill me-1"></i> Skip Song
                    </button>
                    <button id="btn-restart-autodj" class="btn btn-outline-primary btn-sm shadow-sm">
                        <i class="bi bi-arrow-repeat me-1"></i> Reload Auto DJ
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <?php if ($succ = flash('success')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> <?= e($succ) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- BROADCAST TELEMETRY METRIC TILES -->
            <div class="row g-3 mb-4">
                <!-- Status & Mode -->
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card h-100 shadow-sm border-0 bg-body">
                        <div class="card-body d-flex align-items-center">
                            <div class="stat-card-icon bg-<?= $status['online'] ? 'success' : 'danger' ?> bg-opacity-10 text-<?= $status['online'] ? 'success' : 'danger' ?> me-3">
                                <i class="bi bi-broadcast"></i>
                            </div>
                            <div>
                                <span class="text-muted small text-uppercase fw-semibold">Radio Status</span>
                                <h4 class="mb-0 fw-bold">
                                    <span class="badge bg-<?= $status['online'] ? 'success' : 'danger' ?> pulse-dot">
                                        ● <?= $status['online'] ? 'ONLINE' : 'OFFLINE' ?>
                                    </span>
                                </h4>
                                <small class="text-muted">Mode: <strong><?= strtoupper($nowPlaying['source'] ?? 'AUTO_DJ') ?></strong></small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Current Listeners -->
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card h-100 shadow-sm border-0 bg-body">
                        <div class="card-body d-flex align-items-center">
                            <div class="stat-card-icon bg-primary bg-opacity-10 text-primary me-3">
                                <i class="bi bi-headphones"></i>
                            </div>
                            <div>
                                <span class="text-muted small text-uppercase fw-semibold">Current Listeners</span>
                                <h3 class="mb-0 fw-bold" id="dash-listener-count"><?= number_format($status['listeners'] ?? 0) ?></h3>
                                <small class="text-muted">Peak: <strong id="dash-peak-count"><?= number_format($status['peak'] ?? 0) ?></strong> listeners</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bitrate & Format -->
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card h-100 shadow-sm border-0 bg-body">
                        <div class="card-body d-flex align-items-center">
                            <div class="stat-card-icon bg-info bg-opacity-10 text-info me-3">
                                <i class="bi bi-soundwave"></i>
                            </div>
                            <div>
                                <span class="text-muted small text-uppercase fw-semibold">Audio Quality</span>
                                <h3 class="mb-0 fw-bold"><?= $status['bitrate'] ?? 128 ?> <span class="fs-6 text-muted font-normal">kbps</span></h3>
                                <small class="text-muted">MP3 Stereo 44.1 kHz</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Music Library Stats -->
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card h-100 shadow-sm border-0 bg-body">
                        <div class="card-body d-flex align-items-center">
                            <div class="stat-card-icon bg-warning bg-opacity-10 text-warning me-3">
                                <i class="bi bi-disc"></i>
                            </div>
                            <div>
                                <span class="text-muted small text-uppercase fw-semibold">Active Media</span>
                                <h3 class="mb-0 fw-bold"><?= number_format($songCount) ?> <span class="fs-6 text-muted font-normal">tracks</span></h3>
                                <small class="text-muted"><?= $playlistCount ?> playlists active</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- NOW PLAYING & BROADCAST MONITOR ROW -->
            <div class="row g-3 mb-4">
                <div class="col-lg-7">
                    <div class="card shadow-sm border-0 bg-body h-100">
                        <div class="card-header bg-transparent border-0 pb-0 d-flex justify-content-between align-items-center">
                            <h5 class="card-title fw-bold mb-0"><i class="bi bi-play-circle text-primary me-2"></i> On-Air Broadcast Console</h5>
                            <span class="badge bg-secondary">Mount: <?= e($status['mount']) ?></span>
                        </div>
                        <div class="card-body">
                            <div class="p-3 bg-body-tertiary rounded-3 mb-3 border">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <div class="p-3 rounded-3 bg-primary text-white text-center shadow-sm" style="width:70px;height:70px;">
                                            <i class="bi bi-vinyl fs-1"></i>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="small text-muted text-uppercase fw-bold tracking-wider">Now Playing</div>
                                        <h4 class="fw-bold mb-1 text-truncate" id="dash-now-title"><?= e($nowPlaying['title'] ?? 'Broadcast Active') ?></h4>
                                        <div class="text-muted" id="dash-now-artist"><i class="bi bi-person me-1"></i> <?= e($nowPlaying['artist'] ?? 'Radio Agro') ?></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Up Next Preview -->
                            <div class="d-flex align-items-center justify-content-between p-2 px-3 bg-body-secondary rounded-2 border">
                                <span class="small text-muted text-uppercase fw-semibold"><i class="bi bi-fast-forward me-1"></i> Up Next:</span>
                                <span class="fw-medium text-truncate ms-2" id="dash-next-track">
                                    <?= $nextSong ? e("{$nextSong['artist']} - {$nextSong['title']}") : 'Auto DJ Smart Rotation' ?>
                                </span>
                            </div>

                            <!-- Stream Player Controls inside Dashboard -->
                            <div class="mt-4 pt-2 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <button id="dash-play-toggle" class="btn btn-primary btn-sm px-3 rounded-pill">
                                        <i class="bi bi-play-fill me-1"></i> Test Stream Audio
                                    </button>
                                    <a href="<?= base_url('admin/stream') ?>" class="btn btn-outline-secondary btn-sm rounded-pill">
                                        <i class="bi bi-link-45deg me-1"></i> Stream Links
                                    </a>
                                </div>
                                <div class="text-muted small">
                                    Icecast Server: <strong><?= e(config('radio.icecast.host')) ?>:<?= e(config('radio.icecast.port')) ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PENDING REQUESTS & QUICK AUDIT -->
                <div class="col-lg-5">
                    <div class="card shadow-sm border-0 bg-body h-100">
                        <div class="card-header bg-transparent border-0 pb-0 d-flex justify-content-between align-items-center">
                            <h5 class="card-title fw-bold mb-0"><i class="bi bi-chat-heart text-danger me-2"></i> Song Requests & Moderation</h5>
                            <a href="<?= base_url('admin/requests') ?>" class="small text-decoration-none">View All (<?= $pendingRequests ?>)</a>
                        </div>
                        <div class="card-body">
                            <?php if ($pendingRequests > 0): ?>
                                <div class="alert alert-warning py-2 small d-flex align-items-center">
                                    <i class="bi bi-bell-fill me-2"></i> You have <strong><?= $pendingRequests ?></strong> pending song requests waiting for DJ moderation.
                                </div>
                            <?php else: ?>
                                <div class="text-muted text-center py-4">
                                    <i class="bi bi-check-circle fs-2 text-success"></i>
                                    <p class="mt-2 mb-0 small">No pending song requests. Everything is clean!</p>
                                </div>
                            <?php endif; ?>

                            <div class="mt-3">
                                <h6 class="fw-bold small text-uppercase text-muted">Recent Broadcast Events</h6>
                                <ul class="list-group list-group-flush small">
                                    <?php if (empty($events)): ?>
                                        <li class="list-group-item text-muted">No recent broadcast events recorded.</li>
                                    <?php else: ?>
                                        <?php foreach (array_slice($events, 0, 4) as $ev): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                <div>
                                                    <span class="badge bg-secondary-subtle text-secondary me-1"><?= e($ev['type']) ?></span>
                                                    <span><?= e($ev['source'] ?? 'system') ?></span>
                                                </div>
                                                <span class="text-muted font-monospace"><?= e($ev['timestamp'] ?? '') ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- LISTENER CHARTS ROW (Chart.js) -->
            <div class="card shadow-sm border-0 bg-body mb-4">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="card-title fw-bold mb-0"><i class="bi bi-bar-chart-line text-primary me-2"></i> Listener Statistics Trends</h5>
                        <p class="text-muted small mb-0">Historical concurrent connections & listener activity</p>
                    </div>
                    <div class="btn-group btn-group-sm" role="group">
                        <a href="<?= base_url('admin?period=today') ?>" class="btn btn-outline-primary <?= $period === 'today' ? 'active' : '' ?>">Today</a>
                        <a href="<?= base_url('admin?period=week') ?>" class="btn btn-outline-primary <?= $period === 'week' ? 'active' : '' ?>">This Week</a>
                        <a href="<?= base_url('admin?period=month') ?>" class="btn btn-outline-primary <?= $period === 'month' ? 'active' : '' ?>">This Month</a>
                    </div>
                </div>
                <div class="card-body">
                    <div style="height: 320px;">
                        <canvas id="listenerChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Chart.js Render
    const ctx = document.getElementById('listenerChart').getContext('2d');
    const chartLabels = <?= json_encode($chartData['labels'] ?? []) ?>;
    const chartListeners = <?= json_encode($chartData['listeners'] ?? []) ?>;
    const chartPeaks = <?= json_encode($chartData['peaks'] ?? []) ?>;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartLabels.length ? chartLabels : ['00:00', '04:00', '08:00', '12:00', '16:00', '20:00'],
            datasets: [
                {
                    label: 'Active Listeners',
                    data: chartListeners.length ? chartListeners : [0, 0, 0, 0, 0, 0],
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2,
                    pointRadius: 3,
                },
                {
                    label: 'Peak Connections',
                    data: chartPeaks.length ? chartPeaks : [0, 0, 0, 0, 0, 0],
                    borderColor: '#10b981',
                    borderDash: [5, 5],
                    borderWidth: 2,
                    pointRadius: 0,
                    fill: false,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' },
                tooltip: { mode: 'index', intersect: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 }
                }
            }
        }
    });

    // 2. Dash Audio Player
    const dashPlayBtn = document.getElementById('dash-play-toggle');
    const globalAudio = document.getElementById('global-radio-audio');

    if (dashPlayBtn && globalAudio) {
        dashPlayBtn.addEventListener('click', function () {
            if (globalAudio.paused) {
                globalAudio.src = '<?= e($streamUrl) ?>?t=' + Date.now();
                globalAudio.play();
                dashPlayBtn.innerHTML = '<i class="bi bi-stop-fill me-1"></i> Stop Preview';
                dashPlayBtn.classList.replace('btn-primary', 'btn-danger');
            } else {
                globalAudio.pause();
                globalAudio.src = '';
                dashPlayBtn.innerHTML = '<i class="bi bi-play-fill me-1"></i> Test Stream Audio';
                dashPlayBtn.classList.replace('btn-danger', 'btn-primary');
            }
        });
    }

    // 3. Skip Button Action
    const skipBtn = document.getElementById('btn-skip-track');
    if (skipBtn) {
        skipBtn.addEventListener('click', function () {
            if (!confirm('Are you sure you want to skip the currently playing track?')) return;
            skipBtn.disabled = true;

            fetch('<?= base_url('api/admin/radio/skip') ?>', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message || 'Skip command executed.');
                location.reload();
            })
            .catch(err => {
                alert('Communication error: ' + err);
                skipBtn.disabled = false;
            });
        });
    }

    // 4. Restart AutoDJ Action
    const restartBtn = document.getElementById('btn-restart-autodj');
    if (restartBtn) {
        restartBtn.addEventListener('click', function () {
            restartBtn.disabled = true;
            fetch('<?= base_url('api/admin/radio/auto-dj') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=restart'
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message || 'Auto DJ reloaded.');
                location.reload();
            })
            .catch(err => {
                alert('Error: ' + err);
                restartBtn.disabled = false;
            });
        });
    }
});
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
