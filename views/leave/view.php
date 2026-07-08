<div class="two-column">
    <section class="panel">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">Request #<?= (int) $request['id'] ?></p>
                <h2><?= e($request['leave_type_name']) ?></h2>
            </div>
            <?php $hasRecall = has_official_leave_recall($request); $displayStatus = $hasRecall ? 'recalled' : $request['status']; ?>
            <span class="badge <?= e($hasRecall ? 'warning' : status_badge_class($request['status'])) ?>">
                <?= e(status_label($displayStatus)) ?>
            </span>
            <?php if ($hasRecall): ?>
                <span class="badge warning">Recalled</span>
            <?php endif; ?>
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
                <span>Job group</span>
                <strong><?= e($request['job_group'] ?? 'N/A') ?></strong>
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

        <?php if (!empty($canRecall) && !has_official_leave_recall($request)): ?>
            <div class="note-box">
                <span>Official Recall</span>
                <p>The immediate supervisor can recall this approved leave request. Upload the signed PDF recall letter to make the action official and notify the staff member.</p>
                <p>
                    <a class="btn btn-small btn-primary" href="#official-recall-form">Open recall form</a>
                </p>
            </div>
        <?php endif; ?>

        <?php if (!empty($request['passport_photo_path'])): ?>
            <div class="note-box">
                <span>Passport Photo</span>
                <p>Attached to this leave request.</p>
                <a href="<?= e(url('leave/passport-photo')) ?>&id=<?= (int) $request['id'] ?>" target="_blank" rel="noopener">
                    <img class="passport-photo-preview" src="<?= e(url('leave/passport-photo')) ?>&id=<?= (int) $request['id'] ?>" alt="Passport photo preview">
                </a>
            </div>
        <?php endif; ?>

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
                <?php if ($hasRecall): ?>
                    <p>
                        <strong>Recall notice:</strong>
                        This leave was recalled on <?= e(format_date($request['recalled_at'])) ?>
                        <?php if (!empty($recalledByName)): ?>
                            by <?= e($recalledByName) ?>
                        <?php endif; ?>.
                    </p>
                    <?php if (!empty($request['recall_reason'])): ?>
                        <p><?= nl2br(e($request['recall_reason'])) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($request['recall_attachment_path'])): ?>
                        <p>
                            <a class="btn btn-small btn-ghost" href="<?= e(url('leave/recall-attachment')) ?>&id=<?= (int) $request['id'] ?>" target="_blank" rel="noopener">
                                View Recall Letter
                            </a>
                        </p>
                    <?php endif; ?>
                <?php endif; ?>
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
                    <?php if (!$hasRecall): ?>
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
                    <?php else: ?>
                        <p>The leave has been officially recalled by the supervisor. A separate report-back record has not been logged.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($canRecall)): ?>
            <div class="note-box" id="official-recall-form">
                <span>Official Recall</span>
                <div
                    class="recall-preview"
                    data-recall-preview
                    data-recall-employee="<?= e($request['employee_name']) ?>"
                    data-recall-supervisor="<?= e(current_user()['full_name'] ?? 'Immediate supervisor') ?>"
                    data-recall-leave-type="<?= e($request['leave_type_name']) ?>"
                    data-recall-leave-period="<?= e(format_date($request['start_date'])) ?> to <?= e(format_date($request['end_date'])) ?>"
                    data-recall-report-back="<?= e(format_date($reportBackDate)) ?>"
                >
                    <span>Recall Preview</span>
                    <div class="approval-meta recall-preview-meta">
                        <div>
                            <span>Leave period</span>
                            <strong><?= e(format_date($request['start_date'])) ?> to <?= e(format_date($request['end_date'])) ?></strong>
                        </div>
                        <div>
                            <span>Expected report-back date</span>
                            <strong><?= e(format_date($reportBackDate)) ?></strong>
                        </div>
                        <div>
                            <span>Recalled by</span>
                            <strong><?= e(current_user()['full_name'] ?? 'Immediate supervisor') ?></strong>
                        </div>
                        <div>
                            <span>Attachment</span>
                            <strong data-recall-preview-attachment>Not selected yet</strong>
                        </div>
                    </div>
                    <p class="recall-preview-message" data-recall-preview-message>
                        Type a recall reason to preview the official notice that will be emailed with the signed PDF letter attached.
                    </p>
                </div>
                <form class="form" method="post" action="<?= e(url('leave/recall')) ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $request['id'] ?>">
                    <label>
                        <span>Recall reason *</span>
                        <textarea name="recall_reason" rows="3" class="<?= has_field_error('recall_reason') ? 'is-invalid' : '' ?>" placeholder="Explain why the employee is being recalled" data-recall-reason-input required><?= e(old('recall_reason')) ?></textarea>
                        <?php if ($error = field_error('recall_reason')): ?><small class="field-error"><?= e($error) ?></small><?php endif; ?>
                    </label>
                    <label>
                        <span>Official recall letter (PDF) *</span>
                        <input type="file" name="recall_attachment" accept=".pdf,application/pdf" class="<?= has_field_error('recall_attachment') ? 'is-invalid' : '' ?>" data-recall-attachment-input required>
                        <?php if ($error = field_error('recall_attachment')): ?><small class="field-error"><?= e($error) ?></small><?php endif; ?>
                        <small>Upload the signed PDF recall letter for the employee record.</small>
                    </label>
                    <button class="btn btn-primary" type="submit">Recall from Leave</button>
                </form>
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
