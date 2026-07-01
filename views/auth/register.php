<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Request Account | <?= e(app_config('name')) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/styles.css')) ?>">
</head>
<body class="auth-page">
    <div class="auth-card auth-card-wide">
        <div class="auth-header">
            <div class="arms-row">
                <img src="<?= e(asset('images/government-arm.png')) ?>" alt="Coat of Arms of the Republic of Kenya" onerror="this.style.display='none'">
                <div class="auth-header-center">
                    <div class="county">County Government of Busia</div>
                    <div class="system">Staff Leave Management System</div>
                </div>
                <img src="<?= e(asset('images/busia-logo.jpg')) ?>" alt="County Government of Busia" onerror="this.style.display='none'">
            </div>
        </div>
        <hr class="tricolor">
        <div class="auth-content">
            <div class="auth-form-heading">
                <p class="eyebrow">Self Registration</p>
                <h2 id="register-title">Request Staff Account</h2>
                <p>Your request will be reviewed by ICT before login access is activated.</p>
            </div>

            <?php if ($message = flash('error')): ?>
                <div class="alert alert-error"><?= e($message) ?></div>
            <?php endif; ?>

            <form class="form grid-form auth-form" method="post" action="<?= e(url('register')) ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <label>
                    <span>First name</span>
                    <input type="text" name="first_name" value="<?= e(old('first_name')) ?>" class="<?= has_field_error('first_name') ? 'is-invalid' : '' ?>" required autofocus>
                    <?php if ($error = field_error('first_name')): ?><small class="field-error"><?= e($error) ?></small><?php endif; ?>
                </label>
                <label>
                    <span>Last name</span>
                    <input type="text" name="last_name" value="<?= e(old('last_name')) ?>" class="<?= has_field_error('last_name') ? 'is-invalid' : '' ?>" required>
                    <?php if ($error = field_error('last_name')): ?><small class="field-error"><?= e($error) ?></small><?php endif; ?>
                </label>
                <label>
                    <span>Email address</span>
                    <input type="email" name="email" value="<?= e(old('email')) ?>" class="<?= has_field_error('email') ? 'is-invalid' : '' ?>" required>
                    <?php if ($error = field_error('email')): ?><small class="field-error"><?= e($error) ?></small><?php endif; ?>
                </label>
                <label>
                    <span>National ID</span>
                    <input type="text" name="national_id" value="<?= e(old('national_id')) ?>" class="<?= has_field_error('national_id') ? 'is-invalid' : '' ?>" required>
                    <?php if ($error = field_error('national_id')): ?><small class="field-error"><?= e($error) ?></small><?php endif; ?>
                </label>
                <label>
                    <span>Gender</span>
                    <select name="gender" class="<?= has_field_error('gender') ? 'is-invalid' : '' ?>" required>
                        <option value="">Select gender</option>
                        <?php foreach (gender_options() as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= old('gender') === $value ? 'selected' : '' ?>>
                                <?= e($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($error = field_error('gender')): ?><small class="field-error"><?= e($error) ?></small><?php endif; ?>
                </label>
                <label>
                    <span>Phone number</span>
                    <input type="tel" name="phone" value="<?= e(old('phone')) ?>" class="<?= has_field_error('phone') ? 'is-invalid' : '' ?>" inputmode="tel" autocomplete="tel" placeholder="+254 700 000 000">
                    <?php if ($error = field_error('phone')): ?><small class="field-error"><?= e($error) ?></small><?php endif; ?>
                    <small>Use a Kenyan mobile number only.</small>
                </label>
                <label>
                    <span>Payroll / ID number</span>
                    <input type="text" name="staff_id" value="<?= e(old('staff_id')) ?>" class="<?= has_field_error('staff_id') ? 'is-invalid' : '' ?>" placeholder="Enter payroll number or ID number" required>
                    <?php if ($error = field_error('staff_id')): ?><small class="field-error"><?= e($error) ?></small><?php endif; ?>
                    <small>If you do not have a payroll number, enter your ID number here.</small>
                </label>
                <label>
                    <span>Department</span>
                    <select name="directorate_id" class="<?= has_field_error('directorate_id') ? 'is-invalid' : '' ?>" required data-directorate-select>
                        <option value="">Select department</option>
                        <?php foreach (($directorates ?? []) as $directorate): ?>
                            <option value="<?= (int) $directorate['id'] ?>" <?= (int) old('directorate_id', '0') === (int) $directorate['id'] ? 'selected' : '' ?>>
                                <?= e($directorate['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($error = field_error('directorate_id')): ?><small class="field-error"><?= e($error) ?></small><?php endif; ?>
                </label>
                <label>
                    <span>Directorate</span>
                    <select name="department_id" class="<?= has_field_error('department_id') ? 'is-invalid' : '' ?>" required data-department-select>
                        <option value="">Select directorate</option>
                        <?php foreach (($departments ?? []) as $department): ?>
                            <option value="<?= (int) $department['id'] ?>"
                                data-directorate-id="<?= (int) ($department['directorate_id'] ?? 0) ?>"
                                <?= (int) old('department_id', '0') === (int) $department['id'] ? 'selected' : '' ?>>
                                <?= e($department['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($error = field_error('department_id')): ?><small class="field-error"><?= e($error) ?></small><?php endif; ?>
                    <small>Select the directorate under the chosen department.</small>
                </label>
                <label>
                    <span>Designation</span>
                    <input type="text" name="designation" value="<?= e(old('designation')) ?>" class="<?= has_field_error('designation') ? 'is-invalid' : '' ?>" required>
                    <?php if ($error = field_error('designation')): ?><small class="field-error"><?= e($error) ?></small><?php endif; ?>
                </label>
                <label>
                    <span>Employment date</span>
                    <input type="date" name="employment_date" value="<?= e(old('employment_date')) ?>" class="<?= has_field_error('employment_date') ? 'is-invalid' : '' ?>" max="<?= e(date('Y-m-d')) ?>">
                    <?php if ($error = field_error('employment_date')): ?><small class="field-error"><?= e($error) ?></small><?php endif; ?>
                    <small>Select today or a past date.</small>
                </label>
                <label class="span-2">
                    <span>Employment supporting document</span>
                    <input type="file" name="employment_document" class="<?= has_field_error('employment_document') ? 'is-invalid' : '' ?>" accept=".pdf,application/pdf" required>
                    <?php if ($error = field_error('employment_document')): ?><small class="field-error"><?= e($error) ?></small><?php endif; ?>
                    <small>Upload a PDF county employment letter from HR, appointment letter, staff ID copy, or other proof of employment.</small>
                </label>
                <label>
                    <span>Password</span>
                    <div class="password-wrap">
                        <input type="password" name="password" class="<?= has_field_error('password') ? 'is-invalid' : '' ?>" required minlength="6">
                        <button type="button" class="password-toggle" aria-label="Show password">
                            <svg class="eye-open" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="eye-closed" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><path d="M1 1l22 22"/></svg>
                        </button>
                    </div>
                    <?php if ($error = field_error('password')): ?><small class="field-error"><?= e($error) ?></small><?php endif; ?>
                </label>
                <label>
                    <span>Confirm password</span>
                    <div class="password-wrap">
                        <input type="password" name="password_confirmation" class="<?= has_field_error('password_confirmation') ? 'is-invalid' : '' ?>" required minlength="6">
                        <button type="button" class="password-toggle" aria-label="Show password">
                            <svg class="eye-open" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="eye-closed" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><path d="M1 1l22 22"/></svg>
                        </button>
                    </div>
                    <?php if ($error = field_error('password_confirmation')): ?><small class="field-error"><?= e($error) ?></small><?php endif; ?>
                </label>
                <button class="btn btn-primary btn-block span-2" type="submit">Submit Account Request</button>
            </form>

            <div class="auth-footer">
                <span>HR, Supervisor, and Director accounts are created by the admin.</span>
            </div>

            <div class="auth-footer">
                <span>Already approved?</span>
                <a href="<?= e(url('login')) ?>">Back to login</a>
            </div>
        </div>
    </div>
    <footer class="system-footer">
        <span>Copyright <?= e(date('Y')) ?> County Government of Busia. All rights reserved.</span>
    </footer>
    <?php unset($_SESSION['old'], $_SESSION['errors']); ?>
    <script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
