<?php

/**
 * Shared institutional dashboard header — Bootstrap 5 navbar (application-wide).
 *
 * Expected variables (set by renderInstitutionalDashboardHeader):
 * - $user, $notificationCount, $notifications, $messageRecipients
 * - $pageTitle, $pageSubtitle (optional), $notificationsHeading, $messagePlaceholder
 * - $bodyClass, $extraStylesheets
 */

if (!isset($user) || !is_array($user)) {
    throw new RuntimeException('Director header requires a valid $user array.');
}

$notificationCount = $notificationCount ?? 0;
$notifications = $notifications ?? [];
$messageRecipients = $messageRecipients ?? [];
$pageTitle = $pageTitle ?? 'Partnership Director Dashboard';
$pageSubtitle = $pageSubtitle ?? '';
$notificationsHeading = $notificationsHeading ?? 'Campus Proposal Notifications';
$messagePlaceholder = $messagePlaceholder ?? 'Type your message to Campus Admins, President, or Executive Officer...';
$logoUrl = assetUrl('assets/images/dwu_logo.jpg');
$bodyClass = $bodyClass ?? 'app-shell';
$extraStylesheets = $extraStylesheets ?? [];

$showNotificationBar = $notifications !== [];
$primaryNotice = $notifications[0] ?? null;
$logoutUrl = logoutRoute();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — DWU PDMIS</title>
    <link rel="stylesheet" href="<?= e(assetUrl('css/site-footer.css')) ?>">
    <?php foreach ($extraStylesheets as $stylesheet): ?>
        <link rel="stylesheet" href="<?= e($stylesheet) ?>">
    <?php endforeach; ?>
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
        };
    </script>
    <?php if (str_contains((string) $bodyClass, 'campus-admin-theme')): ?>
        <?php
        $campusAdminCss = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'campus-admin-dashboard.css';
        $campusAdminCssVersion = is_file($campusAdminCss) ? (string) filemtime($campusAdminCss) : (string) time();
        ?>
        <link rel="stylesheet" href="<?= e(assetUrl('css/campus-admin-dashboard.css') . '?v=' . $campusAdminCssVersion) ?>">
    <?php endif; ?>
</head>
<body class="d-flex flex-column min-vh-100 <?= e($bodyClass) ?>">

