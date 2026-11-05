<?php /** @var array $errors @var array $data @var bool $sent @var array $pool @var array $picked */
$err = function ($k) use ($errors) { return isset($errors[$k]) ? '<p class="field-error" id="err-' . $k . '"><i class="ph-bold ph-warning-circle"></i>' . e($errors[$k]) . '</p>' : ''; };
$has = function ($k) use ($errors) { return isset($errors[$k]) ? ' has-error' : ''; };
$inv = function ($k) use ($errors) { return isset($errors[$k]) ? ' aria-invalid="true" aria-describedby="err-' . $k . '"' : ''; };
$catalog = niche_catalog();
$platforms = enabled_platforms();
$isBrand = $data['inquiry_type'] === 'brand';
?>
<section class="page-hero" style="padding-bottom:20px">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb"><a href="<?= e(url('')) ?>">Home</a><span aria-hidden="true">/</span><span>Contact</span></nav>
        <?php if ($picked && !$sent): ?>
            <h1 class="h1" data-split>Request a quote.</h1>
            <p class="lead">Tell us about the campaign for the <?= count($picked) === 1 ? 'creator' : count($picked) . ' creators' ?> you picked. We confirm availability and send pricing within one business day. Nothing to pay online.</p>
        <?php else: ?>
            <h1 class="h1" data-split>Build your campaign brief.</h1>
            <p class="lead">Two minutes, four quick steps. A real person from the Peakilo team replies within one business day with a creator plan.</p>
        <?php endif; ?>
    </div>
</section>

