<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/sidebar.php'; ?>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0 fw-bold">MongoDB Database Backup & Recovery</h3>
                    <p class="text-muted small mb-0">Create, download, and restore MongoDB snapshots and station configurations</p>
                </div>
                <div class="col-sm-6 text-sm-end mt-2 mt-sm-0">
                    <form action="<?= base_url('admin/backup/create') ?>" method="POST" style="display:inline;">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-primary btn-sm shadow-sm">
                            <i class="bi bi-cloud-arrow-down-fill me-1"></i> Create Snapshot Backup
                        </button>
                    </form>
                    <button class="btn btn-outline-danger btn-sm shadow-sm ms-2" data-bs-toggle="modal" data-bs-target="#restoreModal">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Restore from Backup
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
                <div class="card-header bg-transparent border-bottom fw-bold">
                    <i class="bi bi-archive text-primary me-2"></i> Available Backup Archives
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Archive File</th>
                                    <th>Created Date</th>
                                    <th>Filesize</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($backups)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            <i class="bi bi-folder-x fs-1 d-block mb-2 text-secondary"></i>
                                            No database backup archives created yet. Click "Create Snapshot Backup" above.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($backups as $b): ?>
                                        <tr>
                                            <td class="fw-bold font-monospace">
                                                <i class="bi bi-file-earmark-zip text-warning me-2"></i> <?= e($b['filename']) ?>
                                            </td>
                                            <td><?= e($b['created_at']) ?></td>
                                            <td><?= format_bytes($b['size']) ?></td>
                                            <td class="text-end">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="<?= base_url('admin/backup/download/' . urlencode($b['filename'])) ?>" class="btn btn-outline-primary" title="Download Archive">
                                                        <i class="bi bi-download"></i> Download
                                                    </a>
                                                    <form action="<?= base_url('admin/backup/delete/' . urlencode($b['filename'])) ?>" method="POST" onsubmit="return confirm('Delete this backup archive?');" style="display:inline;">
                                                        <?= csrf_field() ?>
                                                        <button type="submit" class="btn btn-outline-danger" title="Delete Archive">
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
            </div>
        </div>
    </div>
</main>

<!-- Restore Modal -->
<div class="modal fade" id="restoreModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= base_url('admin/backup/restore') ?>" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i> Restore MongoDB Database</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">
                        <strong>Warning:</strong> Restoring a backup will overwrite current metadata, schedules, playlists, and users.
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Backup Archive (.tar.gz or .json)</label>
                        <input type="file" name="backup_file" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you absolutely sure? Current MongoDB data will be replaced.');">
                        Restore Database
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
