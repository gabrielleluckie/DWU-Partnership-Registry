<?php

declare(strict_types=1);

/**
 * Development quick-login handler (local only).
 * POST email=<dev-account@dwu.ac.pg>
 */

require_once __DIR__ . '/includes/guard.php';
require_once __DIR__ . '/includes/dev-auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(loginRoute());
}

if (!isDevAuthEnabled()) {
    http_response_code(404);
    exit('Not found.');
}

$email = strtolower(trim((string) ($_POST['email'] ?? '')));

if ($email === '' || !attemptDevQuickLogin($pdo, $email)) {
    redirectWithError('Development quick-login failed. Run scripts/seed_dev_users.sql and try again.');
}

$user = currentUser($pdo);

if ($user === null) {
    redirectWithError('Session could not be established after dev login.');
}

redirectToRoleDashboard($user['role']);
