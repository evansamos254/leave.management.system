<section class="panel">
    <div class="panel-heading">
        <div>
            <p class="eyebrow"><?= e(role_label($user['role'])) ?> <?= $user['role'] === 'admin' ? 'View' : 'Queue' ?></p>
            <h2><?= $user['role'] === 'admin' ? 'Approval Progress' : 'Pending Leave Requests' ?></h2>
        </div>
    </div>

    <?php if (!$requests): ?>
        <p class="muted"><?= $user['role'] === 'admin' ? 'No leave requests are currently in progress.' : 'No leave requests are pending at your approval stage.' ?></p>
    <?php else: ?>
        <div class="approval-list">
            <?php foreach ($requests as $request): ?>
                <article class="approval-card">
                    <div class="approval-main">
                        <div>
                            <p class="eyebrow">Request #<?= (int) $request['id'] ?></p>
                            <h3><?= e($request['employee_name']) ?></h3>
                            <p class="muted">
                                Payroll/ID: <?= e($request['staff_id']) ?> | <?= e($request['directorate_name'] ?? 'No department') ?> | <?= e($request['department_name'] ?? 'No directorate') ?>
                            </p>
                        </div>
                        <span class="badge warning"><?= e(status_label($request['status'])) ?></span>
                    </div>

                    <div class="approval-meta">
                        <div>
                            <span>Leave Type</span>
                            <strong><?= e($request['leave_type_name']) ?></strong>
                        </div>
                        <div>
                            <span>Dates</span>
                            <strong><?= e(format_date($request['start_date'])) ?> to <?= e(format_date($request['end_date'])) ?></strong>
                        </div>
                        <div>
                            <span>Working Days</span>
                            <strong><?= e(format_days($request['days_requested'])) ?></strong>
                        </div>
                    </div>

                    <?php if ($user['role'] !== 'supervisor'): ?>
                        <div class="button-row request-actions">
                            <a class="btn btn-ghost" href="<?= e(url('leave/view')) ?>&id=<?= (int) $request['id'] ?>">View Details</a>
                        </div>
                    <?php else: ?>
                        <div class="button-row request-actions">
                            <a class="btn btn-primary" href="<?= e(url('leave/view')) ?>&id=<?= (int) $request['id'] ?>">View Details</a>
                            <a class="btn btn-ghost" href="<?= e(url('leave/edit')) ?>&id=<?= (int) $request['id'] ?>">Edit Before Approval</a>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
