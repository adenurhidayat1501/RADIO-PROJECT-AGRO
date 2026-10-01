<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/sidebar.php'; ?>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0 fw-bold">Platform Settings & Service Controls</h3>
            <p class="text-muted small">Manage website metadata, social media links, and streaming daemon maintenance</p>
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

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0 bg-body">
                        <div class="card-body p-4">
                            <form action="<?= base_url('admin/settings') ?>" method="POST">
                                <?= csrf_field() ?>

                                <h5 class="fw-bold mb-3 text-primary border-bottom pb-2">General Web Portal Settings</h5>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Public Website Title</label>
                                    <input type="text" name="site_title" class="form-control" value="<?= e($settings['site_title'] ?? 'Radio Agro - Suara Petani Mandiri') ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Public Tagline</label>
                                    <input type="text" name="public_tagline" class="form-control" value="<?= e($settings['public_tagline'] ?? 'Menebar Inspirasi & Musik Nusantara 24 Jam Non-Stop') ?>">
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Contact Email</label>
                                        <input type="email" name="contact_email" class="form-control" value="<?= e($settings['contact_email'] ?? 'redaksi@radioagro.id') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">WhatsApp HotLine / Request</label>
                                        <input type="text" name="contact_whatsapp" class="form-control" value="<?= e($settings['contact_whatsapp'] ?? '+62 812-3456-7890') ?>">
                                    </div>
                                </div>

                                <h5 class="fw-bold mb-3 text-primary border-bottom pb-2">Social Media Channels</h5>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold"><i class="bi bi-youtube text-danger me-1"></i> YouTube Channel URL</label>
                                        <input type="text" name="youtube_url" class="form-control" value="<?= e($settings['youtube_url'] ?? 'https://youtube.com/@radioagro') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold"><i class="bi bi-instagram text-danger me-1"></i> Instagram URL</label>
                                        <input type="text" name="instagram_url" class="form-control" value="<?= e($settings['instagram_url'] ?? 'https://instagram.com/radioagro') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold"><i class="bi bi-facebook text-primary me-1"></i> Facebook Page URL</label>
                                        <input type="text" name="facebook_url" class="form-control" value="<?= e($settings['facebook_url'] ?? 'https://facebook.com/radioagro') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold"><i class="bi bi-twitter-x me-1"></i> X (Twitter) URL</label>
                                        <input type="text" name="twitter_url" class="form-control" value="<?= e($settings['twitter_url'] ?? 'https://x.com/radioagro') ?>">
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check2-circle me-1"></i> Save Platform Settings</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Engine Status & Liquidsoap Reload Controls -->
                <div class="col-lg-4">
                    <div class="card shadow-sm border-0 bg-body mb-4">
                        <div class="card-header bg-transparent border-bottom fw-bold">
                            <i class="bi bi-cpu text-primary me-2"></i> Streaming Engine Diagnostics
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span>Icecast2 Server:</span>
                                <span class="badge bg-<?= $icecastStatus['server_online'] ? 'success' : 'danger' ?>">
                                    <?= $icecastStatus['server_online'] ? 'RUNNING' : 'STOPPED' ?>
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span>Mountpoint (<?= e(config('radio.icecast.mountpoint', '/live')) ?>):</span>
                                <span class="badge bg-<?= $icecastStatus['online'] ? 'success' : 'secondary' ?>">
                                    <?= $icecastStatus['online'] ? 'STREAMING' : 'IDLE' ?>
                                </span>
                            </div>

                            <hr>

                            <h6 class="fw-bold mb-2 small text-uppercase text-muted">Liquidsoap Daemon Recompile</h6>
                            <p class="small text-muted mb-3">
                                Re-export all database playlists to disk and trigger a smooth Liquidsoap reload:
                            </p>
                            <form action="<?= base_url('admin/settings/reload-liquidsoap') ?>" method="POST">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="bi bi-arrow-repeat me-1"></i> Recompile Config & Reload Service
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../partials/footer.php'; ?>
