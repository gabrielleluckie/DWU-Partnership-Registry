<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/guard.php';

if (isLoggedIn()) {
    redirect('router.php');
}

redirect(loginRoute());