<!-- Global application navbar -->
<header class="app-global-header sticky-top">
    <nav class="navbar navbar-expand-lg navbar-dark app-navbar shadow-sm">
        <div class="container-fluid px-3 px-lg-4 py-2">

            <!-- Brand -->
            <a class="navbar-brand app-navbar-brand d-flex align-items-center gap-3 me-lg-4"
               href="<?= e(dashboardForRole($user['role'] ?? '') ?? loginRoute()) ?>">
                <img src="<?= e($logoUrl) ?>"
                     alt="Divine Word University"
                     class="app-navbar-logo">
                <span class="app-brand-text d-none d-sm-block">
                    <span class="app-brand-slogan d-block">Serving the Nation with Quality Education Over 30 Years</span>
                    <span class="app-brand-title d-block">Partnership Registry</span>
                    <span class="app-brand-division d-block">Divine Word University — Partnership Division</span>
                </span>
            </a>

            <!-- Mobile toggle -->
            <button class="navbar-toggler app-navbar-toggler border-0 ms-auto"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#appNavbarCollapse"
                    aria-controls="appNavbarCollapse"
                    aria-expanded="false"
                    aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Collapsible actions -->
            <div class="collapse navbar-collapse" id="appNavbarCollapse">
                <div class="navbar-nav ms-lg-auto align-items-lg-center gap-lg-2 py-2 py-lg-0 w-100 w-lg-auto">

                    <!-- Page context (visible on mobile in collapse) -->
                    <div class="app-nav-page-context d-lg-none mb-3 pb-3 border-bottom border-secondary border-opacity-25">
                        <p class="small text-white fw-semibold mb-0"><?= e($pageTitle) ?></p>
                        <?php if ($pageSubtitle !== ''): ?>
                            <p class="small text-secondary mb-0"><?= e($pageSubtitle) ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Message -->
                    <div class="nav-item position-relative" id="directorMessageMenu">
                        <button type="button"
                                id="directorMessageBtn"
                                class="btn btn-link nav-link app-nav-action px-3"
                                title="Send Message"
                                aria-label="Send Message"
                                aria-expanded="false"
                                aria-haspopup="true">
                            <i class="bi bi-chat-dots fs-5"></i>
                            <span class="d-lg-none ms-2">Send Message</span>
                        </button>
                        <div class="director-dropdown-panel wide" id="directorMessagePanel" role="dialog" aria-labelledby="directorMessageHeading">
                            <div class="director-dropdown-header" id="directorMessageHeading">Send Message</div>
                            <div class="director-dropdown-body">
                                <form id="directorMessageForm" onsubmit="return submitDirectorMessage(event)">
                                    <label class="director-form-label" for="messageRecipient">Recipient</label>
                                    <select id="messageRecipient" name="recipient" class="director-form-select form-select form-select-sm" required>
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
                                              class="director-form-textarea form-control form-control-sm"
                                              placeholder="<?= e($messagePlaceholder) ?>"
                                              required></textarea>
                                </form>
                            </div>
                            <div class="director-dropdown-footer">
                                <button type="submit" form="directorMessageForm" class="director-btn-primary btn btn-success btn-sm w-100">Send Message</button>
                            </div>
                        </div>
                    </div>

                    <!-- Notifications -->
                    <div class="nav-item position-relative" id="directorNotificationMenu">
                        <button type="button"
                                id="directorNotificationBtn"
                                class="btn btn-link nav-link app-nav-action px-3 position-relative"
                                title="Notifications"
                                aria-label="Notifications"
                                aria-expanded="false"
                                aria-haspopup="true">
                            <i class="bi bi-bell fs-5"></i>
                            <?php if ($notificationCount > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger app-notify-badge">
                                    <?= (int) $notificationCount ?>
                                </span>
                            <?php endif; ?>
                            <span class="d-lg-none ms-2">Notifications</span>
                        </button>
                        <div class="director-dropdown-panel wide" id="directorNotificationPanel" role="menu">
                            <div class="director-dropdown-header"><?= e($notificationsHeading) ?></div>
                            <div class="director-dropdown-body">
                                <?php if ($notifications === []): ?>
                                    <p class="text-muted small mb-0">No new notifications.</p>
                                <?php else: ?>
                                    <?php foreach ($notifications as $notice): ?>
                                        <?php if (!empty($notice['href'])): ?>
                                            <a href="<?= e($notice['href']) ?>" class="director-notification-item director-notification-link">
                                                <strong><?= e($notice['title']) ?></strong>
                                                <span><?= e($notice['detail']) ?></span>
                                            </a>
                                        <?php else: ?>
                                            <div class="director-notification-item">
                                                <strong><?= e($notice['title']) ?></strong>
                                                <span><?= e($notice['detail']) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- User profile + logout -->
                    <div class="nav-item d-flex align-items-center gap-2 ms-lg-2 app-user-nav-cluster">
                        <div class="app-user-summary d-none d-lg-block text-end">
                            <span class="app-user-name d-block"><?= e($user['display_name'] ?? strtoupper($user['name'])) ?></span>
                            <span class="app-user-role d-block"><?= e($user['role']) ?></span>
                        </div>

                        <div class="position-relative" id="directorUserMenu">
                            <button type="button"
                                    id="directorUserBtn"
                                    class="btn btn-link nav-link app-nav-action app-user-trigger d-flex align-items-center gap-2 px-2 py-1"
                                    aria-expanded="false"
                                    aria-haspopup="true">
                                <img class="app-user-avatar rounded-circle"
                                     src="<?= e($user['avatar']) ?>"
                                     alt="<?= e($user['name']) ?>"
                                     width="40" height="40">
                                <i class="bi bi-chevron-down small opacity-75 d-none d-lg-inline"></i>
                            </button>
                            <div class="director-dropdown-panel" id="directorUserPanel" role="menu">
                                <div class="director-user-dropdown-header">
                                    <img class="director-user-avatar rounded-circle" src="<?= e($user['avatar']) ?>" alt="">
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
                                    <form method="post"
                                          action="<?= e(profilePhotoUploadAction()) ?>"
                                          enctype="multipart/form-data"
                                          class="mb-2">
                                        <input type="hidden" name="redirect_to" value="<?= e((string) ($_SERVER['REQUEST_URI'] ?? '')) ?>">
                                        <input type="file"
                                               id="headerProfilePhotoInput"
                                               name="profile_photo"
                                               class="d-none"
                                               accept="image/jpeg,image/png,image/webp,image/gif"
                                               data-profile-photo-input>
                                        <button type="button"
                                                class="btn btn-outline-light btn-sm w-100"
                                                data-profile-photo-trigger="headerProfilePhotoInput">
                                            <i class="bi bi-camera me-1"></i>
                                            <?= !empty($user['has_photo']) ? 'Change photo' : 'Add profile photo' ?>
                                        </button>
                                    </form>
                                    <a href="<?= e($logoutUrl) ?>" class="director-signout-link btn btn-danger btn-sm w-100">
                                        <i class="bi bi-box-arrow-right me-1"></i> Logout
                                    </a>
                                </div>
                            </div>
                        </div>

                        <a href="<?= e($logoutUrl) ?>"
                           class="btn btn-sm btn-warning fw-semibold app-logout-btn d-none d-lg-inline-flex align-items-center">
                            <i class="bi bi-box-arrow-right me-1"></i> Logout
                        </a>

                        <a href="<?= e($logoutUrl) ?>"
                           class="btn btn-sm btn-warning fw-semibold app-logout-btn d-lg-none w-100 mt-2">
                            <i class="bi bi-box-arrow-right me-1"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <?php if ($showNotificationBar && $primaryNotice !== null): ?>
        <div class="alert alert-dark app-notification-bar alert-dismissible fade show rounded-0 mb-0 border-0"
             role="alert"
             id="appNotificationBar">
            <div class="container-fluid px-3 px-lg-4 d-flex align-items-start gap-2">
                <?php if (!empty($primaryNotice['href']) && directorCurrentSection() !== 'review'): ?>
                    <a href="<?= e($primaryNotice['href']) ?>"
                       class="app-notification-bar-link flex-grow-1 d-flex align-items-start gap-2 text-decoration-none">
                        <i class="bi bi-bell-fill app-notification-icon flex-shrink-0" aria-hidden="true"></i>
                        <span class="flex-grow-1">
                            <strong class="d-block small"><?= e($primaryNotice['title']) ?></strong>
                            <span class="small opacity-75"><?= e($primaryNotice['detail']) ?></span>
                            <?php if ($notificationCount > 1): ?>
                                <span class="badge bg-warning text-dark ms-2"><?= (int) $notificationCount ?> total</span>
                            <?php endif; ?>
                        </span>
                    </a>
                <?php else: ?>
                    <i class="bi bi-bell-fill app-notification-icon flex-shrink-0" aria-hidden="true"></i>
                    <div class="flex-grow-1">
                        <strong class="d-block small"><?= e($primaryNotice['title']) ?></strong>
                        <span class="small opacity-75"><?= e($primaryNotice['detail']) ?></span>
                        <?php if ($notificationCount > 1): ?>
                            <span class="badge bg-warning text-dark ms-2"><?= (int) $notificationCount ?> total</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <button type="button"
                        class="btn-close btn-close-white"
                        data-bs-dismiss="alert"
                        aria-label="Dismiss notification"></button>
            </div>
        </div>
    <?php endif; ?>
</header>

<main class="container-fluid flex-grow-1 px-3 px-lg-4 py-2 py-lg-3 campus-admin-main" style="max-width:96rem;">
