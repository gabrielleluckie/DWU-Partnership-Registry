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

        redirect('dashboard_director.php');
    }

    if ($action === 'reject_proposal') {
        $proposalId = (int) ($_POST['proposal_id'] ?? 0);
        $comment = trim($_POST['rejection_comment'] ?? '');

        if ($comment === '') {
            setFlash('error', 'A rejection comment is required.');
            redirect('dashboard_director.php');
        }

        if (updateProposalStatus($proposalId, 'rejected', $comment)) {
            setFlash('success', 'Proposal #' . $proposalId . ' has been rejected with feedback.');
        } else {
            setFlash('error', 'Unable to reject the selected proposal.');
        }

        redirect('dashboard_director.php');
    }

    if ($action === 'register_agreement') {
        $partnerId = (int) ($_POST['partner_id'] ?? 0);
        $partnershipType = trim($_POST['partnership_type'] ?? '');
        $agreementType = trim($_POST['agreement_type'] ?? '');
        $signedDate = trim($_POST['signed_date'] ?? '');
        $expiryDate = trim($_POST['expiry_date'] ?? '');

        if ($partnerId <= 0 || $partnershipType === '' || $agreementType === '' || $signedDate === '' || $expiryDate === '') {
            setFlash('error', 'All agreement fields are required.');
            redirect('dashboard_director.php');
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $signedDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiryDate)) {
            setFlash('error', 'Dates must be provided in YYYY-MM-DD format.');
            redirect('dashboard_director.php');
        }

        if (isset($_FILES['agreement_pdf']) && $_FILES['agreement_pdf']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/uploads/agreements';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($_FILES['agreement_pdf']['name']));
            $targetPath = $uploadDir . '/' . time() . '_' . $safeName;
            move_uploaded_file($_FILES['agreement_pdf']['tmp_name'], $targetPath);
        }

        try {
            $agreementId = createAgreementWithHistory(
                $pdo,
                $partnerId,
                $partnershipType,
                $agreementType,
                $signedDate,
                $expiryDate,
                $user['name']
            );

            setFlash('success', 'Agreement #' . $agreementId . ' registered successfully in the live registry.');
        } catch (Throwable $exception) {
            setFlash('error', $exception->getMessage());
        }

        redirect('dashboard_director.php');
    }
}

$pendingProposals = getProposalsByStatus('pending');
$pendingCount = count($pendingProposals);
$counts = fetchAgreementCounts($pdo);
$partners = fetchPartners($pdo);

renderDirectorDashboardHeader(
    $user,
    'Partnership Director Dashboard',
    $pendingProposals,
    $pendingCount
);
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
    <h2 class="mb-4 text-lg font-semibold text-slate-900">Executive Analytics Overview</h2>
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
                            <form method="post" action="dashboard_director.php">
                                <input type="hidden" name="action" value="approve_proposal">
                                <input type="hidden" name="proposal_id" value="<?= (int) $proposal['id'] ?>">
                                <button type="submit"
                                        class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                    Approve for Registry Entry
                                </button>
                            </form>

                            <form method="post" action="dashboard_director.php" class="flex flex-1 flex-wrap items-end gap-2">
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

        <form method="post" action="dashboard_director.php" enctype="multipart/form-data" class="space-y-4 px-6 py-5">
            <input type="hidden" name="action" value="register_agreement">

            <div class="rounded-lg border border-emerald-100 bg-emerald-50 px-3 py-2 text-xs text-emerald-800">
                Submit this form after campus review is complete. Campus Admin proposal forms never write directly to the registry.
            </div>

            <div>
                <label for="partner_id" class="mb-1.5 block text-sm font-medium text-slate-700">Partner organisation</label>
                <select id="partner_id" name="partner_id" required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-dwu-green focus:outline-none focus:ring-2 focus:ring-dwu-green/20">
                    <option value="">Select a registered partner...</option>
                    <?php foreach ($partners as $partner): ?>
                        <option value="<?= (int) $partner['Partner_ID'] ?>">
                            <?= e($partner['Name']) ?> (<?= e($partner['campus_name']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($partners === []): ?>
                    <p class="mt-2 text-xs text-amber-700">No partners exist yet. Partner records are created when you register an active partnership here.</p>
                <?php endif; ?>
            </div>

            <div>
                <label for="partnership_type" class="mb-1.5 block text-sm font-medium text-slate-700">Partnership type</label>
                <input type="text" id="partnership_type" name="partnership_type" required
                       placeholder="e.g. Clinical Training Partnership"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-dwu-green focus:outline-none focus:ring-2 focus:ring-dwu-green/20">
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="agreement_type" class="mb-1.5 block text-sm font-medium text-slate-700">Agreement type</label>
                    <select id="agreement_type" name="agreement_type" required
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-dwu-green focus:outline-none focus:ring-2 focus:ring-dwu-green/20">
                        <option value="">Select type...</option>
                        <option value="MOU">MOU</option>
                        <option value="MOA">MOA</option>
                        <option value="DFAT Contract">DFAT Contract</option>
                    </select>
                </div>
                <div>
                    <label for="agreement_pdf" class="mb-1.5 block text-sm font-medium text-slate-700">Scanned agreement (PDF)</label>
                    <input type="file" id="agreement_pdf" name="agreement_pdf" accept=".pdf,application/pdf"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-dwu-green file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white">
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="signed_date" class="mb-1.5 block text-sm font-medium text-slate-700">Signed date</label>
                    <input type="date" id="signed_date" name="signed_date" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-dwu-green focus:outline-none focus:ring-2 focus:ring-dwu-green/20">
                </div>
                <div>
                    <label for="expiry_date" class="mb-1.5 block text-sm font-medium text-slate-700">Expiry date</label>
                    <input type="date" id="expiry_date" name="expiry_date" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-dwu-green focus:outline-none focus:ring-2 focus:ring-dwu-green/20">
                </div>
            </div>

            <div class="pt-2">
                <button type="submit"
                        class="w-full rounded-lg bg-dwu-green px-4 py-3 text-sm font-semibold text-white transition hover:bg-dwu-dark sm:w-auto">
                    Register Agreement in Registry
                </button>
            </div>
        </form>
    </section>
</div>

<?php renderDirectorDashboardFooter(); ?>
