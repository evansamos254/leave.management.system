<section class="panel">
    <div class="panel-heading">
        <div>
            <p class="eyebrow">Records</p>
            <h2>My Leave History</h2>
        </div>
        <?php if ($employee): ?>
            <a class="btn btn-primary" href="<?= e(url('leave/apply')) ?>">New Request</a>
        <?php endif; ?>
    </div>

    <?php if (!$employee): ?>
        <p class="muted">This account does not have a staff profile.</p>
    <?php elseif (!$requests): ?>
        <p class="muted">No leave requests submitted yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Leave Type</th>
                    <th>Dates</th>
                    <th>Days</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($requests as $request): ?>
                    <tr>
                        <td><?= e($request['leave_type_name']) ?></td>
                        <td><?= e(format_date($request['start_date'])) ?> to <?= e(format_date($request['end_date'])) ?></td>
                        <td><?= e(format_days($request['days_requested'])) ?></td>
                        <td><span class="badge <?= str_starts_with($request['status'], 'pending_') ? 'warning' : e($request['status']) ?>"><?= e(status_label($request['status'])) ?></span></td>
                        <td><?= e(format_date($request['submitted_at'])) ?></td>
                        <td>
                            <div class="button-row">
                                <a class="btn btn-small" href="<?= e(url('leave/view')) ?>&id=<?= (int) $request['id'] ?>">View</a>
                                <a class="btn btn-small btn-ghost" href="<?= e(url('leave/pdf')) ?>&id=<?= (int) $request['id'] ?>">PDF</a>
                                <?php if ($request['status'] === 'pending_supervisor'): ?>
                                    <a class="btn btn-small btn-ghost" href="<?= e(url('leave/edit')) ?>&id=<?= (int) $request['id'] ?>">Edit</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
