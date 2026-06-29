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
</section>

<?php if ($user['role'] === 'admin'): ?>
    <section class="stats-grid compact">
        <article class="stat-card">
            <span>Users</span>
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
            <span>Departments</span>
            <strong><?= (int) $stats['departments'] ?></strong>
        </article>
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

<?php if ($employee): ?>
    <section class="panel">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">Current Year</p>
                <h2>Leave Balances</h2>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Leave Type</th>
                    <th>Entitlement</th>
                    <th>Carried Forward</th>
                    <th>Used</th>
                    <th>Available</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($balances as $balance): ?>
                    <tr>
                        <td><?= e($balance['name']) ?></td>
                        <td><?= e(format_days($balance['entitlement'])) ?></td>
                        <td><?= e(format_days($balance['carried_forward'])) ?></td>
                        <td><?= e(format_days($balance['used_days'])) ?></td>
                        <td><span class="badge success"><?= e(format_days($balance['available_days'])) ?> days</span></td>
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
                <a class="btn btn-ghost" href="<?= e(url('admin/activity')) ?>">System Activity</a>
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
