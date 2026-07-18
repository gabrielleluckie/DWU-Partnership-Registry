<?php

declare(strict_types=1);

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

function fetchCampuses(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT Campus_ID, Name, Province FROM campuses ORDER BY Name ASC'
    );

    return $stmt->fetchAll();
}

function fetchPartners(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT p.Partner_ID, p.Name, p.Country, p.Website, c.Name AS campus_name, c.Campus_ID
         FROM partners p
         INNER JOIN campuses c ON p.Campus_ID = c.Campus_ID
         ORDER BY p.Name ASC'
    );

    return $stmt->fetchAll();
}

function fetchAgreements(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT
            a.Agree_ID AS id,
            p.Name AS partner,
            a.Agreement_type AS type,
            a.Partnership_type,
            c.Name AS campus,
            c.Campus_ID,
            a.Status AS status,
            a.Expiry_date AS expiry,
            a.Signed_date AS signed_date,
            COALESCE(ct.Name, \'N/A\') AS contact
         FROM agreements a
         INNER JOIN partners p ON a.Partner_ID = p.Partner_ID
         INNER JOIN campuses c ON p.Campus_ID = c.Campus_ID
         LEFT JOIN (
             SELECT Partner_ID, MIN(Contact_ID) AS Contact_ID
             FROM contacts
             GROUP BY Partner_ID
         ) first_contact ON p.Partner_ID = first_contact.Partner_ID
         LEFT JOIN contacts ct ON ct.Contact_ID = first_contact.Contact_ID
         ORDER BY a.Agree_ID ASC'
    );

    return $stmt->fetchAll();
}

function fetchAgreementCounts(PDO $pdo): array
{
    $counts = [
        'Active'         => 0,
        'Soon to Expire' => 0,
        'Expired'        => 0,
        'Total'          => 0,
    ];

    $stmt = $pdo->query('SELECT Status FROM agreements');

    while ($row = $stmt->fetch()) {
        $counts['Total']++;

        if (isset($counts[$row['Status']])) {
            $counts[$row['Status']]++;
        }
    }

    return $counts;
}

function fetchFilteredAgreements(PDO $pdo, ?string $status = null, ?string $type = null, ?int $campusId = null): array
{
    $sql = 'SELECT
                a.Agree_ID AS id,
                p.Name AS partner,
                a.Agreement_type AS type,
                a.Partnership_type,
                c.Name AS campus,
                c.Campus_ID,
                a.Status AS status,
                a.Expiry_date AS expiry,
                a.Signed_date AS signed_date
            FROM agreements a
            INNER JOIN partners p ON a.Partner_ID = p.Partner_ID
            INNER JOIN campuses c ON p.Campus_ID = c.Campus_ID
            WHERE 1 = 1';
    $params = [];

    if ($status !== null && $status !== '' && $status !== 'ALL') {
        $sql .= ' AND a.Status = :status';
        $params['status'] = $status;
    }

    if ($type !== null && $type !== '' && $type !== 'ALL') {
        $sql .= ' AND a.Agreement_type = :type';
        $params['type'] = $type;
    }

    if ($campusId !== null && $campusId > 0) {
        $sql .= ' AND c.Campus_ID = :campus_id';
        $params['campus_id'] = $campusId;
    }

    $sql .= ' ORDER BY a.Agree_ID DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function fetchAgreementHistory(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT
            ah.AgreeHis_ID,
            ah.Agree_ID,
            ah.Event_type,
            ah.Event_Date,
            ah.Comments,
            p.Name AS partner_name,
            a.Agreement_type
         FROM agreement_history ah
         INNER JOIN agreements a ON ah.Agree_ID = a.Agree_ID
         INNER JOIN partners p ON a.Partner_ID = p.Partner_ID
         ORDER BY ah.Event_Date DESC, ah.AgreeHis_ID DESC'
    );

    return $stmt->fetchAll();
}

function fetchPartnersWithContacts(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT
            p.Partner_ID,
            p.Name AS partner_name,
            p.Country,
            p.Website,
            c.Name AS campus_name,
            ct.Name AS contact_name,
            ct.Email AS contact_email,
            ct.Phone_Number AS contact_phone
         FROM partners p
         INNER JOIN campuses c ON p.Campus_ID = c.Campus_ID
         LEFT JOIN (
             SELECT Partner_ID, MIN(Contact_ID) AS Contact_ID
             FROM contacts
             GROUP BY Partner_ID
         ) first_contact ON p.Partner_ID = first_contact.Partner_ID
         LEFT JOIN contacts ct ON ct.Contact_ID = first_contact.Contact_ID
         ORDER BY p.Name ASC'
    );

    return $stmt->fetchAll();
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

    $status = computeAgreementStatus($expiryDate);

    $pdo->beginTransaction();

    try {
        $insertAgreement = $pdo->prepare(
            'INSERT INTO agreements
                (Partner_ID, Partnership_type, Agreement_type, Status, Signed_date, Expiry_date)
             VALUES
                (:partner_id, :partnership_type, :agreement_type, :status, :signed_date, :expiry_date)'
        );

        $insertAgreement->execute([
            'partner_id'       => $partnerId,
            'partnership_type' => $partnershipType,
            'agreement_type'   => $agreementType,
            'status'           => $status,
            'signed_date'      => $signedDate,
            'expiry_date'      => $expiryDate,
        ]);

        $agreementId = (int) $pdo->lastInsertId();

        $insertHistory = $pdo->prepare(
            'INSERT INTO agreement_history
                (Agree_ID, Event_type, Event_Date, Comments)
             VALUES
                (:agree_id, :event_type, :event_date, :comments)'
        );

        $insertHistory->execute([
            'agree_id'    => $agreementId,
            'event_type'  => 'Agreement Created',
            'event_date'  => date('Y-m-d'),
            'comments'    => sprintf(
                'Initial agreement registration submitted by %s via the Partnership Director dashboard.',
                $createdBy
            ),
        ]);

        $pdo->commit();

        return $agreementId;
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}
