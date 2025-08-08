<?php

declare(strict_types=1);

namespace Plugs\Illusion\Carbon;

class Carbon extends \DateTime
{
    // Common formats
    const DEFAULT_TO_STRING_FORMAT = 'Y-m-d H:i:s';
    
    // Days of week
    const SUNDAY = 0;
    const MONDAY = 1;
    const TUESDAY = 2;
    const WEDNESDAY = 3;
    const THURSDAY = 4;
    const FRIDAY = 5;
    const SATURDAY = 6;
    
    // Months
    const JANUARY = 1;
    const FEBRUARY = 2;
    const MARCH = 3;
    const APRIL = 4;
    const MAY = 5;
    const JUNE = 6;
    const JULY = 7;
    const AUGUST = 8;
    const SEPTEMBER = 9;
    const OCTOBER = 10;
    const NOVEMBER = 11;
    const DECEMBER = 12;

    /**
     * Create a new Carbon instance.
     *
     * @param string|\DateTimeInterface|null $time
     * @param \DateTimeZone|string|null $timezone
     */
    public function __construct($time = 'now', \DateTimeZone|string|null $timezone = null)
    {
        if ($time instanceof \DateTimeInterface) {
            $time = $time->format('Y-m-d H:i:s.u');
        }

        parent::__construct($time ?? 'now', static::safeCreateDateTimeZone($timezone));
    }

    /**
     * Create a new instance from a DateTime object.
     */
    public static function instance(\DateTimeInterface $dt): self
    {
        $timezone = $dt->getTimezone();
        return new static($dt->format('Y-m-d H:i:s.u'), $timezone !== false ? $timezone : null);
    }

    /**
     * Create a new Carbon instance for today.
     */
    public static function today($timezone = null): self
    {
        return static::now($timezone)->startOfDay();
    }

    /**
     * Create a new Carbon instance for now.
     */
    public static function now($timezone = null): self
    {
        return new static(null, $timezone);
    }

    /**
     * Create a new Carbon instance for yesterday.
     */
    public static function yesterday($timezone = null): self
    {
        return static::now($timezone)->subDay()->startOfDay();
    }

    /**
     * Create a new Carbon instance for tomorrow.
     */
    public static function tomorrow($timezone = null): self
    {
        return static::now($timezone)->addDay()->startOfDay();
    }

    /**
     * Create a new Carbon instance from a specific date and time.
     */
    public static function create(
        int $year,
        int $month,
        int $day,
        int $hour = 0,
        int $minute = 0,
        int $second = 0,
        $timezone = null
    ): self {
        return static::createFromFormat(
            'Y-n-j G:i:s',
            sprintf('%s-%s-%s %s:%s:%s', $year, $month, $day, $hour, $minute, $second),
            $timezone
        );
    }

    /**
     * Create a Carbon instance from a format.
     */
    public static function createFromFormat($format, $time, $timezone = null): self
    {
        if ($timezone !== null) {
            $dt = parent::createFromFormat($format, $time, static::safeCreateDateTimeZone($timezone));
        } else {
            $dt = parent::createFromFormat($format, $time);
        }

        if ($dt === false) {
            $errors = static::getLastErrors();
            $errorMessages = $errors ? ($errors['errors'] ?? ['Invalid date format']) : ['Invalid date format'];
            throw new \InvalidArgumentException(implode(PHP_EOL, $errorMessages));
        }

        return static::instance($dt);
    }

    /**
     * Create a Carbon instance from a timestamp.
     */
    public static function createFromTimestamp(int|float $timestamp): self
    {
        $dt = parent::createFromTimestamp($timestamp);
        return static::instance($dt);
    }

