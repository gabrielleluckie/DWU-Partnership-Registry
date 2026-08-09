<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Lightweight mail sender (mirrors Laravel Mail facade for local/dev use).
 */
final class Mailer
{
    /** @param list<string> $recipients */
    public function sendHtml(array $recipients, string $subject, string $htmlBody): int
    {
        $config = require dirname(__DIR__, 2) . '/config/mail.php';
        $sent = 0;

        foreach ($recipients as $recipient) {
            $recipient = trim($recipient);

            if ($recipient === '' || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=UTF-8',
                'From: ' . $config['from']['name'] . ' <' . $config['from']['address'] . '>',
            ];

            $ok = @mail($recipient, $subject, $htmlBody, implode("\r\n", $headers));

            if (!$ok && ($config['driver'] ?? 'mail') === 'log') {
                $this->logMessage($config['log_path'], $recipient, $subject, $htmlBody);
                $ok = true;
            }

            if (!$ok && appEnvIsLocal()) {
                $this->logMessage($config['log_path'], $recipient, $subject, $htmlBody);
                $ok = true;
            }

            if ($ok) {
                $sent++;
            }
        }

        return $sent;
    }

    private function logMessage(string $path, string $to, string $subject, string $body): void
    {
        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $entry = sprintf(
            "[%s] TO: %s | SUBJECT: %s\n%s\n%s\n",
            date('Y-m-d H:i:s'),
            $to,
            $subject,
            str_repeat('-', 60),
            $body
        );

        file_put_contents($path, $entry, FILE_APPEND);
    }
}

function appEnvIsLocal(): bool
{
    if (function_exists('appEnv')) {
        return appEnv() === 'local';
    }

    return true;
}
