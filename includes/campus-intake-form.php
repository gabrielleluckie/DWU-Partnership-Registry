<?php

/**
 * Campus Admin — Proposed Partnership Submission Form (Bootstrap 5 Single-Page)
 *
 * Expected: $user (array), $draft (array)
 */

$draft = $draft ?? [];
$today = date('Y-m-d');
$logoUrl = assetUrl('assets/images/dwu_logo.jpg');

$field = static function (string $name, string $default = '', array $legacy = []) use ($draft): string {
    if (isset($draft[$name]) && !is_array($draft[$name])) {
        return (string) $draft[$name];
    }
    foreach ($legacy as $legacyName) {
        if (isset($draft[$legacyName]) && !is_array($draft[$legacyName])) {
            return (string) $draft[$legacyName];
        }
    }
    return $default;
};

$campusDefault = $field('campus', '', ['submitter_campus']);
if ($campusDefault === '' && !empty($user['campus'])) {
    $campusMap = [
        'Madang' => 'Madang (Main)', 'Rabaul' => 'Rabaul', 'Wewak' => 'Sepik (Wewak)',
        'Sepik' => 'Sepik (Wewak)', 'Port Moresby' => 'Port Moresby',
        'Hagen' => 'Mt. Hagen', 'Mt. Hagen' => 'Mt. Hagen',
    ];
    foreach ($campusMap as $needle => $value) {
        if (stripos($user['campus'], $needle) !== false) {
            $campusDefault = $value;
            break;
        }
    }
}

$checked = static function (string $name, string $value, array $legacy = []) use ($draft, $campusDefault): string {
    if ($name === 'campus' && empty($draft['campus']) && empty($draft['submitter_campus']) && $campusDefault !== '') {
        return $campusDefault === $value ? 'checked' : '';
    }
    $current = $draft[$name] ?? null;
    if ($current === null) {
        foreach ($legacy as $legacyName) {
            if (isset($draft[$legacyName])) { $current = $draft[$legacyName]; break; }
        }
    }
    if (is_array($current)) return in_array($value, $current, true) ? 'checked' : '';
    return (string) $current === $value ? 'checked' : '';
};

$selected = static function (string $name, string $value, array $legacy = []) use ($draft): string {
    $current = $draft[$name] ?? '';
    if ($current === '') {
        foreach ($legacy as $legacyName) {
            if (!empty($draft[$legacyName])) { $current = $draft[$legacyName]; break; }
        }
    }
    return (string) $current === $value ? 'selected' : '';
};

$arrayChecked = static function (string $name, string $value, array $legacy = []) use ($draft): string {
    $current = $draft[$name] ?? null;
    if (!is_array($current)) {
        foreach ($legacy as $legacyName) {
            if (isset($draft[$legacyName]) && is_array($draft[$legacyName])) { $current = $draft[$legacyName]; break; }
        }
    }
    return is_array($current) && in_array($value, $current, true) ? 'checked' : '';
};

$slug = static fn (string $s): string => preg_replace('/[^a-z0-9]+/i', '_', strtolower($s));

$req = '<span class="text-danger">*</span>';

$jumpSections = [
    'a' => ['label' => 'Submitter',           'icon' => 'bi-person-badge'],
    'b' => ['label' => 'Partner',             'icon' => 'bi-building'],
    'c' => ['label' => 'Partnership Type',    'icon' => 'bi-file-earmark-text'],
    'd' => ['label' => 'DWU Commitments',     'icon' => 'bi-arrow-up-circle'],
    'e' => ['label' => 'Partner Contributions','icon' => 'bi-gift'],
    'f' => ['label' => 'Duration',            'icon' => 'bi-calendar-range'],
    'g' => ['label' => 'Benefits',            'icon' => 'bi-graph-up-arrow'],
    'h' => ['label' => 'Documents',           'icon' => 'bi-paperclip'],
    'i' => ['label' => 'Declaration',         'icon' => 'bi-pen'],
    'j' => ['label' => 'Comments',            'icon' => 'bi-chat-dots'],
];

$sectionIcons = [
    'a' => 'bi-person-badge', 'b' => 'bi-building', 'c' => 'bi-file-earmark-text',
    'd' => 'bi-arrow-up-circle', 'e' => 'bi-gift', 'f' => 'bi-calendar-range',
    'g' => 'bi-graph-up-arrow', 'h' => 'bi-paperclip', 'i' => 'bi-pen', 'j' => 'bi-chat-dots',
];

