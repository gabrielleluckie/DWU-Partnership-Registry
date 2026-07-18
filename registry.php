<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/guard.php';

$loggedInUser = requireAuth($pdo);
$agreements = fetchAgreements($pdo);

$activePage = 'registry';
$headerTitle = 'Partnership Registry Master';
$pageTitle = 'Registry Master — DWU PDMIS';
$navItems = legacyNavItems($loggedInUser['role']);

require_once __DIR__ . '/includes/header.php';
?>

<div class="custom-card">
    <div class="card-header">Master Partnership Registry</div>
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Partner</th>
                    <th>Agreement Type</th>
                    <th>Campus</th>
                    <th>Contact</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($agreements === []): ?>
                    <tr>
                        <td colspan="6" class="empty-state">No partnership records in the registry yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($agreements as $agreement): ?>
                        <tr>
                            <td><strong>#<?= str_pad((string) $agreement['id'], 4, '0', STR_PAD_LEFT) ?></strong></td>
                            <td style="font-weight:600; color:var(--dwu-green-dark);"><?= e($agreement['partner']) ?></td>
                            <td><?= e($agreement['type']) ?></td>
                            <td><?= e($agreement['campus']) ?></td>
                            <td><?= e($agreement['contact']) ?></td>
                            <td><span class="badge badge-<?= badgeClass($agreement['status']) ?>"><?= e($agreement['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
