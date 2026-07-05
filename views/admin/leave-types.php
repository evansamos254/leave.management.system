<?php
$activeTypes = array_values(array_filter(
    $leaveTypes ?? [],
    fn (array $type): bool => (int) ($type['is_active'] ?? 0) === 1
));

$policyOrder = [
    'Annual Leave',
    'Compassionate Leave',
    'Maternity Leave',
    'Paternity Leave',
    'Sick Leave',
    'Study Leave',
];

usort($activeTypes, static function (array $left, array $right) use ($policyOrder): int {
    $leftIndex = array_search($left['name'] ?? '', $policyOrder, true);
    $rightIndex = array_search($right['name'] ?? '', $policyOrder, true);

    $leftIndex = $leftIndex === false ? PHP_INT_MAX : $leftIndex;
    $rightIndex = $rightIndex === false ? PHP_INT_MAX : $rightIndex;

    if ($leftIndex === $rightIndex) {
        return strcasecmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? ''));
    }

    return $leftIndex <=> $rightIndex;
});

$eligibilityLabel = static function (array $type): string {
    return match ($type['gender_eligibility'] ?? 'any') {
        'male' => 'Male Staff Only',
        'female' => 'Female Staff Only',
        default => 'All Staff',
    };
};

$entitlementLabel = static function (array $type): string {
    $tracked = LeaveType::isBalanceTracked($type);
    $days = (float) ($type['default_entitlement'] ?? 0);

    if (!$tracked && $days <= 0) {
        return 'As per HR Policy';
    }

    return !$tracked
        ? format_days($days, '0') . ' Days (or your policy)'
        : format_days($days, '0') . ' Days';
};

$trackingLabel = static function (array $type): array {
    return LeaveType::isBalanceTracked($type)
        ? ['label' => 'Tracked', 'class' => 'success']
        : ['label' => 'Not Tracked', 'class' => 'warning'];
};

$attachmentLabel = static function (array $type): array {
    if ((int) ($type['requires_attachment'] ?? 0) !== 1) {
        return ['label' => 'Optional', 'class' => 'success', 'note' => null];
    }

    $afterDays = $type['attachment_after_days'] !== null ? (float) $type['attachment_after_days'] : null;

    return [
        'label' => ($afterDays !== null && $afterDays > 0)
            ? 'Required after ' . format_days($afterDays, '0') . ' Days'
            : 'Required',
        'class' => 'warning',
        'note' => null,
    ];
};

$remunerationLabel = static function (array $type): array {
    return (int) ($type['is_paid'] ?? 1) === 1
        ? ['label' => 'With Pay', 'class' => 'success']
        : ['label' => 'Without Pay', 'class' => 'danger'];
};
?>

<section class="leave-policy-board">
    <div class="policy-board-title">
        <h2>Recommended Version</h2>
    </div>

    <?php if (empty($activeTypes)): ?>
        <p class="muted">No active leave types configured yet.</p>
    <?php else: ?>
    <div class="table-wrap">
        <table class="leave-policy-table">
            <thead>
                <tr>
                    <th>Leave Type</th>
                    <th>Eligibility</th>
                    <th>Leave Entitlement</th>
                    <th>Balance Tracking</th>
                    <th>Attachment</th>
                    <th>Remuneration</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($activeTypes as $type): ?>
                    <?php
                    $tracking = $trackingLabel($type);
                    $attachment = $attachmentLabel($type);
                    $remuneration = $remunerationLabel($type);
                    ?>
                    <tr>
                        <td>
                            <strong><?= e($type['name']) ?></strong>
                        </td>
                        <td><?= e($eligibilityLabel($type)) ?></td>
                        <td><?= e($entitlementLabel($type)) ?></td>
                        <td><?= e($tracking['label']) ?></td>
                        <td><?= e($attachment['label']) ?></td>
                        <td><?= e($remuneration['label']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</section>

<details class="policy-maintenance">
    <summary class="policy-maintenance-summary">
        <span class="eyebrow">Policy Maintenance</span>
        <strong>Manage Leave Types</strong>
    </summary>

    <div class="two-column policy-maintenance-grid">
        <section class="panel">
            <div class="panel-heading">
                <div>
                    <p class="eyebrow">Setup</p>
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
                    <p class="eyebrow">Maintenance</p>
                    <h2>Update Existing Leave Types</h2>
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
                                <td><input class="table-input table-input-sm" type="number" name="attachment_after_days" min="0" step="1" value="<?= e(format_days($type['attachment_after_days'], '')) ?>" placeholder="-"></td>
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
    </div>
</details>
