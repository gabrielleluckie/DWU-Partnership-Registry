<?php

/**
 * Shared partnership registry listing (Director, President, Executive Officer).
 *
 * Expected variables:
 * - $agreements (list<array>)
 * - $filterStatus, $filterCampus, $campuses
 * - $highlightAgreementId (optional int)
 */

use App\Models\Agreement;

$highlightAgreementId = isset($highlightAgreementId) ? (int) $highlightAgreementId : 0;
$listStatus = $filterStatus ?? 'ALL';
$listCampus = (int) ($filterCampus ?? 0);
?>
<form method="get" action="<?= e(routePath('dashboard/registry')) ?>" class="app-card mb-4">
    <div class="app-card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-6">
                <label for="status" class="form-label small fw-semibold text-secondary">Status</label>
                <select id="status" name="status" class="form-select form-select-sm">
                    <option value="ALL" <?= ($filterStatus ?? 'ALL') === 'ALL' ? 'selected' : '' ?>>All</option>
                    <option value="<?= e(Agreement::STATUS_ACTIVE) ?>" <?= ($filterStatus ?? '') === Agreement::STATUS_ACTIVE ? 'selected' : '' ?>>Active</option>
                    <option value="<?= e(Agreement::STATUS_EXPIRING_SOON) ?>" <?= ($filterStatus ?? '') === Agreement::STATUS_EXPIRING_SOON ? 'selected' : '' ?>>Expiring Soon</option>
                    <option value="<?= e(Agreement::STATUS_EXPIRED) ?>" <?= ($filterStatus ?? '') === Agreement::STATUS_EXPIRED ? 'selected' : '' ?>>Expired</option>
                </select>
            </div>
            <div class="col-md-6">
                <label for="campus_id" class="form-label small fw-semibold text-secondary">Campus</label>
                <select id="campus_id" name="campus_id" class="form-select form-select-sm">
                    <option value="0">All campuses</option>
                    <?php foreach ($campuses as $campus): ?>
                        <option value="<?= (int) $campus['Campus_ID'] ?>"
                            <?= (int) ($filterCampus ?? 0) === (int) $campus['Campus_ID'] ? 'selected' : '' ?>>
                            <?= e($campus['Name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-success btn-sm">Apply Filters</button>
                <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm">Print Registry</button>
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
        <table class="table table-hover table-borderless mb-0 align-middle registry-listing-table">
            <thead>
                <tr>
                    <th class="small text-secondary">Partner Name</th>
                    <th class="small text-secondary">Campus</th>
                    <th class="small text-secondary">Agreement Type</th>
                    <th class="small text-secondary">Lifespan</th>
                    <th class="small text-secondary">Status</th>
                    <th class="small text-secondary">Document</th>
                    <th class="small text-secondary"></th>
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
                        <?php
                        $rowId = (int) ($agreement['id'] ?? 0);
                        $isHighlighted = $highlightAgreementId > 0 && $rowId === $highlightAgreementId;
                        $signedLabel = !empty($agreement['signed_date'])
                            ? date('M j, Y', strtotime($agreement['signed_date']))
                            : '—';
                        $expiryLabel = !empty($agreement['expiry'])
                            ? date('M j, Y', strtotime($agreement['expiry']))
                            : '—';
                        $detailUrl = registryDashboardUrl([
                            'status'       => $listStatus,
                            'campus_id'    => $listCampus,
                            'agreement_id' => $rowId,
                        ]);
                        ?>
                        <tr id="agreement-<?= $rowId ?>"
                            class="registry-agreement-row<?= $isHighlighted ? ' table-warning' : '' ?>"
                            data-href="<?= e($detailUrl) ?>"
                            tabindex="0"
                            role="link"
                            aria-label="View details for <?= e((string) ($agreement['partner'] ?? 'agreement')) ?>">
                            <td class="fw-medium">
                                <a href="<?= e($detailUrl) ?>" class="registry-agreement-name">
                                    <?= e($agreement['partner']) ?>
                                </a>
                            </td>
                            <td class="text-secondary"><?= e($agreement['campus']) ?></td>
                            <td class="text-secondary"><?= e($agreement['type']) ?></td>
                            <td>
                                <div class="agreement-lifespan small">
                                    <span class="lifespan-node"><?= e($signedLabel) ?></span>
                                    <span class="lifespan-arrow" aria-hidden="true">→</span>
                                    <span class="lifespan-duration badge bg-light text-dark border"><?= e($agreement['duration'] ?? '—') ?></span>
                                    <span class="lifespan-arrow" aria-hidden="true">→</span>
                                    <span class="lifespan-node text-warning-emphasis"><?= e($expiryLabel) ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="badge rounded-pill <?= statusBadgeClasses($agreement['status']) ?>">
                                    <?= e($agreement['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (agreementHasDocument($agreement)): ?>
                                    <div class="d-flex flex-wrap gap-1">
                                        <a class="btn btn-sm btn-outline-dark"
                                           href="<?= e(agreementDownloadUrl($rowId)) ?>"
                                           target="_blank"
                                           rel="noopener">View</a>
                                        <a class="btn btn-sm btn-success"
                                           href="<?= e(agreementDownloadUrl($rowId, true)) ?>">Download</a>
                                    </div>
                                <?php else: ?>
                                    <span class="small text-secondary">None</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-dark" href="<?= e($detailUrl) ?>">Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<style>
.agreement-lifespan {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.35rem;
    color: #94a3b8;
}
.agreement-lifespan .lifespan-arrow {
    opacity: 0.65;
    font-size: 0.75rem;
}
.agreement-lifespan .lifespan-duration {
    font-weight: 600;
}
.registry-agreement-row {
    cursor: pointer;
}
.registry-agreement-name {
    color: inherit;
    text-decoration: none;
    font-weight: 600;
}
.registry-agreement-name:hover {
    text-decoration: underline;
}
</style>

<script>
document.querySelectorAll('tr.registry-agreement-row[data-href]').forEach(function (row) {
    row.addEventListener('click', function (event) {
        if (event.target.closest('a, button, input, label, select')) {
            return;
        }
        window.location.href = row.getAttribute('data-href');
    });
    row.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter' && event.key !== ' ') {
            return;
        }
        if (event.target.closest('a, button, input, label, select')) {
            return;
        }
        event.preventDefault();
        window.location.href = row.getAttribute('data-href');
    });
});
</script>
