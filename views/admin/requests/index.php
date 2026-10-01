<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/sidebar.php'; ?>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0 fw-bold">Song Requests & Moderation</h3>
            <p class="text-muted small">Moderate listener submissions, approve tracks, or trigger instant playback via Auto DJ</p>
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
                    <div class="btn-group btn-group-sm">
                        <a href="<?= base_url('admin/requests?status=pending') ?>" class="btn btn-outline-secondary <?= $currentStatus === 'pending' ? 'active' : '' ?>">Pending</a>
                        <a href="<?= base_url('admin/requests?status=approved') ?>" class="btn btn-outline-secondary <?= $currentStatus === 'approved' ? 'active' : '' ?>">Approved</a>
                        <a href="<?= base_url('admin/requests?status=played') ?>" class="btn btn-outline-secondary <?= $currentStatus === 'played' ? 'active' : '' ?>">Played</a>
                        <a href="<?= base_url('admin/requests?status=all') ?>" class="btn btn-outline-secondary <?= $currentStatus === 'all' ? 'active' : '' ?>">All Requests</a>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Listener Name</th>
                                    <th>Requested Song</th>
                                    <th>Message / Dedication</th>
                                    <th>Submitted At</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($requests)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="bi bi-chat-heart fs-1 d-block mb-2 text-secondary"></i>
                                            No song requests found in this view.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($requests as $req): ?>
                                        <tr>
                                            <td class="fw-bold">
                                                <i class="bi bi-person-fill text-muted me-1"></i> <?= e($req['name']) ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($req['song'])): ?>
                                                    <strong><?= e($req['song']['artist']) ?></strong> - <?= e($req['song']['title']) ?>
                                                <?php else: ?>
                                                    <span class="text-muted italic">[Song unavailable in library]</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small text-muted" style="max-width: 250px;">
                                                <?= e($req['message'] ?: 'No message') ?>
                                            </td>
                                            <td><small class="font-monospace text-muted"><?= e($req['created_at']) ?></small></td>
                                            <td>
                                                <span class="badge bg-<?= match($req['status'] ?? 'pending') {
                                                    'approved' => 'info',
                                                    'played' => 'success',
                                                    'rejected' => 'danger',
                                                    default => 'warning text-dark',
                                                } ?>">
                                                    <?= strtoupper($req['status'] ?? 'pending') ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group btn-group-sm">
                                                    <?php if (($req['status'] ?? '') === 'pending' || ($req['status'] ?? '') === 'approved'): ?>
                                                        <form action="<?= base_url('admin/requests/' . $req['_id'] . '/play-next') ?>" method="POST" style="display:inline;">
                                                            <?= csrf_field() ?>
                                                            <button type="submit" class="btn btn-primary" title="Queue to Play Next">
                                                                <i class="bi bi-play-fill"></i> Play Next
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>

                                                    <?php if (($req['status'] ?? '') === 'pending'): ?>
                                                        <form action="<?= base_url('admin/requests/' . $req['_id'] . '/approve') ?>" method="POST" style="display:inline;">
                                                            <?= csrf_field() ?>
                                                            <button type="submit" class="btn btn-outline-success" title="Approve">
                                                                <i class="bi bi-check-lg"></i>
                                                            </button>
                                                        </form>
                                                        <form action="<?= base_url('admin/requests/' . $req['_id'] . '/reject') ?>" method="POST" style="display:inline;">
                                                            <?= csrf_field() ?>
                                                            <button type="submit" class="btn btn-outline-danger" title="Reject">
                                                                <i class="bi bi-x-lg"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>

                                                    <form action="<?= base_url('admin/requests/' . $req['_id'] . '/delete') ?>" method="POST" onsubmit="return confirm('Delete this request record?');" style="display:inline;">
                                                        <?= csrf_field() ?>
                                                        <button type="submit" class="btn btn-outline-secondary" title="Delete">
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

<?php require __DIR__ . '/../partials/footer.php'; ?>
