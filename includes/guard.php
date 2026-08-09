<?php

declare(strict_types=1);

use App\Models\Agreement;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../config/app.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $path = __DIR__ . '/../app/' . $relative . '.php';

    if (is_file($path)) {
        require_once $path;
    }
});

require_once __DIR__ . '/functions.php';

const ROLE_CAMPUS_ADMIN = 'Campus Admin';
const ROLE_PRESIDENT = 'President';
const ROLE_EXECUTIVE_OFFICER = 'Executive Officer';
const ROLE_PARTNERSHIP_DIRECTOR = 'Partnership Director';

/**
 * @return array{first_name_col: string, last_name_col: string, has_password: bool, campus_table: string}
 */
function usersTableMeta(PDO $pdo): array
{
    static $meta = null;

    if ($meta !== null) {
        return $meta;
    }

    $columns = $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN);
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

    $meta = [
        'first_name_col' => in_array('First_name', $columns, true) ? 'First_name' : 'First_Name',
        'last_name_col'  => in_array('Last_name', $columns, true) ? 'Last_name' : 'Last_Name',
        'has_password'   => in_array('password', $columns, true),
        'campus_table'   => in_array('campus', $tables, true) ? 'campus' : 'campuses',
    ];

    return $meta;
}

function normalizeDbRole(string $role): string
{
    $normalized = roleSlug($role);

    return match ($normalized) {
        'campus_admin'         => ROLE_CAMPUS_ADMIN,
        'partnership_director', 'director' => ROLE_PARTNERSHIP_DIRECTOR,
        'executive_officer'    => ROLE_EXECUTIVE_OFFICER,
        'president'            => ROLE_PRESIDENT,
        default                => $role,
    };
}

/** Normalize any role label to a lowercase slug (e.g. partnership_director). */
function roleSlug(string $role): string
{
    return strtolower(str_replace([' ', '-'], '_', trim($role)));
}

/** Resolve the active role from the user profile or session store. */
function resolveActiveUserRole(?array $user = null): string
{
    if ($user !== null && !empty($user['role'])) {
        return (string) $user['role'];
    }

    if (!empty($_SESSION['user_role'])) {
        return (string) $_SESSION['user_role'];
    }

    if (!empty($_SESSION['role'])) {
        return (string) $_SESSION['role'];
    }

    return '';
}

function syncSessionRole(string $displayRole): void
{
    $_SESSION['user_role'] = normalizeDbRole($displayRole);
    $_SESSION['role'] = roleSlug($_SESSION['user_role']);
}

function normalizeUserRow(array $user): array
{
    if (isset($user['First_name']) || isset($user['First_Name'])) {
        $user['First_name'] = $user['First_name'] ?? $user['First_Name'];
        $user['Last_name'] = $user['Last_name'] ?? $user['Last_Name'];
    }

    $user['Role'] = normalizeDbRole((string) ($user['Role'] ?? ''));
    $user['campus_name'] = $user['campus_name'] ?? 'Head Office';

    return $user;
}

function buildUserSelectSql(PDO $pdo, bool $includePassword = false): string
{
    $meta = usersTableMeta($pdo);
    $campusTable = $meta['campus_table'];
    $first = $meta['first_name_col'];
    $last = $meta['last_name_col'];
    $passwordSelect = $includePassword && $meta['has_password'] ? ', u.password' : '';

    return "SELECT u.User_ID, u.Campus_ID,
                   u.`{$first}` AS First_name, u.`{$last}` AS Last_name,
                   u.Email, u.Phone_Number, u.Role{$passwordSelect},
                   c.Name AS campus_name
            FROM users u
            LEFT JOIN `{$campusTable}` c ON u.Campus_ID = c.Campus_ID";
}

/** Application web-root path (e.g. /IS406_PartnershipRegistry). */
function appBasePath(): string
{
    static $base = null;

    if ($base !== null) {
        return $base;
    }

    $documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    $applicationRoot = realpath(dirname(__DIR__));

    if (
        is_string($documentRoot) && $documentRoot !== false
        && is_string($applicationRoot) && $applicationRoot !== false
    ) {
        $documentRoot = str_replace('\\', '/', $documentRoot);
        $applicationRoot = str_replace('\\', '/', $applicationRoot);

        if (str_starts_with($applicationRoot, $documentRoot)) {
            $base = substr($applicationRoot, strlen($documentRoot));
            $base = rtrim($base, '/');

            return $base === '' ? '' : $base;
        }
    }

    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $scriptDir = dirname($scriptName);

    if (str_ends_with($scriptDir, '/dashboard')) {
        $base = dirname($scriptDir);
    } else {
        $base = ($scriptDir === '/' || $scriptDir === '.') ? '' : $scriptDir;
    }

    $base = rtrim($base, '/');

    return $base === '' ? '' : $base;
}

