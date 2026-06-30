# Online Leave Application System

Plain PHP and MySQL implementation for employee leave requests, approval workflow, leave balances, reports, and admin management.

Leave balances are tracked by financial year, using a July-to-June cycle by default. If your fiscal year starts on a different month, adjust `config/app.php`.

## Default Admin

Email: `admin@leavesystem.local`

Password: `Admin@123`

## Account Creation

Users can request an account from the login page. New self-registered accounts stay `pending` until ICT/Admin approves them from `Account Requests`. Applicants provide a National ID, and users can log in using either email address or National ID.

Admin or HR users can still create active worker accounts directly from `Workers > Add Worker`.

## Email/SMS Notifications

Applicants receive an external notification when they submit an account request and when ICT/Admin approves or rejects it.

Email uses SMTP. On the live server, either set the `LEAVE_SMTP_PASSWORD` environment variable or create `config/mail.local.php` with:

```php
<?php

return [
    'smtp' => [
        'password' => 'your gmail app password',
    ],
];
```

SMS needs a provider gateway; add the gateway details in `config/app.php` under `notifications.sms`.

All outbound attempts are recorded in `storage/logs/outbound-notifications.log`. Make sure the live server has a writable `storage/logs` directory.

## Leave End Reminders

Approved leave requests can be checked automatically and emailed when the leave is about to end.

Run the reminder job from cron once a day:

```bash
php scripts/leave-end-reminders.php
```

Example cron entry:

```bash
0 7 * * * /usr/bin/php /path/to/leave-system/scripts/leave-end-reminders.php >> /path/to/leave-system/storage/logs/leave-reminders.log 2>&1
```

The job checks approved leave requests ending today or tomorrow, sends the reminder email, and marks each request so it is not emailed twice.
On first run, it adds the reminder marker column automatically if it is missing.

## Setup

1. Create a MySQL database by importing `database/leave_system.sql`.
2. Update `config/database.php` if your MySQL username, password, or host is different.
3. Serve the `public/` folder through XAMPP, WAMP, or another PHP server.
4. Open `public/index.php` in the browser through the server URL.
