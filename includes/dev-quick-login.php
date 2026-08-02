<?php

/**
 * Dev Quick-Login panel — included on index.php when APP_ENV=local.
 *
 * Expected: devQuickLoginAccounts(), dashboardForRole(), e()
 */

if (!isDevAuthEnabled()) {
    return;
}

$devAccounts = devQuickLoginAccounts();
?>
<div class="mt-6 rounded-lg border-2 border-dashed border-amber-300 bg-amber-50 p-4">
    <div class="mb-3 flex items-start gap-2">
        <span class="inline-flex rounded bg-amber-200 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-900">
            Dev only
        </span>
        <div>
            <p class="text-sm font-semibold text-amber-950">Quick-Login (local development)</p>
            <p class="mt-0.5 text-xs text-amber-800">
                Microsoft Entra ID is not connected. Sign in instantly as a seeded test user.
            </p>
        </div>
    </div>

    <div class="grid gap-2">
        <?php foreach ($devAccounts as $account): ?>
            <form method="post" action="index.php" class="m-0">
                <input type="hidden" name="dev_quick_login" value="1">
                <input type="hidden" name="email" value="<?= e($account['email']) ?>">
                <button type="submit"
                        class="flex w-full items-center justify-between rounded-lg border border-amber-200 bg-white px-3 py-2 text-left text-sm transition hover:border-dwu-green hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-dwu-green/30">
                    <span>
                        <span class="font-semibold text-slate-800"><?= e($account['label']) ?></span>
                        <span class="mt-0.5 block text-xs text-slate-500"><?= e($account['email']) ?></span>
                    </span>
                    <span class="shrink-0 text-xs font-medium text-dwu-green">Sign in →</span>
                </button>
            </form>
        <?php endforeach; ?>
    </div>

    <p class="mt-3 text-[11px] leading-relaxed text-amber-700">
        Requires seeded users in the database.
        Run <code class="rounded bg-amber-100 px-1">scripts/seed_dev_users.sql</code> if quick-login fails.
    </p>
</div>
