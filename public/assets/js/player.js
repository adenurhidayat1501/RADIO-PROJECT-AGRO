/**
 * Radio Agro - Pure Vanilla JS Interactive Stream Player
 * Handles HTML5 Live Audio, Dynamic Metadata Polling & Song Requests
 */

document.addEventListener('DOMContentLoaded', function () {
    const audio = document.getElementById('live-stream-audio');
    const playBtn = document.getElementById('btn-main-play');
    const playIcon = document.getElementById('main-play-icon');
    const playText = document.getElementById('main-play-text');
    const vinylDisc = document.getElementById('vinyl-disc');
    const vBars = document.querySelectorAll('.v-bar');
    const volumeSlider = document.getElementById('volume-slider');

    const npTitle = document.getElementById('live-np-title');
    const npArtist = document.getElementById('live-np-artist');
    const npListeners = document.getElementById('live-listener-count');
    const upNextText = document.getElementById('live-up-next');

    let isPlaying = false;
    const streamSource = audio.getAttribute('data-src');

    // 1. Play / Pause Stream Toggle
    if (playBtn && audio) {
        playBtn.addEventListener('click', function () {
            if (audio.paused) {
                // Bust cache to attach to current live edge
                audio.src = streamSource + (streamSource.includes('?') ? '&' : '?') + 't=' + Date.now();
                audio.play().then(() => {
                    isPlaying = true;
                    playBtn.classList.add('playing');
                    playIcon.className = 'bi bi-stop-fill fs-3';
                    playText.innerText = 'STOP BROADCAST';
                    if (vinylDisc) vinylDisc.classList.add('spinning');
                    vBars.forEach(b => b.classList.add('active'));
                }).catch(err => {
                    console.error('Playback could not be started:', err);
                });
            } else {
                audio.pause();
                audio.src = '';
                isPlaying = false;
                playBtn.classList.remove('playing');
                playIcon.className = 'bi bi-play-fill fs-3';
                playText.innerText = 'LISTEN LIVE';
                if (vinylDisc) vinylDisc.classList.remove('spinning');
                vBars.forEach(b => b.classList.remove('active'));
            }
        });
    }

    // 2. Volume Controller
    if (volumeSlider && audio) {
        volumeSlider.addEventListener('input', function () {
            audio.volume = parseFloat(this.value);
        });
    }

    // 3. Periodic Now Playing & Listener Polling (Every 5 seconds)
    function pollRadioTelemetry() {
        fetch('/api/radio/now-playing')
            .then(res => res.json())
            .then(data => {
                if (data && data.now_playing) {
                    if (npTitle && data.now_playing.title) {
                        npTitle.innerText = data.now_playing.title;
                    }
                    if (npArtist && data.now_playing.artist) {
                        npArtist.innerText = data.now_playing.artist;
                    }
                }
                if (npListeners && typeof data.listeners !== 'undefined') {
                    npListeners.innerText = data.listeners.toLocaleString();
                }
            })
            .catch(() => {});

        // Fetch Next Song Preview
        fetch('/api/radio/next-song')
            .then(res => res.json())
            .then(data => {
                if (data && data.next_song && upNextText) {
                    upNextText.innerText = data.next_song.artist + ' - ' + data.next_song.title;
                }
            })
            .catch(() => {});
    }

    pollRadioTelemetry();
    setInterval(pollRadioTelemetry, 5000);

    // 4. Song Request Live Search
    const searchInput = document.getElementById('req-search-song');
    const songResults = document.getElementById('req-song-results');
    const selectedSongIdInput = document.getElementById('req-selected-song-id');
    const selectedSongDisplay = document.getElementById('req-selected-song-display');
    const requestForm = document.getElementById('song-request-form');
    const requestAlert = document.getElementById('req-alert');

    let searchTimeout = null;

    if (searchInput && songResults) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            const query = this.value.trim();

            if (query.length < 2) {
                songResults.innerHTML = '';
                return;
            }

            searchTimeout = setTimeout(() => {
                songResults.innerHTML = '<div class="p-2 text-center text-muted small"><i class="bi bi-hourglass-split"></i> Searching music library...</div>';

                fetch('/api/songs?q=' + encodeURIComponent(query))
                    .then(res => res.json())
                    .then(data => {
                        const songs = data.songs || [];
                        if (songs.length === 0) {
                            songResults.innerHTML = '<div class="p-2 text-center text-muted small">No tracks found matching your query.</div>';
                            return;
                        }

                        let html = '<div class="list-group list-group-flush">';
                        songs.forEach(s => {
                            html += `
                                <button type="button" class="list-group-item list-group-item-action bg-dark text-white border-secondary select-song-item py-2" data-id="${s.id}" data-name="${s.artist} - ${s.title}">
                                    <div class="fw-semibold">${s.title}</div>
                                    <small class="text-muted">${s.artist} • ${s.duration}</small>
                                </button>
                            `;
                        });
                        html += '</div>';
                        songResults.innerHTML = html;

                        // Attach select event
                        document.querySelectorAll('.select-song-item').forEach(btn => {
                            btn.addEventListener('click', function () {
                                const id = this.getAttribute('data-id');
                                const name = this.getAttribute('data-name');
                                selectedSongIdInput.value = id;
                                selectedSongDisplay.innerText = name;
                                selectedSongDisplay.classList.remove('d-none');
                                songResults.innerHTML = '';
                                searchInput.value = '';
                            });
                        });
                    })
                    .catch(() => {
                        songResults.innerHTML = '<div class="p-2 text-center text-danger small">Error searching songs.</div>';
                    });
            }, 300);
        });
    }

    // 5. Submit Song Request via AJAX
    if (requestForm) {
        requestForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const name = document.getElementById('req-name').value.trim();
            const songId = selectedSongIdInput.value;
            const message = document.getElementById('req-message').value.trim();

            if (!name) {
                showReqAlert('Please enter your name.', 'danger');
                return;
            }

            if (!songId) {
                showReqAlert('Please select a song from the search results.', 'danger');
                return;
            }

            const formData = new FormData();
            formData.append('name', name);
            formData.append('song_id', songId);
            formData.append('message', message);

            const submitBtn = document.getElementById('btn-submit-req');
            submitBtn.disabled = true;
            submitBtn.innerText = 'Submitting Request...';

            fetch('/api/song-request', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.innerText = 'Send Song Request';

                if (data.success) {
                    showReqAlert(data.message, 'success');
                    requestForm.reset();
                    selectedSongIdInput.value = '';
                    selectedSongDisplay.classList.add('d-none');
                } else {
                    showReqAlert(data.error || 'Failed to submit request.', 'danger');
                }
            })
            .catch(err => {
                submitBtn.disabled = false;
                submitBtn.innerText = 'Send Song Request';
                showReqAlert('Network or server error.', 'danger');
            });
        });
    }

    function showReqAlert(msg, type) {
        if (!requestAlert) return;
        requestAlert.className = `alert alert-${type} small py-2`;
        requestAlert.innerText = msg;
        requestAlert.classList.remove('d-none');
    }
});
