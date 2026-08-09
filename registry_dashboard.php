<?php

declare(strict_types=1);

/**
 * Shared executive registry dashboard — Partnership Director, President, Executive Officer.
 */

require_once __DIR__ . '/includes/guard.php';

$user = requireRole($pdo, [
    ROLE_PARTNERSHIP_DIRECTOR,
    ROLE_PRESIDENT,
    ROLE_EXECUTIVE_OFFICER,
]);

$filterStatus = $_GET['status'] ?? 'ALL';
$filterCampus = isset($_GET['campus_id']) ? (int) $_GET['campus_id'] : 0;
$highlightAgreementId = isset($_GET['agreement_id']) ? (int) $_GET['agreement_id'] : 0;

$counts = fetchAgreementCounts($pdo);
$campuses = fetchCampuses($pdo);
$agreements = fetchFilteredAgreements(
    $pdo,
    $filterStatus !== 'ALL' ? $filterStatus : null,
    null,
    $filterCampus > 0 ? $filterCampus : null
);

renderInstitutionalDashboardHeader($user, 'Partnership Registry Dashboard', [
    'notifications'        => [],
    'notificationCount'    => 0,
    'pageSubtitle'         => 'Automated agreement status overview and registry listings.',
    'extraStylesheets'     => [assetUrl('css/campus-admin-dashboard.css')],
]);

renderDashboardLogoutAction(registryHeaderBackLink($user));
?>

<div class="app-page-heading mb-4">
    <h1 class="h3 mb-1">Partnership Registry Dashboard</h1>
    <p class="text-secondary mb-0">Shared executive view — filter agreements by status and campus.</p>
</div>

<?php renderMetricCards($counts); ?>

<?php require __DIR__ . '/includes/views/partnership-registry-listing.php'; ?>

<?php renderDirectorDashboardFooter(); ?>
