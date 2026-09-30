<?php

declare(strict_types=1);

/**
 * Front controller.
 *
 * FrankenPHP serves everything from this document root and falls back to this
 * file for any path that is not a real file, so a single entry point owns
 * routing, JSON endpoints and error handling.
 */

require_once __DIR__ . '/../bootstrap.php';

// PHP's built-in server routes every request through this script. Handing real
// files back to it mirrors Caddy's `try_files {path} /index.php`, so static
// assets behave identically locally and in the container.
if (PHP_SAPI === 'cli-server') {
    $requested = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

    if ($requested !== '/' && !str_contains($requested, '..')) {
        $file = __DIR__ . '/' . ltrim(rawurldecode($requested), '/');

        if (is_file($file) && !str_ends_with($file, '.php')) {
            return false;
        }
    }
}

use App\Config;
use App\HomePage;
use App\Response;
use App\Router;
use App\UserRepository;

$router = new Router();
$users = new UserRepository();

$router->get('/', static function (): void {
    Response::html(HomePage::render());
});

$router->get('/health', static function (): void {
    Response::json([
        'status' => 'ok',
        'app' => Config::appName(),
        'php' => PHP_VERSION,
        'runtime' => Config::runtimeName(),
        'timestamp' => gmdate('c'),
    ], 200, ['Cache-Control' => 'no-store']);
});

$router->get('/api/users', static function () use ($users): void {
    $role = isset($_GET['role']) ? (string) $_GET['role'] : null;
    $list = $users->all($role);

    Response::json(['data' => $list, 'count' => count($list)]);
});

$router->get('/api/users/{id}', static function (array $params) use ($users): void {
    $id = $params['id'] ?? '';

    if (!ctype_digit($id)) {
        Response::error(400, 'User id must be an integer');

        return;
    }

    $user = $users->find((int) $id);

    if ($user === null) {
        Response::error(404, sprintf('User %d not found', (int) $id));

        return;
    }

    Response::json(['data' => $user]);
});

$router->post('/api/users', static function () use ($users): void {
    $payload = json_decode((string) file_get_contents('php://input'), true);

    if (!is_array($payload) || !isset($payload['name'], $payload['email'])) {
        Response::error(422, 'Body must be JSON with "name" and "email"');

        return;
    }

    $created = $users->create([
        'name' => (string) $payload['name'],
        'email' => (string) $payload['email'],
        'role' => isset($payload['role']) ? (string) $payload['role'] : 'viewer',
    ]);

    Response::json(['data' => $created], 201, ['Location' => '/api/users/' . $created['id']]);
});

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');