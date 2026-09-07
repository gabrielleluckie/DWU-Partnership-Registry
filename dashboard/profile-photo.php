<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/guard.php';

$user = requireAuth($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(dashboardForRole((string) $user['role']) ?? loginRoute());
}

$redirectTo = safeProfilePhotoRedirect($user);

try {
    storeUserProfilePhoto((int) $user['id'], $_FILES['profile_photo'] ?? []);
} catch (InvalidArgumentException $exception) {
    setFlash('error', $exception->getMessage());
} catch (Throwable $exception) {
    setFlash('error', 'Could not update your profile photo. Please try again.');
}

redirect($redirectTo);
