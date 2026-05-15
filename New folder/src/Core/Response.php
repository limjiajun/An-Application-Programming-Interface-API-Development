<?php

declare(strict_types=1);

namespace App\Core;

class Response
{
    public static function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public static function success(mixed $data, int $status = 200, ?string $message = null): void
    {
        $payload = [
            'status' => 'success',
            'data' => $data,
        ];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        self::json($payload, $status);
    }

    public static function error(string $message, int $status = 400, array $errors = []): void
    {
        $payload = [
            'status' => 'error',
            'message' => $message,
        ];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        self::json($payload, $status);
    }
}

