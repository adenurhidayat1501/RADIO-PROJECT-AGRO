<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/sidebar.php'; ?>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0 fw-bold">System & Automation Diagnostic Logs</h3>
                    <p class="text-muted small mb-0">Inspect streaming engine, scheduler worker, and application log output</p>
                </div>
                <div class="col-sm-6 text-sm-end mt-2 mt-sm-0">
                    <a href="<?= base_url('admin/logs?channel=' . $currentChannel) ?>" class="btn btn-outline-primary btn-sm me-2">
                        <i class="bi bi-arrow-clockwise me-1"></i> Refresh Log
                    </a>
                    <form action="<?= base_url('admin/logs/clear/' . $currentChannel) ?>" method="POST" onsubmit="return confirm('Clear this log channel?');" style="display:inline;">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-trash me-1"></i> Clear Log
                        </button>
                    </form>
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

            <ul class="nav nav-tabs mb-3">
                <li class="nav-item">
                    <a class="nav-link <?= $currentChannel === 'app' ? 'active fw-bold' : '' ?>" href="<?= base_url('admin/logs?channel=app') ?>">
                        <i class="bi bi-window-stack me-1"></i> app.log
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentChannel === 'stream' ? 'active fw-bold' : '' ?>" href="<?= base_url('admin/logs?channel=stream') ?>">
                        <i class="bi bi-broadcast me-1"></i> stream.log
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentChannel === 'scheduler' ? 'active fw-bold' : '' ?>" href="<?= base_url('admin/logs?channel=scheduler') ?>">
                        <i class="bi bi-clock-history me-1"></i> scheduler.log
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentChannel === 'radio' ? 'active fw-bold' : '' ?>" href="<?= base_url('admin/logs?channel=radio') ?>">
                        <i class="bi bi-robot me-1"></i> radio.log
                    </a>
                </li>
            </ul>

            <div class="card shadow-sm border-0 bg-dark text-light font-monospace small">
                <div class="card-header bg-dark border-secondary border-opacity-25 d-flex justify-content-between align-items-center">
                    <span class="text-secondary">Viewing latest 150 lines from <code>storage/logs/<?= e($currentChannel) ?>.log</code></span>
                    <span class="badge bg-secondary"><?= count($lines) ?> entries</span>
                </div>
                <div class="card-body p-3" style="max-height: 550px; overflow-y: auto;">
                    <?php if (empty($lines)): ?>
                        <div class="text-muted text-center py-4">No log lines recorded in this channel yet.</div>
                    <?php else: ?>
                        <?php foreach ($lines as $line): ?>
                            <?php
                                $color = 'text-light';
                                if (str_contains($line, '[ERROR]')) $color = 'text-danger fw-bold';
                                elseif (str_contains($line, '[WARNING]')) $color = 'text-warning';
                                elseif (str_contains($line, '[INFO]')) $color = 'text-info';
                            ?>
                            <div class="<?= $color ?> mb-1" style="white-space: pre-wrap; word-break: break-all;"><?= e($line) ?></div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../partials/footer.php'; ?>
