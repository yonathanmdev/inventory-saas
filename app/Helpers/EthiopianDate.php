<?php

namespace App\Helpers;

use DateTimeImmutable;
use DateTimeZone;

class EthiopianDate
{
    private const JDN_OFFSET     = 1723856;
    private const UNIX_EPOCH_JDN = 2440588;

    /** EC y/m/d → Gregorian 'Y-m-d', or null if the date is invalid. */
    public static function toGregorian(int $y, int $m, int $d): ?string
    {
        if ($y < 1900 || $y > 2200 || $m < 1 || $m > 13 || $d < 1) {
            return null;
        }

        $maxDay = $m < 13 ? 30 : ($y % 4 === 3 ? 6 : 5);   // Pagume has 5 or 6 days

        if ($d > $maxDay) {
            return null;
        }

        $jdn = self::JDN_OFFSET + 365 + 365 * ($y - 1) + intdiv($y, 4) + 30 * $m + $d - 31;

        return (new DateTimeImmutable('1970-01-01', new DateTimeZone('UTC')))
           ->modify(sprintf('%+d days', $jdn - self::UNIX_EPOCH_JDN))
            ->format('Y-m-d');
    }

    /** Gregorian 'Y-m-d' → [year, month, day] in the Ethiopian calendar. */
    public static function fromGregorian(string $ymd): array
    {
        $utc = new DateTimeImmutable($ymd, new DateTimeZone('UTC'));
        $jdn = intdiv($utc->getTimestamp(), 86400) + self::UNIX_EPOCH_JDN;

        $r = ($jdn - self::JDN_OFFSET) % 1461;
        $n = $r % 365 + 365 * intdiv($r, 1460);

        return [
            4 * intdiv($jdn - self::JDN_OFFSET, 1461) + intdiv($r, 365) - intdiv($r, 1460),
            intdiv($n, 30) + 1,
            $n % 30 + 1,
        ];
    }

    public static function today(): array
    {
        $local = new DateTimeImmutable('now', new DateTimeZone('Africa/Addis_Ababa'));

        return self::fromGregorian($local->format('Y-m-d'));
    }
    /** 'dd/mm/yyyy' (also with - or .) → [year, month, day], or null. */
public static function parse(string $value): ?array
{
    if (!preg_match('#^(\d{1,2})[/.\-](\d{1,2})[/.\-](\d{4})$#', trim($value), $m)) {
        return null;
    }

    return [(int) $m[3], (int) $m[2], (int) $m[1]];
}
}