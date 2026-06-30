<?php

class LeaveReminderService
{
    public static function sendUpcomingEndReminders(int $windowDays = 1): array
    {
        self::ensureSchema();

        $requests = LeaveRequest::endingSoonReminders($windowDays);
        $results = [
            'checked' => count($requests),
            'sent' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($requests as $request) {
            $requestId = (int) ($request['id'] ?? 0);

            try {
                $sent = ExternalNotificationService::leaveEndingSoon($request);
                if ($sent) {
                    LeaveRequest::markEndReminderSent($requestId);
                    $results['sent']++;
                    continue;
                }

                $results['failed']++;
                $results['errors'][] = self::failureNote($request, ExternalNotificationService::lastEmailError());
            } catch (Throwable $throwable) {
                $results['failed']++;
                $results['errors'][] = self::failureNote($request, $throwable->getMessage());
                app_log($throwable);
            }
        }

        return $results;
    }

    private static function ensureSchema(): void
    {
        $stmt = db()->prepare(
            "SELECT COUNT(*)
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'leave_requests'
               AND COLUMN_NAME = 'end_reminder_sent_at'"
        );
        $stmt->execute();

        if ((int) $stmt->fetchColumn() > 0) {
            return;
        }

        db()->exec('ALTER TABLE leave_requests ADD COLUMN end_reminder_sent_at DATETIME NULL AFTER resumed_at');
    }

    private static function failureNote(array $request, string $reason): string
    {
        $name = $request['employee_name'] ?? 'Unknown staff member';
        $email = $request['employee_email'] ?? 'no email';

        return trim($name . ' <' . $email . '>: ' . ($reason !== '' ? $reason : 'unknown error'));
    }
}
