<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Radio Control Center') ?> | <?= e(config('radio.station.name', 'Radio Agro')) ?></title>
    <!-- Google Font: Inter & Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- AdminLTE 4 & Custom Radio CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<div class="app-wrapper">
    <!-- Navbar Header -->
    <nav class="app-header navbar navbar-expand bg-body shadow-sm">
        <div class="container-fluid">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                        <i class="bi bi-list fs-5"></i>
                    </a>
                </li>
                <li class="nav-item d-none d-md-block">
                    <a href="<?= base_url() ?>" target="_blank" class="nav-link text-primary fw-medium">
                        <i class="bi bi-broadcast me-1"></i> Public Radio Site <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                    </a>
                </li>
            </ul>

            <ul class="navbar-nav ms-auto align-items-center">
                <!-- Audio Player Quick Bar -->
                <li class="nav-item me-3 d-none d-lg-block">
                    <div class="d-flex align-items-center bg-dark text-white px-3 py-1 rounded-pill shadow-sm">
                        <span class="badge bg-danger pulse-dot me-2">● LIVE</span>
                        <span id="nav-now-playing" class="small text-truncate me-3" style="max-width: 250px;">Loading stream...</span>
                        <button id="nav-play-btn" class="btn btn-sm btn-outline-light rounded-circle p-1" style="width:28px;height:28px;" title="Listen Live">
                            <i class="bi bi-play-fill"></i>
                        </button>
                    </div>
                </li>

                <!-- User Dropdown Menu -->
                <li class="nav-item dropdown user-menu">
                    <a href="#" class="nav-link dropdown-toggle d-flex align-items-center" data-bs-toggle="dropdown">
                        <div class="avatar-circle me-2 bg-primary text-white fw-bold">
                            <?= strtoupper(substr(auth_user()['username'] ?? 'A', 0, 1)) ?>
                        </div>
                        <span class="d-none d-md-inline fw-semibold"><?= e(auth_user()['display_name'] ?? 'Admin') ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li class="user-header bg-primary text-white p-3 text-center">
                            <h6 class="mb-0 fw-bold"><?= e(auth_user()['display_name'] ?? 'Administrator') ?></h6>
                            <small class="badge bg-light text-primary text-uppercase mt-1"><?= e(auth_user()['role'] ?? 'Admin') ?></small>
                        </li>
                        <li class="user-footer d-flex justify-content-between p-2">
                            <a href="<?= base_url('admin/settings') ?>" class="btn btn-sm btn-outline-secondary">Settings</a>
                            <a href="<?= base_url('admin/logout') ?>" class="btn btn-sm btn-danger">Sign out</a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>
