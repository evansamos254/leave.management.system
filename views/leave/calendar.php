<section class="panel">
    <div class="panel-heading">
        <div>
            <p class="eyebrow">Approved Leave</p>
            <h2><?= e($monthLabel) ?></h2>
        </div>
        <div class="button-row">
            <a class="btn btn-ghost" href="<?= e(url('leave/calendar')) ?>&month=<?= e($previousMonth) ?>">Previous</a>
            <a class="btn btn-ghost" href="<?= e(url('leave/calendar')) ?>&month=<?= e(date('Y-m')) ?>">Today</a>
            <a class="btn btn-ghost" href="<?= e(url('leave/calendar')) ?>&month=<?= e($nextMonth) ?>">Next</a>
        </div>
    </div>

    <form class="filter-bar" method="get" action="index.php">
        <input type="hidden" name="route" value="leave/calendar">
        <label>
            <span>Month</span>
            <input type="month" name="month" value="<?= e($month) ?>">
        </label>
        <button class="btn btn-primary" type="submit">Open Month</button>
    </form>

    <div class="calendar-wrap">
        <div class="calendar-grid">
            <?php foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dayName): ?>
                <div class="calendar-weekday"><?= e($dayName) ?></div>
            <?php endforeach; ?>

            <?php foreach ($weeks as $week): ?>
                <?php foreach ($week as $day): ?>
                    <div class="calendar-day <?= $day['in_month'] ? '' : 'muted-day' ?> <?= $day['is_today'] ? 'today' : '' ?>">
                        <div class="calendar-date">
                            <span><?= e((string) $day['day']) ?></span>
                            <?php if ($day['is_today']): ?>
                                <strong>Today</strong>
                            <?php endif; ?>
                        </div>

                        <div class="calendar-events">
                            <?php foreach (array_slice($day['events'], 0, 3) as $event): ?>
                                <a class="calendar-event" href="<?= e(url('leave/view')) ?>&id=<?= (int) $event['id'] ?>">
                                    <strong><?= e($event['employee_name']) ?></strong>
                                    <span><?= e($event['leave_type_name']) ?></span>
                                </a>
                            <?php endforeach; ?>

                            <?php if (count($day['events']) > 3): ?>
                                <span class="calendar-more">+<?= count($day['events']) - 3 ?> more</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="panel">
    <div class="panel-heading">
        <div>
            <p class="eyebrow">Schedule</p>
            <h2>Approved Leave This Month</h2>
        </div>
    </div>

    <?php if (!$events): ?>
        <p class="muted">No approved leave appears on this calendar month.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Staff</th>
                    <th>Department</th>
                    <th>Directorate</th>
                    <th>Leave Type</th>
                    <th>Dates</th>
                    <th>Days</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($events as $event): ?>
                    <tr>
                        <td>
                            <strong><?= e($event['employee_name']) ?></strong>
                            <small>Payroll/ID: <?= e($event['staff_id']) ?></small>
                        </td>
                        <td><?= e($event['directorate_name'] ?? 'N/A') ?></td>
                        <td><?= e($event['department_name'] ?? 'N/A') ?></td>
                        <td><?= e($event['leave_type_name']) ?></td>
                        <td><?= e(format_date($event['start_date'])) ?> to <?= e(format_date($event['end_date'])) ?></td>
                        <td><?= e(format_days($event['days_requested'])) ?></td>
                        <td><a class="btn btn-small btn-ghost" href="<?= e(url('leave/view')) ?>&id=<?= (int) $event['id'] ?>">View</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
