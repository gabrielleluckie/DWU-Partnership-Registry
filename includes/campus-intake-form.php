<?php

/**
 * Campus Admin — Proposed Partnership Submission Form
 *
 * Expected: $user (array), $draft (array)
 */

$draft = $draft ?? [];
$today = date('Y-m-d');

$field = static function (string $name, string $default = '') use ($draft): string {
    $value = $draft[$name] ?? $default;

    return is_array($value) ? '' : (string) $value;
};

$campusDefault = (string) ($draft['submitter_campus'] ?? '');
if ($campusDefault === '' && !empty($user['campus'])) {
    $campusMap = [
        'Madang'       => 'Madang (Main)',
        'Rabaul'       => 'Rabaul',
        'Wewak'        => 'Sepik (Wewak)',
        'Sepik'        => 'Sepik (Wewak)',
        'Port Moresby' => 'Port Moresby',
    ];
    foreach ($campusMap as $needle => $value) {
        if (stripos($user['campus'], $needle) !== false) {
            $campusDefault = $value;
            break;
        }
    }
}

$checked = static function (string $name, string $value) use ($draft, $campusDefault): string {
    if ($name === 'submitter_campus' && empty($draft['submitter_campus']) && $campusDefault !== '') {
        return $campusDefault === $value ? 'checked' : '';
    }

    $current = $draft[$name] ?? '';

    if (is_array($current)) {
        return in_array($value, $current, true) ? 'checked' : '';
    }

    return (string) $current === $value ? 'checked' : '';
};

$selected = static function (string $name, string $value) use ($draft): string {
    $current = $draft[$name] ?? '';

    return (string) $current === $value ? 'selected' : '';
};

$inputClass = 'mt-1 block w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm text-slate-800 shadow-sm transition focus:border-transparent focus:outline-none focus:ring-2 focus:ring-emerald-600';
$labelClass = 'block text-sm font-semibold text-slate-700';
$sectionClass = 'rounded-xl border border-slate-200 bg-white p-6 shadow-sm';
$sectionTitleClass = 'mb-5 border-b border-slate-100 pb-3 text-lg font-bold tracking-tight text-emerald-900';
$choiceClass = 'flex items-start gap-2.5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50/40';
$req = '<span class="text-red-600" aria-hidden="true">*</span>';
?>

