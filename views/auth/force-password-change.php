<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Change Password | <?= e(app_config('name')) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/styles.css')) ?>">
</head>
<body class="auth-page">
    <main class="auth-shell auth-shell-login">
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

        <section class="auth-card auth-form-card" aria-labelledby="password-setup-title">
            <div class="auth-form-heading">
                <p class="eyebrow">Security check</p>
                <h2 id="password-setup-title">Change Your Password</h2>
                <p><?= e(($user['full_name'] ?? 'Staff') . ', please set a new password before continuing.') ?></p>
            </div>

            <?php if ($message = flash('success')): ?>
                <div class="alert alert-success"><?= e($message) ?></div>
            <?php endif; ?>

            <?php if ($message = flash('error')): ?>
                <div class="alert alert-error"><?= e($message) ?></div>
            <?php endif; ?>

            <form class="form auth-form" method="post" action="<?= e(url('profile/password')) ?>" autocomplete="off">
                <?= csrf_field() ?>
                <input type="hidden" name="redirect_to" value="dashboard">
                <label>
                    <span>Current password</span>
                    <input type="password" name="current_password" placeholder="Enter your temporary password" required autocomplete="current-password" autofocus>
                </label>
                <label>
                    <span>New password</span>
                    <input type="password" name="password" placeholder="Enter new password" required minlength="6" autocomplete="new-password">
                </label>
                <label>
                    <span>Confirm password</span>
                    <input type="password" name="password_confirmation" placeholder="Confirm new password" required minlength="6" autocomplete="new-password">
                </label>
                <button class="btn btn-primary btn-block" type="submit">Update Password</button>
            </form>

            <form class="auth-link-row" method="post" action="<?= e(url('logout')) ?>">
                <?= csrf_field() ?>
                <button type="submit" class="link-button">Logout</button>
            </form>
        </section>
        <footer class="system-footer">
            <span>Copyright <?= e(date('Y')) ?> County Government of Busia. All rights reserved.</span>
        </footer>
    </main>
    <script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
