<?php
$codeExpiresIn = max(1, (int) ceil(((int) ($expiresAt ?? time()) - time()) / 60));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify Login | <?= e(app_config('name')) ?></title>
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
                <p class="eyebrow">Verification</p>
                <h2>Enter Your Login Code</h2>
                <p>We sent a <?= (int) ($otpDigits ?? 6) ?>-digit code to <?= e($maskedEmail ?? 'your registered email address') ?>.</p>
            </div>

            <?php if ($message = flash('success')): ?>
                <div class="alert alert-success"><?= e($message) ?></div>
            <?php endif; ?>

            <?php if ($message = flash('error')): ?>
                <div class="alert alert-error"><?= e($message) ?></div>
            <?php endif; ?>

            <form class="form auth-form" method="post" action="<?= e(url('login/otp')) ?>" autocomplete="off">
                <?= csrf_field() ?>
                <label>
                    <span>Verification code</span>
                    <input
                        type="text"
                        name="otp_code"
                        inputmode="numeric"
                        pattern="[0-9]{<?= (int) ($otpDigits ?? 6) ?>}"
                        maxlength="<?= (int) ($otpDigits ?? 6) ?>"
                        placeholder="Enter <?= (int) ($otpDigits ?? 6) ?>-digit code"
                        required
                        autofocus
                        autocomplete="one-time-code"
                    >
                    <small>This code expires in about <?= (int) $codeExpiresIn ?> minute(s).</small>
                </label>
                <button class="btn btn-primary btn-block" type="submit">Verify Code</button>
            </form>

            <div class="auth-link-row">
                <form method="post" action="<?= e(url('login/otp/resend')) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="link-button">Resend code</button>
                </form>
                <form method="post" action="<?= e(url('login/otp/cancel')) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="link-button">Use another account</button>
                </form>
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
