<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/guard.php';

$user = requireRole($pdo, [ROLE_CAMPUS_ADMIN]);

$activeTab = $_GET['tab'] ?? 'submit';
if (!in_array($activeTab, ['submit', 'review'], true)) {
    $activeTab = 'submit';
}

initializeMockProposals();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['form_action'] ?? '';

    if ($action === 'save_draft') {
        saveCampusProposalDraft($user, $_POST);
        setFlash('success', 'Draft saved successfully. You can continue editing at any time.');
        redirect('dashboard_campus_admin.php?tab=submit');
    }

    if ($action === 'submit_proposal') {
        $partnerName = trim((string) ($_POST['partner_legal_name'] ?? ''));
        $submitterName = trim((string) ($_POST['submitter_name'] ?? $user['name']));

        if ($partnerName === '' || $submitterName === '') {
            setFlash('error', 'Partner legal name and submitter name are required before submission.');
            redirect('dashboard_campus_admin.php?tab=submit');
        }

        if (empty($_POST['declaration_confirm'])) {
            setFlash('error', 'You must confirm the declaration before submitting to the Director.');
            redirect('dashboard_campus_admin.php?tab=submit');
        }

        $proposalId = createCampusProposal($_POST, $user, 'pending');
        setFlash('success', 'Proposal #' . $proposalId . ' has been submitted to the Partnership Director for review.');
        redirect('dashboard_campus_admin.php?tab=review');
    }
}

$draft = getCampusProposalDraft($user);
$approvedProposals = getProposalsByStatus('approved');
$rejectedProposals = getProposalsByStatus('rejected');

renderCampusAdminDashboardHeader(
    $user,
    'Campus Admin Dashboard',
    $approvedProposals,
    $rejectedProposals
);
?>

<?php if ($message = flashMessage('success')): ?>
    <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        <?= e($message) ?>
    </div>
<?php endif; ?>

<?php if ($message = flashMessage('error')): ?>
    <div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
        <?= e($message) ?>
    </div>
<?php endif; ?>

<div class="mb-6 border-b border-slate-200">
    <nav class="-mb-px flex gap-6" aria-label="Campus admin tabs">
        <a href="?tab=submit"
           class="<?= $activeTab === 'submit'
               ? 'border-dwu-green text-dwu-green'
               : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' ?> whitespace-nowrap border-b-2 px-1 py-4 text-sm font-semibold">
            Submit Proposal Form
        </a>
        <a href="?tab=review"
           class="<?= $activeTab === 'review'
               ? 'border-dwu-green text-dwu-green'
               : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' ?> whitespace-nowrap border-b-2 px-1 py-4 text-sm font-semibold">
            Review Proposal Forms
        </a>
    </nav>
</div>

<?php if ($activeTab === 'submit'): ?>
    <?php require __DIR__ . '/includes/campus-intake-form.php'; ?>
<?php else: ?>
    <div class="grid gap-8 lg:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-emerald-800">Approved Proposals</h2>
                <p class="text-sm text-slate-500">Proposals cleared by the Partnership Director.</p>
            </div>
            <div class="divide-y divide-slate-100">
                <?php if ($approvedProposals === []): ?>
                    <p class="px-6 py-8 text-sm italic text-slate-500">No approved proposals yet.</p>
                <?php else: ?>
                    <?php foreach ($approvedProposals as $proposal): ?>
                        <article class="px-6 py-5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="font-semibold text-slate-900"><?= e($proposal['partner_name']) ?></h3>
                                    <p class="mt-1 text-sm text-slate-500"><?= e($proposal['agreement_type']) ?> · <?= e($proposal['campus']) ?></p>
                                </div>
                                <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">Approved</span>
                            </div>
                            <p class="mt-3 text-xs text-slate-400">Submitted <?= e($proposal['submitted_at']) ?> by <?= e($proposal['submitted_by']) ?></p>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-rose-800">Rejected Proposals</h2>
                <p class="text-sm text-slate-500">Returned submissions requiring revision.</p>
            </div>
            <div class="divide-y divide-slate-100">
                <?php if ($rejectedProposals === []): ?>
                    <p class="px-6 py-8 text-sm italic text-slate-500">No rejected proposals.</p>
                <?php else: ?>
                    <?php foreach ($rejectedProposals as $proposal): ?>
                        <article class="px-6 py-5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="font-semibold text-slate-900"><?= e($proposal['partner_name']) ?></h3>
                                    <p class="mt-1 text-sm text-slate-500"><?= e($proposal['agreement_type']) ?> · <?= e($proposal['campus']) ?></p>
                                </div>
                                <span class="rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-800">Rejected</span>
                            </div>
                            <?php if ($proposal['rejection_comment'] !== ''): ?>
                                <p class="mt-3 rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700">
                                    <?= e($proposal['rejection_comment']) ?>
                                </p>
                            <?php endif; ?>
                            <button type="button"
                                    onclick="alert('Edit & Resubmit workflow will open the proposal form for proposal #<?= (int) $proposal['id'] ?>.')"
                                    class="mt-4 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                Edit &amp; Resubmit
                            </button>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>
<?php endif; ?>

<?php if ($activeTab === 'submit'): ?>
    <script src="<?= e(assetUrl('js/campus-intake-form.js')) ?>"></script>
<?php endif; ?>

<?php renderDirectorDashboardFooter(); ?>
