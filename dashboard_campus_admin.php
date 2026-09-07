<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/guard.php';

use App\Models\Agreement;

$user = requireRole($pdo, [ROLE_CAMPUS_ADMIN]);

$activeTab = $_GET['tab'] ?? 'submit';
if (!in_array($activeTab, ['submit', 'review', 'agreements'], true)) {
    $activeTab = 'submit';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['form_action'] ?? '';
    $returnTab = (string) ($_POST['return_tab'] ?? $activeTab);
    if (!in_array($returnTab, ['submit', 'review', 'agreements'], true)) {
        $returnTab = 'submit';
    }

    if ($action === 'save_draft') {
        try {
            lockCampusAdminFormCampus($_POST, $user);
            $existingDraftId = isset($_POST['draft_id']) ? (int) $_POST['draft_id'] : 0;
            saveCampusProposalDraft($user, $_POST, $existingDraftId > 0 ? $existingDraftId : null);
            setFlash('success', 'Draft saved.');
            redirect(routePath('dashboard/campus-admin') . '?tab=submit');
        } catch (Throwable $exception) {
            setFlash('error', $exception->getMessage());
            redirect(routePath('dashboard/campus-admin') . '?tab=submit');
        }
    }

    if ($action === 'delete_draft') {
        $draftId = (int) ($_POST['draft_id'] ?? 0);
        if ($draftId > 0 && deleteCampusProposalDraft($user, $draftId)) {
            setFlash('success', 'Draft deleted.');
        } else {
            setFlash('error', 'That draft could not be deleted.');
        }
        $query = '?tab=' . rawurlencode($returnTab);
        if ($returnTab === 'agreements' && isset($_GET['status'])) {
            $query .= '&status=' . rawurlencode((string) $_GET['status']);
        }
        redirect(routePath('dashboard/campus-admin') . $query);
    }

    if ($action === 'submit_proposal') {
        $partnerName = trim((string) ($_POST['partner_name'] ?? $_POST['partner_legal_name'] ?? ''));
        $submitterName = trim((string) ($_POST['staff_name'] ?? $_POST['submitter_name'] ?? $user['name']));

        if ($partnerName === '' || $submitterName === '') {
            setFlash('error', 'Partner legal name and staff name are required before submission.');
            redirect(routePath('dashboard/campus-admin') . '?tab=submit');
        }

        if (empty($_POST['staff_declaration_agree']) && empty($_POST['declaration_confirm'])) {
            setFlash('error', 'You must confirm the staff declaration before submitting to the Director.');
            redirect(routePath('dashboard/campus-admin') . '?tab=submit');
        }

        try {
            lockCampusAdminFormCampus($_POST, $user);
            $proposalId = createCampusProposal($_POST, $user, 'pending');
            $submittedDraftId = isset($_POST['draft_id']) ? (int) $_POST['draft_id'] : 0;
            if ($submittedDraftId > 0) {
                deleteCampusProposalDraft($user, $submittedDraftId);
            }
            setFlash('success', 'Proposal #' . $proposalId . ' has been submitted to the Partnership Director for review.');
            redirect(routePath('dashboard/campus-admin') . '?tab=review');
        } catch (Throwable $exception) {
            setFlash('error', $exception->getMessage());
            redirect(routePath('dashboard/campus-admin') . '?tab=submit');
        }
    }
}

$editingDraftId = isset($_GET['draft']) ? (int) $_GET['draft'] : 0;
$draft = [];
if ($editingDraftId > 0) {
    $draftRow = fetchCampusProposalDraftById($pdo, (int) ($user['id'] ?? 0), $editingDraftId);
    if ($draftRow === null) {
        setFlash('error', 'That draft was not found.');
        redirect(routePath('dashboard/campus-admin') . '?tab=submit');
    }
    $draft = $draftRow['form'] ?? [];
}
$drafts = listCampusProposalDrafts($user);
$approvedProposals = getProposalsByStatus('approved', $user);
$rejectedProposals = getProposalsByStatus('rejected', $user);

$allowedAgreementStatuses = [
    Agreement::STATUS_ACTIVE,
    Agreement::STATUS_EXPIRING_SOON,
    Agreement::STATUS_EXPIRED,
    'ALL',
];
$filterStatus = (string) ($_GET['status'] ?? 'ALL');
if (!in_array($filterStatus, $allowedAgreementStatuses, true)) {
    $filterStatus = Agreement::STATUS_ACTIVE;
}

$viewAgreement = null;
$registryAgreements = [];
$campusId = (int) ($user['campus_id'] ?? 0);

if ($activeTab === 'agreements') {
    $viewId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($viewId > 0) {
        $candidate = fetchRegistryAgreementById($pdo, $viewId);

        if ($candidate === null || !userCanAccessRegistryAgreement($user, $candidate)) {
            setFlash('error', 'That agreement was not found for your campus.');
            redirect(routePath('dashboard/campus-admin') . '?tab=agreements&status=' . rawurlencode($filterStatus));
        }

        $viewAgreement = $candidate;
    } elseif ($campusId > 0) {
        $registryAgreements = fetchFilteredAgreements(
            $pdo,
            $filterStatus !== 'ALL' ? $filterStatus : null,
            null,
            $campusId
        );
    }
}

