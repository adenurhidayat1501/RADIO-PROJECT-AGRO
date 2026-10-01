<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/sidebar.php'; ?>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0 fw-bold">Playlist Management</h3>
                    <p class="text-muted small mb-0">Organize music rotations, special blocks, and show programming</p>
                </div>
                <div class="col-sm-6 text-sm-end mt-2 mt-sm-0">
                    <a href="<?= base_url('admin/playlists/create') ?>" class="btn btn-primary btn-sm shadow-sm">
                        <i class="bi bi-plus-circle me-1"></i> Create New Playlist
                    </a>
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
                <?php if (empty($playlists)): ?>
                    <div class="col-12">
                        <div class="card shadow-sm border-0 bg-body text-center py-5">
                            <div class="card-body">
                                <i class="bi bi-collection-play fs-1 text-muted d-block mb-3"></i>
                                <h5 class="fw-bold">No Playlists Created Yet</h5>
                                <p class="text-muted small">Create your first playlist to organize tracks for scheduled shows or Auto DJ rotation.</p>
                                <a href="<?= base_url('admin/playlists/create') ?>" class="btn btn-primary btn-sm">Create Playlist</a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($playlists as $pl): ?>
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="card shadow-sm border-0 bg-body h-100">
                                <div class="card-body p-4 d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="fw-bold mb-0 text-truncate"><?= e($pl['name']) ?></h5>
                                        <span class="badge bg-<?= ($pl['status'] ?? 'active') === 'active' ? 'success' : 'secondary' ?>">
                                            <?= strtoupper($pl['status'] ?? 'active') ?>
                                        </span>
                                    </div>
                                    <p class="text-muted small flex-grow-1"><?= e($pl['description'] ?: 'No description provided.') ?></p>

                                    <div class="d-flex justify-content-between small text-muted border-top border-bottom py-2 my-3">
                                        <span><i class="bi bi-music-note-beamed me-1"></i> <strong><?= $pl['song_count'] ?></strong> tracks</span>
                                        <span><i class="bi bi-clock me-1"></i> <?= $pl['total_duration'] ?></span>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex gap-2">
                                            <?php if ($pl['shuffle'] ?? true): ?>
                                                <span class="badge bg-secondary-subtle text-secondary"><i class="bi bi-shuffle"></i> Shuffle</span>
                                            <?php endif; ?>
                                            <?php if ($pl['crossfade'] ?? true): ?>
                                                <span class="badge bg-secondary-subtle text-secondary"><i class="bi bi-bezier2"></i> Crossfade</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= base_url('admin/playlists/' . $pl['_id'] . '/manage') ?>" class="btn btn-outline-primary">
                                                <i class="bi bi-list-check me-1"></i> Manage Tracks
                                            </a>
                                            <form action="<?= base_url('admin/playlists/' . $pl['_id'] . '/delete') ?>" method="POST" onsubmit="return confirm('Delete this playlist?');" style="display:inline;">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-outline-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../partials/footer.php'; ?>
