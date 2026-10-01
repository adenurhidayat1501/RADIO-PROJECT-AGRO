<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/sidebar.php'; ?>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0 fw-bold">Edit Track Metadata</h3>
            <p class="text-muted small">Update title, artist, album, and rotation settings</p>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0 bg-body">
                        <div class="card-body p-4">
                            <form action="<?= base_url('admin/music/' . $song['_id'] . '/edit') ?>" method="POST">
                                <?= csrf_field() ?>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Song Title</label>
                                    <input type="text" name="title" class="form-control" value="<?= e($song['title']) ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Artist</label>
                                    <input type="text" name="artist" class="form-control" value="<?= e($song['artist']) ?>" required>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Album</label>
                                        <input type="text" name="album" class="form-control" value="<?= e($song['album'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Genre</label>
                                        <input type="text" name="genre" class="form-control" value="<?= e($song['genre'] ?? 'Various') ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Release Year</label>
                                        <input type="number" name="year" class="form-control" value="<?= e($song['year'] ?? date('Y')) ?>">
                                    </div>
                                </div>

                                <div class="mb-4 form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="enabled" id="enabledCheck" <?= ($song['enabled'] ?? true) ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-semibold" for="enabledCheck">Enable for Auto DJ Playlist Rotation</label>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-1"></i> Save Changes</button>
                                    <a href="<?= base_url('admin/music') ?>" class="btn btn-outline-secondary">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 mt-3 mt-lg-0">
                    <div class="card shadow-sm border-0 bg-body">
                        <div class="card-header bg-transparent fw-bold">File Information</div>
                        <div class="card-body small">
                            <dl class="row mb-0">
                                <dt class="col-sm-4 text-muted">Filename:</dt>
                                <dd class="col-sm-8 font-monospace text-truncate"><?= e($song['filename']) ?></dd>

                                <dt class="col-sm-4 text-muted">Filepath:</dt>
                                <dd class="col-sm-8 font-monospace small text-truncate"><?= e($song['filepath']) ?></dd>

                                <dt class="col-sm-4 text-muted">Duration:</dt>
                                <dd class="col-sm-8"><?= format_duration($song['duration'] ?? 0) ?></dd>

                                <dt class="col-sm-4 text-muted">Filesize:</dt>
                                <dd class="col-sm-8"><?= format_bytes($song['filesize'] ?? 0) ?></dd>

                                <dt class="col-sm-4 text-muted">Play Count:</dt>
                                <dd class="col-sm-8"><?= number_format($song['play_count'] ?? 0) ?> times</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../partials/footer.php'; ?>
