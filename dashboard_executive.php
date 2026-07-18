<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/guard.php';

$user = requireRole($pdo, [ROLE_PRESIDENT, ROLE_EXECUTIVE_OFFICER]);

$filterStatus = $_GET['status'] ?? 'ALL';
$filterType = $_GET['type'] ?? 'ALL';
$filterCampus = isset($_GET['campus_id']) ? (int) $_GET['campus_id'] : 0;

$counts = fetchAgreementCounts($pdo);
$campuses = fetchCampuses($pdo);
$agreements = fetchFilteredAgreements(
    $pdo,
    $filterStatus !== 'ALL' ? $filterStatus : null,
    $filterType !== 'ALL' ? $filterType : null,
    $filterCampus > 0 ? $filterCampus : null
);

renderDashboardHeader(
    $user,
    'Executive Dashboard',
    'Executive overview of partnership registry status and listings.'
);
?>

<?php renderMetricCards($counts); ?>

<form method="get" action="dashboard_executive.php" class="mb-8 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div class="grid flex-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <label for="status" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
                <select id="status" name="status"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-dwu-green focus:outline-none focus:ring-2 focus:ring-dwu-green/20">
                    <option value="ALL" <?= $filterStatus === 'ALL' ? 'selected' : '' ?>>All statuses</option>
                    <option value="Active" <?= $filterStatus === 'Active' ? 'selected' : '' ?>>Active</option>
                    <option value="Soon to Expire" <?= $filterStatus === 'Soon to Expire' ? 'selected' : '' ?>>Expiring Soon</option>
                    <option value="Expired" <?= $filterStatus === 'Expired' ? 'selected' : '' ?>>Expired</option>
                </select>
            </div>
            <div>
                <label for="type" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Agreement Type</label>
                <select id="type" name="type"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-dwu-green focus:outline-none focus:ring-2 focus:ring-dwu-green/20">
                    <option value="ALL" <?= $filterType === 'ALL' ? 'selected' : '' ?>>All types</option>
                    <option value="MOU" <?= $filterType === 'MOU' ? 'selected' : '' ?>>MOU</option>
                    <option value="MOA" <?= $filterType === 'MOA' ? 'selected' : '' ?>>MOA</option>
                    <option value="DFAT Contract" <?= $filterType === 'DFAT Contract' ? 'selected' : '' ?>>DFAT Contract</option>
                </select>
            </div>
            <div>
                <label for="campus_id" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Campus</label>
                <select id="campus_id" name="campus_id"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-dwu-green focus:outline-none focus:ring-2 focus:ring-dwu-green/20">
                    <option value="0">All campuses</option>
                    <?php foreach ($campuses as $campus): ?>
                        <option value="<?= (int) $campus['Campus_ID'] ?>"
                            <?= $filterCampus === (int) $campus['Campus_ID'] ? 'selected' : '' ?>>
                            <?= e($campus['Name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="flex flex-wrap gap-3">
            <button type="submit"
                    class="rounded-lg bg-dwu-green px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-dwu-dark">
                Apply Filters
            </button>
            <button type="button"
                    onclick="window.print()"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                Print Registry (PDF)
            </button>
        </div>
    </div>
</form>

<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 px-6 py-4">
        <h2 class="text-lg font-semibold text-slate-900">Partnership Registry</h2>
        <p class="text-sm text-slate-500"><?= count($agreements) ?> record(s) matching current filters.</p>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Partner</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Campus</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Signed</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Expiry</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                <?php if ($agreements === []): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                            No partnership records found for the selected filters.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($agreements as $agreement): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-6 py-4 font-mono text-xs text-slate-500">
                                #<?= str_pad((string) $agreement['id'], 4, '0', STR_PAD_LEFT) ?>
                            </td>
                            <td class="px-6 py-4 font-medium text-slate-900"><?= e($agreement['partner']) ?></td>
                            <td class="px-6 py-4 text-slate-600"><?= e($agreement['type']) ?></td>
                            <td class="px-6 py-4 text-slate-600"><?= e($agreement['campus']) ?></td>
                            <td class="px-6 py-4 text-slate-600"><?= e(date('M j, Y', strtotime($agreement['signed_date']))) ?></td>
                            <td class="px-6 py-4 text-slate-600"><?= e(date('M j, Y', strtotime($agreement['expiry']))) ?></td>
                            <td class="px-6 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset <?= statusBadgeClasses($agreement['status']) ?>">
                                    <?= e($agreement['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php renderDashboardFooter(); ?>
