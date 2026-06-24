<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password | <?= e(app_config('name')) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/styles.css')) ?>">
</head>
<body class="auth-page">
    <main class="auth-card">
        <div class="auth-brand">
            <span class="brand-mark">BC</span>
            <div>
                <h1>Reset Password</h1>
                <p>Recover access using your email address or National ID.</p>
            </div>
        </div>

        <?php if ($message = flash('success')): ?>
            <div class="alert alert-success"><?= e($message) ?></div>
        <?php endif; ?>

        <?php if ($message = flash('error')): ?>
            <div class="alert alert-error"><?= e($message) ?></div>
        <?php endif; ?>

        <form class="form" method="post" action="<?= e(url('forgot-password')) ?>" autocomplete="off">
            <?= csrf_field() ?>
            <label>
                <span>Email address or National ID</span>
                <input type="text" name="identifier" value="" placeholder="Email address or National ID" required autofocus autocomplete="off" data-clear-on-load>
            </label>
            <button class="btn btn-primary btn-block" type="submit">Send Temporary Password</button>
        </form>

        <div class="auth-footer">
            <a href="<?= e(url('login')) ?>">Back to login</a>
        </div>
        <footer class="system-footer">
            <span>Copyright <?= e(date('Y')) ?> County Government of Busia. All rights reserved.</span>
        </footer>
    </main>
    <script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
