<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/sidebar.php'; ?>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0 fw-bold">Broadcast Weekly Schedule</h3>
                    <p class="text-muted small mb-0">Automate Auto DJ playlist switches and Live DJ broadcast time slots (Timezone: <?= e(config('radio.station.timezone', 'Asia/Jakarta')) ?>)</p>
                </div>
                <div class="col-sm-6 text-sm-end mt-2 mt-sm-0">
                    <button class="btn btn-primary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#newSlotModal">
                        <i class="bi bi-calendar-plus me-1"></i> Add Show Time Slot
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

            <!-- Days Navigation Tabs -->
            <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                <?php foreach ($days as $dayNum => $dayLabel): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $dayNum === (int) date('N') ? 'active' : '' ?>" id="pills-day-<?= $dayNum ?>-tab" data-bs-toggle="pill" data-bs-target="#pills-day-<?= $dayNum ?>" type="button" role="tab">
                            <?= e($dayLabel) ?>
                            <?php if ($dayNum === (int) date('N')): ?>
                                <span class="badge bg-danger ms-1">TODAY</span>
                            <?php endif; ?>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="tab-content" id="pills-tabContent">
                <?php foreach ($days as $dayNum => $dayLabel): ?>
                    <div class="tab-pane fade <?= $dayNum === (int) date('N') ? 'show active' : '' ?>" id="pills-day-<?= $dayNum ?>" role="tabpanel">
                        <div class="card shadow-sm border-0 bg-body">
                            <div class="card-header bg-transparent border-bottom fw-bold">
                                <?= e($dayLabel) ?> Schedule
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 140px;">Time</th>
                                                <th>Show / Program Name</th>
                                                <th>Mode</th>
                                                <th>Playlist / DJ Assignment</th>
                                                <th class="text-end">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($grid[$dayNum])): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center py-4 text-muted">
                                                        No dedicated shows scheduled for this day. Radio runs normal Auto DJ rotation.
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($grid[$dayNum] as $slot): ?>
                                                    <tr>
                                                        <td class="fw-bold font-monospace text-primary">
                                                            <?= e($slot['start_time']) ?> - <?= e($slot['end_time']) ?>
                                                        </td>
                                                        <td class="fw-semibold"><?= e($slot['name']) ?></td>
                                                        <td>
                                                            <span class="badge bg-<?= ($slot['mode'] ?? 'auto_dj') === 'live' ? 'danger' : 'primary' ?>">
                                                                <?= strtoupper($slot['mode'] ?? 'auto_dj') ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <small class="text-muted">
                                                                <?= ($slot['mode'] ?? 'auto_dj') === 'live' ? 'Live Studio Harbor' : 'Scheduled Playlist Rotation' ?>
                                                            </small>
                                                        </td>
                                                        <td class="text-end">
                                                            <form action="<?= base_url('admin/schedules/' . $slot['_id'] . '/delete') ?>" method="POST" onsubmit="return confirm('Remove this schedule slot?');" style="display:inline;">
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
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</main>

<!-- Add Schedule Slot Modal -->
<div class="modal fade" id="newSlotModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= base_url('admin/schedules') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add Show / Broadcast Slot</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Show Title</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Suara Petani Mandiri Pagi" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Day</label>
                            <select name="day" class="form-select">
                                <?php foreach ($days as $num => $lbl): ?>
                                    <option value="<?= $num ?>" <?= $num === (int) date('N') ? 'selected' : '' ?>><?= $lbl ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Start Time</label>
                            <input type="time" name="start_time" class="form-control" value="08:00" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">End Time</label>
                            <input type="time" name="end_time" class="form-control" value="10:00" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Broadcast Mode</label>
                        <select name="mode" class="form-select">
                            <option value="auto_dj">Auto DJ (Playlist Driven)</option>
                            <option value="live">Live Studio (DJ on Air)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Target Playlist (For Auto DJ)</label>
                        <select name="playlist_id" class="form-select">
                            <option value="">-- Default Library Rotation --</option>
                            <?php foreach ($playlists as $pl): ?>
                                <option value="<?= $pl['_id'] ?>"><?= e($pl['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Schedule Show</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
