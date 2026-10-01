<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($station['name'] ?? 'Radio Agro') ?> - Suara Petani Mandiri 24/7</title>
    <meta name="description" content="<?= e($station['description'] ?? 'Radio Komunitas Pertanian, Berita & Hiburan Nusantara') ?>">

    <!-- Google Fonts: Inter & Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom Public Styling -->
    <link rel="stylesheet" href="<?= asset('css/public.css') ?>">
</head>
<body>

<!-- Hidden Native Audio Source (Connected via player.js) -->
<audio id="live-stream-audio" preload="none" data-src="<?= e($streamUrl) ?>"></audio>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-custom py-3">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center text-white" href="<?= base_url() ?>">
            <div class="p-2 rounded-circle bg-primary bg-opacity-25 text-primary me-2 d-inline-flex">
                <i class="bi bi-broadcast fs-4"></i>
            </div>
            <div>
                <span class="fs-4 fw-bold font-outfit tracking-wide"><?= e($station['name'] ?? 'Radio Agro') ?></span>
                <span class="d-block small text-muted font-normal" style="font-size: 0.75rem;">ONLINE BROADCAST 24/7</span>
            </div>
        </a>

        <button class="navbar-toggler border-secondary text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navContent">
            <i class="bi bi-list fs-2"></i>
        </button>

        <div class="collapse navbar-collapse" id="navContent">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link active" href="#broadcast">On Air</a></li>
                <li class="nav-item"><a class="nav-link" href="#schedule">Jadwal Acara</a></li>
                <li class="nav-item"><a class="nav-link" href="#programs">Program Unggulan</a></li>
                <li class="nav-item"><a class="nav-link" href="#djs">Penyiar / DJ</a></li>
                <li class="nav-item"><a class="nav-link" href="#about">Tentang Kami</a></li>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-outline-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#songRequestModal">
                    <i class="bi bi-heart-fill text-danger me-1"></i> Request Lagu
                </button>
                <a href="<?= base_url('admin') ?>" class="btn btn-dark btn-sm rounded-pill px-3 border-secondary">
                    <i class="bi bi-shield-lock me-1"></i> Studio
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- MAIN HERO BROADCAST DECK -->
<section id="broadcast" class="hero-deck">
    <div class="container">
        <div class="row align-items-center justify-content-center">
            <div class="col-lg-10">
                <div class="glass-card p-4 p-md-5 position-relative overflow-hidden">
                    <div class="row align-items-center g-4">
                        <!-- Vinyl Album Cover Section -->
                        <div class="col-md-5 text-center">
                            <div class="vinyl-container">
                                <div class="vinyl-disc" id="vinyl-disc">
                                    <div class="vinyl-center">
                                        <i class="bi bi-soundwave fs-1"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 d-flex justify-content-center">
                                <div class="visualizer-container">
                                    <div class="v-bar"></div>
                                    <div class="v-bar"></div>
                                    <div class="v-bar"></div>
                                    <div class="v-bar"></div>
                                    <div class="v-bar"></div>
                                    <div class="v-bar"></div>
                                    <div class="v-bar"></div>
                                    <div class="v-bar"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Live Broadcast Information & Player Section -->
                        <div class="col-md-7">
                            <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
                                <div class="live-badge">
                                    <span class="live-dot"></span>
                                    <span>LIVE ON AIR</span>
                                </div>
                                <div class="telemetry-chip">
                                    <i class="bi bi-headphones text-primary"></i>
                                    <span id="live-listener-count"><?= number_format($status['listeners'] ?? 0) ?></span> LISTENERS
                                </div>
                                <div class="telemetry-chip">
                                    <i class="bi bi-soundwave text-info"></i>
                                    <span><?= $status['bitrate'] ?? 128 ?> KBPS HD</span>
                                </div>
                            </div>

                            <span class="small text-muted text-uppercase fw-bold tracking-wider d-block mb-1">Sedang Mengudara (Now Playing)</span>
                            <h2 class="display-6 fw-bold font-outfit text-white mb-2 text-truncate" id="live-np-title">
                                <?= e($nowPlaying['title'] ?? 'Radio Agro Live Stream') ?>
                            </h2>
                            <h4 class="text-primary fw-medium mb-4 text-truncate" id="live-np-artist">
                                <i class="bi bi-music-note-beamed me-1"></i> <?= e($nowPlaying['artist'] ?? 'Radio Agro') ?>
                            </h4>

                            <!-- Big Play Button & Volume -->
                            <div class="d-flex align-items-center gap-3 flex-wrap mb-4">
                                <button id="btn-main-play" class="btn-listen-live">
                                    <i id="main-play-icon" class="bi bi-play-fill fs-3"></i>
                                    <span id="main-play-text">LISTEN LIVE</span>
                                </button>

                                <div class="d-flex align-items-center gap-2 bg-dark bg-opacity-50 px-3 py-2 rounded-pill border border-secondary border-opacity-25">
                                    <i class="bi bi-volume-up text-muted"></i>
                                    <input type="range" class="form-range" id="volume-slider" min="0" max="1" step="0.05" value="0.9" style="width: 90px;">
                                </div>

                                <button class="btn btn-outline-secondary btn-sm rounded-circle p-2" data-bs-toggle="modal" data-bs-target="#tuneInModal" title="Tune-in Options">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </button>
                            </div>

                            <!-- Next Track Bar -->
                            <div class="p-3 rounded-3 bg-dark bg-opacity-60 border border-secondary border-opacity-25 d-flex align-items-center">
                                <i class="bi bi-fast-forward text-muted me-2"></i>
                                <span class="small text-muted me-2 text-uppercase fw-bold">Up Next:</span>
                                <span class="small text-white text-truncate fw-medium" id="live-up-next">
                                    Auto DJ Smart Rotation
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- WEEKLY BROADCAST TIMETABLE -->
<section id="schedule" class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <span class="badge bg-primary bg-opacity-25 text-primary text-uppercase fw-bold px-3 py-2 rounded-pill mb-2">Timetable</span>
            <h2 class="fw-bold font-outfit text-white">Jadwal Siaran Mingguan</h2>
            <p class="text-muted">Rangkaian program edukasi pertanian, berita desa, dan tembang hiburan sepanjang hari</p>
        </div>

        <div class="row g-3 justify-content-center">
            <?php if (empty($todaySchedules)): ?>
                <div class="col-lg-8">
                    <div class="glass-card p-4 text-center text-muted">
                        <i class="bi bi-calendar2-check fs-2 d-block mb-2 text-primary"></i>
                        Program Siaran Reguler & Musik Non-Stop 24 Jam dengan Auto DJ engine.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($todaySchedules as $slot): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="glass-card p-4 h-100 border-start border-4 border-primary">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-primary text-white font-monospace">
                                    <?= e($slot['start_time']) ?> - <?= e($slot['end_time']) ?> WIB
                                </span>
                                <span class="badge bg-dark border text-muted">
                                    <?= strtoupper($slot['mode'] ?? 'AUTO_DJ') ?>
                                </span>
                            </div>
                            <h5 class="fw-bold text-white mb-1"><?= e($slot['name']) ?></h5>
                            <small class="text-muted">Siaran Radio Agro Hari Ini</small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- FLAGSHIP SHOWS -->