    /**
     * Parse a date string into a Carbon instance.
     * This is a more flexible alternative to createFromFormat.
     */
    public static function parse($time = null, $timezone = null): self
    {
        if ($time === null) {
            return static::now($timezone);
        }

        if ($time instanceof \DateTimeInterface) {
            return static::instance($time);
        }

        if (is_numeric($time)) {
            return static::createFromTimestamp((int) $time);
        }

        try {
            return new static($time, $timezone);
        } catch (\Exception $e) {
            // Try common formats if direct parsing fails
            $commonFormats = [
                'Y-m-d H:i:s',
                'Y-m-d H:i:s.u',
                'Y-m-d',
                'd/m/Y',
                'm/d/Y',
                'd-m-Y',
                'm-d-Y',
                'Y/m/d',
                'Y-m-d\TH:i:s',
                'Y-m-d\TH:i:sP',
                'Y-m-d\TH:i:s.uP',
                \DateTime::ATOM,
                \DateTime::ISO8601,
                \DateTime::RFC2822,
                \DateTime::RFC3339,
                \DateTime::RSS,
            ];

            foreach ($commonFormats as $format) {
                try {
                    return static::createFromFormat($format, $time, $timezone);
                } catch (\InvalidArgumentException $formatException) {
                    continue;
                }
            }

            throw new \InvalidArgumentException("Failed to parse time string: {$time}. Original error: " . $e->getMessage());
        }
    }

    /**
     * Set the time by time string.
     */
    public function setTimeFromTimeString(string $time): self
    {
        $time = explode(':', $time);
        $hour = $time[0];
        $minute = $time[1] ?? 0;
        $second = $time[2] ?? 0;

        return $this->setTime((int) $hour, (int) $minute, (int) $second);
    }

    /**
     * Resets the time to 00:00:00
     */
    public function startOfDay(): self
    {
        return $this->setTime(0, 0, 0, 0);
    }

    /**
     * Resets the time to 23:59:59.999999
     */
    public function endOfDay(): self
    {
        return $this->setTime(23, 59, 59, 999999);
    }

    /**
     * Modify to the start of the week (Monday)
     */
    public function startOfWeek(): self
    {
        $dayOfWeek = $this->dayOfWeek();
        $daysToSubtract = ($dayOfWeek === static::SUNDAY) ? 6 : $dayOfWeek - static::MONDAY;
        return $this->subDays($daysToSubtract)->startOfDay();
    }

    /**
     * Modify to the end of the week (Sunday)
     */
    public function endOfWeek(): self
    {
        $dayOfWeek = $this->dayOfWeek();
        $daysToAdd = (static::SUNDAY === 0) ? (7 - $dayOfWeek) % 7 : static::SUNDAY - $dayOfWeek;
        if ($daysToAdd === 0 && $dayOfWeek !== static::SUNDAY) {
            $daysToAdd = 7;
        }
        return $this->addDays($daysToAdd)->endOfDay();
    }

    /**
     * Modify to the start of the month
     */
    public function startOfMonth(): self
    {
        $dt = $this->copy();
        $dt->setDate((int) $this->format('Y'), (int) $this->format('n'), 1);
        return $dt->startOfDay();
    }

    /**
     * Modify to the end of the month
     */
    public function endOfMonth(): self
    {
        $dt = $this->copy();
        $dt->setDate((int) $this->format('Y'), (int) $this->format('n'), (int) $this->format('t'));
        return $dt->endOfDay();
    }

    /**
     * Modify to the start of the year
     */
    public function startOfYear(): self
    {
        $dt = $this->copy();
        $dt->setDate((int) $this->format('Y'), 1, 1);
        return $dt->startOfDay();
    }

    /**
     * Modify to the end of the year
     */
    public function endOfYear(): self
    {
        $dt = $this->copy();
        $dt->setDate((int) $this->format('Y'), 12, 31);
        return $dt->endOfDay();
    }

    /**
     * Add one day
     */
    public function addDay(): self
    {
        return $this->modify('+1 day');
    }

    /**
     * Add days
     */
    public function addDays(int $value): self
    {
        return $this->modify("+$value days");
    }

    /**
     * Subtract one day
     */
    public function subDay(): self
    {
        return $this->modify('-1 day');
    }

    /**
     * Subtract days
     */
    public function subDays(int $value): self
    {
        return $this->modify("-$value days");
    }

    /**
     * Add one week
     */
    public function addWeek(): self
    {
        return $this->addDays(7);
    }

    /**
     * Add weeks
     */
    public function addWeeks(int $value): self
    {
        return $this->addDays($value * 7);
    }

    /**
     * Subtract one week
     */
    public function subWeek(): self
    {
        return $this->subDays(7);
    }

