<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/sidebar.php'; ?>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0 fw-bold">DJ Accounts & Harbor Credentials</h3>
                    <p class="text-muted small mb-0">Authorized broadcasters permitted to connect live to Liquidsoap Harbor</p>
                </div>
                <div class="col-sm-6 text-sm-end mt-2 mt-sm-0">
                    <a href="<?= base_url('admin/djs/create') ?>" class="btn btn-primary btn-sm shadow-sm">
                        <i class="bi bi-person-plus-fill me-1"></i> Add DJ Account
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

            <?php if ($modal = flash('credentials_modal')): ?>
                <!-- Freshly generated credentials popup -->
                <div class="card border-primary mb-4 shadow">
                    <div class="card-header bg-primary text-white fw-bold">
                        <i class="bi bi-shield-lock-fill me-2"></i> NEW DJ CREDENTIALS GENERATED (COPY NOW)
                    </div>
                    <div class="card-body">
                        <p class="text-danger fw-bold small">
                            For security, Icecast/Harbor passwords are never shown again after initial creation. Please deliver these details to the DJ:
                        </p>
                        <div class="row g-2 font-monospace bg-light p-3 rounded border">
                            <div class="col-md-3"><strong>DJ Name:</strong> <?= e($modal['display_name']) ?></div>
                            <div class="col-md-3"><strong>Icecast User:</strong> <?= e($modal['username']) ?></div>
                            <div class="col-md-3"><strong>Password:</strong> <span class="badge bg-dark"><?= e($modal['password']) ?></span></div>
                            <div class="col-md-3"><strong>Mount:</strong> <?= e($modal['mount']) ?> (Port <?= e($modal['port']) ?>)</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0 bg-body">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>DJ Display Name</th>
                                    <th>Icecast Username</th>
                                    <th>Mountpoint</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($djs)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="bi bi-person-video3 fs-1 d-block mb-2 text-secondary"></i>
                                            No DJ accounts configured. Click "Add DJ Account" to authorize a broadcaster.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($djs as $dj): ?>
                                        <tr>
                                            <td class="fw-bold">
                                                <div class="avatar-circle bg-primary-subtle text-primary me-2">
                                                    <?= strtoupper(substr($dj['display_name'] ?? 'D', 0, 1)) ?>
                                                </div>
                                                <?= e($dj['display_name']) ?>
                                            </td>
                                            <td class="font-monospace text-primary"><?= e($dj['icecast_username']) ?></td>
                                            <td><span class="badge bg-secondary-subtle text-secondary font-monospace"><?= e($dj['mountpoint'] ?? '/live-dj') ?></span></td>
                                            <td>
                                                <form action="<?= base_url('admin/djs/' . $dj['_id'] . '/toggle') ?>" method="POST" style="display:inline;">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-sm btn-<?= ($dj['status'] ?? 'active') === 'active' ? 'success' : 'secondary' ?>">
                                                        <?= strtoupper($dj['status'] ?? 'active') ?>
                                                    </button>
                                                </form>
                                            </td>
                                            <td class="small text-muted"><?= e($dj['created_at']) ?></td>
                                            <td class="text-end">
                                                <form action="<?= base_url('admin/djs/' . $dj['_id'] . '/delete') ?>" method="POST" onsubmit="return confirm('Revoke and delete this DJ account?');" style="display:inline;">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-trash"></i> Revoke
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

<?php require __DIR__ . '/../partials/footer.php'; ?>
