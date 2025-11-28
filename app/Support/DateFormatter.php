<?php

namespace App\Support;

use DateTime;
use DateTimeInterface;

class DateFormatter
{
    public const NORWEGIAN_FORMAT = 'd.m.Y';

    public const DATABASE_FORMAT = 'Y-m-d';

    /**
     * Convert a Norwegian format date (DD.MM.YYYY) to database format (YYYY-MM-DD).
     * Returns today's date if parsing fails.
     */
    public static function norwegianToDatabase(string $norwegianDate): string
    {
        $dateObject = DateTime::createFromFormat(self::NORWEGIAN_FORMAT, $norwegianDate);

        return $dateObject ? $dateObject->format(self::DATABASE_FORMAT) : now()->format(self::DATABASE_FORMAT);
    }

    /**
     * Convert a database format date (YYYY-MM-DD) or DateTime object to Norwegian format (DD.MM.YYYY).
     */
    public static function databaseToNorwegian(DateTimeInterface|string $date): string
    {
        if (is_string($date)) {
            $dateObject = DateTime::createFromFormat(self::DATABASE_FORMAT, $date);

            return $dateObject ? $dateObject->format(self::NORWEGIAN_FORMAT) : '';
        }

        return $date->format(self::NORWEGIAN_FORMAT);
    }

    /**
     * Return today's date in Norwegian format (DD.MM.YYYY).
     */
    public static function todayNorwegian(): string
    {
        return now()->format(self::NORWEGIAN_FORMAT);
    }
}