renderCampusAdminDashboardHeader(
    $user,
    'Campus Admin Dashboard',
    $approvedProposals,
    $rejectedProposals
);
?>

<?php require __DIR__ . '/includes/views/campus-admin-hero-slideshow.php'; ?>

<div class="app-subnav app-subnav-bleed">
    <div class="container-fluid px-3 px-lg-4">
        <ul class="nav nav-tabs campus-admin-tabs mb-0" role="tablist">
            <li class="nav-item" role="presentation">
                <a href="?tab=submit"
                   class="nav-link fw-semibold <?= $activeTab === 'submit' ? 'active' : '' ?>">
                    <i class="bi bi-file-earmark-plus me-1"></i> Submit Proposal Form
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a href="?tab=review"
                   class="nav-link fw-semibold <?= $activeTab === 'review' ? 'active' : '' ?>">
                    <i class="bi bi-list-check me-1"></i> Review Proposal Forms
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a href="?tab=agreements"
                   class="nav-link fw-semibold <?= $activeTab === 'agreements' ? 'active' : '' ?>">
                    <i class="bi bi-folder2-open me-1"></i> Active Agreements
                </a>
            </li>
        </ul>
    </div>
</div>

<?php if ($activeTab === 'review'): ?>
<div class="app-page-heading mb-3 d-none d-md-block">
    <h1 class="h5 mb-1">Campus Admin Dashboard</h1>
    <p class="text-secondary small mb-0">Submit and review proposed partnership agreements.</p>
</div>
<?php endif; ?>

<?php if ($activeTab === 'submit'): ?>
    <?php
    $draftsReturnTab = 'submit';
    require __DIR__ . '/includes/views/campus-admin-drafts.php';
    ?>
    <?php require __DIR__ . '/includes/campus-intake-form.php'; ?>
    <script src="<?= e(assetUrl('js/campus-intake-form.js')) ?>"></script>
<?php elseif ($activeTab === 'agreements'): ?>
    <?php
    $draftsReturnTab = 'agreements';
    require __DIR__ . '/includes/views/campus-admin-drafts.php';
    $agreements = $registryAgreements;
    require __DIR__ . '/includes/views/campus-admin-agreements.php';
    ?>
<?php else: ?>
    <?php
    $draftsReturnTab = 'review';
    require __DIR__ . '/includes/views/campus-admin-drafts.php';
    ?>
    <div class="review-grid">
        <section class="review-panel">
            <div class="review-panel-header">
                <h2>Approved Proposals</h2>
                <p>Proposals cleared by the Partnership Director.</p>
            </div>
            <div class="review-panel-body">
                <?php if ($approvedProposals === []): ?>
                    <p class="review-empty">No approved proposals yet.</p>
                <?php else: ?>
                    <?php foreach ($approvedProposals as $proposal): ?>
                        <article class="review-item">
                            <div class="review-item-top">
                                <div>
                                    <h3><?= e($proposal['partner_name']) ?></h3>
                                    <p class="review-meta"><?= e($proposal['agreement_type']) ?> · <?= e($proposal['campus']) ?></p>
                                </div>
                                <span class="badge-approved">Approved</span>
                            </div>
                            <p class="review-date">Submitted <?= e($proposal['submitted_at']) ?> by <?= e($proposal['submitted_by']) ?></p>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="review-panel">
            <div class="review-panel-header">
                <h2>Rejected Proposals</h2>
                <p>Returned submissions requiring revision.</p>
            </div>
            <div class="review-panel-body">
                <?php if ($rejectedProposals === []): ?>
                    <p class="review-empty">No rejected proposals.</p>
                <?php else: ?>
                    <?php foreach ($rejectedProposals as $proposal): ?>
                        <article class="review-item">
                            <div class="review-item-top">
                                <div>
                                    <h3><?= e($proposal['partner_name']) ?></h3>
                                    <p class="review-meta"><?= e($proposal['agreement_type']) ?> · <?= e($proposal['campus']) ?></p>
                                </div>
                                <span class="badge-rejected">Rejected</span>
                            </div>
                            <?php if ($proposal['rejection_comment'] !== ''): ?>
                                <p class="review-rejection-note"><?= e($proposal['rejection_comment']) ?></p>
                            <?php endif; ?>
                            <button type="button"
                                    onclick="alert('Edit & Resubmit workflow will open the proposal form for proposal #<?= (int) $proposal['id'] ?>.')"
                                    class="btn-review-action">
                                Edit &amp; Resubmit
                            </button>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>
<?php endif; ?>

<?php
$slideshowJs = __DIR__ . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'campus-admin-slideshow.js';
$slideshowJsVersion = is_file($slideshowJs) ? (string) filemtime($slideshowJs) : (string) time();
?>
<script src="<?= e(assetUrl('js/campus-admin-slideshow.js') . '?v=' . $slideshowJsVersion) ?>"></script>
<?php renderDirectorDashboardFooter(); ?>
