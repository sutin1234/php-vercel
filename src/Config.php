<?php

declare(strict_types=1);

namespace App;

/**
 * Runtime configuration read from environment variables.
 */
final class Config
{
    public static function get(string $key, ?string $default = null): ?string
    {
        $value = getenv($key);

        if ($value === false || $value === '') {
            return $default;
        }

        return $value;
    }

    public static function appName(): string
    {
        return self::get('APP_NAME', 'php-vercel') ?? 'php-vercel';
    }

    public static function isVercel(): bool
    {
        return self::get('VERCEL') !== null || self::get('VERCEL_URL') !== null;
    }

    public static function runtimeName(): string
    {
        return self::isVercel() ? 'frankenphp-container' : 'php-dev-server';
    }
}