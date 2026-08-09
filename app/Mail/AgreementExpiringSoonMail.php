<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Agreement;

/** HTML mailable for 30-day agreement expiry alerts. */
final class AgreementExpiringSoonMail
{
    private Agreement $agreement;
    private string $registryUrl;

    public function __construct(Agreement $agreement, string $registryUrl)
    {
        $this->agreement = $agreement;
        $this->registryUrl = $registryUrl;
    }

    public function subject(): string
    {
        return sprintf(
            'Partnership Agreement Expiring Soon — %s (%s)',
            $this->agreement->partnerName,
            $this->agreement->campus
        );
    }

    public function render(): string
    {
        $partner = htmlspecialchars($this->agreement->partnerName, ENT_QUOTES, 'UTF-8');
        $type = htmlspecialchars($this->agreement->agreementType, ENT_QUOTES, 'UTF-8');
        $campus = htmlspecialchars($this->agreement->campus, ENT_QUOTES, 'UTF-8');
        $expiry = $this->agreement->expiryDate
            ? htmlspecialchars(date('F j, Y', strtotime($this->agreement->expiryDate)), ENT_QUOTES, 'UTF-8')
            : '—';
        $days = $this->agreement->daysRemaining();
        $daysLabel = $days !== null ? (string) abs($days) : '—';
        $url = htmlspecialchars($this->registryUrl, ENT_QUOTES, 'UTF-8');
        $duration = htmlspecialchars($this->agreement->duration(), ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Agreement Expiring Soon</title></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f1f5f9;padding:24px 0;">
<tr><td align="center">
<table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background:#ffffff;border-radius:12px;overflow:hidden;">
<tr><td style="background:#006837;padding:20px 28px;color:#ffffff;">
<h1 style="margin:0;font-size:20px;">DWU Partnership Registry</h1>
<p style="margin:8px 0 0;font-size:13px;opacity:0.9;">Automated Expiry Alert</p>
</td></tr>
<tr><td style="padding:28px;color:#0f172a;">
<p style="margin:0 0 12px;font-size:15px;">A partnership agreement is entering the <strong style="color:#b45309;">30-day expiration window</strong>.</p>
<table role="presentation" width="100%" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;margin:16px 0;">
<tr><td style="padding:12px 16px;font-size:13px;"><strong>Partner:</strong> {$partner}</td></tr>
<tr><td style="padding:0 16px 12px;font-size:13px;"><strong>Agreement Type:</strong> {$type}</td></tr>
<tr><td style="padding:0 16px 12px;font-size:13px;"><strong>Campus:</strong> {$campus}</td></tr>
<tr><td style="padding:0 16px 12px;font-size:13px;"><strong>Lifespan:</strong> {$duration}</td></tr>
<tr><td style="padding:0 16px 12px;font-size:13px;"><strong>Expiration Date:</strong> {$expiry}</td></tr>
<tr><td style="padding:0 16px 16px;font-size:13px;"><strong>Days Remaining:</strong> {$daysLabel}</td></tr>
</table>
<a href="{$url}" style="display:inline-block;background:#006837;color:#ffffff;text-decoration:none;padding:12px 20px;border-radius:8px;font-size:14px;font-weight:bold;">View Agreement in Registry</a>
</td></tr>
</table>
</td></tr></table>
</body></html>
HTML;
    }
}
