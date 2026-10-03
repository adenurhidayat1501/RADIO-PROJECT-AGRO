<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/sidebar.php'; ?>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0 fw-bold">
                        <i class="bi bi-shield-check text-primary me-2"></i>Analisis Error & Diagnosa Sistem
                    </h3>
                    <p class="text-muted small mb-0">Inspeksi kesehatan menyeluruh: Database, Icecast2, Liquidsoap, Penyimpanan Lagu & Nginx</p>
                </div>
                <div class="col-sm-6 text-sm-end mt-2 mt-sm-0">
                    <a href="<?= base_url('admin/diagnostics') ?>" class="btn btn-outline-primary btn-sm me-2">
                        <i class="bi bi-arrow-clockwise me-1"></i> Jalankan Ulang Diagnosa
                    </a>
                    <button type="button" class="btn btn-primary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#repairModal">
                        <i class="bi bi-tools me-1"></i> Perbaiki Otomatis (Auto-Repair)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <?php if ($succ = flash('success')): ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill me-2 fs-5 align-middle"></i> <?= e($succ) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($err = flash('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-5 align-middle"></i> <?= e($err) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- SUMMARY TILES & HEALTH SCORE -->
            <div class="row g-3 mb-4">
                <!-- Health Score -->
                <div class="col-12 col-md-3">
                    <div class="card h-100 border-0 shadow-sm bg-body">
                        <div class="card-body d-flex align-items-center">
                            <div class="position-relative me-3 d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                                <div class="rounded-circle d-flex align-items-center justify-content-center w-100 h-100 fw-bold fs-4 bg-<?= $summary['health_score'] >= 90 ? 'success' : ($summary['health_score'] >= 70 ? 'warning' : 'danger') ?> bg-opacity-10 text-<?= $summary['health_score'] >= 90 ? 'success' : ($summary['health_score'] >= 70 ? 'warning' : 'danger') ?>">
                                    <?= $summary['health_score'] ?>%
                                </div>
                            </div>
                            <div>
                                <div class="text-muted small text-uppercase fw-semibold">Kesehatan Sistem</div>
                                <h4 class="mb-0 fw-bold text-<?= $summary['health_score'] >= 90 ? 'success' : ($summary['health_score'] >= 70 ? 'warning' : 'danger') ?>">
                                    <?= $summary['errors'] === 0 ? ($summary['warnings'] === 0 ? 'Optimal' : 'Ada Catatan') : 'Perlu Tindakan' ?>
                                </h4>
                                <small class="text-muted"><?= $summary['timestamp'] ?></small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Passed -->
                <div class="col-6 col-md-3">
                    <div class="card h-100 border-0 shadow-sm bg-body">
                        <div class="card-body d-flex align-items-center">
                            <div class="p-3 rounded-3 bg-success bg-opacity-10 text-success me-3 fs-3">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <div>
                                <div class="text-muted small text-uppercase fw-semibold">Pengujian Lolos</div>
                                <h3 class="mb-0 fw-bold text-success"><?= $summary['passed'] ?> <small class="text-muted fs-6">/ <?= $summary['total'] ?></small></h3>
                                <small class="text-success">Semua komponen normal</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Warnings -->
                <div class="col-6 col-md-3">
                    <div class="card h-100 border-0 shadow-sm bg-body">
                        <div class="card-body d-flex align-items-center">
                            <div class="p-3 rounded-3 bg-warning bg-opacity-10 text-warning me-3 fs-3">
                                <i class="bi bi-exclamation-circle"></i>
                            </div>
                            <div>
                                <div class="text-muted small text-uppercase fw-semibold">Peringatan (Warning)</div>
                                <h3 class="mb-0 fw-bold text-warning"><?= $summary['warnings'] ?></h3>
                                <small class="text-muted">Dapat dioptimalkan</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Errors -->
                <div class="col-12 col-md-3">
                    <div class="card h-100 border-0 shadow-sm bg-body">
                        <div class="card-body d-flex align-items-center">
                            <div class="p-3 rounded-3 bg-danger bg-opacity-10 text-danger me-3 fs-3">
                                <i class="bi bi-x-octagon"></i>
                            </div>
                            <div>
                                <div class="text-muted small text-uppercase fw-semibold">Error / Gangguan</div>
                                <h3 class="mb-0 fw-bold text-danger"><?= $summary['errors'] ?></h3>
                                <small class="<?= $summary['errors'] > 0 ? 'text-danger fw-bold' : 'text-muted' ?>">
                                    <?= $summary['errors'] > 0 ? 'Membutuhkan perbaikan' : 'Tidak ada error' ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DIAGNOSTIC CHECKLIST BY CATEGORY -->
            <div class="row g-4 mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm bg-body">
                        <div class="card-header bg-transparent border-bottom d-flex align-items-center justify-content-between py-3">
                            <span class="fw-bold">
                                <i class="bi bi-card-checklist text-primary me-2"></i>Hasil Analisis Tiap Komponen
                            </span>
                            <span class="badge bg-secondary"><?= $summary['total'] ?> Parameter Diuji</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 110px;">Status</th>
                                            <th style="width: 160px;">Kategori</th>
                                            <th style="width: 260px;">Parameter</th>
                                            <th>Hasil Analisis & Solusi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($checks as $item): ?>
                                            <tr class="<?= $item['status'] === 'error' ? 'table-danger bg-opacity-25' : ($item['status'] === 'warning' ? 'table-warning bg-opacity-10' : '') ?>">
                                                <td>
                                                    <?php if ($item['status'] === 'ok'): ?>
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                            <i class="bi bi-check-circle-fill me-1"></i> NORMAL
                                                        </span>
                                                    <?php elseif ($item['status'] === 'warning'): ?>
                                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">
                                                            <i class="bi bi-exclamation-triangle-fill me-1"></i> WARNING
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                                            <i class="bi bi-x-circle-fill me-1"></i> ERROR
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-dark bg-opacity-10 text-body font-monospace"><?= e($item['category']) ?></span>
                                                </td>
                                                <td>
                                                    <span class="fw-semibold text-body"><?= e($item['name']) ?></span>
                                                </td>
                                                <td>
                                                    <div class="small <?= $item['status'] === 'error' ? 'text-danger fw-semibold' : 'text-body' ?>">
                                                        <?= e($item['message']) ?>
                                                    </div>
                                                    <?php if (!empty($item['remedy'])): ?>
                                                        <div class="mt-2 p-2 bg-dark bg-opacity-10 rounded-2 border d-flex align-items-center justify-content-between">
                                                            <div class="font-monospace small text-primary text-truncate me-2">
                                                                <i class="bi bi-terminal me-1"></i> <?= e($item['remedy']) ?>
                                                            </div>
                                                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 btn-copy-cmd" data-cmd="<?= e($item['remedy']) ?>" title="Salin Perintah">
                                                                <i class="bi bi-copy"></i>
                                                            </button>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SYSTEM LOG VIEWER TABS -->
            <div class="card border-0 shadow-sm bg-body mb-4">
                <div class="card-header bg-transparent border-bottom py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="fw-bold">
                            <i class="bi bi-terminal-split text-info me-2"></i>Live Service Error & Diagnostic Logs
                        </span>
                        <small class="text-muted">Menampilkan 35 baris terbaru dari setiap service</small>
                    </div>
                </div>
                <div class="card-body p-3">
                    <ul class="nav nav-pills mb-3" id="logTabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" id="tab-liquidsoap-btn" data-bs-toggle="pill" data-bs-target="#tab-liquidsoap" type="button">
                                <i class="bi bi-broadcast-pin me-1"></i> Liquidsoap Log
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="tab-icecast-btn" data-bs-toggle="pill" data-bs-target="#tab-icecast" type="button">
                                <i class="bi bi-speaker me-1"></i> Icecast Error Log
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="tab-nginx-btn" data-bs-toggle="pill" data-bs-target="#tab-nginx" type="button">
                                <i class="bi bi-hdd-network me-1"></i> Nginx Error Log
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="tab-app-btn" data-bs-toggle="pill" data-bs-target="#tab-app" type="button">
                                <i class="bi bi-code-square me-1"></i> PHP App Log
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="logTabsContent">
                        <!-- Liquidsoap -->
                        <div class="tab-pane fade show active" id="tab-liquidsoap">
                            <pre class="bg-dark text-light p-3 rounded-3 font-monospace small mb-0" style="max-height: 380px; overflow-y: auto;"><?php
                                if (!empty($logs['liquidsoap'])) {
                                    foreach ($logs['liquidsoap'] as $line) {
                                        $isErr = stripos($line, 'error') !== false || stripos($line, 'fatal') !== false || stripos($line, 'exception') !== false;
                                        $color = $isErr ? 'text-danger fw-bold' : (stripos($line, 'warning') !== false ? 'text-warning' : 'text-light');
                                        echo "<span class='{$color}'>" . htmlspecialchars($line) . "</span>\n";
                                    }
                                } else {
                                    echo "<span class='text-muted'>Log file /var/log/radio/liquidsoap.log belum berisi data atau belum dibuat.</span>";
                                }
                            ?></pre>
                        </div>

                        <!-- Icecast -->
                        <div class="tab-pane fade" id="tab-icecast">
                            <pre class="bg-dark text-light p-3 rounded-3 font-monospace small mb-0" style="max-height: 380px; overflow-y: auto;"><?php
                                if (!empty($logs['icecast'])) {
                                    foreach ($logs['icecast'] as $line) {
                                        $isErr = stripos($line, 'error') !== false || stripos($line, 'fatal') !== false;
                                        $color = $isErr ? 'text-danger fw-bold' : 'text-light';
                                        echo "<span class='{$color}'>" . htmlspecialchars($line) . "</span>\n";
                                    }
                                } else {
                                    echo "<span class='text-muted'>Tidak ada error pada Icecast log.</span>";
                                }
                            ?></pre>
                        </div>

                        <!-- Nginx -->
                        <div class="tab-pane fade" id="tab-nginx">
                            <pre class="bg-dark text-light p-3 rounded-3 font-monospace small mb-0" style="max-height: 380px; overflow-y: auto;"><?php
                                if (!empty($logs['nginx_error'])) {
                                    foreach ($logs['nginx_error'] as $line) {
                                        $isErr = stripos($line, 'error') !== false || stripos($line, 'crit') !== false;
                                        $color = $isErr ? 'text-danger fw-bold' : 'text-light';
                                        echo "<span class='{$color}'>" . htmlspecialchars($line) . "</span>\n";
                                    }
                                } else {
                                    echo "<span class='text-muted'>Tidak ada error pada Nginx reverse proxy log.</span>";
                                }
                            ?></pre>
                        </div>

                        <!-- App Log -->
                        <div class="tab-pane fade" id="tab-app">
                            <pre class="bg-dark text-light p-3 rounded-3 font-monospace small mb-0" style="max-height: 380px; overflow-y: auto;"><?php
                                if (!empty($logs['app_log'])) {
                                    foreach ($logs['app_log'] as $line) {
                                        echo "<span>" . htmlspecialchars($line) . "</span>\n";
                                    }
                                } else {
                                    echo "<span class='text-muted'>Tidak ada log aplikasi yang tercatat di storage/logs/app.log.</span>";
                                }
                            ?></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- AUTO-REPAIR CONFIRMATION MODAL -->