<form id="campusIntakeForm"
      action="dashboard_campus_admin.php?tab=submit"
      method="post"
      enctype="multipart/form-data"
      class="space-y-6">

    <!-- Form header banner -->
    <header class="overflow-hidden rounded-xl border border-emerald-200 bg-white shadow-sm">
        <div class="bg-gradient-to-r from-emerald-900 to-emerald-800 px-6 py-5 text-white">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-100">
                Divine Word University | Partnership Office — Madang, Papua New Guinea
            </p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">
                Proposed Partnership Submission Form
            </h1>
        </div>
        <div class="border-l-4 border-amber-400 bg-amber-50 px-6 py-4">
            <p class="text-sm font-semibold text-amber-900">Draft Version — For Internal Campus Use</p>
            <p class="mt-1 text-sm leading-relaxed text-amber-800">
                Complete all required fields marked with <?= $req ?>. This form captures proposed partnership details
                for review by the Partnership Director. Supporting documents may be attached where available.
                Save a draft at any time or submit when ready for director approval.
            </p>
        </div>
    </header>

    <!-- Section A -->
    <section class="<?= e($sectionClass) ?>">
        <h2 class="<?= e($sectionTitleClass) ?>">Section A: Submitter Details</h2>
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
                <label class="<?= e($labelClass) ?>" for="submitter_name">Full Name <?= $req ?></label>
                <input type="text" id="submitter_name" name="submitter_name" required
                       class="<?= e($inputClass) ?>"
                       value="<?= e($field('submitter_name', $user['name'] ?? '')) ?>">
            </div>
            <div>
                <label class="<?= e($labelClass) ?>" for="submitter_department">Faculty or Department <?= $req ?></label>
                <input type="text" id="submitter_department" name="submitter_department" required
                       class="<?= e($inputClass) ?>"
                       value="<?= e($field('submitter_department', $user['department'] ?? '')) ?>">
            </div>
            <div>
                <label class="<?= e($labelClass) ?>" for="submission_date">Date of Submission <?= $req ?></label>
                <input type="date" id="submission_date" name="submission_date" required
                       class="<?= e($inputClass) ?>"
                       value="<?= e($field('submission_date', $today)) ?>">
            </div>
            <div>
                <label class="<?= e($labelClass) ?>" for="submitter_email">Email Address <?= $req ?></label>
                <input type="email" id="submitter_email" name="submitter_email" required
                       class="<?= e($inputClass) ?>"
                       value="<?= e($field('submitter_email', $user['email'] ?? '')) ?>">
            </div>
            <div class="md:col-span-2">
                <label class="<?= e($labelClass) ?>" for="submitter_phone">Contact Phone Number</label>
                <input type="tel" id="submitter_phone" name="submitter_phone"
                       class="<?= e($inputClass) ?>"
                       value="<?= e($field('submitter_phone')) ?>"
                       placeholder="+675 XXX XXXX">
            </div>
            <fieldset class="md:col-span-2">
                <legend class="<?= e($labelClass) ?>">Campus <?= $req ?></legend>
                <div class="mt-2 grid grid-cols-1 gap-3 md:grid-cols-2">
                    <?php
                    $campuses = [
                        'Madang (Main)' => 'Madang (Main)',
                        'Rabaul'        => 'Rabaul',
                        'Sepik (Wewak)' => 'Sepik (Wewak)',
                        'Port Moresby'  => 'Port Moresby',
                    ];
                    foreach ($campuses as $value => $label):
                    ?>
                        <label class="<?= e($choiceClass) ?>">
                            <input type="radio" name="submitter_campus" value="<?= e($value) ?>" required
                                   class="mt-0.5 text-emerald-700 focus:ring-emerald-600"
                                   <?= $checked('submitter_campus', $value) ?>>
                            <span><?= e($label) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>
        </div>
    </section>

    <!-- Section B -->
    <section class="<?= e($sectionClass) ?>">
        <h2 class="<?= e($sectionTitleClass) ?>">Section B: Partner Organisation Details</h2>
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="<?= e($labelClass) ?>" for="partner_legal_name">Full Legal Name of Partner <?= $req ?></label>
                <input type="text" id="partner_legal_name" name="partner_legal_name" required
                       class="<?= e($inputClass) ?>"
                       value="<?= e($field('partner_legal_name')) ?>">
            </div>
            <div>
                <label class="<?= e($labelClass) ?>" for="partner_location">Country and City/Province <?= $req ?></label>
                <input type="text" id="partner_location" name="partner_location" required
                       class="<?= e($inputClass) ?>"
                       value="<?= e($field('partner_location')) ?>"
                       placeholder="e.g. Papua New Guinea, Port Moresby">
            </div>
            <div>
                <label class="<?= e($labelClass) ?>" for="partner_website">Organisation Website</label>
                <input type="url" id="partner_website" name="partner_website"
                       class="<?= e($inputClass) ?>"
                       value="<?= e($field('partner_website')) ?>"
                       placeholder="https://">
            </div>
        </div>

        <fieldset class="mt-6">
            <legend class="<?= e($labelClass) ?>">Type of Organisation <?= $req ?></legend>
            <div class="mt-2 grid grid-cols-1 gap-3 md:grid-cols-2">
                <?php
                $orgTypes = [
                    'University/Academic',
                    'Government',
                    'NGO',
                    'Private Sector/Industry',
                    'International',
                    'Community',
                    'Other',
                ];
                foreach ($orgTypes as $type):
                    $isOrgChecked = is_array($draft['org_types'] ?? null) && in_array($type, $draft['org_types'], true);
                ?>
                    <label class="<?= e($choiceClass) ?>">
                        <input type="checkbox" name="org_types[]" value="<?= e($type) ?>"
                               class="mt-0.5 rounded text-emerald-700 focus:ring-emerald-600"
                               <?= $isOrgChecked ? 'checked' : '' ?>>
                        <span><?= e($type) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <div id="orgTypeOtherWrap" class="mt-3 hidden">
                <label class="<?= e($labelClass) ?>" for="org_type_other">Please specify other organisation type</label>
                <input type="text" id="org_type_other" name="org_type_other"
                       class="<?= e($inputClass) ?>"
                       value="<?= e($field('org_type_other')) ?>">
            </div>
        </fieldset>

        <div class="mt-6 rounded-lg border border-slate-100 bg-slate-50 p-5">
            <h3 class="mb-4 text-sm font-bold uppercase tracking-wide text-emerald-900">Primary Contact Person</h3>
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label class="<?= e($labelClass) ?>" for="contact_name">Name <?= $req ?></label>
                    <input type="text" id="contact_name" name="contact_name" required
                           class="<?= e($inputClass) ?>"
                           value="<?= e($field('contact_name')) ?>">
                </div>
                <div>
                    <label class="<?= e($labelClass) ?>" for="contact_title">Title/Position <?= $req ?></label>
                    <input type="text" id="contact_title" name="contact_title" required
                           class="<?= e($inputClass) ?>"
                           value="<?= e($field('contact_title')) ?>">
                </div>
                <div>
                    <label class="<?= e($labelClass) ?>" for="contact_email">Email Address <?= $req ?></label>
                    <input type="email" id="contact_email" name="contact_email" required
                           class="<?= e($inputClass) ?>"
                           value="<?= e($field('contact_email')) ?>">
                </div>
                <div>
                    <label class="<?= e($labelClass) ?>" for="contact_phone">Phone Number <?= $req ?></label>
                    <input type="tel" id="contact_phone" name="contact_phone" required
                           class="<?= e($inputClass) ?>"
                           value="<?= e($field('contact_phone')) ?>">
                </div>
            </div>
        </div>
    </section>

    <!-- Section C -->
    <section class="<?= e($sectionClass) ?>">
        <h2 class="<?= e($sectionTitleClass) ?>">Section C: Type and Nature of Partnership</h2>
        <fieldset>
            <legend class="<?= e($labelClass) ?>">Type of Agreement Proposed <?= $req ?></legend>
            <div class="mt-2 grid grid-cols-1 gap-3 md:grid-cols-2">
                <?php foreach (['MOU', 'MOA', 'Service Agreement', 'Formal Contract', 'Arrangement', 'Other'] as $agreement): ?>
                    <label class="<?= e($choiceClass) ?>">
                        <input type="radio" name="agreement_type" value="<?= e($agreement) ?>" required
                               class="mt-0.5 text-emerald-700 focus:ring-emerald-600"
                               <?= $checked('agreement_type', $agreement) ?>>
                        <span><?= e($agreement) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <fieldset class="mt-6">
            <legend class="<?= e($labelClass) ?>">Type of Partnership <?= $req ?></legend>
            <div class="mt-2 grid grid-cols-1 gap-3 md:grid-cols-2">
                <?php
                $partnershipTypes = [
                    'Academic/Twinning',
                    'Research Collaboration',
                    'Student Exchange/Placement',
                    'Community Engagement',
                    'Funded Programme (e.g., DFAT, AusAID)',
                    'Industry/Workforce Training',
                    'Other',
                ];
                foreach ($partnershipTypes as $type):
                    $isChecked = is_array($draft['partnership_types'] ?? null) && in_array($type, $draft['partnership_types'], true);
                ?>
                    <label class="<?= e($choiceClass) ?>">
                        <input type="checkbox" name="partnership_types[]" value="<?= e($type) ?>"
                               class="mt-0.5 rounded text-emerald-700 focus:ring-emerald-600"
                               <?= $isChecked ? 'checked' : '' ?>>
                        <span><?= e($type) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <div class="mt-6">
            <label class="<?= e($labelClass) ?>" for="partnership_description">
                Brief Description (3–5 sentence summary) <?= $req ?>
            </label>
            <textarea id="partnership_description" name="partnership_description" required rows="4"
                      class="<?= e($inputClass) ?>"
                      placeholder="Summarise the purpose, scope, and expected outcomes of the proposed partnership."><?= e($field('partnership_description')) ?></textarea>
        </div>
    </section>

    <!-- Section D -->
    <section class="<?= e($sectionClass) ?>">
        <h2 class="<?= e($sectionTitleClass) ?>">Section D: What DWU is Committing To</h2>
        <div>
            <label class="<?= e($labelClass) ?>" for="dwu_commitments">Commitments <?= $req ?></label>
            <textarea id="dwu_commitments" name="dwu_commitments" required rows="4"
                      class="<?= e($inputClass) ?>"
                      placeholder="Describe resources, staff time, facilities, or services DWU will provide."><?= e($field('dwu_commitments')) ?></textarea>
        </div>

        <fieldset class="mt-6">
            <legend class="<?= e($labelClass) ?>">Financial Spend Required? <?= $req ?></legend>
            <div class="mt-2 grid grid-cols-1 gap-3 md:grid-cols-3">
                <?php foreach (['Yes', 'No', 'Not yet confirmed'] as $option): ?>
                    <label class="<?= e($choiceClass) ?>">
                        <input type="radio" name="dwu_financial_spend" value="<?= e($option) ?>" required
                               class="mt-0.5 text-emerald-700 focus:ring-emerald-600"
                               data-dwu-spend-toggle
                               <?= $checked('dwu_financial_spend', $option) ?>>
                        <span><?= e($option) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <div id="dwuSpendFields" class="mt-5 hidden grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
                <label class="<?= e($labelClass) ?>" for="dwu_estimated_cost">Estimated Cost to DWU (PGK or USD)</label>
                <input type="text" id="dwu_estimated_cost" name="dwu_estimated_cost"
                       class="<?= e($inputClass) ?>"
                       value="<?= e($field('dwu_estimated_cost')) ?>"
                       placeholder="e.g. PGK 25,000">
            </div>
            <div>
                <label class="<?= e($labelClass) ?>" for="dwu_responsible_department">Responsible DWU Faculty/Department <?= $req ?></label>
                <input type="text" id="dwu_responsible_department" name="dwu_responsible_department"
                       class="<?= e($inputClass) ?>"
                       value="<?= e($field('dwu_responsible_department')) ?>">
            </div>
        </div>
    </section>

    <!-- Section E -->
    <section class="<?= e($sectionClass) ?>">
        <h2 class="<?= e($sectionTitleClass) ?>">Section E: What the Partner Organisation is Contributing</h2>
        <div>
            <label class="<?= e($labelClass) ?>" for="partner_contributions">Partner Contributions <?= $req ?></label>
            <textarea id="partner_contributions" name="partner_contributions" required rows="4"
                      class="<?= e($inputClass) ?>"
                      placeholder="Describe funding, expertise, equipment, or in-kind support from the partner."><?= e($field('partner_contributions')) ?></textarea>
        </div>

        <fieldset class="mt-6">
            <legend class="<?= e($labelClass) ?>">Financial Funding to DWU? <?= $req ?></legend>
            <div class="mt-2 grid grid-cols-1 gap-3 md:grid-cols-3">
                <?php foreach (['Yes', 'No', 'Not yet confirmed'] as $option): ?>
                    <label class="<?= e($choiceClass) ?>">
                        <input type="radio" name="partner_funding" value="<?= e($option) ?>" required
                               class="mt-0.5 text-emerald-700 focus:ring-emerald-600"
                               data-partner-funding-toggle
                               <?= $checked('partner_funding', $option) ?>>
                        <span><?= e($option) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <div id="partnerFundingFields" class="mt-5 hidden space-y-5">
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label class="<?= e($labelClass) ?>" for="partner_funding_amount">Amount Being Given to DWU</label>
                    <input type="text" id="partner_funding_amount" name="partner_funding_amount"
                           class="<?= e($inputClass) ?>"
                           value="<?= e($field('partner_funding_amount')) ?>">
                </div>
                <div>
                    <label class="<?= e($labelClass) ?>" for="partner_payment_method">Payment Method</label>
                    <select id="partner_payment_method" name="partner_payment_method"
                            class="<?= e($inputClass) ?>">
                        <option value="">Select payment method...</option>
                        <?php
                        $paymentMethods = [
                            'One-off payment',
                            'Installments',
                            'In-kind contribution',
                            'Grant disbursement',
                            'Scholarship fund',
                            'Other',
                        ];
                        foreach ($paymentMethods as $method):
                        ?>
                            <option value="<?= e($method) ?>" <?= $selected('partner_payment_method', $method) ?>><?= e($method) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <fieldset>
                <legend class="<?= e($labelClass) ?>">Currency</legend>
                <div class="mt-2 grid grid-cols-2 gap-3 md:grid-cols-4">
                    <?php foreach (['PGK', 'USD', 'AUD', 'Other'] as $currency): ?>
                        <label class="<?= e($choiceClass) ?>">
                            <input type="radio" name="partner_funding_currency" value="<?= e($currency) ?>"
                                   class="mt-0.5 text-emerald-700 focus:ring-emerald-600"
                                   <?= $checked('partner_funding_currency', $currency) ?>>
                            <span><?= e($currency) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>
        </div>

        <div class="mt-6">
            <label class="<?= e($labelClass) ?>" for="partner_conditions">Partner Conditions <span class="text-xs font-normal text-slate-500">(Optional)</span></label>
            <textarea id="partner_conditions" name="partner_conditions" rows="3"
                      class="<?= e($inputClass) ?>"
                      placeholder="Any conditions or expectations from the partner organisation."><?= e($field('partner_conditions')) ?></textarea>
        </div>
    </section>

    <!-- Section F -->
    <section class="<?= e($sectionClass) ?>">
        <h2 class="<?= e($sectionTitleClass) ?>">Section F: Duration and Timeline</h2>
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
                <label class="<?= e($labelClass) ?>" for="proposed_start_date">Proposed Start Date <?= $req ?></label>
                <input type="date" id="proposed_start_date" name="proposed_start_date" required
                       class="<?= e($inputClass) ?>"
                       value="<?= e($field('proposed_start_date')) ?>">
            </div>
            <div>
                <label class="<?= e($labelClass) ?>" for="proposed_end_date">Proposed End/Expiry Date <?= $req ?></label>
                <input type="date" id="proposed_end_date" name="proposed_end_date" required
                       class="<?= e($inputClass) ?>"
                       value="<?= e($field('proposed_end_date')) ?>">
            </div>
            <div>
                <label class="<?= e($labelClass) ?>" for="total_duration">Total Duration <?= $req ?></label>
                <input type="text" id="total_duration" name="total_duration" required
                       class="<?= e($inputClass) ?>"
                       value="<?= e($field('total_duration')) ?>"
                       placeholder="e.g. 3 years">
            </div>
            <fieldset>
                <legend class="<?= e($labelClass) ?>">Option to Renew <?= $req ?></legend>
                <div class="mt-2 grid grid-cols-1 gap-3">
                    <?php foreach (['Yes', 'No', 'Not yet discussed'] as $option): ?>
                        <label class="<?= e($choiceClass) ?>">
                            <input type="radio" name="renew_option" value="<?= e($option) ?>" required
                                   class="mt-0.5 text-emerald-700 focus:ring-emerald-600"
                                   <?= $checked('renew_option', $option) ?>>
                            <span><?= e($option) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>
            <div class="md:col-span-2">
                <label class="<?= e($labelClass) ?>" for="milestones">Specific Milestones/Activities</label>
                <textarea id="milestones" name="milestones" rows="3"
                          class="<?= e($inputClass) ?>"
                          placeholder="Key activities, deliverables, or review points across the partnership timeline."><?= e($field('milestones')) ?></textarea>
            </div>
        </div>
    </section>

    <!-- Section G -->
    <section class="<?= e($sectionClass) ?>">
        <h2 class="<?= e($sectionTitleClass) ?>">Section G: Benefits to DWU and Justification</h2>
        <div class="space-y-5">
            <div>
                <label class="<?= e($labelClass) ?>" for="main_benefits">Main Benefits to DWU <?= $req ?></label>
                <textarea id="main_benefits" name="main_benefits" required rows="3"
                          class="<?= e($inputClass) ?>"><?= e($field('main_benefits')) ?></textarea>
            </div>
            <div>
                <label class="<?= e($labelClass) ?>" for="strategic_goals">Strategic Goals Supported <?= $req ?></label>
                <textarea id="strategic_goals" name="strategic_goals" required rows="3"
                          class="<?= e($inputClass) ?>"><?= e($field('strategic_goals')) ?></textarea>
            </div>
            <div>
                <label class="<?= e($labelClass) ?>" for="risks_concerns">Risks or Concerns</label>
                <textarea id="risks_concerns" name="risks_concerns" rows="3"
                          class="<?= e($inputClass) ?>"><?= e($field('risks_concerns')) ?></textarea>
            </div>
        </div>

        <fieldset class="mt-6">
            <legend class="<?= e($labelClass) ?>">Similar Partnerships Attempted Before? <?= $req ?></legend>
            <div class="mt-2 grid grid-cols-1 gap-3 md:grid-cols-3">
                <?php foreach (['Yes', 'No', 'Not Sure'] as $option): ?>
                    <label class="<?= e($choiceClass) ?>">
                        <input type="radio" name="similar_partnerships" value="<?= e($option) ?>" required
                               class="mt-0.5 text-emerald-700 focus:ring-emerald-600"
                               data-similar-toggle
                               <?= $checked('similar_partnerships', $option) ?>>
                        <span><?= e($option) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <div id="similarPartnershipWrap" class="mt-5 hidden">
            <label class="<?= e($labelClass) ?>" for="similar_partnerships_detail">Describe Outcome of Previous Engagement</label>
            <textarea id="similar_partnerships_detail" name="similar_partnerships_detail" rows="3"
                      class="<?= e($inputClass) ?>"><?= e($field('similar_partnerships_detail')) ?></textarea>
        </div>
    </section>

    <!-- Section H -->
    <section class="<?= e($sectionClass) ?>">
        <h2 class="<?= e($sectionTitleClass) ?>">Section H: Supporting Documents</h2>
        <fieldset>
            <legend class="<?= e($labelClass) ?>">Supporting Documents to Attach</legend>
            <p class="mt-1 text-sm text-slate-600">
                Select document types to attach. File uploads are optional at draft stage but encouraged before final submission.
            </p>
            <div class="mt-2 grid grid-cols-1 gap-3 md:grid-cols-2">
                <?php
                $docTypes = [
                    'doc_mou'        => 'Draft MOU/MOA',
                    'doc_loi'        => 'Letter of Intent',
                    'doc_profile'    => 'Partner Profile/Brochure',
                    'doc_budget'     => 'Budget Breakdown',
                    'doc_correspond' => 'Correspondence',
                    'doc_research'   => 'Supporting Research',
                    'doc_other'      => 'Other',
                ];
                foreach ($docTypes as $key => $label):
                    $isDocChecked = !empty($draft[$key . '_enabled']);
                ?>
                    <div class="flex flex-col gap-2">
                        <label class="<?= e($choiceClass) ?>">
                            <input type="checkbox" name="<?= e($key) ?>_enabled" value="1"
                                   class="doc-toggle mt-0.5 rounded text-emerald-700 focus:ring-emerald-600"
                                   data-doc-target="<?= e($key) ?>"
                                   <?= $isDocChecked ? 'checked' : '' ?>>
                            <span><?= e($label) ?></span>
                        </label>
                        <div id="<?= e($key) ?>Upload" class="doc-upload <?= $isDocChecked ? '' : 'hidden' ?>">
                            <label class="flex cursor-pointer flex-col items-center justify-center rounded-lg border border-dashed border-emerald-200 bg-white px-3 py-4 transition hover:border-emerald-400 hover:bg-emerald-50/30 focus-within:border-emerald-500 focus-within:ring-2 focus-within:ring-emerald-600">
                                <svg class="mb-1.5 h-6 w-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                                <span class="text-xs font-medium text-emerald-800">Upload file</span>
                                <span class="mt-0.5 text-[11px] text-slate-500">PDF, DOCX, or image</span>
                                <input type="file" name="<?= e($key) ?>_file" class="sr-only" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg">
                            </label>
                            <p class="doc-filename mt-1.5 truncate text-xs text-slate-500"></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </fieldset>
    </section>

    <!-- Section I -->
    <section class="<?= e($sectionClass) ?>">
        <h2 class="<?= e($sectionTitleClass) ?>">Section I: Declaration by Campus Administrator</h2>
        <label class="flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50/60 p-4">
            <input type="checkbox" id="declaration_confirm" name="declaration_confirm" value="1" required
                   class="mt-1 h-4 w-4 rounded text-emerald-700 focus:ring-emerald-600"
                   <?= !empty($draft['declaration_confirm']) ? 'checked' : '' ?>>
            <span class="text-sm leading-relaxed text-slate-700">
                I confirm that the information provided in this proposal is accurate to the best of my knowledge,
                has been reviewed at campus level, and is submitted for consideration by the Partnership Director
                in accordance with DWU partnership governance procedures.
            </span>
        </label>

        <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
                <label class="<?= e($labelClass) ?>" for="declaration_name">Name <?= $req ?></label>
                <input type="text" id="declaration_name" name="declaration_name" required
                       class="<?= e($inputClass) ?>"
                       value="<?= e($field('declaration_name', $user['name'] ?? '')) ?>">
            </div>
            <div>
                <label class="<?= e($labelClass) ?>" for="declaration_initials">Signature/Digital Initials <?= $req ?></label>
                <input type="text" id="declaration_initials" name="declaration_initials" required
                       class="<?= e($inputClass) ?>"
                       value="<?= e($field('declaration_initials')) ?>"
                       placeholder="e.g. A.S.">
            </div>
            <div>
                <label class="<?= e($labelClass) ?>" for="declaration_campus">Campus <?= $req ?></label>
                <input type="text" id="declaration_campus" name="declaration_campus" required
                       class="<?= e($inputClass) ?>"
                       value="<?= e($field('declaration_campus', $user['campus'] ?? '')) ?>">
            </div>
            <div>
                <label class="<?= e($labelClass) ?>" for="declaration_date">Date <?= $req ?></label>
                <input type="date" id="declaration_date" name="declaration_date" required
                       class="<?= e($inputClass) ?>"
                       value="<?= e($field('declaration_date', $today)) ?>">
            </div>
        </div>
    </section>

    <!-- Actions -->
    <div class="sticky bottom-0 z-20 -mx-1 flex flex-col-reverse gap-3 rounded-xl border border-slate-200 bg-white/95 p-4 shadow-lg backdrop-blur sm:flex-row sm:items-center sm:justify-end sm:gap-4">
        <button type="submit"
                name="form_action"
                value="save_draft"
                class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-slate-100 px-6 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-200 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2">
            Save Draft
        </button>
        <button type="submit"
                name="form_action"
                value="submit_proposal"
                class="inline-flex items-center justify-center rounded-lg bg-emerald-800 px-6 py-3 text-sm font-semibold text-white shadow-md transition hover:scale-[1.02] hover:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2">
            Submit Proposal to Director
        </button>
    </div>
</form>
