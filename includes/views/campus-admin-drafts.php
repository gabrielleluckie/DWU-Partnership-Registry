<?php

/**
 * Campus Admin — saved draft proposals list.
 *
 * Expected:
 * - $drafts (list<array>)
 * - $editingDraftId (int)
 * - $draftsReturnTab (string)
 */

$drafts = $drafts ?? [];
$editingDraftId = (int) ($editingDraftId ?? 0);
$draftsReturnTab = (string) ($draftsReturnTab ?? 'submit');
if (!in_array($draftsReturnTab, ['submit', 'review', 'agreements'], true)) {
    $draftsReturnTab = 'submit';
}

$campusAdminUrl = routePath('dashboard/campus-admin');
?>

<section class="review-panel campus-admin-drafts-panel mb-3">
    <div class="review-panel-header">
        <h2>Draft Proposals</h2>
    </div>
    <div class="review-panel-body">
        <?php if ($drafts === []): ?>
            <p class="review-empty">No saved drafts.</p>
        <?php else: ?>
            <?php foreach ($drafts as $savedDraft): ?>
                <?php
                $draftId = (int) ($savedDraft['id'] ?? 0);
                $isEditing = $editingDraftId > 0 && $draftId === $editingDraftId;
                $partnerLabel = trim((string) ($savedDraft['partner_name'] ?? ''));
                if ($partnerLabel === '') {
                    $partnerLabel = (string) ($savedDraft['title'] ?? 'Untitled draft');
                }
                $metaParts = array_filter([
                    (string) ($savedDraft['agreement_type'] ?? ''),
                    (string) ($savedDraft['campus'] ?? ''),
                ], static fn (string $part): bool => $part !== '');
                $continueUrl = $campusAdminUrl . '?tab=submit&draft=' . $draftId;
                ?>
                <article class="review-item campus-admin-draft-item<?= $isEditing ? ' is-editing' : '' ?>">
                    <div class="review-item-top">
                        <div>
                            <h3>
                                <a href="<?= e($continueUrl) ?>" class="campus-admin-draft-link">
                                    <?= e($partnerLabel) ?>
                                </a>
                            </h3>
                            <?php if ($metaParts !== []): ?>
                                <p class="review-meta"><?= e(implode(' · ', $metaParts)) ?></p>
                            <?php endif; ?>
                        </div>
                        <span class="badge-draft"><?= $isEditing ? 'Editing' : 'Draft' ?></span>
                    </div>
                    <p class="review-date">Saved <?= e((string) ($savedDraft['saved_at'] ?? '')) ?></p>
                    <div class="campus-admin-draft-actions">
                        <a href="<?= e($continueUrl) ?>" class="btn-review-action">
                            <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>
                            Edit
                        </a>
                        <form method="post" action="<?= e($campusAdminUrl . '?tab=' . rawurlencode($draftsReturnTab)) ?>" class="campus-admin-draft-delete">
                            <input type="hidden" name="form_action" value="delete_draft">
                            <input type="hidden" name="draft_id" value="<?= $draftId ?>">
                            <input type="hidden" name="return_tab" value="<?= e($draftsReturnTab) ?>">
                            <button type="submit"
                                    class="btn-review-action campus-admin-draft-delete-btn"
                                    onclick="return confirm('Delete this draft proposal? This cannot be undone.');">
                                <i class="bi bi-trash me-1" aria-hidden="true"></i>
                                Delete
                            </button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