<section id="programs" class="py-5 bg-dark bg-opacity-50">
    <div class="container">
        <div class="text-center mb-5">
            <span class="badge bg-success bg-opacity-25 text-success text-uppercase fw-bold px-3 py-2 rounded-pill mb-2">Programs</span>
            <h2 class="fw-bold font-outfit text-white">Program Unggulan</h2>
            <p class="text-muted">Sajian informasi aktual pertanian, pasar tani, dan hiburan interaktif</p>
        </div>

        <div class="row g-4">
            <?php if (empty($programs)): ?>
                <div class="col-md-4">
                    <div class="glass-card p-4 h-100">
                        <div class="badge bg-primary mb-3">06:00 - 09:00 WIB</div>
                        <h4 class="fw-bold text-white mb-2">Semangat Pagi Tani</h4>
                        <p class="text-muted small">Update cuaca, harga komoditas pasar, dan musik penyemangat aktivitas di ladang dan sawah.</p>
                        <div class="text-primary small fw-semibold"><i class="bi bi-mic me-1"></i> Host: Kang Dado</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-card p-4 h-100">
                        <div class="badge bg-success mb-3">13:00 - 15:00 WIB</div>
                        <h4 class="fw-bold text-white mb-2">Klinik Tanaman & Pupuk</h4>
                        <p class="text-muted small">Diskusi pakar agrikultur mengenai pencegahan hama dan tips peningkatan hasil panen.</p>
                        <div class="text-primary small fw-semibold"><i class="bi bi-mic me-1"></i> Host: Tim Ahli Agro</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-card p-4 h-100">
                        <div class="badge bg-info mb-3">19:00 - 22:00 WIB</div>
                        <h4 class="fw-bold text-white mb-2">Nusantara Bergoyang</h4>
                        <p class="text-muted small">Pentas lagu dangdut klasik, campursari, dan musik pop nusantara pilihan pendengar setia.</p>
                        <div class="text-primary small fw-semibold"><i class="bi bi-mic me-1"></i> Host: DJ Sahabat Tani</div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($programs as $prog): ?>
                    <div class="col-md-4">
                        <div class="glass-card p-4 h-100">
                            <h4 class="fw-bold text-white mb-2"><?= e($prog['title']) ?></h4>
                            <p class="text-muted small"><?= e($prog['description']) ?></p>
                            <div class="text-primary small fw-semibold mb-1"><i class="bi bi-mic me-1"></i> Host: <?= e($prog['host'] ?: 'Broadcaster') ?></div>
                            <?php if (!empty($prog['schedule_note'])): ?>
                                <small class="text-muted"><i class="bi bi-clock me-1"></i> <?= e($prog['schedule_note']) ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- RESIDENT DJS & BROADCASTERS -->
