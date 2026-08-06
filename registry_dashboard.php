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

$directorBackLink = registryHeaderBackLink($user);
$logoutUrl = logoutRoute();
?>

<div class="d-flex justify-content-end align-items-center gap-2 mb-3 app-registry-action-bar">
    <?php if (is_array($directorBackLink) && !empty($directorBackLink['href'])): ?>
        <a href="<?= e($directorBackLink['href']) ?>"
           class="btn btn-outline-success btn-sm rounded-circle app-registry-icon-btn"
           title="Back to Director Dashboard"
           aria-label="Back to Director Dashboard">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
        </a>
    <?php endif; ?>
    <a href="<?= e($logoutUrl) ?>"
       class="btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-2 px-3 app-registry-logout-btn">
        <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
        <span>Logout</span>
    </a>
</div>

<div class="app-page-heading mb-4">
    <h1 class="h3 mb-1">Partnership Registry Dashboard</h1>
    <p class="text-secondary mb-0">Shared executive view — filter agreements by status and campus.</p>
</div>

<?php renderMetricCards($counts); ?>

<?php require __DIR__ . '/includes/views/partnership-registry-listing.php'; ?>

<?php renderDirectorDashboardFooter(); ?>
