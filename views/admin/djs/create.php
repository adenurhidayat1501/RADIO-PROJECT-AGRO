<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/sidebar.php'; ?>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0 fw-bold">Create DJ Account</h3>
            <p class="text-muted small">Provision Icecast / Harbor live broadcast credentials</p>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <?php if ($err = flash('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= e($err) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-7">
                    <div class="card shadow-sm border-0 bg-body">
                        <div class="card-body p-4">
                            <form action="<?= base_url('admin/djs/create') ?>" method="POST">
                                <?= csrf_field() ?>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">DJ Display Name</label>
                                    <input type="text" name="display_name" class="form-control" placeholder="e.g. Kang Dado / DJ Agro" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Icecast Source Username</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                                        <input type="text" name="icecast_username" class="form-control" placeholder="e.g. dj_dado" required>
                                    </div>
                                    <div class="form-text small">Used when logging into Mixxx, BUTT, or OBS encoder.</div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Icecast Source Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-key"></i></span>
                                        <input type="text" name="icecast_password" class="form-control" placeholder="Leave blank to generate random secure password">
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check2-circle me-1"></i> Provision DJ Credentials</button>
                                    <a href="<?= base_url('admin/djs') ?>" class="btn btn-outline-secondary">Cancel</a>
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
