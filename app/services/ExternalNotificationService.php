<?php

class ExternalNotificationService
{
    private static string $lastEmailError = '';

    public static function lastEmailError(): string
    {
        return self::$lastEmailError;
    }

    public static function accountRequestReceived(string $name, string $email, ?string $phone = null): bool
    {
        return self::sendToContact(
            $name,
            $email,
            $phone,
            'Account request received',
            'Hello ' . $name . ', your account request has been received and is waiting for ICT approval.'
        );
    }

    public static function accountRequestApproved(array $user): bool
    {
        return self::sendToContact(
            $user['full_name'],
            $user['email'],
            $user['phone'] ?? null,
            'Your leave system account has been approved',
            'Hello ' . $user['full_name'] . ', ICT has approved your account request. You can now log in to the leave system using your email address or National ID.'
        );
    }

    public static function accountRequestRejected(array $user, ?string $reason = null): bool
    {
        $message = 'Hello ' . $user['full_name'] . ', your account request was rejected.';
        if ($reason !== null && trim($reason) !== '') {
            $message .= PHP_EOL . 'Reason: ' . trim($reason);
        }
        $message .= PHP_EOL . 'Please contact ICT for assistance.';

        return self::sendToContact(
            $user['full_name'],
            $user['email'],
            $user['phone'] ?? null,
            'Account request rejected',
            $message
        );
    }

    public static function workerAccountCreated(array $user, string $temporaryPassword): bool
    {
        $loginDetails = 'Login email: ' . $user['email'];
        if (!empty($user['national_id'])) {
            $loginDetails .= ' National ID: ' . $user['national_id'];
        }

        return self::sendToContact(
            $user['full_name'],
            $user['email'],
            $user['phone'] ?? null,
            'Your leave system account has been created',
            'Hello ' . $user['full_name'] . ', your leave system account has been created. '
                . $loginDetails . ' Temporary password: ' . $temporaryPassword
                . ' Please log in and change your password from your profile.',
            'Staff account creation instructions sent.'
        );
    }

    public static function passwordReset(array $user, string $temporaryPassword): bool
    {
        $message = 'Hello ' . $user['full_name'] . ', your leave system password was reset. '
            . 'Temporary password: ' . $temporaryPassword . ' '
            . 'Log in using your email address or National ID, then change your password from your profile.';

        return self::sendEmail(
            $user['email'],
            'Leave system password reset',
            $message,
            'Password reset instructions sent.'
        );
    }

    public static function leaveRequestSubmitted(array $request, string $nextRole = 'supervisor'): bool
    {
        return self::sendLeaveEmail(
            $request,
            'Leave request submitted',
            'Your leave request has been submitted and is waiting for ' . role_label($nextRole) . ' review.'
        );
    }

    public static function leaveRequestProgressed(array $request, string $nextRole): bool
    {
        return self::sendLeaveEmail(
            $request,
            'Leave request moved to ' . role_label($nextRole) . ' review',
            'Your leave request has moved to ' . role_label($nextRole) . ' review.'
        );
    }

    public static function leaveRequestApproved(array $request): bool
    {
        return self::sendLeaveEmail(
            $request,
            'Leave request approved',
            'Your leave request has received final approval.' . PHP_EOL . self::leaveApprovalWish($request)
        );
    }

    public static function leaveApprovalWish(array $request): string
    {
        $leaveType = strtolower((string) ($request['leave_type_name'] ?? ''));

        if (str_contains($leaveType, 'sick')) {
            return 'We wish you a quick recovery and good health.';
        }

        if (str_contains($leaveType, 'maternity')) {
            return 'We wish you a safe maternity period and good health for you and your baby.';
        }

        if (str_contains($leaveType, 'paternity')) {
            return 'We wish you joyful time with your family and the new baby.';
        }

        if (str_contains($leaveType, 'compassionate')) {
            return 'We wish you comfort, strength, and peace during this time.';
        }

        if (str_contains($leaveType, 'study')) {
            return 'We wish you success in your studies.';
        }

        if (str_contains($leaveType, 'annual')) {
            return 'We wish you a restful and refreshing break.';
        }

        return 'We wish you well during your approved leave period.';
    }

