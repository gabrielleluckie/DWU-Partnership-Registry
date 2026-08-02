<?php

declare(strict_types=1);

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
        'Active'         => 'active',
        'Expired'        => 'expired',
        'Soon to Expire' => 'soon',
        default          => 'soon',
    };
}

function computeAgreementStatus(string $expiryDate): string
{
    $today = new DateTimeImmutable('today');
    $expiry = new DateTimeImmutable($expiryDate);
    $daysRemaining = (int) $today->diff($expiry)->format('%r%a');

    if ($daysRemaining < 0) {
        return 'Expired';
    }

    if ($daysRemaining <= 60) {
        return 'Soon to Expire';
    }

    return 'Active';
}

function resolveAgreementDisplayStatus(?string $expiryDate, ?string $dbStatus = null): string
{
    if ($expiryDate !== null && $expiryDate !== '') {
        return computeAgreementStatus($expiryDate);
    }

    return match ($dbStatus) {
        'Expired' => 'Expired',
        'Active'  => 'Active',
        default   => 'Active',
    };
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
        $row['status'] = resolveAgreementDisplayStatus($row['expiry'] ?? null, $row['status'] ?? null);
    }
    unset($row);

    return $rows;
}

function fetchAgreementCounts(PDO $pdo): array
{
    $counts = [
        'Active'         => 0,
        'Soon to Expire' => 0,
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

    if ($status !== null && $status !== '' && $status !== 'ALL') {
        $sql .= ' AND a.Status = :status';
        $params['status'] = $status;
    }

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

    if ($status === null || $status === '' || $status === 'ALL') {
        foreach ($rows as &$row) {
            $row['status'] = resolveAgreementDisplayStatus($row['expiry'] ?? null, $row['status'] ?? null);
        }
        unset($row);
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
    if ($expiryDate < $signedDate) {
        throw new InvalidArgumentException('Expiry date must be on or after the signed date.');
    }

    $tables = registryTableNames($pdo);
    $agreementTable = $tables['agreement'];
    $partnerTable = $tables['partner'];
    $historyTable = $tables['agreement_history'];

    if ($agreementTable === null || $partnerTable === null) {
        throw new RuntimeException('Agreement registry tables are not available.');
    }

    $status = computeAgreementStatus($expiryDate);

    $partnerStmt = $pdo->prepare("SELECT Campus_ID FROM `{$partnerTable}` WHERE Partner_ID = :partner_id LIMIT 1");
    $partnerStmt->execute(['partner_id' => $partnerId]);
    $partner = $partnerStmt->fetch();

    if ($partner === false) {
        throw new InvalidArgumentException('Selected partner was not found.');
    }

    $pdo->beginTransaction();

    try {
        $insertAgreement = $pdo->prepare(
            "INSERT INTO `{$agreementTable}`
                (Partner_ID, Campus_ID, Partnership_Type, Agreement_Type, Status, Signed_Date, Expiry_Date)
             VALUES
                (:partner_id, :campus_id, :partnership_type, :agreement_type, :status, :signed_date, :expiry_date)"
        );

        $insertAgreement->execute([
            'partner_id'       => $partnerId,
            'campus_id'        => (int) $partner['Campus_ID'],
            'partnership_type' => $partnershipType,
            'agreement_type'   => $agreementType,
            'status'           => $status,
            'signed_date'      => $signedDate,
            'expiry_date'      => $expiryDate,
        ]);

        $agreementId = (int) $pdo->lastInsertId();

        if ($historyTable !== null) {
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
                'comments'    => sprintf(
                    'Initial agreement registration submitted by %s via the Partnership Director dashboard.',
                    $createdBy
                ),
            ]);
        }

        $pdo->commit();

        return $agreementId;
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}
