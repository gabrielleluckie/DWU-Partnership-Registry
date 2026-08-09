<?php

declare(strict_types=1);

use App\Models\Agreement;

/**
 * Resolve registry table names (supports singular live schema and legacy plural names).
 *
 * @return array{agreement: ?string, partner: ?string, contact: ?string, campus: string, agreement_history: ?string}
 */
function registryTableNames(PDO $pdo): array
{
    static $names = null;

    if ($names !== null) {
        return $names;
    }

    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $lower = array_map('strtolower', $tables);

    $resolve = static function (string $singular, string $plural) use ($tables, $lower): ?string {
        $singularKey = array_search(strtolower($singular), $lower, true);
        if ($singularKey !== false) {
            return $tables[$singularKey];
        }

        $pluralKey = array_search(strtolower($plural), $lower, true);

        return $pluralKey !== false ? $tables[$pluralKey] : null;
    };

    $names = [
        'agreement'         => $resolve('agreement', 'agreements'),
        'partner'           => $resolve('partner', 'partners'),
        'contact'           => $resolve('contact', 'contacts'),
        'campus'            => campusTableName($pdo),
        'agreement_history' => $resolve('agreement_history', 'agreement_history'),
    ];

    return $names;
}

function agreementTableName(PDO $pdo): ?string
{
    return registryTableNames($pdo)['agreement'];
}

function partnerTableName(PDO $pdo): ?string
{
    return registryTableNames($pdo)['partner'];
}

function contactTableName(PDO $pdo): ?string
{
    return registryTableNames($pdo)['contact'];
}

function partnerHasSoftDelete(PDO $pdo): bool
{
    static $has = null;

    if ($has !== null) {
        return $has;
    }

    $table = partnerTableName($pdo);

    if ($table === null) {
        $has = false;

        return $has;
    }

    $columns = $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_COLUMN);
    $has = in_array('Is_Deleted', $columns, true);

    return $has;
}

function badgeClass(string $status): string
{
    return match ($status) {
        Agreement::STATUS_ACTIVE         => 'active',
        Agreement::STATUS_EXPIRED        => 'expired',
        Agreement::STATUS_EXPIRING_SOON => 'soon',
        'Soon to Expire'                 => 'soon',
        default                          => 'soon',
    };
}

function computeAgreementStatus(string $expiryDate): string
{
    return Agreement::calculatedStatusFromExpiry($expiryDate);
}

function resolveAgreementDisplayStatus(?string $expiryDate, ?string $dbStatus = null): string
{
    if ($expiryDate !== null && $expiryDate !== '') {
        return Agreement::calculatedStatusFromExpiry($expiryDate);
    }

    return match ($dbStatus) {
        'Expired'        => Agreement::STATUS_EXPIRED,
        'Expiring Soon'  => Agreement::STATUS_EXPIRING_SOON,
        'Active'         => Agreement::STATUS_ACTIVE,
        default          => Agreement::STATUS_ACTIVE,
    };
}

function enrichAgreementRow(array $row): array
{
    $model = Agreement::fromRow($row);

    return array_merge($row, $model->toListingArray());
}

function campusTableName(PDO $pdo): string
{
    static $name = null;

    if ($name !== null) {
        return $name;
    }

    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $name = in_array('campus', $tables, true) ? 'campus' : 'campuses';

    return $name;
}

function fetchCampuses(PDO $pdo): array
{
    $table = campusTableName($pdo);
    $stmt = $pdo->query("SELECT Campus_ID, Name, Province FROM `{$table}` ORDER BY Name ASC");

    return $stmt->fetchAll();
}

function fetchPartners(PDO $pdo): array
{
    $partnerTable = partnerTableName($pdo);
    $campusTable = campusTableName($pdo);

    if ($partnerTable === null) {
        return [];
    }

    $deletedFilter = partnerHasSoftDelete($pdo) ? ' WHERE p.Is_Deleted = 0' : '';

    $sql = "SELECT p.Partner_ID, p.Name, p.Country, p.Website, c.Name AS campus_name, c.Campus_ID
            FROM `{$partnerTable}` p
            INNER JOIN `{$campusTable}` c ON p.Campus_ID = c.Campus_ID{$deletedFilter}
            ORDER BY p.Name ASC";

    return $pdo->query($sql)->fetchAll();
}

