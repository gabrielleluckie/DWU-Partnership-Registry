<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/guard.php';

$loggedInUser = requireAuth($pdo);

if ($loggedInUser['role'] === ROLE_CAMPUS_ADMIN) {
    redirect('dashboard_campus_admin.php?tab=submit');
}

$dashboard = dashboardForRole($loggedInUser['role']);

if ($dashboard !== null) {
    redirect($dashboard);
}

redirectWithError('No intake dashboard is available for your role.');
