<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Request Account | <?= e(app_config('name')) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/styles.css')) ?>">
</head>
<body class="auth-page">
    <main class="auth-shell auth-shell-register">
        <header class="auth-civic-header">
            <div class="auth-logo-card auth-logo-left">
                <img src="<?= e(asset('images/government-arm.png')) ?>" alt="Government coat of arms">
            </div>
            <div class="auth-civic-title">
                <span>County Government of Busia</span>
                <strong>Staff Leave Application</strong>
            </div>
            <div class="auth-logo-card auth-logo-right county-logo-card">
                <img src="<?= e(asset('images/busia-logo.jpg')) ?>" alt="County Government of Busia logo">
            </div>
        </header>

        <section class="auth-card auth-form-card auth-registration-card" aria-labelledby="register-title">
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
                    <input type="text" name="first_name" value="<?= e(old('first_name')) ?>" required autofocus>
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
                    <input type="text" name="national_id" value="<?= e(old('national_id')) ?>" required>
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
                    <input type="text" name="phone" value="<?= e(old('phone')) ?>">
                </label>
                <label>
                    <span>Payroll / ID number</span>
                    <input type="text" name="staff_id" value="<?= e(old('staff_id')) ?>" placeholder="Enter payroll number or ID number" required>
                    <small>If you do not have a payroll number, enter your ID number here.</small>
                </label>
                <label>
                    <span>Department</span>
                    <select name="directorate_id" required data-directorate-select>
                        <option value="">Select department</option>
                        <?php foreach (($directorates ?? []) as $directorate): ?>
                            <option value="<?= (int) $directorate['id'] ?>" <?= (int) old('directorate_id', '0') === (int) $directorate['id'] ? 'selected' : '' ?>>
                                <?= e($directorate['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Directorate</span>
                    <select name="department_id" required data-department-select>
                        <option value="">Select directorate</option>
                        <?php foreach (($departments ?? []) as $department): ?>
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
                    <span>Employment date</span>
                    <input type="date" name="employment_date" value="<?= e(old('employment_date')) ?>">
                </label>
                <label class="span-2">
                    <span>Employment supporting document</span>
                    <input type="file" name="employment_document" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/png" required>
                    <small>Upload a county employment letter from HR, appointment letter, staff ID copy, or other proof of employment.</small>
                </label>
                <label>
                    <span>Password</span>
                    <input type="password" name="password" required minlength="6">
                </label>
                <label>
                    <span>Confirm password</span>
                    <input type="password" name="password_confirmation" required minlength="6">
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
        </section>
    </main>
    <?php unset($_SESSION['old']); ?>
    <script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
