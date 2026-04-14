<?php

namespace App\Support;

/**
 * Standard units for new entries: gram (g), liter (L), piece (PCS).
 * Legacy DB values (ml, kg) remain supported for display and filters.
 */
final class MaterialUnit
{
    public const STANDARD_CODES = ['g', 'l', 'pcs'];

    /** All codes that may exist in the database. */
    public const ALL_CODES = ['g', 'l', 'pcs', 'ml', 'kg'];

    public static function label(?string $code): string
    {
        if ($code === null || $code === '') {
            return '';
        }

        $key = strtolower(trim($code));
        $translated = __('admin.'.$key);

        if ($translated !== 'admin.'.$key) {
            return $translated;
        }

        return strtoupper($key);
    }

    /**
     * @return array<string, string> value => label
     */
    public static function standardSelectOptions(): array
    {
        $out = [];
        foreach (self::STANDARD_CODES as $code) {
            $out[$code] = self::label($code);
        }

        return $out;
    }

    /**
     * Select options: standard units plus any legacy unit pinned from the form or material.
     *
     * @return array<string, string>
     */
    public static function optionsForForm(?string $currentUnit, ?string $materialUnit = null): array
    {
        $out = self::standardSelectOptions();

        foreach (array_filter([$currentUnit, $materialUnit]) as $raw) {
            $c = strtolower((string) $raw);
            if ($c !== '' && ! isset($out[$c])) {
                $out[$c] = self::label($c);
            }
        }

        return $out;
    }

    /**
     * @return array<string, string>
     */
    public static function filterOptions(): array
    {
        $out = [];
        foreach (self::ALL_CODES as $code) {
            $out[$code] = self::label($code);
        }

        return $out;
    }

    public static function formatQuantity(float|string|int|null $quantity, ?string $unitCode, int $maxDecimals = 2): string
    {
        if (! is_numeric($quantity)) {
            $qty = 0.0;
        } else {
            $qty = (float) $quantity;
        }

        $label = self::label($unitCode);

        if (abs($qty - round($qty)) < 0.000001) {
            $num = (string) (int) round($qty);
        } else {
            $num = rtrim(rtrim(number_format($qty, $maxDecimals, '.', ''), '0'), '.');
        }

        if ($label === '') {
            return $num;
        }

        return $num.' '.$label;
    }
}
