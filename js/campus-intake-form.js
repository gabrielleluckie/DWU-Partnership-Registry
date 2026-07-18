(function () {
    'use strict';

    const form = document.getElementById('campusIntakeForm');
    if (!form) return;

    const draftBtn = form.querySelector('button[value="save_draft"]');
    const submitBtn = form.querySelector('button[value="submit_proposal"]');

    if (draftBtn) {
        draftBtn.addEventListener('click', function () {
            form.noValidate = true;
        });
    }

    if (submitBtn) {
        submitBtn.addEventListener('click', function () {
            form.noValidate = false;
        });
    }

    function selectedValue(name) {
        const checked = form.querySelector('input[name="' + name + '"]:checked');
        return checked ? checked.value : '';
    }

    function toggleBlock(id, show) {
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.toggle('hidden', !show);
    }

    function toggleDwuSpendFields() {
        const isYes = selectedValue('dwu_financial_spend') === 'Yes';
        toggleBlock('dwuSpendFields', isYes);

        const dept = document.getElementById('dwu_responsible_department');
        if (dept) {
            dept.required = isYes;
        }
    }

    function togglePartnerFundingFields() {
        toggleBlock('partnerFundingFields', selectedValue('partner_funding') === 'Yes');
    }

    function toggleSimilarPartnershipFields() {
        toggleBlock('similarPartnershipWrap', selectedValue('similar_partnerships') === 'Yes');
    }

    function toggleOrgTypeOther() {
        const other = form.querySelector('input[name="org_types[]"][value="Other"]');
        toggleBlock('orgTypeOtherWrap', !!(other && other.checked));
    }

    function bindRadioToggle(selector, handler) {
        form.querySelectorAll(selector).forEach(function (input) {
            input.addEventListener('change', handler);
        });
    }

    bindRadioToggle('[data-dwu-spend-toggle]', toggleDwuSpendFields);
    bindRadioToggle('[data-partner-funding-toggle]', togglePartnerFundingFields);
    bindRadioToggle('[data-similar-toggle]', toggleSimilarPartnershipFields);

    form.querySelectorAll('input[name="org_types[]"]').forEach(function (input) {
        input.addEventListener('change', toggleOrgTypeOther);
    });

    form.querySelectorAll('.doc-toggle').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            const targetId = checkbox.getAttribute('data-doc-target') + 'Upload';
            toggleBlock(targetId, checkbox.checked);

            if (!checkbox.checked) {
                const upload = document.getElementById(targetId);
                const fileInput = upload ? upload.querySelector('input[type="file"]') : null;
                const filename = upload ? upload.querySelector('.doc-filename') : null;
                if (fileInput) fileInput.value = '';
                if (filename) filename.textContent = '';
            }
        });
    });

    form.querySelectorAll('.doc-upload input[type="file"]').forEach(function (input) {
        input.addEventListener('change', function () {
            const wrap = input.closest('.doc-upload');
            const filename = wrap ? wrap.querySelector('.doc-filename') : null;
            if (!filename) return;

            if (input.files && input.files.length > 0) {
                filename.textContent = 'Selected: ' + input.files[0].name;
            } else {
                filename.textContent = '';
            }
        });
    });

    const startDate = document.getElementById('proposed_start_date');
    const endDate = document.getElementById('proposed_end_date');
    const duration = document.getElementById('total_duration');

    function updateDurationHint() {
        if (!startDate || !endDate || !duration || !startDate.value || !endDate.value) return;

        const start = new Date(startDate.value);
        const end = new Date(endDate.value);
        if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime()) || end <= start) return;

        const months = (end.getFullYear() - start.getFullYear()) * 12 + (end.getMonth() - start.getMonth());
        if (months > 0 && duration.value.trim() === '') {
            const years = Math.floor(months / 12);
            const rem = months % 12;
            let hint = '';
            if (years > 0) hint += years + ' year' + (years > 1 ? 's' : '');
            if (rem > 0) hint += (hint ? ', ' : '') + rem + ' month' + (rem > 1 ? 's' : '');
            duration.placeholder = 'Suggested: ' + hint;
        }
    }

    if (startDate) startDate.addEventListener('change', updateDurationHint);
    if (endDate) endDate.addEventListener('change', updateDurationHint);

    toggleDwuSpendFields();
    togglePartnerFundingFields();
    toggleSimilarPartnershipFields();
    toggleOrgTypeOther();
})();
