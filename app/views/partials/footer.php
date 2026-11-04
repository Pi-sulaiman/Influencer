<?php
$socials = [
    'instagram_url' => ['ph-instagram-logo', 'Instagram'],
    'linkedin_url'  => ['ph-linkedin-logo', 'LinkedIn'],
    'youtube_url'   => ['ph-youtube-logo', 'YouTube'],
    'tiktok_url'    => ['ph-tiktok-logo', 'TikTok'],
    'x_url'         => ['ph-x-logo', 'X'],
];
?>
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="about">
                <a class="logo" href="<?= e(url('')) ?>" aria-label="Peakilo home"><?= mark_svg() ?><span class="logo-word">peakilo</span></a>
                <p>An influencer marketing agency that pairs brands with vetted creators and runs every campaign end to end.</p>
                <div class="socials">
                    <?php foreach ($socials as $key => $s): if ($link = setting($key)): ?>
                        <a href="<?= e($link) ?>" target="_blank" rel="noopener" aria-label="Peakilo on <?= e($s[1]) ?>"><i class="ph <?= e($s[0]) ?>"></i></a>
                    <?php endif; endforeach; ?>
                    <?php if ($email = setting('contact_email')): ?>
                        <a href="mailto:<?= e($email) ?>" aria-label="Email Peakilo"><i class="ph ph-envelope-simple"></i></a>
                    <?php endif; ?>
                </div>
            </div>
            <div>
                <h4>Company</h4>
                <ul>
                    <li><a href="<?= e(url('about')) ?>">About</a></li>
                    <li><a href="<?= e(url('services')) ?>">Services</a></li>
                    <li><a href="<?= e(url('blog')) ?>">Blog</a></li>
                    <li><a href="<?= e(url('contact')) ?>">Contact</a></li>
                </ul>
            </div>
            <div>
                <h4>Brands</h4>
                <ul>
                    <li><a href="<?= e(url('brands')) ?>">How it works</a></li>
                    <li><a href="<?= e(url('influencers')) ?>">Creator niches</a></li>
                    <li><a href="<?= e(url('#estimate')) ?>">Estimate reach</a></li>
                    <li><a href="<?= e(url('contact?type=brand')) ?>">Start a campaign</a></li>
                </ul>
            </div>
            <div>
                <h4>Creators</h4>
                <ul>
                    <li><a href="<?= e(url('creators')) ?>">Why Peakilo</a></li>
                    <li><a href="<?= e(url('join')) ?>">Join as a creator</a></li>
                    <li><a href="<?= e(url('blog/category/creator-growth')) ?>">Creator guides</a></li>
                </ul>
            </div>
            <div class="newsletter">
                <h4>Newsletter</h4>
                <p>One short email a month on creator marketing that works.</p>
                <form action="<?= e(url('subscribe')) ?>" method="post" data-subscribe novalidate>
                    <?= csrf_field() ?>
                    <label class="sr-only" for="nl-email">Email address</label>
                    <input id="nl-email" type="email" name="email" placeholder="you@company.com" autocomplete="email" required>
                    <input class="hp-field" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
                    <button class="btn btn-dark" type="submit">Subscribe</button>
                </form>
                <p class="msg" data-subscribe-msg aria-live="polite"></p>
            </div>
        </div>

        <div class="footer-bottom">
            <span>&copy; <?= date('Y') ?> <?= e(setting('site_name', 'Peakilo')) ?>. All rights reserved.</span>
            <nav aria-label="Legal">
                <a href="<?= e(url('privacy')) ?>">Privacy</a>
                <a href="<?= e(url('terms')) ?>">Terms</a>
                <a href="<?= e(url('sitemap.xml')) ?>">Sitemap</a>
            </nav>
        </div>
    </div>

    <!-- Full-width wordmark: top half solid, bottom half fades and blurs away. -->
    <div class="footer-mark" aria-hidden="true">
        <svg viewBox="0 0 1000 176" preserveAspectRatio="xMidYMax meet" data-footer-mark>
            <path class="fm-mark" transform="translate(4 0) scale(1.95)" d="M0 0H28L62 40L52 52H62L72 40L38 0H66L100 40L78 66H40L24 90H0L34 40Z"/>
            <text class="fm-word" x="214" y="176" textLength="782" lengthAdjust="spacingAndGlyphs">peakilo</text>
        </svg>
        <span class="fm-blur b1"></span><span class="fm-blur b2"></span><span class="fm-blur b3"></span>
    </div>
</footer>
