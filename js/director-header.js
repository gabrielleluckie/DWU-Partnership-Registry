function closeDirectorPanels() {
    const panels = [
        { panel: 'directorNotificationPanel', btn: 'directorNotificationBtn', menu: 'directorNotificationMenu' },
        { panel: 'directorMessagePanel', btn: 'directorMessageBtn', menu: 'directorMessageMenu' },
        { panel: 'directorUserPanel', btn: 'directorUserBtn', menu: 'directorUserMenu' },
    ];

    panels.forEach(({ panel, btn, menu }) => {
        const panelEl = document.getElementById(panel);
        const btnEl = document.getElementById(btn);
        const menuEl = document.getElementById(menu);

        if (panelEl) panelEl.classList.remove('open');
        if (menuEl) menuEl.classList.remove('open');
        if (btnEl) btnEl.setAttribute('aria-expanded', 'false');
    });
}

function toggleDirectorPanel(panelId, btnId, menuId) {
    const panel = document.getElementById(panelId);
    const btn = document.getElementById(btnId);
    const menu = document.getElementById(menuId);

    if (!panel || !btn) return;

    const willOpen = !panel.classList.contains('open');
    closeDirectorPanels();

    if (willOpen) {
        panel.classList.add('open');
        if (menu) menu.classList.add('open');
        btn.setAttribute('aria-expanded', 'true');
    }
}

document.getElementById('directorNotificationBtn')?.addEventListener('click', function (event) {
    event.stopPropagation();
    toggleDirectorPanel('directorNotificationPanel', 'directorNotificationBtn', 'directorNotificationMenu');
});

document.getElementById('directorMessageBtn')?.addEventListener('click', function (event) {
    event.stopPropagation();
    toggleDirectorPanel('directorMessagePanel', 'directorMessageBtn', 'directorMessageMenu');
});

document.getElementById('directorUserBtn')?.addEventListener('click', function (event) {
    event.stopPropagation();
    toggleDirectorPanel('directorUserPanel', 'directorUserBtn', 'directorUserMenu');
});

document.addEventListener('click', closeDirectorPanels);

document.querySelectorAll('.director-dropdown-panel').forEach(function (panel) {
    panel.addEventListener('click', function (event) {
        event.stopPropagation();
    });
});

document.querySelectorAll('[data-profile-photo-trigger]').forEach(function (button) {
    button.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();

        const inputId = button.getAttribute('data-profile-photo-trigger');
        const input = inputId ? document.getElementById(inputId) : null;

        if (input) {
            input.click();
        }
    });
});

document.querySelectorAll('[data-profile-photo-input]').forEach(function (input) {
    input.addEventListener('change', function () {
        if (!input.files || input.files.length === 0 || !input.form) {
            return;
        }

        var maxBytes = 5 * 1024 * 1024;
        if (input.files[0].size > maxBytes) {
            alert('Photo must be 5 MB or smaller.');
            input.value = '';
            return;
        }

        input.form.submit();
    });
});

function submitDirectorMessage(event) {
    event.preventDefault();

    const recipient = document.getElementById('messageRecipient');
    const body = document.getElementById('messageBody');

    if (!recipient || !body) return false;

    const recipientLabel = recipient.options[recipient.selectedIndex]?.text || 'selected recipient';

    alert(
        'Message queued for delivery to:\n' +
        recipientLabel +
        '\n\n' +
        body.value.trim()
    );

    body.value = '';
    recipient.selectedIndex = 0;
    closeDirectorPanels();

    return false;
}