<section id="djs" class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <span class="badge bg-warning bg-opacity-25 text-warning text-uppercase fw-bold px-3 py-2 rounded-pill mb-2">Crew</span>
            <h2 class="fw-bold font-outfit text-white">Kerabat Siar & DJ Radio</h2>
            <p class="text-muted">Sosok di balik mikrofon yang setia menemani hari Anda</p>
        </div>

        <div class="row g-4 justify-content-center">
            <?php if (empty($djs)): ?>
                <div class="col-md-4 text-center">
                    <div class="glass-card p-4">
                        <div class="p-3 rounded-circle bg-primary bg-opacity-20 text-primary d-inline-flex mb-3">
                            <i class="bi bi-person-fill fs-1"></i>
                        </div>
                        <h5 class="fw-bold text-white mb-1">Kang Dado</h5>
                        <p class="text-muted small mb-0">Senior Broadcaster & Agro Host</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($djs as $dj): ?>
                    <div class="col-md-4 col-lg-3 text-center">
                        <div class="glass-card p-4">
                            <div class="p-3 rounded-circle bg-primary bg-opacity-20 text-primary d-inline-flex mb-3">
                                <i class="bi bi-person-fill fs-2"></i>
                            </div>
                            <h5 class="fw-bold text-white mb-1"><?= e($dj['display_name']) ?></h5>
                            <p class="text-muted small mb-0"><i class="bi bi-broadcast me-1 text-danger"></i> Broadcaster</p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- RECENT DEDICATIONS / SONG REQUESTS -->
<?php if (!empty($recentRequests)): ?>
<section class="py-5 bg-dark bg-opacity-25">
    <div class="container">
        <div class="text-center mb-4">
            <h3 class="fw-bold font-outfit text-white">Pilihan & Salam Pendengar</h3>
            <p class="text-muted small">Lagu dan atensi yang telah disetujui untuk diputar</p>
        </div>
        <div class="row g-3 justify-content-center">
            <?php foreach ($recentRequests as $req): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="glass-card p-3 small">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong class="text-white"><i class="bi bi-chat-quote me-1 text-primary"></i> <?= e($req['name']) ?></strong>
                            <span class="badge bg-success-subtle text-success"><?= strtoupper($req['status']) ?></span>
                        </div>
                        <div class="text-primary fw-medium text-truncate">
                            <?= !empty($req['song']) ? e("{$req['song']['artist']} - {$req['song']['title']}") : 'Request Lagu' ?>
                        </div>
                        <?php if (!empty($req['message'])): ?>
                            <div class="text-muted italic mt-1 text-truncate">"<?= e($req['message']) ?>"</div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ABOUT SECTION -->
