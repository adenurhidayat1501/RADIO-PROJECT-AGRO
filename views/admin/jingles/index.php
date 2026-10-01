<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/sidebar.php'; ?>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0 fw-bold">Jingles & Station IDs</h3>
                    <p class="text-muted small mb-0">Audio stings, sweepers, and station identifications rotated into Auto DJ</p>
                </div>
                <div class="col-sm-6 text-sm-end mt-2 mt-sm-0">
                    <button class="btn btn-primary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#uploadJingleModal">
                        <i class="bi bi-cloud-arrow-up-fill me-1"></i> Upload Jingle
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

            <div class="card shadow-sm border-0 bg-body">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Jingle Title</th>
                                    <th>Filename</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($jingles)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="bi bi-bell fs-1 d-block mb-2 text-secondary"></i>
                                            No jingles uploaded yet. Upload your station identification stings.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($jingles as $j): ?>
                                        <tr>
                                            <td class="fw-semibold"><i class="bi bi-volume-up text-primary me-2"></i> <?= e($j['title']) ?></td>
                                            <td class="font-monospace small text-muted"><?= e($j['filename']) ?></td>
                                            <td><?= format_duration($j['duration'] ?? 0) ?></td>
                                            <td>
                                                <form action="<?= base_url('admin/jingles/' . $j['_id'] . '/toggle') ?>" method="POST" style="display:inline;">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-sm btn-<?= ($j['enabled'] ?? true) ? 'success' : 'secondary' ?>">
                                                        <?= ($j['enabled'] ?? true) ? 'Active' : 'Disabled' ?>
                                                    </button>
                                                </form>
                                            </td>
                                            <td class="text-end">
                                                <form action="<?= base_url('admin/jingles/' . $j['_id'] . '/delete') ?>" method="POST" onsubmit="return confirm('Delete this jingle?');" style="display:inline;">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
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

<!-- Upload Jingle Modal -->
<div class="modal fade" id="uploadJingleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= base_url('admin/jingles/upload') ?>" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Upload Station Jingle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Jingle Name / Identifier</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Radio Agro Station ID Sting 1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Audio File (.mp3, .wav, .ogg)</label>
                        <input type="file" name="audio_file" class="form-control" accept="audio/*,.mp3,.wav,.ogg" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload & Sync</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