    public static function leaveRequestRejected(array $request, string $stageRole, ?string $reason = null): bool
    {
        $message = 'Your leave request was rejected at the ' . role_label($stageRole) . ' stage.';
        if ($reason !== null && trim($reason) !== '') {
            $message .= PHP_EOL . 'Reason: ' . trim($reason);
        }

        return self::sendLeaveEmail($request, 'Leave request rejected', $message);
    }

    private static function sendLeaveEmail(array $request, string $subject, string $statusMessage): bool
    {
        $name = $request['employee_name'] ?? $request['full_name'] ?? 'Applicant';
        $email = $request['employee_email'] ?? $request['email'] ?? '';
        $phone = $request['employee_phone'] ?? $request['phone'] ?? null;
        $summary = self::leaveRequestSummary($request);

        return self::sendToContact(
            $name,
            $email,
            $phone,
            $subject,
            'Hello ' . $name . ',' . PHP_EOL . PHP_EOL
                . $statusMessage . PHP_EOL . PHP_EOL
                . $summary . PHP_EOL
                . 'Please log in to the leave system to view the full progress.',
            'Leave request progress email sent.'
        );
    }

    private static function leaveRequestSummary(array $request): string
    {
        return 'Leave type: ' . ($request['leave_type_name'] ?? 'N/A') . PHP_EOL
            . 'Dates: ' . format_date($request['start_date'] ?? null) . ' to ' . format_date($request['end_date'] ?? null) . PHP_EOL
            . 'Working days: ' . format_days($request['days_requested'] ?? null, 'N/A');
    }

    private static function sendToContact(
        string $name,
        string $email,
        ?string $phone,
        string $subject,
        string $message,
        ?string $logMessage = null
    ): bool {
        $emailSent = self::sendEmail($email, $subject, $message, $logMessage);

        if ($phone !== null && trim($phone) !== '') {
            self::sendSms($phone, $message, $logMessage);
        }

        return $emailSent;
    }

    private static function sendEmail(string $email, string $subject, string $message, ?string $logMessage = null): bool
    {
        $config = app_config('notifications', [])['email'] ?? [];
        $logMessage = $logMessage ?? $message;
        self::$lastEmailError = '';

        if (empty($config['enabled'])) {
            self::$lastEmailError = 'Email notifications are disabled';
            self::log('email', $email, $subject, $logMessage, 'skipped');
            return false;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            self::$lastEmailError = 'Recipient email address is invalid';
            self::log('email', $email, $subject, $logMessage, 'skipped');
            return false;
        }

        $from = $config['from'] ?? 'no-reply@leavesystem.local';
        $fromName = $config['from_name'] ?? app_config('name', 'Busia County Leave System');

        if (($config['transport'] ?? 'mail') === 'smtp') {
            $sent = self::sendSmtpEmail($email, $subject, $message, $from, $fromName, $config['smtp'] ?? []);
        } else {
            $headers = [
                'From: ' . $from,
                'Reply-To: ' . $from,
                'Content-Type: text/plain; charset=UTF-8',
            ];

            $sent = @mail($email, $subject, $message, implode(PHP_EOL, $headers));
        }

        $status = $sent ? 'sent' : 'failed' . (self::$lastEmailError !== '' ? ' (' . self::$lastEmailError . ')' : '');
        self::log('email', $email, $subject, $logMessage, $status);

        return $sent;
    }