<section id="about" class="py-5">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="badge bg-primary bg-opacity-25 text-primary text-uppercase fw-bold px-3 py-2 rounded-pill mb-3">About Radio Agro</span>
                <h2 class="display-6 fw-bold font-outfit text-white mb-3">Radio Mandiri Petani & Komunitas Nusantara</h2>
                <p class="text-muted leading-relaxed">
                    Radio Agro hadir sebagai media komunikasi swadaya untuk petani, peternak, dan masyarakat pedesaan. Kami menyiarkan edukasi teknologi tani, informasi harga pasar komoditas harian, serta hiburan musik nusantara 24 jam non-stop dengan infrastruktur penyiaran mandiri berteknologi tinggi.
                </p>
                <div class="d-flex gap-3 mt-4">
                    <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#songRequestModal">
                        <i class="bi bi-music-note-beamed me-1"></i> Kirim Request Lagu
                    </button>
                    <button class="btn btn-outline-light rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#tuneInModal">
                        <i class="bi bi-hdd-network me-1"></i> Akses Stream URL
                    </button>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <div class="p-5 glass-card">
                    <i class="bi bi-tree fs-1 text-success d-block mb-3"></i>
                    <h4 class="fw-bold text-white mb-2">Suara Kemandirian Tani</h4>
                    <p class="text-muted small mb-0">
                        Disiarkan melalui server Icecast2 & Liquidsoap 2.x dengan encoder audio digital 128 kbps stereo.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer class="py-5 border-top border-secondary border-opacity-25 bg-black">
    <div class="container">
        <div class="row g-4 align-items-center justify-content-between">
            <div class="col-md-6 text-center text-md-start">
                <h4 class="fw-bold font-outfit text-white mb-1"><?= e($station['name'] ?? 'Radio Agro') ?></h4>
                <p class="text-muted small mb-0">&copy; <?= date('Y') ?> Radio Agro Self-Hosted Broadcast Network. All rights reserved.</p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <div class="d-flex justify-content-center justify-content-md-end gap-3 fs-5">
                    <a href="https://youtube.com" target="_blank" class="text-muted hover-white"><i class="bi bi-youtube"></i></a>
                    <a href="https://instagram.com" target="_blank" class="text-muted hover-white"><i class="bi bi-instagram"></i></a>
                    <a href="https://facebook.com" target="_blank" class="text-muted hover-white"><i class="bi bi-facebook"></i></a>
                    <a href="https://wa.me" target="_blank" class="text-muted hover-white"><i class="bi bi-whatsapp"></i></a>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- MODAL: SONG REQUEST -->
<div class="modal fade" id="songRequestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-white"><i class="bi bi-heart-fill text-danger me-2"></i> Request Lagu Pilihan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="req-alert" class="alert d-none"></div>

                <form id="song-request-form">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted text-uppercase">Nama Anda</label>
                        <input type="text" id="req-name" class="form-control" placeholder="Nama / Panggilan" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted text-uppercase">Cari Lagu di Library</label>
                        <input type="text" id="req-search-song" class="form-control" placeholder="Ketik judul lagu atau artis...">
                        <div id="req-song-results" class="mt-2" style="max-height: 180px; overflow-y: auto;"></div>
                    </div>

                    <div id="req-selected-song-display" class="alert alert-primary py-2 small d-none mb-3"></div>
                    <input type="hidden" id="req-selected-song-id" value="">

                    <div class="mb-4">
                        <label class="form-label fw-semibold small text-muted text-uppercase">Pesan / Titip Salam</label>
                        <textarea id="req-message" class="form-control" rows="2" placeholder="Salam untuk rekan tani di sawah..."></textarea>
                    </div>

                    <button type="submit" id="btn-submit-req" class="btn btn-primary w-100 rounded-pill py-2 fw-semibold">
                        <i class="bi bi-send-fill me-1"></i> Send Song Request
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: TUNE-IN / STREAM LINKS -->
<div class="modal fade" id="tuneInModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-white"><i class="bi bi-broadcast text-primary me-2"></i> Tune-in & Direct Stream Links</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">
                    Dengarkan siaran Radio Agro langsung dari aplikasi pemutar musik eksternal favorit Anda:
                </p>

                <div class="d-grid gap-2 mb-4">
                    <a href="<?= base_url('stream.m3u') ?>" class="btn btn-outline-light text-start p-3 rounded-3" download>
                        <div class="fw-bold"><i class="bi bi-file-earmark-music me-2 text-warning"></i> Unduh File M3U</div>
                        <small class="text-muted">Untuk VLC Media Player, Winamp, iTunes, Foobar2000</small>
                    </a>
                    <a href="<?= base_url('stream.pls') ?>" class="btn btn-outline-light text-start p-3 rounded-3" download>
                        <div class="fw-bold"><i class="bi bi-file-earmark-play me-2 text-info"></i> Unduh File PLS</div>
                        <small class="text-muted">Format standar SHOUTcast & Icecast tune-in</small>
                    </a>
                    <a href="<?= e($streamUrl) ?>" target="_blank" class="btn btn-outline-light text-start p-3 rounded-3">
                        <div class="fw-bold"><i class="bi bi-link-45deg me-2 text-primary"></i> Direct MP3 Stream URL</div>
                        <small class="font-monospace text-muted text-truncate d-block"><?= e($streamUrl) ?></small>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Public Interactive Audio Player JS -->
<script src="<?= asset('js/player.js') ?>"></script>

</body>
</html>
