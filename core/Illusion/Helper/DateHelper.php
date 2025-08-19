<?php

declare(strict_types=1);

namespace Illusion\Helper;

use DateTime;
use DateInterval;
use DateTimeInterface;
use InvalidArgumentException;

final class DateHelper
{
    private function __construct()
    {
        // Prevent instantiation
    }

    public static function now(): string
    {
        return (new DateTime())->format(DateTimeInterface::ATOM);
    }

    public static function format(string|DateTimeInterface $date, string $format = 'Y-m-d H:i:s'): string
    {
        $dateTime = self::parseDate($date);
        return $dateTime->format($format);
    }

    public static function diffInDays(string|DateTimeInterface $start, string|DateTimeInterface $end): int
    {
        $startDate = self::parseDate($start);
        $endDate = self::parseDate($end);
        
        $diff = $startDate->diff($endDate);
        return (int) $diff->format('%r%a');
    }

    public static function diffForHumans(string|DateTimeInterface $date): string
    {
        $dateTime = self::parseDate($date);
        $now = new DateTime();
        $diff = $dateTime->diff($now);
        
        if ($diff->y > 0) {
            return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
        }
        
        if ($diff->m > 0) {
            return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
        }
        
        if ($diff->d > 0) {
            return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
        }
        
        if ($diff->h > 0) {
            return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
        }
        
        if ($diff->i > 0) {
            return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
        }
        
        return 'just now';
    }

    public static function addDays(string|DateTimeInterface $date, int $days): string
    {
        $dateTime = self::parseDate($date);
        $interval = new DateInterval("P{$days}D");
        $dateTime->add($interval);
        
        return $dateTime->format('Y-m-d H:i:s');
    }

    public static function subtractDays(string|DateTimeInterface $date, int $days): string
    {
        $dateTime = self::parseDate($date);
        $interval = new DateInterval("P{$days}D");
        $dateTime->sub($interval);
        
        return $dateTime->format('Y-m-d H:i:s');
    }

    public static function isWeekend(string|DateTimeInterface $date): bool
    {
        $dateTime = self::parseDate($date);
        $dayOfWeek = (int) $dateTime->format('N');
        
        return $dayOfWeek >= 6;
    }

    public static function timestamp(): int
    {
        return time();
    }

    private static function parseDate(string|DateTimeInterface $date): DateTime
    {
        if ($date instanceof DateTimeInterface) {
            return DateTime::createFromInterface($date);
        }

        try {
            return new DateTime($date);
        } catch (\Exception $e) {
            throw new InvalidArgumentException('Invalid date format: ' . $date);
        }
    }
}