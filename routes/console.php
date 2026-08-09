<?php

declare(strict_types=1);

/**
 * Console route registration (mirrors Laravel routes/console.php).
 *
 * Schedule this command to run daily via Windows Task Scheduler or cron:
 *
 *   0 6 * * * cd /path/to/IS406_PartnershipRegistry && php artisan agreements:update-statuses
 *
 * Windows Task Scheduler action:
 *   Program: php
 *   Arguments: artisan agreements:update-statuses
 *   Start in: C:\xampp\htdocs\IS406_PartnershipRegistry
 */

return [
    'schedule' => [
        [
            'command'  => 'agreements:update-statuses',
            'interval' => 'daily',
            'at'       => '06:00',
        ],
    ],
];
