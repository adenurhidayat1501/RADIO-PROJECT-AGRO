<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/sidebar.php'; ?>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0 fw-bold">Commercials & Advertisements</h3>
                    <p class="text-muted small mb-0">Sponsor spots and community announcements</p>
                </div>
                <div class="col-sm-6 text-sm-end mt-2 mt-sm-0">
                    <button class="btn btn-primary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#uploadAdModal">
                        <i class="bi bi-cloud-arrow-up-fill me-1"></i> Upload Ad Spot
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
                                    <th>Spot Title</th>
                                    <th>Sponsor</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($ads)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="bi bi-megaphone fs-1 d-block mb-2 text-secondary"></i>
                                            No commercial spots currently configured.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($ads as $ad): ?>
                                        <tr>
                                            <td class="fw-semibold"><i class="bi bi-volume-up text-warning me-2"></i> <?= e($ad['title']) ?></td>
                                            <td><?= e($ad['sponsor'] ?: 'Public Service') ?></td>
                                            <td><?= format_duration($ad['duration'] ?? 0) ?></td>
                                            <td>
                                                <form action="<?= base_url('admin/advertisements/' . $ad['_id'] . '/toggle') ?>" method="POST" style="display:inline;">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-sm btn-<?= ($ad['enabled'] ?? true) ? 'success' : 'secondary' ?>">
                                                        <?= ($ad['enabled'] ?? true) ? 'Active' : 'Disabled' ?>
                                                    </button>
                                                </form>
                                            </td>
                                            <td class="text-end">
                                                <form action="<?= base_url('admin/advertisements/' . $ad['_id'] . '/delete') ?>" method="POST" onsubmit="return confirm('Delete this advertisement?');" style="display:inline;">
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

<!-- Upload Ad Modal -->
<div class="modal fade" id="uploadAdModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= base_url('admin/advertisements/upload') ?>" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Upload Commercial Ad Spot</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ad Title</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Pupuk Organik Nusantara Promo" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Sponsor Name</label>
                        <input type="text" name="sponsor" class="form-control" placeholder="e.g. PT Pupuk Nusantara">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Audio File (.mp3, .wav, .ogg)</label>
                        <input type="file" name="audio_file" class="form-control" accept="audio/*,.mp3,.wav,.ogg" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload Spot</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
