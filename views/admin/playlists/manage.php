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
                                    <th style="width: 50px;">Pos</th>
                                    <th>Track Title</th>
                                    <th>Artist</th>
                                    <th>Album</th>
                                    <th>Duration</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($songs)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="bi bi-music-note-list fs-1 d-block mb-2 text-secondary"></i>
                                            This playlist is currently empty. Click "Add Track to Playlist" above.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($songs as $s): ?>
                                        <tr data-item-id="<?= $s['playlist_item_id'] ?>">
                                            <td><span class="badge bg-secondary-subtle text-secondary font-monospace"><?= $s['position'] ?></span></td>
                                            <td class="fw-semibold text-truncate"><?= e($s['title']) ?></td>
                                            <td><?= e($s['artist']) ?></td>
                                            <td><?= e($s['album'] ?? 'Single') ?></td>
                                            <td><?= format_duration($s['duration'] ?? 0) ?></td>
                                            <td class="text-end">
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

<?php require __DIR__ . '/../partials/footer.php'; ?>
