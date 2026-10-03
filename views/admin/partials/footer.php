    <footer class="app-footer">
        <div class="float-end d-none d-sm-inline">
            <b>Icecast2 + Liquidsoap</b> Broadcast Engine
        </div>
        <strong>&copy; <?= date('Y') ?> <a href="<?= base_url() ?>" class="text-decoration-none"><?= e(config('radio.station.name', 'Radio Agro')) ?></a>.</strong> All rights reserved.
    </footer>
</div>
<!-- ./app-wrapper -->

<!-- Hidden Audio Player for In-Panel Monitoring -->
<audio id="global-radio-audio" preload="none" src="<?= e(\App\Services\StreamGeneratorService::getStreamUrl()) ?>"></audio>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE 4 JS -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta2/dist/js/adminlte.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Audio live preview toggle logic
document.addEventListener('DOMContentLoaded', function () {
    const audio = document.getElementById('global-radio-audio');
    const playBtn = document.getElementById('nav-play-btn');
    const nowPlayingText = document.getElementById('nav-now-playing');

    if (playBtn && audio) {
        playBtn.addEventListener('click', function () {
            if (audio.paused) {
                // Bust cache on stream connect to prevent buffer lag
                let streamSrc = '<?= e(\App\Services\StreamGeneratorService::getStreamUrl()) ?>';
                if (!streamSrc || streamSrc.includes('127.0.0.1') || streamSrc.includes('localhost')) {
                    streamSrc = window.location.origin + '/live';
                } else if (window.location.protocol === 'https:' && streamSrc.startsWith('http:')) {
                    streamSrc = streamSrc.replace(/^http:/, 'https:');
                }
                const sep = streamSrc.includes('?') ? '&' : '?';
                audio.src = streamSrc + sep + 't=' + Date.now();
                audio.play().then(() => {
                    playBtn.innerHTML = '<i class="bi bi-stop-fill text-danger"></i>';
                }).catch(err => {
                    console.warn('Admin audio play initial attempt failed, retrying origin /live:', err);
                    audio.src = window.location.origin + '/live?t=' + Date.now();
                    audio.play().then(() => {
                        playBtn.innerHTML = '<i class="bi bi-stop-fill text-danger"></i>';
                    }).catch(retryErr => {
                        console.error('Admin audio play blocked:', retryErr);
                    });
                });
            } else {
                audio.pause();
                audio.removeAttribute('src');
                playBtn.innerHTML = '<i class="bi bi-play-fill"></i>';
            }
        });
    }

    // Poll live now playing status every 5 seconds
    function updateRadioStatus() {
        fetch('<?= base_url('api/radio/status') ?>')
            .then(res => res.json())
            .then(data => {
                if (nowPlayingText && data.now_playing) {
                    nowPlayingText.innerText = (data.now_playing.artist || 'Radio Agro') + ' - ' + (data.now_playing.title || 'Live Stream');
                }
            })
            .catch(() => {});
    }

    updateRadioStatus();
    setInterval(updateRadioStatus, 5000);
});
</script>
</body>
</html>
