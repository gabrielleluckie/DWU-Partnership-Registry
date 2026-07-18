<?php
require __DIR__ . '/../database.php';

$updates = [
    'agnes.kula@dwu.ac.pg' => password_hash('director123', PASSWORD_DEFAULT),
    'president@dwu.ac.pg' => password_hash('president123', PASSWORD_DEFAULT),
    'jmete@dwu.ac.pg' => password_hash('executive123', PASSWORD_DEFAULT),
    'asanki@dwu.ac.pg' => password_hash('campus123', PASSWORD_DEFAULT),
    'mtavana@dwu.ac.pg' => password_hash('campus123', PASSWORD_DEFAULT),
    'pkari@dwu.ac.pg' => password_hash('campus123', PASSWORD_DEFAULT),
];

$stmt = $pdo->prepare('UPDATE users SET password = :password WHERE Email = :email');

foreach ($updates as $email => $hash) {
    $stmt->execute(['password' => $hash, 'email' => $email]);
    echo "Updated {$email}\n";
}
