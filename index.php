<?php

declare(strict_types=1);

/** Legacy entry — redirect to canonical login route. */
require_once __DIR__ . '/includes/guard.php';

$query = $_SERVER['QUERY_STRING'] ?? '';
$target = loginRoute() . ($query !== '' ? '?' . $query : '');

redirect($target);