    /**
     * Subtract weeks
     */
    public function subWeeks(int $value): self
    {
        return $this->subDays($value * 7);
    }

    /**
     * Add one month
     */
    public function addMonth(): self
    {
        return $this->modify('+1 month');
    }

    /**
     * Add months
     */
    public function addMonths(int $value): self
    {
        return $this->modify("+$value months");
    }

    /**
     * Subtract one month
     */
    public function subMonth(): self
    {
        return $this->modify('-1 month');
    }

    /**
     * Subtract months
     */
    public function subMonths(int $value): self
    {
        return $this->modify("-$value months");
    }

    /**
     * Add one year
     */
    public function addYear(): self
    {
        return $this->modify('+1 year');
    }

    /**
     * Add years
     */
    public function addYears(int $value): self
    {
        return $this->modify("+$value years");
    }

    /**
     * Subtract one year
     */
    public function subYear(): self
    {
        return $this->modify('-1 year');
    }

    /**
     * Subtract years
     */
    public function subYears(int $value): self
    {
        return $this->modify("-$value years");
    }

    /**
     * Add one hour
     */
    public function addHour(): self
    {
        return $this->modify('+1 hour');
    }

    /**
     * Add hours
     */
    public function addHours(int $value): self
    {
        return $this->modify("+$value hours");
    }

    /**
     * Subtract one hour
     */
    public function subHour(): self
    {
        return $this->modify('-1 hour');
    }

    /**
     * Subtract hours
     */
    public function subHours(int $value): self
    {
        return $this->modify("-$value hours");
    }

    /**
     * Add one minute
     */
    public function addMinute(): self
    {
        return $this->modify('+1 minute');
    }

    /**
     * Add minutes
     */
    public function addMinutes(int $value): self
    {
        return $this->modify("+$value minutes");
    }

    /**
     * Subtract one minute
     */
    public function subMinute(): self
    {
        return $this->modify('-1 minute');
    }

    /**
     * Subtract minutes
     */
    public function subMinutes(int $value): self
    {
        return $this->modify("-$value minutes");
    }

    /**
     * Add one second
     */
    public function addSecond(): self
    {
        return $this->modify('+1 second');
    }

    /**
     * Add seconds
     */
    public function addSeconds(int $value): self
    {
        return $this->modify("+$value seconds");
    }

    /**
     * Subtract one second
     */
    public function subSecond(): self
    {
        return $this->modify('-1 second');
    }

    /**
     * Subtract seconds
     */
    public function subSeconds(int $value): self
    {
        return $this->modify("-$value seconds");
    }

    /**
     * Get the difference in years
     */
    public function diffInYears(?\DateTimeInterface $dt = null, bool $abs = true): int
    {
        $dt = $dt ? static::instance($dt) : static::now($this->getTimezone());
        return (int) $this->diff($dt, $abs)->format('%r%y');
    }

    /**
     * Get the difference in months
     */
    public function diffInMonths(?\DateTimeInterface $dt = null, bool $abs = true): int
    {
        $dt = $dt ? static::instance($dt) : static::now($this->getTimezone());
        return $this->diffInYears($dt, $abs) * 12 + (int) $this->diff($dt, $abs)->format('%r%m');
    }

    /**
     * Get the difference in weeks
     */
    public function diffInWeeks(?\DateTimeInterface $dt = null, bool $abs = true): int
    {
        return (int) ($this->diffInDays($dt, $abs) / 7);
    }

    /**
     * Get the difference in days
     */
    public function diffInDays(?\DateTimeInterface $dt = null, bool $abs = true): int
    {
        $dt = $dt ? static::instance($dt) : static::now($this->getTimezone());
        return (int) $this->diff($dt, $abs)->format('%r%a');
    }

    /**
     * Get the difference in hours
     */
    public function diffInHours(?\DateTimeInterface $dt = null, bool $abs = true): int
    {
        return (int) ($this->diffInSeconds($dt, $abs) / 3600);
    }

    /**
     * Get the difference in minutes
     */
    public function diffInMinutes(?\DateTimeInterface $dt = null, bool $abs = true): int
    {
        return (int) ($this->diffInSeconds($dt, $abs) / 60);
    }

