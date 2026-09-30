<?php

declare(strict_types=1);

/**
 * Autoloader entry point.
 *
 * Lives outside the public document root so it is never served over HTTP.
 * The container build runs `composer install`, so vendor/autoload.php exists in
 * production; the fallback keeps the app bootable before the first install.
 */

$vendor = __DIR__ . '/vendor/autoload.php';

if (is_file($vendor)) {
    require_once $vendor;
} else {
    spl_autoload_register(static function (string $class): void {
        if (!str_starts_with($class, 'App\\')) {
            return;
        }

        $path = __DIR__ . '/src/' . str_replace('\\', '/', substr($class, 4)) . '.php';

        if (is_file($path)) {
            require_once $path;
        }
    });
}