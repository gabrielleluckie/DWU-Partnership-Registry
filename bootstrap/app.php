<?php

declare(strict_types=1);

/**
 * CLI bootstrap (mirrors Laravel bootstrap/app.php).
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/guard.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $path = __DIR__ . '/../app/' . $relative . '.php';

    if (is_file($path)) {
        require_once $path;
    }
});

return $pdo;
