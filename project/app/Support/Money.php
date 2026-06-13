<?php

declare(strict_types=1);

namespace App\Support;

final class Money
{
    public static function toCents(float|int|string $amount): int
    {
        if (is_int($amount)) {
            return $amount * 100;
        }

        $normalized = str_replace(",", ".", trim((string) $amount));

        if ($normalized === "") {
            return 0;
        }

        $sign = 1;
        if (str_starts_with($normalized, "-")) {
            $sign = -1;
            $normalized = substr($normalized, 1);
        }

        if (! preg_match("/^\\d+(?:\\.\\d+)?$/", $normalized)) {
            throw new \InvalidArgumentException("Некорректный формат суммы.");
        }

        [$whole, $fraction] = array_pad(explode(".", $normalized, 2), 2, "0");
        $fraction = str_pad($fraction, 3, "0");

        $cents = ((int) $whole) * 100 + (int) substr($fraction, 0, 2);
        $roundDigit = (int) substr($fraction, 2, 1);

        if ($roundDigit >= 5) {
            $cents += 1;
        }

        return $sign * $cents;
    }

    public static function fromCents(int $cents): string
    {
        $sign = $cents < 0 ? "-" : "";
        $absolute = abs($cents);
        $whole = intdiv($absolute, 100);
        $fraction = str_pad((string) ($absolute % 100), 2, "0", STR_PAD_LEFT);

        return "{$sign}{$whole}.{$fraction}";
    }
}
