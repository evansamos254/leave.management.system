<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | <?= e(app_config('name')) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/styles.css')) ?>">
</head>
<body class="auth-page">
    <div class="auth-card">
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
                <p class="eyebrow">Staff Portal</p>
                <h2 id="login-title">Sign In</h2>
                <p>Use your approved staff account to continue.</p>
            </div>

            <?php if ($message = flash('success')): ?>
                <div class="alert alert-success"><?= e($message) ?></div>
            <?php endif; ?>

            <?php if ($message = flash('error')): ?>
                <div class="alert alert-error"><?= e($message) ?></div>
            <?php endif; ?>

            <form class="form auth-form" method="post" action="<?= e(url('login')) ?>" autocomplete="off">
                <?= csrf_field() ?>
                <label>
                    <span>Email address or National ID</span>
                    <input type="text" name="identifier" value="" placeholder="Email address or National ID" required autofocus autocomplete="off" data-clear-on-load>
                </label>
                <label>
                    <span>Password</span>
                    <div class="password-wrap">
                        <input type="password" name="password" value="" placeholder="Enter password" required autocomplete="off" data-clear-on-load>
                        <button type="button" class="password-toggle" aria-label="Show password">
                            <svg class="eye-open" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="eye-closed" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><path d="M1 1l22 22"/></svg>
                        </button>
                    </div>
                </label>
                <input type="hidden" name="use_otp" value="0">
                <label class="auth-toggle">
                    <input type="checkbox" name="use_otp" value="1" checked>
                    <div>
                        <span>Use login verification code</span>
                        <small>Uncheck this to sign in directly without 2FA for this login.</small>
                    </div>
                </label>
                <button class="btn btn-primary btn-block" type="submit">Login</button>
            </form>

            <p class="muted auth-note">
                Login verification is available for all users and can be turned on or off at sign-in.
            </p>

            <div class="auth-link-row">
                <a href="<?= e(url('forgot-password')) ?>">Forgot password?</a>
            </div>

            <div class="auth-footer">
                <span>Need an account?</span>
                <a href="<?= e(url('register')) ?>">Request staff account</a>
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
