<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/guard.php';

$loggedInUser = requireRole($pdo, [
    ROLE_PARTNERSHIP_DIRECTOR,
    ROLE_PRESIDENT,
    ROLE_EXECUTIVE_OFFICER,
]);

$partners = fetchPartnersWithContacts($pdo);

$activePage = 'partners';
$headerTitle = 'Partner Directory';
$pageTitle = 'Partner Directory — DWU PDMIS';
$navItems = legacyNavItems($loggedInUser['role']);

require_once __DIR__ . '/includes/header.php';
?>

<div class="custom-card">
    <div class="card-header">Registered Partner Organisations</div>
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Partner</th>
                    <th>Campus</th>
                    <th>Country</th>
                    <th>Primary Contact</th>
                    <th>Contact Email</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($partners === []): ?>
                    <tr>
                        <td colspan="5" class="empty-state">No partner organisations registered yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($partners as $partner): ?>
                        <tr>
                            <td style="font-weight:600;"><?= e($partner['partner_name']) ?></td>
                            <td><?= e($partner['campus_name']) ?></td>
                            <td><?= e($partner['Country']) ?></td>
                            <td><?= e($partner['contact_name'] ?? 'N/A') ?></td>
                            <td><?= e($partner['contact_email'] ?? 'N/A') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
