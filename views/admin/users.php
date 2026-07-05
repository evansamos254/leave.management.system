<section class="panel">
    <?php
    $viewer = current_user();
    $privilegedRoles = ['admin', 'supervisor', 'hr', 'director', 'chief_officer'];
    ?>
    <div class="panel-heading">
        <div>
            <p class="eyebrow"><?= e(role_label($viewer['role'])) ?></p>
            <h2>User Management</h2>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>User</th>
                <th>Payroll / ID</th>
                <th>Department</th>
                <th>Directorate</th>
                <th>Access</th>
                <th>Supervisor</th>
                <th>Save</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $account): ?>
                <?php
                $canEditAccess = $viewer['role'] === 'admin' || !in_array($account['role'], $privilegedRoles, true);
                $canAdminManage = $viewer['role'] === 'admin' && $account['role'] !== 'admin';
                $canViewHistory = $viewer['role'] === 'admin';
                $isHrOffice = $account['role'] === 'hr' && empty($account['department_name']);
                $directorateLabel = $isHrOffice ? 'HR Office' : ($account['directorate_name'] ?? 'N/A');
                $departmentLabel = $isHrOffice ? 'Office-level account' : ($account['department_name'] ?? 'N/A');
                ?>
                <tr>
                    <td>
                        <strong><?= e($account['full_name']) ?></strong>
                        <small><?= e($account['email']) ?></small>
                        <small>ID: <?= e($account['national_id'] ?? 'N/A') ?></small>
                        <small>Gender: <?= e(gender_label($account['gender'] ?? null)) ?></small>
                        <?php if (!empty($account['phone'])): ?>
                            <small><?= e(format_kenyan_phone_number($account['phone'])) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= e($account['staff_id'] ?? 'N/A') ?>
                        <small><?= e(designation_label($account['designation'] ?? null, $account['role'] ?? null)) ?></small>
                        <small>Job group: <?= e($account['job_group'] ?? 'N/A') ?></small>
                    </td>
                    <td><?= e($directorateLabel) ?></td>
                    <td><?= e($departmentLabel) ?></td>
                    <td>
                        <?php if ($canEditAccess): ?>
                            <form class="table-form" method="post" action="<?= e(url('admin/users/update')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $account['id'] ?>">
                                <input type="hidden" name="employee_id" value="<?= (int) ($account['employee_id'] ?? 0) ?>">
                                <select name="role">
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?= e($role) ?>" <?= $account['role'] === $role ? 'selected' : '' ?>>
                                            <?= e(role_label($role)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="status">
                                    <?php foreach ($statuses as $status): ?>
                                        <option value="<?= e($status) ?>" <?= $account['status'] === $status ? 'selected' : '' ?>>
                                            <?= e(role_label($status)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                        <?php else: ?>
                            <span class="badge"><?= e(role_label($account['role'])) ?></span>
                            <small><?= e(role_label($account['status'])) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                            <?php if ($canEditAccess && $account['employee_id']): ?>
                                <select name="supervisor_id">
                                    <option value="">No assigned supervisor</option>
                                    <?php foreach ($approvers as $approver): ?>
                                        <?php if ((int) $approver['employee_id'] === (int) $account['employee_id']) {
                                            continue;
                                        } ?>
                                        <option value="<?= (int) $approver['employee_id'] ?>"
                                            <?= (int) ($account['supervisor_id'] ?? 0) === (int) $approver['employee_id'] ? 'selected' : '' ?>>
                                            <?= e($approver['full_name']) ?> (<?= e(role_label($approver['role'])) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <span class="muted"><?= e($account['supervisor_name'] ?? 'N/A') ?></span>
                            <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($canEditAccess): ?>
                            <button class="btn btn-small btn-primary" type="submit">Save</button>
                        </form>
                        <?php else: ?>
                            <span class="muted">Admin only</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="button-row">
                            <?php if ($canViewHistory): ?>
                                <a class="btn btn-small btn-ghost" href="<?= e(url('admin/users/history')) ?>&id=<?= (int) $account['id'] ?>">History</a>
                            <?php endif; ?>
                            <?php if ($canAdminManage): ?>
                                <a class="btn btn-small btn-ghost" href="<?= e(url('admin/users/edit')) ?>&id=<?= (int) $account['id'] ?>">Edit Profile</a>
                                <form method="post" action="<?= e(url('admin/users/reset-password')) ?>" class="inline-form confirm-form" data-confirm="Reset this staff password and generate a temporary password?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $account['id'] ?>">
                                    <button class="btn btn-small btn-ghost" type="submit">Reset Password</button>
                                </form>
                                <?php if (in_array($account['status'], ['active', 'inactive'], true)): ?>
                                    <form method="post" action="<?= e(url('admin/users/toggle-status')) ?>" class="inline-form confirm-form" data-confirm="<?= $account['status'] === 'active' ? 'Deactivate this staff account?' : 'Reactivate this staff account?' ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $account['id'] ?>">
                                        <button class="btn btn-small <?= $account['status'] === 'active' ? 'btn-danger' : 'btn-primary' ?>" type="submit">
                                            <?= $account['status'] === 'active' ? 'Deactivate' : 'Reactivate' ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" action="<?= e(url('admin/users/delete')) ?>" class="inline-form confirm-form" data-confirm="Delete this staff account and its related leave records?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $account['id'] ?>">
                                    <button class="btn btn-small btn-danger" type="submit">Delete</button>
                                </form>
                            <?php else: ?>
                            <span class="muted"><?= $viewer['role'] === 'admin' ? 'Protected' : 'Admin only' ?></span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
