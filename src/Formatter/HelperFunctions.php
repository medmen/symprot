<?php

declare(strict_types=1);

namespace App\Formatter;

final class HelperFunctions
{
    private function __construct()
    {
        // static utility class
    }

    public static function parseTaToSeconds(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }
        // handle mm:ss
        if (str_contains($value, ':')) {
            [$m, $s] = array_pad(explode(':', $value, 2), 2, '0');
            $m = (int)trim($m);
            $s = (int)trim($s);
            if ($m < 0) { $m = 0; }
            if ($s < 0) { $s = 0; }
            return $m * 60 + $s;
        }
        // plain seconds
        if (is_numeric($value)) {
            $sec = (int)$value;
            return $sec > 0 ? $sec : 0;
        }
        return 0;
    }

    public static function formatSeconds(int $seconds): string
    {
        $minutes = intdiv($seconds, 60);
        $secs = $seconds % 60;
        return sprintf('%d:%02d', $minutes, $secs);
    }

    public static function isStarter(array $row): bool
    {
        $seq = strtolower(trim((string)($row['sequence'] ?? '')));
        if ($seq === '') {
            return false;
        }
        return str_contains($seq, 'localizer') || str_contains($seq, 'scout') || str_contains($seq, 'topo');
    }
}
