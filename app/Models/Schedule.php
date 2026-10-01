<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Database;

class Schedule extends BaseModel
{
    protected static string $collection = 'schedules';

    public static function getScheduleForDay(int $day): array
    {
        return static::find(
            ['day' => $day, 'enabled' => true],
            ['sort' => ['start_time' => 1]]
        );
    }

    public static function getCurrentActiveSlot(int $day, string $time): ?array
    {
        return static::findOne([
            'day' => $day,
            'enabled' => true,
            'start_time' => ['$lte' => $time],
            'end_time' => ['$gt' => $time],
        ]);
    }

    public static function getWeeklyGrid(): array
    {
        $grid = [
            1 => [], // Monday
            2 => [], // Tuesday
            3 => [], // Wednesday
            4 => [], // Thursday
            5 => [], // Friday
            6 => [], // Saturday
            7 => [], // Sunday
        ];

        $all = static::find(['enabled' => true], ['sort' => ['start_time' => 1]]);
        foreach ($all as $item) {
            $day = (int) ($item['day'] ?? 1);
            if (isset($grid[$day])) {
                $grid[$day][] = $item;
            }
        }

        return $grid;
    }
}