/** Build an absolute path from the application web root. */
function appUrl(string $path = ''): string
{
    $base = appBasePath();
    $path = ltrim(str_replace('\\', '/', $path), '/');

    if ($base === '') {
        return $path === '' ? '/' : '/' . $path;
    }

    return $path === '' ? $base : $base . '/' . $path;
}

function roleDashboardMap(): array
{
    return [
        ROLE_CAMPUS_ADMIN          => routePath('dashboard/campus-admin'),
        ROLE_PRESIDENT             => routePath('dashboard/registry'),
        ROLE_EXECUTIVE_OFFICER     => routePath('dashboard/registry'),
        ROLE_PARTNERSHIP_DIRECTOR  => routePath('dashboard/director'),
    ];
}

/** Canonical login page path. */
function loginRoute(): string
{
    return appUrl('login');
}

function logoutRoute(): string
{
    return appUrl('logout');
}

/** Build a dashboard or application route path. */
function routePath(string $route): string
{
    return appUrl(ltrim($route, '/'));
}

function dashboardForRole(string $role): ?string
{
    $normalizedRole = normalizeDbRole($role);

    return roleDashboardMap()[$normalizedRole] ?? null;
}

function isPartnershipDirectorRole(?string $role): bool
{
    if ($role === null || $role === '') {
        return false;
    }

    return in_array(roleSlug($role), ['partnership_director', 'director'], true);
}

function isExecutiveRegistryHomeRole(?string $role): bool
{
    if ($role === null || $role === '') {
        return false;
    }

    return in_array(roleSlug($role), ['president', 'executive_officer'], true);
}

function isRegistryDashboardRequest(): bool
{
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $uri = str_replace('\\', '/', (string) ($_SERVER['REQUEST_URI'] ?? ''));

    return str_contains($script, 'registry_dashboard.php')
        || str_contains($script, '/dashboard/registry')
        || preg_match('#/dashboard/registry(?:\.php)?(?:\?|$)#', $uri) === 1;
}

function roleMatchesAllowed(string $role, array $allowedRoles): bool
{
    $roleSlug = roleSlug($role);
    $directorSlugs = ['partnership_director', 'director'];

    foreach ($allowedRoles as $allowedRole) {
        $allowedSlug = roleSlug((string) $allowedRole);

        if ($role === $allowedRole || $allowedSlug === $roleSlug) {
            return true;
        }

        if (
            in_array($roleSlug, $directorSlugs, true)
            && in_array($allowedSlug, $directorSlugs, true)
        ) {
            return true;
        }
    }

    return false;
}

/**
 * Optional back-navigation link for the global header.
 *
 * @return array{label: string, href: string}|null
 */
function registryHeaderBackLink(?array $user = null): ?array
{
    $activeRole = resolveActiveUserRole($user);
    $directorDashboard = routePath('dashboard/director');

    if (dashboardForRole($activeRole) !== $directorDashboard && !isPartnershipDirectorRole($activeRole)) {
        return null;
    }

    if (isExecutiveRegistryHomeRole($activeRole)) {
        return null;
    }

    return [
        'label' => '← Back to Director Dashboard',
        'href'  => $directorDashboard,
    ];
}

function fetchUserById(PDO $pdo, int $userId): ?array
{
    $sql = buildUserSelectSql($pdo) . ' WHERE u.User_ID = :user_id LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['user_id' => $userId]);
    $user = $stmt->fetch();

    return $user ? normalizeUserRow($user) : null;
}

function fetchUserByEmail(PDO $pdo, string $email): ?array
{
    $sql = buildUserSelectSql($pdo, true) . ' WHERE u.Email = :email LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    return $user ? normalizeUserRow($user) : null;
}

function usersTableHasPasswordColumn(PDO $pdo): bool
{
    return usersTableMeta($pdo)['has_password'];
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
        'campus_id'    => isset($user['Campus_ID']) && $user['Campus_ID'] !== null && $user['Campus_ID'] !== ''
            ? (int) $user['Campus_ID']
            : null,
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
    $displayRole = normalizeDbRole((string) ($user['Role'] ?? $user['role'] ?? ''));

    $_SESSION['user_id'] = (int) $user['User_ID'];
    $_SESSION['user_role'] = $displayRole;
    $_SESSION['role'] = roleSlug($displayRole);
    $_SESSION['user_name'] = trim($user['First_name'] . ' ' . $user['Last_name']);
    $_SESSION['campus_id'] = isset($user['Campus_ID']) && $user['Campus_ID'] !== null && $user['Campus_ID'] !== ''
        ? (int) $user['Campus_ID']
        : null;
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
    if (
        !preg_match('#^https?://#i', $location)
        && !str_starts_with($location, '/')
    ) {
        $location = appUrl($location);
    }

    header('Location: ' . $location);
    exit;
}

