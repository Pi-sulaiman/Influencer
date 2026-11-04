<?php /** @var string $error @var string $email */ ?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Sign in | Peakilo Admin</title>
    <link rel="icon" href="<?= e(url('assets/img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="https://api.fontshare.com/v2/css?f[]=satoshi@400,500,700,900&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css">
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body>
<div class="auth">
    <div class="auth-art">
        <a class="sb-logo" href="<?= e(url('')) ?>" style="padding:0"><?= mark_svg('') ?><b>peakilo</b></a>
        <?= mark_svg('chev') ?><?= mark_svg('chev-2') ?>
        <div>
            <h2>Every creator. Every campaign. One place.</h2>
            <p>Review new influencer profiles, run campaign links and publish to the blog.</p>
        </div>
    </div>
    <div class="auth-form">
        <form class="auth-card" method="post" action="<?= e(url('admin/login' . (query('next') ? '?next=' . rawurlencode(query('next')) : ''))) ?>">
            <?= csrf_field() ?>
            <h1>Welcome back</h1>
            <p>Sign in to the Peakilo admin.</p>
            <?php foreach (flashes() as $f): ?>
                <div class="alert alert-<?= $f['type'] === 'success' ? 'success' : 'error' ?>"><i class="ph-fill ph-check-circle"></i><?= e($f['message']) ?></div>
            <?php endforeach; ?>
            <?php if ($error): ?><div class="alert alert-error" role="alert"><i class="ph-fill ph-warning-circle"></i><?= e($error) ?></div><?php endif; ?>
            <div class="field">
                <label for="email">Email</label>
                <input class="input" id="email" type="email" name="email" value="<?= e($email) ?>" autocomplete="username" required autofocus>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <div class="pw-wrap">
                    <input class="input" id="password" type="password" name="password" autocomplete="current-password" required>
                    <button type="button" aria-label="Show password" onclick="var i=document.getElementById('password');i.type=i.type==='password'?'text':'password';this.firstElementChild.className=i.type==='password'?'ph ph-eye':'ph ph-eye-slash'"><i class="ph ph-eye"></i></button>
                </div>
            </div>
            <button class="btn btn-primary btn-lg btn-block mt-24" type="submit">Sign in</button>
            <p class="muted small mt-16" style="text-align:center"><a href="<?= e(url('')) ?>">Back to the website</a></p>
        </form>
    </div>
</div>
</body>
</html>
