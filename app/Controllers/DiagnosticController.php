<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\DiagnosticService;

class DiagnosticController extends BaseController
{
    private DiagnosticService $diagnosticService;

    public function __construct()
    {
        $this->diagnosticService = new DiagnosticService();
    }

    /**
     * GET /admin/diagnostics
     * Render complete health & error analysis dashboard
     */
    public function index(): void
    {
        $report = $this->diagnosticService->runDiagnostics();

        $this->view('admin.diagnostics.index', [
            'summary' => $report['summary'],
            'checks' => $report['checks'],
            'logs' => $report['logs'],
        ]);
    }

    /**
     * POST /admin/diagnostics/repair
     * Execute automated self-healing repairs
     */
    public function repair(): void
    {
        $res = $this->diagnosticService->runAutoRepair();
        log_activity('system_auto_repair', ['steps' => count($res['steps'])]);

        $this->redirect('admin/diagnostics', [
            'success' => 'Perbaikan otomatis berhasil dijalankan! ' . implode(' | ', $res['steps'])
        ]);
    }
}
