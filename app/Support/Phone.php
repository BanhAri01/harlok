<?php

namespace App\Support;

use Illuminate\Support\Str;

class Phone
{
    public const SIMULATOR_PREFIX = 'sim';
    public const SAMPLE_PREFIX = 'cth';

    public static function channel(string $phone): string
    {
        return match (true) {
            str_starts_with($phone, self::SIMULATOR_PREFIX) => 'simulator',
            str_starts_with($phone, self::SAMPLE_PREFIX) => 'contoh',
            default => 'wa',
        };
    }

    public static function normalize(?string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $raw);

        if ($digits === '' || $digits === null) {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62'.$digits;
        }

        return preg_match('/^62\d{8,13}$/', $digits) ? $digits : null;
    }

    public static function simulator(): string
    {
        return self::SIMULATOR_PREFIX.Str::lower(Str::random(12));
    }

    public static function isSimulator(string $phone): bool
    {
        return str_starts_with($phone, self::SIMULATOR_PREFIX);
    }

    public static function mask(string $phone): string
    {
        if (self::channel($phone) !== 'wa') {
            return ucfirst(self::channel($phone));
        }

        return substr($phone, 0, 5).str_repeat('•', max(strlen($phone) - 8, 3)).substr($phone, -3);
    }

    public static function display(string $phone): string
    {
        if (self::channel($phone) !== 'wa') {
            return ucfirst(self::channel($phone));
        }

        return '0'.substr($phone, 2);
    }
}
