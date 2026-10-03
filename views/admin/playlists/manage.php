<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/sidebar.php'; ?>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0 fw-bold">Manage Playlist: <span class="text-primary"><?= e($playlist['name']) ?></span></h3>
                    <p class="text-muted small mb-0"><?= count($songs) ?> tracks in playlist</p>
                </div>
                <div class="col-sm-6 text-sm-end mt-2 mt-sm-0">
                    <a href="<?= base_url('admin/playlists') ?>" class="btn btn-outline-secondary btn-sm me-2">
                        <i class="bi bi-arrow-left me-1"></i> Back to Playlists
                    </a>
                    <button class="btn btn-primary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#addSongModal">
                        <i class="bi bi-plus-circle me-1"></i> Add Track to Playlist
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

            <div class="card shadow-sm border-0 bg-body">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="playlist-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 70px;">Order</th>
                                    <th>Track Title</th>
                                    <th>Artist</th>
                                    <th>Album</th>
                                    <th>Duration</th>
                                    <th class="text-end" style="width: 180px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="sortable-playlist">
                                <?php if (empty($songs)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="bi bi-music-note-list fs-1 d-block mb-2 text-secondary"></i>
                                            This playlist is currently empty. Click "Add Track to Playlist" above.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($songs as $s): ?>
                                        <tr class="playlist-row align-middle" draggable="true" data-item-id="<?= $s['playlist_item_id'] ?>">
                                            <td>
                                                <div class="d-flex align-items-center gap-1">
                                                    <span class="drag-handle text-muted cursor-grab" title="Drag to reorder" style="cursor: grab;">
                                                        <i class="bi bi-grip-vertical fs-5"></i>
                                                    </span>
                                                    <span class="badge bg-secondary-subtle text-secondary font-monospace row-pos-badge"><?= $s['position'] ?></span>
                                                </div>
                                            </td>
                                            <td class="fw-semibold text-truncate"><?= e($s['title']) ?></td>
                                            <td><?= e($s['artist']) ?></td>
                                            <td><?= e($s['album'] ?? 'Single') ?></td>
                                            <td><?= format_duration($s['duration'] ?? 0) ?></td>
                                            <td class="text-end">
                                                <div class="btn-group btn-group-sm me-1" role="group">
                                                    <button type="button" class="btn btn-outline-secondary btn-reorder-up" title="Move Up">
                                                        <i class="bi bi-chevron-up"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-secondary btn-reorder-down" title="Move Down">
                                                        <i class="bi bi-chevron-down"></i>
                                                    </button>
                                                </div>
                                                <form action="<?= base_url('admin/playlists/' . $playlist['_id'] . '/remove/' . $s['playlist_item_id']) ?>" method="POST" onsubmit="return confirm('Remove track from playlist?');" style="display:inline;">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove Track">
                                                        <i class="bi bi-x-lg"></i>
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
                <div class="card-footer bg-transparent py-2 d-flex justify-content-between align-items-center">
                    <span class="text-muted small"><i class="bi bi-info-circle me-1"></i> Drag rows or use arrows to rearrange playback order. Changes sync to Auto DJ automatically.</span>
                    <span id="reorder-status-badge" class="badge bg-success-subtle text-success d-none"><i class="bi bi-check2 me-1"></i> Order Saved</span>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Add Track Modal -->
<div class="modal fade" id="addSongModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="<?= base_url('admin/playlists/' . $playlist['_id'] . '/add-song') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-music-note me-2 text-primary"></i> Select Track from Music Library</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Choose Song</label>
                        <select name="song_id" class="form-select" size="10" required>
                            <?php foreach ($allSongs as $song): ?>
                                <option value="<?= $song['_id'] ?>">
                                    <?= e($song['artist']) ?> - <?= e($song['title']) ?> (<?= format_duration($song['duration'] ?? 0) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Add to Playlist</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tbody = document.getElementById('sortable-playlist');
    const statusBadge = document.getElementById('reorder-status-badge');
    const reorderUrl = '<?= base_url('admin/playlists/' . $playlist['_id'] . '/reorder') ?>';
    const csrfToken = '<?= csrf_token() ?>';

    function updatePositions() {
        const rows = tbody.querySelectorAll('.playlist-row');
        const order = [];
        rows.forEach((r, idx) => {
            const badge = r.querySelector('.row-pos-badge');
            if (badge) badge.innerText = idx + 1;
            order.push(r.getAttribute('data-item-id'));
        });
        return order;
    }

    function saveOrder() {
        const order = updatePositions();
        if (order.length === 0) return;

        if (statusBadge) {
            statusBadge.className = 'badge bg-warning-subtle text-warning';
            statusBadge.innerHTML = '<i class="bi bi-arrow-repeat spin me-1"></i> Saving...';
            statusBadge.classList.remove('d-none');
        }

        fetch(reorderUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ order: order, csrf_token: csrfToken })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && statusBadge) {
                statusBadge.className = 'badge bg-success-subtle text-success';
                statusBadge.innerHTML = '<i class="bi bi-check-circle me-1"></i> Rotation Synced!';
                setTimeout(() => {
                    statusBadge.classList.add('d-none');
                }, 3000);
            }
        })
        .catch(err => {
            if (statusBadge) {
                statusBadge.className = 'badge bg-danger-subtle text-danger';
                statusBadge.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i> Save Failed';
            }
        });
    }

    // Up / Down Button Handlers
    tbody.addEventListener('click', function (e) {
        const upBtn = e.target.closest('.btn-reorder-up');
        const downBtn = e.target.closest('.btn-reorder-down');

        if (upBtn) {
            const row = upBtn.closest('tr');
            if (row && row.previousElementSibling && row.previousElementSibling.classList.contains('playlist-row')) {
                tbody.insertBefore(row, row.previousElementSibling);
                saveOrder();
            }
        } else if (downBtn) {
            const row = downBtn.closest('tr');
            if (row && row.nextElementSibling && row.nextElementSibling.classList.contains('playlist-row')) {
                tbody.insertBefore(row.nextElementSibling, row);
                saveOrder();
            }
        }
    });

    // Native Drag and Drop
    let draggedRow = null;
    tbody.querySelectorAll('.playlist-row').forEach(row => {
        row.addEventListener('dragstart', function (e) {
            draggedRow = this;
            this.classList.add('table-active');
            e.dataTransfer.effectAllowed = 'move';
        });

        row.addEventListener('dragend', function () {
            this.classList.remove('table-active');
            draggedRow = null;
            saveOrder();
        });

        row.addEventListener('dragover', function (e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            if (draggedRow && draggedRow !== this) {
                const rect = this.getBoundingClientRect();
                const next = (e.clientY - rect.top) / (rect.bottom - rect.top) > 0.5;
                tbody.insertBefore(draggedRow, next ? this.nextSibling : this);
            }
        });
    });
});
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
