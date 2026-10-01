<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Database;

class DjAccount extends BaseModel
{
    protected static string $collection = 'dj_accounts';

    public static function createDj(array $data, string $plainPassword): array
    {
        $data['icecast_password_hash'] = password_hash($plainPassword, PASSWORD_BCRYPT);
        $data['status'] = $data['status'] ?? 'active';
        $data['mountpoint'] = $data['mountpoint'] ?? '/live';

        if (isset($data['user_id']) && Database::isValidObjectId($data['user_id'])) {
            $data['user_id'] = Database::toObjectId($data['user_id']);
        }

        return static::create($data);
    }

    public static function findByUsername(string $username): ?array
    {
        return static::findOne(['icecast_username' => $username]);
    }

    public static function findByUserId(string $userId): ?array
    {
        if (!Database::isValidObjectId($userId)) {
            return null;
        }
        return static::findOne(['user_id' => Database::toObjectId($userId)]);
    }

    public static function verifyPassword(string $username, string $plainPassword): bool
    {
        $dj = static::findByUsername($username);
        if (!$dj || ($dj['status'] ?? 'active') !== 'active') {
            return false;
        }

        return password_verify($plainPassword, $dj['icecast_password_hash'] ?? '');
    }
}
