<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Router;
use App\UserRepository;

$failures = 0;
$tests = 0;

function assertSame(mixed $expected, mixed $actual, string $label): void
{
    global $failures, $tests;
    $tests++;

    if ($expected === $actual) {
        printf("  ok   %s\n", $label);

        return;
    }

    $failures++;
    printf(
        "  FAIL %s\n       expected: %s\n       actual:   %s\n",
        $label,
        var_export($expected, true),
        var_export($actual, true)
    );
}

$router = new Router();
$router->get('/api/users/{id}', static fn (array $params) => null);
$router->get('/', static fn (array $params) => null);

$match = $router->match('GET', '/api/users/42?full=1');
assertSame('42', $match['params']['id'] ?? null, 'router extracts path parameters');
assertSame(200, $match['status'], 'router resolves a known path');

assertSame(200, $router->match('GET', '/')['status'], 'router matches the root path');
assertSame(404, $router->match('GET', '/missing')['status'], 'unknown path returns 404');
assertSame(405, $router->match('POST', '/')['status'], 'wrong method returns 405');

$repository = new UserRepository();
assertSame(3, count($repository->all()), 'repository seeds three users');
assertSame(1, count($repository->all('admin')), 'repository filters by role');
assertSame(2, $repository->find(2)['id'] ?? null, 'repository finds a user by id');
assertSame(null, $repository->find(999), 'repository returns null for unknown id');
assertSame(4, $repository->create(['name' => 'Test', 'email' => 't@example.com'])['id'], 'repository creates a user');

printf("\n%d tests, %d failures\n", $tests, $failures);

exit($failures === 0 ? 0 : 1);