    /**
     * Get the difference in seconds
     */
    public function diffInSeconds(?\DateTimeInterface $dt = null, bool $abs = true): int
    {
        $dt = $dt ? static::instance($dt) : static::now($this->getTimezone());
        $value = $dt->getTimestamp() - $this->getTimestamp();
        return $abs ? abs($value) : $value;
    }

    /**
     * Get the day of week
     */
    public function dayOfWeek(): int
    {
        return (int) $this->format('w');
    }

    /**
     * Get the day of year
     */
    public function dayOfYear(): int
    {
        return (int) $this->format('z') + 1;
    }

    /**
     * Get the week of year
     */
    public function weekOfYear(): int
    {
        return (int) $this->format('W');
    }

    /**
     * Get the days in the month
     */
    public function daysInMonth(): int
    {
        return (int) $this->format('t');
    }

    /**
     * Checks if this date is a weekday
     */
    public function isWeekday(): bool
    {
        return !$this->isWeekend();
    }

    /**
     * Checks if this date is a weekend
     */
    public function isWeekend(): bool
    {
        return in_array($this->dayOfWeek(), [static::SATURDAY, static::SUNDAY], true);
    }

    /**
     * Checks if this date is today
     */
    public function isToday(): bool
    {
        return $this->toDateString() === static::now($this->getTimezone())->toDateString();
    }

    /**
     * Checks if this date is yesterday
     */
    public function isYesterday(): bool
    {
        return $this->toDateString() === static::yesterday($this->getTimezone())->toDateString();
    }

    /**
     * Checks if this date is tomorrow
     */
    public function isTomorrow(): bool
    {
        return $this->toDateString() === static::tomorrow($this->getTimezone())->toDateString();
    }

    /**
     * Checks if this date is in the future
     */
    public function isFuture(): bool
    {
        return $this->greaterThan(static::now($this->getTimezone()));
    }

    /**
     * Checks if this date is in the past
     */
    public function isPast(): bool
    {
        return $this->lessThan(static::now($this->getTimezone()));
    }

    /**
     * Checks if this date is a leap year
     */
    public function isLeapYear(): bool
    {
        return $this->format('L') === '1';
    }

    /**
     * Checks if this date is equal to another
     */
    public function eq(\DateTimeInterface $dt): bool
    {
        return $this == static::instance($dt);
    }

    /**
     * Alias for eq()
     */
    public function equalTo(\DateTimeInterface $dt): bool
    {
        return $this->eq($dt);
    }

    /**
     * Checks if this date is not equal to another
     */
    public function ne(\DateTimeInterface $dt): bool
    {
        return !$this->eq($dt);
    }

    /**
     * Alias for ne()
     */
    public function notEqualTo(\DateTimeInterface $dt): bool
    {
        return $this->ne($dt);
    }

    /**
     * Checks if this date is greater than another
     */
    public function gt(\DateTimeInterface $dt): bool
    {
        return $this > static::instance($dt);
    }

    /**
     * Alias for gt()
     */
    public function greaterThan(\DateTimeInterface $dt): bool
    {
        return $this->gt($dt);
    }

    /**
     * Checks if this date is greater than or equal to another
     */
    public function gte(\DateTimeInterface $dt): bool
    {
        return $this >= static::instance($dt);
    }

    /**
     * Alias for gte()
     */
    public function greaterThanOrEqualTo(\DateTimeInterface $dt): bool
    {
        return $this->gte($dt);
    }

    /**
     * Checks if this date is less than another
     */
    public function lt(\DateTimeInterface $dt): bool
    {
        return $this < static::instance($dt);
    }

    /**
     * Alias for lt()
     */
    public function lessThan(\DateTimeInterface $dt): bool
    {
        return $this->lt($dt);
    }

    /**
     * Checks if this date is less than or equal to another
     */
    public function lte(\DateTimeInterface $dt): bool
    {
        return $this <= static::instance($dt);
    }

    /**
     * Alias for lte()
     */
    public function lessThanOrEqualTo(\DateTimeInterface $dt): bool
    {
        return $this->lte($dt);
    }

    /**
     * Check if this date is between two other dates
     */
    public function between(\DateTimeInterface $dt1, \DateTimeInterface $dt2, bool $equal = true): bool
    {
        $start = static::instance($dt1);
        $end = static::instance($dt2);

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        if ($equal) {
            return $this->gte($start) && $this->lte($end);
        }

        return $this->gt($start) && $this->lt($end);
    }