$campuses = ['Madang (Main)', 'Rabaul', 'Sepik (Wewak)', 'Port Moresby', 'Mt. Hagen'];
$partnerTypes = ['University/Academic', 'Govt Agency', 'NGO', 'Private Sector/Industry', "Int'l Org", 'Community Org', 'Other'];
$agreementTypes = [
    'Memorandum of Understanding (MOU)', 'Memorandum of Agreement (MOA)',
    'Service Agreement', 'Formal Contract', 'Arrangement', 'Other',
];
$partnershipNatures = [
    'Academic/Twinning', 'Research Collaboration', 'Student Exchange/Placement',
    'Community Engagement', 'Funded Programme (e.g., DFAT, AusAID)', 'Industry/Workforce Training', 'Other',
];
$docTypes = [
    'doc_mou' => 'Draft MOU/MOA', 'doc_loi' => 'Letter of Intent', 'doc_profile' => 'Partner Profile',
    'doc_budget' => 'Budget Breakdown', 'doc_correspond' => 'Correspondence',
    'doc_background' => 'Background Info', 'doc_other' => 'Other',
];

$renderSectionHeader = static function (string $letter, string $title, string $subtitle, string $icon): void {
    ?>
    <div class="section-header-band">
        <div class="section-header-icon"><i class="bi <?= e($icon) ?>"></i></div>
        <div>
            <p class="section-header-subtitle">Section <?= strtoupper($letter) ?></p>
            <h2 class="section-header-title h6"><?= e($title) ?></h2>
            <p class="text-muted small mb-0"><?= e($subtitle) ?></p>
        </div>
    </div>
    <?php
};
?>

<link rel="stylesheet" href="<?= e(assetUrl('css/campus-intake-form.css')) ?>">

