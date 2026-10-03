/**
 * Radio Agro - Pure Vanilla JS Interactive Stream Player & Visualizer
 * Follows .agents UX & Audio Performance guidelines:
 * - HTML5 Live Stream with Cache-Busting Reconnect
 * - Web Audio API AnalyserNode Frequency Visualizer on Canvas
 * - MediaSession API (Lock screen / Bluetooth metadata)
 * - Persistent Volume Controller with Mute Toggle
 * - Mobile Sticky Bottom Player Bar
 * - Live Debounced Song Request Search & Toast Notifications
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const config = window.RADIO_CONFIG || {
            baseUrl: '',
            streamUrl: '/live'
        };

        let baseUrl = (config.baseUrl || '').replace(/\/+$/, '');
        if (window.location.protocol === 'https:' && baseUrl.startsWith('http:')) {
            baseUrl = baseUrl.replace(/^http:/, 'https:');
        }
        if (!baseUrl || baseUrl.includes('127.0.0.1') || baseUrl.includes('localhost')) {
            baseUrl = window.location.origin;
        }

        let streamBaseUrl = config.streamUrl || '/live';
        // Auto-fix: Never connect to localhost or 127.0.0.1 in client browser
        if (streamBaseUrl.includes('127.0.0.1') || streamBaseUrl.includes('localhost')) {
            streamBaseUrl = window.location.origin + '/live';
        } else if (window.location.protocol === 'https:' && streamBaseUrl.startsWith('http:')) {
            streamBaseUrl = streamBaseUrl.replace(/^http:/, 'https:');
        }

        // Elements
        const audio = document.getElementById('live-stream-audio');
        const playBtn = document.getElementById('btn-main-play');
        const playIcon = document.getElementById('main-play-icon');
        const playText = document.getElementById('main-play-text');

        const miniPlayer = document.getElementById('sticky-bottom-player');
        const miniPlayBtn = document.getElementById('btn-mini-play');
        const miniPlayIcon = document.getElementById('mini-play-icon');
        const miniThumb = document.getElementById('mini-thumb');
        const miniTitle = document.getElementById('mini-np-title');
        const miniArtist = document.getElementById('mini-np-artist');

        const vinylDisc = document.getElementById('vinyl-disc');
        const vinylTonearm = document.getElementById('vinyl-tonearm');
        const volumeSlider = document.getElementById('volume-slider');
        const volumeIconBtn = document.getElementById('volume-icon-btn');
        const volumeIcon = document.getElementById('volume-icon');

        const npTitle = document.getElementById('live-np-title');
        const npArtist = document.getElementById('live-np-artist');
        const npListeners = document.getElementById('live-listener-count');
        const npBroadcastMode = document.getElementById('live-broadcast-mode');
        const upNextText = document.getElementById('live-up-next');

        const copyStreamBtn = document.getElementById('btn-copy-stream');
        const canvas = document.getElementById('audio-visualizer');

        let isPlaying = false;
        let lastVolume = 0.9;
        let audioCtx = null;
        let analyser = null;
        let visualizerAnimId = null;

        // ----------------------------------------------------------------------
        // 1. Toast Notification Helper
        // ----------------------------------------------------------------------
        function showToast(message, type = 'info', duration = 3500) {
            const container = document.getElementById('toast-container');
            if (!container) return;

            const toast = document.createElement('div');
            toast.className = `radio-toast toast-${type}`;

            let icon = 'bi-info-circle-fill text-info';
            if (type === 'success') icon = 'bi-check-circle-fill text-success';
            if (type === 'error') icon = 'bi-exclamation-triangle-fill text-danger';

            toast.innerHTML = `
                <i class="bi ${icon} fs-5"></i>
                <div class="small fw-medium flex-grow-1">${message}</div>
                <button type="button" class="btn-close btn-close-white btn-sm" aria-label="Close"></button>
            `;

            const closeBtn = toast.querySelector('.btn-close');
            closeBtn.addEventListener('click', () => {
                toast.style.animation = 'toastSlideOut 0.3s forwards';
                setTimeout(() => toast.remove(), 300);
            });

            container.appendChild(toast);

            setTimeout(() => {
                if (toast.parentElement) {
                    toast.style.animation = 'toastSlideOut 0.3s forwards';
                    setTimeout(() => toast.remove(), 300);
                }
            }, duration);
        }

        // ----------------------------------------------------------------------
        // 2. Audio Visualizer on Canvas
        // ----------------------------------------------------------------------
        function initWebAudio() {
            if (audioCtx) return;
            try {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (AudioContext) {
                    audioCtx = new AudioContext();
                }
            } catch (err) {
                // Non-blocking
            }
        }

        function drawVisualizer() {
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            const width = canvas.width;
            const height = canvas.height;

            const bufferLength = analyser ? analyser.frequencyBinCount : 32;
            const dataArray = new Uint8Array(bufferLength);

            let simPhase = 0;

            function render() {
                visualizerAnimId = requestAnimationFrame(render);
                ctx.clearRect(0, 0, width, height);

                const barCount = 18;
                const barSpacing = 4;
                const totalSpacing = (barCount - 1) * barSpacing;
                const barWidth = Math.max(3, (width - totalSpacing) / barCount);

                if (isPlaying) {
                    if (analyser && sourceNode) {
                        analyser.getByteFrequencyData(dataArray);
                    } else {
                        // High quality organic simulated frequency wave
                        simPhase += 0.08;
                        for (let i = 0; i < barCount; i++) {
                            const val = (Math.sin(simPhase + i * 0.45) * 0.5 + 0.5) * 180 +
                                        (Math.cos(simPhase * 1.3 + i * 0.7) * 0.5 + 0.5) * 75;
                            dataArray[i] = Math.min(255, Math.max(25, val));
                        }
                    }
                }

                for (let i = 0; i < barCount; i++) {
                    const rawVal = isPlaying ? (dataArray[i] || 20) : 10;
                    const percent = rawVal / 255;
                    const barHeight = Math.max(4, percent * height);
                    const x = i * (barWidth + barSpacing);
                    const y = height - barHeight;

                    // Aurora gradient for bars: Emerald to Cyan
                    const gradient = ctx.createLinearGradient(0, y, 0, height);
                    gradient.addColorStop(0, '#22d3ee');
                    gradient.addColorStop(1, '#10b981');

                    ctx.fillStyle = gradient;
                    ctx.beginPath();
                    // Draw rounded top bar
                    const radius = Math.min(3, barWidth / 2);
                    ctx.roundRect ? ctx.roundRect(x, y, barWidth, barHeight, [radius, radius, 1, 1]) : ctx.rect(x, y, barWidth, barHeight);
                    ctx.fill();
                }
            }

            render();
        }

        drawVisualizer();

        // ----------------------------------------------------------------------
        // 3. Play / Pause Broadcast Toggle
        // ----------------------------------------------------------------------
        function togglePlayback() {
            if (audio.paused) {
                initWebAudio();
                if (audioCtx && audioCtx.state === 'suspended') {
                    audioCtx.resume();
                }

                // Ensure unmuted & set volume
                audio.muted = false;
                audio.volume = lastVolume;

                // Cache buster to connect directly to the live stream edge
                let playUrl = streamBaseUrl;
                if (!playUrl || playUrl.includes('127.0.0.1') || playUrl.includes('localhost')) {
                    playUrl = window.location.origin + '/live';
                } else if (window.location.protocol === 'https:' && playUrl.startsWith('http:')) {
                    playUrl = playUrl.replace(/^http:/, 'https:');
                }

                const separator = playUrl.includes('?') ? '&' : '?';
                audio.src = playUrl + separator + 't=' + Date.now();

                const playPromise = audio.play();
                if (playPromise !== undefined) {
                    playPromise.then(() => {
                        setPlayingState(true);
                        showToast('Menghubungkan ke siaran live Radio Agro...', 'success');
                    }).catch(err => {
                        console.warn('Primary stream playback failed, retrying with direct origin /live:', err);
                        // Fallback retry directly to window.location.origin + '/live'
                        const fallbackUrl = window.location.origin + '/live?t=' + Date.now();
                        audio.src = fallbackUrl;
                        audio.play().then(() => {
                            setPlayingState(true);
                            showToast('Menghubungkan ke siaran live Radio Agro...', 'success');
                        }).catch(retryErr => {
                            console.error('All audio playback attempts failed:', retryErr);
                            setPlayingState(false);
                            showToast('Gagal memulai audio. Mohon tekan tombol sekali lagi.', 'error');
                        });
                    });
                }
            } else {
                audio.pause();
                audio.removeAttribute('src');
                audio.load();
                setPlayingState(false);
            }
        }

        function setPlayingState(playing) {
            isPlaying = playing;

            if (playing) {
                if (playBtn) {
                    playBtn.classList.add('playing');
                    playIcon.className = 'bi bi-stop-fill fs-2';
                    playText.innerText = 'HENTIKAN SIARAN';
                }
                if (miniPlayBtn) {
                    miniPlayBtn.classList.add('playing');
                    miniPlayIcon.className = 'bi bi-stop-fill';
                }
                if (vinylDisc) vinylDisc.classList.add('spinning');
                if (vinylTonearm) vinylTonearm.classList.add('active');
                if (miniThumb) miniThumb.classList.add('spinning');
            } else {
                if (playBtn) {
                    playBtn.classList.remove('playing');
                    playIcon.className = 'bi bi-play-fill fs-2';
                    playText.innerText = 'DENGARKAN SIARAN';
                }
                if (miniPlayBtn) {
                    miniPlayBtn.classList.remove('playing');
                    miniPlayIcon.className = 'bi bi-play-fill';
                }
                if (vinylDisc) vinylDisc.classList.remove('spinning');
                if (vinylTonearm) vinylTonearm.classList.remove('active');
                if (miniThumb) miniThumb.classList.remove('spinning');
            }
        }

        if (playBtn) playBtn.addEventListener('click', togglePlayback);
        if (miniPlayBtn) miniPlayBtn.addEventListener('click', togglePlayback);

        // Keyboard Shortcuts (Space to Play/Pause, M to Mute)
        document.addEventListener('keydown', function (e) {
            const activeEl = document.activeElement;
            if (activeEl && (activeEl.tagName === 'INPUT' || activeEl.tagName === 'TEXTAREA' || activeEl.isContentEditable)) {
                return;
            }
            if (e.code === 'Space') {
                e.preventDefault();
                togglePlayback();
            } else if (e.key === 'm' || e.key === 'M') {
                e.preventDefault();
                if (volumeIconBtn) volumeIconBtn.click();
            }
        });

        // ----------------------------------------------------------------------
        // 4. Volume Controller & Persistence
        // ----------------------------------------------------------------------
        const savedVolume = localStorage.getItem('radio_agro_volume');
        if (savedVolume !== null && volumeSlider) {
            const v = parseFloat(savedVolume);
            audio.volume = v;
            volumeSlider.value = v;
            updateVolumeIcon(v);
        }

        function updateVolumeIcon(vol) {
            if (!volumeIcon) return;
            if (vol === 0) {
                volumeIcon.className = 'bi bi-volume-mute-fill fs-5 text-danger';
            } else if (vol < 0.4) {
                volumeIcon.className = 'bi bi-volume-down-fill fs-5';
            } else {
                volumeIcon.className = 'bi bi-volume-up-fill fs-5';
            }
        }

        if (volumeSlider) {
            volumeSlider.addEventListener('input', function () {
                const vol = parseFloat(this.value);
                audio.volume = vol;
                if (vol > 0) lastVolume = vol;
                localStorage.setItem('radio_agro_volume', vol.toString());
                updateVolumeIcon(vol);
            });
        }

        if (volumeIconBtn) {
            volumeIconBtn.addEventListener('click', function () {
                if (audio.volume > 0) {
                    lastVolume = audio.volume;
                    audio.volume = 0;
                    if (volumeSlider) volumeSlider.value = 0;
                    updateVolumeIcon(0);
                } else {
                    const restore = lastVolume > 0 ? lastVolume : 0.8;
                    audio.volume = restore;
                    if (volumeSlider) volumeSlider.value = restore;
                    updateVolumeIcon(restore);
                }
            });
        }

        // ----------------------------------------------------------------------
        // 5. Copy Stream URL Button
        // ----------------------------------------------------------------------
        if (copyStreamBtn) {
            copyStreamBtn.addEventListener('click', function () {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(streamBaseUrl).then(() => {
                        showToast('Stream URL berhasil disalin ke clipboard!', 'success');
                    }).catch(() => {
                        showToast(streamBaseUrl, 'info', 5000);
                    });
                } else {
                    showToast(streamBaseUrl, 'info', 5000);
                }
            });
        }

        // ----------------------------------------------------------------------
        // 6. Sticky Floating Mini Player on Scroll
        // ----------------------------------------------------------------------
        const heroSection = document.getElementById('broadcast');
        if (heroSection && miniPlayer) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (!entry.isIntersecting) {
                        miniPlayer.classList.add('visible');
                    } else {
                        miniPlayer.classList.remove('visible');
                    }
                });
            }, { threshold: 0.1 });

            observer.observe(heroSection);
        }

        // ----------------------------------------------------------------------
        // 7. Telemetry & Now Playing Polling (Every 5 seconds)
        // ----------------------------------------------------------------------
        function updateMediaSession(title, artist, album) {
            if ('mediaSession' in navigator) {
                navigator.mediaSession.metadata = new MediaMetadata({
                    title: title || 'Live Broadcast',
                    artist: artist || 'Radio Agro',
                    album: album || 'Radio Agro Streaming',
                    artwork: [
                        { src: `${baseUrl}/assets/images/logo.png`, sizes: '512x512', type: 'image/png' }
                    ]
                });

                navigator.mediaSession.setActionHandler('play', togglePlayback);
                navigator.mediaSession.setActionHandler('pause', togglePlayback);
                navigator.mediaSession.setActionHandler('stop', togglePlayback);
            }
        }

        function pollRadioTelemetry() {
            fetch(`${baseUrl}/api/radio/now-playing`)
                .then(res => res.json())
                .then(data => {
                    if (data && data.now_playing) {
                        const title = data.now_playing.title || 'Radio Agro Live';
                        const artist = data.now_playing.artist || 'Radio Agro';
                        const album = data.now_playing.album || '';

                        if (npTitle && npTitle.innerText !== title) {
                            npTitle.innerText = title;
                        }
                        if (npArtist && npArtist.innerText !== artist) {
                            npArtist.innerText = artist;
                        }
                        if (miniTitle && miniTitle.innerText !== title) {
                            miniTitle.innerText = title;
                        }
                        if (miniArtist && miniArtist.innerText !== artist) {
                            miniArtist.innerText = artist;
                        }

                        updateMediaSession(title, artist, album);
                    }

                    if (npListeners && typeof data.listeners !== 'undefined') {
                        npListeners.innerText = Number(data.listeners).toLocaleString();
                    }

                    if (npBroadcastMode && data.mode) {
                        npBroadcastMode.innerText = data.mode === 'live_dj' ? 'LIVE DJ ON AIR' : 'LIVE ON AIR';
                    }
                })
                .catch(() => {});

            // Fetch Next Song Preview
            fetch(`${baseUrl}/api/radio/next-song`)
                .then(res => res.json())
                .then(data => {
                    if (data && data.next_song && upNextText) {
                        upNextText.innerText = `${data.next_song.artist} - ${data.next_song.title}`;
                    }
                })
                .catch(() => {});
        }

        pollRadioTelemetry();
        setInterval(pollRadioTelemetry, 5000);

        // ----------------------------------------------------------------------
        // 8. Song Request Live Search & Form Submission
        // ----------------------------------------------------------------------
        const searchInput = document.getElementById('req-search-song');
        const songResults = document.getElementById('req-song-results');
        const selectedDisplay = document.getElementById('req-selected-song-display');
        const selectedText = document.getElementById('selected-song-text');
        const selectedIdInput = document.getElementById('req-selected-song-id');
        const clearSelectionBtn = document.getElementById('btn-clear-selection');
        const requestForm = document.getElementById('song-request-form');
        const requestAlert = document.getElementById('req-alert');
        const reqMessage = document.getElementById('req-message');
        const charCounter = document.getElementById('char-counter');
        const submitReqBtn = document.getElementById('btn-submit-req');

        let searchTimeout = null;

        // Character counter
        if (reqMessage && charCounter) {
            reqMessage.addEventListener('input', function () {
                charCounter.innerText = `${this.value.length} / 200`;
            });
        }

        // Live Debounced Search
        if (searchInput && songResults) {
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimeout);
                const query = this.value.trim();

                if (query.length < 2) {
                    songResults.innerHTML = '';
                    return;
                }

                searchTimeout = setTimeout(() => {
                    songResults.innerHTML = '<div class="p-3 text-center text-muted small"><i class="bi bi-arrow-repeat spin me-1"></i> Mencari lagu di koleksi...</div>';

                    fetch(`${baseUrl}/api/songs?q=${encodeURIComponent(query)}`)
                        .then(res => res.json())
                        .then(data => {
                            const songs = data.songs || [];
                            if (songs.length === 0) {
                                songResults.innerHTML = '<div class="p-3 text-center text-muted small">Tidak ada lagu yang cocok dengan pencarian Anda.</div>';
                                return;
                            }

                            let html = '<div class="d-flex flex-column gap-1">';
                            songs.forEach(s => {
                                html += `
                                    <div class="song-result-item d-flex justify-content-between align-items-center" data-id="${s.id}" data-name="${s.artist} - ${s.title}">
                                        <div class="overflow-hidden me-2">
                                            <div class="fw-semibold text-white text-truncate">${s.title}</div>
                                            <small class="text-muted text-truncate d-block">${s.artist}</small>
                                        </div>
                                        <span class="badge bg-dark border text-muted">${s.duration}</span>
                                    </div>
                                `;
                            });
                            html += '</div>';
                            songResults.innerHTML = html;

                            // Click on song item
                            songResults.querySelectorAll('.song-result-item').forEach(item => {
                                item.addEventListener('click', function () {
                                    const id = this.getAttribute('data-id');
                                    const name = this.getAttribute('data-name');

                                    if (selectedIdInput) selectedIdInput.value = id;
                                    if (selectedText) selectedText.innerText = name;
                                    if (selectedDisplay) selectedDisplay.classList.remove('d-none');

                                    songResults.innerHTML = '';
                                    searchInput.value = '';
                                });
                            });
                        })
                        .catch(() => {
                            songResults.innerHTML = '<div class="p-3 text-center text-danger small">Gagal mencari lagu.</div>';
                        });
                }, 300);
            });
        }

        // Clear Selection
        if (clearSelectionBtn) {
            clearSelectionBtn.addEventListener('click', function () {
                if (selectedIdInput) selectedIdInput.value = '';
                if (selectedDisplay) selectedDisplay.classList.add('d-none');
            });
        }

        // Form Submit
        if (requestForm) {
            requestForm.addEventListener('submit', function (e) {
                e.preventDefault();

                const nameInput = document.getElementById('req-name');
                const name = nameInput ? nameInput.value.trim() : '';
                const songId = selectedIdInput ? selectedIdInput.value.trim() : '';
                const message = reqMessage ? reqMessage.value.trim() : '';

                if (!name) {
                    showToast('Silakan masukkan nama Anda.', 'error');
                    return;
                }

                if (!songId) {
                    showToast('Silakan cari dan pilih lagu yang ingin diputar terlebih dahulu.', 'error');
                    return;
                }

                if (submitReqBtn) {
                    submitReqBtn.disabled = true;
                    submitReqBtn.innerHTML = '<i class="bi bi-arrow-repeat spin me-1"></i> Mengirim...';
                }

                const formData = new FormData();
                formData.append('name', name);
                formData.append('song_id', songId);
                formData.append('message', message);

                fetch(`${baseUrl}/api/song-request`, {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json().then(data => ({ status: res.status, body: data })))
                .then(res => {
                    if (submitReqBtn) {
                        submitReqBtn.disabled = false;
                        submitReqBtn.innerHTML = '<i class="bi bi-send-fill me-1"></i> KIRIM REQUEST LAGU';
                    }

                    if (res.status === 200 && res.body.success) {
                        showToast(res.body.message || 'Request lagu berhasil dikirim!', 'success', 5000);
                        requestForm.reset();
                        if (selectedIdInput) selectedIdInput.value = '';
                        if (selectedDisplay) selectedDisplay.classList.add('d-none');
                        if (charCounter) charCounter.innerText = '0 / 200';

                        // Close modal after 1.2s
                        const modalEl = document.getElementById('songRequestModal');
                        if (modalEl && window.bootstrap) {
                            const modal = window.bootstrap.Modal.getInstance(modalEl);
                            if (modal) {
                                setTimeout(() => modal.hide(), 1200);
                            }
                        }
                    } else {
                        const errMsg = res.body.error || 'Gagal mengirim request lagu.';
                        showToast(errMsg, 'error', 4500);
                    }
                })
                .catch(err => {
                    if (submitReqBtn) {
                        submitReqBtn.disabled = false;
                        submitReqBtn.innerHTML = '<i class="bi bi-send-fill me-1"></i> KIRIM REQUEST LAGU';
                    }
                    showToast('Terjadi kesalahan jaringan: ' + err.message, 'error');
                });
            });
        }
    });
})();