<section class="section-tight" style="padding-top:12px">
    <div class="container brief-grid">
        <div>
            <?php if ($sent): ?>
                <div class="contact-card brief-done" data-reveal="scale">
                    <span class="bd-mark"><?= mark_svg('') ?><i class="ph-bold ph-check"></i></span>
                    <h2 class="h2"><?= query('picked') ? 'Quote request sent.' : (query('brief') ? 'Your brief is in.' : 'Message received.') ?></h2>
                    <p class="lead"><?php if (query('picked')): ?>We are checking availability for the <?= (int) query('picked') === 1 ? 'creator' : (int) query('picked') . ' creators' ?> you picked and will reply within one business day with a quote. There is nothing to pay now.<?php elseif (query('brief')): ?>We are reviewing it now and will reply within one business day with a shortlist approach and a quote.<?php else: ?>Thanks for reaching out. We will reply within one business day.<?php endif; ?></p>
                    <?php if (query('picked')): ?><span data-sl-clear-on-load hidden></span><?php endif; ?>
                    <?php if (query('brief')): ?>
                    <ol class="next-steps">
                        <li><b>Today</b><span>We review your brief and contact the creators.</span></li>
                        <li><b>Within 1 business day</b><span>You get availability, a quote and content ideas.</span></li>
                        <li><b>When you approve</b><span>We sign one contract and manage the campaign end to end.</span></li>
                    </ol>
                    <?php endif; ?>
                    <div class="ctas" style="display:flex;gap:12px;flex-wrap:wrap;margin-top:26px">
                        <a class="btn btn-primary" href="<?= e(url('talent')) ?>">Browse more creators</a>
                        <a class="btn btn-outline" href="<?= e(url('blog')) ?>">Read the blog</a>
                    </div>
                </div>
            <?php else: ?>
            <form class="contact-card brief-form" method="post" action="<?= e(url('contact')) ?>" novalidate data-brief>
                <?= csrf_field() ?>
                <input class="hp-field" type="text" name="company_site" tabindex="-1" autocomplete="off" aria-hidden="true">
                <?php if ($errors): ?><div class="alert alert-error" style="margin-bottom:18px" role="alert" data-brief-errors><i class="ph-fill ph-warning-circle"></i><?= e($errors['form'] ?? 'Please check the highlighted fields.') ?></div><?php endif; ?>

                <div class="type-switch" role="radiogroup" aria-label="I am contacting you as">
                    <?php foreach (['brand' => ['ph-storefront', 'A brand'], 'creator' => ['ph-camera', 'A creator'], 'other' => ['ph-chat-circle', 'Something else']] as $val => $t): ?>
                        <label class="chip"><input type="radio" name="inquiry_type" value="<?= $val ?>"<?= $data['inquiry_type'] === $val ? ' checked' : '' ?> data-type><span><i class="ph-bold <?= $t[0] ?>"></i><?= $t[1] ?></span></label>
                    <?php endforeach; ?>
                </div>

                <?php if ($picked): ?>
                    <div class="picked" data-brand-only data-picked>
                        <div class="picked-head"><b><i class="ph-fill ph-heart"></i> Creators in this request <span data-picked-count><?= count($picked) ?></span></b><a class="link-btn" href="<?= e(url('talent')) ?>">Add more</a></div>
                        <ul>
                            <?php foreach ($picked as $c): ?>
                                <li data-picked-item="<?= e($c['code']) ?>">
                                    <input type="hidden" name="creators[]" value="<?= e($c['code']) ?>">
                                    <img src="<?= e($c['photo']) ?>" alt="" width="44" height="44" loading="lazy"<?= photo_fallback_attr($c) ?>>
                                    <span><b><?= e($c['name']) ?></b><small><?= e($c['niches'][0] ?? 'Creator') ?> &middot; <?= e(short_number($c['reach'])) ?> followers</small></span>
                                    <button type="button" class="icon-btn" data-unpick="<?= e($c['code']) ?>" aria-label="Remove <?= e($c['name']) ?> from this request"><i class="ph-bold ph-x"></i></button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php else: ?>
                    <div class="picked-offer" data-brand-only data-sl-offer hidden>
                        <i class="ph-fill ph-heart"></i><span>You have <b data-sl-offer-count>0</b> shortlisted. Add them to this brief?</span>
                        <a class="btn btn-dark btn-sm" href="<?= e(url('contact?type=brand')) ?>" data-sl-offer-link>Add creators</a>
                    </div>
                <?php endif; ?>

                <div data-brand-only>
                    <ol class="bsteps" aria-label="Brief progress" data-bsteps>
                        <li class="is-current"><span>1</span>Goal</li><li><span>2</span>Audience</li><li><span>3</span>Budget</li><li><span>4</span>Details</li>
                        <span class="bsteps-bar" aria-hidden="true"><span></span></span>
                    </ol>

                    <fieldset class="bstep" data-bstep>
                        <legend class="jstep-title">What should this campaign achieve?</legend>
                        <div class="goal-grid<?= $has('goal') ?>">
                            <?php foreach (brief_goals() as $key => $g): ?>
                                <label class="goal-card"><input type="radio" name="goal" value="<?= e($key) ?>"<?= $data['goal'] === $key ? ' checked' : '' ?> data-sum="goal" data-label="<?= e($g[1]) ?>"><span><i class="ph-bold <?= e($g[0]) ?>"></i><b><?= e($g[1]) ?></b><small><?= e($g[2]) ?></small></span></label>
                            <?php endforeach; ?>
                        </div>
                        <?= $err('goal') ?>
                    </fieldset>

                    <fieldset class="bstep" data-bstep>
                        <legend class="jstep-title">Who do you want to reach?</legend>
                        <div class="field<?= $has('niches') ?>">
                            <span class="label">Niches <span class="opt"><?= $picked ? '(based on the creators you picked, change if you like)' : '(pick up to 6)' ?></span></span>
                            <div class="chips" data-max="6" data-brief-niches>
                                <?php foreach ($catalog as $n): ?>
                                    <label class="chip"><input type="checkbox" name="niches[]" value="<?= e($n['name']) ?>"<?= in_array($n['name'], $data['niches'], true) ? ' checked' : '' ?>><span><i class="ph-bold <?= e($n['icon']) ?>"></i><?= e($n['name']) ?></span></label>
                                <?php endforeach; ?>
                            </div>
                            <?= $err('niches') ?>
                        </div>
                        <div class="field" style="margin-top:24px">
                            <span class="label">Platforms</span>
                            <div class="chips">
                                <?php foreach ($platforms as $k => $p): ?>
                                    <label class="chip"><input type="checkbox" name="platforms[]" value="<?= e($k) ?>"<?= in_array($k, $data['platforms'], true) ? ' checked' : '' ?> data-label="<?= e($p['label']) ?>"><span><i class="ph-fill <?= e($p['icon']) ?>"></i><?= e($p['label']) ?></span></label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="field" style="margin-top:24px">
                            <span class="label">Creator size <span class="opt">(optional)</span></span>
                            <div class="chips">
                                <?php foreach (brief_tiers() as $tier): ?>
                                    <label class="chip"><input type="checkbox" name="tiers[]" value="<?= e($tier) ?>"<?= in_array($tier, $data['tiers'], true) ? ' checked' : '' ?>><span><?= e($tier) ?></span></label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="field" style="margin-top:24px">
                            <label for="b-loc">Target country or city <span class="opt">(optional)</span></label>
                            <input class="input" id="b-loc" name="target_location" value="<?= e($data['target_location']) ?>" placeholder="e.g. India, UAE, London" list="countries">
                            <datalist id="countries"><?php foreach (country_options() as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?></datalist>
                        </div>
                    </fieldset>

                    <fieldset class="bstep" data-bstep>
                        <legend class="jstep-title">Budget and timing</legend>
                        <span class="label" style="font-weight:700;font-size:14.5px;display:block;margin-bottom:10px">Total campaign budget</span>
                        <div class="budget-grid">
                            <?php foreach (brief_budgets() as $b): ?>
                                <label class="goal-card goal-card-sm"><input type="radio" name="budget" value="<?= e($b) ?>"<?= $data['budget'] === $b ? ' checked' : '' ?>><span><b><?= e($b) ?></b></span></label>
                            <?php endforeach; ?>
                        </div>
                        <span class="label" style="font-weight:700;font-size:14.5px;display:block;margin:24px 0 10px">When do you want to launch?</span>
                        <div class="chips">
                            <?php foreach (brief_timelines() as $tl): ?>
                                <label class="chip"><input type="radio" name="timeline" value="<?= e($tl) ?>"<?= $data['timeline'] === $tl ? ' checked' : '' ?>><span><?= e($tl) ?></span></label>
                            <?php endforeach; ?>
                        </div>
                        <p class="field-help" style="margin-top:18px"><i class="ph ph-calculator"></i> Not sure about budget? Try the <a class="accent" href="<?= e(url('#estimate')) ?>">campaign estimator</a>.</p>
                    </fieldset>
                </div>

                <fieldset class="bstep" data-bstep data-details>
                    <legend class="jstep-title" data-details-title><?= $isBrand ? 'Where should we send your plan?' : 'How can we help?' ?></legend>
                    <div class="form-grid">
                        <div class="field<?= $has('name') ?>">
                            <label for="c-name">Your name</label>
                            <input class="input" id="c-name" name="name" value="<?= e($data['name']) ?>" autocomplete="name" required<?= $inv('name') ?>>
                            <?= $err('name') ?>
                        </div>
                        <div class="field<?= $has('email') ?>">
                            <label for="c-email">Work email</label>
                            <input class="input" id="c-email" type="email" name="email" value="<?= e($data['email']) ?>" autocomplete="email" required<?= $inv('email') ?>>
                            <?= $err('email') ?>
                        </div>
                        <div class="field">
                            <label for="c-company">Company or channel <span class="opt">(optional)</span></label>
                            <input class="input" id="c-company" name="company" value="<?= e($data['company']) ?>" autocomplete="organization">
                        </div>
                        <div class="field">
                            <label for="c-phone">Phone or WhatsApp <span class="opt">(optional)</span></label>
                            <input class="input" id="c-phone" name="phone" value="<?= e($data['phone']) ?>" autocomplete="tel" inputmode="tel">
                        </div>
                        <div class="field full<?= $has('website') ?>" data-brand-only>
                            <label for="c-web">Website or product link <span class="opt">(optional)</span></label>
                            <input class="input" id="c-web" name="website" value="<?= e($data['website']) ?>" inputmode="url" placeholder="yourbrand.com"<?= $inv('website') ?>>
                            <?= $err('website') ?>
                        </div>
                        <div class="field full<?= $has('message') ?>">
                            <label for="c-message" data-msg-label><?= $isBrand ? 'Anything else we should know?' : 'Your message' ?> <span class="opt" data-brand-only>(optional)</span></label>
                            <textarea class="textarea" id="c-message" name="message" rows="5" placeholder="Product, key dates, creators you like, past campaigns"<?= $inv('message') ?>><?= e($data['message']) ?></textarea>
                            <?= $err('message') ?>
                        </div>
                    </div>
                    <button class="btn btn-primary btn-lg btn-block" style="margin-top:22px" type="submit" data-brief-submit><span data-submit-label><?= $isBrand ? 'Send my brief' : 'Send message' ?></span> <i class="ph-bold ph-paper-plane-tilt"></i></button>
                    <p class="field-help" style="margin-top:12px;text-align:center"><span data-brand-only><i class="ph-bold ph-shield-check"></i> No payment now. We reply with availability and a quote. </span>By sending this you agree to our <a class="accent" href="<?= e(url('privacy')) ?>">privacy policy</a>.</p>
                </fieldset>

                <div class="jnav" data-bnav hidden>
                    <button class="btn btn-ghost" type="button" data-bprev><i class="ph-bold ph-arrow-left"></i> Back</button>
                    <span class="jnav-count" data-bcount>Step 1 of 4</span>
                    <button class="btn btn-dark" type="button" data-bnext>Continue <i class="ph-bold ph-arrow-right"></i></button>
                </div>
            </form>
            <?php endif; ?>
        </div>

        <aside class="brief-side">
            <div class="brief-summary" data-brand-only data-summary>
                <p class="jp-label">Your brief</p>
                <dl>
                    <?php if ($picked): ?><div><dt>Creators</dt><dd data-s-creators><?= count($picked) ?> picked</dd></div><?php endif; ?>
                    <div><dt>Goal</dt><dd data-s-goal>Not chosen yet</dd></div>
                    <div><dt>Niches</dt><dd data-s-niches>Not chosen yet</dd></div>
                    <div><dt>Platforms</dt><dd data-s-platforms>Any</dd></div>
                    <div><dt>Budget</dt><dd data-s-budget>Not set</dd></div>
                    <div><dt>Launch</dt><dd data-s-timeline>Not set</dd></div>
                </dl>
                <div class="bs-match"<?= $liveCounts ? '' : ' hidden' ?>>
                    <span class="bs-num" data-s-count>0</span>
                    <span>approved creators in our network match this brief</span>
                </div>
                <script type="application/json" data-pool><?= json_encode($pool, JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
            </div>
            <div class="contact-points">
                <?php if ($email = setting('contact_email')): ?>
                    <a href="mailto:<?= e($email) ?>"><span class="ic"><i class="ph-bold ph-envelope-simple"></i></span><div><b><?= e($email) ?></b><span>Email us any time</span></div></a>
                <?php endif; ?>
                <?php if ($wa = setting('whatsapp')): ?>
                    <a href="https://wa.me/<?= e(preg_replace('/\D/', '', $wa)) ?>" target="_blank" rel="noopener"><span class="ic"><i class="ph-bold ph-whatsapp-logo"></i></span><div><b><?= e($wa) ?></b><span>Chat on WhatsApp</span></div></a>
                <?php endif; ?>
                <?php if ($phone = setting('contact_phone')): ?>
                    <a href="tel:<?= e(preg_replace('/[^\d+]/', '', $phone)) ?>"><span class="ic"><i class="ph-bold ph-phone"></i></span><div><b><?= e($phone) ?></b><span>Mon to Fri, business hours</span></div></a>
                <?php endif; ?>
                <a href="<?= e(url('join')) ?>"><span class="ic"><i class="ph-bold ph-camera"></i></span><div><b>Are you a creator?</b><span>Join the network with the creator form</span></div></a>
            </div>
        </aside>
    </div>
</section>
