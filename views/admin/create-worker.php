<section class="panel narrow">
    <div class="panel-heading">
        <div>
            <p class="eyebrow">HR</p>
            <h2>Add Staff</h2>
        </div>
        <a class="btn btn-ghost" href="<?= e(url('workers')) ?>">Staff List</a>
    </div>

    <form class="form grid-form" method="post" action="<?= e(url('workers/create')) ?>">
        <?= csrf_field() ?>
        <label>
            <span>First name</span>
            <input type="text" name="first_name" value="<?= e(old('first_name')) ?>" required>
        </label>
        <label>
            <span>Last name</span>
            <input type="text" name="last_name" value="<?= e(old('last_name')) ?>" required>
        </label>
        <label>
            <span>Email address</span>
            <input type="email" name="email" value="<?= e(old('email')) ?>" required>
        </label>
        <label>
            <span>National ID</span>
            <input type="text" name="national_id" value="<?= e(old('national_id')) ?>">
        </label>
        <label>
            <span>Gender</span>
            <select name="gender" required>
                <option value="">Select gender</option>
                <?php foreach (gender_options() as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= old('gender') === $value ? 'selected' : '' ?>>
                        <?= e($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Phone number</span>
            <input type="tel" name="phone" value="<?= e(old('phone')) ?>" inputmode="tel" autocomplete="tel" placeholder="+254 700 000 000">
            <small>Use a Kenyan mobile number only.</small>
        </label>
        <label>
            <span>Payroll / ID number</span>
            <input type="text" name="staff_id" value="<?= e(old('staff_id')) ?>" placeholder="Enter payroll number or ID number" required>
            <small>If the staff member has no payroll number, enter their ID number here.</small>
        </label>
        <label data-office-scope-field>
            <span>Department</span>
            <select name="directorate_id" required data-directorate-select>
                <option value="">Select department</option>
                <?php foreach ($directorates as $directorate): ?>
                    <option value="<?= (int) $directorate['id'] ?>" <?= (int) old('directorate_id', '0') === (int) $directorate['id'] ? 'selected' : '' ?>>
                        <?= e($directorate['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label data-office-scope-field>
            <span>Directorate</span>
            <select name="department_id" required data-department-select>
                <option value="">Select directorate</option>
                <?php foreach ($departments as $department): ?>
                    <option value="<?= (int) $department['id'] ?>"
                        data-directorate-id="<?= (int) ($department['directorate_id'] ?? 0) ?>"
                        <?= (int) old('department_id', '0') === (int) $department['id'] ? 'selected' : '' ?>>
                        <?= e($department['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small>Select the directorate under the chosen department.</small>
        </label>
        <label>
            <span>Designation</span>
            <input type="text" name="designation" value="<?= e(old('designation')) ?>" required>
        </label>
        <label>
            <span>Job group</span>
            <input
                type="text"
                name="job_group"
                value="<?= e(old('job_group')) ?>"
                placeholder="Select or type job group"
                list="job-group-options"
                autocomplete="off"
                autocapitalize="characters"
                required
            >
            <datalist id="job-group-options">
                <?php foreach (job_group_options() as $value => $label): ?>
                    <option value="<?= e($value) ?>"><?= e($label) ?></option>
                <?php endforeach; ?>
            </datalist>
            <small>Select a standard payroll job group or type the missing one if it is not listed.</small>
        </label>
        <label>
            <span>Role</span>
            <select name="role" required data-worker-role-select>
                <?php foreach ($roles as $role): ?>
                    <option value="<?= e($role) ?>" <?= old('role', 'employee') === $role ? 'selected' : '' ?>>
                        <?= e(role_label($role)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small data-hr-office-note hidden>HR office accounts are created without department or directorate assignment.</small>
        </label>
        <label data-office-scope-field>
            <span>Supervisor / Reporting Officer</span>
            <select name="supervisor_id">
                <option value="">Not assigned</option>
                <?php foreach ($approvers as $approver): ?>
                    <option value="<?= (int) $approver['employee_id'] ?>" <?= (int) old('supervisor_id', '0') === (int) $approver['employee_id'] ? 'selected' : '' ?>>
                        <?= e($approver['full_name']) ?> (<?= e(role_label($approver['role'])) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Employment date</span>
            <input type="date" name="employment_date" value="<?= e(old('employment_date')) ?>" max="<?= e(date('Y-m-d')) ?>">
            <small>Select today or a past date.</small>
        </label>
        <label>
            <span>Temporary password</span>
            <input type="text" name="password" placeholder="Leave blank to generate one">
        </label>
        <button class="btn btn-primary btn-block span-2" type="submit">Create Staff Account</button>
    </form>
</section>
<?php unset($_SESSION['old']); ?>
