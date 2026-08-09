(function () {
    'use strict';

    const form = document.getElementById('partnershipForm');
    if (!form) return;

    const draftBtn = form.querySelector('button[value="save_draft"]');
    const submitBtn = form.querySelector('button[value="submit_proposal"]');
    const errorBanner = document.getElementById('formErrorBanner');

    /* ── Bootstrap ScrollSpy ── */
    if (typeof bootstrap !== 'undefined') {
        const scrollSpyTarget = document.getElementById('partnership-scrollspy');
        if (scrollSpyTarget) {
            bootstrap.ScrollSpy.getOrCreateInstance(document.body, {
                target: '#partnership-scrollspy',
                offset: 90,
                smoothScroll: true,
            });
        }
    }

    /* ── Helpers ── */
    function selectedValue(name) {
        const checked = form.querySelector('input[name="' + name + '"]:checked');
        return checked ? checked.value : '';
    }

    function toggleBlock(id, show) {
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.toggle('d-none', !show);
        if (show) el.classList.add('conditional-panel');
    }

    function setRequired(el, required) {
        if (el) el.required = !!required;
    }

    function isHidden(el) {
        return !el || el.classList.contains('d-none') || el.closest('.d-none');
    }

    function clearFieldError(field) {
        if (!field) return;
        field.classList.remove('field-error', 'is-invalid');
        const msg = field.parentElement && field.parentElement.querySelector('.field-error-msg');
        if (msg) msg.remove();
    }

    function showFieldError(field, message) {
        if (!field) return;
        field.classList.add('field-error', 'is-invalid');
        let msg = field.parentElement && field.parentElement.querySelector('.field-error-msg');
        if (!msg && field.parentElement) {
            msg = document.createElement('div');
            msg.className = 'field-error-msg invalid-feedback d-block';
            field.parentElement.appendChild(msg);
        }
        if (msg) msg.textContent = message || field.validationMessage;
    }

    function hideFormError() {
        if (!errorBanner) return;
        errorBanner.classList.add('d-none');
        errorBanner.textContent = '';
    }

    function showFormError(message) {
        if (!errorBanner) return;
        errorBanner.textContent = message;
        errorBanner.classList.remove('d-none');
        errorBanner.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    /* ── Conditional toggles ── */
    function togglePartnerTypeOther() {
        const select = form.querySelector('[data-partner-type-select]');
        const isOther = select && select.value === 'Other';
        toggleBlock('partnerTypeOtherWrap', isOther);
        setRequired(document.getElementById('partner_type_other'), isOther);
    }

    function togglePartnershipNatureOther() {
        const otherCb = form.querySelector('.partnership-nature-cb[data-nature-value="Other"]');
        toggleBlock('partnershipNatureOtherWrap', !!(otherCb && otherCb.checked));
        setRequired(document.getElementById('partnership_nature_other'), !!(otherCb && otherCb.checked));
    }

    function toggleDwuCostFields() {
        const isYes = selectedValue('dwu_financial_commitment') === 'Yes';
        toggleBlock('dwuCostWrap', isYes);
        setRequired(document.getElementById('dwu_estimated_cost'), isYes);
    }

    function togglePartnerFundingFields() {
        const isYes = selectedValue('partner_financial_contribution') === 'Yes';
        toggleBlock('partnerFundingWrap', isYes);
        setRequired(document.getElementById('partner_funding_amount'), isYes);
        setRequired(document.getElementById('partner_funding_currency'), isYes);
        setRequired(document.getElementById('partner_payment_timing'), isYes);
    }

    function togglePreviousEngagementFields() {
        const isYes = selectedValue('previous_engagement') === 'Yes';
        toggleBlock('previousEngagementWrap', isYes);
        setRequired(document.getElementById('previous_engagement_details'), isYes);
    }

    function bindRadioToggle(selector, handler) {
        form.querySelectorAll(selector).forEach(function (input) {
            input.addEventListener('change', handler);
        });
    }

    /* ── Duration auto-calculation ── */
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    const duration = document.getElementById('total_duration');

    function formatDuration(start, end) {
        let months = (end.getFullYear() - start.getFullYear()) * 12 + (end.getMonth() - start.getMonth());
        if (end.getDate() - start.getDate() < 0) months -= 1;
        if (months <= 0) {
            const diffDays = Math.round((end - start) / (1000 * 60 * 60 * 24));
            return diffDays + ' day' + (diffDays !== 1 ? 's' : '');
        }
        const years = Math.floor(months / 12);
        const rem = months % 12;
        const parts = [];
        if (years > 0) parts.push(years + ' year' + (years > 1 ? 's' : ''));
        if (rem > 0) parts.push(rem + ' month' + (rem > 1 ? 's' : ''));
        return parts.join(', ');
    }

    function updateDuration() {
        if (!startDate || !endDate || !duration) return;
        if (!startDate.value || !endDate.value) {
            duration.value = '';
            return;
        }
        const start = new Date(startDate.value + 'T00:00:00');
        const end = new Date(endDate.value + 'T00:00:00');
        if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime()) || end <= start) {
            duration.value = '';
            if (endDate) endDate.setCustomValidity('End date must be after start date.');
            return;
        }
        endDate.setCustomValidity('');
        duration.value = formatDuration(start, end);
    }

    if (startDate) startDate.addEventListener('change', updateDuration);
    if (endDate) endDate.addEventListener('change', updateDuration);

    /* ── Document uploads & drag-drop ── */
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

    function assignFileToInput(input, file) {
        try {
            const dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        } catch (err) { /* browse fallback */ }
    }

    function findNextAvailableDocInput() {
        const inputs = form.querySelectorAll('.doc-file-input');
        for (let i = 0; i < inputs.length; i++) {
            const key = inputs[i].name.replace('_file', '');
            const cb = form.querySelector('[data-doc-target="' + key + '"]');
            if (cb && cb.checked && !inputs[i].files.length) return inputs[i];
        }
        return form.querySelector('.doc-upload:not(.d-none) .doc-file-input');
    }

    function bindDropZone(zone) {
        if (!zone) return;
        ['dragenter', 'dragover'].forEach(function (evt) {
            zone.addEventListener(evt, function (e) {
                e.preventDefault();
                e.stopPropagation();
                zone.classList.add('is-dragover');
            });
        });
        ['dragleave', 'drop'].forEach(function (evt) {
            zone.addEventListener(evt, function (e) {
                e.preventDefault();
                e.stopPropagation();
                zone.classList.remove('is-dragover');
            });
        });
        zone.addEventListener('drop', function (e) {
            const files = e.dataTransfer.files;
            if (!files || !files.length) return;
            const fileInput = zone.querySelector('input[type="file"]') || findNextAvailableDocInput();
            if (fileInput) assignFileToInput(fileInput, files[0]);
        });
    }

    form.querySelectorAll('.drop-zone').forEach(bindDropZone);

    const masterDropZone = document.getElementById('masterDropZone');
    if (masterDropZone) {
        masterDropZone.addEventListener('click', function () {
            const target = findNextAvailableDocInput();
            if (target) target.click();
        });
        bindDropZone(masterDropZone);
    }

    form.querySelectorAll('.doc-file-input').forEach(function (input) {
        input.addEventListener('change', function () {
            const wrap = input.closest('.doc-upload');
            const filename = wrap ? wrap.querySelector('.doc-filename') : null;
            if (!filename) return;
            filename.textContent = input.files && input.files.length
                ? 'Selected: ' + input.files[0].name : '';
        });
    });

    /* ── Blur validation ── */
    function validateFieldOnBlur(field) {
        if (field.disabled || field.type === 'hidden') return;
        if (isHidden(field)) return;
        clearFieldError(field);
        if (field.name === 'partnership_nature[]') return;
        if (!field.checkValidity()) showFieldError(field);
    }

    form.querySelectorAll('input, select, textarea').forEach(function (field) {
        field.addEventListener('blur', function () { validateFieldOnBlur(field); });
        field.addEventListener('input', function () {
            if (field.classList.contains('field-error') && field.checkValidity()) clearFieldError(field);
        });
    });

    function validatePartnershipNature() {
        return form.querySelectorAll('.partnership-nature-cb:checked').length > 0;
    }

    function validateOnSubmit() {
        hideFormError();
        form.querySelectorAll('.field-error, .is-invalid').forEach(function (el) {
            el.classList.remove('field-error', 'is-invalid');
        });
        form.querySelectorAll('.field-error-msg').forEach(function (el) { el.remove(); });

        updateDuration();

        let firstInvalid = null;
        form.querySelectorAll('input, select, textarea').forEach(function (field) {
            if (field.disabled || field.type === 'hidden') return;
            if (isHidden(field)) return;
            if (!field.checkValidity()) {
                showFieldError(field);
                if (!firstInvalid) firstInvalid = field;
            }
        });

        if (!validatePartnershipNature()) {
            showFormError('Please select at least one Partnership Nature option in Section C.');
            document.getElementById('partnershipNatureGroup').scrollIntoView({ behavior: 'smooth', block: 'center' });
            return false;
        }

        const declaration = document.getElementById('staff_declaration_agree');
        if (!declaration || !declaration.checked) {
            showFormError('You must confirm the Staff Declaration in Section I before submitting.');
            declaration.closest('.form-section').scrollIntoView({ behavior: 'smooth', block: 'center' });
            return false;
        }

        if (!duration || !duration.value) {
            showFormError('Please enter valid start and end dates so total duration can be calculated.');
            document.getElementById('section-f').scrollIntoView({ behavior: 'smooth', block: 'center' });
            return false;
        }

        if (firstInvalid) {
            showFormError('Please complete all required fields marked with * before submitting.');
            firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstInvalid.focus({ preventScroll: true });
            return false;
        }

        return true;
    }

    if (draftBtn) {
        draftBtn.addEventListener('click', function () {
            form.noValidate = true;
            setRequired(document.getElementById('staff_declaration_agree'), false);
            setRequired(document.getElementById('signee_name'), false);
            setRequired(document.getElementById('signee_campus'), false);
            setRequired(document.getElementById('signee_date'), false);
            hideFormError();
        });
    }

    if (submitBtn) {
        submitBtn.addEventListener('click', function (e) {
            form.noValidate = false;
            setRequired(document.getElementById('staff_declaration_agree'), true);
            setRequired(document.getElementById('signee_name'), true);
            setRequired(document.getElementById('signee_campus'), true);
            setRequired(document.getElementById('signee_date'), true);
            if (!validateOnSubmit()) e.preventDefault();
        });
    }

    /* ── Bind toggles & init ── */
    const partnerTypeSelect = form.querySelector('[data-partner-type-select]');
    if (partnerTypeSelect) partnerTypeSelect.addEventListener('change', togglePartnerTypeOther);

    form.querySelectorAll('.partnership-nature-cb').forEach(function (cb) {
        cb.addEventListener('change', togglePartnershipNatureOther);
    });

    bindRadioToggle('[data-dwu-commitment-toggle]', toggleDwuCostFields);
    bindRadioToggle('[data-partner-contribution-toggle]', togglePartnerFundingFields);
    bindRadioToggle('[data-previous-engagement-toggle]', togglePreviousEngagementFields);

    togglePartnerTypeOther();
    togglePartnershipNatureOther();
    toggleDwuCostFields();
    togglePartnerFundingFields();
    togglePreviousEngagementFields();
    updateDuration();
})();
