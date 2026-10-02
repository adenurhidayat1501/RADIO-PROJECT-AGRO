<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/sidebar.php'; ?>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0 fw-bold">Music Library</h3>
                    <p class="text-muted small mb-0">Audio assets stored in <code><?= e($storagePath) ?></code> with MongoDB metadata</p>
                </div>
                <div class="col-sm-6 text-sm-end mt-2 mt-sm-0">
                    <button class="btn btn-primary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#uploadModal">
                        <i class="bi bi-cloud-arrow-up-fill me-1"></i> Upload Audio Track
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

            <?php if ($err = flash('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= e($err) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0 bg-body">
                <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <form method="GET" action="<?= base_url('admin/music') ?>" class="d-flex align-items-center">
                        <div class="input-group input-group-sm" style="max-width: 320px;">
                            <input type="text" name="q" class="form-control" placeholder="Search track, artist, album..." value="<?= e($search) ?>">
                            <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                        </div>
                    </form>
                    <span class="text-muted small">Total Tracks: <strong><?= number_format($pagination['total']) ?></strong></span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Track / Title</th>
                                    <th>Artist</th>
                                    <th>Album</th>
                                    <th>Genre</th>
                                    <th>Duration</th>
                                    <th>Plays</th>
                                    <th>Size</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($songs)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">
                                            <i class="bi bi-disc fs-1 d-block mb-2 text-secondary"></i>
                                            No audio files found in music library. Click "Upload Audio Track" to add music.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($songs as $song): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="p-2 rounded bg-primary-subtle text-primary me-2">
                                                        <i class="bi bi-music-note"></i>
                                                    </div>
                                                    <div>
                                                        <span class="fw-semibold d-block"><?= e($song['title']) ?></span>
                                                        <small class="text-muted font-monospace"><?= e($song['filename']) ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?= e($song['artist']) ?></td>
                                            <td><?= e($song['album'] ?? 'Single') ?></td>
                                            <td><span class="badge bg-secondary-subtle text-secondary"><?= e($song['genre'] ?? 'Various') ?></span></td>
                                            <td><?= format_duration($song['duration'] ?? 0) ?></td>
                                            <td><span class="badge bg-light text-dark border"><?= number_format($song['play_count'] ?? 0) ?></span></td>
                                            <td><small class="text-muted"><?= format_bytes($song['filesize'] ?? 0) ?></small></td>
                                            <td class="text-end">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="<?= base_url('admin/music/' . $song['_id'] . '/edit') ?>" class="btn btn-outline-secondary" title="Edit Metadata">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <form action="<?= base_url('admin/music/' . $song['_id'] . '/delete') ?>" method="POST" onsubmit="return confirm('Permanently delete this song and audio file?');" style="display:inline;">
                                                        <?= csrf_field() ?>
                                                        <button type="submit" class="btn btn-outline-danger" title="Delete Track">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <?php if ($pagination['total_pages'] > 1): ?>
                    <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
                        <small class="text-muted">Showing page <?= $pagination['current_page'] ?> of <?= $pagination['total_pages'] ?></small>
                        <ul class="pagination pagination-sm mb-0">
                            <?php if ($pagination['has_prev']): ?>
                                <li class="page-item"><a class="page-link" href="<?= base_url('admin/music?page=' . ($pagination['current_page'] - 1) . '&q=' . urlencode($search)) ?>">Previous</a></li>
                            <?php endif; ?>
                            <?php if ($pagination['has_next']): ?>
                                <li class="page-item"><a class="page-link" href="<?= base_url('admin/music?page=' . ($pagination['current_page'] + 1) . '&q=' . urlencode($search)) ?>">Next</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- Upload Audio Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= base_url('admin/music/upload') ?>" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-cloud-arrow-up text-primary me-2"></i> Upload Audio File</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">
                        Audio files are validated, stored in <code><?= e($storagePath) ?></code>, and their ID3 tags are extracted into MongoDB automatically.
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Audio File (.mp3, .wav, .ogg, .aac, .m4a, .mp4, .flac)</label>
                        <input type="file" name="audio_file" class="form-control" accept="audio/*,.mp3,.wav,.ogg,.aac,.m4a,.mp4,.flac" required>
                    </div>
                    <div class="form-text small">
                        Allowed extensions: .mp3, .wav, .ogg, .aac, .m4a, .mp4, .flac. Media files with video containers are automatically converted to clean MP3. Max size up to 128MB.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-upload me-1"></i> Start Upload & Tag Extraction</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
