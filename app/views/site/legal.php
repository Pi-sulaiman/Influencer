<?php /** @var string $doc */
$site = e(setting('site_name', 'Peakilo'));
$email = e(setting('contact_email', 'hello@peakilo.com'));
$updated = date('F j, Y', filemtime(__FILE__));
?>
<section class="page-hero" style="padding-bottom:24px">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb"><a href="<?= e(url('')) ?>">Home</a><span aria-hidden="true">/</span><span><?= $doc === 'privacy' ? 'Privacy Policy' : 'Terms of Use' ?></span></nav>
        <h1 class="h1"><?= $doc === 'privacy' ? 'Privacy Policy' : 'Terms of Use' ?></h1>
        <p class="lead">Last updated <?= e($updated) ?>. This is a starting template. Have it reviewed by a legal professional for your country before launch.</p>
    </div>
</section>
<section class="section-tight" style="padding-top:0">
    <div class="container legal">
        <div class="prose">
        <?php if ($doc === 'privacy'): ?>
            <h2>Who we are</h2>
            <p><?= $site ?> ("we", "us") is an influencer marketing agency. This policy explains what information we collect through this website and how we use it. Questions can be sent to <a href="mailto:<?= $email ?>"><?= $email ?></a>.</p>
            <h2>Information we collect</h2>
            <ul>
                <li><strong>Creator profiles:</strong> name, email, phone, location, social channels, follower counts, niches, rate range, bio and profile photo that you submit through our creator form.</li>
                <li><strong>Contact enquiries:</strong> the details you send through our contact form.</li>
                <li><strong>Newsletter:</strong> your email address if you subscribe.</li>
                <li><strong>Usage data:</strong> basic, anonymised analytics about how the site is used. We store a one-way hash instead of your IP address for spam protection.</li>
            </ul>
            <h2>How we use it</h2>
            <ul>
                <li>To review creator profiles and match creators with brand campaigns.</li>
                <li>To share a creator's profile with a brand, only for campaigns that creator applied to or agreed to be considered for.</li>
                <li>To reply to enquiries and send the newsletter you asked for.</li>
                <li>To feature a creator on our website, only if they gave permission in the creator form.</li>
            </ul>
            <h2>How long we keep it</h2>
            <p>We keep creator profiles while you are part of our network. You can ask us to update or delete your information at any time by emailing <a href="mailto:<?= $email ?>"><?= $email ?></a>.</p>
            <h2>Your rights</h2>
            <p>Depending on where you live, you may have the right to access, correct, delete or export your data, and to object to certain uses. Contact us and we will respond within 30 days.</p>
            <h2>Cookies</h2>
            <p>We use a session cookie to keep forms secure and to remember which campaign link you arrived from. If analytics is enabled, it may set its own cookies.</p>
        <?php else: ?>
            <h2>Using this website</h2>
            <p>By using the <?= $site ?> website you agree to these terms. If you do not agree, please do not use the site.</p>
            <h2>Creator network</h2>
            <ul>
                <li>Joining the creator network is free and does not guarantee paid work.</li>
                <li>You confirm that the information you submit, including follower counts and rates, is accurate and that you own the social accounts you list.</li>
                <li>Each paid campaign is covered by its own agreement that sets out deliverables, fees, usage rights and payment dates.</li>
            </ul>
            <h2>Brand enquiries</h2>
            <p>Estimates on this website, including the campaign estimator, are for guidance only and are not a quote or a guarantee of results.</p>
            <h2>Content</h2>
            <p>Text, graphics and the <?= $site ?> logo belong to <?= $site ?> unless stated otherwise. Creator content shown on the site belongs to the creator and is used with permission.</p>
            <h2>Data sources</h2>
            <p>The lists of states and cities on our forms contain information from the <a href="https://github.com/dr5hn/countries-states-cities-database" target="_blank" rel="noopener">Countries States Cities Database</a>, which is made available under the <a href="https://opendatacommons.org/licenses/odbl/1-0/" target="_blank" rel="noopener">Open Database License (ODbL)</a>.</p>
            <h2>Liability</h2>
            <p>The website is provided as is. To the extent allowed by law, we are not liable for indirect losses arising from use of the site.</p>
            <h2>Contact</h2>
            <p>Questions about these terms can be sent to <a href="mailto:<?= $email ?>"><?= $email ?></a>.</p>
        <?php endif; ?>
        </div>
    </div>
</section>
