<?php

declare(strict_types=1);

namespace App\Core;

class Request
{
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        private readonly array $body
    ) {
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = $_SERVER['PATH_INFO'] ?? '';

        if ($path === '') {
            $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
            $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

            if ($scriptName !== '' && str_starts_with($requestPath, $scriptName)) {
                $path = substr($requestPath, strlen($scriptName));
            } else {
                $scriptDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
                $path = $scriptDir !== '' && str_starts_with($requestPath, $scriptDir)
                    ? substr($requestPath, strlen($scriptDir))
                    : $requestPath;
            }
        }

        $path = '/' . trim((string) $path, '/');
        $raw = file_get_contents('php://input') ?: '';
        $body = [];

        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                throw new ApiException('Invalid JSON request body.', 400);
            }
            $body = $decoded;
        }

        return new self($method, $path === '/' ? '/' : rtrim($path, '/'), $_GET, $body);
    }

    public function input(): array
    {
        return $this->body;
    }
}

