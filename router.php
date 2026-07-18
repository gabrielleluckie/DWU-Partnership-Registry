<?php

declare(strict_types=1);

/**
 * Role-based routing handshake.
 * Validates the active session and redirects to the correct dashboard.
 */

require_once __DIR__ . '/includes/guard.php';

$user = requireAuth($pdo);

$requestedRole = $_GET['role'] ?? null;

if (is_string($requestedRole) && $requestedRole !== '') {
    $allowedDashboard = dashboardForRole($requestedRole);

    if ($allowedDashboard === null) {
        redirectWithError('Unknown role route requested.');
    }

    if ($user['role'] !== $requestedRole) {
        redirectWithError('You do not have permission to access that dashboard.');
    }

    redirect($allowedDashboard);
}

redirectToRoleDashboard($user['role']);
