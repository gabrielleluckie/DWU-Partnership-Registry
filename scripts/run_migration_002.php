<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/database.php';

$columns = $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN);
$lastNameCol = in_array('Last_name', $columns, true) ? 'Last_name' : 'Last_Name';

if (!in_array('password', $columns, true)) {
    $pdo->exec('ALTER TABLE users ADD COLUMN password VARCHAR(255) NULL AFTER Email');
    echo "Added users.password column.\n";
} else {
    echo "users.password column already exists.\n";
}

$pdo->exec("UPDATE users SET password = CONCAT(LOWER(`{$lastNameCol}`), User_ID)");
echo "Set passwords to lowercase last name + User_ID.\n\n";

$rows = $pdo->query(
    "SELECT User_ID, Email, `{$lastNameCol}` AS Last_name, Role, password
     FROM users
     ORDER BY User_ID"
)->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $row) {
    echo sprintf(
        "%d | %s | %s | %s\n",
        (int) $row['User_ID'],
        $row['Email'],
        $row['Role'],
        $row['password']
    );
}
