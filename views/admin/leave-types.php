<div class="two-column">
    <section class="panel">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">Policy</p>
                <h2>Add Leave Type</h2>
            </div>
        </div>

        <form class="form" method="post" action="<?= e(url('admin/leave-types')) ?>">
            <?= csrf_field() ?>
            <label>
                <span>Name</span>
                <input type="text" name="name" required>
            </label>
            <label>
                <span>Default Entitlement</span>
                <input type="number" name="default_entitlement" min="0" step="1" value="0">
            </label>
            <label>
                <span>Who can apply</span>
                <select name="gender_eligibility">
                    <?php foreach (leave_gender_options() as $value => $label): ?>
                        <option value="<?= e($value) ?>"><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Attachment After Days</span>
                <input type="number" name="attachment_after_days" min="0" step="1">
            </label>
            <div class="check-grid">
                <label><input type="checkbox" name="requires_balance" checked> Requires balance</label>
                <label><input type="checkbox" name="requires_attachment"> Requires attachment</label>
                <label><input type="checkbox" name="is_paid" checked> Paid leave</label>
                <label><input type="checkbox" name="is_active" checked> Active</label>
            </div>
            <button class="btn btn-primary" type="submit">Save Leave Type</button>
        </form>
    </section>

    <section class="panel">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">Existing</p>
                <h2>Leave Types</h2>
            </div>
        </div>

        <div class="stack">
            <?php foreach ($leaveTypes as $type): ?>
                <form class="mini-editor" method="post" action="<?= e(url('admin/leave-types')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $type['id'] ?>">
                    <input type="text" name="name" value="<?= e($type['name']) ?>" required>
                    <select name="gender_eligibility" aria-label="Who can apply">
                        <?php foreach (leave_gender_options() as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= ($type['gender_eligibility'] ?? 'any') === $value ? 'selected' : '' ?>>
                                <?= e($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="default_entitlement" min="0" step="1" value="<?= e(format_days($type['default_entitlement'], '')) ?>">
                    <input type="number" name="attachment_after_days" min="0" step="1" value="<?= e(format_days($type['attachment_after_days'], '')) ?>" placeholder="Attachment after days">
                    <label><input type="checkbox" name="requires_balance" <?= (int) $type['requires_balance'] === 1 ? 'checked' : '' ?>> Balance</label>
                    <label><input type="checkbox" name="requires_attachment" <?= (int) $type['requires_attachment'] === 1 ? 'checked' : '' ?>> Attachment</label>
                    <label><input type="checkbox" name="is_paid" <?= (int) $type['is_paid'] === 1 ? 'checked' : '' ?>> Paid</label>
                    <label><input type="checkbox" name="is_active" <?= (int) $type['is_active'] === 1 ? 'checked' : '' ?>> Active</label>
                    <button class="btn btn-small btn-primary" type="submit">Update</button>
                </form>
            <?php endforeach; ?>
        </div>
    </section>
</div>
