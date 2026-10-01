<?php

declare(strict_types=1);

namespace App\Models;

class Advertisement extends BaseModel
{
    protected static string $collection = 'advertisements';

    public static function getActiveAds(): array
    {
        return static::find(['enabled' => true], ['sort' => ['title' => 1]]);
    }
}
