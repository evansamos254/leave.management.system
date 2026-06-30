<section class="panel">
    <?php
    $roleValue = old('role', $account['role']);
    $isHrOffice = $roleValue === 'hr';
    ?>
    <div class="panel-heading">
        <div>
            <p class="eyebrow">Admin</p>
            <h2>Edit Staff Profile</h2>
        </div>
        <a class="btn btn-ghost" href="<?= e(url('admin/users')) ?>">Back to Users</a>
    </div>

    <form class="form grid-form" method="post" action="<?= e(url('admin/users/edit')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $account['id'] ?>">

        <label>
            <span>First name</span>
            <input type="text" name="first_name" value="<?= e(old('first_name', $nameParts['first_name'])) ?>" required>
        </label>
        <label>
            <span>Last name</span>
            <input type="text" name="last_name" value="<?= e(old('last_name', $nameParts['last_name'])) ?>" required>
        </label>
        <label>
            <span>Email address</span>
            <input type="email" name="email" value="<?= e(old('email', $account['email'])) ?>" required>
        </label>
        <label>
            <span>National ID</span>
            <input type="text" name="national_id" value="<?= e(old('national_id', $account['national_id'] ?? '')) ?>">
        </label>
        <label>
            <span>Gender</span>
            <select name="gender" required>
                <option value="">Select gender</option>
                <?php foreach (gender_options() as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= old('gender', $account['gender'] ?? '') === $value ? 'selected' : '' ?>>
                        <?= e($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Phone number</span>
            <input type="text" name="phone" value="<?= e(old('phone', $account['phone'] ?? '')) ?>">
        </label>
        <label>
            <span>Payroll / ID number</span>
            <input type="text" name="staff_id" value="<?= e(old('staff_id', $account['staff_id'] ?? '')) ?>" required>
        </label>
        <label>
            <span>Role</span>
            <select name="role" required data-worker-role-select>
                <?php foreach ($roles as $role): ?>
                    <option value="<?= e($role) ?>" <?= $roleValue === $role ? 'selected' : '' ?>>
                        <?= e(role_label($role)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Status</span>
            <select name="status" required>
                <?php foreach ($statuses as $status): ?>
                    <option value="<?= e($status) ?>" <?= old('status', $account['status']) === $status ? 'selected' : '' ?>>
                        <?= e(role_label($status)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label data-office-scope-field <?= $isHrOffice ? 'hidden' : '' ?>>
            <span>Department</span>
            <select name="directorate_id" required data-directorate-select>
                <option value="">Select department</option>
                <?php foreach ($directorates as $directorate): ?>
                    <option value="<?= (int) $directorate['id'] ?>" <?= (int) old('directorate_id', (string) ($account['directorate_id'] ?? 0)) === (int) $directorate['id'] ? 'selected' : '' ?>>
                        <?= e($directorate['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label data-office-scope-field <?= $isHrOffice ? 'hidden' : '' ?>>
            <span>Directorate</span>
            <select name="department_id" required data-department-select>
                <option value="">Select directorate</option>
                <?php foreach ($departments as $department): ?>
                    <option value="<?= (int) $department['id'] ?>"
                        data-directorate-id="<?= (int) ($department['directorate_id'] ?? 0) ?>"
                        <?= (int) old('department_id', (string) ($account['department_id'] ?? 0)) === (int) $department['id'] ? 'selected' : '' ?>>
                        <?= e($department['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small>Select the directorate under the chosen department.</small>
        </label>
        <label>
            <span>Designation</span>
            <input type="text" name="designation" value="<?= e(old('designation', designation_form_value($account['designation'] ?? null, $account['role'] ?? null))) ?>" required>
        </label>
        <label>
            <span>Employment date</span>
            <input type="date" name="employment_date" value="<?= e(old('employment_date', $account['employment_date'] ?? '')) ?>" max="<?= e(date('Y-m-d')) ?>">
            <small>Select today or a past date.</small>
        </label>
        <label>
            <span>Supervisor</span>
            <select name="supervisor_id">
                <option value="">No assigned supervisor</option>
                <?php foreach ($approvers as $approver): ?>
                    <?php if ((int) $approver['employee_id'] === (int) ($account['employee_id'] ?? 0)) {
                        continue;
                    } ?>
                    <option value="<?= (int) $approver['employee_id'] ?>"
                        <?= (int) old('supervisor_id', (string) ($account['supervisor_id'] ?? 0)) === (int) $approver['employee_id'] ? 'selected' : '' ?>>
                        <?= e($approver['full_name']) ?> (<?= e(role_label($approver['role'])) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <div class="span-2 button-row">
            <button class="btn btn-primary" type="submit">Save Staff Profile</button>
            <a class="btn btn-ghost" href="<?= e(url('admin/users')) ?>">Cancel</a>
        </div>
    </form>
</section>

<?php unset($_SESSION['old']); ?>
