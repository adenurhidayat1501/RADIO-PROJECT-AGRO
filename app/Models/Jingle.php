<?php

declare(strict_types=1);

namespace App\Models;

class Jingle extends BaseModel
{
    protected static string $collection = 'jingles';

    public static function getActiveJingles(): array
    {
        return static::find(['enabled' => true], ['sort' => ['title' => 1]]);
    }
}
