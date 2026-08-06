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
        <table class="table table-dark table-hover table-borderless mb-0 align-middle">
            <thead class="border-bottom border-secondary">
                <tr>
                    <th class="small text-secondary">Partner Name</th>
                    <th class="small text-secondary">Campus</th>
                    <th class="small text-secondary">Agreement Type</th>
                    <th class="small text-secondary">Lifespan</th>
                    <th class="small text-secondary">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($agreements === []): ?>
                    <tr>
                        <td colspan="5" class="text-center text-secondary py-5">
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
                        ?>
                        <tr id="agreement-<?= $rowId ?>" class="<?= $isHighlighted ? 'table-warning' : '' ?>">
                            <td class="fw-medium"><?= e($agreement['partner']) ?></td>
                            <td class="text-secondary"><?= e($agreement['campus']) ?></td>
                            <td class="text-secondary"><?= e($agreement['type']) ?></td>
                            <td>
                                <div class="agreement-lifespan small">
                                    <span class="lifespan-node"><?= e($signedLabel) ?></span>
                                    <span class="lifespan-arrow" aria-hidden="true">→</span>
                                    <span class="lifespan-duration badge bg-dark border border-secondary"><?= e($agreement['duration'] ?? '—') ?></span>
                                    <span class="lifespan-arrow" aria-hidden="true">→</span>
                                    <span class="lifespan-node text-warning-emphasis"><?= e($expiryLabel) ?></span>
                                </div>
                            </td>
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
</style>

<?php if ($highlightAgreementId > 0): ?>
<script>
document.getElementById('agreement-<?= (int) $highlightAgreementId ?>')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
</script>
<?php endif; ?>
