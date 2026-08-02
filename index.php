<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/guard.php';
require_once __DIR__ . '/includes/dev-auth.php';

if (isLoggedIn()) {
    $user = currentUser($pdo);

    if ($user !== null) {
        redirectToRoleDashboard($user['role']);
    }
}

$error = $_GET['error'] ?? '';
$success = flashMessage('success');
$loginError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['dev_quick_login'])) {
        if (!isDevAuthEnabled()) {
            $loginError = 'Development quick-login is not available in this environment.';
        } else {
            $devEmail = strtolower(trim((string) ($_POST['email'] ?? '')));

            if ($devEmail === '') {
                $loginError = 'Please select a development test account.';
            } elseif (!attemptDevQuickLogin($pdo, $devEmail)) {
                $loginError = 'Dev quick-login failed. Ensure seeded users exist (run scripts/seed_dev_users.sql).';
            } else {
                $user = currentUser($pdo);
                redirectToRoleDashboard($user['role'] ?? $_SESSION['user_role'] ?? ROLE_CAMPUS_ADMIN);
            }
        }
    } else {
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';

        if (!is_string($email) || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $loginError = 'Please enter a valid email address.';
        } elseif ($password === '') {
            $loginError = 'Please enter your password.';
        } elseif (!usersTableHasPasswordColumn($pdo)) {
            $loginError = isDevAuthEnabled()
                ? 'Password login is disabled locally until SSO or password columns are configured. Use Dev Quick-Login below.'
                : 'Password authentication is not configured. Contact the system administrator.';
        } else {
            $user = fetchUserByEmail($pdo, strtolower(trim($email)));

            if ($user === null || !password_verify($password, $user['password'] ?? '')) {
                $loginError = 'Invalid email or password. Please try again.';
            } else {
                loginUser($user);
                redirect('router.php');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — DWU PDMIS</title>
    <link rel="stylesheet" href="css/site-footer.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        dwu: { green: '#006633', dark: '#004d26', gold: '#FFCC00' }
                    }
                }
            }
        }
    </script>
</head>
<body class="flex min-h-screen flex-col bg-gradient-to-br from-dwu-dark via-dwu-green to-emerald-800">
    <div class="flex flex-1 items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <div class="mb-8 text-center text-white">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-emerald-100">Divine Word University</p>
                <h1 class="mt-2 text-3xl font-bold">PDMIS Portal</h1>
                <p class="mt-2 text-sm text-emerald-100">Partnership Database Management Information System</p>
            </div>

            <div class="rounded-2xl bg-white p-8 shadow-2xl ring-1 ring-black/5">
                <h2 class="text-xl font-bold text-slate-900">Sign in to your account</h2>
                <p class="mt-1 text-sm text-slate-500">Use your institutional email and password.</p>

                <?php if ($error !== ''): ?>
                    <div class="mt-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                        <?= e($error) ?>
                    </div>
                <?php endif; ?>

                <?php if ($success !== null): ?>
                    <div class="mt-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        <?= e($success) ?>
                    </div>
                <?php endif; ?>

                <?php if ($loginError !== ''): ?>
                    <div class="mt-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                        <?= e($loginError) ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="index.php" class="mt-6 space-y-5" novalidate>
                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Email address</label>
                        <input type="email"
                               id="email"
                               name="email"
                               required
                               autocomplete="username"
                               value="<?= e(is_string($email ?? null) ? $email : '') ?>"
                               class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm shadow-sm focus:border-dwu-green focus:outline-none focus:ring-2 focus:ring-dwu-green/20"
                               placeholder="name@dwu.ac.pg">
                    </div>

                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">Password</label>
                        <input type="password"
                               id="password"
                               name="password"
                               required
                               autocomplete="current-password"
                               class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm shadow-sm focus:border-dwu-green focus:outline-none focus:ring-2 focus:ring-dwu-green/20"
                               placeholder="Enter your password">
                    </div>

                    <button type="submit"
                            class="w-full rounded-lg bg-dwu-green px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-dwu-dark focus:outline-none focus:ring-2 focus:ring-dwu-green focus:ring-offset-2">
                        Sign In
                    </button>
                </form>

                <?php require __DIR__ . '/includes/dev-quick-login.php'; ?>

                <?php if (!isDevAuthEnabled()): ?>
                <div class="mt-6 rounded-lg bg-slate-50 p-4 text-xs text-slate-500">
                    <p class="font-semibold text-slate-700">Demo credentials</p>
                    <ul class="mt-2 space-y-1">
                        <li>Director: agnes.kula@dwu.ac.pg / director123</li>
                        <li>President: president@dwu.ac.pg / president123</li>
                        <li>Executive: jmete@dwu.ac.pg / executive123</li>
                        <li>Campus Admin: asanki@dwu.ac.pg / campus123</li>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php renderSiteFooter(); ?>
</body>
</html>
