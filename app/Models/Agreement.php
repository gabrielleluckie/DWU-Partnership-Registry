<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;

/**
 * Agreement domain model — status engine helpers (mirrors Laravel Eloquent accessors).
 */
final class Agreement
{
    public const STATUS_ACTIVE = 'Active';
    public const STATUS_EXPIRING_SOON = 'Expiring Soon';
    public const STATUS_EXPIRED = 'Expired';
    public const STATUS_SUBMITTED = 'Submitted';
    public const STATUS_APPROVED = 'Approved';
    public const STATUS_REJECTED = 'Rejected';

    /** @return list<string> */
    public static function registryLifecycleStatuses(): array
    {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_EXPIRING_SOON,
            self::STATUS_EXPIRED,
        ];
    }

    public int $id;
    public string $partnerName;
    public string $agreementType;
    public string $campus;
    public int $campusId;
    public ?string $signedDate;
    public ?string $expiryDate;
    public ?string $dbStatus;
    public ?string $expiryAlertSentAt;

    public function __construct(
        int $id,
        string $partnerName,
        string $agreementType,
        string $campus,
        int $campusId,
        ?string $signedDate,
        ?string $expiryDate,
        ?string $dbStatus = null,
        ?string $expiryAlertSentAt = null
    ) {
        $this->id = $id;
        $this->partnerName = $partnerName;
        $this->agreementType = $agreementType;
        $this->campus = $campus;
        $this->campusId = $campusId;
        $this->signedDate = $signedDate;
        $this->expiryDate = $expiryDate;
        $this->dbStatus = $dbStatus;
        $this->expiryAlertSentAt = $expiryAlertSentAt;
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (int) ($row['Agree_ID'] ?? $row['id'] ?? 0),
            (string) ($row['partner'] ?? $row['partner_name'] ?? $row['Name'] ?? ''),
            (string) ($row['type'] ?? $row['Agreement_Type'] ?? ''),
            (string) ($row['campus'] ?? $row['campus_name'] ?? ''),
            (int) ($row['Campus_ID'] ?? $row['campus_id'] ?? 0),
            self::nullableDate($row['signed_date'] ?? $row['Signed_Date'] ?? null),
            self::nullableDate($row['expiry'] ?? $row['Expiry_Date'] ?? null),
            isset($row['status']) ? (string) $row['status'] : (isset($row['Status']) ? (string) $row['Status'] : null),
            self::nullableDateTime($row['Expiry_Alert_Sent_At'] ?? null)
        );
    }

    public function daysRemaining(): ?int
    {
        if ($this->expiryDate === null || $this->expiryDate === '') {
            return null;
        }

        $today = new DateTimeImmutable('today');
        $expiry = new DateTimeImmutable($this->expiryDate);

        return (int) $today->diff($expiry)->format('%r%a');
    }

    public function calculatedStatus(): string
    {
        $daysRemaining = $this->daysRemaining();

        if ($daysRemaining === null) {
            return self::STATUS_ACTIVE;
        }

        if ($daysRemaining < 0) {
            return self::STATUS_EXPIRED;
        }

        if ($daysRemaining <= 30) {
            return self::STATUS_EXPIRING_SOON;
        }

        return self::STATUS_ACTIVE;
    }

    public function databaseStatus(): string
    {
        return match ($this->calculatedStatus()) {
            self::STATUS_EXPIRED       => self::STATUS_EXPIRED,
            self::STATUS_EXPIRING_SOON => self::STATUS_EXPIRING_SOON,
            default                    => self::STATUS_ACTIVE,
        };
    }

    public function duration(): string
    {
        if ($this->signedDate === null || $this->expiryDate === null) {
            return '—';
        }

        $start = new DateTimeImmutable($this->signedDate);
        $end = new DateTimeImmutable($this->expiryDate);

        if ($end < $start) {
            return '—';
        }

        $months = ((int) $end->format('Y') - (int) $start->format('Y')) * 12
            + ((int) $end->format('m') - (int) $start->format('m'));

        if ($months <= 0) {
            $days = (int) $start->diff($end)->days;

            return $days . ' ' . ($days === 1 ? 'Day' : 'Days');
        }

        if ($months >= 12 && $months % 12 === 0) {
            $years = (int) ($months / 12);

            return $years . ' ' . ($years === 1 ? 'Year' : 'Years');
        }

        return $months . ' ' . ($months === 1 ? 'Month' : 'Months');
    }

    public static function calculatedStatusFromExpiry(?string $expiryDate): string
    {
        return (new self(0, '', '', '', 0, null, $expiryDate))->calculatedStatus();
    }

    public function shouldSendExpiryAlert(): bool
    {
        if ($this->expiryAlertSentAt !== null) {
            return false;
        }

        $daysRemaining = $this->daysRemaining();

        if ($daysRemaining === null) {
            return false;
        }

        return $daysRemaining >= 1 && $daysRemaining <= 30;
    }

    /** @return array<string, mixed> */
    public function toListingArray(): array
    {
        return [
            'id'             => $this->id,
            'partner'        => $this->partnerName,
            'type'           => $this->agreementType,
            'campus'         => $this->campus,
            'Campus_ID'      => $this->campusId,
            'signed_date'    => $this->signedDate ?? '',
            'expiry'         => $this->expiryDate ?? '',
            'status'         => $this->calculatedStatus(),
            'days_remaining' => $this->daysRemaining(),
            'duration'       => $this->duration(),
        ];
    }

    public function registryUrl(string $baseUrl): string
    {
        return rtrim($baseUrl, '/') . '/dashboard/registry?agreement_id=' . $this->id;
    }

    private static function nullableDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    private static function nullableDateTime($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
