<?php

declare(strict_types=1);

/**
 * @deprecated Use includes/guard.php instead.
 */
require_once __DIR__ . '/guard.php';

function getCurrentUser(PDO $pdo): ?array
{
    return currentUser($pdo);
}

function isPartnershipDirector(?array $user): bool
{
    return $user !== null && $user['role'] === ROLE_PARTNERSHIP_DIRECTOR;
}
