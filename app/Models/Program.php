<?php

declare(strict_types=1);

namespace App\Models;

class Program extends BaseModel
{
    protected static string $collection = 'programs';

    public static function getActivePrograms(): array
    {
        return static::find(['status' => 'active'], ['sort' => ['title' => 1]]);
    }
}