function fetchAgreements(PDO $pdo): array
{
    $tables = registryTableNames($pdo);
    $agreementTable = $tables['agreement'];
    $partnerTable = $tables['partner'];
    $contactTable = $tables['contact'];
    $campusTable = $tables['campus'];

    if ($agreementTable === null || $partnerTable === null) {
        return [];
    }

    $contactJoin = '';
    $contactSelect = "'N/A' AS contact";

    if ($contactTable !== null) {
        $contactJoin = "LEFT JOIN (
                            SELECT Partner_ID, MIN(Contact_ID) AS Contact_ID
                            FROM `{$contactTable}`
                            GROUP BY Partner_ID
                        ) first_contact ON p.Partner_ID = first_contact.Partner_ID
                        LEFT JOIN `{$contactTable}` ct ON ct.Contact_ID = first_contact.Contact_ID";
        $contactSelect = 'COALESCE(ct.Name, \'N/A\') AS contact';
    }

    $sql = "SELECT
                a.Agree_ID AS id,
                p.Name AS partner,
                a.Agreement_Type AS type,
                a.Partnership_Type AS Partnership_type,
                c.Name AS campus,
                c.Campus_ID,
                a.Status AS status,
                a.Expiry_Date AS expiry,
                a.Signed_Date AS signed_date,
                {$contactSelect}
            FROM `{$agreementTable}` a
            INNER JOIN `{$partnerTable}` p ON a.Partner_ID = p.Partner_ID
            INNER JOIN `{$campusTable}` c ON COALESCE(a.Campus_ID, p.Campus_ID) = c.Campus_ID
            {$contactJoin}
            ORDER BY a.Agree_ID ASC";

    $rows = $pdo->query($sql)->fetchAll();

    foreach ($rows as &$row) {
        $row = enrichAgreementRow($row);
    }
    unset($row);

    return $rows;
}

function fetchAgreementCounts(PDO $pdo): array
{
    $counts = [
        'Active'         => 0,
        'Expiring Soon'  => 0,
        'Expired'        => 0,
        'Total'          => 0,
    ];

    $agreementTable = agreementTableName($pdo);

    if ($agreementTable === null) {
        return $counts;
    }

    $stmt = $pdo->query("SELECT Expiry_Date, Status FROM `{$agreementTable}`");

    while ($row = $stmt->fetch()) {
        $counts['Total']++;
        $status = resolveAgreementDisplayStatus($row['Expiry_Date'] ?? null, $row['Status'] ?? null);

        if (isset($counts[$status])) {
            $counts[$status]++;
        }
    }

    return $counts;
}

function fetchFilteredAgreements(PDO $pdo, ?string $status = null, ?string $type = null, ?int $campusId = null): array
{
    $tables = registryTableNames($pdo);
    $agreementTable = $tables['agreement'];
    $partnerTable = $tables['partner'];
    $campusTable = $tables['campus'];

    if ($agreementTable === null || $partnerTable === null) {
        return [];
    }

    $sql = "SELECT
                a.Agree_ID AS id,
                p.Name AS partner,
                a.Agreement_Type AS type,
                a.Partnership_Type AS Partnership_type,
                c.Name AS campus,
                c.Campus_ID,
                a.Status AS status,
                a.Expiry_Date AS expiry,
                a.Signed_Date AS signed_date
            FROM `{$agreementTable}` a
            INNER JOIN `{$partnerTable}` p ON a.Partner_ID = p.Partner_ID
            INNER JOIN `{$campusTable}` c ON COALESCE(a.Campus_ID, p.Campus_ID) = c.Campus_ID
            WHERE 1 = 1";
    $params = [];

    if ($type !== null && $type !== '' && $type !== 'ALL') {
        $sql .= ' AND a.Agreement_Type = :type';
        $params['type'] = $type;
    }

    if ($campusId !== null && $campusId > 0) {
        $sql .= ' AND c.Campus_ID = :campus_id';
        $params['campus_id'] = $campusId;
    }

    $sql .= ' ORDER BY a.Agree_ID DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    foreach ($rows as &$row) {
        $row = enrichAgreementRow($row);
    }
    unset($row);

    if ($status !== null && $status !== '' && $status !== 'ALL') {
        $rows = array_values(array_filter(
            $rows,
            static fn(array $row): bool => ($row['status'] ?? '') === $status
        ));
    }

    return $rows;
}

