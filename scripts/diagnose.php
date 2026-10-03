#!/usr/bin/env php
<?php

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    die("Script ini harus dijalankan dari terminal CLI.\n");
}

require_once __DIR__ . '/../bootstrap.php';

use App\Services\DiagnosticService;

// ANSI Colors
$C_RESET = "\033[0m";
$C_BOLD = "\033[1m";
$C_RED = "\033[1;31m";
$C_GREEN = "\033[1;32m";
$C_YELLOW = "\033[1;33m";
$C_BLUE = "\033[1;34m";
$C_CYAN = "\033[1;36m";
$C_WHITE = "\033[1;37m";
$C_BG_RED = "\033[41;1;37m";
$C_BG_GREEN = "\033[42;1;30m";

echo "\n{$C_CYAN}=============================================================================={$C_RESET}\n";
echo "{$C_BOLD}{$C_WHITE}       RADIO AGRO - SISTEM ANALISIS ERROR & HEALTH CHECK (VPS DIAGNOSTIC){$C_RESET}\n";
echo "{$C_CYAN}=============================================================================={$C_RESET}\n";
echo "Waktu Pemeriksaan: " . date('Y-m-d H:i:s T') . "\n";
echo "Host Server: " . gethostname() . " (" . php_uname('s') . " " . php_uname('r') . ")\n\n";

$diag = new DiagnosticService();

// Check if user requested auto-repair
if (in_array('--repair', $argv, true)) {
    echo "{$C_YELLOW}>>> Menjalankan Perbaikan Otomatis (Auto-Repair)...{$C_RESET}\n";
    $repair = $diag->runAutoRepair();
    foreach ($repair['steps'] as $step) {
        echo "  {$C_GREEN}✔{$C_RESET} {$step}\n";
    }
    echo "\n{$C_GREEN}>>> Perbaikan selesai! Menjalankan ulang diagnosa...{$C_RESET}\n\n";
    sleep(1);
}

// Run Diagnostics
$report = $diag->runDiagnostics();
$summary = $report['summary'];
$checks = $report['checks'];

// Group checks by category
$grouped = [];
foreach ($checks as $c) {
    $grouped[$c['category']][] = $c;
}

foreach ($grouped as $category => $items) {
    echo "{$C_BOLD}{$C_BLUE}── [ {$category} ] ──{$C_RESET}\n";

    foreach ($items as $item) {
        if ($item['status'] === 'ok') {
            $badge = "{$C_GREEN}[  OK  ]{$C_RESET}";
        } elseif ($item['status'] === 'warning') {
            $badge = "{$C_YELLOW}[ WARN ]{$C_RESET}";
        } else {
            $badge = "{$C_RED}[ FAIL ]{$C_RESET}";
        }

        echo sprintf("  %s %-36s : %s\n", $badge, $item['name'], $item['message']);

        if ($item['status'] !== 'ok' && !empty($item['remedy'])) {
            echo "         {$C_CYAN}↳ Solusi: {$C_BOLD}{$item['remedy']}{$C_RESET}\n";
        }
    }
    echo "\n";
}

// Summary Score Box
echo "{$C_CYAN}=============================================================================={$C_RESET}\n";
echo "{$C_BOLD}RINGKASAN KESEHATAN SISTEM (SYSTEM HEALTH SCORE):{$C_RESET}\n";

$scoreColor = $summary['health_score'] >= 90 ? $C_GREEN : ($summary['health_score'] >= 70 ? $C_YELLOW : $C_RED);
echo sprintf("  Skor Kebugaran : %s%d%%%s\n", $scoreColor, $summary['health_score'], $C_RESET);
echo sprintf("  Total Pengujian: %d\n", $summary['total']);
echo sprintf("  %sLolos        : %d%s\n", $C_GREEN, $summary['passed'], $C_RESET);
echo sprintf("  %sPeringatan   : %d%s\n", $C_YELLOW, $summary['warnings'], $C_RESET);
echo sprintf("  %sError / Gagal: %d%s\n", $C_RED, $summary['errors'], $C_RESET);
echo "{$C_CYAN}=============================================================================={$C_RESET}\n";

// Action Recommendations
if ($summary['errors'] > 0) {
    echo "\n{$C_BG_RED} [!] DITEMUKAN MASALAH KRITIS YANG MEMBUTUHKAN TINDAKAN: {$C_RESET}\n";
    $failItems = array_filter($checks, fn($c) => $c['status'] === 'error');
    $i = 1;
    foreach ($failItems as $f) {
        echo "  {$i}. {$C_BOLD}{$f['name']}{$C_RESET}: {$f['message']}\n";
        if (!empty($f['remedy'])) {
            echo "     Perintah perbaikan: {$C_GREEN}{$f['remedy']}{$C_RESET}\n";
        }
        $i++;
    }
    echo "\n{$C_YELLOW}Tips: Jalankan 'php scripts/diagnose.php --repair' untuk sinkronisasi otomatis.{$C_RESET}\n";
} else {
    echo "\n{$C_BG_GREEN} [✔] SEMUA SISTEM SIARAN BERJALAN NORMAL & SIAP SIAR! {$C_RESET}\n\n";
}

// Check logs for recent errors
$logs = $report['logs'];
$hasLogErrors = false;

if (!empty($logs['liquidsoap'])) {
    $errLines = array_filter($logs['liquidsoap'], fn($l) => stripos($l, 'error') !== false || stripos($l, 'fatal') !== false || stripos($l, 'exception') !== false);
    if (!empty($errLines)) {
        $hasLogErrors = true;
        echo "{$C_YELLOW}>>> Baris Error Terkini di Liquidsoap Log (/var/log/radio/liquidsoap.log):{$C_RESET}\n";
        foreach (array_slice($errLines, -5) as $el) {
            echo "  {$C_RED}• {$el}{$C_RESET}\n";
        }
        echo "\n";
    }
}

if (!empty($logs['nginx_error'])) {
    $errLines = array_filter($logs['nginx_error'], fn($l) => stripos($l, 'error') !== false || stripos($l, 'crit') !== false);
    if (!empty($errLines)) {
        $hasLogErrors = true;
        echo "{$C_YELLOW}>>> Baris Error Terkini di Nginx Error Log (/var/log/nginx/radio_error.log):{$C_RESET}\n";
        foreach (array_slice($errLines, -5) as $el) {
            echo "  {$C_RED}• {$el}{$C_RESET}\n";
        }
        echo "\n";
    }
}

echo "{$C_CYAN}=============================================================================={$C_RESET}\n\n";
exit($summary['errors'] > 0 ? 1 : 0);
