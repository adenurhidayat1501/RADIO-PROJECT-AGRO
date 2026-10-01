<?php

declare(strict_types=1);

namespace App\Models;

class User extends BaseModel
{
    protected static string $collection = 'users';

    public static function findByUsernameOrEmail(string $identifier): ?array
    {
        $identifier = trim($identifier);
        return static::findOne([
            '$or' => [
                ['username' => $identifier],
                ['email' => $identifier],
            ]
        ]);
    }

    public static function createUser(array $data): array
    {
        if (isset($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
            unset($data['password']);
        }

        $data['role'] = $data['role'] ?? 'viewer';
        $data['status'] = $data['status'] ?? 'active';

        return static::create($data);
    }

    public static function updatePassword(string $id, string $plainPassword): bool
    {
        $hash = password_hash($plainPassword, PASSWORD_BCRYPT);
        return static::update($id, ['password_hash' => $hash]);
    }
}
