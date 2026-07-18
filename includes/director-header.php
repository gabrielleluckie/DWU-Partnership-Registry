<?php

/**
 * Shared institutional dashboard header — DWU green banner with logo, messaging & notifications.
 *
 * Expected variables (set by renderInstitutionalDashboardHeader):
 * - $user (array) current user profile
 * - $notificationCount (int)
 * - $notifications (array)
 * - $messageRecipients (array)
 * - $pageTitle (string)
 * - $notificationsHeading (string)
 * - $messagePlaceholder (string)
 */

if (!isset($user) || !is_array($user)) {
    throw new RuntimeException('Director header requires a valid $user array.');
}

$notificationCount = $notificationCount ?? 0;
$notifications = $notifications ?? [];
$messageRecipients = $messageRecipients ?? [];
$pageTitle = $pageTitle ?? 'Partnership Director Dashboard';
$notificationsHeading = $notificationsHeading ?? 'Campus Proposal Notifications';
$messagePlaceholder = $messagePlaceholder ?? 'Type your message to Campus Admins, President, or Executive Officer...';
$logoUrl = assetUrl('assets/images/dwu_logo.jpg');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — DWU PDMIS</title>
    <link rel="stylesheet" href="<?= e(assetUrl('css/site-footer.css')) ?>">
    <link rel="stylesheet" href="<?= e(assetUrl('css/director-header.css')) ?>">
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
        };
    </script>
</head>
<body class="flex min-h-screen flex-col bg-slate-50 text-slate-800 antialiased">
<header class="director-site-header flex items-center justify-between px-6">
    <div class="director-header-brand flex min-w-0 items-center gap-3">
        <img class="director-header-logo h-16 w-auto shrink-0 object-contain sm:h-20"
             src="<?= e($logoUrl) ?>"
             alt="Divine Word University">
        <div class="director-header-brand-text min-w-0">
            <div class="director-header-slogan">Serving the Nation with Quality Education Over 30 Years</div>
            <div class="director-header-title">Partnership Registry</div>
        </div>
    </div>

    <div class="director-header-actions ml-auto flex shrink-0 items-center gap-4">
        <!-- Message -->
        <div class="relative" id="directorMessageMenu">
            <button class="relative rounded-md p-2 text-white transition hover:bg-white/10"
                    type="button"
                    id="directorMessageBtn"
                    title="Send Message"
                    aria-label="Send Message"
                    aria-expanded="false"
                    aria-haspopup="true">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"></path>
                </svg>
            </button>
            <div class="director-dropdown-panel wide" id="directorMessagePanel" role="dialog" aria-labelledby="directorMessageHeading">
                <div class="director-dropdown-header" id="directorMessageHeading">Send Message</div>
                <div class="director-dropdown-body">
                    <form id="directorMessageForm" onsubmit="return submitDirectorMessage(event)">
                        <label class="director-form-label" for="messageRecipient">Recipient</label>
                        <select id="messageRecipient" name="recipient" class="director-form-select" required>
                            <option value="">Select recipient...</option>
                            <?php foreach ($messageRecipients as $recipient): ?>
                                <option value="<?= e($recipient['email']) ?>">
                                    <?= e($recipient['name']) ?> — <?= e($recipient['role']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label class="director-form-label" for="messageBody">Message</label>
                        <textarea id="messageBody"
                                  name="message"
                                  class="director-form-textarea"
                                  placeholder="<?= e($messagePlaceholder) ?>"
                                  required></textarea>
                    </form>
                </div>
                <div class="director-dropdown-footer">
                    <button type="submit" form="directorMessageForm" class="director-btn-primary">Send Message</button>
                </div>
            </div>
        </div>

        <!-- Notifications -->
        <div class="relative" id="directorNotificationMenu">
            <button class="relative rounded-md p-2 text-white transition hover:bg-white/10"
                    type="button"
                    id="directorNotificationBtn"
                    title="Notifications"
                    aria-label="Notifications"
                    aria-expanded="false"
                    aria-haspopup="true">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 01-3.46 0"></path>
                </svg>
                <span class="absolute -right-0.5 -top-0.5 flex h-[18px] min-w-[18px] items-center justify-center rounded-full border-2 border-[#00684d] bg-red-600 px-1 text-[10px] font-bold leading-none text-white">
                    <?= (int) $notificationCount ?>
                </span>
            </button>
            <div class="director-dropdown-panel wide" id="directorNotificationPanel" role="menu">
                <div class="director-dropdown-header"><?= e($notificationsHeading) ?></div>
                <div class="director-dropdown-body">
                    <?php if ($notifications === []): ?>
                        <p class="text-sm text-slate-500">No new proposal submissions.</p>
                    <?php else: ?>
                        <?php foreach ($notifications as $notice): ?>
                            <div class="director-notification-item">
                                <strong><?= e($notice['title']) ?></strong>
                                <span><?= e($notice['detail']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- User menu -->
        <div class="director-user-menu" id="directorUserMenu">
            <button class="director-user-trigger"
                    type="button"
                    id="directorUserBtn"
                    aria-expanded="false"
                    aria-haspopup="true">
                <span class="director-user-name"><?= e($user['display_name'] ?? strtoupper($user['name'])) ?></span>
                <svg class="director-user-chevron" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M7 10l5 5 5-5z"></path>
                </svg>
                <img class="director-user-avatar h-12 w-12 shrink-0 object-cover sm:h-[52px] sm:w-[52px]"
                     src="<?= e($user['avatar']) ?>"
                     alt="<?= e($user['name']) ?>">
            </button>
            <div class="director-dropdown-panel" id="directorUserPanel" role="menu">
                <div class="director-user-dropdown-header">
                    <img class="director-user-avatar" src="<?= e($user['avatar']) ?>" alt="">
                    <div>
                        <h3><?= e($user['name']) ?></h3>
                        <p><?= e($user['role']) ?></p>
                    </div>
                </div>
                <div class="director-dropdown-body">
                    <div class="director-detail-row">
                        <span class="director-detail-label">Staff ID</span>
                        <span class="director-detail-value"><?= e($user['staff_id']) ?></span>
                    </div>
                    <div class="director-detail-row">
                        <span class="director-detail-label">Email</span>
                        <span class="director-detail-value"><?= e($user['email']) ?></span>
                    </div>
                    <div class="director-detail-row">
                        <span class="director-detail-label">Campus</span>
                        <span class="director-detail-value"><?= e($user['campus']) ?></span>
                    </div>
                </div>
                <div class="director-dropdown-footer">
                    <a href="logout.php" class="director-signout-link">Sign Out</a>
                </div>
            </div>
        </div>
    </div>
</header>

<main class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
