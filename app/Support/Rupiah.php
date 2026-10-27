<?php

namespace App\Support;

class Rupiah
{
    public static function format(int|float|null $amount): string
    {
        return 'Rp'.number_format((float) $amount, 0, ',', '.');
    }

    public static function kg(int|float $kg): string
    {
        $decimals = fmod($kg, 1.0) === 0.0 ? 0 : 1;

        return number_format($kg, $decimals, ',', '.').' kg';
    }

    public static function percent(float $value): string
    {
        return number_format(abs($value), 1, ',', '.').'%';
    }
}
