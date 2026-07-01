<div class="two-column">
    <section class="panel">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">Account Request #<?= (int) $request['id'] ?></p>
                <h2><?= e($request['full_name']) ?></h2>
            </div>
            <span class="badge warning"><?= e(status_label($request['status'])) ?></span>
        </div>

        <div class="profile-grid">
            <div>
                <span>Applicant name</span>
                <strong><?= e($request['full_name']) ?></strong>
            </div>
            <div>
                <span>Email address</span>
                <strong><?= e($request['email']) ?></strong>
            </div>
            <div>
                <span>National ID</span>
                <strong><?= e($request['national_id'] ?? 'N/A') ?></strong>
            </div>
            <div>
                <span>Gender</span>
                <strong><?= e(gender_label($request['gender'] ?? null)) ?></strong>
            </div>
            <div>
                <span>Phone number</span>
                <strong><?= e(format_kenyan_phone_number($request['phone'] ?? '') ?: 'N/A') ?></strong>
            </div>
            <div>
                <span>Requested role</span>
                <strong><?= e(role_label($request['role'])) ?></strong>
            </div>
            <div>
                <span>Payroll / ID number</span>
                <strong><?= e($request['staff_id'] ?? 'N/A') ?></strong>
            </div>
            <div>
                <span>Designation</span>
                <strong><?= e(designation_label($request['designation'] ?? null, $request['role'] ?? null)) ?></strong>
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
                <span>Employment date</span>
                <strong><?= e(format_date($request['employment_date'] ?? null)) ?></strong>
            </div>
            <div>
                <span>Submitted</span>
                <strong><?= e(format_date($request['created_at'])) ?></strong>
            </div>
        </div>

        <div class="button-row request-actions">
            <a class="btn btn-ghost" href="<?= e(url('admin/account-requests')) ?>">Back to Requests</a>

            <?php if (!empty($canAct)): ?>
                <form method="post" action="<?= e(url('admin/account-requests/action')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $request['id'] ?>">
                    <input type="hidden" name="action" value="approve">
                    <button class="btn btn-primary" type="submit">Approve Account</button>
                </form>
                <form class="form" method="post" action="<?= e(url('admin/account-requests/action')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $request['id'] ?>">
                    <input type="hidden" name="action" value="reject">
                    <label>
                        <span>Rejection note</span>
                        <textarea name="rejection_reason" rows="3" placeholder="Write why this account request is being rejected" required></textarea>
                    </label>
                    <button class="btn btn-danger" type="submit">Reject Request</button>
                </form>
            <?php else: ?>
                <span class="badge warning">Waiting for ICT approval</span>
            <?php endif; ?>
        </div>
    </section>

    <section class="panel">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">Employment Proof</p>
                <h2>Attached Form</h2>
            </div>
            <?php if ($documentUrl): ?>
                <a class="btn btn-small btn-ghost" href="<?= e($documentUrl) ?>" target="_blank" rel="noopener">Open Form</a>
            <?php endif; ?>
        </div>

        <?php if (!$documentUrl): ?>
            <div class="alert alert-error">Supporting document is missing.</div>
        <?php elseif ($canPreviewDocument): ?>
            <div class="document-preview">
                <iframe class="document-preview-frame" src="<?= e($documentUrl) ?>" title="Employment supporting document"></iframe>
            </div>
        <?php else: ?>
            <div class="note-box">
                <span>Attached file</span>
                <p><?= e(basename((string) $request['employment_document_path'])) ?></p>
                <a class="btn btn-primary" href="<?= e($documentUrl) ?>" target="_blank" rel="noopener">View Attached Form</a>
            </div>
        <?php endif; ?>
    </section>
</div>
