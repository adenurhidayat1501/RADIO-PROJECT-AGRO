<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/sidebar.php'; ?>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0 fw-bold">Station Identity & Stream Settings</h3>
            <p class="text-muted small">Configure station profile, stream mountpoint, and encoder parameters</p>
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

            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0 bg-body">
                        <div class="card-body p-4">
                            <form action="<?= base_url('admin/station') ?>" method="POST">
                                <?= csrf_field() ?>

                                <h5 class="fw-bold mb-3 text-primary border-bottom pb-2">Station Profile</h5>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Station Name</label>
                                    <input type="text" name="name" class="form-control" value="<?= e($station['name'] ?? 'Radio Agro') ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Description / Slogan</label>
                                    <textarea name="description" class="form-control" rows="2"><?= e($station['description'] ?? '') ?></textarea>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Primary Genre</label>
                                        <input type="text" name="genre" class="form-control" value="<?= e($station['genre'] ?? 'Various') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Timezone</label>
                                        <input type="text" name="timezone" class="form-control" value="<?= e($station['timezone'] ?? 'Asia/Jakarta') ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Language Code</label>
                                        <input type="text" name="language" class="form-control" value="<?= e($station['language'] ?? 'id') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Country Code</label>
                                        <input type="text" name="country" class="form-control" value="<?= e($station['country'] ?? 'ID') ?>">
                                    </div>
                                </div>

                                <h5 class="fw-bold mb-3 text-primary border-bottom pb-2">Stream Output Parameters</h5>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Icecast Mountpoint</label>
                                        <div class="input-group">
                                            <span class="input-group-text">/</span>
                                            <input type="text" name="mountpoint" class="form-control" value="<?= e(ltrim($station['stream']['mountpoint'] ?? 'live', '/')) ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Bitrate (kbps)</label>
                                        <select name="bitrate" class="form-select">
                                            <?php foreach ([64, 96, 128, 192, 256, 320] as $b): ?>
                                                <option value="<?= $b ?>" <?= ($station['stream']['bitrate'] ?? 128) == $b ? 'selected' : '' ?>><?= $b ?> kbps</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Format</label>
                                        <select name="format" class="form-select">
                                            <option value="mp3" <?= ($station['stream']['format'] ?? 'mp3') == 'mp3' ? 'selected' : '' ?>>MP3 Audio</option>
                                            <option value="aac" <?= ($station['stream']['format'] ?? 'mp3') == 'aac' ? 'selected' : '' ?>>AAC / AAC+ (High Efficiency)</option>
                                            <option value="ogg" <?= ($station['stream']['format'] ?? 'mp3') == 'ogg' ? 'selected' : '' ?>>Ogg Vorbis</option>
                                        </select>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                    <i class="bi bi-check2-circle me-1"></i> Save Changes & Reconfigure
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 mt-3 mt-lg-0">
                    <div class="card shadow-sm border-0 bg-body mb-3">
                        <div class="card-header bg-transparent fw-bold">
                            <i class="bi bi-info-circle text-info me-1"></i> Auto DJ Automation
                        </div>
                        <div class="card-body small text-muted">
                            <p>Changing the stream mountpoint or bitrate will automatically re-generate the Liquidsoap configuration (<code>/etc/radio/radio.liq</code>).</p>
                            <p class="mb-0">When your DJ software connects via Icecast / Harbor, it will seamlessly crossfade over the Auto DJ on the designated mountpoint.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../partials/footer.php'; ?>
