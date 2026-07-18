<?php

if (!function_exists('requireAuth')) {
    require_once __DIR__ . '/guard.php';
}

$activePage = $activePage ?? 'home';
$headerTitle = $headerTitle ?? 'PDMIS';
$pageTitle = $pageTitle ?? 'DWU PDMIS';
$navItems = $navItems ?? [];
$pageStylesheets = $pageStylesheets ?? [];
$pageScripts = $pageScripts ?? [];

if (!isset($loggedInUser) || !is_array($loggedInUser)) {
    $loggedInUser = requireAuth($pdo);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(assetUrl('css/main.css')) ?>">
    <link rel="stylesheet" href="<?= e(assetUrl('css/site-footer.css')) ?>">
<?php foreach ($pageStylesheets as $stylesheet): ?>
    <link rel="stylesheet" href="<?= e(assetUrl($stylesheet)) ?>">
<?php endforeach; ?>
    <style>
        :root {
            --dwu-green: #006633;
            --dwu-green-dark: #004d26;
            --dwu-header-green: #00684d;
            --dwu-header-blue: #3366cc;
            --dwu-yellow: #FFCC00;
            --dwu-user-blue: #5dade2;
            --bg-light: #f8f9fa;
            --text-main: #2b2b2b;
            --border-color: #dee2e6;
            --header-height: 80px;
            --sidebar-width: 260px;
            --sidebar-mini-width: 72px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: system-ui, -apple-system, sans-serif; }
        body { background-color: var(--bg-light); color: var(--text-main); min-height: 100vh; display: flex; flex-direction: column; }

        .site-header {
            position: fixed; top: 0; left: 0; right: 0; height: var(--header-height); z-index: 300;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 24px 0 12px;
            background-color: var(--dwu-header-green);
            background-image:
                radial-gradient(circle at 85% 20%, rgba(255, 255, 255, 0.06) 0%, transparent 35%),
                radial-gradient(circle at 15% 80%, rgba(0, 0, 0, 0.08) 0%, transparent 40%);
            border-top: 3px solid var(--dwu-header-blue);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .header-brand {
            display: flex; align-items: center; gap: 12px; min-width: 0;
        }
        .header-logo { height: 68px; width: auto; display: block; flex-shrink: 0; }
        .header-brand-text { color: white; min-width: 0; }
        .header-slogan {
            font-size: 11px; font-weight: 700; letter-spacing: 0.4px; line-height: 1.3;
            text-transform: uppercase; white-space: nowrap;
        }
        .header-campus {
            font-family: 'Dancing Script', cursive; font-size: 28px; line-height: 1.1;
            margin-top: 2px; white-space: nowrap;
        }

        .header-actions { display: flex; align-items: center; gap: 18px; flex-shrink: 0; }
        .header-icon-btn {
            background: none; border: none; color: rgba(255, 255, 255, 0.65); cursor: pointer;
            padding: 4px; display: flex; align-items: center; justify-content: center;
            transition: color 0.15s ease;
        }
        .header-icon-btn:hover { color: white; }
        .header-icon-btn svg { width: 20px; height: 20px; }

        .user-menu { position: relative; }
        .user-menu-trigger {
            display: flex; align-items: center; gap: 10px; background: none; border: none;
            cursor: pointer; padding: 0;
        }
        .user-menu-name {
            color: var(--dwu-user-blue); font-size: 14px; font-weight: 700;
            letter-spacing: 0.3px; white-space: nowrap;
        }
        .user-menu-chevron {
            width: 12px; height: 12px; color: rgba(255, 255, 255, 0.75);
            transition: transform 0.15s ease;
        }
        .user-menu.open .user-menu-chevron { transform: rotate(180deg); }
        .user-avatar {
            width: 52px; height: 52px; object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.85); background: var(--dwu-green);
        }

        .user-menu-dropdown {
            display: none; position: absolute; top: calc(100% + 12px); right: 0; width: 300px;
            background: white; border: 1px solid var(--border-color); border-radius: 8px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15); overflow: hidden; z-index: 400;
        }
        .user-menu.open .user-menu-dropdown { display: block; animation: dropdownFade 0.15s ease; }
        @keyframes dropdownFade {
            from { opacity: 0; transform: translateY(-4px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .user-dropdown-header {
            display: flex; align-items: center; gap: 12px; padding: 16px;
            background: linear-gradient(135deg, #f8fbf9 0%, #eef6f1 100%);
            border-bottom: 1px solid var(--border-color);
        }
        .user-dropdown-header .user-avatar { width: 48px; height: 48px; border-color: var(--dwu-yellow); }
        .user-dropdown-header h3 { font-size: 14px; font-weight: 600; color: var(--dwu-green-dark); }
        .user-dropdown-header p { font-size: 12px; color: #6c757d; margin-top: 2px; }
        .user-dropdown-body { padding: 12px 16px; }
        .user-detail-row {
            display: flex; justify-content: space-between; gap: 12px;
            padding: 8px 0; border-bottom: 1px solid #f1f3f5; font-size: 12px;
        }
        .user-detail-row:last-child { border-bottom: none; }
        .user-detail-label { color: #6c757d; font-weight: 500; }
        .user-detail-value { color: var(--text-main); font-weight: 600; text-align: right; }
        .user-dropdown-footer {
            padding: 12px 16px; border-top: 1px solid var(--border-color); background: #fafafa;
        }
        .user-dropdown-footer .btn { width: 100%; text-align: center; }

        .app-shell {
            margin-top: var(--header-height);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: calc(100vh - var(--header-height));
        }
        .workspace {
            display: flex;
            flex: 1;
            position: relative;
            min-height: 0;
        }

        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--dwu-green-dark);
            color: white;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            position: sticky;
            top: var(--header-height);
            height: calc(100vh - var(--header-height));
            transition: width 0.25s ease;
            overflow: hidden;
            z-index: 120;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.08);
        }
        .sidebar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            min-height: 64px;
        }
        .sidebar-brand {
            font-size: 16px;
            font-weight: 700;
            color: rgba(255,255,255,0.9);
            white-space: nowrap;
            overflow: hidden;
        }
        .sidebar-brand span { color: var(--dwu-yellow); }
        .sidebar-controls { display: flex; gap: 4px; flex-shrink: 0; }
        .sidebar-ctrl-btn {
            background: rgba(255,255,255,0.08);
            border: none;
            color: rgba(255,255,255,0.8);
            width: 32px;
            height: 32px;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.15s ease, color 0.15s ease;
        }
        .sidebar-ctrl-btn:hover { background: rgba(255,255,255,0.16); color: white; }
        .sidebar-ctrl-btn svg { width: 16px; height: 16px; }

        .sidebar-menu { list-style: none; padding: 12px 0; flex: 1; overflow-y: auto; }
        .sidebar-item a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 18px;
            color: rgba(255,255,255,0.75);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.15s ease;
            white-space: nowrap;
        }
        .sidebar-icon {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
            opacity: 0.85;
        }
        .sidebar-label { overflow: hidden; text-overflow: ellipsis; }
        .sidebar-item.active a, .sidebar-item a:hover {
            color: white;
            background-color: rgba(255,255,255,0.1);
            border-left: 4px solid var(--dwu-yellow);
            padding-left: 14px;
        }

        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: var(--header-height) 0 0 0;
            background: rgba(0, 0, 0, 0.45);
            z-index: 110;
        }

        body.sidebar-minimized .sidebar { width: var(--sidebar-mini-width); }
        body.sidebar-minimized .sidebar-brand,
        body.sidebar-minimized .sidebar-label { display: none; }
        body.sidebar-minimized .sidebar-header { justify-content: center; padding: 18px 8px; }
        body.sidebar-minimized .sidebar-btn-minimize svg { transform: rotate(180deg); }
        body.sidebar-minimized .sidebar-btn-close { display: none; }
        body.sidebar-minimized .sidebar-item a { justify-content: center; padding: 14px 8px; }
        body.sidebar-minimized .sidebar-item.active a,
        body.sidebar-minimized .sidebar-item a:hover { padding-left: 8px; border-left: none; border-right: 4px solid var(--dwu-yellow); }

        body.sidebar-closed .sidebar {
            width: 0;
            box-shadow: none;
        }
        body.sidebar-closed .sidebar-backdrop { display: none !important; }

        .content-column {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .main-wrapper { flex: 1; padding: 24px 28px 32px; width: 100%; }
        .page-topbar {
            background: white;
            padding: 14px 20px;
            margin: -24px -28px 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }
        .sidebar-toggle-btn {
            background: #f1f3f5;
            border: 1px solid var(--border-color);
            color: var(--dwu-green-dark);
            width: 38px;
            height: 38px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: background 0.15s ease, border-color 0.15s ease;
        }
        .sidebar-toggle-btn:hover { background: #e6f2ec; border-color: #b8d4c4; }
        .sidebar-toggle-btn svg { width: 18px; height: 18px; }
        .page-topbar h2 {
            font-size: 18px;
            font-weight: 600;
            color: var(--dwu-green);
            line-height: 1.3;
            min-width: 0;
        }
        .role { background-color: #e6f2ec; color: var(--dwu-green); padding: 3px 8px; border-radius: 4px; font-weight: 600; font-size: 12px; }

        .row { display: flex; flex-wrap: wrap; margin: 0 -12px 24px; }
        .col-3 { flex: 0 0 25%; max-width: 25%; padding: 0 12px; margin-bottom: 12px; }
        .metric-card { background: white; border-radius: 8px; padding: 20px; border: 1px solid var(--border-color); position: relative; overflow: hidden; height: 100%; }
        .metric-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background-color: var(--dwu-green); }
        .metric-card.card-active::before { background-color: #198754; }
        .metric-card.card-soon::before { background-color: #ffc107; }
        .metric-card.card-expired::before { background-color: #dc3545; }
        .metric-label { font-size: 11px; text-transform: uppercase; color: #6c757d; font-weight: 600; margin-bottom: 5px; }
        .metric-value { font-size: 26px; font-weight: 700; }

        .custom-card { background: white; border: 1px solid var(--border-color); border-radius: 8px; margin-bottom: 24px; box-shadow: 0 1px 4px rgba(0,0,0,0.04); overflow: hidden; }
        .card-header { padding: 15px 20px; background-color: #fafafa; border-bottom: 1px solid var(--border-color); font-weight: 600; font-size: 14px; color: #495057; }
        .card-body { padding: 20px; }

        .filter-flex { display: flex; gap: 15px; flex-wrap: wrap; }
        .control-box { display: flex; flex-direction: column; gap: 5px; flex: 1; min-width: 180px; }
        .control-box label { font-size: 12px; font-weight: 600; color: #495057; }
        .control-box select { padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; font-size: 14px; }

        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { background-color: #f1f3f5; color: #495057; font-weight: 600; padding: 12px 20px; border-bottom: 2px solid var(--border-color); text-transform: uppercase; font-size: 11px; text-align: left; }
        td { padding: 12px 20px; border-bottom: 1px solid var(--border-color); text-align: left; }
        tr:hover td { background-color: #fdfdfd; }
        .badge { display: inline-block; padding: 4px 8px; font-size: 11px; font-weight: 600; border-radius: 4px; }
        .badge-active { background-color: #d1e7dd; color: #0f5132; }
        .badge-soon { background-color: #fff3cd; color: #664d03; }
        .badge-expired { background-color: #f8d7da; color: #842029; }

        .btn { padding: 6px 12px; border: none; border-radius: 4px; font-size: 12px; font-weight: 600; cursor: pointer; display: inline-block; }
        .btn-success { background-color: #198754; color: white; }
        .btn-danger { background-color: #dc3545; color: white; }
        .btn-outline { border: 1px solid #ced4da; background: transparent; color: #495057; }
        .btn-outline:hover { background: #f1f3f5; }
        .token-text { font-family: monospace; background: #f1f3f5; padding: 4px 8px; border-radius: 4px; font-size: 12px; max-width: 240px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .empty-notice { text-align: center; padding: 30px; color: #777; font-style: italic; display: none; }

        .table-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; }

        @media (max-width: 1100px) {
            .col-3 { flex: 0 0 50%; max-width: 50%; }
        }

        @media (max-width: 900px) {
            .header-slogan { font-size: 9px; }
            .header-campus { font-size: 22px; }
            .header-brand { gap: 8px; }
            .header-logo { height: 54px; }
            .user-menu-name { display: none; }
            .main-wrapper { padding: 16px; }
            .page-topbar { margin: -16px -16px 20px; padding: 12px 16px; }
            .col-3 { flex: 0 0 100%; max-width: 100%; }

            .sidebar {
                position: fixed;
                left: 0;
                top: var(--header-height);
                height: calc(100vh - var(--header-height));
                transform: translateX(0);
                transition: transform 0.25s ease, width 0.25s ease;
            }
            body.sidebar-closed .sidebar { transform: translateX(-100%); width: var(--sidebar-width); }
            body:not(.sidebar-closed) .sidebar-backdrop { display: block; }
            body.sidebar-minimized .sidebar { width: var(--sidebar-width); }
            body.sidebar-minimized .sidebar-brand,
            body.sidebar-minimized .sidebar-label { display: block; }
            body.sidebar-minimized .sidebar-btn-minimize { display: flex; }
            body.sidebar-minimized .sidebar-header { justify-content: space-between; padding: 18px 16px; }
            body.sidebar-minimized .sidebar-item a { justify-content: flex-start; padding: 12px 18px; }
            body.sidebar-minimized .sidebar-item.active a,
            body.sidebar-minimized .sidebar-item a:hover { border-right: none; border-left: 4px solid var(--dwu-yellow); padding-left: 14px; }
        }
    </style>
</head>
<body>

    <header class="site-header">
        <div class="header-brand">
            <img class="header-logo" src="<?= e(assetUrl('assets/images/dwu_logo.jpg')) ?>" alt="Divine Word University">
            <div class="header-brand-text">
                <div class="header-slogan">Serving the Nation with Quality Education Over 30 Years</div>
                <div class="header-campus">PDMIS Madang Campus</div>
            </div>
        </div>
        <div class="header-actions">
            <button class="header-icon-btn" type="button" title="Notifications" aria-label="Notifications">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 01-3.46 0"></path>
                </svg>
            </button>
            <button class="header-icon-btn" type="button" title="Messages" aria-label="Messages">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"></path>
                </svg>
            </button>
            <div class="user-menu" id="userMenu">
                <button class="user-menu-trigger" type="button" onclick="toggleUserMenu(event)" aria-expanded="false" aria-haspopup="true">
                    <span class="user-menu-name"><?= e($loggedInUser['display_name'] ?? $loggedInUser['name']) ?></span>
                    <svg class="user-menu-chevron" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M7 10l5 5 5-5z"></path>
                    </svg>
                    <img class="user-avatar" src="<?= e($loggedInUser['avatar']) ?>" alt="<?= e($loggedInUser['name']) ?>">
                </button>
                <div class="user-menu-dropdown" role="menu">
                    <div class="user-dropdown-header">
                        <img class="user-avatar" src="<?= e($loggedInUser['avatar']) ?>" alt="">
                        <div>
                            <h3><?= e($loggedInUser['name']) ?></h3>
                            <p><?= e($loggedInUser['role']) ?></p>
                        </div>
                    </div>
                    <div class="user-dropdown-body">
                        <div class="user-detail-row">
                            <span class="user-detail-label">Staff ID</span>
                            <span class="user-detail-value"><?= e($loggedInUser['staff_id']) ?></span>
                        </div>
                        <div class="user-detail-row">
                            <span class="user-detail-label">Email</span>
                            <span class="user-detail-value"><?= e($loggedInUser['email']) ?></span>
                        </div>
                        <div class="user-detail-row">
                            <span class="user-detail-label">Campus</span>
                            <span class="user-detail-value"><?= e($loggedInUser['campus']) ?></span>
                        </div>
                        <div class="user-detail-row">
                            <span class="user-detail-label">Department</span>
                            <span class="user-detail-value"><?= e($loggedInUser['department']) ?></span>
                        </div>
                        <div class="user-detail-row">
                            <span class="user-detail-label">Last Login</span>
                            <span class="user-detail-value"><?= e($loggedInUser['last_login']) ?></span>
                        </div>
                    </div>
                    <div class="user-dropdown-footer">
                        <a href="logout.php" class="btn btn-outline" style="display:block;text-align:center;text-decoration:none;">Sign Out</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="app-shell">
        <div class="workspace">
            <nav class="sidebar" id="sidebar" aria-label="Main navigation">
                <div class="sidebar-header">
                    <div class="sidebar-brand">DWU <span>PDMIS</span></div>
                    <div class="sidebar-controls">
                        <button class="sidebar-ctrl-btn sidebar-btn-minimize" type="button" onclick="toggleSidebarMinimize()" title="Minimize sidebar" aria-label="Minimize sidebar">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
                        </button>
                        <button class="sidebar-ctrl-btn sidebar-btn-close" type="button" onclick="closeSidebar()" title="Close sidebar" aria-label="Close sidebar">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <ul class="sidebar-menu">
                    <?php foreach ($navItems as $key => $item): ?>
                    <li class="sidebar-item<?= $activePage === $key ? ' active' : '' ?>">
                        <a href="<?= e($item['href']) ?>" title="<?= e($item['label']) ?>">
                            <?= $item['icon'] ?>
                            <span class="sidebar-label"><?= e($item['label']) ?></span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
            <div class="sidebar-backdrop" id="sidebarBackdrop" onclick="closeSidebar()" aria-hidden="true"></div>

            <div class="content-column">
                <main class="main-wrapper">
                    <div class="page-topbar">
                        <button class="sidebar-toggle-btn" id="sidebarToggle" type="button" onclick="toggleSidebar()" title="Toggle navigation" aria-label="Toggle navigation" aria-expanded="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 12h18M3 6h18M3 18h18"/>
                            </svg>
                        </button>
                        <h2><?= e($headerTitle) ?></h2>
                    </div>
