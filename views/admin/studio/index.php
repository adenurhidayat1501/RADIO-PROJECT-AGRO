<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/sidebar.php'; ?>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0 fw-bold">Live Studio & Broadcast Encoder Setup</h3>
            <p class="text-muted small">Connect live DJ software (Mixxx, BUTT, OBS, RadioBOSS) to broadcast directly onto the stream</p>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <!-- Studio Status Banner -->
            <div class="card shadow-sm border-0 mb-4 bg-<?= ($nowPlaying['source'] ?? '') === 'live_dj' ? 'danger' : 'primary' ?> text-white">
                <div class="card-body p-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div>
                        <span class="badge bg-light text-dark fw-bold text-uppercase mb-1">Live Studio Engine</span>
                        <h3 class="fw-bold mb-0">
                            <?php if (($nowPlaying['source'] ?? '') === 'live_dj'): ?>
                                <i class="bi bi-mic-fill me-2"></i> LIVE DJ ON AIR NOW
                            <?php else: ?>
                                <i class="bi bi-robot me-2"></i> AUTO DJ RUNNING (WAITING FOR LIVE DJ)
                            <?php endif; ?>
                        </h3>
                        <p class="mb-0 opacity-75 small">
                            When a DJ connects using the credentials below, Liquidsoap will automatically crossfade the music and switch to Live Studio.
                        </p>
                    </div>
                    <div>
                        <button id="btn-force-autodj" class="btn btn-light shadow-sm">
                            <i class="bi bi-arrow-repeat me-1"></i> Force Auto DJ Takeover
                        </button>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Encoder Connection Details Card -->
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0 bg-body h-100">
                        <div class="card-header bg-transparent border-bottom fw-bold">
                            <i class="bi bi-gear-wide-connected text-primary me-2"></i> Live DJ Encoder Settings (Mixxx / BUTT / OBS)
                        </div>
                        <div class="card-body p-4">
                            <p class="text-muted small mb-3">
                                Configure your external broadcast software with the following parameters:
                            </p>

                            <table class="table table-bordered align-middle">
                                <tbody>
                                    <tr>
                                        <th class="bg-body-tertiary" style="width: 35%;">Server Type</th>
                                        <td><span class="badge bg-secondary">Icecast 2</span></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-body-tertiary">Server Host / IP</th>
                                        <td class="font-monospace fw-semibold"><?= e($_SERVER['SERVER_NAME'] ?? $harborConfig['host']) ?></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-body-tertiary">Port</th>
                                        <td class="font-monospace fw-semibold text-primary"><?= e($harborConfig['port']) ?></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-body-tertiary">Mountpoint</th>
                                        <td class="font-monospace fw-semibold text-danger"><?= e($harborConfig['mount']) ?></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-body-tertiary">Source Username</th>
                                        <td class="font-monospace"><?= e($harborConfig['user']) ?></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-body-tertiary">Source Password</th>
                                        <td class="font-monospace"><?= e($harborConfig['password']) ?></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-body-tertiary">Audio Codec</th>
                                        <td>MP3 (128 kbps or 192 kbps), 44.1 kHz Stereo</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Software Guides Card -->
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0 bg-body h-100">
                        <div class="card-header bg-transparent border-bottom fw-bold">
                            <i class="bi bi-info-circle text-info me-2"></i> Software Quick Start Guides
                        </div>
                        <div class="card-body p-4">
                            <div class="accordion" id="guideAccordion">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#mixxxGuide">
                                            Mixxx DJ Setup Guide
                                        </button>
                                    </h2>
                                    <div id="mixxxGuide" class="accordion-collapse collapse show" data-bs-parent="#guideAccordion">
                                        <div class="accordion-body small text-muted">
                                            1. Open <strong>Preferences → Live Broadcasting</strong>.<br>
                                            2. Type: <strong>Icecast 2</strong>.<br>
                                            3. Host: <strong><?= e($_SERVER['SERVER_NAME'] ?? 'your-vps-ip') ?></strong>, Port: <strong><?= e($harborConfig['port']) ?></strong>.<br>
                                            4. Mount: <strong><?= e($harborConfig['mount']) ?></strong>.<br>
                                            5. Login: <strong>source</strong>, Password: <strong><?= e($harborConfig['password']) ?></strong>.<br>
                                            6. Enable Live Broadcasting to go on air.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#buttGuide">
                                            B.U.T.T (Broadcast Using This Tool)
                                        </button>
                                    </h2>
                                    <div id="buttGuide" class="accordion-collapse collapse" data-bs-parent="#guideAccordion">
                                        <div class="accordion-body small text-muted">
                                            1. Open BUTT → <strong>Settings → Server</strong>.<br>
                                            2. Type: Icecast, Address: <strong><?= e($_SERVER['SERVER_NAME'] ?? 'your-vps-ip') ?></strong>, Port: <strong><?= e($harborConfig['port']) ?></strong>.<br>
                                            3. Password: <strong><?= e($harborConfig['password']) ?></strong>, Icecast mount: <strong><?= e($harborConfig['mount']) ?></strong>.<br>
                                            4. User: <strong>source</strong>.<br>
                                            5. Click the play button to start streaming microphone/desktop audio.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.getElementById('btn-force-autodj')?.addEventListener('click', function () {
    if (!confirm('Take over stream and force Auto DJ mode?')) return;
    fetch('<?= base_url('api/admin/radio/auto-dj') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=restart'
    }).then(res => res.json()).then(data => {
        alert(data.message);
        location.reload();
    });
});
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