function redirectWithError(string $error): never
{
    redirect(loginRoute() . '?error=' . urlencode($error));
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

    syncSessionRole((string) $user['role']);

    return $user;
}

function requireRole(PDO $pdo, array $allowedRoles): array
{
    $user = requireAuth($pdo);

    if (!roleMatchesAllowed((string) $user['role'], $allowedRoles)) {
        redirectWithError('You do not have permission to access that page.');
    }

    return $user;
}

function redirectToRoleDashboard(string $role): never
{
    $dashboard = dashboardForRole(normalizeDbRole($role));

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
    return appUrl(ltrim($path, '/'));
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

/**
 * In-memory proposal review queue (session). Campus Admin submissions land here only —
 * they are NOT written to partner/agreement/contact registry tables until the
 * Partnership Director completes the Active Partnership Entry Form.
 */
function initializeMockProposals(): void
{
    $queueVersion = 2;

    if (($_SESSION['proposals_version'] ?? 0) < $queueVersion) {
        $_SESSION['proposals'] = [];
        $_SESSION['proposals_version'] = $queueVersion;
    }

    if (!isset($_SESSION['proposals'])) {
        $_SESSION['proposals'] = [];
    }
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

    $partnershipTypes = $formData['partnership_nature'] ?? $formData['partnership_types'] ?? [];
    if (!is_array($partnershipTypes)) {
        $partnershipTypes = [$partnershipTypes];
    }

    $newId = $maxId + 1;
    // Session queue only — registry tables are updated exclusively via the Director entry form.
    $_SESSION['proposals'][] = [
        'id'                 => $newId,
        'partner_name'       => trim((string) ($formData['partner_name'] ?? $formData['partner_legal_name'] ?? '')),
        'partnership_type'   => implode(', ', array_map('strval', $partnershipTypes)),
        'agreement_type'     => trim((string) ($formData['agreement_type'] ?? 'MOU')),
        'campus'             => trim((string) ($formData['campus'] ?? $formData['submitter_campus'] ?? $user['campus'])),
        'submitted_by'       => trim((string) ($formData['staff_name'] ?? $formData['submitter_name'] ?? $user['name'])),
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

function renderDashboardLogoutAction(?array $backLink = null): void
{
    ?>
    <div class="d-flex justify-content-end align-items-center gap-2 mb-3 app-dashboard-action-bar">
        <?php if (is_array($backLink) && !empty($backLink['href'])): ?>
            <a href="<?= e($backLink['href']) ?>"
               class="btn btn-outline-success btn-sm rounded-circle app-dashboard-icon-btn"
               title="<?= e($backLink['label'] ?? 'Back') ?>"
               aria-label="<?= e($backLink['label'] ?? 'Back') ?>">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
            </a>
        <?php endif; ?>
        <a href="<?= e(logoutRoute()) ?>"
           class="btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-2 px-3 app-dashboard-logout-btn">
            <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
            <span>Logout</span>
        </a>
    </div>
    <?php
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
                    <a href="<?= e(logoutRoute()) ?>"
                       class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                        Logout
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
    $meta = usersTableMeta($pdo);
    $campusTable = $meta['campus_table'];
    $first = $meta['first_name_col'];
    $last = $meta['last_name_col'];

    $sql = "SELECT u.`{$first}` AS First_name, u.`{$last}` AS Last_name, u.Email, u.Role, c.Name AS campus_name
            FROM users u
            LEFT JOIN `{$campusTable}` c ON u.Campus_ID = c.Campus_ID
            WHERE u.Role IN ('Campus Admin', 'President', 'Executive Officer', 'campus_admin', 'president', 'executive_officer')
            ORDER BY u.`{$last}` ASC";

    $stmt = $pdo->query($sql);
    $recipients = [];

    while ($row = $stmt->fetch()) {
        $recipients[] = [
            'name'   => trim($row['First_name'] . ' ' . $row['Last_name']),
            'email'  => $row['Email'],
            'role'   => normalizeDbRole((string) $row['Role']),
            'campus' => $row['campus_name'] ?? 'Head Office',
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
    $meta = usersTableMeta($pdo);
    $campusTable = $meta['campus_table'];
    $first = $meta['first_name_col'];
    $last = $meta['last_name_col'];

    $sql = "SELECT u.`{$first}` AS First_name, u.`{$last}` AS Last_name, u.Email, u.Role, c.Name AS campus_name
            FROM users u
            LEFT JOIN `{$campusTable}` c ON u.Campus_ID = c.Campus_ID
            WHERE u.Role IN ('Partnership Director', 'President', 'Executive Officer', 'partnership_director', 'president', 'executive_officer')
            ORDER BY u.`{$last}` ASC";

    $stmt = $pdo->query($sql);
    $recipients = [];

    while ($row = $stmt->fetch()) {
        $recipients[] = [
            'name'   => trim($row['First_name'] . ' ' . $row['Last_name']),
            'email'  => $row['Email'],
            'role'   => normalizeDbRole((string) $row['Role']),
            'campus' => $row['campus_name'] ?? 'Head Office',
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

    $bodyClass = $options['bodyClass'] ?? 'app-shell';
    $pageSubtitle = $options['pageSubtitle'] ?? '';
    $extraStylesheets = array_merge([
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
        'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
        assetUrl('css/app-shell.css'),
        assetUrl('css/director-header.css'),
        assetUrl('css/site-footer.css'),
    ], $options['extraStylesheets'] ?? []);

    require __DIR__ . '/director-header.php';
}

function renderDirectorDashboardHeader(
    array $user,
    string $pageTitle,
    array $pendingProposals = [],
    int $notificationCount = 0
): void {
    if ($notificationCount <= 0) {
        $notificationCount = count($pendingProposals);
    }

    renderInstitutionalDashboardHeader($user, $pageTitle, [
        'notifications'            => buildDirectorNotifications($pendingProposals),
        'notificationCount'        => $notificationCount,
        'useMockNotificationCount' => false,
        'pageSubtitle'             => 'Review campus proposals and register approved partnerships in the live registry.',
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
        'pageSubtitle'          => 'Submit and review proposed partnership agreements.',
        'extraStylesheets'      => [assetUrl('css/campus-admin-dashboard.css')],
    ]);
}

function renderExecutiveDashboardHeader(
    array $user,
    string $pageTitle,
    string $pageSubtitle = ''
): void {
    global $pdo;

    renderInstitutionalDashboardHeader($user, $pageTitle, [
        'notifications'        => [],
        'notificationCount'    => 0,
        'messageRecipients'    => fetchDirectorMessageRecipients($pdo),
        'notificationsHeading' => 'Registry Notifications',
        'messagePlaceholder'   => 'Type your message to Campus Admins, Partnership Director, or Executive staff...',
        'pageSubtitle'         => $pageSubtitle,
        'extraStylesheets'     => [assetUrl('css/campus-admin-dashboard.css')],
    ]);
}

function renderDirectorDashboardFooter(): void
{
    ?>
        </main>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?= e(assetUrl('js/director-header.js')) ?>"></script>
        <?php renderSiteFooter(); ?>
    </body>
    </html>
    <?php
}

function statusBadgeClasses(string $status): string
{
    return match ($status) {
        'Active', Agreement::STATUS_ACTIVE         => 'bg-success',
        'Expired', Agreement::STATUS_EXPIRED      => 'bg-danger',
        'Expiring Soon', Agreement::STATUS_EXPIRING_SOON, 'Soon to Expire' => 'bg-warning text-dark',
        default                                     => 'bg-secondary',
    };
}

function renderMetricCards(array $counts): void
{
    ?>
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="app-card p-4 h-100">
                <p class="small text-secondary mb-1">Total Agreements</p>
                <p class="h3 fw-bold mb-0"><?= (int) $counts['Total'] ?></p>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="app-card p-4 h-100">
                <p class="small text-secondary mb-1">Active</p>
                <p class="h3 fw-bold mb-0 text-success"><?= (int) $counts['Active'] ?></p>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="app-card p-4 h-100">
                <p class="small text-secondary mb-1">Expiring Soon</p>
                <p class="h3 fw-bold mb-0 text-warning"><?= (int) ($counts['Expiring Soon'] ?? $counts['Soon to Expire'] ?? 0) ?></p>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="app-card p-4 h-100">
                <p class="small text-secondary mb-1">Expired</p>
                <p class="h3 fw-bold mb-0 text-danger"><?= (int) $counts['Expired'] ?></p>
            </div>
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
            'href'  => routePath('dashboard/campus-admin'),
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
