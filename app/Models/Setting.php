<?php

declare(strict_types=1);

namespace App\Models;

class Setting extends BaseModel
{
    protected static string $collection = 'settings';

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::findOne(['key' => $key]);
        return $setting ? ($setting['value'] ?? $default) : $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::getCollection()->updateOne(
            ['key' => $key],
            ['$set' => ['key' => $key, 'value' => $value, 'updated_at' => new \MongoDB\BSON\UTCDateTime()]],
            ['upsert' => true]
        );
    }

    public static function getAll(): array
    {
        $items = static::find();
        $settings = [];
        foreach ($items as $item) {
            $settings[$item['key']] = $item['value'] ?? null;
        }
        return $settings;
    }
}
