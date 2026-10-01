<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/sidebar.php'; ?>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0 fw-bold">Create System User</h3>
            <p class="text-muted small">Provision admin panel account with role permissions</p>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-7">
                    <div class="card shadow-sm border-0 bg-body">
                        <div class="card-body p-4">
                            <form action="<?= base_url('admin/users/create') ?>" method="POST">
                                <?= csrf_field() ?>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Username</label>
                                    <input type="text" name="username" class="form-control" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Display Name</label>
                                    <input type="text" name="display_name" class="form-control" placeholder="Full name or station nickname">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Email Address</label>
                                    <input type="email" name="email" class="form-control">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Password</label>
                                    <input type="password" name="password" class="form-control" required>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Role</label>
                                    <select name="role" class="form-select">
                                        <option value="admin">Administrator (Full Control)</option>
                                        <option value="manager">Manager (Playlists, Library & Schedules)</option>
                                        <option value="dj">DJ (Studio, Requests & Live)</option>
                                        <option value="viewer">Viewer (Read Only Metrics)</option>
                                    </select>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary px-4">Create Account</button>
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
