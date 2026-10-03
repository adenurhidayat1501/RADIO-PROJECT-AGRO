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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS & Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom Aurora Design System Styling -->
    <link rel="stylesheet" href="<?= asset('css/public.css') ?>">

    <!-- Critical Design System Tokens & Anti-Cache Embed -->
    <style id="radio-agro-critical-tokens">
        :root {
            --bg-canvas: #04070d;
            --primary: #10b981;
            --cyan: #06b6d4;
        }
        body {
            background-color: #04070d !important;
            color: #f8fafc !important;
            font-family: 'Inter', -apple-system, sans-serif !important;
        }
        .hero-deck .glass-card {
            background: rgba(11, 19, 36, 0.82) !important;
            backdrop-filter: blur(28px) !important;
            -webkit-backdrop-filter: blur(28px) !important;
            border: 1px solid rgba(255, 255, 255, 0.12) !important;
            border-radius: 28px !important;
            box-shadow: 0 25px 60px -10px rgba(0, 0, 0, 0.85), 0 0 35px rgba(16, 185, 129, 0.15) !important;
        }
        .turntable-plinth {
            position: relative;
            width: 270px;
            height: 270px;
            margin: 0 auto;
            background: radial-gradient(circle at 35% 35%, #182234 0%, #0b1324 60%, #040812 100%) !important;
            border-radius: 28px !important;
            border: 1px solid rgba(255, 255, 255, 0.14) !important;
            box-shadow: inset 0 1px 2px rgba(255, 255, 255, 0.18), inset 0 -4px 10px rgba(0, 0, 0, 0.8), 0 20px 50px -10px rgba(0, 0, 0, 0.85), 0 0 30px rgba(16, 185, 129, 0.15) !important;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
        }
        .turntable-platter {
            position: relative;
            width: 226px;
            height: 226px;
            border-radius: 50%;
            background: #020408 !important;
            box-shadow: 0 0 0 4px #1e293b, 0 0 0 6px rgba(16, 185, 129, 0.35), 0 10px 30px rgba(0, 0, 0, 0.9) !important;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .vinyl-disc {
            width: 214px !important;
            height: 214px !important;
            border-radius: 50% !important;
            background: radial-gradient(circle at center, transparent 32%, rgba(255, 255, 255, 0.05) 33%, transparent 34%), radial-gradient(circle at center, transparent 42%, rgba(255, 255, 255, 0.04) 43%, transparent 44%), radial-gradient(circle at center, transparent 52%, rgba(255, 255, 255, 0.04) 53%, transparent 54%), radial-gradient(circle at center, transparent 65%, rgba(255, 255, 255, 0.035) 66%, transparent 67%), radial-gradient(circle at center, transparent 78%, rgba(255, 255, 255, 0.04) 79%, transparent 80%), radial-gradient(circle at center, transparent 88%, rgba(255, 255, 255, 0.03) 89%, transparent 90%), conic-gradient(from 45deg, #222d3d 0deg, #090e18 60deg, #2a374a 120deg, #0a0f19 180deg, #222d3d 240deg, #090e18 300deg, #222d3d 360deg) !important;
            border: 3px solid rgba(255, 255, 255, 0.1) !important;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            box-shadow: inset 0 0 25px rgba(0, 0, 0, 0.95) !important;
            transition: transform 0.8s cubic-bezier(0.16, 1, 0.3, 1) !important;
        }
        .vinyl-disc.spinning {
            animation: vinylSpin 6s linear infinite !important;
        }
        .vinyl-center-label {
            width: 82px !important;
            height: 82px !important;
            border-radius: 50% !important;
            background: linear-gradient(135deg, #10b981 0%, #06b6d4 100%) !important;
            box-shadow: 0 0 25px rgba(16, 185, 129, 0.5), inset 0 0 14px rgba(0, 0, 0, 0.45) !important;
        }
        .vinyl-tonearm {
            position: absolute;
            top: 14px;
            right: 18px;
            width: 60px;
            height: 125px;
            pointer-events: none;
            transform-origin: 46px 14px;
            transform: rotate(-35deg);
            transition: transform 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
            z-index: 10;
        }
        .vinyl-tonearm.active {
            transform: rotate(8deg) !important;
        }
        .tonearm-pivot {
            position: absolute;
            top: 5px;
            right: 5px;
            width: 24px;
            height: 24px;
            background: radial-gradient(circle, #64748b 0%, #334155 100%);
            border-radius: 50%;
            border: 2px solid #94a3b8;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.6);
        }
        .tonearm-rod {
            position: absolute;
            top: 16px;
            right: 15px;
            width: 4px;
            height: 95px;
            background: linear-gradient(to right, #cbd5e1, #ffffff, #94a3b8);
            border-radius: 2px;
            box-shadow: 1px 1px 3px rgba(0,0,0,0.5);
        }
        .tonearm-head {
            position: absolute;
            bottom: 0;
            right: 7px;
            width: 16px;
            height: 20px;
            background: #0f172a;
            border: 1px solid #34d399;
            border-radius: 3px;
            box-shadow: 0 0 10px rgba(16, 185, 129, 0.8);
        }
        .btn-listen-live {
            background: linear-gradient(135deg, #10b981 0%, #06b6d4 100%) !important;
            color: #ffffff !important;
            border: 1px solid rgba(255, 255, 255, 0.25) !important;
            font-weight: 700 !important;
            font-size: 1.05rem !important;
            font-family: 'Outfit', sans-serif !important;
            letter-spacing: 0.5px !important;
            min-height: 54px !important;
            padding: 12px 34px !important;
            border-radius: 9999px !important;
            box-shadow: 0 10px 30px -5px rgba(16, 185, 129, 0.5), 0 0 20px rgba(6, 182, 212, 0.3) !important;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1) !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            cursor: pointer;
            text-decoration: none !important;
        }
        .btn-listen-live:hover {
            background: linear-gradient(135deg, #34d399 0%, #22d3ee 100%) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 16px 40px -4px rgba(16, 185, 129, 0.75), 0 0 30px rgba(6, 182, 212, 0.5) !important;
            color: #ffffff !important;
        }
        .btn-listen-live:active {
            transform: translateY(0) !important;
        }
        .btn-listen-live.playing {
            background: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%) !important;
            box-shadow: 0 10px 30px -5px rgba(244, 63, 94, 0.55), 0 0 20px rgba(225, 29, 72, 0.35) !important;
        }
        .volume-control-pill {
            background: rgba(15, 26, 48, 0.92) !important;
            border: 1px solid rgba(255, 255, 255, 0.14) !important;
            padding: 6px 16px !important;
            border-radius: 9999px !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 10px !important;
            height: 54px !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4) !important;
        }
        .btn-request-nav {
            background: rgba(16, 185, 129, 0.16) !important;
            border: 1px solid rgba(16, 185, 129, 0.4) !important;
            color: #ffffff !important;
            font-weight: 600 !important;
            font-size: 0.88rem !important;
            transition: all 0.2s ease !important;
        }
        .btn-request-nav:hover {
            background: rgba(16, 185, 129, 0.3) !important;
            border-color: #34d399 !important;
            box-shadow: 0 0 15px rgba(16, 185, 129, 0.4) !important;
        }
        .btn-admin-nav {
            background: rgba(255, 255, 255, 0.08) !important;
            border: 1px solid rgba(255, 255, 255, 0.18) !important;
            color: #cbd5e1 !important;
            font-weight: 500 !important;
            font-size: 0.88rem !important;
            transition: all 0.2s ease !important;
            text-decoration: none !important;
        }
        .btn-admin-nav:hover {
            background: rgba(255, 255, 255, 0.16) !important;
            border-color: rgba(255, 255, 255, 0.35) !important;
            color: #ffffff !important;
        }
        .badge-on-air {
            background: linear-gradient(135deg, #10b981 0%, #06b6d4 100%) !important;
            color: #ffffff !important;
            box-shadow: 0 0 15px rgba(16, 185, 129, 0.5) !important;
        }
        .schedule-card.active-slot {
            border: 1.5px solid #10b981 !important;
            background: rgba(16, 185, 129, 0.1) !important;
            box-shadow: 0 0 35px rgba(16, 185, 129, 0.25) !important;
        }
    </style>
</head>
<body>

<!-- Aurora Ambient Mesh Lights -->
<div class="aurora-mesh" aria-hidden="true">
    <div class="aurora-orb aurora-orb-1"></div>
    <div class="aurora-orb aurora-orb-2"></div>
    <div class="aurora-orb aurora-orb-3"></div>
</div>

<!-- Floating Toast Container -->
<div id="toast-container" aria-live="polite"></div>

<!-- Hidden Native Audio Source -->
<audio id="live-stream-audio" preload="none" data-src="<?= e($streamUrl) ?>"></audio>

<!-- Navigation Bar -->
<header>
    <nav class="navbar navbar-expand-lg navbar-custom py-3" aria-label="Main Navigation">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center text-white text-decoration-none" href="<?= base_url() ?>">
                <div class="brand-avatar me-3">
                    <i class="bi bi-broadcast fs-4"></i>
                </div>
                <div>
                    <span class="fs-4 fw-bold font-outfit tracking-wide d-block leading-tight text-white"><?= e($station['name'] ?? 'Radio Agro') ?></span>
                    <span class="small text-muted font-normal text-uppercase" style="font-size: 0.72rem; letter-spacing: 1px;">24/7 Agro Streaming Network</span>
                </div>
            </a>

            <button class="navbar-toggler border-secondary text-white p-2" type="button" data-bs-toggle="collapse" data-bs-target="#navContent" aria-label="Toggle navigation">
                <i class="bi bi-list fs-2"></i>
            </button>

            <div class="collapse navbar-collapse" id="navContent">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link active" href="#broadcast"><i class="bi bi-soundwave me-1 text-primary"></i> On Air</a></li>
                    <li class="nav-item"><a class="nav-link" href="#schedule"><i class="bi bi-calendar3 me-1"></i> Jadwal Siaran</a></li>
                    <li class="nav-item"><a class="nav-link" href="#programs"><i class="bi bi-journal-album me-1"></i> Program</a></li>
                    <li class="nav-item"><a class="nav-link" href="#djs"><i class="bi bi-people me-1"></i> Kerabat Siar</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about"><i class="bi bi-info-circle me-1"></i> Tentang</a></li>
                </ul>

                <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
                    <button class="btn btn-request-nav rounded-pill px-3 py-2 text-white" data-bs-toggle="modal" data-bs-target="#songRequestModal">
                        <i class="bi bi-heart-fill text-danger me-1"></i> Request Lagu
                    </button>
                    <a href="<?= base_url('admin') ?>" class="btn btn-admin-nav rounded-pill px-3 py-2 text-white">
                        <i class="bi bi-shield-lock-fill text-primary me-1"></i> Studio Admin
                    </a>
                </div>
            </div>
        </div>
    </nav>
</header>

<main>
    <!-- HERO BROADCAST DECK -->
    <section id="broadcast" class="hero-deck">
        <div class="container">
            <div class="row align-items-center justify-content-center">
                <div class="col-lg-11 col-xl-10">
                    <div class="glass-card p-4 p-md-5">
                        <div class="row align-items-center g-4 g-lg-5">
                            <!-- Vinyl Deck & Spectrum Visualizer -->
                            <div class="col-md-5 text-center">
                                <div class="vinyl-stage">
                                    <div class="turntable-plinth">
                                        <div class="turntable-platter">
                                            <div class="vinyl-disc" id="vinyl-disc" role="img" aria-label="Vinyl Turntable">
                                                <div class="vinyl-center-label">
                                                    <div class="vinyl-spindle-hole"></div>
                                                    <i class="bi bi-soundwave fs-2"></i>
                                                    <small class="font-outfit fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">AGRO LIVE</small>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Realistic Stylus Tonearm -->
                                        <div class="vinyl-tonearm" id="vinyl-tonearm" aria-hidden="true">
                                            <div class="tonearm-pivot"></div>
                                            <div class="tonearm-rod"></div>
                                            <div class="tonearm-head"></div>
                                        </div>
                                    </div>

                                    <!-- Canvas Audio Visualizer -->
                                    <div class="visualizer-wrapper">
                                        <canvas id="audio-visualizer" width="270" height="52" aria-label="Live Frequency Spectrum"></canvas>
                                    </div>
                                </div>
                            </div>

                            <!-- Live Broadcast Metadata & Interaction Deck -->
                            <div class="col-md-7">
                                <!-- Telemetry Chips -->
                                <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                                    <div class="live-badge">
                                        <span class="live-dot"></span>
                                        <span id="live-broadcast-mode"><?= ($nowPlaying['source'] ?? '') === 'live_dj' ? 'LIVE DJ ON AIR' : 'LIVE ON AIR' ?></span>
                                    </div>
                                    <div class="telemetry-chip">
                                        <i class="bi bi-headphones text-primary"></i>
                                        <span id="live-listener-count"><?= number_format($status['listeners'] ?? 0) ?></span> Pendengar
                                    </div>
                                    <div class="telemetry-chip">
                                        <i class="bi bi-broadcast-pin text-info"></i>
                                        <span><?= $status['bitrate'] ?? 128 ?> KBPS HD</span>
                                    </div>
                                </div>

                                <!-- Now Playing Track -->
                                <div class="mb-3">
                                    <span class="small text-muted text-uppercase fw-semibold tracking-wider d-block mb-1">
                                        <i class="bi bi-music-note-beamed text-primary me-1"></i> Sedang Mengudara (Now Playing)
                                    </span>
                                    <h1 class="display-6 fw-bold font-outfit text-white mb-2 text-truncate" id="live-np-title" style="letter-spacing: -0.5px;">
                                        <?= e($nowPlaying['title'] ?? 'Radio Agro Live Stream') ?>
                                    </h1>
                                    <h4 class="text-primary fw-medium mb-0 text-truncate" id="live-np-artist">
                                        <?= e($nowPlaying['artist'] ?? 'Radio Agro') ?>
                                    </h4>
                                </div>

                                <!-- Play Button, Volume Controller, and Stream Link Tools -->
                                <div class="d-flex align-items-center gap-3 flex-wrap my-4">
                                    <button id="btn-main-play" class="btn-listen-live" type="button" aria-label="Listen Live Stream">
                                        <i id="main-play-icon" class="bi bi-play-fill fs-2"></i>
                                        <span id="main-play-text">DENGARKAN SIARAN</span>
                                    </button>

                                    <!-- Volume Pill with Mute Toggle -->
                                    <div class="volume-control-pill" title="Volume Slider">
                                        <button type="button" class="volume-icon-btn" id="volume-icon-btn" aria-label="Toggle Mute">
                                            <i class="bi bi-volume-up-fill fs-5" id="volume-icon"></i>
                                        </button>
                                        <input type="range" class="form-range" id="volume-slider" min="0" max="1" step="0.05" value="0.9" aria-label="Volume Level" style="width: 100px;">
                                    </div>

                                    <!-- Share / Tune In Options -->
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn-control-circle" id="btn-copy-stream" title="Salin URL Stream" aria-label="Copy Stream URL">
                                            <i class="bi bi-link-45deg fs-5"></i>
                                        </button>
                                        <button type="button" class="btn-control-circle" data-bs-toggle="modal" data-bs-target="#tuneInModal" title="Aplikasi Eksternal" aria-label="Tune-in Options">
                                            <i class="bi bi-box-arrow-up-right fs-6"></i>
                                        </button>
                                    </div>

                                    <!-- Keyboard Shortcuts Info -->
                                    <div class="w-100 d-none d-md-flex align-items-center gap-3 text-muted small mt-1">
                                        <span><kbd class="bg-dark text-white border-secondary px-2 py-1 rounded">SPACE</kbd> Play / Pause</span>
                                        <span><kbd class="bg-dark text-white border-secondary px-2 py-1 rounded">M</kbd> Mute / Unmute</span>
                                    </div>
                                </div>

                                <!-- Up Next Track Preview -->
                                <div class="up-next-ticker">
                                    <i class="bi bi-fast-forward-fill text-muted me-2"></i>
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
                <span class="badge bg-success bg-opacity-25 text-success text-uppercase fw-bold px-3 py-2 rounded-pill mb-2">Timetable</span>
                <h2 class="display-6 fw-bold font-outfit text-white">Jadwal Siaran Hari Ini</h2>
                <p class="text-muted">Rangkaian program edukasi pertanian, bursa komoditas desa, dan hiburan sepanjang hari</p>
            </div>

            <div class="row g-4 justify-content-center">
                <?php if (empty($todaySchedules)): ?>
                    <div class="col-lg-8">
                        <div class="glass-card p-4 text-center text-muted">
                            <i class="bi bi-calendar2-check fs-1 d-block mb-2 text-primary"></i>
                            <h5 class="text-white mb-1">Rotasi Musik & Informasi Non-Stop 24 Jam</h5>
                            <p class="small mb-0">Dipandu oleh sistem otomatis cerdas Auto DJ Radio Agro.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($todaySchedules as $slot): ?>
                        <?php
                            $isCurrentSlot = !empty($activeSlot) && (string)($slot['_id'] ?? '') === (string)($activeSlot['_id'] ?? '');
                        ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="schedule-card <?= $isCurrentSlot ? 'active-slot' : '' ?>">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="badge bg-dark border text-light font-monospace">
                                        <i class="bi bi-clock me-1 text-primary"></i> <?= e($slot['start_time']) ?> - <?= e($slot['end_time']) ?> WIB
                                    </span>
                                    <?php if ($isCurrentSlot): ?>
                                        <span class="badge-on-air"><i class="bi bi-broadcast me-1"></i> ON AIR NOW</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary small text-uppercase">
                                            <?= strtoupper($slot['mode'] ?? 'AUTO_DJ') ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <h4 class="fw-bold text-white mb-2"><?= e($slot['name']) ?></h4>
                                <p class="text-muted small mb-0">Program siaran terintegrasi studio Radio Agro.</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- FLAGSHIP SHOWS -->
    <section id="programs" class="py-5" style="background: rgba(0, 0, 0, 0.2);">
        <div class="container">
            <div class="text-center mb-5">
                <span class="badge bg-primary bg-opacity-25 text-primary text-uppercase fw-bold px-3 py-2 rounded-pill mb-2">Unggulan</span>
                <h2 class="display-6 fw-bold font-outfit text-white">Program Unggulan</h2>
                <p class="text-muted">Sajian informasi aktual pertanian, klinik tanaman, dan hiburan rakyat</p>
            </div>

            <div class="row g-4">
                <?php if (empty($programs)): ?>
                    <div class="col-md-4">
                        <div class="glass-card glass-card-interactive p-4 h-100">
                            <div class="badge bg-primary mb-3">06:00 - 09:00 WIB</div>
                            <h4 class="fw-bold text-white mb-2">Semangat Pagi Tani</h4>
                            <p class="text-muted small">Update cuaca, harga komoditas pasar harian, dan tembang penyemangat aktivitas di ladang.</p>
                            <div class="text-primary small fw-semibold"><i class="bi bi-mic-fill me-1"></i> Host: Kang Dado</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="glass-card glass-card-interactive p-4 h-100">
                            <div class="badge bg-success mb-3">13:00 - 15:00 WIB</div>
                            <h4 class="fw-bold text-white mb-2">Klinik Tanaman & Pupuk</h4>
                            <p class="text-muted small">Konsultasi interaktif pencegahan hama, nutrisi organik, dan solusi peningkatan panen.</p>
                            <div class="text-primary small fw-semibold"><i class="bi bi-mic-fill me-1"></i> Host: Tim Ahli Agro</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="glass-card glass-card-interactive p-4 h-100">
                            <div class="badge bg-info mb-3">19:00 - 22:00 WIB</div>
                            <h4 class="fw-bold text-white mb-2">Nusantara Bergoyang</h4>
                            <p class="text-muted small">Lagu dangdut klasik, campursari legendaris, dan musik nusantara kiriman para pendengar.</p>
                            <div class="text-primary small fw-semibold"><i class="bi bi-mic-fill me-1"></i> Host: DJ Sahabat Tani</div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($programs as $prog): ?>
                        <div class="col-md-4">
                            <div class="glass-card glass-card-interactive p-4 h-100">
                                <h4 class="fw-bold text-white mb-2"><?= e($prog['title']) ?></h4>
                                <p class="text-muted small"><?= e($prog['description']) ?></p>
                                <div class="text-primary small fw-semibold mb-1">
                                    <i class="bi bi-mic-fill me-1"></i> Host: <?= e($prog['host'] ?: 'Kerabat Siar') ?>
                                </div>
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
                <span class="badge bg-warning bg-opacity-25 text-warning text-uppercase fw-bold px-3 py-2 rounded-pill mb-2">Penyiar</span>
                <h2 class="display-6 fw-bold font-outfit text-white">Kerabat Siar & DJ Radio</h2>
                <p class="text-muted">Sosok ramah di balik mikrofon yang setia menyapa pendengar setia</p>
            </div>

            <div class="row g-4 justify-content-center">
                <?php if (empty($djs)): ?>
                    <div class="col-md-4 text-center">
                        <div class="glass-card glass-card-interactive p-4">
                            <div class="p-3 rounded-circle bg-success bg-opacity-20 text-success d-inline-flex mb-3">
                                <i class="bi bi-person-badge fs-1"></i>
                            </div>
                            <h4 class="fw-bold text-white mb-1">Kang Dado</h4>
                            <p class="text-muted small mb-0"><i class="bi bi-broadcast text-danger me-1"></i> Broadcaster Utama</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($djs as $dj): ?>
                        <div class="col-md-4 col-lg-3 text-center">
                            <div class="glass-card glass-card-interactive p-4">
                                <div class="p-3 rounded-circle bg-success bg-opacity-20 text-success d-inline-flex mb-3">
                                    <i class="bi bi-person-badge fs-2"></i>
                                </div>
                                <h5 class="fw-bold text-white mb-1"><?= e($dj['display_name']) ?></h5>
                                <p class="text-muted small mb-0"><i class="bi bi-broadcast text-danger me-1"></i> DJ On Air</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- RECENT DEDICATIONS / SONG REQUESTS -->
    <?php if (!empty($recentRequests)): ?>
    <section class="py-5" style="background: rgba(0, 0, 0, 0.3);">
        <div class="container">
            <div class="text-center mb-4">
                <span class="badge bg-danger bg-opacity-25 text-danger text-uppercase fw-bold px-3 py-2 rounded-pill mb-2">Atensi</span>
                <h3 class="fw-bold font-outfit text-white">Salam & Pilihan Pendengar</h3>
                <p class="text-muted small">Daftar lagu request dan titip salam dari sesama pendengar yang telah disetujui</p>
            </div>
            <div class="row g-3 justify-content-center">
                <?php foreach ($recentRequests as $req): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="glass-card p-3 small">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong class="text-white"><i class="bi bi-chat-quote-fill me-1 text-primary"></i> <?= e($req['name']) ?></strong>
                                <span class="badge bg-success-subtle text-success"><?= strtoupper($req['status'] ?? 'APPROVED') ?></span>
                            </div>
                            <div class="text-primary fw-medium text-truncate mb-1">
                                <i class="bi bi-music-note me-1"></i> <?= !empty($req['song']) ? e("{$req['song']['artist']} - {$req['song']['title']}") : 'Lagu Permintaan' ?>
                            </div>
                            <?php if (!empty($req['message'])): ?>
                                <div class="text-muted fst-italic text-truncate">"<?= e($req['message']) ?>"</div>
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
                    <span class="badge bg-success bg-opacity-25 text-success text-uppercase fw-bold px-3 py-2 rounded-pill mb-3">Tentang Kami</span>
                    <h2 class="display-6 fw-bold font-outfit text-white mb-3">Radio Mandiri Petani & Komunitas Nusantara</h2>
                    <p class="text-muted leading-relaxed mb-4">
                        Radio Agro hadir sebagai media komunikasi swadaya untuk petani, peternak, dan masyarakat desa. Kami menyiarkan edukasi teknologi tani, informasi harga pasar komoditas harian, serta hiburan musik nusantara 24 jam non-stop dengan infrastruktur penyiaran mandiri berteknologi modern.
                    </p>
                    <div class="d-flex gap-3 flex-wrap">
                        <button class="btn btn-listen-live px-4 py-2" data-bs-toggle="modal" data-bs-target="#songRequestModal">
                            <i class="bi bi-heart-fill text-danger me-1"></i> Kirim Request Lagu
                        </button>
                        <button class="btn btn-outline-light rounded-pill px-4 py-2" data-bs-toggle="modal" data-bs-target="#tuneInModal">
                            <i class="bi bi-hdd-network me-1"></i> Tune-in Player Eksternal
                        </button>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <div class="glass-card p-5">
                        <div class="brand-avatar mx-auto mb-3" style="width: 64px; height: 64px;">
                            <i class="bi bi-soundwave fs-1"></i>
                        </div>
                        <h3 class="fw-bold text-white mb-2">Suara Kemandirian Tani</h3>
                        <p class="text-muted small mb-0">
                            Didukung mesin audio Liquidsoap 2.x & Icecast 2 dengan encoding MP3 stereo 128 kbps berkinerja tinggi.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- STICKY BOTTOM FLOATING MINI-PLAYER -->
<div id="sticky-bottom-player" class="sticky-bottom-player" role="region" aria-label="Persistent Audio Player">
    <div class="d-flex align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3 overflow-hidden">
            <div class="mini-thumb" id="mini-thumb">
                <i class="bi bi-soundwave fs-5"></i>
            </div>
            <div class="overflow-hidden">
                <div class="fw-bold text-white text-truncate small font-outfit" id="mini-np-title"><?= e($nowPlaying['title'] ?? 'Radio Agro Live') ?></div>
                <div class="text-primary text-truncate" style="font-size: 0.75rem;" id="mini-np-artist"><?= e($nowPlaying['artist'] ?? 'Radio Agro') ?></div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-3">
            <button class="btn-mini-play" id="btn-mini-play" type="button" aria-label="Play/Pause Stream">
                <i class="bi bi-play-fill" id="mini-play-icon"></i>
            </button>
            <button class="btn btn-request-nav btn-sm rounded-pill d-none d-md-inline-flex align-items-center" data-bs-toggle="modal" data-bs-target="#songRequestModal">
                <i class="bi bi-heart-fill text-danger me-1"></i> Request
            </button>
        </div>
    </div>
</div>

<!-- FOOTER -->
<footer class="py-5 border-top border-secondary border-opacity-25" style="background: rgba(4, 7, 13, 0.95);">
    <div class="container">
        <div class="row g-4 align-items-center justify-content-between">
            <div class="col-md-6 text-center text-md-start">
                <h4 class="fw-bold font-outfit text-white mb-1"><?= e($station['name'] ?? 'Radio Agro') ?></h4>
                <p class="text-muted small mb-0">&copy; <?= date('Y') ?> Radio Agro Self-Hosted Broadcast Network. All rights reserved.</p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <div class="d-flex justify-content-center justify-content-md-end gap-3 fs-5">
                    <a href="https://youtube.com" target="_blank" rel="noreferrer" class="text-muted hover-white" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                    <a href="https://instagram.com" target="_blank" rel="noreferrer" class="text-muted hover-white" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="https://facebook.com" target="_blank" rel="noreferrer" class="text-muted hover-white" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                    <a href="https://wa.me" target="_blank" rel="noreferrer" class="text-muted hover-white" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- MODAL: SONG REQUEST -->
<div class="modal fade" id="songRequestModal" tabindex="-1" aria-labelledby="songRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-white" id="songRequestModalLabel">
                    <i class="bi bi-heart-fill text-danger me-2"></i> Request Lagu Pilihan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="req-alert" class="alert d-none"></div>

                <form id="song-request-form">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted text-uppercase" for="req-name">Nama / Panggilan Anda</label>
                        <input type="text" id="req-name" class="form-control" placeholder="Contoh: Pak Budi dari Desa Sukamaju" required maxlength="60">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted text-uppercase" for="req-search-song">Cari Lagu di Koleksi Radio</label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary text-muted"><i class="bi bi-search"></i></span>
                            <input type="text" id="req-search-song" class="form-control" placeholder="Ketik judul lagu atau nama artis...">
                        </div>
                        <div id="req-song-results" class="mt-2" style="max-height: 190px; overflow-y: auto;"></div>
                    </div>

                    <!-- Selected Track Card -->
                    <div id="req-selected-song-display" class="alert alert-success d-none mb-3 py-2 px-3 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2 overflow-hidden">
                            <i class="bi bi-check-circle-fill text-success fs-5"></i>
                            <div class="text-truncate" id="selected-song-text"></div>
                        </div>
                        <button type="button" class="btn btn-sm btn-link text-danger p-0" id="btn-clear-selection" title="Hapus Pilihan">
                            <i class="bi bi-x-circle-fill fs-5"></i>
                        </button>
                    </div>
                    <input type="hidden" id="req-selected-song-id" value="">

                    <div class="mb-4">
                        <div class="d-flex justify-content-between">
                            <label class="form-label fw-semibold small text-muted text-uppercase" for="req-message">Pesan / Titip Salam</label>
                            <span class="small text-muted" id="char-counter">0 / 200</span>
                        </div>
                        <textarea id="req-message" class="form-control" rows="3" maxlength="200" placeholder="Titip salam buat teman-teman petani di sawah..."></textarea>
                    </div>

                    <button type="submit" id="btn-submit-req" class="btn btn-listen-live w-100 py-3 fw-bold">
                        <i class="bi bi-send-fill me-1"></i> KIRIM REQUEST LAGU
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: TUNE-IN / STREAM LINKS -->
<div class="modal fade" id="tuneInModal" tabindex="-1" aria-labelledby="tuneInModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-white" id="tuneInModalLabel">
                    <i class="bi bi-broadcast text-primary me-2"></i> Akses Stream Eksternal
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-4">
                    Putar siaran Radio Agro langsung melalui pemutar musik eksternal (VLC, Winamp, iTunes, Android Audio Player):
                </p>

                <div class="d-grid gap-2">
                    <a href="<?= base_url('stream.m3u') ?>" class="btn btn-outline-light text-start p-3 rounded-3 border-secondary" download>
                        <div class="fw-bold text-white"><i class="bi bi-file-earmark-music me-2 text-warning"></i> Unduh File M3U Playlist</div>
                        <small class="text-muted">Kompatibel untuk VLC, iTunes, Foobar2000, AIMP</small>
                    </a>
                    <a href="<?= base_url('stream.pls') ?>" class="btn btn-outline-light text-start p-3 rounded-3 border-secondary" download>
                        <div class="fw-bold text-white"><i class="bi bi-file-earmark-play me-2 text-info"></i> Unduh File PLS Playlist</div>
                        <small class="text-muted">Format standar SHOUTcast & Icecast player</small>
                    </a>
                    <a href="<?= e($streamUrl) ?>" target="_blank" rel="noreferrer" class="btn btn-outline-light text-start p-3 rounded-3 border-secondary">
                        <div class="fw-bold text-white"><i class="bi bi-link-45deg me-2 text-primary"></i> Direct MP3 Stream URL</div>
                        <small class="font-monospace text-muted text-truncate d-block"><?= e($streamUrl) ?></small>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Global Radio Config for player.js -->
<script>
window.RADIO_CONFIG = {
    baseUrl: '<?= rtrim(base_url(), '/') ?>',
    streamUrl: '<?= e($streamUrl) ?>'
};
</script>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Interactive Audio Player JS -->
<script src="<?= asset('js/player.js') ?>"></script>

</body>
</html>
