<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($path)) {
        require $path;
    }
});

$configPath = dirname(__DIR__) . '/config/config.php';

if (!is_file($configPath)) {
    $configPath = dirname(__DIR__) . '/config/config.example.php';
}

App\Core\Config::load($configPath);
