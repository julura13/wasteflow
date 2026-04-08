<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

final class DisplayDate
{
    public const CALENDAR = 'Y/m/d';

    public const CALENDAR_DATETIME = 'Y/m/d H:i';

    public static function format(?CarbonInterface $dateTime): string
    {
        return $dateTime === null ? '' : $dateTime->format(self::CALENDAR);
    }

    public static function formatOrEmpty(null|CarbonInterface|string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return Carbon::parse($value)->format(self::CALENDAR);
    }

    public static function formatDateTime(?CarbonInterface $dateTime): string
    {
        return $dateTime === null ? '' : $dateTime->format(self::CALENDAR_DATETIME);
    }
}
