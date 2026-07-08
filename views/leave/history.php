<?php if ($employee): ?>
    <section class="panel">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">Financial Year <?= e($financialYearLabel ?? financial_year_label()) ?></p>
                <h2>Leave Balances</h2>
            </div>
            <a class="btn btn-primary" href="<?= e(url('leave/apply')) ?>">New Request</a>
        </div>

        <?php if (!$balances): ?>
            <p class="muted">No tracked leave balances are available for this profile.</p>
        <?php else: ?>
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
        <?php endif; ?>
    </section>
<?php endif; ?>

<section class="panel">
    <div class="panel-heading">
        <div>
            <p class="eyebrow">Records</p>
            <h2>My Leave History</h2>
        </div>
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
                        <td>
                            <?php $hasRecall = has_official_leave_recall($request); $displayStatus = $hasRecall ? 'recalled' : $request['status']; ?>
                            <span class="badge <?= e($hasRecall ? 'warning' : status_badge_class($request['status'])) ?>"><?= e(status_label($displayStatus)) ?></span>
                            <?php if ($hasRecall): ?>
                                <small class="badge warning">Recalled <?= e(format_date($request['recalled_at'])) ?></small>
                                <?php if (!empty($request['recalled_by_name'])): ?>
                                    <small>By <?= e($request['recalled_by_name']) ?></small>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php if (!empty($request['forfeiture_id'])): ?>
                                <small>Payout <?= e(format_currency($request['payout_amount'] ?? null)) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= e(format_date($request['submitted_at'])) ?></td>
                        <td>
                            <div class="button-row">
                                <a class="btn btn-small" href="<?= e(url('leave/view')) ?>&id=<?= (int) $request['id'] ?>">View</a>
                                <a class="btn btn-small btn-ghost" href="<?= e(url('leave/pdf')) ?>&id=<?= (int) $request['id'] ?>">PDF</a>
                                <?php if (!empty($request['recall_attachment_path'])): ?>
                                    <a class="btn btn-small btn-ghost" href="<?= e(url('leave/recall-attachment')) ?>&id=<?= (int) $request['id'] ?>" target="_blank" rel="noopener">Recall Letter</a>
                                <?php endif; ?>
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
