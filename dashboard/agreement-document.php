<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/guard.php';

$user = requireRole($pdo, [
    ROLE_CAMPUS_ADMIN,
    ROLE_PARTNERSHIP_DIRECTOR,
    ROLE_PRESIDENT,
    ROLE_EXECUTIVE_OFFICER,
]);

$agreementId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$forceDownload = isset($_GET['download']) && (string) $_GET['download'] !== '0';

$agreement = fetchRegistryAgreementById($pdo, $agreementId);

if ($agreement === null || !userCanAccessRegistryAgreement($user, $agreement)) {
    http_response_code(404);
    exit('Agreement document not found.');
}

$relativePath = (string) ($agreement['document_path'] ?? '');
$absolutePath = agreementDocumentAbsolutePath($relativePath);

if ($absolutePath === null) {
    http_response_code(404);
    exit('No document is attached to this agreement.');
}

$downloadName = agreementDownloadFilename($relativePath);
$safeName = str_replace(['"', "\r", "\n"], '', $downloadName);
$disposition = $forceDownload ? 'attachment' : 'inline';

header('Content-Type: ' . agreementDocumentMimeType($absolutePath));
header('Content-Length: ' . (string) filesize($absolutePath));
header('Content-Disposition: ' . $disposition . '; filename="' . $safeName . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');

readfile($absolutePath);
exit;
