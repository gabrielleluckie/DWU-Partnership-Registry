<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/database.php';

$sql = file_get_contents(__DIR__ . '/seed_dev_users.sql');

if ($sql === false) {
    fwrite(STDERR, "Could not read seed_dev_users.sql\n");
    exit(1);
}

$pdo->exec($sql);

echo "Dev users seeded.\n";

$rows = $pdo->query('SELECT User_ID, Email, Role, Campus_ID FROM users ORDER BY User_ID')->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);
