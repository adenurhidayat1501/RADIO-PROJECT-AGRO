<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;

class UserController extends BaseController
{
    public function index(): void
    {
        $users = User::find([], ['sort' => ['username' => 1]]);
        $this->view('admin.users.index', ['users' => $users]);
    }

    public function create(): void
    {
        $this->view('admin.users.create');
    }

    public function store(): void
    {
        $username = trim((string) ($_POST['username'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $displayName = trim((string) ($_POST['display_name'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $role = trim((string) ($_POST['role'] ?? 'viewer'));

        if (empty($username) || empty($password)) {
            $this->redirect('admin/users/create', ['error' => 'Username and password are required.']);
        }

        $existing = User::findByUsernameOrEmail($username);
        if ($existing) {
            $this->redirect('admin/users/create', ['error' => 'Username already taken.']);
        }

        User::createUser([
            'username' => $username,
            'email' => $email,
            'display_name' => !empty($displayName) ? $displayName : $username,
            'password' => $password,
            'role' => $role,
            'status' => 'active',
        ]);

        log_activity('create_user', ['username' => $username, 'role' => $role]);

        $this->redirect('admin/users', ['success' => 'User created successfully!']);
    }

    public function edit(string $id): void
    {
        $user = User::findById($id);
        if (!$user) {
            $this->redirect('admin/users', ['error' => 'User not found.']);
        }

        $this->view('admin.users.edit', ['user' => $user]);
    }

    public function update(string $id): void
    {
        $user = User::findById($id);
        if (!$user) {
            $this->redirect('admin/users', ['error' => 'User not found.']);
        }

        $newRole = trim((string) ($_POST['role'] ?? ''));
        $newStatus = trim((string) ($_POST['status'] ?? ''));

        if ($user['username'] === 'admin') {
            $newRole = 'admin';
            $newStatus = 'active';
        } else {
            $newRole = !empty($newRole) ? $newRole : ($user['role'] ?? 'viewer');
            $newStatus = !empty($newStatus) ? $newStatus : ($user['status'] ?? 'active');
        }

        $data = [
            'display_name' => trim((string) ($_POST['display_name'] ?? $user['display_name'])),
            'email' => trim((string) ($_POST['email'] ?? $user['email'])),
            'role' => $newRole,
            'status' => $newStatus,
        ];

        // If new password provided
        $newPass = trim((string) ($_POST['password'] ?? ''));
        if (!empty($newPass)) {
            $data['password_hash'] = password_hash($newPass, PASSWORD_BCRYPT);
        }

        User::update($id, $data);
        log_activity('update_user', ['id' => $id, 'username' => $user['username']]);

        $this->redirect('admin/users', ['success' => 'User profile updated!']);
    }

    public function delete(string $id): void
    {
        $user = User::findById($id);
        // Prevent deleting last admin or self
        if ($user && $user['username'] === 'admin') {
            $this->redirect('admin/users', ['error' => 'Cannot delete system primary administrator.']);
        }

        User::delete($id);
        $this->redirect('admin/users', ['success' => 'User account removed.']);
    }
}
