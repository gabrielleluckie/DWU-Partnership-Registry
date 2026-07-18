<?php

declare(strict_types=1);

/**
 * PDMIS — Secure PDO database connection.
 * Include once; exposes $pdo for parameterized queries.
 */

$dbHost = getenv('PDMIS_DB_HOST') ?: 'localhost';
$dbName = getenv('PDMIS_DB_NAME') ?: 'PartnershipRegistry';
$dbUser = getenv('PDMIS_DB_USER') ?: 'root';
$dbPass = getenv('PDMIS_DB_PASS') ?: '';
$dbCharset = 'utf8mb4';

$dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $dbHost, $dbName, $dbCharset);

$pdoOptions = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $pdoOptions);
} catch (PDOException $exception) {
    error_log('PDMIS database connection failed: ' . $exception->getMessage());
    http_response_code(500);
    exit('Database connection failed. Please contact the system administrator.');
}
