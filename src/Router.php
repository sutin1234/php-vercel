<?php

declare(strict_types=1);

namespace App;

/**
 * Tiny regex router: first matching path wins, then the method is checked.
 */
final class Router
{
    /** @var list<array{method: string, regex: string, handler: callable}> */
    private array $routes = [];

    public function get(string $pattern, callable $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    public function add(string $method, string $pattern, callable $handler): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'regex' => '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $pattern) . '$#',
            'handler' => $handler,
        ];
    }

    /**
     * Resolve a request without executing anything, so the resolution logic
     * stays testable outside of a live HTTP context.
     *
     * @return array{status: int, handler?: callable, params?: array<string, string>}
     */
    public function match(string $method, string $path): array
    {
        $method = strtoupper($method);
        $path = '/' . trim((string) (parse_url($path, PHP_URL_PATH) ?: '/'), '/');
        $pathMatched = false;

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }

            $pathMatched = true;

            if ($route['method'] !== $method) {
                continue;
            }

            return [
                'status' => 200,
                'handler' => $route['handler'],
                'params' => array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY),
            ];
        }

        return ['status' => $pathMatched ? 405 : 404];
    }

    /** @param array<string, string> $params */
    public function dispatch(string $method, string $path): void
    {
        $match = $this->match($method, $path);

        if (isset($match['handler'])) {
            ($match['handler'])($match['params'] ?? []);

            return;
        }

        if ($match['status'] === 405) {
            header('Allow: GET, POST');
            Response::error(405, 'Method not allowed');

            return;
        }

        Response::error(404, 'Route not found: ' . $path);
    }
}