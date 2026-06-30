<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

if (function_exists('set_time_limit')) {
    @set_time_limit(0);
}

try {
    $results = LeaveReminderService::sendUpcomingEndReminders(1);

    echo sprintf(
        "Checked %d leave request(s). Sent %d reminder email(s). Failed %d.\n",
        (int) $results['checked'],
        (int) $results['sent'],
        (int) $results['failed']
    );

    if (!empty($results['errors'])) {
        echo "Failures:\n";
        foreach ($results['errors'] as $error) {
            echo ' - ' . $error . "\n";
        }
    }

    exit((int) $results['failed'] > 0 ? 1 : 0);
} catch (Throwable $throwable) {
    fwrite(STDERR, 'Leave reminder job failed: ' . $throwable->getMessage() . PHP_EOL);
    app_log($throwable);
    exit(1);
}
