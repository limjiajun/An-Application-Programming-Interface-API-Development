<?php

declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;

class Validator
{
    public static function cleanString(mixed $value, int $max = 255, bool $required = false): ?string
    {
        if ($value === null || $value === '') {
            if ($required) {
                throw new ApiException('A required text value is missing.', 422);
            }
            return null;
        }

        $text = trim((string) $value);
        if ($text === '' && $required) {
            throw new ApiException('A required text value is missing.', 422);
        }

        if (mb_strlen($text) > $max) {
            throw new ApiException("Text value is longer than {$max} characters.", 422);
        }

        return $text === '' ? null : $text;
    }

    public static function intOrNull(mixed $value, ?int $min = null): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            throw new ApiException('Integer value is invalid.', 422);
        }

        $number = (int) $value;
        if ($min !== null && $number < $min) {
            throw new ApiException("Integer value must be at least {$min}.", 422);
        }

        return $number;
    }

    public static function decimalOrNull(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $cleaned = preg_replace('/[,\s]/', '', strtoupper((string) $value));
        $cleaned = preg_replace('/^RM/', '', $cleaned);

        if (!is_numeric($cleaned)) {
            throw new ApiException('Decimal or currency value is invalid.', 422);
        }

        return number_format((float) $cleaned, 2, '.', '');
    }

    public static function dateOrNull(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $text = trim((string) $value);
        $formats = ['Y-m-d', 'd/m/Y', 'Ymd'];

        foreach ($formats as $format) {
            $date = DateTimeImmutable::createFromFormat('!' . $format, $text);
            if ($date !== false && $date->format($format) === $text) {
                return $date->format('Y-m-d');
            }
        }

        throw new ApiException('Date value is invalid. Use YYYY-MM-DD, DD/MM/YYYY, or YYYYMMDD.', 422);
    }

    public static function limit(array $query): int
    {
        $default = (int) Config::get('api.default_limit', 50);
        $max = (int) Config::get('api.max_limit', 500);
        $limit = self::intOrNull($query['limit'] ?? null, 1) ?? $default;

        return min($limit, $max);
    }

    public static function offset(array $query): int
    {
        return self::intOrNull($query['offset'] ?? null, 0) ?? 0;
    }

    public static function bool(array $query, string $key, bool $default = false): bool
    {
        if (!array_key_exists($key, $query)) {
            return $default;
        }

        return filter_var($query[$key], FILTER_VALIDATE_BOOLEAN);
    }
}

