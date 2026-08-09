<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/database.php';

$table = 'agreement';
$columns = $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('Expiry_Alert_Sent_At', $columns, true)) {
    $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN Expiry_Alert_Sent_At DATETIME NULL DEFAULT NULL AFTER Expiry_Date");
    echo "Added Expiry_Alert_Sent_At column.\n";
} else {
    echo "Expiry_Alert_Sent_At column already exists.\n";
}

$statusColumn = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE 'Status'")->fetch(PDO::FETCH_ASSOC);

if ($statusColumn && !str_contains((string) $statusColumn['Type'], 'Expiring Soon')) {
    $pdo->exec(
        "ALTER TABLE `{$table}` MODIFY COLUMN Status ENUM(
            'Draft','Submitted','Under Review','Revision Required','Approved','Rejected',
            'Active','Expiring Soon','Expired'
        ) NOT NULL DEFAULT 'Active'"
    );
    echo "Extended Status enum with 'Expiring Soon'.\n";
} else {
    echo "Status enum already supports Expiring Soon.\n";
}

echo "Migration complete.\n";
