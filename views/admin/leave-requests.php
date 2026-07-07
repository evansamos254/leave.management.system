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

<section class="panel">
    <?php $viewer = current_user(); ?>
    <div class="panel-heading">
        <div>
            <p class="eyebrow">System Monitor</p>
            <h2>All Leave Requests</h2>
        </div>
    </div>

    <form class="filter-bar" method="get" action="index.php">
        <input type="hidden" name="route" value="admin/leave-requests">
        <label>
            <span>Search</span>
            <input type="search" name="search" value="<?= e($search) ?>" placeholder="Name, email, payroll number, job group, leave type">
        </label>
        <label>
            <span>Status</span>
            <select name="status">
                <?php foreach ($statuses as $option): ?>
                    <option value="<?= e($option) ?>" <?= $status === $option ? 'selected' : '' ?>>
                        <?= $option === '' ? 'All Statuses' : e(status_label($option)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <button class="btn btn-ghost" type="submit">Filter</button>
        <a class="btn btn-ghost" href="<?= e(url('admin/leave-requests')) ?>">Reset</a>
    </form>

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
                <th>Status</th>
                <th>Return</th>
                <th>Submitted</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$requests): ?>
                <tr>
                    <td colspan="10" class="muted">No leave requests found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($requests as $request): ?>
                    <?php
                    $displayStatus = !empty($request['recalled_at']) ? 'recalled' : $request['status'];
                    $statusClass = !empty($request['recalled_at']) ? 'warning' : status_badge_class($request['status']);
                    ?>
                    <tr>
                        <td>
                            <strong><?= e($request['employee_name']) ?></strong>
                            <small>Payroll/ID: <?= e($request['staff_id']) ?> | <?= e($request['employee_email']) ?></small>
                            <small>Job group: <?= e($request['job_group'] ?? 'N/A') ?></small>
                        </td>
                        <td><?= e($request['directorate_name'] ?? 'N/A') ?></td>
                        <td><?= e($request['department_name'] ?? 'N/A') ?></td>
                        <td><?= e($request['leave_type_name']) ?></td>
                        <td><?= e(format_date($request['start_date'])) ?> to <?= e(format_date($request['end_date'])) ?></td>
                        <td><?= e(format_days($request['days_requested'])) ?></td>
                        <td>
                            <span class="badge <?= e($statusClass) ?>"><?= e(status_label($displayStatus)) ?></span>
                            <?php if (!empty($request['recalled_at'])): ?>
                                <small class="badge warning">Recalled <?= e(format_date($request['recalled_at'])) ?></small>
                                <?php if (!empty($request['recalled_by_name'])): ?>
                                    <small>By <?= e($request['recalled_by_name']) ?></small>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php if (!empty($request['forfeiture_id'])): ?>
                                <small><?= e(format_currency($request['payout_amount'] ?? null)) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($request['status'] === 'approved'): ?>
                                <?php if (!empty($request['resumed_at'])): ?>
                                    <span class="badge success">Reported <?= e(format_date($request['resumed_at'])) ?></span>
                                <?php else: ?>
                                    <span class="badge warning">Due <?= e(format_date(LeaveBalanceService::returnDateAfter($request['end_date']))) ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e(format_date($request['submitted_at'])) ?></td>
                        <td>
                            <a class="btn btn-small" href="<?= e(url('leave/view')) ?>&id=<?= (int) $request['id'] ?>">View</a>
                            <a class="btn btn-small btn-ghost" href="<?= e(url('leave/pdf')) ?>&id=<?= (int) $request['id'] ?>">PDF</a>
                            <?php if (($viewer['role'] ?? '') === 'supervisor' && $request['status'] === 'approved' && empty($request['recalled_at']) && empty($request['resumed_at'])): ?>
                                <a class="btn btn-small btn-warning" href="<?= e(url('leave/view')) ?>&id=<?= (int) $request['id'] ?>#official-recall-form">Recall</a>
                            <?php endif; ?>
                            <?php if (!empty($request['recall_attachment_path'])): ?>
                                <a class="btn btn-small btn-ghost" href="<?= e(url('leave/recall-attachment')) ?>&id=<?= (int) $request['id'] ?>" target="_blank" rel="noopener">Recall Letter</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
