<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Program;

class ProgramController extends BaseController
{
    public function index(): void
    {
        $programs = Program::find([], ['sort' => ['title' => 1]]);
        $this->view('admin.programs.index', ['programs' => $programs]);
    }

    public function store(): void
    {
        $title = trim((string) ($_POST['title'] ?? ''));
        $host = trim((string) ($_POST['host'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $scheduleNote = trim((string) ($_POST['schedule_note'] ?? ''));

        if (empty($title)) {
            $this->redirect('admin/programs', ['error' => 'Program title is required.']);
        }

        Program::create([
            'title' => $title,
            'host' => $host,
            'description' => $description,
            'schedule_note' => $scheduleNote,
            'status' => 'active',
        ]);

        $this->redirect('admin/programs', ['success' => 'Program created successfully!']);
    }

    public function delete(string $id): void
    {
        Program::delete($id);
        $this->redirect('admin/programs', ['success' => 'Program deleted.']);
    }
}
