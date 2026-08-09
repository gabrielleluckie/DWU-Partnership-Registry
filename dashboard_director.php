<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/guard.php';

$user = requireRole($pdo, [ROLE_PARTNERSHIP_DIRECTOR]);

initializeMockProposals();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'approve_proposal') {
        $proposalId = (int) ($_POST['proposal_id'] ?? 0);

        if (updateProposalStatus($proposalId, 'approved')) {
            setFlash('success', 'Proposal #' . $proposalId . ' has been approved.');
        } else {
            setFlash('error', 'Unable to approve the selected proposal.');
        }

        redirect(routePath('dashboard/director'));
    }

    if ($action === 'reject_proposal') {
        $proposalId = (int) ($_POST['proposal_id'] ?? 0);
        $comment = trim($_POST['rejection_comment'] ?? '');

        if ($comment === '') {
            setFlash('error', 'A rejection comment is required.');
            redirect(routePath('dashboard/director'));
        }

        if (updateProposalStatus($proposalId, 'rejected', $comment)) {
            setFlash('success', 'Proposal #' . $proposalId . ' has been rejected with feedback.');
        } else {
            setFlash('error', 'Unable to reject the selected proposal.');
        }

        redirect(routePath('dashboard/director'));
    }

    if ($action === 'register_agreement') {
        try {
            $documentPath = storeUploadedAgreementPdf($_FILES['agreement_pdf'] ?? []);

            $agreementId = registerActivePartnership($pdo, [
                'partner_mode'        => (string) ($_POST['partner_mode'] ?? 'existing'),
                'partner_id'          => (int) ($_POST['partner_id'] ?? 0),
                'partner_name'        => trim((string) ($_POST['partner_name'] ?? '')),
                'partner_country'     => trim((string) ($_POST['partner_country'] ?? '')),
                'partner_address'     => trim((string) ($_POST['partner_address'] ?? '')),
                'partner_website'     => trim((string) ($_POST['partner_website'] ?? '')),
                'campus_id'           => (int) ($_POST['campus_id'] ?? 0),
                'contact_name'        => trim((string) ($_POST['contact_name'] ?? '')),
                'contact_designation' => trim((string) ($_POST['contact_designation'] ?? '')),
                'contact_email'       => trim((string) ($_POST['contact_email'] ?? '')),
                'contact_phone'       => trim((string) ($_POST['contact_phone'] ?? '')),
                'contact_fax'         => trim((string) ($_POST['contact_fax'] ?? '')),
                'partnership_type'    => trim((string) ($_POST['partnership_type'] ?? '')),
                'agreement_type'      => trim((string) ($_POST['agreement_type'] ?? '')),
                'signed_date'         => trim((string) ($_POST['signed_date'] ?? '')),
                'expiry_date'         => trim((string) ($_POST['expiry_date'] ?? '')),
                'scope_description'   => trim((string) ($_POST['scope_description'] ?? '')),
                'document_path'       => $documentPath,
            ], (int) $user['id'], $user['name']);

            setFlash('success', 'Agreement #' . $agreementId . ' registered successfully in the live registry.');
        } catch (Throwable $exception) {
            setFlash('error', $exception->getMessage());
        }

        redirect(routePath('dashboard/director'));
    }
}

$pendingProposals = getProposalsByStatus('pending');
$pendingCount = count($pendingProposals);
$counts = fetchAgreementCounts($pdo);
$partners = fetchPartners($pdo);
$campuses = fetchCampuses($pdo);

renderDirectorDashboardHeader(
    $user,
    'Partnership Director Dashboard',
    $pendingProposals,
    $pendingCount
);

renderDashboardLogoutAction();
?>

<?php if ($message = flashMessage('success')): ?>
    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"><?= e($message) ?></div>
<?php endif; ?>

<?php if ($message = flashMessage('error')): ?>
    <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800"><?= e($message) ?></div>
<?php endif; ?>

