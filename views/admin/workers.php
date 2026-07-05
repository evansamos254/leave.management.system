<section class="panel">
    <div class="panel-heading">
        <div>
            <p class="eyebrow">HR</p>
            <h2>Staff</h2>
        </div>
        <a class="btn btn-primary" href="<?= e(url('workers/create')) ?>">Add Staff</a>
    </div>

    <?php if (!$workers): ?>
        <p class="muted">No staff accounts have been created yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Staff</th>
                    <th>Payroll / ID</th>
                    <th>Department</th>
                    <th>Directorate</th>
                    <th>Role</th>
                    <th>Supervisor</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($workers as $worker): ?>
                    <?php
                    $isHrOffice = $worker['role'] === 'hr' && empty($worker['department_name']);
                    $directorateLabel = $isHrOffice ? 'HR Office' : ($worker['directorate_name'] ?? 'N/A');
                    $departmentLabel = $isHrOffice ? 'Office-level account' : ($worker['department_name'] ?? 'N/A');
                    ?>
                    <tr>
                        <td>
                        <strong><?= e($worker['full_name']) ?></strong>
                        <small><?= e($worker['email']) ?></small>
                        <small>ID: <?= e($worker['national_id'] ?? 'N/A') ?></small>
                        <small>Gender: <?= e(gender_label($worker['gender'] ?? null)) ?></small>
                        <?php if (!empty($worker['phone'])): ?>
                            <small><?= e(format_kenyan_phone_number($worker['phone'])) ?></small>
                        <?php endif; ?>
                    </td>
                        <td>
                            <?= e($worker['staff_id']) ?>
                            <small><?= e(designation_label($worker['designation'] ?? null, $worker['role'] ?? null)) ?></small>
                            <small>Job group: <?= e($worker['job_group'] ?? 'N/A') ?></small>
                        </td>
                        <td><?= e($directorateLabel) ?></td>
                        <td><?= e($departmentLabel) ?></td>
                        <td><span class="badge"><?= e(role_label($worker['role'])) ?></span></td>
                        <td><?= e($worker['supervisor_name'] ?? 'Not assigned') ?></td>
                        <?php
                        $statusClass = match ($worker['status']) {
                            'active' => 'success',
                            'pending' => 'warning',
                            default => 'danger',
                        };
                        ?>
                        <td><span class="badge <?= e($statusClass) ?>"><?= e(role_label($worker['status'])) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
