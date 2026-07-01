<div class="two-column">
    <section class="panel">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">Request #<?= (int) $request['id'] ?></p>
                <h2><?= e($request['leave_type_name']) ?></h2>
            </div>
            <span class="badge <?= e(status_badge_class($request['status'])) ?>">
                <?= e(status_label($request['status'])) ?>
            </span>
        </div>

        <div class="profile-grid">
            <div>
                <span>Staff</span>
                <strong><?= e($request['employee_name']) ?></strong>
            </div>
            <div>
                <span>Payroll / ID number</span>
                <strong><?= e($request['staff_id']) ?></strong>
            </div>
            <div>
                <span>Department</span>
                <strong><?= e($request['directorate_name'] ?? 'N/A') ?></strong>
            </div>
            <div>
                <span>Directorate</span>
                <strong><?= e($request['department_name'] ?? 'N/A') ?></strong>
            </div>
            <div>
                <span>Dates</span>
                <strong><?= e(format_date($request['start_date'])) ?> to <?= e(format_date($request['end_date'])) ?></strong>
            </div>
            <div>
                <span>Working Days</span>
                <strong><?= e(format_days($request['days_requested'])) ?></strong>
            </div>
            <div>
                <span>Contact</span>
                <strong><?= e(format_kenyan_phone_number($request['contact_number'] ?? '')) ?: 'N/A' ?></strong>
            </div>
        </div>

        <?php if ($request['reason']): ?>
            <div class="note-box">
                <span>Reason</span>
                <p><?= nl2br(e($request['reason'])) ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($request['handover_notes'])): ?>
            <div class="note-box">
                <span>Handover Notes</span>
                <p><?= nl2br(e($request['handover_notes'])) ?></p>
            </div>
        <?php endif; ?>

        <?php if ($request['status'] === 'approved'): ?>
            <div class="note-box">
                <span>Return / Resumption</span>
                <?php if (!empty($request['resumed_at'])): ?>
                    <p>
                        Reported back recorded on <?= e(format_date($request['resumed_at'])) ?>
                        <?php if (!empty($request['resumed_by_name'])): ?>
                            by <?= e($request['resumed_by_name']) ?>
                        <?php endif; ?>.
                    </p>
                    <?php if (!empty($request['resumption_notes'])): ?>
                        <p><?= nl2br(e($request['resumption_notes'])) ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <p>Expected report-back date: <?= e(format_date($reportBackDate ?? null)) ?>.</p>
                    <?php if (!empty($canMarkResumed)): ?>
                        <form class="form" method="post" action="<?= e(url('leave/resume')) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $request['id'] ?>">
                            <label>
                                <span>Return note</span>
                                <textarea name="resumption_notes" rows="3" placeholder="Optional note for HR records"></textarea>
                            </label>
                            <button class="btn btn-primary" type="submit">Mark Reported Back</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($request['forfeiture_id']) || !empty($canForfeit)): ?>
            <div class="note-box">
                <span>Forfeiture / Payout</span>
                <?php if (!empty($request['forfeiture_id'])): ?>
                    <p>This leave has been forfeited and the payout record has been saved.</p>
                    <p>Forfeited days: <?= e(format_days($request['days_forfeited'] ?? null)) ?>.</p>
                    <p>Payout amount: <?= e(format_currency($request['payout_amount'] ?? null)) ?>.</p>
                    <p>
                        Recorded by <?= e($request['forfeited_by_name'] ?? 'HR') ?>
                        on <?= e(format_date($request['forfeited_at'] ?? null)) ?>.
                    </p>
                    <?php if (!empty($request['forfeiture_notes'])): ?>
                        <p><?= nl2br(e($request['forfeiture_notes'])) ?></p>
                    <?php endif; ?>
                <?php elseif (!empty($canForfeit)): ?>
                    <form class="form" method="post" action="<?= e(url('leave/forfeit')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $request['id'] ?>">
                        <div class="grid-form">
                            <label>
                                <span>Forfeited days</span>
                                <input type="number" name="days_forfeited" value="<?= e(format_days($request['days_requested'] ?? null, '')) ?>" min="1" step="1" required>
                            </label>
                            <label>
                                <span>Payout amount</span>
                                <input type="number" name="payout_amount" min="0.01" step="0.01" placeholder="Enter payout amount" required>
                            </label>
                            <label class="span-2">
                                <span>Notes</span>
                                <textarea name="notes" rows="3" placeholder="Optional payment note or HR remarks"></textarea>
                            </label>
                        </div>
                        <button class="btn btn-primary" type="submit">Record Forfeiture Payout</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="button-row request-actions">
            <a class="btn btn-primary" href="<?= e(url('leave/pdf')) ?>&id=<?= (int) $request['id'] ?>">Download PDF</a>

            <?php if ($request['attachment_path']): ?>
                <a class="btn btn-ghost" href="<?= e(url('leave/attachment')) ?>&id=<?= (int) $request['id'] ?>">Download Attachment</a>
            <?php endif; ?>

            <?php if (!empty($canEdit)): ?>
                <a class="btn btn-ghost" href="<?= e(url('leave/edit')) ?>&id=<?= (int) $request['id'] ?>">Edit Request</a>
            <?php endif; ?>

            <?php if ((int) $request['employee_user_id'] === (int) current_user()['id'] && str_starts_with($request['status'], 'pending_')): ?>
                <form method="post" action="<?= e(url('leave/cancel')) ?>" class="confirm-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $request['id'] ?>">
                    <button class="btn btn-danger" type="submit">Cancel Request</button>
                </form>
            <?php endif; ?>
        </div>

        <?php if (!empty($canApprove)): ?>
            <form class="approval-form" method="post" action="<?= e(url('approvals/action')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $request['id'] ?>">
                <label>
                    <span>Comments</span>
                    <textarea name="comments" rows="3" placeholder="Approval note or rejection reason"></textarea>
                </label>
                <div class="button-row">
                    <button class="btn btn-danger" type="submit" name="action" value="reject">Reject</button>
                    <button class="btn btn-primary" type="submit" name="action" value="approve">Approve</button>
                </div>
            </form>
        <?php endif; ?>
    </section>

    <section class="panel">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">Workflow</p>
                <h2>Approval Steps</h2>
            </div>
        </div>

        <div class="timeline">
            <?php foreach ($steps as $step): ?>
                <div class="timeline-item <?= e($step['action']) ?>">
                    <div class="timeline-dot"></div>
                    <div>
                        <strong><?= e(role_label($step['role'])) ?></strong>
                        <span><?= e(status_label($step['action'])) ?></span>
                        <?php if ($step['approver_name']): ?>
                            <small>By <?= e($step['approver_name']) ?> on <?= e(format_date($step['acted_at'])) ?></small>
                        <?php endif; ?>
                        <?php if ($step['comments']): ?>
                            <p><?= nl2br(e($step['comments'])) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>