<div class="partnership-form-shell p-2 p-lg-3">
<div class="row g-3">

    <!-- Sticky vertical stepper / jump nav -->
    <div class="col-lg-2 col-xl-2">
        <div class="partnership-stepper-card partnership-stepper-card sticky-top rounded-3 shadow-sm p-2">
            <p class="text-uppercase text-muted fw-bold mb-1 px-1 partnership-stepper-label">
                <i class="bi bi-signpost-split me-1"></i> Jump to Section
            </p>
            <nav id="partnership-scrollspy" class="nav nav-pills flex-column">
                <?php foreach ($jumpSections as $key => $sec): ?>
                    <a class="nav-link" href="#section-<?= e($key) ?>">
                        <span class="section-letter-badge"><?= strtoupper($key) ?></span>
                        <i class="bi <?= e($sec['icon']) ?> stepper-icon"></i>
                        <span class="stepper-label"><?= e($sec['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </div>

    <!-- Main form column -->
    <div class="col-lg-10 col-xl-10">
        <form id="partnershipForm"
              action="dashboard_campus_admin.php?tab=submit"
              method="post"
              enctype="multipart/form-data"
              novalidate>

            <!-- Partnership form header — Bootstrap navbar -->
            <header class="partnership-form-header mb-2">
                <nav class="navbar navbar-expand-lg navbar-dark partnership-navbar rounded-3 shadow-sm">
                    <div class="container-fluid partnership-navbar-inner px-3 py-2">

                        <!-- Brand: logo + university titles -->
                        <a class="navbar-brand partnership-navbar-brand d-flex align-items-center gap-2 me-lg-3"
                           href="#section-a">
                            <img src="<?= e($logoUrl) ?>" alt="Divine Word University — 30th Anniversary"
                                 class="partnership-navbar-logo">
                            <span class="partnership-brand-text">
                                <span class="partnership-brand-division d-block">
                                    Divine Word University — Partnership Division (Madang, PNG)
                                </span>
                            </span>
                        </a>

                        <!-- Mobile toggle -->
                        <button class="navbar-toggler partnership-navbar-toggler ms-auto"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#partnershipNavbarCollapse"
                                aria-controls="partnershipNavbarCollapse"
                                aria-expanded="false"
                                aria-label="Toggle form header details">
                            <span class="navbar-toggler-icon"></span>
                        </button>

                        <!-- Collapsible title section -->
                        <div class="collapse navbar-collapse partnership-navbar-collapse"
                             id="partnershipNavbarCollapse">
                            <div class="navbar-nav ms-lg-auto align-items-lg-end text-lg-end py-2 py-lg-0">
                                <div class="partnership-title-block">
                                    <h2 class="partnership-form-title h6 fw-bold mb-1 mb-lg-0">
                                        Proposed Partnership Submission Form
                                    </h2>
                                    <span class="badge rounded-pill partnership-pdmis-badge">
                                        <i class="bi bi-shield-check me-1" aria-hidden="true"></i>
                                        PDMIS System Entry
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </nav>

                <!-- Instruction bar -->
                <div class="alert alert-dark partnership-instruction-bar alert-dismissible fade show rounded-3 rounded-top-0 mb-0 shadow-sm"
                     role="alert"
                     id="partnershipInstructionBar">
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-info-circle-fill partnership-instruction-icon flex-shrink-0" aria-hidden="true"></i>
                        <p class="mb-0 small partnership-instruction-text">
                            Complete all sections continuously from top to bottom. Fields marked <?= $req ?> are required.
                        </p>
                    </div>
                    <button type="button"
                            class="btn-close btn-close-white partnership-instruction-close"
                            data-bs-dismiss="alert"
                            aria-label="Dismiss instructions"></button>
                </div>
            </header>

            <div id="formErrorBanner" class="alert alert-danger form-error-banner d-none" role="alert"></div>

            <!-- Section A -->
            <section id="section-a" class="card form-section border-0 shadow-sm rounded-3 mb-3">
                <div class="card-body p-3">
                    <?php $renderSectionHeader('a', 'Submitter Details', 'Campus staff submitting this proposal', $sectionIcons['a']); ?>
                    <div class="row g-3">
                        <div class="col-md-6 col-lg-4">
                            <label class="form-label fw-semibold text-secondary small" for="staff_name">Staff Name <?= $req ?></label>
                            <input type="text" class="form-control" id="staff_name" name="staff_name" required
                                   value="<?= e($field('staff_name', $user['name'] ?? '', ['submitter_name'])) ?>">
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <label class="form-label fw-semibold text-secondary small" for="department">Department / Faculty <?= $req ?></label>
                            <input type="text" class="form-control" id="department" name="department" required
                                   value="<?= e($field('department', $user['department'] ?? '', ['submitter_department'])) ?>">
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <label class="form-label fw-semibold text-secondary small" for="submission_date">Submission Date <?= $req ?></label>
                            <input type="date" class="form-control" id="submission_date" name="submission_date" required
                                   value="<?= e($field('submission_date', $today)) ?>">
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <label class="form-label fw-semibold text-secondary small" for="email">Email <?= $req ?></label>
                            <input type="email" class="form-control" id="email" name="email" required
                                   value="<?= e($field('email', $user['email'] ?? '', ['submitter_email'])) ?>">
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <label class="form-label fw-semibold text-secondary small" for="phone">Phone <span class="text-muted fw-normal">(Optional)</span></label>
                            <input type="tel" class="form-control" id="phone" name="phone" placeholder="+675 XXX XXXX"
                                   value="<?= e($field('phone', '', ['submitter_phone'])) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold text-secondary small d-block mb-2">Campus <?= $req ?></label>
                            <div class="row g-2">
                                <?php foreach ($campuses as $campus):
                                    $cid = 'campus_' . $slug($campus);
                                ?>
                                    <div class="col-sm-6 col-lg-4">
                                        <input type="radio" class="btn-check" name="campus" id="<?= e($cid) ?>"
                                               value="<?= e($campus) ?>" required <?= $checked('campus', $campus, ['submitter_campus']) ?>>
                                        <label class="btn selection-tile w-100" for="<?= e($cid) ?>"><?= e($campus) ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Section B -->
            <section id="section-b" class="card form-section border-0 shadow-sm rounded-3 mb-3">
                <div class="card-body p-3">
                    <?php $renderSectionHeader('b', 'Partner Organisation Details', 'Legal entity and primary contact information', $sectionIcons['b']); ?>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold text-secondary small" for="partner_name">Partner Legal Name <?= $req ?></label>
                            <input type="text" class="form-control" id="partner_name" name="partner_name" required
                                   value="<?= e($field('partner_name', '', ['partner_legal_name'])) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small" for="partner_type">Organisation Type <?= $req ?></label>
                            <select class="form-select" id="partner_type" name="partner_type" required data-partner-type-select>
                                <option value="">Select type...</option>
                                <?php foreach ($partnerTypes as $type): ?>
                                    <option value="<?= e($type) ?>" <?= $selected('partner_type', $type) ?>><?= e($type) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 d-none" id="partnerTypeOtherWrap">
                            <label class="form-label fw-semibold text-secondary small" for="partner_type_other">Specify Other Type</label>
                            <input type="text" class="form-control" id="partner_type_other" name="partner_type_other"
                                   value="<?= e($field('partner_type_other', '', ['org_type_other'])) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small" for="partner_location">Location (Country &amp; City/Province) <?= $req ?></label>
                            <input type="text" class="form-control" id="partner_location" name="partner_location" required
                                   placeholder="e.g. Australia, Sydney" value="<?= e($field('partner_location')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small" for="partner_website">Website <span class="text-muted fw-normal">(Optional)</span></label>
                            <input type="url" class="form-control" id="partner_website" name="partner_website" placeholder="https://"
                                   value="<?= e($field('partner_website')) ?>">
                        </div>
                    </div>
                    <div class="mt-3 p-3 rounded-3 bg-light border">
                        <h3 class="h6 fw-bold text-uppercase text-secondary mb-3" style="font-size:0.7rem;letter-spacing:0.06em;">
                            <i class="bi bi-person-lines-fill me-1"></i> Primary Contact Person
                        </h3>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary small" for="contact_person_name">Name <?= $req ?></label>
                                <input type="text" class="form-control" id="contact_person_name" name="contact_person_name" required
                                       value="<?= e($field('contact_person_name', '', ['contact_name'])) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary small" for="contact_person_title">Title / Position <?= $req ?></label>
                                <input type="text" class="form-control" id="contact_person_title" name="contact_person_title" required
                                       value="<?= e($field('contact_person_title', '', ['contact_title'])) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary small" for="contact_person_email">Email <?= $req ?></label>
                                <input type="email" class="form-control" id="contact_person_email" name="contact_person_email" required
                                       value="<?= e($field('contact_person_email', '', ['contact_email'])) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary small" for="contact_person_phone">Phone <span class="text-muted fw-normal">(Optional)</span></label>
                                <input type="tel" class="form-control" id="contact_person_phone" name="contact_person_phone"
                                       value="<?= e($field('contact_person_phone', '', ['contact_phone'])) ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Section C -->
            <section id="section-c" class="card form-section border-0 shadow-sm rounded-3 mb-3">
                <div class="card-body p-3">
                    <?php $renderSectionHeader('c', 'Type & Nature of Partnership', 'Agreement classification and partnership scope', $sectionIcons['c']); ?>
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <label class="form-label fw-semibold text-secondary small" for="agreement_type">Agreement Type <?= $req ?></label>
                            <select class="form-select" id="agreement_type" name="agreement_type" required>
                                <option value="">Select agreement type...</option>
                                <?php foreach ($agreementTypes as $type): ?>
                                    <option value="<?= e($type) ?>" <?= $selected('agreement_type', $type) ?>><?= e($type) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-lg-6" id="partnershipNatureGroup">
                            <label class="form-label fw-semibold text-secondary small d-block">Partnership Nature <?= $req ?></label>
                            <p class="text-muted small mb-2">Select all that apply</p>
                            <div class="row g-2">
                                <?php foreach ($partnershipNatures as $nature):
                                    $nid = 'nature_' . $slug($nature);
                                ?>
                                    <div class="col-12">
                                        <input type="checkbox" class="btn-check partnership-nature-cb" name="partnership_nature[]"
                                               id="<?= e($nid) ?>" value="<?= e($nature) ?>"
                                               data-nature-value="<?= e($nature) ?>"
                                               <?= $arrayChecked('partnership_nature', $nature, ['partnership_types']) ?>>
                                        <label class="btn selection-tile selection-tile-check w-100" for="<?= e($nid) ?>"><?= e($nature) ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-12 d-none" id="partnershipNatureOtherWrap">
                            <label class="form-label fw-semibold text-secondary small" for="partnership_nature_other">Specify Other Partnership Nature</label>
                            <input type="text" class="form-control" id="partnership_nature_other" name="partnership_nature_other"
                                   value="<?= e($field('partnership_nature_other')) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold text-secondary small" for="partnership_description">Partnership Description <?= $req ?></label>
                            <p class="text-muted small mb-1">3 to 5 sentences explaining purpose &amp; benefits</p>
                            <textarea class="form-control" id="partnership_description" name="partnership_description" required rows="4"
                                      placeholder="Summarise the purpose, scope, and expected outcomes."><?= e($field('partnership_description')) ?></textarea>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Section D -->
            <section id="section-d" class="card form-section border-0 shadow-sm rounded-3 mb-3">
                <div class="card-body p-3">
                    <?php $renderSectionHeader('d', 'What DWU is Committing To', 'Resources, obligations, and financial commitments from DWU', $sectionIcons['d']); ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small" for="dwu_commitments">DWU Commitments <?= $req ?></label>
                        <textarea class="form-control" id="dwu_commitments" name="dwu_commitments" required rows="4"
                                  placeholder="Resources, staff time, facilities, or services DWU will provide."><?= e($field('dwu_commitments')) ?></textarea>
                    </div>
                    <label class="form-label fw-semibold text-secondary small d-block mb-2">Financial Commitment Required? <?= $req ?></label>
                    <div class="row g-2 mb-3">
                        <?php foreach (['Yes', 'No', 'Not yet confirmed'] as $option):
                            $oid = 'dwu_fin_' . $slug($option);
                        ?>
                            <div class="col-sm-4">
                                <input type="radio" class="btn-check" name="dwu_financial_commitment" id="<?= e($oid) ?>"
                                       value="<?= e($option) ?>" required data-dwu-commitment-toggle
                                       <?= $checked('dwu_financial_commitment', $option, ['dwu_financial_spend']) ?>>
                                <label class="btn selection-tile w-100" for="<?= e($oid) ?>"><?= e($option) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="d-none mb-3 conditional-panel" id="dwuCostWrap">
                        <label class="form-label fw-semibold text-secondary small" for="dwu_estimated_cost">Estimated Cost (PGK/USD) <?= $req ?></label>
                        <input type="text" class="form-control" id="dwu_estimated_cost" name="dwu_estimated_cost"
                               placeholder="e.g. PGK 25,000" value="<?= e($field('dwu_estimated_cost')) ?>">
                    </div>
                    <div>
                        <label class="form-label fw-semibold text-secondary small" for="dwu_responsible_unit">Responsible Faculty / Dept / Unit <?= $req ?></label>
                        <input type="text" class="form-control" id="dwu_responsible_unit" name="dwu_responsible_unit" required
                               value="<?= e($field('dwu_responsible_unit', '', ['dwu_responsible_department'])) ?>">
                    </div>
                </div>
            </section>

            <!-- Section E -->
            <section id="section-e" class="card form-section border-0 shadow-sm rounded-3 mb-3">
                <div class="card-body p-3">
                    <?php $renderSectionHeader('e', 'What the Partner Organisation is Contributing', 'In-kind support, funding, and conditions from the partner', $sectionIcons['e']); ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small" for="partner_contributions">Partner Contributions <?= $req ?></label>
                        <textarea class="form-control" id="partner_contributions" name="partner_contributions" required rows="4"
                                  placeholder="Funding, expertise, equipment, or in-kind support from the partner."><?= e($field('partner_contributions')) ?></textarea>
                    </div>
                    <label class="form-label fw-semibold text-secondary small d-block mb-2">Partner Contributing Funding? <?= $req ?></label>
                    <div class="row g-2 mb-3">
                        <?php foreach (['Yes', 'No', 'Not yet confirmed'] as $option):
                            $oid = 'partner_fin_' . $slug($option);
                        ?>
                            <div class="col-sm-4">
                                <input type="radio" class="btn-check" name="partner_financial_contribution" id="<?= e($oid) ?>"
                                       value="<?= e($option) ?>" required data-partner-contribution-toggle
                                       <?= $checked('partner_financial_contribution', $option, ['partner_funding']) ?>>
                                <label class="btn selection-tile w-100" for="<?= e($oid) ?>"><?= e($option) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="d-none conditional-panel p-3 rounded-3 border bg-light" id="partnerFundingWrap">
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-secondary small" for="partner_funding_amount">Amount <?= $req ?></label>
                                <input type="number" class="form-control" id="partner_funding_amount" name="partner_funding_amount" min="0" step="0.01"
                                       value="<?= e($field('partner_funding_amount')) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-secondary small" for="partner_funding_currency">Currency <?= $req ?></label>
                                <select class="form-select" id="partner_funding_currency" name="partner_funding_currency">
                                    <option value="">Select currency...</option>
                                    <?php foreach (['PGK', 'USD', 'AUD', 'Other'] as $currency): ?>
                                        <option value="<?= e($currency) ?>" <?= $selected('partner_funding_currency', $currency) ?>><?= e($currency) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-secondary small" for="partner_payment_timing">Payment Method <?= $req ?></label>
                                <select class="form-select" id="partner_payment_timing" name="partner_payment_timing">
                                    <option value="">Select timing...</option>
                                    <?php foreach (['One-off payment', 'Milestones', 'Annual'] as $timing): ?>
                                        <option value="<?= e($timing) ?>" <?= $selected('partner_payment_timing', $timing, ['partner_payment_method']) ?>><?= e($timing) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="form-label fw-semibold text-secondary small" for="partner_contribution_conditions">
                                Contribution Conditions <span class="text-muted fw-normal">(Optional)</span>
                            </label>
                            <textarea class="form-control" id="partner_contribution_conditions" name="partner_contribution_conditions" rows="3"
                                      placeholder="e.g. co-branding, reporting requirements"><?= e($field('partner_contribution_conditions', '', ['partner_conditions'])) ?></textarea>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Section F -->
            <section id="section-f" class="card form-section border-0 shadow-sm rounded-3 mb-3">
                <div class="card-body p-3">
                    <?php $renderSectionHeader('f', 'Duration & Timeline', 'Proposed start/end dates and renewal options', $sectionIcons['f']); ?>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-secondary small" for="start_date">Proposed Start Date <?= $req ?></label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required
                                   value="<?= e($field('start_date', '', ['proposed_start_date'])) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-secondary small" for="end_date">Proposed End Date <?= $req ?></label>
                            <input type="date" class="form-control" id="end_date" name="end_date" required
                                   value="<?= e($field('end_date', '', ['proposed_end_date'])) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-secondary small" for="total_duration">Total Duration <?= $req ?></label>
                            <input type="text" class="form-control bg-light" id="total_duration" name="total_duration" required readonly
                                   placeholder="Auto-calculated" value="<?= e($field('total_duration')) ?>">
                            <div class="form-text">Calculated automatically from start and end dates</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold text-secondary small d-block mb-2">Renewal Option <?= $req ?></label>
                            <div class="row g-2">
                                <?php foreach (['Yes (planned)' => 'Yes (planned)', 'No (one-off)' => 'No (one-off)', 'Not yet discussed' => 'Not yet discussed'] as $value => $label):
                                    $rid = 'renew_' . $slug($value);
                                ?>
                                    <div class="col-sm-4">
                                        <input type="radio" class="btn-check" name="renewal_option" id="<?= e($rid) ?>"
                                               value="<?= e($value) ?>" required <?= $checked('renewal_option', $value, ['renew_option']) ?>>
                                        <label class="btn selection-tile w-100" for="<?= e($rid) ?>"><?= e($label) ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold text-secondary small" for="key_milestones">
                                Key Milestones <span class="text-muted fw-normal">(Optional)</span>
                            </label>
                            <textarea class="form-control" id="key_milestones" name="key_milestones" rows="3"
                                      placeholder="Key activities, deliverables, or review points."><?= e($field('key_milestones', '', ['milestones'])) ?></textarea>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Section G -->
            <section id="section-g" class="card form-section border-0 shadow-sm rounded-3 mb-3">
                <div class="card-body p-3">
                    <?php $renderSectionHeader('g', 'Benefits to DWU & Justification', 'Strategic value, alignment, and risk assessment', $sectionIcons['g']); ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small" for="dwu_benefits">Benefits to DWU <?= $req ?></label>
                        <textarea class="form-control" id="dwu_benefits" name="dwu_benefits" required rows="3"
                                  placeholder="Student opportunities, research capacity, community impact, etc."><?= e($field('dwu_benefits', '', ['main_benefits'])) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small" for="strategic_alignment">Strategic Alignment <?= $req ?></label>
                        <textarea class="form-control" id="strategic_alignment" name="strategic_alignment" required rows="3"
                                  placeholder="Strategic goals and institutional priorities supported."><?= e($field('strategic_alignment', '', ['strategic_goals'])) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small" for="perceived_risks">
                            Perceived Risks <span class="text-muted fw-normal">(Optional)</span>
                        </label>
                        <textarea class="form-control" id="perceived_risks" name="perceived_risks" rows="3"
                                  placeholder="Financial, legal, reputational, or operational risks."><?= e($field('perceived_risks', '', ['risks_concerns'])) ?></textarea>
                    </div>
                    <label class="form-label fw-semibold text-secondary small d-block mb-2">Previous Partnerships with this Organisation? <?= $req ?></label>
                    <div class="row g-2 mb-3">
                        <?php foreach (['Yes' => 'Yes', 'No' => 'No', 'Not sure' => 'Not sure'] as $value => $label):
                            $pid = 'prev_' . $slug($value);
                        ?>
                            <div class="col-sm-4">
                                <input type="radio" class="btn-check" name="previous_engagement" id="<?= e($pid) ?>"
                                       value="<?= e($value) ?>" required data-previous-engagement-toggle
                                       <?= $checked('previous_engagement', $value, ['similar_partnerships']) ?>>
                                <label class="btn selection-tile w-100" for="<?= e($pid) ?>"><?= e($label) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="d-none conditional-panel" id="previousEngagementWrap">
                        <label class="form-label fw-semibold text-secondary small" for="previous_engagement_details">Previous Engagement Details <?= $req ?></label>
                        <textarea class="form-control" id="previous_engagement_details" name="previous_engagement_details" rows="3"
                                  placeholder="Describe the nature and outcome of prior engagement."><?= e($field('previous_engagement_details', '', ['similar_partnerships_detail'])) ?></textarea>
                    </div>
                </div>
            </section>

            <!-- Section H -->
            <section id="section-h" class="card form-section border-0 shadow-sm rounded-3 mb-3">
                <div class="card-body p-3">
                    <?php $renderSectionHeader('h', 'Supporting Documents', 'Attach draft agreements, profiles, and correspondence', $sectionIcons['h']); ?>
                    <div id="masterDropZone" class="drop-zone p-3 text-center mb-3" tabindex="0" role="button"
                         aria-label="Drag and drop files or click to browse">
                        <i class="bi bi-cloud-arrow-up display-5 text-primary mb-2 d-block"></i>
                        <p class="fw-semibold text-dark mb-1">Drag &amp; drop files here</p>
                        <p class="text-muted small mb-0">PDF, DOCX, PNG, or JPG — then assign to document types below</p>
                    </div>
                    <div class="row g-3">
                        <?php foreach ($docTypes as $key => $label):
                            $isDocChecked = !empty($draft[$key . '_enabled']);
                        ?>
                            <div class="col-md-6">
                                <div class="border rounded-3 p-3 h-100 bg-white">
                                    <div class="form-check">
                                        <input class="form-check-input doc-toggle" type="checkbox"
                                               name="<?= e($key) ?>_enabled" value="1" id="<?= e($key) ?>_chk"
                                               data-doc-target="<?= e($key) ?>" <?= $isDocChecked ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-semibold small" for="<?= e($key) ?>_chk"><?= e($label) ?></label>
                                    </div>
                                    <div id="<?= e($key) ?>Upload" class="doc-upload mt-2 <?= $isDocChecked ? '' : 'd-none' ?>">
                                        <label class="drop-zone d-flex flex-column align-items-center justify-content-center p-3 mb-0">
                                            <span class="small fw-medium text-primary">Click or drop file</span>
                                            <input type="file" name="<?= e($key) ?>_file" class="d-none doc-file-input" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg">
                                        </label>
                                        <p class="doc-filename text-muted small text-truncate mb-0 mt-1"></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <!-- Section I -->
            <section id="section-i" class="card form-section border-0 shadow-sm rounded-3 mb-3">
                <div class="card-body p-3">
                    <?php $renderSectionHeader('i', 'Declaration by Staff', 'Accuracy confirmation and sign-off details', $sectionIcons['i']); ?>
                    <div class="form-check border rounded-3 p-2 mb-3 bg-light">
                        <input class="form-check-input" type="checkbox" id="staff_declaration_agree" name="staff_declaration_agree" value="1"
                               <?= !empty($draft['staff_declaration_agree']) || !empty($draft['declaration_confirm']) ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="staff_declaration_agree">
                            I confirm that the information provided is accurate to the best of my knowledge and understand that
                            Presidential sign-off is required before any partnership agreement is finalised.
                        </label>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-secondary small" for="signee_name">Signee Name <?= $req ?></label>
                            <input type="text" class="form-control" id="signee_name" name="signee_name"
                                   value="<?= e($field('signee_name', $user['name'] ?? '', ['declaration_name'])) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-secondary small" for="signee_campus">Signee Campus <?= $req ?></label>
                            <input type="text" class="form-control" id="signee_campus" name="signee_campus"
                                   value="<?= e($field('signee_campus', $user['campus'] ?? '', ['declaration_campus'])) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-secondary small" for="signee_date">Signee Date <?= $req ?></label>
                            <input type="date" class="form-control" id="signee_date" name="signee_date"
                                   value="<?= e($field('signee_date', $today, ['declaration_date'])) ?>">
                        </div>
                    </div>
                </div>
            </section>

            <!-- Section J -->
            <section id="section-j" class="card form-section border-0 shadow-sm rounded-3 mb-3">
                <div class="card-body p-3">
                    <?php $renderSectionHeader('j', 'Review & Comments', 'Campus admin notes and director review preview', $sectionIcons['j']); ?>
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="admin-comment-card p-3 h-100">
                                <span class="card-header-badge mb-3"><i class="bi bi-chat-square-text"></i> Campus Admin</span>
                                <h3 class="h6 fw-bold mb-1">Campus Admin Comments</h3>
                                <p class="text-muted small mb-3">Add justification or notes before submitting to the Director</p>
                                <label class="form-label fw-semibold text-secondary small" for="campus_admin_comments">Your Comments</label>
                                <textarea class="form-control" id="campus_admin_comments" name="campus_admin_comments" rows="5"
                                          placeholder="Provide context, urgency, or campus-level endorsement notes."><?= e($field('campus_admin_comments')) ?></textarea>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="director-review-card p-3 h-100">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h3 class="h6 fw-bold mb-1"><i class="bi bi-shield-check me-1"></i> Partnership Director Review</h3>
                                        <p class="text-muted small mb-0">Read-only preview — completed after submission</p>
                                    </div>
                                    <span class="status-pill-pending">Pending Approval</span>
                                </div>
                                <input type="hidden" name="director_decision_status" value="pending">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold text-secondary small" for="director_comments">Director Comments</label>
                                    <textarea class="form-control bg-white" id="director_comments" name="director_comments" rows="4"
                                              readonly disabled placeholder="Reserved for Partnership Director feedback."><?= e($field('director_comments')) ?></textarea>
                                </div>
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <label class="form-label fw-semibold text-secondary small" for="director_name">Director Name</label>
                                        <input type="text" class="form-control bg-white" id="director_name" name="director_name"
                                               readonly disabled value="<?= e($field('director_name')) ?>" placeholder="—">
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label fw-semibold text-secondary small" for="director_decision_date">Decision Date</label>
                                        <input type="date" class="form-control bg-white" id="director_decision_date" name="director_decision_date"
                                               readonly disabled value="<?= e($field('director_decision_date')) ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Compact sticky action bar -->
            <div class="partnership-action-bar sticky-bottom py-2 px-3">
                <div class="d-flex flex-row flex-wrap gap-2 justify-content-end align-items-center">
                    <button type="submit" name="form_action" value="save_draft"
                            class="btn btn-sm btn-outline-secondary fw-semibold">
                        <i class="bi bi-save me-1"></i> Save Draft
                    </button>
                    <button type="submit" name="form_action" value="submit_proposal"
                            class="btn btn-sm btn-primary">
                        <i class="bi bi-send me-1"></i> Submit Proposal to Director
                    </button>
                </div>
            </div>

        </form>
    </div>
</div>
</div>
