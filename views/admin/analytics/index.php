<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/sidebar.php'; ?>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0 fw-bold">Listener Statistics & Analytics</h3>
            <p class="text-muted small">Aggregated metrics, peak connections, and listener session audits</p>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <!-- Summary Tiles -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 bg-body">
                        <div class="card-body">
                            <span class="text-muted small text-uppercase fw-semibold">Current Active Streams</span>
                            <h2 class="fw-bold mb-0 text-primary"><?= number_format($liveStatus['listeners'] ?? 0) ?></h2>
                            <small class="text-muted">Real-time live listeners connected</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 bg-body">
                        <div class="card-body">
                            <span class="text-muted small text-uppercase fw-semibold">All-Time Peak Listeners</span>
                            <h2 class="fw-bold mb-0 text-success"><?= number_format($liveStatus['peak'] ?? 0) ?></h2>
                            <small class="text-muted">Highest concurrent audience recorded</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 bg-body">
                        <div class="card-body">
                            <span class="text-muted small text-uppercase fw-semibold">Broadcast Bitrate</span>
                            <h2 class="fw-bold mb-0 text-info"><?= $liveStatus['bitrate'] ?? 128 ?> kbps</h2>
                            <small class="text-muted">High-Fidelity Audio Stream</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart Card -->
            <div class="card shadow-sm border-0 bg-body mb-4">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="fw-bold mb-0">Listener Traffic Breakdown</h5>
                    <div class="btn-group btn-group-sm">
                        <a href="<?= base_url('admin/analytics?period=today') ?>" class="btn btn-outline-primary <?= $period === 'today' ? 'active' : '' ?>">Today</a>
                        <a href="<?= base_url('admin/analytics?period=week') ?>" class="btn btn-outline-primary <?= $period === 'week' ? 'active' : '' ?>">This Week</a>
                        <a href="<?= base_url('admin/analytics?period=month') ?>" class="btn btn-outline-primary <?= $period === 'month' ? 'active' : '' ?>">This Month</a>
                    </div>
                </div>
                <div class="card-body">
                    <div style="height: 350px;">
                        <canvas id="analyticsChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Recent Sessions Audit -->
            <div class="card shadow-sm border-0 bg-body">
                <div class="card-header bg-transparent border-bottom fw-bold">
                    <i class="bi bi-shield-check text-success me-2"></i> Recent Listener Sessions (Privacy Anonymized)
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Client / User Agent</th>
                                    <th>Mountpoint</th>
                                    <th>Connected At</th>
                                    <th>Duration</th>
                                    <th>IP Hash (Anonymized)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentSessions)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            No listener sessions recorded yet.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentSessions as $sess): ?>
                                        <tr>
                                            <td class="small text-truncate" style="max-width: 320px;"><?= e($sess['user_agent'] ?: 'Standard Media Player') ?></td>
                                            <td><span class="badge bg-secondary-subtle text-secondary font-monospace"><?= e($sess['mountpoint'] ?? '/live') ?></span></td>
                                            <td class="small"><?= e($sess['connected_at']) ?></td>
                                            <td><?= format_duration($sess['duration'] ?? 0) ?></td>
                                            <td><span class="font-monospace small text-muted"><?= e($sess['ip_hash'] ?? 'anonymized') ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('analyticsChart').getContext('2d');
    const chartLabels = <?= json_encode($chartData['labels'] ?? []) ?>;
    const chartListeners = <?= json_encode($chartData['listeners'] ?? []) ?>;
    const chartPeaks = <?= json_encode($chartData['peaks'] ?? []) ?>;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartLabels.length ? chartLabels : ['00:00', '06:00', '12:00', '18:00'],
            datasets: [
                {
                    label: 'Concurrent Listeners',
                    data: chartListeners.length ? chartListeners : [0, 0, 0, 0],
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.15)',
                    fill: true,
                    tension: 0.3,
                    borderWidth: 2,
                },
                {
                    label: 'Peak Connections',
                    data: chartPeaks.length ? chartPeaks : [0, 0, 0, 0],
                    borderColor: '#10b981',
                    borderDash: [5, 5],
                    borderWidth: 2,
                    fill: false,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } }
            }
        }
    });
});
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
