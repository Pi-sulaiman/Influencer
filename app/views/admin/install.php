<?php /** @var array $checks @var string $dbError @var array $errors */
$allOk = !in_array(false, $checks, true);
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Install Peakilo</title>
    <link rel="icon" href="<?= e(url('assets/img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="https://api.fontshare.com/v2/css?f[]=satoshi@400,500,700,900&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css">
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body>
<div class="auth">
    <div class="auth-art">
        <span class="sb-logo" style="padding:0"><?= mark_svg('') ?><b>peakilo</b></span>
        <?= mark_svg('chev') ?><?= mark_svg('chev-2') ?>
        <div>
            <h2>Let's set up your agency.</h2>
            <p>This one-time step creates the database tables, starter blog posts and your admin account.</p>
        </div>
    </div>
    <div class="auth-form">
        <form class="auth-card" method="post" action="<?= e(url('install')) ?>">
            <?= csrf_field() ?>
            <h1>Install Peakilo</h1>
            <p>Check the list below, then create your admin login.</p>
            <ul class="checks-list">
                <?php foreach ($checks as $label => $ok): ?>
                    <li class="<?= $ok ? 'ok' : 'bad' ?>"><i class="ph-fill <?= $ok ? 'ph-check-circle' : 'ph-x-circle' ?>"></i><?= e($label) ?></li>
                <?php endforeach; ?>
            </ul>
            <?php if ($dbError): ?>
                <div class="alert alert-error"><i class="ph-fill ph-warning-circle"></i><div>Database connection failed. Check the 'db' block in config.php.<br><small><?= e($dbError) ?></small></div></div>
            <?php endif; ?>
            <?php if (!empty($errors['db'])): ?><div class="alert alert-error"><i class="ph-fill ph-warning-circle"></i><?= e($errors['db']) ?></div><?php endif; ?>
            <div class="field<?= isset($errors['name']) ? ' has-error' : '' ?>">
                <label for="name">Your name</label>
                <input class="input" id="name" name="name" value="<?= e(input('name')) ?>" required>
                <?php if (isset($errors['name'])): ?><span class="error"><?= e($errors['name']) ?></span><?php endif; ?>
            </div>
            <div class="field<?= isset($errors['email']) ? ' has-error' : '' ?>">
                <label for="email">Admin email</label>
                <input class="input" id="email" type="email" name="email" value="<?= e(input('email')) ?>" autocomplete="username" required>
                <?php if (isset($errors['email'])): ?><span class="error"><?= e($errors['email']) ?></span><?php endif; ?>
            </div>
            <div class="field<?= isset($errors['password']) ? ' has-error' : '' ?>">
                <label for="password">Password</label>
                <input class="input" id="password" type="password" name="password" autocomplete="new-password" minlength="8" required>
                <span class="hint">At least 8 characters. You can add more admins later.</span>
                <?php if (isset($errors['password'])): ?><span class="error"><?= e($errors['password']) ?></span><?php endif; ?>
            </div>
            <button class="btn btn-primary btn-lg btn-block mt-24" type="submit"<?= $allOk ? '' : ' disabled' ?>>Install and create account</button>
        </form>
    </div>
</div>
</body>
</html>