    /**
     * Return the minimum of this and another date
     */
    public function min(\DateTimeInterface $dt): self
    {
        return $this->lt($dt) ? $this : static::instance($dt);
    }

    /**
     * Return the maximum of this and another date
     */
    public function max(\DateTimeInterface $dt): self
    {
        return $this->gt($dt) ? $this : static::instance($dt);
    }

    /**
     * Return the date in Y-m-d format
     */
    public function toDateString(): string
    {
        return $this->format('Y-m-d');
    }

    /**
     * Return the time in H:i:s format
     */
    public function toTimeString(): string
    {
        return $this->format('H:i:s');
    }

    /**
     * Return the date in Y-m-d H:i:s format
     */
    public function toDateTimeString(): string
    {
        return $this->format('Y-m-d H:i:s');
    }

    /**
     * Return the date in Y-m-d H:i:s.u format
     */
    public function toDateTimeMicroString(): string
    {
        return $this->format('Y-m-d H:i:s.u');
    }

    /**
     * Return the date in ISO 8601 format
     */
    public function toIsoString(): string
    {
        return $this->format('Y-m-d\TH:i:s.uP');
    }

    /**
     * Return the date in RFC 2822 format
     */
    public function toRfc2822String(): string
    {
        return $this->format('r');
    }

    /**
     * Return the date in Atom format
     */
    public function toAtomString(): string
    {
        return $this->format(\DateTime::ATOM);
    }

    /**
     * Return the date as a human-readable string
     */
    public function toHumanString(): string
    {
        $now = static::now($this->getTimezone());

        if ($this->isSameDay($now)) {
            return 'Today at '.$this->format('g:i A');
        }

        if ($this->isSameDay($now->subDay())) {
            return 'Yesterday at '.$this->format('g:i A');
        }

        if ($this->isSameDay($now->addDays(2))) { // Reset to tomorrow
            return 'Tomorrow at '.$this->format('g:i A');
        }

        if ($this->diffInYears($now) < 1) {
            return $this->format('M j \a\t g:i A');
        }

        return $this->format('M j, Y \a\t g:i A');
    }

    /**
     * Checks if this date is the same day as another
     */
    public function isSameDay(\DateTimeInterface $dt): bool
    {
        return $this->toDateString() === static::instance($dt)->toDateString();
    }

    /**
     * Checks if this date is the same month as another
     */
    public function isSameMonth(\DateTimeInterface $dt): bool
    {
        $other = static::instance($dt);
        return $this->format('Y-m') === $other->format('Y-m');
    }

    /**
     * Checks if this date is the same year as another
     */
    public function isSameYear(\DateTimeInterface $dt): bool
    {
        $other = static::instance($dt);
        return $this->format('Y') === $other->format('Y');
    }

    /**
     * Get a copy of this instance
     */
    public function copy(): self
    {
        return clone $this;
    }

    /**
     * Handle dynamic method calls
     */
    public function __call($method, $parameters)
    {
        // Handle startsWith('add') and startsWith('sub')
        if (strncmp($method, 'add', 3) === 0) {
            $unit = lcfirst(substr($method, 3));
            $value = $parameters[0] ?? 1;
            return $this->modify("+{$value} {$unit}");
        }

        if (strncmp($method, 'sub', 3) === 0) {
            $unit = lcfirst(substr($method, 3));
            $value = $parameters[0] ?? 1;
            return $this->modify("-{$value} {$unit}");
        }

        throw new \BadMethodCallException(sprintf(
            'Call to undefined method %s::%s()', static::class, $method
        ));
    }

    /**
     * Safely create DateTimeZone instance
     */
    protected static function safeCreateDateTimeZone(\DateTimeZone|string|null $timezone): ?\DateTimeZone
    {
        if ($timezone === null) {
            return null;
        }

        if ($timezone instanceof \DateTimeZone) {
            return $timezone;
        }

        try {
            return new \DateTimeZone($timezone);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Default format for __toString
     */
    public function __toString(): string
    {
        return $this->toDateTimeString();
    }
}