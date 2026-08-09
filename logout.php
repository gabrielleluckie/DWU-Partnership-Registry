<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/guard.php';

logoutUser();
setFlash('success', 'You have been signed out successfully.');
redirect(loginRoute());
