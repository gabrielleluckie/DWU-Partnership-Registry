<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

const ROLE_CAMPUS_ADMIN = 'Campus Admin';
const ROLE_PRESIDENT = 'President';
const ROLE_EXECUTIVE_OFFICER = 'Executive Officer';
const ROLE_PARTNERSHIP_DIRECTOR = 'Partnership Director';

function roleDashboardMap(): array
{
    return [
        ROLE_CAMPUS_ADMIN          => 'dashboard_campus_admin.php',
        ROLE_PRESIDENT             => 'dashboard_executive.php',
        ROLE_EXECUTIVE_OFFICER     => 'dashboard_executive.php',
        ROLE_PARTNERSHIP_DIRECTOR  => 'dashboard_director.php',
    ];
}

function dashboardForRole(string $role): ?string
{
    return roleDashboardMap()[$role] ?? null;
}

function fetchUserById(PDO $pdo, int $userId): ?array
{
    $stmt = $pdo->prepare(
        'SELECT u.User_ID, u.Campus_ID, u.First_name, u.Last_name, u.Email,
                u.Phone_Number, u.Role, c.Name AS campus_name
         FROM users u
         INNER JOIN campuses c ON u.Campus_ID = c.Campus_ID
         WHERE u.User_ID = :user_id
         LIMIT 1'
    );
    $stmt->execute(['user_id' => $userId]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function fetchUserByEmail(PDO $pdo, string $email): ?array
{
    $stmt = $pdo->prepare(
        'SELECT u.User_ID, u.Campus_ID, u.First_name, u.Last_name, u.Email,
                u.password, u.Phone_Number, u.Role, c.Name AS campus_name
         FROM users u
         INNER JOIN campuses c ON u.Campus_ID = c.Campus_ID
         WHERE u.Email = :email
         LIMIT 1'
    );
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function formatUserProfile(array $user): array
{
    $fullName = trim($user['First_name'] . ' ' . $user['Last_name']);

    return [
        'id'           => (int) $user['User_ID'],
        'name'         => $fullName,
        'display_name' => strtoupper($user['Last_name']) . ', ' . $user['First_name'],
        'email'        => $user['Email'],
        'role'         => $user['Role'],
        'campus'       => $user['campus_name'],
        'campus_id'    => (int) $user['Campus_ID'],
        'staff_id'     => 'USR-' . str_pad((string) $user['User_ID'], 4, '0', STR_PAD_LEFT),
        'department'   => 'Office of Partnerships & Development',
        'last_login'   => date('Y-m-d H:i:s'),
        'avatar'       => 'https://ui-avatars.com/api/?name='
            . urlencode($fullName)
            . '&background=006633&color=FFCC00&size=128&bold=true',
    ];
}

function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

function currentUser(PDO $pdo): ?array
{
    if (!isLoggedIn()) {
        return null;
    }

    $user = fetchUserById($pdo, (int) $_SESSION['user_id']);

    return $user ? formatUserProfile($user) : null;
}

function loginUser(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['User_ID'];
    $_SESSION['user_role'] = $user['Role'];
    $_SESSION['user_name'] = trim($user['First_name'] . ' ' . $user['Last_name']);
}

function logoutUser(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            (bool) $params['secure'],
            (bool) $params['httponly']
        );
    }

    session_destroy();
}

function redirect(string $location): never
{
    header('Location: ' . $location);
    exit;
}

function redirectWithError(string $error): never
{
    redirect('index.php?error=' . urlencode($error));
}

function requireAuth(PDO $pdo): array
{
    if (!isLoggedIn()) {
        redirectWithError('Please sign in to continue.');
    }

    $user = currentUser($pdo);

    if ($user === null) {
        logoutUser();
        redirectWithError('Your session has expired. Please sign in again.');
    }

    return $user;
}

function requireRole(PDO $pdo, array $allowedRoles): array
{
    $user = requireAuth($pdo);

    if (!in_array($user['role'], $allowedRoles, true)) {
        redirectWithError('You do not have permission to access that page.');
    }

    return $user;
}

function redirectToRoleDashboard(string $role): never
{
    $dashboard = dashboardForRole($role);

    if ($dashboard === null) {
        redirectWithError('No dashboard is configured for your role.');
    }

    redirect($dashboard);
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function assetUrl(string $path): string
{
    static $base = null;

    if ($base === null) {
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $base = ($scriptDir === '/' || $scriptDir === '.') ? '' : $scriptDir;
    }

    return ($base !== '' ? $base . '/' : '') . ltrim($path, '/');
}

function flashMessage(string $key): ?string
{
    if (empty($_SESSION['flash'][$key])) {
        return null;
    }

    $message = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);

    return $message;
}

function setFlash(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

function initializeMockProposals(): void
{
    if (!empty($_SESSION['proposals'])) {
        return;
    }

    $_SESSION['proposals'] = [
        [
            'id'                 => 1001,
            'partner_name'       => 'Port Moresby General Hospital',
            'partnership_type'   => 'Clinical Training Partnership',
            'agreement_type'     => 'MOA',
            'campus'             => 'Port Moresby Campus',
            'submitted_by'       => 'Peter Kari',
            'submitted_at'       => '2026-07-10',
            'status'             => 'pending',
            'rejection_comment'  => '',
        ],
        [
            'id'                 => 1002,
            'partner_name'       => 'Kokopo Tourism Board',
            'partnership_type'   => 'Community Engagement',
            'agreement_type'     => 'MOU',
            'campus'             => 'Rabaul Campus',
            'submitted_by'       => 'Michael Tavana',
            'submitted_at'       => '2026-07-12',
            'status'             => 'pending',
            'rejection_comment'  => '',
        ],
        [
            'id'                 => 1003,
            'partner_name'       => 'Sepik River Eco-Tourism Cooperative',
            'partnership_type'   => 'Research Collaboration',
            'agreement_type'     => 'MOU',
            'campus'             => 'Wewak Campus',
            'submitted_by'       => 'Alois Sanki',
            'submitted_at'       => '2026-07-08',
            'status'             => 'approved',
            'rejection_comment'  => '',
        ],
        [
            'id'                 => 1004,
            'partner_name'       => 'Madang Provincial Health Authority',
            'partnership_type'   => 'Health Outreach',
            'agreement_type'     => 'MOA',
            'campus'             => 'Wewak Campus',
            'submitted_by'       => 'Alois Sanki',
            'submitted_at'       => '2026-07-01',
            'status'             => 'rejected',
            'rejection_comment'  => 'Incomplete partner contact details and unsigned draft MOA attached.',
        ],
    ];
}

function getProposalsByStatus(string $status): array
{
    initializeMockProposals();

    return array_values(array_filter(
        $_SESSION['proposals'],
        static fn(array $proposal): bool => $proposal['status'] === $status
    ));
}

function findProposalById(int $proposalId): ?array
{
    initializeMockProposals();

    foreach ($_SESSION['proposals'] as $proposal) {
        if ((int) $proposal['id'] === $proposalId) {
            return $proposal;
        }
    }

    return null;
}

function updateProposalStatus(int $proposalId, string $status, string $comment = ''): bool
{
    initializeMockProposals();

    foreach ($_SESSION['proposals'] as &$proposal) {
        if ((int) $proposal['id'] === $proposalId) {
            $proposal['status'] = $status;
            $proposal['rejection_comment'] = $comment;
            return true;
        }
    }

    unset($proposal);

    return false;
}

function getCampusProposalDraft(array $user): array
{
    $key = $user['staff_id'] ?? $user['email'] ?? 'default';

    return $_SESSION['proposal_drafts'][$key] ?? [];
}

function saveCampusProposalDraft(array $user, array $formData): void
{
    $key = $user['staff_id'] ?? $user['email'] ?? 'default';
    $_SESSION['proposal_drafts'][$key] = $formData;
}

function createCampusProposal(array $formData, array $user, string $status = 'pending'): int
{
    initializeMockProposals();

    $maxId = 1000;
    foreach ($_SESSION['proposals'] as $proposal) {
        $maxId = max($maxId, (int) $proposal['id']);
    }

    $partnershipTypes = $formData['partnership_types'] ?? [];
    if (!is_array($partnershipTypes)) {
        $partnershipTypes = [$partnershipTypes];
    }

    $newId = $maxId + 1;
    $_SESSION['proposals'][] = [
        'id'                 => $newId,
        'partner_name'       => trim((string) ($formData['partner_legal_name'] ?? '')),
        'partnership_type'   => implode(', ', array_map('strval', $partnershipTypes)),
        'agreement_type'     => trim((string) ($formData['agreement_type'] ?? 'MOU')),
        'campus'             => trim((string) ($formData['submitter_campus'] ?? $user['campus'])),
        'submitted_by'       => trim((string) ($formData['submitter_name'] ?? $user['name'])),
        'submitted_at'       => date('Y-m-d'),
        'status'             => $status,
        'rejection_comment'  => '',
        'form_data'          => $formData,
    ];

    return $newId;
}

function renderSiteFooter(): void
{
    require __DIR__ . '/site-footer.php';
}

function renderDashboardHeader(array $user, string $title, string $subtitle = ''): void
{
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= e($title) ?> — DWU PDMIS</title>
        <link rel="stylesheet" href="css/main.css">
        <link rel="stylesheet" href="css/site-footer.css">
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            dwu: {
                                green: '#006633',
                                dark: '#004d26',
                                gold: '#FFCC00',
                            }
                        }
                    }
                }
            }
        </script>
    </head>
    <body class="flex min-h-screen flex-col bg-slate-50 text-slate-800 antialiased">
        <header class="border-b border-slate-200 bg-white shadow-sm">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-dwu-green">Divine Word University</p>
                    <h1 class="text-lg font-bold text-slate-900 sm:text-xl"><?= e($title) ?></h1>
                    <?php if ($subtitle !== ''): ?>
                        <p class="mt-1 text-sm text-slate-500"><?= e($subtitle) ?></p>
                    <?php endif; ?>
                </div>
                <div class="flex items-center gap-3 sm:gap-4">
                    <div class="hidden text-right sm:block">
                        <p class="text-sm font-semibold text-slate-900"><?= e($user['name']) ?></p>
                        <p class="text-xs text-slate-500"><?= e($user['role']) ?> · <?= e($user['campus']) ?></p>
                    </div>
                    <a href="logout.php"
                       class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                        Sign Out
                    </a>
                </div>
            </div>
        </header>
        <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
    <?php
}

