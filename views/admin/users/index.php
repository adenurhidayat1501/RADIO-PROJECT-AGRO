<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/sidebar.php'; ?>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0 fw-bold">User Management & Roles</h3>
                    <p class="text-muted small mb-0">Manage console administrators, station managers, and station staff</p>
                </div>
                <div class="col-sm-6 text-sm-end mt-2 mt-sm-0">
                    <a href="<?= base_url('admin/users/create') ?>" class="btn btn-primary btn-sm shadow-sm">
                        <i class="bi bi-person-plus me-1"></i> Create User
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

            <?php if ($err = flash('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= e($err) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0 bg-body">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle bg-primary text-white me-2">
                                                    <?= strtoupper(substr($u['username'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <span class="fw-semibold d-block"><?= e($u['display_name'] ?? $u['username']) ?></span>
                                                    <small class="text-muted">@<?= e($u['username']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= e($u['email'] ?: 'No email') ?></td>
                                        <td>
                                            <span class="badge bg-<?= match($u['role'] ?? 'viewer') {
                                                'admin' => 'danger',
                                                'manager' => 'primary',
                                                'dj' => 'success',
                                                default => 'secondary',
                                            } ?>">
                                                <?= strtoupper($u['role'] ?? 'viewer') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= ($u['status'] ?? 'active') === 'active' ? 'success-subtle text-success' : 'danger-subtle text-danger' ?>">
                                                <?= strtoupper($u['status'] ?? 'active') ?>
                                            </span>
                                        </td>
                                        <td><small class="text-muted"><?= e($u['created_at']) ?></small></td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= base_url('admin/users/' . $u['_id'] . '/edit') ?>" class="btn btn-outline-secondary">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <?php if ($u['username'] !== 'admin'): ?>
                                                    <form action="<?= base_url('admin/users/' . $u['_id'] . '/delete') ?>" method="POST" onsubmit="return confirm('Delete this user?');" style="display:inline;">
                                                        <?= csrf_field() ?>
                                                        <button type="submit" class="btn btn-outline-danger">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../partials/footer.php'; ?>