function fetchAgreementHistory(PDO $pdo): array
{
    $tables = registryTableNames($pdo);
    $historyTable = $tables['agreement_history'];
    $agreementTable = $tables['agreement'];
    $partnerTable = $tables['partner'];

    if ($historyTable === null || $agreementTable === null || $partnerTable === null) {
        return [];
    }

    $sql = "SELECT
                ah.AgreeHis_ID,
                ah.Agree_ID,
                ah.Event_Type,
                ah.Event_Date,
                ah.Comments,
                p.Name AS partner_name,
                a.Agreement_Type
            FROM `{$historyTable}` ah
            INNER JOIN `{$agreementTable}` a ON ah.Agree_ID = a.Agree_ID
            INNER JOIN `{$partnerTable}` p ON a.Partner_ID = p.Partner_ID
            ORDER BY ah.Event_Date DESC, ah.AgreeHis_ID DESC";

    return $pdo->query($sql)->fetchAll();
}

function fetchPartnersWithContacts(PDO $pdo): array
{
    $tables = registryTableNames($pdo);
    $partnerTable = $tables['partner'];
    $contactTable = $tables['contact'];
    $campusTable = $tables['campus'];

    if ($partnerTable === null) {
        return [];
    }

    $deletedFilter = partnerHasSoftDelete($pdo) ? ' WHERE p.Is_Deleted = 0' : '';
    $contactJoin = '';
    $contactSelect = 'NULL AS contact_name, NULL AS contact_email, NULL AS contact_phone';

    if ($contactTable !== null) {
        $contactJoin = "LEFT JOIN (
                            SELECT Partner_ID, MIN(Contact_ID) AS Contact_ID
                            FROM `{$contactTable}`
                            GROUP BY Partner_ID
                        ) first_contact ON p.Partner_ID = first_contact.Partner_ID
                        LEFT JOIN `{$contactTable}` ct ON ct.Contact_ID = first_contact.Contact_ID";
        $contactSelect = 'ct.Name AS contact_name, ct.Email AS contact_email, ct.Phone_Number AS contact_phone';
    }

    $sql = "SELECT
                p.Partner_ID,
                p.Name AS partner_name,
                p.Country,
                p.Website,
                c.Name AS campus_name,
                {$contactSelect}
            FROM `{$partnerTable}` p
            INNER JOIN `{$campusTable}` c ON p.Campus_ID = c.Campus_ID
            {$contactJoin}{$deletedFilter}
            ORDER BY p.Name ASC";

    return $pdo->query($sql)->fetchAll();
}

function createAgreementWithHistory(
    PDO $pdo,
    int $partnerId,
    string $partnershipType,
    string $agreementType,
    string $signedDate,
    string $expiryDate,
    string $createdBy
): int {
    $partnerTable = partnerTableName($pdo);

    if ($partnerTable === null) {
        throw new RuntimeException('Partner registry table is not available.');
    }

    $partnerStmt = $pdo->prepare("SELECT Campus_ID FROM `{$partnerTable}` WHERE Partner_ID = :partner_id LIMIT 1");
    $partnerStmt->execute(['partner_id' => $partnerId]);
    $partner = $partnerStmt->fetch();

    if ($partner === false) {
        throw new InvalidArgumentException('Selected partner was not found.');
    }

    return registerActivePartnership($pdo, [
        'partner_mode'        => 'existing',
        'partner_id'          => $partnerId,
        'campus_id'           => (int) $partner['Campus_ID'],
        'partnership_type'    => $partnershipType,
        'agreement_type'      => $agreementType,
        'signed_date'         => $signedDate,
        'expiry_date'         => $expiryDate,
        'scope_description'   => '',
        'document_path'       => null,
        'contact_name'        => 'Registry Contact',
        'contact_designation' => null,
        'contact_email'       => null,
        'contact_phone'       => null,
        'contact_fax'         => null,
    ], (int) ($_SESSION['user_id'] ?? 0), $createdBy);
}

/**
 * @param array{
 *     partner_mode: string,
 *     partner_id?: int,
 *     partner_name?: string,
 *     partner_country?: string,
 *     partner_address?: string,
 *     partner_website?: string,
 *     campus_id: int,
 *     contact_name: string,
 *     contact_designation?: string,
 *     contact_email?: string,
 *     contact_phone?: string,
 *     contact_fax?: string,
 *     partnership_type: string,
 *     agreement_type: string,
 *     signed_date: string,
 *     expiry_date: string,
 *     scope_description?: string,
 *     document_path?: ?string
 * } $data
 */