<!-- Dynamic proposal notification (pending queue only) -->
<?php if ($pendingCount > 0): ?>
    <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 shadow-sm" role="status">
        <div class="flex flex-wrap items-start gap-3">
            <span class="inline-flex shrink-0 items-center rounded-full bg-amber-200 px-2.5 py-0.5 text-xs font-bold text-amber-900">
                <?= (int) $pendingCount ?> pending
            </span>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-amber-950">
                    <?= (int) $pendingCount === 1 ? '1 proposal awaits your review' : (int) $pendingCount . ' proposals await your review' ?>
                </p>
                <p class="mt-0.5 text-xs text-amber-800">
                    Latest: <?= e($pendingProposals[0]['partner_name']) ?>
                    · <?= e($pendingProposals[0]['campus']) ?>
                    · submitted by <?= e($pendingProposals[0]['submitted_by']) ?>
                </p>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="mb-6 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600" role="status">
        <i class="bi bi-inbox me-1 text-slate-400" aria-hidden="true"></i>
        No new pending proposal submissions from satellite campuses.
    </div>
<?php endif; ?>

<section class="mb-8">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <h2 class="h5 mb-0 text-slate-900">Executive Analytics Overview</h2>
        <a href="<?= e(routePath('dashboard/registry')) ?>" class="btn btn-sm btn-outline-success">
            <i class="bi bi-table me-1"></i> Open Registry Dashboard
        </a>
    </div>
    <?php renderMetricCards($counts); ?>
</section>

<div class="grid gap-8 xl:grid-cols-2">
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Pending Approvals &amp; Rejections</h2>
                    <p class="text-sm text-slate-500">Campus Admin submissions queued for director review.</p>
                </div>
                <?php if ($pendingCount === 0): ?>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">Queue empty</span>
                <?php else: ?>
                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800"><?= (int) $pendingCount ?> in queue</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="divide-y divide-slate-100">
            <?php if ($pendingProposals === []): ?>
                <div class="px-6 py-10 text-center">
                    <p class="text-sm text-slate-500">No new pending proposal submissions from satellite campuses.</p>
                    <p class="mt-2 text-xs text-slate-400">
                        When a Campus Admin submits a proposal, it will appear here for approval or revision feedback.
                    </p>
                </div>
            <?php else: ?>
                <?php foreach ($pendingProposals as $proposal): ?>
                    <article class="px-6 py-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h3 class="font-semibold text-slate-900"><?= e($proposal['partner_name']) ?></h3>
                                <p class="mt-1 text-sm text-slate-500">
                                    <?= e($proposal['agreement_type']) ?> · <?= e($proposal['campus']) ?>
                                </p>
                                <?php if (($proposal['partnership_type'] ?? '') !== ''): ?>
                                    <p class="mt-1 text-xs text-slate-500"><?= e($proposal['partnership_type']) ?></p>
                                <?php endif; ?>
                                <p class="mt-1 text-xs text-slate-400">
                                    Submitted <?= e($proposal['submitted_at']) ?> by <?= e($proposal['submitted_by']) ?>
                                </p>
                            </div>
                            <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">Pending Review</span>
                        </div>

                        <p class="mt-3 text-xs text-slate-500">
                            Approve or reject with feedback. This updates the review queue only — it does not register the partnership in the live database.
                        </p>

                        <div class="mt-4 flex flex-wrap gap-3">
                            <form method="post" action="">
                                <input type="hidden" name="action" value="approve_proposal">
                                <input type="hidden" name="proposal_id" value="<?= (int) $proposal['id'] ?>">
                                <button type="submit"
                                        class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                    Approve for Registry Entry
                                </button>
                            </form>

                            <form method="post" action="" class="flex flex-1 flex-wrap items-end gap-2">
                                <input type="hidden" name="action" value="reject_proposal">
                                <input type="hidden" name="proposal_id" value="<?= (int) $proposal['id'] ?>">
                                <div class="min-w-[220px] flex-1">
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Revision feedback (required to reject)
                                    </label>
                                    <input type="text"
                                           name="rejection_comment"
                                           required
                                           placeholder="Explain what the campus must revise..."
                                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500/20">
                                </div>
                                <button type="submit"
                                        class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-700">
                                    Return for Revision
                                </button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4">
            <h2 class="text-lg font-semibold text-slate-900">Active Partnership Entry Form</h2>
            <p class="text-sm text-slate-500">
                The only path to create official records in the live registry (<code class="text-xs">partner</code>, <code class="text-xs">agreement</code>, <code class="text-xs">contact</code> tables).
            </p>
        </div>

        <?php require __DIR__ . '/includes/views/director-partnership-entry-form.php'; ?>
    </section>
</div>

<?php renderDirectorDashboardFooter(); ?>
