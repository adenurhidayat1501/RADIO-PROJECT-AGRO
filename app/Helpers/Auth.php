<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\User;

class Auth
{
    private const SESSION_USER_ID = '_auth_user_id';
    private static ?array $cachedUser = null;

    private static array $roleHierarchy = [
        'admin' => 4,
        'manager' => 3,
        'dj' => 2,
        'viewer' => 1,
    ];

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function user(): ?array
    {
        if (self::$cachedUser !== null) {
            return self::$cachedUser;
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $userId = $_SESSION[self::SESSION_USER_ID] ?? null;
        if (!$userId) {
            return null;
        }

        $user = User::findById($userId);
        if (!$user || ($user['status'] ?? 'active') !== 'active') {
            self::logout();
            return null;
        }

        self::$cachedUser = $user;
        return self::$cachedUser;
    }

    public static function id(): ?string
    {
        $user = self::user();
        return $user ? (string) $user['_id'] : null;
    }

    public static function role(): ?string
    {
        $user = self::user();
        return $user['role'] ?? null;
    }

    public static function hasRole(string ...$roles): bool
    {
        $currentRole = self::role();
        if (!$currentRole) {
            return false;
        }

        // If admin, they have access to everything
        if ($currentRole === 'admin') {
            return true;
        }

        return in_array($currentRole, $roles, true);
    }

    public static function attempt(string $username, string $password): bool
    {
        $user = User::findByUsernameOrEmail($username);
        if (!$user) {
            return false;
        }

        if (($user['status'] ?? 'active') !== 'active') {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);
        $_SESSION[self::SESSION_USER_ID] = (string) $user['_id'];
        self::$cachedUser = $user;

        log_activity('user_login', ['username' => $user['username']], (string) $user['_id']);

        return true;
    }

    public static function logout(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!empty($_SESSION[self::SESSION_USER_ID])) {
            log_activity('user_logout', [], (string) $_SESSION[self::SESSION_USER_ID]);
        }

        unset($_SESSION[self::SESSION_USER_ID]);
        self::$cachedUser = null;
        session_regenerate_id(true);
    }
}