function registerActivePartnership(
    PDO $pdo,
    array $data,
    int $directorUserId,
    string $createdByLabel = ''
): int {
    $partnerMode = strtolower(trim((string) ($data['partner_mode'] ?? 'existing')));
    $campusId = (int) ($data['campus_id'] ?? 0);
    $partnershipType = trim((string) ($data['partnership_type'] ?? ''));
    $agreementType = trim((string) ($data['agreement_type'] ?? ''));
    $signedDate = trim((string) ($data['signed_date'] ?? ''));
    $expiryDate = trim((string) ($data['expiry_date'] ?? ''));
    $scopeDescription = trim((string) ($data['scope_description'] ?? ''));
    $documentPath = $data['document_path'] ?? null;
    $contactName = trim((string) ($data['contact_name'] ?? ''));
    $contactDesignation = trim((string) ($data['contact_designation'] ?? ''));
    $contactEmail = trim((string) ($data['contact_email'] ?? ''));
    $contactPhone = trim((string) ($data['contact_phone'] ?? ''));
    $contactFax = trim((string) ($data['contact_fax'] ?? ''));

    if ($directorUserId <= 0) {
        throw new InvalidArgumentException('A valid director session is required to register agreements.');
    }

    if ($campusId <= 0) {
        throw new InvalidArgumentException('Please select the managing DWU campus.');
    }

    if ($partnershipType === '' || $agreementType === '' || $signedDate === '' || $expiryDate === '') {
        throw new InvalidArgumentException('Partnership type, agreement type, and agreement dates are required.');
    }

    if ($contactName === '') {
        throw new InvalidArgumentException('Primary contact person name is required.');
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $signedDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiryDate)) {
        throw new InvalidArgumentException('Dates must be provided in YYYY-MM-DD format.');
    }

    if ($expiryDate < $signedDate) {
        throw new InvalidArgumentException('Expiry date must be on or after the signed date.');
    }

    $tables = registryTableNames($pdo);
    $agreementTable = $tables['agreement'];
    $partnerTable = $tables['partner'];
    $contactTable = $tables['contact'];
    $historyTable = $tables['agreement_history'];
    $campusTable = $tables['campus'];

    if ($agreementTable === null || $partnerTable === null || $contactTable === null) {
        throw new RuntimeException('Registry tables are not available.');
    }

    $campusStmt = $pdo->prepare("SELECT Campus_ID FROM `{$campusTable}` WHERE Campus_ID = :campus_id LIMIT 1");
    $campusStmt->execute(['campus_id' => $campusId]);

    if ($campusStmt->fetch() === false) {
        throw new InvalidArgumentException('Selected campus was not found.');
    }

    if ($partnerMode === 'new') {
        $partnerName = trim((string) ($data['partner_name'] ?? ''));
        $partnerCountry = trim((string) ($data['partner_country'] ?? ''));
        $partnerAddress = trim((string) ($data['partner_address'] ?? ''));
        $partnerWebsite = trim((string) ($data['partner_website'] ?? ''));

        if ($partnerName === '' || $partnerCountry === '') {
            throw new InvalidArgumentException('Partner name and country are required for new partners.');
        }

        $partnerId = createPartnerRecord(
            $pdo,
            $campusId,
            $partnerName,
            $partnerCountry,
            $partnerAddress,
            $partnerWebsite
        );
    } else {
        $partnerId = (int) ($data['partner_id'] ?? 0);

        if ($partnerId <= 0) {
            throw new InvalidArgumentException('Please select an existing partner.');
        }

        $partnerSql = "SELECT Partner_ID FROM `{$partnerTable}` WHERE Partner_ID = :partner_id";
        if (partnerHasSoftDelete($pdo)) {
            $partnerSql .= ' AND Is_Deleted = 0';
        }
        $partnerSql .= ' LIMIT 1';

        $partnerStmt = $pdo->prepare($partnerSql);
        $partnerStmt->execute(['partner_id' => $partnerId]);

        if ($partnerStmt->fetch() === false) {
            throw new InvalidArgumentException('Selected partner was not found.');
        }
    }

    $pdo->beginTransaction();

    try {
        createContactRecord(
            $pdo,
            $partnerId,
            $contactName,
            $contactDesignation,
            $contactEmail,
            $contactPhone,
            $contactFax
        );

        $insertAgreement = $pdo->prepare(
            "INSERT INTO `{$agreementTable}`
                (Partner_ID, Campus_ID, Submitted_By, Reviewed_By, Partnership_Type, Agreement_Type,
                 Scope_Description, Status, Signed_Date, Expiry_Date, Document_Path)
             VALUES
                (:partner_id, :campus_id, :submitted_by, :reviewed_by, :partnership_type, :agreement_type,
                 :scope_description, :status, :signed_date, :expiry_date, :document_path)"
        );

        $insertAgreement->execute([
            'partner_id'         => $partnerId,
            'campus_id'          => $campusId,
            'submitted_by'       => $directorUserId,
            'reviewed_by'        => $directorUserId,
            'partnership_type'   => $partnershipType,
            'agreement_type'     => $agreementType,
            'scope_description'  => $scopeDescription !== '' ? $scopeDescription : null,
            'status'             => Agreement::STATUS_ACTIVE,
            'signed_date'        => $signedDate,
            'expiry_date'        => $expiryDate,
            'document_path'      => $documentPath,
        ]);

        $agreementId = (int) $pdo->lastInsertId();

        if ($historyTable !== null) {
            $historyComment = $createdByLabel !== ''
                ? sprintf(
                    'Active partnership registered by %s via the Partnership Director dashboard.',
                    $createdByLabel
                )
                : 'Active partnership registered via the Partnership Director dashboard.';

            if ($scopeDescription !== '') {
                $historyComment .= ' Scope/funding notes recorded.';
            }

            $insertHistory = $pdo->prepare(
                "INSERT INTO `{$historyTable}`
                    (Agree_ID, Event_Type, Event_Date, Comments)
                 VALUES
                    (:agree_id, :event_type, :event_date, :comments)"
            );

            $insertHistory->execute([
                'agree_id'    => $agreementId,
                'event_type'  => 'Agreement Created',
                'event_date'  => date('Y-m-d H:i:s'),
                'comments'    => $historyComment,
            ]);
        }

        $pdo->commit();

        return $agreementId;
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

function createPartnerRecord(
    PDO $pdo,
    int $campusId,
    string $name,
    string $country,
    string $address = '',
    string $website = ''
): int {
    $partnerTable = partnerTableName($pdo);

    if ($partnerTable === null) {
        throw new RuntimeException('Partner registry table is not available.');
    }

    $columns = $pdo->query("SHOW COLUMNS FROM `{$partnerTable}`")->fetchAll(PDO::FETCH_COLUMN);
    $fields = ['Campus_ID', 'Name', 'Country'];
    $values = [':campus_id', ':name', ':country'];
    $params = [
        'campus_id' => $campusId,
        'name'      => $name,
        'country'   => $country,
    ];

    if (in_array('Address', $columns, true)) {
        $fields[] = 'Address';
        $values[] = ':address';
        $params['address'] = $address !== '' ? $address : null;
    }

    if (in_array('Website', $columns, true)) {
        $fields[] = 'Website';
        $values[] = ':website';
        $params['website'] = $website !== '' ? $website : null;
    }

    if (in_array('Is_Deleted', $columns, true)) {
        $fields[] = 'Is_Deleted';
        $values[] = '0';
    }

    $sql = sprintf(
        'INSERT INTO `%s` (%s) VALUES (%s)',
        $partnerTable,
        implode(', ', $fields),
        implode(', ', $values)
    );

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return (int) $pdo->lastInsertId();
}

function createContactRecord(
    PDO $pdo,
    int $partnerId,
    string $name,
    string $designation = '',
    string $email = '',
    string $phone = '',
    string $fax = ''
): int {
    $contactTable = contactTableName($pdo);

    if ($contactTable === null) {
        throw new RuntimeException('Contact registry table is not available.');
    }

    $stmt = $pdo->prepare(
        "INSERT INTO `{$contactTable}`
            (Partner_ID, Name, Designation, Email, Phone_Number, Fax)
         VALUES
            (:partner_id, :name, :designation, :email, :phone_number, :fax)"
    );

    $stmt->execute([
        'partner_id'   => $partnerId,
        'name'         => $name,
        'designation'  => $designation !== '' ? $designation : null,
        'email'        => $email !== '' ? $email : null,
        'phone_number' => $phone !== '' ? $phone : null,
        'fax'          => $fax !== '' ? $fax : null,
    ]);

    return (int) $pdo->lastInsertId();
}

function storeUploadedAgreementPdf(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('Unable to upload the agreement PDF. Please try again.');
    }

    $uploadDir = dirname(__DIR__) . '/uploads/agreements';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename((string) ($file['name'] ?? 'agreement.pdf')));
    $filename = time() . '_' . $safeName;
    $targetPath = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new RuntimeException('Failed to store the uploaded agreement PDF.');
    }

    return 'uploads/agreements/' . $filename;
}
