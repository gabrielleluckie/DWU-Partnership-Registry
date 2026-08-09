<?php

declare(strict_types=1);

/**
 * Application environment helpers.
 *
 * Set APP_ENV=local in the server environment for explicit local development mode.
 * When unset, localhost / 127.0.0.1 is treated as local.
 */
function appEnv(): string
{
    static $env = null;

    if ($env !== null) {
        return $env;
    }

    $configured = getenv('APP_ENV');

    if (is_string($configured) && $configured !== '') {
        $env = strtolower(trim($configured));

        return $env;
    }

    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));

    if (
        $host === ''
        || str_contains($host, 'localhost')
        || str_contains($host, '127.0.0.1')
        || str_starts_with($host, '[::1]')
    ) {
        $env = 'local';

        return $env;
    }

    $env = 'production';

    return $env;
}

function isLocalEnvironment(): bool
{
    return appEnv() === 'local';
}