function renderDashboardFooter(): void
{
    ?>
        </main>
        <?php renderSiteFooter(); ?>
    </body>
    </html>
    <?php
}

function fetchDirectorMessageRecipients(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT u.First_name, u.Last_name, u.Email, u.Role, c.Name AS campus_name
         FROM users u
         INNER JOIN campuses c ON u.Campus_ID = c.Campus_ID
         WHERE u.Role IN ('Campus Admin', 'President', 'Executive Officer')
         ORDER BY
            FIELD(u.Role, 'President', 'Executive Officer', 'Campus Admin'),
            c.Name ASC,
            u.Last_name ASC"
    );

    $recipients = [];

    while ($row = $stmt->fetch()) {
        $recipients[] = [
            'name'   => trim($row['First_name'] . ' ' . $row['Last_name']),
            'email'  => $row['Email'],
            'role'   => $row['Role'],
            'campus' => $row['campus_name'],
        ];
    }

    return $recipients;
}

function buildDirectorNotifications(array $pendingProposals): array
{
    $notifications = [];

    foreach ($pendingProposals as $proposal) {
        $notifications[] = [
            'title'  => 'New proposal: ' . $proposal['partner_name'],
            'detail' => $proposal['campus'] . ' · Submitted ' . $proposal['submitted_at'] . ' by ' . $proposal['submitted_by'],
        ];
    }

    return $notifications;
}

