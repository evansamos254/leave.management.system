<?php
$isAdminAccount = ($account['role'] ?? '') === 'admin';
$canManageAccount = !$isAdminAccount;
$status = $account['status'] ?? 'inactive';
$activityCount = count($activity ?? []);
$leaveCount = count($leaveRequests ?? []);
$lastLogin = !empty($account['last_login_at']) ? date('d M Y H:i', strtotime((string) $account['last_login_at'])) : 'Never';
?>
<section class="panel">
    <div class="panel-heading">
        <div>
            <p class="eyebrow">Admin</p>
            <h2><?= e($account['full_name']) ?> History</h2>
        </div>
        <div class="button-row">
            <a class="btn btn-ghost" href="<?= e(url('admin/users')) ?>">Back to Users</a>
            <?php if ($canManageAccount): ?>
                <a class="btn btn-ghost" href="<?= e(url('admin/users/edit')) ?>&id=<?= (int) $account['id'] ?>">Edit Profile</a>
                <form method="post" action="<?= e(url('admin/users/reset-password')) ?>" class="inline-form confirm-form" data-confirm="Reset this staff password and generate a temporary password?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $account['id'] ?>">
                    <button class="btn btn-ghost" type="submit">Reset Password</button>
                </form>
                <?php if (in_array($status, ['active', 'inactive'], true)): ?>
                    <form method="post" action="<?= e(url('admin/users/toggle-status')) ?>" class="inline-form confirm-form" data-confirm="<?= $status === 'active' ? 'Deactivate this staff account?' : 'Reactivate this staff account?' ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $account['id'] ?>">
                        <button class="btn <?= $status === 'active' ? 'btn-danger' : 'btn-primary' ?>" type="submit">
                            <?= $status === 'active' ? 'Deactivate' : 'Reactivate' ?>
                        </button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <section class="stats-grid compact">
        <article class="stat-card">
            <span>Role</span>
            <strong><?= e(role_label($account['role'] ?? 'employee')) ?></strong>
        </article>
        <article class="stat-card">
            <span>Status</span>
            <strong><?= e(status_label($status)) ?></strong>
        </article>
        <article class="stat-card">
            <span>Activity Logs</span>
            <strong><?= (int) $activityCount ?></strong>
        </article>
        <article class="stat-card">
            <span>Leave Requests</span>
            <strong><?= (int) $leaveCount ?></strong>
        </article>
    </section>

    <div class="profile-grid">
        <div>
            <span>Email</span>
            <?= e($account['email'] ?? 'N/A') ?>
        </div>
        <div>
            <span>National ID</span>
            <?= e($account['national_id'] ?? 'N/A') ?>
        </div>
        <div>
            <span>Payroll / ID</span>
            <?= e($account['staff_id'] ?? 'N/A') ?>
        </div>
        <div>
            <span>Phone</span>
            <?= e(!empty($account['phone']) ? format_kenyan_phone_number($account['phone']) : 'N/A') ?>
        </div>
        <div>
            <span>Department</span>
            <?= e($account['department_name'] ?? 'N/A') ?>
        </div>
        <div>
            <span>Directorate</span>
            <?= e($account['directorate_name'] ?? 'N/A') ?>
        </div>
        <div>
            <span>Designation</span>
            <?= e(designation_label($account['designation'] ?? null, $account['role'] ?? null)) ?>
        </div>
        <div>
            <span>Last Login</span>
            <?= e($lastLogin) ?>
        </div>
    </div>
</section>

<section class="panel">
    <div class="panel-heading">
        <div>
            <p class="eyebrow">Audit Trail</p>
            <h2>Account Activity</h2>
        </div>
    </div>

    <?php if (!$activity): ?>
        <p class="muted">No account activity has been recorded for this staff member yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Time</th>
                    <th>Activity</th>
                    <th>Actor</th>
                    <th>Record</th>
                    <th>IP Address</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($activity as $log): ?>
                    <?php
                    $recordLabel = 'N/A';
                    if (!empty($log['leave_request_id'])) {
                        $recordLabel = 'Leave #' . (int) $log['leave_request_id'];
                        $details = [];
                        if (!empty($log['leave_type_name'])) {
                            $details[] = $log['leave_type_name'];
                        }
                        if (!empty($log['leave_start_date']) || !empty($log['leave_end_date'])) {
                            $details[] = format_date($log['leave_start_date'] ?? null) . ' to ' . format_date($log['leave_end_date'] ?? null);
                        }
                        if (!empty($log['leave_days_requested'])) {
                            $details[] = format_days($log['leave_days_requested']);
                        }
                        if (!empty($details)) {
                            $recordLabel .= ' — ' . implode(' | ', $details);
                        }
                    } elseif (!empty($log['entity_type'])) {
                        $recordLabel = status_label((string) $log['entity_type']);
                        if (!empty($log['entity_id'])) {
                            $recordLabel .= ' #' . (int) $log['entity_id'];
                        }
                    }
                    ?>
                    <tr>
                        <td><?= e(date('d M Y H:i', strtotime($log['created_at']))) ?></td>
                        <td><span class="badge"><?= e(audit_action_label($log['action'])) ?></span></td>
                        <td>
                            <strong><?= e($log['actor_name'] ?? 'System') ?></strong>
                            <?php if (!empty($log['actor_email'])): ?>
                                <small><?= e($log['actor_email']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= e($recordLabel) ?></td>
                        <td><?= e($log['ip_address'] ?? 'N/A') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="panel">
    <div class="panel-heading">
        <div>
            <p class="eyebrow">Leave History</p>
            <h2>Leave Requests</h2>
        </div>
    </div>

    <?php if (!$employee): ?>
        <p class="muted">This account does not have a linked staff profile, so leave records are not available.</p>
    <?php elseif (!$leaveRequests): ?>
        <p class="muted">No leave requests have been submitted yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Reference</th>
                    <th>Leave Type</th>
                    <th>Period</th>
                    <th>Days</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($leaveRequests as $request): ?>
                    <tr>
                        <td>LAF-<?= (int) $request['id'] ?></td>
                        <td><?= e($request['leave_type_name']) ?></td>
                        <td><?= e(format_date($request['start_date'])) ?> to <?= e(format_date($request['end_date'])) ?></td>
                        <td><?= e(format_days($request['days_requested'])) ?></td>
                        <td><span class="badge <?= e(status_badge_class($request['status'])) ?>"><?= e(status_label($request['status'])) ?></span></td>
                        <td><?= e(format_date($request['submitted_at'])) ?></td>
                        <td>
                            <div class="button-row">
                                <a class="btn btn-small btn-ghost" href="<?= e(url('leave/view')) ?>&id=<?= (int) $request['id'] ?>">View</a>
                                <a class="btn btn-small btn-ghost" href="<?= e(url('leave/pdf')) ?>&id=<?= (int) $request['id'] ?>">PDF</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
