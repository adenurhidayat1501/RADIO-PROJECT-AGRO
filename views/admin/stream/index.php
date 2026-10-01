<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/sidebar.php'; ?>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0 fw-bold">Stream Link Generator</h3>
            <p class="text-muted small">Public broadcast URLs, M3U playlists, and PLS tune-in files for listeners and third-party radio directories</p>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="row g-4">
                <!-- Stream Generator Box -->
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0 bg-body">
                        <div class="card-header bg-transparent border-bottom fw-bold d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-link-45deg text-primary me-2"></i> Generated Broadcast Stream Links</span>
                            <span class="badge bg-<?= $status['online'] ? 'success' : 'danger' ?>">
                                <?= $status['online'] ? 'STREAM ACTIVE' : 'STREAM OFFLINE' ?>
                            </span>
                        </div>
                        <div class="card-body p-4">
                            <!-- 1. Direct Stream URL -->
                            <div class="mb-4">
                                <label class="form-label fw-bold text-uppercase small text-muted">Direct Audio Stream URL</label>
                                <div class="input-group">
                                    <input type="text" id="stream-url-input" class="form-control font-monospace" value="<?= e($streamUrl) ?>" readonly>
                                    <button class="btn btn-primary btn-copy" data-target="stream-url-input">
                                        <i class="bi bi-clipboard me-1"></i> COPY STREAM URL
                                    </button>
                                </div>
                                <div class="form-text small">Use for HTML5 web audio players, Android/iOS native audio streams, or media apps.</div>
                            </div>

                            <!-- 2. M3U Playlist File -->
                            <div class="mb-4">
                                <label class="form-label fw-bold text-uppercase small text-muted">M3U Playlist File (Winamp, VLC, iTunes)</label>
                                <div class="input-group">
                                    <input type="text" id="m3u-url-input" class="form-control font-monospace" value="<?= e($m3uUrl) ?>" readonly>
                                    <button class="btn btn-outline-secondary btn-copy" data-target="m3u-url-input">
                                        <i class="bi bi-clipboard me-1"></i> COPY M3U
                                    </button>
                                    <a href="<?= e($m3uUrl) ?>" class="btn btn-outline-primary" download="stream.m3u">
                                        <i class="bi bi-download"></i>
                                    </a>
                                </div>
                            </div>

                            <!-- 3. PLS Playlist File -->
                            <div class="mb-4">
                                <label class="form-label fw-bold text-uppercase small text-muted">PLS Playlist File (SHOUTcast / Icecast tune-in)</label>
                                <div class="input-group">
                                    <input type="text" id="pls-url-input" class="form-control font-monospace" value="<?= e($plsUrl) ?>" readonly>
                                    <button class="btn btn-outline-secondary btn-copy" data-target="pls-url-input">
                                        <i class="bi bi-clipboard me-1"></i> COPY PLS
                                    </button>
                                    <a href="<?= e($plsUrl) ?>" class="btn btn-outline-primary" download="stream.pls">
                                        <i class="bi bi-download"></i>
                                    </a>
                                </div>
                            </div>

                            <!-- HTML Embed Code -->
                            <div class="mt-4 pt-3 border-top">
                                <label class="form-label fw-bold text-uppercase small text-muted">HTML5 Embed Player Code</label>
                                <div class="position-relative">
                                    <textarea id="embed-code" class="form-control font-monospace small" rows="2" readonly><audio controls autoplay src="<?= e($streamUrl) ?>"></audio></textarea>
                                    <button class="btn btn-sm btn-outline-dark btn-copy position-absolute end-0 top-0 m-2" data-target="embed-code">
                                        <i class="bi bi-code-slash me-1"></i> Copy Embed
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Live Stream Test Player Card -->
                <div class="col-lg-4">
                    <div class="card shadow-sm border-0 bg-body">
                        <div class="card-header bg-transparent border-bottom fw-bold">
                            <i class="bi bi-speaker text-primary me-2"></i> Stream Monitor Player
                        </div>
                        <div class="card-body text-center p-4">
                            <div class="p-4 rounded-circle bg-primary bg-opacity-10 d-inline-flex mb-3 text-primary">
                                <i class="bi bi-broadcast fs-1"></i>
                            </div>
                            <h5 class="fw-bold mb-1"><?= e($station['name'] ?? 'Radio Agro') ?></h5>
                            <p class="text-muted small"><?= e($station['genre'] ?? 'Various') ?> • <?= $station['stream']['bitrate'] ?? 128 ?> kbps</p>

                            <audio id="test-audio" class="w-100 mt-2 mb-3" controls preload="none">
                                <source src="<?= e($streamUrl) ?>" type="audio/mpeg">
                                Your browser does not support audio element.
                            </audio>

                            <div class="small text-muted border-top pt-2">
                                Mountpoint: <strong><?= e($station['stream']['mountpoint'] ?? '/live') ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.querySelectorAll('.btn-copy').forEach(btn => {
    btn.addEventListener('click', function () {
        const targetId = this.getAttribute('data-target');
        const input = document.getElementById(targetId);
        if (input) {
            navigator.clipboard.writeText(input.value).then(() => {
                const oldHtml = this.innerHTML;
                this.innerHTML = '<i class="bi bi-check2"></i> COPIED!';
                this.classList.add('btn-success');
                setTimeout(() => {
                    this.innerHTML = oldHtml;
                    this.classList.remove('btn-success');
                }, 2000);
            });
        }
    });
});
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
