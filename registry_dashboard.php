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

$viewAgreement = null;
$agreementHistory = [];

if ($highlightAgreementId > 0) {
    $candidate = fetchRegistryAgreementById($pdo, $highlightAgreementId);

    if ($candidate === null || !userCanAccessRegistryAgreement($user, $candidate)) {
        setFlash('error', 'That agreement was not found in the registry.');
        redirect(registryDashboardUrl([
            'status'    => $filterStatus,
            'campus_id' => $filterCampus,
        ]));
    }

    $viewAgreement = $candidate;
    $agreementHistory = fetchAgreementHistoryForId($pdo, $highlightAgreementId);
}

$counts = fetchAgreementCounts($pdo);
$campuses = fetchCampuses($pdo);
$agreements = $viewAgreement === null ? fetchFilteredAgreements(
    $pdo,
    $filterStatus !== 'ALL' ? $filterStatus : null,
    null,
    $filterCampus > 0 ? $filterCampus : null
) : [];

renderInstitutionalDashboardHeader($user, 'Partnership Registry Dashboard', [
    'notifications'        => [],
    'notificationCount'    => 0,
    'pageSubtitle'         => 'Automated agreement status overview and registry listings.',
    'bodyClass'            => 'app-shell campus-admin-theme',
    'extraStylesheets'     => [
        assetUrl('css/campus-admin-dashboard.css') . '?v=' . (string) (
            is_file(__DIR__ . '/css/campus-admin-dashboard.css')
                ? filemtime(__DIR__ . '/css/campus-admin-dashboard.css')
                : time()
        ),
    ],
]);

renderDashboardLogoutAction(registryHeaderBackLink($user));
?>

<?php renderDirectorFlashMessages(); ?>

<?php if ($viewAgreement === null): ?>
<div class="app-page-heading mb-4">
    <h1 class="h3 mb-1">Partnership Registry Dashboard</h1>
    <p class="text-secondary mb-0">Shared executive view — open an agreement to see its full details.</p>
</div>
<?php endif; ?>

<?php if ($viewAgreement !== null): ?>
    <?php require __DIR__ . '/includes/views/partnership-registry-detail.php'; ?>
<?php else: ?>
    <?php renderMetricCards($counts); ?>
    <?php require __DIR__ . '/includes/views/partnership-registry-listing.php'; ?>
<?php endif; ?>

<?php renderDirectorDashboardFooter(); ?>
