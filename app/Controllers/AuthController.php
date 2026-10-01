<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;

class AuthController extends BaseController
{
    public function showLoginForm(): void
    {
        if (Auth::check()) {
            $this->redirect('admin');
        }

        $this->view('admin.login', [
            'title' => 'Sign In to Radio Management',
        ]);
    }

    public function login(): void
    {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if (empty($username) || empty($password)) {
            $this->redirect('admin/login', ['error' => 'Username and password are required.']);
        }

        if (Auth::attempt($username, $password)) {
            $this->redirect('admin', ['success' => 'Welcome back to Radio Control Center!']);
        }

        $this->redirect('admin/login', ['error' => 'Invalid username or password credentials.']);
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('admin/login', ['success' => 'You have logged out successfully.']);
    }
}
