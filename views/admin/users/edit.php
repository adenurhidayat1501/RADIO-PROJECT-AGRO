<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/sidebar.php'; ?>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0 fw-bold">Edit User: <?= e($user['username']) ?></h3>
            <p class="text-muted small">Update user credentials, role permissions, and access status</p>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-7">
                    <div class="card shadow-sm border-0 bg-body">
                        <div class="card-body p-4">
                            <form action="<?= base_url('admin/users/' . $user['_id'] . '/edit') ?>" method="POST">
                                <?= csrf_field() ?>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Display Name</label>
                                    <input type="text" name="display_name" class="form-control" value="<?= e($user['display_name'] ?? '') ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Email Address</label>
                                    <input type="email" name="email" class="form-control" value="<?= e($user['email'] ?? '') ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Reset Password (leave empty to keep current)</label>
                                    <input type="password" name="password" class="form-control" placeholder="New password">
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Role</label>
                                        <select name="role" class="form-select" <?= $user['username'] === 'admin' ? 'disabled' : '' ?>>
                                            <option value="admin" <?= ($user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrator</option>
                                            <option value="manager" <?= ($user['role'] ?? '') === 'manager' ? 'selected' : '' ?>>Manager</option>
                                            <option value="dj" <?= ($user['role'] ?? '') === 'dj' ? 'selected' : '' ?>>DJ</option>
                                            <option value="viewer" <?= ($user['role'] ?? '') === 'viewer' ? 'selected' : '' ?>>Viewer</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Account Status</label>
                                        <select name="status" class="form-select" <?= $user['username'] === 'admin' ? 'disabled' : '' ?>>
                                            <option value="active" <?= ($user['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                                            <option value="inactive" <?= ($user['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary px-4">Update Profile</button>
                                    <a href="<?= base_url('admin/users') ?>" class="btn btn-outline-secondary">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../partials/footer.php'; ?>
