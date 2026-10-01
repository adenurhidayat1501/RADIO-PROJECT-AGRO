<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/sidebar.php'; ?>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0 fw-bold">Create New Playlist</h3>
            <p class="text-muted small">Configure playlist behavior, shuffle, and crossfade settings</p>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-7">
                    <div class="card shadow-sm border-0 bg-body">
                        <div class="card-body p-4">
                            <form action="<?= base_url('admin/playlists/create') ?>" method="POST">
                                <?= csrf_field() ?>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Playlist Name</label>
                                    <input type="text" name="name" class="form-control" placeholder="e.g. Morning Pertanian Mandiri" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Description</label>
                                    <textarea name="description" class="form-control" rows="2" placeholder="Brief note about the show or musical genre..."></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Playlist Type</label>
                                    <select name="type" class="form-select">
                                        <option value="music">General Music Rotation</option>
                                        <option value="show">Special Show Programming</option>
                                        <option value="jingles">Station Identifiers</option>
                                    </select>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="shuffle" id="shuffleCheck" checked>
                                            <label class="form-check-label fw-semibold" for="shuffleCheck">Randomize / Shuffle Order</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="crossfade" id="crossfadeCheck" checked>
                                            <label class="form-check-label fw-semibold" for="crossfadeCheck">Smart Crossfade Transitions</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-plus-lg me-1"></i> Save & Add Tracks</button>
                                    <a href="<?= base_url('admin/playlists') ?>" class="btn btn-outline-secondary">Cancel</a>
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
