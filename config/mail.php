<?php

declare(strict_types=1);

return [
    'driver'    => getenv('MAIL_DRIVER') ?: 'mail',
    'from'      => [
        'address' => getenv('MAIL_FROM_ADDRESS') ?: 'pdmis@dwu.ac.pg',
        'name'    => getenv('MAIL_FROM_NAME') ?: 'DWU Partnership Registry',
    ],
    'log_path'  => __DIR__ . '/../storage/logs/mail.log',
];
