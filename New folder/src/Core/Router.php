<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function put(string $path, callable $handler): void
    {
        $this->add('PUT', $path, $handler);
    }

    public function patch(string $path, callable $handler): void
    {
        $this->add('PATCH', $path, $handler);
    }

    public function delete(string $path, callable $handler): void
    {
        $this->add('DELETE', $path, $handler);
    }

    public function dispatch(Request $request): void
    {
        foreach ($this->routes[$request->method] ?? [] as $route) {
            if (preg_match($route['pattern'], $request->path, $matches)) {
                $params = array_filter(
                    $matches,
                    static fn ($key): bool => is_string($key),
                    ARRAY_FILTER_USE_KEY
                );

                call_user_func($route['handler'], $request, $params);
                return;
            }
        }

        throw new ApiException('Endpoint not found.', 404);
    }

    private function add(string $method, string $path, callable $handler): void
    {
        $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $path);
        $this->routes[$method][] = [
            'pattern' => '#^' . $pattern . '$#',
            'handler' => $handler,
        ];
    }
}

