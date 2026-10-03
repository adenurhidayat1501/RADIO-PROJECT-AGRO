<?php
$reqPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
$isActive = function(string $route) use ($reqPath): string {
    $trimmedReq = rtrim($reqPath, '/');
    if ($route === 'admin') {
        return ($trimmedReq === '/admin' || str_ends_with($trimmedReq, '/admin')) ? 'active' : '';
    }
    return str_contains($trimmedReq, $route) ? 'active' : '';
};
?>
<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <div class="sidebar-brand">
        <a href="<?= base_url('admin') ?>" class="brand-link text-decoration-none d-flex align-items-center">
            <span class="brand-icon me-2"><i class="bi bi-soundwave fs-3 text-primary"></i></span>
            <span class="brand-text fw-bold tracking-wide"><?= e(config('radio.station.name', 'Radio Agro')) ?></span>
        </a>
    </div>

    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
                <!-- Dashboard -->
                <li class="nav-item">
                    <a href="<?= base_url('admin') ?>" class="nav-link <?= $isActive('admin') ?>">
                        <i class="nav-icon bi bi-speedometer2"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <!-- RADIO GROUP -->
                <li class="nav-header text-uppercase small text-muted mt-2">Radio Control</li>
                <li class="nav-item">
                    <a href="<?= base_url('admin/station') ?>" class="nav-link <?= $isActive('admin/station') ?>">
                        <i class="nav-icon bi bi-sliders2"></i>
                        <p>Station Profile</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('admin/stream') ?>" class="nav-link <?= $isActive('admin/stream') ?>">
                        <i class="nav-icon bi bi-hdd-network"></i>
                        <p>Stream Generator</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('admin/studio') ?>" class="nav-link <?= $isActive('admin/studio') ?>">
                        <i class="nav-icon bi bi-mic"></i>
                        <p>Live Studio / DJ</p>
                    </a>
                </li>

                <!-- MEDIA GROUP -->
                <li class="nav-header text-uppercase small text-muted mt-2">Media & Assets</li>
                <li class="nav-item">
                    <a href="<?= base_url('admin/music') ?>" class="nav-link <?= $isActive('admin/music') ?>">
                        <i class="nav-icon bi bi-music-note-list"></i>
                        <p>Music Library</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('admin/playlists') ?>" class="nav-link <?= $isActive('admin/playlists') ?>">
                        <i class="nav-icon bi bi-collection-play"></i>
                        <p>Playlists</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('admin/jingles') ?>" class="nav-link <?= $isActive('admin/jingles') ?>">
                        <i class="nav-icon bi bi-bell"></i>
                        <p>Jingles & Stings</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('admin/advertisements') ?>" class="nav-link <?= $isActive('admin/advertisements') ?>">
                        <i class="nav-icon bi bi-megaphone"></i>
                        <p>Advertisements</p>
                    </a>
                </li>

                <!-- PROGRAM GROUP -->
                <li class="nav-header text-uppercase small text-muted mt-2">Broadcast Schedule</li>
                <li class="nav-item">
                    <a href="<?= base_url('admin/schedules') ?>" class="nav-link <?= $isActive('admin/schedules') ?>">
                        <i class="nav-icon bi bi-calendar3"></i>
                        <p>Weekly Schedule</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('admin/programs') ?>" class="nav-link <?= $isActive('admin/programs') ?>">
                        <i class="nav-icon bi bi-journal-album"></i>
                        <p>Programs & Shows</p>
                    </a>
                </li>

                <!-- COMMUNITY GROUP -->
                <li class="nav-header text-uppercase small text-muted mt-2">Listeners & Requests</li>
                <li class="nav-item">
                    <a href="<?= base_url('admin/requests') ?>" class="nav-link <?= $isActive('admin/requests') ?>">
                        <i class="nav-icon bi bi-chat-heart"></i>
                        <p>Song Requests</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('admin/analytics') ?>" class="nav-link <?= $isActive('admin/analytics') ?>">
                        <i class="nav-icon bi bi-graph-up-arrow"></i>
                        <p>Listener Statistics</p>
                    </a>
                </li>

                <!-- SYSTEM GROUP -->
                <li class="nav-header text-uppercase small text-muted mt-2">System & Accounts</li>
                <li class="nav-item">
                    <a href="<?= base_url('admin/djs') ?>" class="nav-link <?= $isActive('admin/djs') ?>">
                        <i class="nav-icon bi bi-person-video3"></i>
                        <p>DJ Accounts</p>
                    </a>
                </li>
                <?php if (has_role('admin')): ?>
                <li class="nav-item">
                    <a href="<?= base_url('admin/users') ?>" class="nav-link <?= $isActive('admin/users') ?>">
                        <i class="nav-icon bi bi-people"></i>
                        <p>User Management</p>
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a href="<?= base_url('admin/backup') ?>" class="nav-link <?= $isActive('admin/backup') ?>">
                        <i class="nav-icon bi bi-cloud-arrow-down"></i>
                        <p>MongoDB Backup</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('admin/diagnostics') ?>" class="nav-link <?= $isActive('admin/diagnostics') ?>">
                        <i class="nav-icon bi bi-heart-pulse text-danger"></i>
                        <p>Analisis Error & Health</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('admin/logs') ?>" class="nav-link <?= $isActive('admin/logs') ?>">
                        <i class="nav-icon bi bi-terminal"></i>
                        <p>System Logs</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('admin/settings') ?>" class="nav-link <?= $isActive('admin/settings') ?>">
                        <i class="nav-icon bi bi-gear"></i>
                        <p>Settings & AutoDJ</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>
