<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\DjAccount;
use App\Models\User;

class DjAccountController extends BaseController
{
    public function index(): void
    {
        $djs = DjAccount::find([], ['sort' => ['display_name' => 1]]);
        $this->view('admin.djs.index', ['djs' => $djs]);
    }

    public function create(): void
    {
        $users = User::find(['role' => ['$in' => ['dj', 'manager', 'admin']]]);
        $this->view('admin.djs.create', ['users' => $users]);
    }

    public function store(): void
    {
        $displayName = trim((string) ($_POST['display_name'] ?? ''));
        $username = trim((string) ($_POST['icecast_username'] ?? ''));
        $plainPassword = trim((string) ($_POST['icecast_password'] ?? ''));
        $userId = trim((string) ($_POST['user_id'] ?? ''));

        if (empty($displayName) || empty($username)) {
            $this->redirect('admin/djs/create', ['error' => 'Display name and username are required.']);
        }

        // Generate strong password if blank
        if (empty($plainPassword)) {
            $plainPassword = bin2hex(random_bytes(6));
        }

        $existing = DjAccount::findByUsername($username);
        if ($existing) {
            $this->redirect('admin/djs/create', ['error' => 'DJ Icecast username is already taken.']);
        }

        $dj = DjAccount::createDj([
            'user_id' => $userId,
            'display_name' => $displayName,
            'icecast_username' => $username,
            'mountpoint' => '/live-dj',
            'status' => 'active',
        ], $plainPassword);

        log_activity('create_dj_account', ['username' => $username, 'display_name' => $displayName]);

        // Flash credentials ONCE for secure setup by the operator
        $this->redirect('admin/djs', [
            'success' => "DJ Account created! PLEASE NOTE PASSWORD NOW: {$plainPassword}",
            'credentials_modal' => [
                'display_name' => $displayName,
                'username' => $username,
                'password' => $plainPassword,
                'mount' => '/live-dj',
                'port' => config('radio.liquidsoap.harbor_port', 8005),
            ]
        ]);
    }

    public function toggle(string $id): void
    {
        $dj = DjAccount::findById($id);
        if ($dj) {
            $newStatus = ($dj['status'] ?? 'active') === 'active' ? 'inactive' : 'active';
            DjAccount::update($id, ['status' => $newStatus]);
        }
        $this->redirect('admin/djs');
    }

    public function delete(string $id): void
    {
        DjAccount::delete($id);
        $this->redirect('admin/djs', ['success' => 'DJ account removed.']);
    }
}
