<section class="panel">
    <div class="panel-heading">
        <div>
            <p class="eyebrow">Policy</p>
            <h2>Add Leave Type</h2>
        </div>
    </div>

    <form class="form leave-type-add-form" method="post" action="<?= e(url('admin/leave-types')) ?>">
        <?= csrf_field() ?>
        <div class="leave-type-fields">
            <label>
                <span>Name</span>
                <input type="text" name="name" required placeholder="e.g. Annual Leave">
            </label>
            <label>
                <span>Default Entitlement (days)</span>
                <input type="number" name="default_entitlement" min="0" step="1" value="0">
            </label>
            <label>
                <span>Who Can Apply</span>
                <select name="gender_eligibility">
                    <?php foreach (leave_gender_options() as $value => $label): ?>
                        <option value="<?= e($value) ?>"><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Attachment Required After (days)</span>
                <input type="number" name="attachment_after_days" min="0" step="1" placeholder="Leave blank if not applicable">
            </label>
        </div>
        <div class="leave-type-checks">
            <label class="check-label"><input type="checkbox" name="requires_balance" checked> Requires balance</label>
            <label class="check-label"><input type="checkbox" name="requires_attachment"> Requires attachment</label>
            <label class="check-label"><input type="checkbox" name="is_paid" checked> Paid leave</label>
            <label class="check-label"><input type="checkbox" name="is_active" checked> Active</label>
        </div>
        <div>
            <button class="btn btn-primary" type="submit">Add Leave Type</button>
        </div>
    </form>
</section>

<section class="panel">
    <div class="panel-heading">
        <div>
            <p class="eyebrow">Existing</p>
            <h2>Leave Types</h2>
        </div>
    </div>

    <?php if (empty($leaveTypes)): ?>
        <p class="muted">No leave types defined yet.</p>
    <?php else: ?>
    <div class="table-wrap">
        <table class="leave-types-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Entitlement</th>
                    <th>Eligibility</th>
                    <th>Attach. After</th>
                    <th>Balance</th>
                    <th>Attachment</th>
                    <th>Paid</th>
                    <th>Active</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($leaveTypes as $type): ?>
                <tr>
                    <form method="post" action="<?= e(url('admin/leave-types')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $type['id'] ?>">
                        <td><input class="table-input" type="text" name="name" value="<?= e($type['name']) ?>" required></td>
                        <td><input class="table-input table-input-sm" type="number" name="default_entitlement" min="0" step="1" value="<?= e(format_days($type['default_entitlement'], '')) ?>"></td>
                        <td>
                            <select class="table-input" name="gender_eligibility" aria-label="Who can apply">
                                <?php foreach (leave_gender_options() as $value => $label): ?>
                                    <option value="<?= e($value) ?>" <?= ($type['gender_eligibility'] ?? 'any') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input class="table-input table-input-sm" type="number" name="attachment_after_days" min="0" step="1" value="<?= e(format_days($type['attachment_after_days'], '')) ?>" placeholder="—"></td>
                        <td class="td-check"><input type="checkbox" name="requires_balance" <?= (int) $type['requires_balance'] === 1 ? 'checked' : '' ?>></td>
                        <td class="td-check"><input type="checkbox" name="requires_attachment" <?= (int) $type['requires_attachment'] === 1 ? 'checked' : '' ?>></td>
                        <td class="td-check"><input type="checkbox" name="is_paid" <?= (int) $type['is_paid'] === 1 ? 'checked' : '' ?>></td>
                        <td class="td-check"><input type="checkbox" name="is_active" <?= (int) $type['is_active'] === 1 ? 'checked' : '' ?>></td>
                        <td><button class="btn btn-small btn-primary" type="submit">Update</button></td>
                    </form>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</section>
