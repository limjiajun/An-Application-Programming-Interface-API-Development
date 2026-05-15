<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $db = Config::get('database');
        $dsn = sprintf(
            'pgsql:host=%s;port=%d;dbname=%s',
            $db['host'],
            $db['port'],
            $db['dbname']
        );

        try {
            self::$connection = new PDO($dsn, $db['user'], $db['password'], $db['options'] ?? []);
        } catch (PDOException $exception) {
            throw new ApiException('Database connection failed. Check PostgreSQL, config.php, and the pdo_pgsql PHP extension.', 500);
        }

        return self::$connection;
    }
}
