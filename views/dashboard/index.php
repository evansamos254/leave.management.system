<section class="stats-grid">
    <article class="stat-card">
        <span>Total Requests</span>
        <strong><?= (int) $counts['total'] ?></strong>
    </article>
    <article class="stat-card warning">
        <span>Pending</span>
        <strong><?= (int) $counts['pending'] ?></strong>
    </article>
    <article class="stat-card success">
        <span>Approved</span>
        <strong><?= (int) $counts['approved'] ?></strong>
    </article>
    <article class="stat-card danger">
        <span>Rejected</span>
        <strong><?= (int) $counts['rejected'] ?></strong>
    </article>
    <article class="stat-card warning">
        <span>Recalled</span>
        <strong><?= (int) ($counts['recalled'] ?? 0) ?></strong>
    </article>
</section>

<?php if ($user['role'] === 'admin'): ?>
    <section class="stats-grid compact">
        <article class="stat-card">
            <span>Approved Users</span>
            <strong><?= (int) $stats['users'] ?></strong>
        </article>
        <article class="stat-card">
            <span>Staff</span>
            <strong><?= (int) $stats['employees'] ?></strong>
        </article>
        <article class="stat-card">
            <span>Supervisors</span>
            <strong><?= (int) $stats['supervisors'] ?></strong>
        </article>
        <article class="stat-card">
            <span>HR</span>
            <strong><?= (int) $stats['hr'] ?></strong>
        </article>
        <article class="stat-card">
            <span>Directors</span>
            <strong><?= (int) $stats['directors'] ?></strong>
        </article>
        <article class="stat-card">
            <span>Chief Officers</span>
            <strong><?= (int) $stats['chief_officers'] ?></strong>
        </article>
        <article class="stat-card">
            <span>Departments</span>
            <strong><?= (int) $stats['departments'] ?></strong>
        </article>
    </section>
<?php endif; ?>

<?php if (!empty($returnToDutyRequest)): ?>
    <?php
    $reportBackDate = LeaveBalanceService::returnDateAfter((string) $returnToDutyRequest['end_date']);
    $leaveHasEnded = strtotime(date('Y-m-d')) > strtotime((string) $returnToDutyRequest['end_date']);
    ?>
    <section class="panel">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">Return to Duty</p>
                <h2>Welcome back! Are you back from leave?</h2>
            </div>
            <span class="badge warning">Action needed</span>
        </div>

        <div class="note-box">
            <span>Leave reminder</span>
            <p>
                <?php if ($leaveHasEnded): ?>
                    Your approved leave ended on <?= e(format_date($returnToDutyRequest['end_date'])) ?>.
                <?php else: ?>
                    Your leave (Ref: LAF-<?= (int) $returnToDutyRequest['id'] ?>) started on
                    <?= e(format_date($returnToDutyRequest['start_date'])) ?> and was scheduled to end on
                    <?= e(format_date($returnToDutyRequest['end_date'])) ?>.
                <?php endif; ?>
                If you have resumed duty, click the button below to notify your supervisor and HR.
            </p>
            <p>Report-back date: <?= e(format_date($reportBackDate)) ?>.</p>
            <div class="button-row">
                <form method="post" action="<?= e(url('leave/resume')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $returnToDutyRequest['id'] ?>">
                    <button class="btn btn-primary" type="submit">I Have Resumed Duty</button>
                </form>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php
$isHrOffice = $user['role'] === 'hr' && (!$employee || empty($employee['department_name']));
$directorateLabel = $isHrOffice ? 'HR Office' : ($employee['directorate_name'] ?? 'Not assigned');
$departmentLabel = $isHrOffice ? 'Office-level account' : ($employee['department_name'] ?? 'Not assigned');
?>

