<?php

declare(strict_types=1);

/**
 * Legacy executive dashboard entry — redirects to the registry home for executive roles.
 */

require_once __DIR__ . '/includes/guard.php';

$user = requireRole($pdo, [ROLE_PRESIDENT, ROLE_EXECUTIVE_OFFICER]);

redirect(routePath('dashboard/registry'));