    private static function sendSmtpEmail(
        string $email,
        string $subject,
        string $message,
        string $from,
        string $fromName,
        array $smtp
    ): bool {
        if (empty($smtp['host']) || empty($smtp['username']) || empty($smtp['password'])) {
            self::$lastEmailError = 'SMTP host, username, or password is missing';
            return false;
        }

        $attempts = self::smtpAttempts($smtp);
        foreach ($attempts as $attempt) {
            if (self::sendSmtpAttempt($email, $subject, $message, $from, $fromName, $attempt)) {
                return true;
            }
        }

        return false;
    }

    private static function smtpAttempts(array $smtp): array
    {
        $attempts = [$smtp];
        $host = strtolower((string) ($smtp['host'] ?? ''));
        $port = (int) ($smtp['port'] ?? 587);

        if ($host === 'smtp.gmail.com') {
            if ($port !== 465) {
                $fallback = $smtp;
                $fallback['port'] = 465;
                $fallback['encryption'] = 'ssl';
                $attempts[] = $fallback;
            }

            if ($port !== 587) {
                $fallback = $smtp;
                $fallback['port'] = 587;
                $fallback['encryption'] = 'tls';
                $attempts[] = $fallback;
            }
        }

        return $attempts;
    }

    private static function sendSmtpAttempt(
        string $email,
        string $subject,
        string $message,
        string $from,
        string $fromName,
        array $smtp
    ): bool {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = (string) $smtp['host'];
            $mail->SMTPAuth = true;
            $mail->Username = (string) $smtp['username'];
            $mail->Password = (string) $smtp['password'];
            $mail->Port = (int) ($smtp['port'] ?? 587);
            $mail->Timeout = (int) ($smtp['timeout'] ?? 15);
            $mail->CharSet = 'UTF-8';

            $encryption = strtolower((string) ($smtp['encryption'] ?? 'tls'));
            if ($encryption === 'tls') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($encryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            }

            $mail->setFrom($from, $fromName);
            $mail->addAddress($email);
            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body = $message;

            return $mail->send();
        } catch (Throwable $throwable) {
            self::$lastEmailError = self::cleanMailError($throwable->getMessage())
                . ' using '
                . (string) ($smtp['host'] ?? 'smtp')
                . ':'
                . (string) ($smtp['port'] ?? '')
                . ' '
                . (string) ($smtp['encryption'] ?? '');
            app_log($throwable);
            return false;
        }
    }

    private static function cleanMailError(string $message): string
    {
        $message = preg_replace('/\s+/', ' ', trim($message));

        return substr($message ?: 'SMTP send failed', 0, 180);
    }

    private static function sendSms(string $phone, string $message, ?string $logMessage = null): void
    {
        $config = app_config('notifications', [])['sms'] ?? [];
        $logMessage = $logMessage ?? $message;

        if (empty($config['enabled']) || empty($config['gateway_url'])) {
            self::log('sms', $phone, 'SMS notification', $logMessage, 'skipped');
            return;
        }

        $payload = http_build_query([
            'to' => $phone,
            'message' => $message,
            'sender' => $config['sender'] ?? 'LeaveSystem',
        ]);

        $headers = ['Content-Type: application/x-www-form-urlencoded'];
        if (!empty($config['api_key'])) {
            $headers[] = 'Authorization: Bearer ' . $config['api_key'];
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode(PHP_EOL, $headers),
                'content' => $payload,
                'timeout' => 10,
            ],
        ]);

        $response = @file_get_contents($config['gateway_url'], false, $context);
        self::log('sms', $phone, 'SMS notification', $logMessage, $response === false ? 'failed' : 'sent');
    }

    private static function log(string $channel, string $recipient, string $subject, string $message, string $status): void
    {
        $line = sprintf(
            "[%s] %s %s to %s | %s | %s%s",
            date('Y-m-d H:i:s'),
            strtoupper($channel),
            $status,
            $recipient,
            $subject,
            $message,
            PHP_EOL
        );

        $logDir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        if (is_dir($logDir) && is_writable($logDir)) {
            @file_put_contents($logDir . '/outbound-notifications.log', $line, FILE_APPEND | LOCK_EX);
        }
    }
}
