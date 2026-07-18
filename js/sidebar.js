const SIDEBAR_KEY = 'pdmis-sidebar-state';

function applySidebarState() {
    let state = localStorage.getItem(SIDEBAR_KEY);
    if (!state) {
        state = window.innerWidth <= 900 ? 'closed' : 'open';
    }
    document.body.classList.remove('sidebar-closed', 'sidebar-minimized');
    if (state === 'closed') {
        document.body.classList.add('sidebar-closed');
    } else if (state === 'minimized') {
        document.body.classList.add('sidebar-minimized');
    }
    updateSidebarToggle();
}

function setSidebarState(state) {
    localStorage.setItem(SIDEBAR_KEY, state);
    applySidebarState();
}

function updateSidebarToggle() {
    const toggle = document.getElementById('sidebarToggle');
    if (!toggle) return;
    const isClosed = document.body.classList.contains('sidebar-closed');
    toggle.setAttribute('aria-expanded', isClosed ? 'false' : 'true');
    toggle.title = isClosed ? 'Show navigation' : 'Hide navigation';
}

function toggleSidebar() {
    if (document.body.classList.contains('sidebar-closed')) {
        const prev = localStorage.getItem(SIDEBAR_KEY + '-prev') || 'open';
        setSidebarState(prev === 'closed' ? 'open' : prev);
    } else {
        const current = document.body.classList.contains('sidebar-minimized') ? 'minimized' : 'open';
        localStorage.setItem(SIDEBAR_KEY + '-prev', current);
        setSidebarState('closed');
    }
}

function closeSidebar() {
    setSidebarState('closed');
}

function toggleSidebarMinimize() {
    const isMinimized = document.body.classList.contains('sidebar-minimized');
    setSidebarState(isMinimized ? 'open' : 'minimized');
}

function toggleUserMenu(event) {
    event.stopPropagation();
    const menu = document.getElementById('userMenu');
    const isOpen = menu.classList.toggle('open');
    menu.querySelector('.user-menu-trigger').setAttribute('aria-expanded', isOpen ? 'true' : 'false');
}

document.addEventListener('click', function (event) {
    const menu = document.getElementById('userMenu');
    if (menu && !menu.contains(event.target)) {
        menu.classList.remove('open');
        menu.querySelector('.user-menu-trigger').setAttribute('aria-expanded', 'false');
    }
});

window.addEventListener('resize', function () {
    if (window.innerWidth > 900 && document.body.classList.contains('sidebar-closed')) {
        const state = localStorage.getItem(SIDEBAR_KEY);
        if (state === 'closed') {
            const backdrop = document.getElementById('sidebarBackdrop');
            if (backdrop) {
                backdrop.style.display = 'none';
            }
        }
    }
});

applySidebarState();
