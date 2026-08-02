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

renderExecutiveDashboardHeader(
    $user,
    'Executive Dashboard',
    'Executive overview of partnership registry status and listings.'
);
?>

<div class="app-page-heading mb-4">
    <h1 class="h3 mb-1">Executive Dashboard</h1>
    <p class="text-secondary mb-0">Executive overview of partnership registry status and listings.</p>
</div>

<?php renderMetricCards($counts); ?>

<form method="get" action="dashboard_executive.php" class="app-card mb-4">
    <div class="app-card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="status" class="form-label small fw-semibold text-secondary">Status</label>
                <select id="status" name="status" class="form-select form-select-sm">
                    <option value="ALL" <?= $filterStatus === 'ALL' ? 'selected' : '' ?>>All statuses</option>
                    <option value="Active" <?= $filterStatus === 'Active' ? 'selected' : '' ?>>Active</option>
                    <option value="Soon to Expire" <?= $filterStatus === 'Soon to Expire' ? 'selected' : '' ?>>Expiring Soon</option>
                    <option value="Expired" <?= $filterStatus === 'Expired' ? 'selected' : '' ?>>Expired</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="type" class="form-label small fw-semibold text-secondary">Agreement Type</label>
                <select id="type" name="type" class="form-select form-select-sm">
                    <option value="ALL" <?= $filterType === 'ALL' ? 'selected' : '' ?>>All types</option>
                    <option value="MOU" <?= $filterType === 'MOU' ? 'selected' : '' ?>>MOU</option>
                    <option value="MOA" <?= $filterType === 'MOA' ? 'selected' : '' ?>>MOA</option>
                    <option value="DFAT Contract" <?= $filterType === 'DFAT Contract' ? 'selected' : '' ?>>DFAT Contract</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="campus_id" class="form-label small fw-semibold text-secondary">Campus</label>
                <select id="campus_id" name="campus_id" class="form-select form-select-sm">
                    <option value="0">All campuses</option>
                    <?php foreach ($campuses as $campus): ?>
                        <option value="<?= (int) $campus['Campus_ID'] ?>"
                            <?= $filterCampus === (int) $campus['Campus_ID'] ? 'selected' : '' ?>>
                            <?= e($campus['Name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-success btn-sm">Apply Filters</button>
                <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm">Print Registry (PDF)</button>
            </div>
        </div>
    </div>
</form>

<section class="app-card overflow-hidden">
    <div class="app-card-header">
        <h2 class="h5 mb-1">Partnership Registry</h2>
        <p class="small text-secondary mb-0"><?= count($agreements) ?> record(s) matching current filters.</p>
    </div>

    <div class="table-responsive">
        <table class="table table-dark table-hover table-borderless mb-0 align-middle">
            <thead class="border-bottom border-secondary">
                <tr>
                    <th class="small text-secondary">ID</th>
                    <th class="small text-secondary">Partner</th>
                    <th class="small text-secondary">Type</th>
                    <th class="small text-secondary">Campus</th>
                    <th class="small text-secondary">Signed</th>
                    <th class="small text-secondary">Expiry</th>
                    <th class="small text-secondary">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($agreements === []): ?>
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-5">
                            No partnership records found for the selected filters.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($agreements as $agreement): ?>
                        <tr>
                            <td class="font-monospace small text-secondary">
                                #<?= str_pad((string) $agreement['id'], 4, '0', STR_PAD_LEFT) ?>
                            </td>
                            <td class="fw-medium"><?= e($agreement['partner']) ?></td>
                            <td class="text-secondary"><?= e($agreement['type']) ?></td>
                            <td class="text-secondary"><?= e($agreement['campus']) ?></td>
                            <td class="text-secondary"><?= e(date('M j, Y', strtotime($agreement['signed_date']))) ?></td>
                            <td class="text-secondary"><?= e(date('M j, Y', strtotime($agreement['expiry']))) ?></td>
                            <td>
                                <span class="badge rounded-pill <?= statusBadgeClasses($agreement['status']) ?>">
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

<?php renderDirectorDashboardFooter(); ?>
