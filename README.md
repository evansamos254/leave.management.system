# Online Leave Application System

Plain PHP and MySQL implementation for employee leave requests, approval workflow, leave balances, reports, and admin management.

## Default Admin

Email: `admin@leavesystem.local`

Password: `Admin@123`

## Account Creation

Users can request an account from the login page. New self-registered accounts stay `pending` until ICT/Admin approves them from `Account Requests`. Applicants provide a National ID, and users can log in using either email address or National ID.

Admin or HR users can still create active worker accounts directly from `Workers > Add Worker`.

## Email/SMS Notifications

Applicants receive an external notification when they submit an account request and when ICT/Admin approves or rejects it.

Email uses PHP `mail()` by default. SMS needs a provider gateway; add the gateway details in `config/app.php` under `notifications.sms`.

All outbound attempts are recorded in `storage/logs/outbound-notifications.log`.

## Setup

1. Create a MySQL database by importing `database/leave_system.sql`.
2. Update `config/database.php` if your MySQL username, password, or host is different.
3. Serve the `public/` folder through XAMPP, WAMP, or another PHP server.
4. Open `public/index.php` in the browser through the server URL.
