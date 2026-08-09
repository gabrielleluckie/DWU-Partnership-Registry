<?php

/**
 * Partnership Director — Active Partnership Entry Form.
 *
 * Expected: $partners (array), $campuses (array)
 */

$partners = $partners ?? [];
$campuses = $campuses ?? [];

$partnershipTypeOptions = [
    'Twinning',
    'Research Collaboration',
    'Student Exchange',
    'Industry / Workforce Training',
    'Community Engagement',
    'Clinical Training Partnership',
    'Funded Programme (e.g., DFAT)',
    'Other',
];

$agreementTypeOptions = [
    'MOU',
    'MOA',
    'Contract',
    'DFAT Contract',
    'Service Agreement',
];
?>
<form method="post"
      action=""
      enctype="multipart/form-data"
      id="directorPartnershipEntryForm"
      class="space-y-5 px-6 py-5">

    <input type="hidden" name="action" value="register_agreement">

    <div class="rounded-lg border border-emerald-100 bg-emerald-50 px-3 py-2 text-xs text-emerald-800">
        Submit this form after campus review is complete. This is the only path that writes official records to
        <code class="text-xs">partner</code>, <code class="text-xs">contact</code>, and <code class="text-xs">agreement</code>.
    </div>

    <!-- 1. Partner selection -->
    <fieldset class="space-y-4 rounded-xl border border-slate-200 p-4">
        <legend class="px-1 text-sm font-semibold text-slate-900">1. Partner Organisation</legend>

        <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Partner entry mode">
            <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm has-[:checked]:border-dwu-green has-[:checked]:bg-emerald-50">
                <input type="radio" name="partner_mode" value="existing" class="text-dwu-green focus:ring-dwu-green" checked>
                Select Existing Partner
            </label>
            <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm has-[:checked]:border-dwu-green has-[:checked]:bg-emerald-50">
                <input type="radio" name="partner_mode" value="new" class="text-dwu-green focus:ring-dwu-green">
                + Register New Partner
            </label>
        </div>

        <div id="existingPartnerFields">
            <label for="partner_id" class="mb-1.5 block text-sm font-medium text-slate-700">Existing partner</label>
            <select id="partner_id" name="partner_id"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-dwu-green focus:outline-none focus:ring-2 focus:ring-dwu-green/20">
                <option value="">Select a registered partner...</option>
                <?php foreach ($partners as $partner): ?>
                    <option value="<?= (int) $partner['Partner_ID'] ?>">
                        <?= e($partner['Name']) ?> (<?= e($partner['campus_name']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($partners === []): ?>
                <p class="mt-2 text-xs text-amber-700">No partners exist yet. Use “Register New Partner” to create one.</p>
            <?php endif; ?>
        </div>

        <div id="newPartnerFields" class="hidden space-y-4">
            <div>
                <label for="partner_name" class="mb-1.5 block text-sm font-medium text-slate-700">Partner name</label>
                <input type="text" id="partner_name" name="partner_name"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-dwu-green focus:outline-none focus:ring-2 focus:ring-dwu-green/20">
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="partner_country" class="mb-1.5 block text-sm font-medium text-slate-700">Country</label>
                    <input type="text" id="partner_country" name="partner_country"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-dwu-green focus:outline-none focus:ring-2 focus:ring-dwu-green/20">
                </div>
                <div>
                    <label for="partner_website" class="mb-1.5 block text-sm font-medium text-slate-700">Website URL</label>
                    <input type="url" id="partner_website" name="partner_website" placeholder="https://"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-dwu-green focus:outline-none focus:ring-2 focus:ring-dwu-green/20">
                </div>
            </div>
            <div>
                <label for="partner_address" class="mb-1.5 block text-sm font-medium text-slate-700">Physical address</label>
                <textarea id="partner_address" name="partner_address" rows="3"
                          class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-dwu-green focus:outline-none focus:ring-2 focus:ring-dwu-green/20"></textarea>
            </div>
        </div>
    </fieldset>

    <!-- 2. Managing campus -->
    <fieldset class="space-y-3 rounded-xl border border-slate-200 p-4">
        <legend class="px-1 text-sm font-semibold text-slate-900">2. Managing DWU Campus</legend>
        <label for="campus_id" class="mb-1.5 block text-sm font-medium text-slate-700">Campus managing this partnership</label>
        <select id="campus_id" name="campus_id" required
                class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-dwu-green focus:outline-none focus:ring-2 focus:ring-dwu-green/20">
            <option value="">Select campus...</option>
            <?php foreach ($campuses as $campus): ?>
                <option value="<?= (int) $campus['Campus_ID'] ?>"><?= e($campus['Name']) ?></option>
            <?php endforeach; ?>
        </select>
    </fieldset>

    <!-- 3. Primary contact -->
    <fieldset class="space-y-4 rounded-xl border border-slate-200 p-4">
        <legend class="px-1 text-sm font-semibold text-slate-900">3. Primary Contact Person</legend>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="contact_name" class="mb-1.5 block text-sm font-medium text-slate-700">Contact person name</label>
                <input type="text" id="contact_name" name="contact_name" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-dwu-green focus:outline-none focus:ring-2 focus:ring-dwu-green/20">
            </div>
            <div>
                <label for="contact_designation" class="mb-1.5 block text-sm font-medium text-slate-700">Designation / title</label>
                <input type="text" id="contact_designation" name="contact_designation"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-dwu-green focus:outline-none focus:ring-2 focus:ring-dwu-green/20">
            </div>
            <div>
                <label for="contact_email" class="mb-1.5 block text-sm font-medium text-slate-700">Email address</label>
                <input type="email" id="contact_email" name="contact_email"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-dwu-green focus:outline-none focus:ring-2 focus:ring-dwu-green/20">
            </div>
            <div>
                <label for="contact_phone" class="mb-1.5 block text-sm font-medium text-slate-700">Phone number</label>
                <input type="tel" id="contact_phone" name="contact_phone"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-dwu-green focus:outline-none focus:ring-2 focus:ring-dwu-green/20">
            </div>
            <div>
                <label for="contact_fax" class="mb-1.5 block text-sm font-medium text-slate-700">Fax</label>
                <input type="text" id="contact_fax" name="contact_fax"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-dwu-green focus:outline-none focus:ring-2 focus:ring-dwu-green/20">
            </div>
        </div>
    </fieldset>

    <!-- 4. Agreement details -->
    <fieldset class="space-y-4 rounded-xl border border-slate-200 p-4">
        <legend class="px-1 text-sm font-semibold text-slate-900">4. Agreement Details &amp; Lifespan</legend>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="partnership_type" class="mb-1.5 block text-sm font-medium text-slate-700">Partnership type</label>
                <select id="partnership_type" name="partnership_type" required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-dwu-green focus:outline-none focus:ring-2 focus:ring-dwu-green/20">
                    <option value="">Select partnership type...</option>
                    <?php foreach ($partnershipTypeOptions as $option): ?>
                        <option value="<?= e($option) ?>"><?= e($option) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="agreement_type" class="mb-1.5 block text-sm font-medium text-slate-700">Agreement type</label>
                <select id="agreement_type" name="agreement_type" required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-dwu-green focus:outline-none focus:ring-2 focus:ring-dwu-green/20">
                    <option value="">Select agreement type...</option>
                    <?php foreach ($agreementTypeOptions as $option): ?>
                        <option value="<?= e($option) ?>"><?= e($option) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="signed_date" class="mb-1.5 block text-sm font-medium text-slate-700">Signed date</label>
                <input type="date" id="signed_date" name="signed_date" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-dwu-green focus:outline-none focus:ring-2 focus:ring-dwu-green/20">
            </div>
            <div>
                <label for="expiry_date" class="mb-1.5 block text-sm font-medium text-slate-700">Expiry date</label>
                <input type="date" id="expiry_date" name="expiry_date" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-dwu-green focus:outline-none focus:ring-2 focus:ring-dwu-green/20">
            </div>
            <div class="sm:col-span-2">
                <label for="agreement_pdf" class="mb-1.5 block text-sm font-medium text-slate-700">Scanned agreement (PDF)</label>
                <input type="file" id="agreement_pdf" name="agreement_pdf" accept=".pdf,application/pdf"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-dwu-green file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white">
            </div>
        </div>
    </fieldset>

    <!-- 5. Scope -->
    <fieldset class="space-y-3 rounded-xl border border-slate-200 p-4">
        <legend class="px-1 text-sm font-semibold text-slate-900">5. Scope &amp; Historical Comments</legend>
        <label for="scope_description" class="mb-1.5 block text-sm font-medium text-slate-700">Scope description &amp; funding notes</label>
        <textarea id="scope_description"
                  name="scope_description"
                  rows="4"
                  placeholder="Enter scope details, renewal history, or funding notes (e.g., DFAT funded twinning program)..."
                  class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-dwu-green focus:outline-none focus:ring-2 focus:ring-dwu-green/20"></textarea>
    </fieldset>

    <div class="pt-2">
        <button type="submit"
                class="w-full rounded-lg bg-dwu-green px-4 py-3 text-sm font-semibold text-white transition hover:bg-dwu-dark sm:w-auto">
            Register Agreement in Registry
        </button>
    </div>
</form>

<script>
(function () {
    const form = document.getElementById('directorPartnershipEntryForm');
    if (!form) return;

    const modeRadios = form.querySelectorAll('input[name="partner_mode"]');
    const existingFields = document.getElementById('existingPartnerFields');
    const newFields = document.getElementById('newPartnerFields');
    const partnerSelect = document.getElementById('partner_id');
    const newRequiredIds = ['partner_name', 'partner_country'];

    function setPartnerMode() {
        const mode = form.querySelector('input[name="partner_mode"]:checked')?.value || 'existing';
        const isExisting = mode === 'existing';

        existingFields.classList.toggle('hidden', !isExisting);
        newFields.classList.toggle('hidden', isExisting);

        if (partnerSelect) {
            partnerSelect.required = isExisting;
            partnerSelect.disabled = !isExisting;
        }

        newRequiredIds.forEach(function (id) {
            const input = document.getElementById(id);
            if (input) {
                input.required = !isExisting;
                input.disabled = isExisting;
            }
        });

        ['partner_address', 'partner_website'].forEach(function (id) {
            const input = document.getElementById(id);
            if (input) {
                input.disabled = isExisting;
            }
        });
    }

    modeRadios.forEach(function (radio) {
        radio.addEventListener('change', setPartnerMode);
    });

    setPartnerMode();
})();
</script>
