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
    'base_url' => 'https://uatleave.busiacounty.go.ke',
    'upload_dir' => dirname(__DIR__) . '/uploads/leave-attachments',
    'max_upload_size' => 5 * 1024 * 1024,
    'allowed_upload_extensions' => ['pdf'],
    'profile_photo_dir' => dirname(__DIR__) . '/uploads/profile-photos',
    'profile_photo_max_size' => 10 * 1024 * 1024,
    'profile_photo_extensions' => ['jpg', 'jpeg', 'png', 'webp'],
    'leave_passport_photo_dir' => dirname(__DIR__) . '/uploads/leave-passport-photos',
    'leave_passport_photo_max_size' => 10 * 1024 * 1024,
    'leave_passport_photo_extensions' => ['jpg', 'jpeg', 'png', 'webp'],
    'employment_document_dir' => dirname(__DIR__) . '/uploads/employment-documents',
    'employment_document_max_size' => 10 * 1024 * 1024,
    'employment_document_extensions' => ['pdf'],
    'financial_year' => [
        'start_month' => 7,
        'start_day' => 1,
    ],
    'security' => [
        'max_login_attempts' => 3,
        'login_lockout_minutes' => 15,
        'session_timeout_minutes' => 30,
        'login_otp' => [
            'enabled' => true,
            'digits' => 6,
            'expiry_minutes' => 5,
            'max_attempts' => 5,
            'resend_cooldown_seconds' => 30,
        ],
    ],
    'notifications' => [
        'email' => [
            'enabled' => true,
            'transport' => 'smtp',
            'from' => 'noreply@busiacounty.go.ke',
            'from_name' => 'Busia County Leave System',
            'smtp' => [
                'host' => 'mail.busiacounty.go.ke',
                'port' => 587,
                'encryption' => 'tls',
                'username' => 'noreply@busiacounty.go.ke',
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
