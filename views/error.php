<section class="panel narrow">
    <h2><?= e($title ?? 'Error') ?></h2>
    <p class="muted"><?= e($message ?? 'An unexpected error occurred.') ?></p>
    <a class="btn btn-primary" href="<?= e(url('dashboard')) ?>">Back to Dashboard</a>
</section>

