<?php
/** @var array|null $campaign @var array $data @var array $errors @var bool $posted */
$err = function ($k) use ($errors) { return isset($errors[$k]) ? '<p class="field-error" id="err-' . $k . '"><i class="ph-bold ph-warning-circle"></i>' . e($errors[$k]) . '</p>' : ''; };
$has = function ($k) use ($errors) { return isset($errors[$k]) ? ' has-error' : ''; };
$inv = function ($k) use ($errors) { return isset($errors[$k]) ? ' aria-invalid="true" aria-describedby="err-' . $k . '"' : ''; };
$platforms = platform_options();
$campaignPlatforms = $campaign ? csv_list($campaign['platforms']) : [];
$currencies = currencies();
$sym = trim($currencies[$data['currency']] ?? '$');
$stepNames = ['About you', 'Channels', 'Audience', 'Rates', 'Review'];
?>
<section class="join">
    <div class="container join-grid">
        <div class="join-main">
            <div class="join-intro" data-join-intro>
            <?php if ($campaign): ?>
                <header class="join-campaign" data-reveal>
                    <?php if ($campaign['cover_image']): ?><img class="jc-cover" src="<?= e(upload_url($campaign['cover_image'])) ?>" alt="" width="1200" height="400"><?php endif; ?>
                    <div class="jc-body">
                        <span class="jc-brand"><?= mark_svg('') ?> <?= e($campaign['brand_name'] ?: 'Peakilo campaign') ?></span>
                        <h1 class="h2"><?= e($campaign['title']) ?></h1>
                        <?php if ($campaign['description']): ?><p class="lead"><?= nl2br(e($campaign['description'])) ?></p><?php endif; ?>
                        <div class="jc-facts">
                            <?php if ($campaign['budget_min'] || $campaign['budget_max']): ?><span><i class="ph-bold ph-wallet"></i><?= e(fee_range($campaign['budget_min'], $campaign['budget_max'], $campaign['currency'])) ?> per creator</span><?php endif; ?>
                            <?php if ($campaign['deadline']): ?><span><i class="ph-bold ph-calendar-blank"></i>Apply by <?= e(nice_date($campaign['deadline'])) ?></span><?php endif; ?>
                            <?php if ($campaign['location']): ?><span><i class="ph-bold ph-map-pin"></i><?= e($campaign['location']) ?></span><?php endif; ?>
                            <?php foreach ($campaignPlatforms as $p): if (isset($platforms[$p])): ?><span><i class="ph-fill <?= e($platforms[$p]['icon']) ?>"></i><?= e($platforms[$p]['label']) ?></span><?php endif; endforeach; ?>
                        </div>
                        <?php if ($campaign['deliverables']): ?>
                            <details class="jc-deliverables" open>
                                <summary>What the campaign needs</summary>
                                <ul class="check-list"><?php foreach (preg_split('/\r?\n/', $campaign['deliverables']) as $line): if (trim($line) === '') continue; ?><li><i class="ph-fill ph-check-circle"></i><?= e(trim($line, " -•\t")) ?></li><?php endforeach; ?></ul>
                            </details>
                        <?php endif; ?>
                        <?php partial('join_intro_cta'); ?>
                    </div>
                </header>
            <?php else: ?>
                <header class="join-head" data-reveal>
                    <p class="eyebrow"><?= mark_svg('') ?> Creator network</p>
                    <h1 class="h1">Join the Peakilo creator network.</h1>
                    <p class="lead">Free to join. Add your channels, audience and rates once. We bring you paid campaigns that fit.</p>
                    <ul class="join-perks">
                        <li><i class="ph-bold ph-clock"></i>About 5 minutes</li>
                        <li><i class="ph-bold ph-hand-coins"></i>You set your rates</li>
                        <li><i class="ph-bold ph-lock-simple"></i>Private until you apply</li>
                    </ul>
                    <?php partial('join_intro_cta'); ?>
                </header>
            <?php endif; ?>
            </div>

            <?php if ($errors): ?>
                <div class="alert alert-error" role="alert" style="margin-bottom:20px" data-join-errors>
                    <i class="ph-fill ph-warning-circle"></i>
                    <span><?= isset($errors['form']) ? e($errors['form']) : 'Please fix the highlighted fields and submit again.' ?><?= isset($errors['photo']) ? '' : ' If you chose a photo, please select it again.' ?></span>
                </div>
            <?php endif; ?>

            <div class="join-draft" data-draft-note hidden>
                <i class="ph-bold ph-floppy-disk"></i>
                <span>We restored the details you typed last time.</span>
                <button type="button" class="link-btn" data-draft-clear>Start over</button>
            </div>

            <ol class="jsteps-nav" data-jsteps-nav aria-label="Form progress" style="--steps:<?= count($stepNames) ?>">
                <?php foreach ($stepNames as $i => $label): ?>
                    <li<?= $i === 0 ? ' class="is-current"' : '' ?>><span><?= $i + 1 ?></span><em><?= e($label) ?></em></li>
                <?php endforeach; ?>
                <span class="jsteps-bar" aria-hidden="true"><span></span></span>
            </ol>

            <form class="join-form" method="post" action="<?= e(url('join')) ?>" enctype="multipart/form-data" novalidate data-join data-posted="<?= $posted ? '1' : '0' ?>" data-campaign="<?= e($campaign['code'] ?? '') ?>">
                <?= csrf_field() ?>
                <?php if ($campaign): ?><input type="hidden" name="campaign_code" value="<?= e($campaign['code']) ?>"><?php endif; ?>
                <input class="hp-field" type="text" name="company_site" tabindex="-1" autocomplete="off" aria-hidden="true">

                <!-- STEP 1: ABOUT YOU -->
                <fieldset class="jstep is-current" data-jstep>
                    <legend class="jstep-title">About you <small>Who brands will be working with</small></legend>
                    <div class="field<?= $has('photo') ?>">
                        <span class="label">Profile photo</span>
                        <label class="dropzone" data-dropzone>
                            <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" data-photo-input<?= $inv('photo') ?>>
                            <span class="dz-preview" data-photo-preview><i class="ph-bold ph-user"></i></span>
                            <span class="dz-text"><b><span class="dz-desk">Drop a photo here or <u>browse</u></span><span class="dz-phone">Tap to add your photo</span></b><small>A clear, recent photo of your face. JPG, PNG or WEBP up to <?= (int) cfg('upload_max_mb', 5) ?> MB.</small></span>
                            <span class="dz-badge" aria-hidden="true"><i class="ph-bold ph-camera-plus"></i></span>
                        </label>
                        <?= $err('photo') ?>
                    </div>
                    <div class="form-grid">
                        <div class="field<?= $has('full_name') ?>">
                            <label for="j-name">Full name</label>
                            <input class="input" id="j-name" name="full_name" value="<?= e($data['full_name']) ?>" autocomplete="name" required placeholder="As on your ID" data-preview="name"<?= $inv('full_name') ?>>
                            <?= $err('full_name') ?>
                        </div>
                        <div class="field">
                            <label for="j-display">Creator or channel name <span class="opt">(optional)</span></label>
                            <input class="input" id="j-display" name="display_name" value="<?= e($data['display_name']) ?>" placeholder="The name your audience knows" data-preview="display">
                        </div>
                        <div class="field<?= $has('email') ?>">
                            <label for="j-email">Email</label>
                            <input class="input" id="j-email" type="email" name="email" value="<?= e($data['email']) ?>" autocomplete="email" required placeholder="you@email.com"<?= $inv('email') ?>>
                            <?= $err('email') ?>
                        </div>
                        <div class="field<?= $has('phone') ?>">
                            <label for="j-phone">WhatsApp number</label>
                            <div class="input-group wa-group"><span class="prefix"><i class="ph-fill ph-whatsapp-logo" aria-hidden="true"></i></span><input class="input" id="j-phone" name="phone" type="tel" value="<?= e($data['phone']) ?>" autocomplete="tel" inputmode="tel" placeholder="+91 98765 43210" required<?= $inv('phone') ?>></div>
                            <p class="field-help">Include your country code. We send campaign briefs here.</p>
                            <?= $err('phone') ?>
                        </div>
                        <div class="field">
                            <label for="j-city">City <span class="opt">(optional)</span></label>
                            <input class="input" id="j-city" name="city" value="<?= e($data['city']) ?>" autocomplete="address-level2" data-preview="city">
                        </div>
                        <div class="field<?= $has('country') ?>">
                            <label for="j-country">Country</label>
                            <select class="select" id="j-country" name="country" required data-picker data-picker-search data-picker-placeholder="Search countries" data-preview="country" data-country<?= $inv('country') ?>>
                                <option value="" data-placeholder<?= $data['country'] === '' ? ' selected' : '' ?>>Choose your country</option>
                                <?= country_options_html($data['country']) ?>
                            </select>
                            <?= $err('country') ?>
                        </div>
                        <div class="field">
                            <label for="j-gender">Gender <span class="opt">(optional)</span></label>
                            <select class="select" id="j-gender" name="gender" data-picker>
                                <?php foreach (['' => 'Prefer not to say', 'Female' => 'Female', 'Male' => 'Male', 'Non-binary' => 'Non-binary', 'Other' => 'Other'] as $v => $l): ?>
                                    <option value="<?= e($v) ?>"<?= $data['gender'] === $v ? ' selected' : '' ?>><?= e($l) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="j-lang">Languages you create in <span class="opt">(optional)</span></label>
                            <?php $langSel = csv_list($data['languages']); ?>
                            <select class="select" id="j-lang" name="languages[]" multiple data-picker data-picker-search data-picker-placeholder="Search languages" data-placeholder="Choose languages" data-languages>
                                <?php foreach (language_options() as $lg): ?><option value="<?= e($lg) ?>"<?= in_array($lg, $langSel, true) ? ' selected' : '' ?>><?= e($lg) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </fieldset>

                <!-- STEP 2: CHANNELS -->
                <fieldset class="jstep" data-jstep>
                    <legend class="jstep-title">Your channels <small>Links, followers and average views</small></legend>
                    <div class="smart-link js-only">
                        <i class="ph-bold ph-link-simple" aria-hidden="true"></i>
                        <label class="sr-only" for="j-smart">Paste a profile link</label>
                        <input id="j-smart" type="url" inputmode="url" placeholder="Paste any profile link and we will add it for you" autocomplete="off" data-smart-link>
                        <span class="smart-plats" aria-hidden="true"><?php foreach ($platforms as $p): ?><i class="ph-fill <?= e($p['icon']) ?>"></i><?php endforeach; ?></span>
                    </div>
                    <p class="field-help" style="margin-bottom:16px">Turn on each platform you post on. Write counts however you like: <b>45000</b>, <b>45K</b> or <b>1.2M</b>. Average views come from your last 10 posts in your app's insights.</p>
                    <div class="channels<?= $has('channels') ?>" data-channels>
                        <?php foreach ($platforms as $key => $p):
                            $on = $data[$key] !== '' || in_array($key, $campaignPlatforms, true) && !$errors && !$posted; ?>
                            <div class="channel<?= $on ? ' is-on' : '' ?><?= isset($errors[$key]) || isset($errors[$key . '_followers']) ? ' has-error' : '' ?>" data-channel="<?= e($key) ?>" data-domains="<?= e(implode(',', $p['domains'])) ?>">
                                <button type="button" class="channel-toggle" aria-expanded="<?= $on ? 'true' : 'false' ?>" data-channel-toggle>
                                    <span class="ch-ic"><i class="ph-fill <?= e($p['icon']) ?>" aria-hidden="true"></i></span>
                                    <span class="ch-name"><?= e($p['label']) ?><small data-channel-summary></small></span>
                                    <span class="switch-mini" aria-hidden="true"></span>
                                </button>
                                <div class="channel-fields">
                                    <div class="field ch-link<?= $has($key) ?>">
                                        <label for="j-<?= e($key) ?>">Profile link or @handle</label>
                                        <input class="input" id="j-<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($data[$key]) ?>" placeholder="<?= e($p['placeholder']) ?>" autocapitalize="off" autocomplete="off" spellcheck="false" data-preview-handle="<?= e($key) ?>"<?= $inv($key) ?>>
                                        <p class="link-hint" data-link-hint aria-live="polite"></p>
                                        <?= $err($key) ?>
                                    </div>
                                    <div class="field<?= $has($key . '_followers') ?>">
                                        <label for="j-<?= e($key) ?>-f"><?= $key === 'youtube' ? 'Subscribers' : 'Followers' ?></label>
                                        <input class="input" id="j-<?= e($key) ?>-f" name="<?= e($key) ?>_followers" value="<?= e($data[$key . '_followers']) ?>" inputmode="decimal" placeholder="e.g. 45K" data-followers data-count<?= $inv($key . '_followers') ?>>
                                        <p class="count-hint" data-count-hint></p>
                                        <?= $err($key . '_followers') ?>
                                    </div>
                                    <div class="field">
                                        <label for="j-<?= e($key) ?>-v"><?= e($p['views']) ?></label>
                                        <input class="input" id="j-<?= e($key) ?>-v" name="<?= e($key) ?>_avg_views" value="<?= e($data[$key . '_avg_views']) ?>" inputmode="decimal" placeholder="Optional, e.g. 12K" data-views data-count>
                                        <p class="count-hint" data-count-hint></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?= $err('channels') ?>
                    <div class="form-grid" style="margin-top:22px">
                        <div class="field">
                            <label for="j-primary">Main platform</label>
                            <select class="select" id="j-primary" name="primary_platform" data-primary data-picker>
                                <option value="">Choose your main platform</option>
                                <?php foreach ($platforms as $key => $p): ?><option value="<?= e($key) ?>"<?= $data['primary_platform'] === $key ? ' selected' : '' ?>><?= e($p['label']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="j-er">Average engagement rate <span class="opt">(optional)</span></label>
                            <div class="input-group input-suffix"><input class="input" id="j-er" name="engagement_rate" value="<?= e($data['engagement_rate']) ?>" inputmode="decimal" placeholder="4.5" data-er><span class="suffix">%</span></div>
                        </div>
                        <div class="field">
                            <label for="j-mi">Total impressions, last 30 days <span class="opt">(optional)</span></label>
                            <input class="input" id="j-mi" name="monthly_impressions" value="<?= e($data['monthly_impressions']) ?>" inputmode="decimal" placeholder="e.g. 2.5M" data-monthly data-count>
                            <p class="count-hint" data-count-hint></p>
                        </div>
                        <div class="field">
                            <label for="j-web">Website or media kit link <span class="opt">(optional)</span></label>
                            <input class="input" id="j-web" name="website" value="<?= e($data['website']) ?>" type="url" inputmode="url" placeholder="https://">
                        </div>
                    </div>
                    <div class="field<?= $has('insights_image') ?>" style="margin-top:18px">
                        <span class="label">Insights screenshot <span class="opt">(optional, gets you verified faster)</span></span>
                        <label class="file-drop" data-file-drop>
                            <input type="file" name="insights_image" accept="image/jpeg,image/png,image/webp" data-file-input>
                            <span class="fd-ic"><i class="ph-bold ph-chart-bar" aria-hidden="true"></i></span>
                            <span class="fd-txt"><b data-file-name>Upload a screenshot of your insights</b><small>Your app's audience or account insights page, showing followers and reach.</small></span>
                            <span class="fd-btn" aria-hidden="true">Choose</span>
                        </label>
                        <?= $err('insights_image') ?>
                    </div>
                    <div class="reach-bar js-only" data-reach-bar aria-live="polite">
                        <div><span>Total followers</span><b data-total-followers>0</b></div>
                        <div><span>Avg. impressions per post</span><b data-total-views>0</b></div>
                        <div><span>Creator size</span><b data-tier>-</b></div>
                    </div>
                </fieldset>

                <!-- STEP 3: AUDIENCE AND CONTENT -->
                <fieldset class="jstep" data-jstep>
                    <legend class="jstep-title">Audience and content <small>Helps us match you with the right brands</small></legend>
                    <div class="field<?= $has('niches') ?>">
                        <span class="label">Your niches <span class="opt">(pick up to 5)</span></span>
                        <div class="chips" data-niches data-max="5">
                            <?php foreach (niche_catalog() as $n): ?>
                                <label class="chip"><input type="checkbox" name="niches[]" value="<?= e($n['name']) ?>"<?= in_array($n['name'], $data['niches'], true) ? ' checked' : '' ?>><span><i class="ph-bold <?= e($n['icon']) ?>" aria-hidden="true"></i><?= e($n['name']) ?></span></label>
                            <?php endforeach; ?>
                        </div>
                        <?= $err('niches') ?>
                    </div>
                    <div class="field" style="margin-top:26px">
                        <span class="label">Content you make <span class="opt">(optional)</span></span>
                        <div class="chips">
                            <?php foreach (content_type_options() as $t): ?>
                                <label class="chip"><input type="checkbox" name="content_types[]" value="<?= e($t) ?>"<?= in_array($t, $data['content_types'], true) ? ' checked' : '' ?>><span><?= e($t) ?></span></label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="audience-box">
                        <p class="label"><i class="ph-bold ph-users-three" aria-hidden="true"></i> Your audience <span class="opt">(optional, from your insights)</span></p>
                        <div class="form-grid">
                            <div class="field full">
                                <label for="j-acountry">Where most of your audience lives</label>
                                <select class="select" id="j-acountry" name="audience_country" data-picker data-picker-search data-picker-placeholder="Search countries">
                                    <option value="" data-placeholder<?= $data['audience_country'] === '' ? ' selected' : '' ?>>Choose a country</option>
                                    <?= country_options_html($data['audience_country'], true, ['Worldwide' => ['Worldwide, no single country', 'Spread across many countries']]) ?>
                                </select>
                            </div>
                            <div class="field">
                                <span class="label">Main age group</span>
                                <div class="seg" role="radiogroup" aria-label="Main audience age group">
                                    <?php foreach (audience_age_options() as $a): ?>
                                        <label><input type="radio" name="audience_age" value="<?= e($a) ?>"<?= $data['audience_age'] === $a ? ' checked' : '' ?>><span><?= e($a) ?></span></label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="field">
                                <span class="label">Audience gender</span>
                                <div class="seg" role="radiogroup" aria-label="Audience gender split">
                                    <?php foreach (audience_gender_options() as $g): ?>
                                        <label><input type="radio" name="audience_gender" value="<?= e($g) ?>"<?= $data['audience_gender'] === $g ? ' checked' : '' ?>><span><?= e($g) ?></span></label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-grid" style="margin-top:22px">
                        <div class="field full">
                            <label for="j-brands">Brands you have worked with <span class="opt">(optional)</span></label>
                            <input class="input" id="j-brands" name="past_brands" value="<?= e($data['past_brands']) ?>" maxlength="500" placeholder="e.g. Nykaa, boAt, Myntra">
                        </div>
                        <div class="field full">
                            <label for="j-bio">Short bio <span class="opt">(optional)</span></label>
                            <textarea class="textarea" id="j-bio" name="bio" rows="4" maxlength="600" placeholder="Who you are, what you create and who watches it" data-counter data-preview="bio"><?= e($data['bio']) ?></textarea>
                            <p class="field-help"><span data-count-for="j-bio">0</span>/600</p>
                        </div>
                    </div>
                </fieldset>

                <!-- STEP 4: RATES -->
                <fieldset class="jstep" data-jstep>
                    <legend class="jstep-title">Your rates <small>Shared only with the Peakilo team</small></legend>
                    <div class="fee-box" data-fee>
                        <div class="fee-head">
                            <div>
                                <span class="label">Fee range per deliverable</span>
                                <p class="field-help">What you usually charge for one sponsored post or video, from simplest to most involved.</p>
                            </div>
                            <div class="field fee-cur">
                                <label class="sr-only" for="j-cur">Currency</label>
                                <select class="select pick-sm" id="j-cur" name="currency" data-currency data-picker data-picker-search data-picker-placeholder="Search currencies" data-picker-title="Currency">
                                    <?= currency_options_html($data['currency']) ?>
                                </select>
                            </div>
                        </div>
                        <div class="dual-range" data-dual-range aria-hidden="true">
                            <div class="dr-track"><span class="dr-fill"></span></div>
                            <input type="range" min="0" max="100" step="1" value="10" data-dr-min tabindex="-1">
                            <input type="range" min="0" max="100" step="1" value="40" data-dr-max tabindex="-1">
                        </div>
                        <div class="form-grid">
                            <div class="field<?= $has('fee_min') ?>">
                                <label for="j-feemin">Minimum fee</label>
                                <div class="input-group"><span class="prefix" data-sym><?= e($sym) ?></span><input class="input" id="j-feemin" name="fee_min" value="<?= e($data['fee_min']) ?>" inputmode="decimal" placeholder="5,000" data-fee-min<?= $inv('fee_min') ?>></div>
                                <?= $err('fee_min') ?>
                            </div>
                            <div class="field<?= $has('fee_max') ?>">
                                <label for="j-feemax">Maximum fee</label>
                                <div class="input-group"><span class="prefix" data-sym><?= e($sym) ?></span><input class="input" id="j-feemax" name="fee_max" value="<?= e($data['fee_max']) ?>" inputmode="decimal" placeholder="25,000" data-fee-max<?= $inv('fee_max') ?>></div>
                                <?= $err('fee_max') ?>
                            </div>
                        </div>
                    </div>

                    <details class="rate-card"<?= array_filter(array_map(function ($k) use ($data) { return $data['rate_' . $k]; }, array_keys(rate_card_options()))) ? ' open' : '' ?>>
                        <summary><span><i class="ph-bold ph-list-checks" aria-hidden="true"></i><b>Add a rate card</b><small>Optional. Price each format and we fill your range for you.</small></span><i class="ph-bold ph-caret-down rc-caret" aria-hidden="true"></i></summary>
                        <div class="rc-grid">
                            <?php foreach (rate_card_options() as $k => $r): ?>
                                <div class="field rc-row">
                                    <label for="j-rate-<?= e($k) ?>"><i class="ph-bold <?= e($r[1]) ?>" aria-hidden="true"></i><?= e($r[0]) ?></label>
                                    <div class="input-group"><span class="prefix" data-sym><?= e($sym) ?></span><input class="input" id="j-rate-<?= e($k) ?>" name="rate_<?= e($k) ?>" value="<?= e($data['rate_' . $k]) ?>" inputmode="decimal" placeholder="-" data-rate></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </details>

                    <div class="field<?= $has('rate_card_image') ?>" style="margin-top:16px">
                        <span class="label">Rate card image <span class="opt">(optional)</span></span>
                        <label class="file-drop" data-file-drop>
                            <input type="file" name="rate_card_image" accept="image/jpeg,image/png,image/webp" data-file-input>
                            <span class="fd-ic"><i class="ph-bold ph-file-image" aria-hidden="true"></i></span>
                            <span class="fd-txt"><b data-file-name>Upload your rate card</b><small>Already have one? Add the image. JPG, PNG or WEBP up to <?= (int) cfg('upload_max_mb', 5) ?> MB.</small></span>
                            <span class="fd-btn" aria-hidden="true">Choose</span>
                        </label>
                        <?= $err('rate_card_image') ?>
                    </div>

                    <div class="form-grid" style="margin-top:20px">
                        <div class="field full">
                            <label for="j-feenotes">What is included <span class="opt">(optional)</span></label>
                            <input class="input" id="j-feenotes" name="fee_notes" value="<?= e($data['fee_notes']) ?>" placeholder="e.g. Reel + 3 stories, 1 revision, 30 days usage">
                        </div>
                    </div>
                    <label class="toggle-row">
                        <input type="checkbox" name="barter_ok" value="1"<?= $data['barter_ok'] ? ' checked' : '' ?>>
                        <span class="tr-switch" aria-hidden="true"></span>
                        <span><b>Open to product-only collaborations</b><small>Gifted products or experiences instead of a fee, for brands you love.</small></span>
                    </label>
                    <div class="pay-note">
                        <i class="ph-bold ph-shield-check" aria-hidden="true"></i>
                        <p><b>You never pay anything.</b> Peakilo agrees the budget with the brand, sends you the brief and pays you on the date we agree before you start.</p>
                    </div>
                </fieldset>

                <!-- STEP 5: REVIEW -->
                <fieldset class="jstep" data-jstep>
                    <legend class="jstep-title">Review and submit <small>Check everything looks right</small></legend>
                    <div class="review js-only" data-review></div>
                    <?php if ($campaign): ?>
                        <div class="field" style="margin-top:18px">
                            <label for="j-pitch">Why are you a good fit for <?= e($campaign['title']) ?>? <span class="opt">(optional)</span></label>
                            <textarea class="textarea" id="j-pitch" name="pitch" rows="4" maxlength="1500" placeholder="Share a content idea or a past campaign that went well"><?= e($data['pitch']) ?></textarea>
                        </div>
                    <?php endif; ?>
                    <div class="consents">
                        <label class="check<?= $has('consent_terms') ?>"><input type="checkbox" name="consent_terms" value="1"<?= $data['consent_terms'] ? ' checked' : '' ?> required<?= $inv('consent_terms') ?>><span>I confirm these details are accurate and I agree to the <a href="<?= e(url('terms')) ?>" target="_blank">terms</a> and <a href="<?= e(url('privacy')) ?>" target="_blank">privacy policy</a>.</span></label>
                        <?= $err('consent_terms') ?>
                        <label class="check"><input type="checkbox" name="consent_feature" value="1"<?= $data['consent_feature'] ? ' checked' : '' ?>><span>Peakilo may show my photo, creator name and audience stats to brands on its website. Your rates and contact details stay private. <span class="opt">(optional)</span></span></label>
                    </div>
                    <button class="btn btn-primary btn-lg btn-block js-hide-until-last" type="submit" data-submit><?= $campaign ? 'Submit application' : 'Create my creator profile' ?> <i class="ph-bold ph-arrow-right"></i></button>
                </fieldset>

                <div class="jnav" data-jnav hidden>
                    <button class="btn btn-ghost" type="button" data-jprev><i class="ph-bold ph-arrow-left"></i> Back</button>
                    <span class="jnav-count" data-jcount>Step 1 of <?= count($stepNames) ?></span>
                    <button class="btn btn-dark" type="button" data-jnext>Continue <i class="ph-bold ph-arrow-right"></i></button>
                </div>
            </form>
        </div>

        <aside class="join-preview" aria-label="Live preview of your creator card">
            <p class="jp-label"><span class="live-dot" aria-hidden="true"></span>Live preview</p>
            <div class="jp-card" data-preview-card>
                <div class="jp-media">
                    <img data-pv-photo alt="" hidden>
                    <span class="jp-initials" data-pv-initials>P</span>
                    <span class="jp-tier" data-pv-tier hidden></span>
                </div>
                <div class="jp-body">
                    <div class="jp-name"><span data-pv-name>Your name</span> <i class="ph-fill ph-seal-check"></i></div>
                    <div class="jp-loc" data-pv-loc>Your city</div>
                    <div class="jp-niches" data-pv-niches><span class="tag">Your niches</span></div>
                    <div class="jp-stats">
                        <div><b data-pv-followers>0</b><span>followers</span></div>
                        <div><b data-pv-views>-</b><span>avg. views</span></div>
                        <div><b data-pv-er>-</b><span>engagement</span></div>
                    </div>
                    <div class="jp-foot">
                        <div class="jp-platforms" data-pv-platforms></div>
                        <div class="jp-fee"><span>Your rate</span><b data-pv-fee>Not set</b></div>
                    </div>
                </div>
            </div>
            <div class="strength" data-strength>
                <div class="st-head"><span>Profile strength</span><b data-strength-pct>0%</b></div>
                <div class="st-bar"><span data-strength-bar></span></div>
                <p class="st-tip" data-strength-tip>Add a photo to get started.</p>
            </div>
            <ul class="jp-tips">
                <li><i class="ph-bold ph-lock-simple"></i>Your rates and contact details are private. Brands only see them when you apply to a campaign.</li>
                <li><i class="ph-bold ph-clock"></i>We review new profiles within a few working days.</li>
            </ul>
        </aside>
    </div>
</section>
