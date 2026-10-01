<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/sidebar.php'; ?>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0 fw-bold">Radio Programs & Shows</h3>
                    <p class="text-muted small mb-0">Manage flagship shows, host profiles, and show notes</p>
                </div>
                <div class="col-sm-6 text-sm-end mt-2 mt-sm-0">
                    <button class="btn btn-primary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#newProgramModal">
                        <i class="bi bi-plus-circle me-1"></i> Add Radio Program
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

            <div class="row g-4">
                <?php if (empty($programs)): ?>
                    <div class="col-12">
                        <div class="card shadow-sm border-0 bg-body text-center py-5">
                            <div class="card-body">
                                <i class="bi bi-journal-album fs-1 text-muted d-block mb-3"></i>
                                <h5 class="fw-bold">No Programs Listed Yet</h5>
                                <p class="text-muted small">Add your station's flagship shows and host profiles to display on the public radio website.</p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($programs as $prog): ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="card shadow-sm border-0 bg-body h-100">
                                <div class="card-body p-4 d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="fw-bold mb-0 text-primary"><?= e($prog['title']) ?></h5>
                                        <form action="<?= base_url('admin/programs/' . $prog['_id'] . '/delete') ?>" method="POST" onsubmit="return confirm('Delete this program profile?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                    <h6 class="text-muted small mb-3"><i class="bi bi-mic me-1"></i> Host: <strong><?= e($prog['host'] ?: 'Resident Broadcaster') ?></strong></h6>
                                    <p class="text-muted small flex-grow-1"><?= e($prog['description'] ?: 'No description provided.') ?></p>
                                    <?php if (!empty($prog['schedule_note'])): ?>
                                        <div class="p-2 bg-body-tertiary rounded small border mt-2">
                                            <i class="bi bi-clock me-1 text-primary"></i> <?= e($prog['schedule_note']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- Add Program Modal -->
<div class="modal fade" id="newProgramModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= base_url('admin/programs') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add Radio Program</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Program Title</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Obrolan Tani Sore" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Host / Presenter</label>
                        <input type="text" name="host" class="form-control" placeholder="e.g. Kang Dado & Tim Agro">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Overview of topics, music, and segments..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Broadcast Air Time Note</label>
                        <input type="text" name="schedule_note" class="form-control" placeholder="e.g. Senin - Jumat, 16:00 - 18:00 WIB">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Program</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