<section class="panel">
    <div class="panel-heading">
        <div>
            <p class="eyebrow">Alerts</p>
            <h2>Notifications</h2>
        </div>
        <?php if ($notifications): ?>
            <form method="post" action="<?= e(url('notifications/read')) ?>">
                <?= csrf_field() ?>
                <button class="btn btn-ghost" type="submit">Mark Read</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (!$notifications): ?>
        <p class="muted">No notifications yet.</p>
    <?php else: ?>
        <div class="notification-list">
            <?php foreach ($notifications as $notification): ?>
                <a class="notification-item <?= (int) $notification['is_read'] === 0 ? 'unread' : '' ?>"
                   href="<?= e($notification['link'] ?: url('dashboard')) ?>">
                    <strong><?= e($notification['title']) ?></strong>
                    <span><?= e($notification['message']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php if (!empty($leaveTypes)): ?>
    <section class="panel">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">Policy</p>
                <h2>Leave Types Supported</h2>
            </div>
        </div>

        <section class="stats-grid compact">
            <article class="stat-card">
                <span>Active Types</span>
                <strong><?= (int) $leaveTypeStats['active'] ?></strong>
            </article>
            <article class="stat-card">
                <span>Paid Leave</span>
                <strong><?= (int) $leaveTypeStats['paid'] ?></strong>
            </article>
            <article class="stat-card">
                <span>Unpaid Leave</span>
                <strong><?= (int) $leaveTypeStats['unpaid'] ?></strong>
            </article>
            <article class="stat-card">
                <span>Balance Tracked</span>
                <strong><?= (int) $leaveTypeStats['tracked'] ?></strong>
            </article>
        </section>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Leave Type</th>
                    <th>Eligibility</th>
                    <th>Entitlement</th>
                    <th>Balance</th>
                    <th>Attachment</th>
                    <th>Paid</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($leaveTypes as $type): ?>
                    <?php
                    $requiresAttachment = (int) ($type['requires_attachment'] ?? 0) === 1;
                    $attachmentAfterDays = $type['attachment_after_days'] ?? null;
                    ?>
                    <tr>
                        <td><?= e($type['name']) ?></td>
                        <td><?= e(leave_gender_label($type['gender_eligibility'] ?? 'any')) ?></td>
                        <td><?= e(format_days($type['default_entitlement'])) ?> days</td>
                        <td>
                            <?php if (LeaveType::isBalanceTracked($type)): ?>
                                <span class="badge success">Tracked</span>
                            <?php else: ?>
                                <span class="badge warning">Uncounted</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($requiresAttachment): ?>
                                <span class="badge warning">Required</span>
                                <?php if ($attachmentAfterDays !== null): ?>
                                    <small>after <?= e(format_days($attachmentAfterDays)) ?> days</small>
                                <?php endif; ?>
                            <?php elseif ($attachmentAfterDays !== null): ?>
                                <span class="badge warning">After <?= e(format_days($attachmentAfterDays)) ?> days</span>
                            <?php else: ?>
                                <span class="badge success">Optional</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ((int) ($type['is_paid'] ?? 0) === 1): ?>
                                <span class="badge success">Paid</span>
                            <?php else: ?>
                                <span class="badge danger">Unpaid</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<?php if (!empty($liveOverview)): ?>
    <?php
    $pendingStageCounts = $liveOverview['pending_by_stage'];
    $pendingStageTotal = array_sum($pendingStageCounts);
    ?>
    <section class="panel">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">Live Desk</p>
                <h2>Staff On-Leave Dashboard</h2>
            </div>
            <a class="btn btn-primary" href="<?= e(url('leave/calendar')) ?>">Open Calendar</a>
        </div>

        <section class="stats-grid">
            <article class="stat-card success">
                <span>On Leave Today</span>
                <strong><?= count($liveOverview['on_leave']) ?></strong>
            </article>
            <article class="stat-card">
                <span>Returning Today</span>
                <strong><?= count($liveOverview['returning_today']) ?></strong>
            </article>
            <article class="stat-card">
                <span>Upcoming Leave</span>
                <strong><?= count($liveOverview['upcoming']) ?></strong>
            </article>
            <article class="stat-card warning">
                <span>Pending By Stage</span>
                <strong><?= (int) $pendingStageTotal ?></strong>
            </article>
        </section>

        <div class="live-overview-grid">
            <div class="live-list">
                <h3>On Leave Today</h3>
                <?php if (!$liveOverview['on_leave']): ?>
                    <p class="muted">No approved leave covers today.</p>
                <?php else: ?>
                    <?php foreach (array_slice($liveOverview['on_leave'], 0, 8) as $leave): ?>
                        <a class="live-item" href="<?= e(url('leave/view')) ?>&id=<?= (int) $leave['id'] ?>">
                            <strong><?= e($leave['employee_name']) ?></strong>
                            <span><?= e($leave['leave_type_name']) ?> | returns <?= e(format_date($leave['end_date'])) ?></span>
                        </a>
                    <?php endforeach; ?>
                    <?php if (count($liveOverview['on_leave']) > 8): ?>
                        <p class="muted">Open the calendar to see <?= count($liveOverview['on_leave']) - 8 ?> more.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="live-list">
                <h3>Returning Today</h3>
                <?php if (!$liveOverview['returning_today']): ?>
                    <p class="muted">No approved leave ends today.</p>
                <?php else: ?>
                    <?php foreach (array_slice($liveOverview['returning_today'], 0, 8) as $leave): ?>
                        <a class="live-item" href="<?= e(url('leave/view')) ?>&id=<?= (int) $leave['id'] ?>">
                            <strong><?= e($leave['employee_name']) ?></strong>
                            <span><?= e($leave['department_name'] ?? 'No directorate') ?></span>
                        </a>
                    <?php endforeach; ?>
                    <?php if (count($liveOverview['returning_today']) > 8): ?>
                        <p class="muted">Open the calendar to see <?= count($liveOverview['returning_today']) - 8 ?> more.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="live-list">
                <h3>Upcoming Leave</h3>
                <?php if (!$liveOverview['upcoming']): ?>
                    <p class="muted">No upcoming approved leave.</p>
                <?php else: ?>
                    <?php foreach (array_slice($liveOverview['upcoming'], 0, 8) as $leave): ?>
                        <a class="live-item" href="<?= e(url('leave/view')) ?>&id=<?= (int) $leave['id'] ?>">
                            <strong><?= e($leave['employee_name']) ?></strong>
                            <span><?= e(format_date($leave['start_date'])) ?> to <?= e(format_date($leave['end_date'])) ?></span>
                        </a>
                    <?php endforeach; ?>
                    <?php if (count($liveOverview['upcoming']) > 8): ?>
                        <p class="muted">Open the calendar to see <?= count($liveOverview['upcoming']) - 8 ?> more.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="live-list">
                <h3>Pending Stages</h3>
                <div class="stage-list">
                    <?php foreach ($pendingStageCounts as $status => $total): ?>
                        <div class="stage-row">
                            <span><?= e(status_label($status)) ?></span>
                            <strong><?= (int) $total ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if (in_array($user['role'], ['admin', 'supervisor'], true)): ?>
    <section class="panel">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">Workflow</p>
                <h2><?= $user['role'] === 'admin' ? 'Approval Progress' : 'Pending Approvals' ?></h2>
            </div>
            <div class="button-row">
                <a class="btn btn-ghost" href="<?= e(url('admin/leave-requests')) ?>">All Requests</a>
                <a class="btn btn-ghost" href="<?= e(url('admin/activity')) ?>">System Logs</a>
                <a class="btn btn-primary" href="<?= e(url('approvals')) ?>"><?= $user['role'] === 'admin' ? 'Open Progress' : 'Open Approvals' ?></a>
            </div>
        </div>

        <?php if (!$pendingApprovals): ?>
            <p class="muted"><?= $user['role'] === 'admin' ? 'No leave requests are currently in progress.' : 'No requests are waiting for your action.' ?></p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Staff</th>
                        <th>Leave Type</th>
                        <th>Dates</th>
                        <th>Days</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach (array_slice($pendingApprovals, 0, 5) as $request): ?>
                        <tr>
                            <td><?= e($request['employee_name']) ?></td>
                            <td><?= e($request['leave_type_name']) ?></td>
                            <td><?= e(format_date($request['start_date'])) ?> to <?= e(format_date($request['end_date'])) ?></td>
                            <td><?= e(format_days($request['days_requested'])) ?></td>
                            <td><span class="badge warning"><?= e(status_label($request['status'])) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>
