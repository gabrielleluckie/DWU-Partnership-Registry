<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/guard.php';

$loggedInUser = requireAuth($pdo);
$history = fetchAgreementHistory($pdo);

$activePage = 'logs';
$headerTitle = 'Agreement History & Audit Trail';
$pageTitle = 'Agreement History — DWU PDMIS';
$navItems = legacyNavItems($loggedInUser['role']);

require_once __DIR__ . '/includes/header.php';
?>

<div class="custom-card">
    <div class="card-header">Agreement History Log</div>
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Event Date</th>
                    <th>Partner</th>
                    <th>Event Type</th>
                    <th>Comments</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($history === []): ?>
                    <tr>
                        <td colspan="4" class="empty-state">No agreement history events recorded yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($history as $entry): ?>
                        <tr>
                            <td style="font-variant-numeric: tabular-nums; color:#555;"><?= e(date('M j, Y', strtotime($entry['Event_Date']))) ?></td>
                            <td><strong><?= e($entry['partner_name']) ?></strong></td>
                            <td><span class="badge" style="background:#e9ecef; color:#495057; font-family:monospace; font-size:10px;"><?= e($entry['Event_type']) ?></span></td>
                            <td><?= e($entry['Comments'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