function buildCampusAdminNotifications(array $approvedProposals, array $rejectedProposals): array
{
    $notifications = [];

    foreach ($approvedProposals as $proposal) {
        $notifications[] = [
            'title'  => 'Approved: ' . $proposal['partner_name'],
            'detail' => $proposal['agreement_type'] . ' · Cleared on ' . $proposal['submitted_at'],
        ];
    }

    foreach ($rejectedProposals as $proposal) {
        $detail = $proposal['agreement_type'] . ' · Returned for revision';
        if (($proposal['rejection_comment'] ?? '') !== '') {
            $detail .= ' — ' . $proposal['rejection_comment'];
        }

        $notifications[] = [
            'title'  => 'Rejected: ' . $proposal['partner_name'],
            'detail' => $detail,
        ];
    }

    return $notifications;
}

function fetchCampusAdminMessageRecipients(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT u.First_name, u.Last_name, u.Email, u.Role, c.Name AS campus_name
         FROM users u
         INNER JOIN campuses c ON u.Campus_ID = c.Campus_ID
         WHERE u.Role IN ('Partnership Director', 'President', 'Executive Officer')
         ORDER BY
            FIELD(u.Role, 'Partnership Director', 'President', 'Executive Officer'),
            u.Last_name ASC"
    );

    $recipients = [];

    while ($row = $stmt->fetch()) {
        $recipients[] = [
            'name'   => trim($row['First_name'] . ' ' . $row['Last_name']),
            'email'  => $row['Email'],
            'role'   => $row['Role'],
            'campus' => $row['campus_name'],
        ];
    }

    return $recipients;
}