<div class="modal fade" id="repairModal" tabindex="-1" aria-labelledby="repairModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="repairModalLabel">
                    <i class="bi bi-tools text-primary me-2"></i>Perbaikan Otomatis Sistem
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Proses Auto-Repair akan menjalankan tindakan pemulihan berikut secara otomatis:</p>
                <ul class="list-group list-group-flush mb-3 small">
                    <li class="list-group-item d-flex align-items-center">
                        <i class="bi bi-check2-circle text-success me-2"></i> Memvalidasi dan membuat direktori penyimpanan jika belum ada
                    </li>
                    <li class="list-group-item d-flex align-items-center">
                        <i class="bi bi-check2-circle text-success me-2"></i> Memindai file MP3 di <code>/var/lib/radio/music</code> dan sinkronisasi ke <code>default.m3u</code>
                    </li>
                    <li class="list-group-item d-flex align-items-center">
                        <i class="bi bi-check2-circle text-success me-2"></i> Menyiapkan file emergency safety tone fallback
                    </li>
                    <li class="list-group-item d-flex align-items-center">
                        <i class="bi bi-check2-circle text-success me-2"></i> Re-kompilasi script Liquidsoap <code>/etc/radio/radio.liq</code>
                    </li>
                    <li class="list-group-item d-flex align-items-center">
                        <i class="bi bi-check2-circle text-success me-2"></i> Mengirim perintah Telnet <code>autodj.reload</code> & <code>autodj.skip</code>
                    </li>
                </ul>
                <div class="alert alert-info py-2 small mb-0">
                    <i class="bi bi-info-circle me-1"></i> Siaran streaming akan berpindah mulus ke playlist terbaru tanpa memutus server Icecast.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <form action="<?= base_url('admin/diagnostics/repair') ?>" method="POST">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-play-circle me-1"></i> Mulai Perbaikan Sekarang
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Copy command helper
    document.querySelectorAll('.btn-copy-cmd').forEach(btn => {
        btn.addEventListener('click', function () {
            const cmd = this.getAttribute('data-cmd');
            if (cmd && navigator.clipboard) {
                navigator.clipboard.writeText(cmd).then(() => {
                    const originalHtml = this.innerHTML;
                    this.innerHTML = '<i class="bi bi-check2 text-success"></i>';
                    setTimeout(() => {
                        this.innerHTML = originalHtml;
                    }, 1800);
                });
            }
        });
    });
});
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
