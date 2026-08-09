<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\AgreementExpiringSoonMail;
use App\Models\Agreement;
use App\Services\Mailer;
use PDO;
use RuntimeException;
use Throwable;

/**
 * Console command: php artisan agreements:update-statuses
 */
final class UpdateAgreementStatuses
{
    private PDO $pdo;
    private Mailer $mailer;

    public function __construct(PDO $pdo, ?Mailer $mailer = null)
    {
        $this->pdo = $pdo;
        $this->mailer = $mailer ?? new Mailer();
    }

    public function handle(): int
    {
        $tables = registryTableNames($this->pdo);
        $agreementTable = $tables['agreement'];
        $partnerTable = $tables['partner'];
        $campusTable = $tables['campus'];

        if ($agreementTable === null || $partnerTable === null) {
            throw new RuntimeException('Agreement registry tables are not available.');
        }

        $hasAlertColumn = agreementHasExpiryAlertColumn($this->pdo);
        $alertSelect = $hasAlertColumn ? ', a.Expiry_Alert_Sent_At' : ', NULL AS Expiry_Alert_Sent_At';

        $sql = "SELECT a.Agree_ID, a.Signed_Date, a.Expiry_Date, a.Status, a.Agreement_Type,
                       p.Name AS partner_name, c.Name AS campus_name, c.Campus_ID{$alertSelect}
                FROM `{$agreementTable}` a
                INNER JOIN `{$partnerTable}` p ON a.Partner_ID = p.Partner_ID
                INNER JOIN `{$campusTable}` c ON COALESCE(a.Campus_ID, p.Campus_ID) = c.Campus_ID";

        $rows = $this->pdo->query($sql)->fetchAll();
        $updated = 0;
        $alertsSent = 0;
        $directorEmails = fetchPartnershipDirectorEmails($this->pdo);
        $baseUrl = appBaseUrl();

        $updateStmt = $this->pdo->prepare(
            "UPDATE `{$agreementTable}` SET Status = :status WHERE Agree_ID = :id"
        );

        $alertStmt = $hasAlertColumn
            ? $this->pdo->prepare(
                "UPDATE `{$agreementTable}` SET Expiry_Alert_Sent_At = NOW() WHERE Agree_ID = :id"
            )
            : null;

        foreach ($rows as $row) {
            $agreement = Agreement::fromRow([
                'Agree_ID'              => $row['Agree_ID'],
                'Signed_Date'           => $row['Signed_Date'],
                'Expiry_Date'           => $row['Expiry_Date'],
                'Status'                => $row['Status'],
                'Agreement_Type'        => $row['Agreement_Type'],
                'partner_name'          => $row['partner_name'],
                'campus_name'           => $row['campus_name'],
                'Campus_ID'             => $row['Campus_ID'],
                'Expiry_Alert_Sent_At'  => $row['Expiry_Alert_Sent_At'] ?? null,
            ]);

            $newStatus = $agreement->databaseStatus();

            if ($newStatus !== ($row['Status'] ?? '')) {
                $updateStmt->execute(['status' => $newStatus, 'id' => $agreement->id]);
                $updated++;
            }

            if ($agreement->shouldSendExpiryAlert() && $directorEmails !== []) {
                $mail = new AgreementExpiringSoonMail($agreement, $agreement->registryUrl($baseUrl));
                $sent = $this->mailer->sendHtml($directorEmails, $mail->subject(), $mail->render());

                if ($sent > 0 && $alertStmt !== null) {
                    $alertStmt->execute(['id' => $agreement->id]);
                }

                $alertsSent += $sent > 0 ? 1 : 0;
            }
        }

        echo sprintf(
            "Agreement status engine complete. Updated: %d | Expiry alerts sent: %d\n",
            $updated,
            $alertsSent
        );

        return 0;
    }
}

/** @return list<string> */
function fetchPartnershipDirectorEmails(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT Email FROM users
         WHERE Role IN ('partnership_director', 'Partnership Director')
           AND (Is_Active = 1 OR Is_Active IS NULL)"
    );

    $emails = [];

    while ($row = $stmt->fetch()) {
        if (!empty($row['Email'])) {
            $emails[] = (string) $row['Email'];
        }
    }

    return array_values(array_unique($emails));
}

function agreementHasExpiryAlertColumn(PDO $pdo): bool
{
    static $has = null;

    if ($has !== null) {
        return $has;
    }

    $tables = registryTableNames($pdo);
    $table = $tables['agreement'];

    if ($table === null) {
        $has = false;

        return $has;
    }

    $columns = $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_COLUMN);
    $has = in_array('Expiry_Alert_Sent_At', $columns, true);

    return $has;
}

function appBaseUrl(): string
{
    $configured = getenv('APP_URL');

    if (is_string($configured) && $configured !== '') {
        return rtrim($configured, '/');
    }

    return 'http://localhost/IS406_PartnershipRegistry';
}
