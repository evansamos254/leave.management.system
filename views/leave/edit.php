<div class="two-column">
    <section class="panel">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">Request #<?= (int) $request['id'] ?></p>
                <h2>Edit Leave Request</h2>
            </div>
            <span class="badge warning"><?= e(status_label($request['status'])) ?></span>
        </div>

        <?php if (empty($user['gender'])): ?>
            <div class="alert alert-error">
                <?= !empty($editingAsSupervisor) ? 'This staff profile needs gender set to show gender-specific leave types.' : 'Set your gender in the profile menu to see gender-specific leave types.' ?>
            </div>
        <?php endif; ?>

        <script type="application/json" id="leave-planner-data"><?= json_encode($leavePlanner ?? ['leaveTypes' => [], 'holidays' => []], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?></script>

        <form class="form" method="post" action="<?= e(url('leave/edit')) ?>&id=<?= (int) $request['id'] ?>" enctype="multipart/form-data" data-leave-planner>
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $request['id'] ?>">
            <div class="grid-form">
                <label>
                    <span>Staff Name</span>
                    <input type="text" value="<?= e($user['full_name']) ?>" disabled>
                </label>
                <label>
                    <span>Payroll / ID number</span>
                    <input type="text" value="<?= e($employee['staff_id'] ?? 'N/A') ?>" disabled>
                </label>
                <label>
                    <span>Department</span>
                    <input type="text" value="<?= e($employee['directorate_name'] ?? 'N/A') ?>" disabled>
                </label>
                <label>
                    <span>Directorate</span>
                    <input type="text" value="<?= e($employee['department_name'] ?? 'N/A') ?>" disabled>
                </label>
                <label>
                    <span>Designation</span>
                    <input type="text" value="<?= e($employee['designation'] ?? 'N/A') ?>" disabled>
                </label>
                <label>
                    <span>Contact Number</span>
                    <input type="text" name="contact_number" value="<?= e($request['contact_number'] ?? '') ?>">
                </label>
                <label>
                    <span>Leave Type</span>
                    <select name="leave_type_id" required data-leave-type-select>
                        <option value="">Select leave type</option>
                        <?php foreach ($leaveTypes as $type): ?>
                            <option value="<?= (int) $type['id'] ?>" <?= (int) $request['leave_type_id'] === (int) $type['id'] ? 'selected' : '' ?>>
                                <?= e($type['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small data-leave-type-hint>Select a leave type to see its rules.</small>
                </label>
                <label>
                    <span>Start Date</span>
                    <input id="start_date" type="date" name="start_date" value="<?= e($request['start_date']) ?>" required data-leave-start-date>
                </label>
                <label>
                    <span>Leave Days</span>
                    <input id="requested_days_display" type="text" value="<?= e(format_days($request['days_requested'])) ?> working day(s)" readonly data-leave-days-display>
                    <small data-leave-days-hint>Weekends and public holidays are skipped.</small>
                </label>
                <label>
                    <span>End Date</span>
                    <input id="end_date" type="date" name="end_date" value="<?= e($request['end_date']) ?>" required data-leave-end-date>
                    <small>Select the last day of leave.</small>
                </label>
                <div class="span-2 leave-planner-box">
                    <div class="leave-planner-head">
                        <div>
                            <p class="eyebrow">Live Planner</p>
                            <h3>Leave Schedule</h3>
                        </div>
                        <span class="badge warning" data-planner-status>Select leave type</span>
                    </div>
                    <div class="leave-planner-metrics">
                        <div>
                            <span>Working Days</span>
                            <strong data-planner-days>-</strong>
                        </div>
                        <div>
                            <span>Leave Ends</span>
                            <strong data-planner-end>-</strong>
                        </div>
                        <div>
                            <span>Report Back</span>
                            <strong data-planner-return>-</strong>
                        </div>
                        <div>
                            <span>Balance</span>
                            <strong data-planner-balance>-</strong>
                        </div>
                    </div>
                    <p class="planner-note" data-planner-note>Choose a leave type, start date, and end date.</p>
                </div>
                <label>
                    <span>Supporting Attachment</span>
                    <input type="file" name="attachment" accept=".pdf,application/pdf">
                    <small data-planner-attachment>PDF attachment guidance will appear after selecting a leave type.</small>
                    <?php if ($request['attachment_path']): ?>
                        <small>Existing attachment will be kept unless you choose a new one.</small>
                    <?php endif; ?>
                </label>
                <label class="span-2">
                    <span>Reason / Comments</span>
                    <textarea name="reason" rows="4" placeholder="Add a short reason for the request"><?= e($request['reason'] ?? '') ?></textarea>
                </label>
                <label class="span-2">
                    <span>Handover notes</span>
                    <textarea name="handover_notes" rows="4" placeholder="State who will handle your duties and any important handover information"><?= e($request['handover_notes'] ?? '') ?></textarea>
                    <small>Include the officer covering your duties, key tasks, and urgent contacts if applicable.</small>
                </label>
            </div>
            <div class="button-row">
                <a class="btn btn-ghost" href="<?= e(url('leave/view')) ?>&id=<?= (int) $request['id'] ?>">Cancel</a>
                <button class="btn btn-primary" type="submit">Save Changes</button>
            </div>
        </form>
    </section>

    <section class="panel">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">Available</p>
                <h2><?= !empty($editingAsSupervisor) ? 'Staff Balances' : 'Your Balances' ?></h2>
            </div>
        </div>
        <div class="balance-list">
            <?php foreach ($balances as $balance): ?>
                <div class="balance-row">
                    <span><?= e($balance['name']) ?></span>
                    <strong><?= e(format_days($balance['available_days'])) ?> days</strong>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>
