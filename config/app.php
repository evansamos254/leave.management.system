<?php

$mailOverrides = is_file(__DIR__ . '/mail.local.php')
    ? require __DIR__ . '/mail.local.php'
    : [];
$smtpOverrides = $mailOverrides['smtp'] ?? [];
unset($mailOverrides['smtp']);

return [
    'name' => 'Busia County Staff Online Leave Application System',
    'hro_confirmation_name' => '',
    'timezone' => 'Africa/Nairobi',
    'base_url' => '',
    'upload_dir' => dirname(__DIR__) . '/uploads/leave-attachments',
    'max_upload_size' => 5 * 1024 * 1024,
    'allowed_upload_extensions' => ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'],
    'profile_photo_dir' => dirname(__DIR__) . '/uploads/profile-photos',
    'profile_photo_max_size' => 10 * 1024 * 1024,
    'profile_photo_extensions' => ['jpg', 'jpeg', 'png', 'webp'],
    'employment_document_dir' => dirname(__DIR__) . '/uploads/employment-documents',
    'employment_document_max_size' => 10 * 1024 * 1024,
    'employment_document_extensions' => ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'],
    'security' => [
        'max_login_attempts' => 3,
        'login_lockout_minutes' => 15,
        'session_timeout_minutes' => 30,
    ],
    'notifications' => [
        'email' => [
            'enabled' => true,
            'transport' => 'smtp',
            'from' => 'evansamos702@gmail.com',
            'from_name' => 'Busia County Leave System',
            'smtp' => [
                'host' => 'smtp.gmail.com',
                'port' => 587,
                'encryption' => 'tls',
                'username' => 'evansamos702@gmail.com',
                'password' => getenv('LEAVE_SMTP_PASSWORD') ?: '',
                'timeout' => 15,
                ...$smtpOverrides,
            ],
            ...$mailOverrides,
        ],
        'sms' => [
            'enabled' => false,
            'gateway_url' => '',
            'api_key' => '',
            'sender' => 'BusiaLeave',
        ],
    ],
];