function renderInstitutionalDashboardHeader(
    array $user,
    string $pageTitle,
    array $options = []
): void {
    global $pdo;

    $notifications = $options['notifications'] ?? [];
    $notificationCount = (int) ($options['notificationCount'] ?? 0);
    $messageRecipients = $options['messageRecipients'] ?? fetchDirectorMessageRecipients($pdo);
    $notificationsHeading = $options['notificationsHeading'] ?? 'Campus Proposal Notifications';
    $messagePlaceholder = $options['messagePlaceholder'] ?? 'Type your message to Campus Admins, President, or Executive Officer...';
    $useMockNotificationCount = $options['useMockNotificationCount'] ?? false;

    if ($notificationCount <= 0 && $notifications !== []) {
        $notificationCount = count($notifications);
    }

    if ($useMockNotificationCount && $notificationCount <= 0) {
        $notificationCount = 3;
    }

    require __DIR__ . '/director-header.php';
}

function renderDirectorDashboardHeader(
    array $user,
    string $pageTitle,
    array $pendingProposals = [],
    int $notificationCount = 0
): void {
    renderInstitutionalDashboardHeader($user, $pageTitle, [
        'notifications'            => buildDirectorNotifications($pendingProposals),
        'notificationCount'        => $notificationCount,
        'useMockNotificationCount' => true,
    ]);
}

function renderCampusAdminDashboardHeader(
    array $user,
    string $pageTitle,
    array $approvedProposals = [],
    array $rejectedProposals = []
): void {
    global $pdo;

    renderInstitutionalDashboardHeader($user, $pageTitle, [
        'notifications'         => buildCampusAdminNotifications($approvedProposals, $rejectedProposals),
        'messageRecipients'     => fetchCampusAdminMessageRecipients($pdo),
        'notificationsHeading'  => 'Proposal Status Updates',
        'messagePlaceholder'    => 'Type your message to the Partnership Director, President, or Executive Officer...',
    ]);
}

function renderDirectorDashboardFooter(): void
{
    ?>
        </main>
        <script src="<?= e(assetUrl('js/director-header.js')) ?>"></script>
        <?php renderSiteFooter(); ?>
    </body>
    </html>
    <?php
}

function statusBadgeClasses(string $status): string
{
    return match ($status) {
        'Active'         => 'bg-emerald-100 text-emerald-800 ring-emerald-600/20',
        'Expired'        => 'bg-rose-100 text-rose-800 ring-rose-600/20',
        'Soon to Expire' => 'bg-amber-100 text-amber-800 ring-amber-600/20',
        default          => 'bg-slate-100 text-slate-700 ring-slate-500/20',
    };
}

function renderMetricCards(array $counts): void
{
    ?>
    <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Total Agreements</p>
            <p class="mt-2 text-3xl font-bold text-slate-900"><?= (int) $counts['Total'] ?></p>
        </div>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-emerald-700">Active</p>
            <p class="mt-2 text-3xl font-bold text-emerald-900"><?= (int) $counts['Active'] ?></p>
        </div>
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-amber-700">Expiring Soon</p>
            <p class="mt-2 text-3xl font-bold text-amber-900"><?= (int) $counts['Soon to Expire'] ?></p>
        </div>
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-rose-700">Expired</p>
            <p class="mt-2 text-3xl font-bold text-rose-900"><?= (int) $counts['Expired'] ?></p>
        </div>
    </div>
    <?php
}

function legacyNavItems(string $role): array
{
    $homeHref = dashboardForRole($role) ?? 'router.php';

    $items = [
        'home' => [
            'label' => 'My Dashboard',
            'href'  => $homeHref,
            'icon'  => '<svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>',
        ],
        'registry' => [
            'label' => 'Registry Master',
            'href'  => 'registry.php',
            'icon'  => '<svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"/><path d="M4 9h16"/><path d="M9 4v16"/></svg>',
        ],
        'logs' => [
            'label' => 'Agreement History',
            'href'  => 'logs.php',
            'icon'  => '<svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>',
        ],
    ];

    if ($role === ROLE_CAMPUS_ADMIN) {
        $items['intake'] = [
            'label' => 'Submit Proposal',
            'href'  => 'dashboard_campus_admin.php',
            'icon'  => '<svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12H2"/><path d="M5 12V4h14v8"/><path d="M5 12v8h14v-8"/></svg>',
        ];
    }

    if (in_array($role, [ROLE_PARTNERSHIP_DIRECTOR, ROLE_PRESIDENT, ROLE_EXECUTIVE_OFFICER], true)) {
        $items['partners'] = [
            'label' => 'Partner Directory',
            'href'  => 'security.php',
            'icon'  => '<svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 11-7.778 7.778 5.5 5.5 0 017.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>',
        ];
    }

    return $items;
}
