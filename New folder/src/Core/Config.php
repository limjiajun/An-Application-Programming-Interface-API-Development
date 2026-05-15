<?php

declare(strict_types=1);

namespace App\Core;

class Config
{
    private static array $values = [];

    public static function load(string $path): void
    {
        if (!is_file($path)) {
            throw new ApiException('Missing config/config.php. Copy config/config.example.php first.', 500);
        }

        self::$values = require $path;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::$values;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}

