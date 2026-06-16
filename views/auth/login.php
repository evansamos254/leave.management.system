<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | <?= e(app_config('name')) ?></title>
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

        <section class="auth-card auth-form-card" aria-labelledby="login-title">
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
                    <input type="password" name="password" value="" placeholder="Enter password" required autocomplete="off" data-clear-on-load>
                </label>
                <button class="btn btn-primary btn-block" type="submit">Login</button>
            </form>

            <div class="auth-link-row">
                <a href="<?= e(url('forgot-password')) ?>">Forgot password?</a>
            </div>

            <div class="auth-footer">
                <span>Need an account?</span>
                <a href="<?= e(url('register')) ?>">Request staff account</a>
            </div>
        </section>
    </main>
    <script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
