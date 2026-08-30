<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/guard.php';

$user = requireRole($pdo, [ROLE_PARTNERSHIP_DIRECTOR]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'register_agreement') {
        try {
            $documentPath = storeUploadedAgreementPdf($_FILES['agreement_pdf'] ?? []);

            $agreementId = registerActivePartnership($pdo, [
                'partner_mode'        => (string) ($_POST['partner_mode'] ?? 'existing'),
                'partner_id'          => (int) ($_POST['partner_id'] ?? 0),
                'partner_name'        => trim((string) ($_POST['partner_name'] ?? '')),
                'partner_country'     => trim((string) ($_POST['partner_country'] ?? '')),
                'partner_address'     => trim((string) ($_POST['partner_address'] ?? '')),
                'partner_website'     => trim((string) ($_POST['partner_website'] ?? '')),
                'campus_id'           => (int) ($_POST['campus_id'] ?? 0),
                'contact_name'        => trim((string) ($_POST['contact_name'] ?? '')),
                'contact_designation' => trim((string) ($_POST['contact_designation'] ?? '')),
                'contact_email'       => trim((string) ($_POST['contact_email'] ?? '')),
                'contact_phone'       => trim((string) ($_POST['contact_phone'] ?? '')),
                'contact_fax'         => trim((string) ($_POST['contact_fax'] ?? '')),
                'partnership_type'    => trim((string) ($_POST['partnership_type'] ?? '')),
                'agreement_type'      => trim((string) ($_POST['agreement_type'] ?? '')),
                'signed_date'         => trim((string) ($_POST['signed_date'] ?? '')),
                'expiry_date'         => trim((string) ($_POST['expiry_date'] ?? '')),
                'scope_description'   => trim((string) ($_POST['scope_description'] ?? '')),
                'document_path'       => $documentPath,
            ], (int) $user['id'], $user['name']);

            setFlash('success', 'Agreement #' . $agreementId . ' registered successfully in the live registry.');
        } catch (Throwable $exception) {
            setFlash('error', $exception->getMessage());
        }

        redirect(directorRegisterPath());
    }
}

$pendingProposals = fetchSubmittedProposals($pdo);
$pendingCount = count($pendingProposals);
$partners = fetchPartners($pdo);
$campuses = fetchCampuses($pdo);
$directorEntryFormAction = directorRegisterPath();

renderDirectorDashboardHeader(
    $user,
    'Active Partnership Entry Form',
    $pendingProposals,
    $pendingCount,
    [
        'pageSubtitle'     => 'The only path to register signed agreements in the live registry with Status = Active.',
        'extraStylesheets' => [assetUrl('css/director-partnership-entry-form.css')],
    ]
);

renderDashboardLogoutAction();
renderDirectorSubnav('register', $pendingCount);
?>

<div class="director-register-page">
    <?php renderDirectorFlashMessages(); ?>
    <section class="director-entry-form-panel director-panel">
        <div class="director-panel-header">
            <h1>Active Partnership Entry Form</h1>
            <p>The only path to register signed agreements in the live registry with Status = Active.</p>
        </div>

        <?php require __DIR__ . '/includes/views/director-partnership-entry-form.php'; ?>
    </section>
</div>

<?php renderDirectorDashboardFooter(); ?>
