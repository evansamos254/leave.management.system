<section class="panel">
    <div class="panel-heading">
        <div>
            <p class="eyebrow">Analytics</p>
            <h2>Leave Reports</h2>
        </div>
        <?php if (in_array(current_user()['role'], ['admin', 'hr', 'director', 'supervisor'], true)): ?>
            <div class="actions">
                <a class="btn btn-ghost" href="<?= e(url('reports/csv') . ($reportQuery ? '&' . $reportQuery : '')) ?>">Export CSV</a>
                <a class="btn btn-primary" href="<?= e(url('reports/pdf') . ($reportQuery ? '&' . $reportQuery : '')) ?>">Export PDF</a>
            </div>
        <?php endif; ?>
    </div>

    <form class="filter-bar" method="get" action="index.php">
        <input type="hidden" name="route" value="reports">
        <label>
            <span>Department</span>
            <select name="directorate_id" data-directorate-select data-allow-all-departments="true">
                <option value="">All departments</option>
                <?php foreach ($directorates as $directorate): ?>
                    <option value="<?= e((string) $directorate['id']) ?>" <?= (int) $directorateId === (int) $directorate['id'] ? 'selected' : '' ?>>
                        <?= e($directorate['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Directorate</span>
            <select name="department_id" data-department-select>
                <option value="">All directorates</option>
                <?php foreach ($departments as $department): ?>
                    <option
                        value="<?= e((string) $department['id']) ?>"
                        data-directorate-id="<?= e((string) ($department['directorate_id'] ?? '')) ?>"
                        <?= (int) $departmentId === (int) $department['id'] ? 'selected' : '' ?>
                    >
                        <?= e($department['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>From</span>
            <input type="date" name="from" value="<?= e($from) ?>">
        </label>
        <label>
            <span>To</span>
            <input type="date" name="to" value="<?= e($to) ?>">
        </label>
        <button class="btn btn-ghost" type="submit">Filter</button>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Leave Type</th>
                <th>Approved Requests</th>
                <th>Total Days</th>
                <th>Scope</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$summary): ?>
                <tr>
                    <td colspan="4" class="muted">No approved leave requests found for the selected report filters.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($summary as $row): ?>
                    <tr>
                        <td><?= e($row['leave_type_name']) ?></td>
                        <td><?= e((string) $row['request_count']) ?></td>
                        <td><?= e(format_days($row['total_days'])) ?></td>
                        <td>
                            <?= e($selectedDirectorate['name'] ?? 'All departments') ?>
                            /
                            <?= e($selectedDepartment['name'] ?? 'All directorates') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel">
    <div class="panel-heading">
        <div>
            <p class="eyebrow">Workflow</p>
            <h2>Pending Requests</h2>
        </div>
    </div>

    <?php if (!$pending): ?>
        <p class="muted">No pending requests in your current queue.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Staff</th>
                    <th>Leave Type</th>
                    <th>Dates</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($pending as $request): ?>
                    <tr>
                        <td>
                            <strong><?= e($request['employee_name']) ?></strong>
                            <small>Job group: <?= e($request['job_group'] ?? 'N/A') ?></small>
                        </td>
                        <td><?= e($request['leave_type_name']) ?></td>
                        <td><?= e(format_date($request['start_date'])) ?> to <?= e(format_date($request['end_date'])) ?></td>
                        <td><span class="badge warning"><?= e(status_label($request['status'])) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
