<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Schedule;
use App\Models\Playlist;
use App\Models\DjAccount;
use App\Services\Database;

class ScheduleController extends BaseController
{
    public function index(): void
    {
        $grid = Schedule::getWeeklyGrid();
        $playlists = Playlist::find(['status' => 'active']);
        $djs = DjAccount::find(['status' => 'active']);

        $days = [
            1 => 'Senin (Monday)',
            2 => 'Selasa (Tuesday)',
            3 => 'Rabu (Wednesday)',
            4 => 'Kamis (Thursday)',
            5 => 'Jumat (Friday)',
            6 => 'Sabtu (Saturday)',
            7 => 'Minggu (Sunday)',
        ];

        $this->view('admin.schedules.index', [
            'grid' => $grid,
            'playlists' => $playlists,
            'djs' => $djs,
            'days' => $days,
        ]);
    }

    public function store(): void
    {
        $name = trim((string) ($_POST['name'] ?? ''));
        $day = (int) ($_POST['day'] ?? 1);
        $startTime = trim((string) ($_POST['start_time'] ?? '08:00'));
        $endTime = trim((string) ($_POST['end_time'] ?? '12:00'));
        $mode = trim((string) ($_POST['mode'] ?? 'auto_dj'));
        $playlistId = trim((string) ($_POST['playlist_id'] ?? ''));
        $djId = trim((string) ($_POST['dj_id'] ?? ''));

        if (empty($name)) {
            $this->redirect('admin/schedules', ['error' => 'Schedule title is required.']);
        }

        $data = [
            'name' => $name,
            'day' => $day,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'mode' => $mode,
            'playlist_id' => Database::isValidObjectId($playlistId) ? Database::toObjectId($playlistId) : null,
            'dj_id' => Database::isValidObjectId($djId) ? Database::toObjectId($djId) : null,
            'enabled' => true,
        ];

        Schedule::create($data);
        log_activity('create_schedule', ['name' => $name, 'day' => $day, 'time' => "{$startTime}-{$endTime}"]);

        $this->redirect('admin/schedules', ['success' => 'Broadcast time slot scheduled successfully!']);
    }

    public function delete(string $id): void
    {
        Schedule::delete($id);
        log_activity('delete_schedule', ['id' => $id]);
        $this->redirect('admin/schedules', ['success' => 'Time slot removed.']);
    }
}
