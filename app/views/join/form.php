<?php
/** @var array|null $campaign @var array $data @var array $errors @var bool $posted */
$err = function ($k) use ($errors) { return isset($errors[$k]) ? '<p class="field-error" id="err-' . $k . '"><i class="ph-bold ph-warning-circle"></i>' . e($errors[$k]) . '</p>' : ''; };
$has = function ($k) use ($errors) { return isset($errors[$k]) ? ' has-error' : ''; };
$inv = function ($k) use ($errors) { return isset($errors[$k]) ? ' aria-invalid="true" aria-describedby="err-' . $k . '"' : ''; };
$platforms = enabled_platforms();
$campaignPlatforms = $campaign ? array_values(array_intersect(csv_list($campaign['platforms']), array_keys($platforms))) : [];
$currencies = currencies();
$sym = trim($currencies[$data['currency']] ?? '$');
$stepNames = ['About you', 'Channels', 'Rates'];
// Optional sections start open when they already hold something (or an error).
$openIf = function (array $keys) use ($data, $errors) {
    foreach ($keys as $k) {
        if (isset($errors[$k]) || (isset($data[$k]) && $data[$k] !== '' && $data[$k] !== [])) return ' open';
    }
    return '';
};
$rateOpen = isset($errors['rate_card_image']) || array_filter(array_map(function ($k) use ($data) { return $data['rate_' . $k]; }, array_keys(rate_card_options())));
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
                            <?php foreach ($campaignPlatforms as $p): ?><span><i class="ph-fill <?= e($platforms[$p]['icon']) ?>"></i><?= e($platforms[$p]['label']) ?></span><?php endforeach; ?>
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
                    <p class="lead">Free to join. Add your channels and rates once. We bring you paid campaigns that fit, and you get a Peakilo creator card to share.</p>
                    <ul class="join-perks">
                        <li><i class="ph-bold ph-clock"></i>3 quick steps</li>
                        <li><i class="ph-bold ph-hand-coins"></i>You set your rates</li>
                        <li><i class="ph-bold ph-identification-card"></i>Free creator card at the end</li>
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
                <script type="application/json" data-locations><?= json_encode(location_js_config(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>

                <!-- STEP 1: ABOUT YOU -->
                <fieldset class="jstep is-current" data-jstep>
                    <legend class="jstep-title">About you <small>Who brands will be working with</small></legend>
                    <div class="field<?= $has('photo') ?>">
                        <span class="label">Profile photo</span>
                        <label class="dropzone" data-dropzone>
                            <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" data-photo-input<?= $inv('photo') ?>>
                            <span class="dz-preview" data-photo-preview><i class="ph-bold ph-user"></i></span>
                            <span class="dz-text"><b><span class="dz-desk">Drop a photo here or <u>browse</u></span><span class="dz-phone">Tap to add your photo</span></b><small>A clear, recent photo of your face. It goes on your creator card. JPG, PNG or WEBP up to <?= (int) cfg('upload_max_mb', 5) ?> MB.</small></span>
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
                            <label for="j-display">Creator name <span class="opt">(optional)</span></label>
                            <input class="input" id="j-display" name="display_name" value="<?= e($data['display_name']) ?>" placeholder="The name your audience knows" data-preview="display">
                        </div>
                        <div class="field<?= $has('phone') ?>">
                            <label for="j-phone">WhatsApp number</label>
                            <div class="input-group wa-group"><span class="prefix"><i class="ph-fill ph-whatsapp-logo" aria-hidden="true"></i></span><input class="input" id="j-phone" name="phone" type="tel" value="<?= e($data['phone']) ?>" autocomplete="tel" inputmode="tel" placeholder="+91 98765 43210" required data-phone<?= $inv('phone') ?>></div>
                            <p class="field-help">We send campaign briefs here.</p>
                            <?= $err('phone') ?>
                        </div>
                        <div class="field<?= $has('email') ?>">
                            <label for="j-email">Email</label>
                            <input class="input" id="j-email" type="email" name="email" value="<?= e($data['email']) ?>" autocomplete="email" inputmode="email" required placeholder="you@email.com"<?= $inv('email') ?>>
                            <?= $err('email') ?>
                        </div>
                        <div class="field<?= $has('country') ?>">
                            <label for="j-country">Country</label>
                            <select class="select" id="j-country" name="country" required data-picker data-picker-search data-picker-placeholder="Search countries" data-preview="country" data-country<?= $inv('country') ?>>
                                <option value="" data-placeholder<?= $data['country'] === '' ? ' selected' : '' ?>>Choose your country</option>
                                <?= country_options_html($data['country']) ?>
                            </select>
                            <?= $err('country') ?>
                        </div>
                        <?php $stateList = states_for($data['country'] ?: 'India'); $hasList = (bool) $stateList; ?>
                        <div class="field<?= $has('state') ?>" data-state-field>
                            <label for="<?= $hasList ? 'j-state' : 'j-state-text' ?>" data-state-label>State</label>
                            <div data-state-select-wrap<?= $hasList ? '' : ' hidden' ?>>
                                <select class="select" id="j-state" name="state" required data-state-select data-picker data-picker-search data-picker-placeholder="Search states" data-picker-title="State"<?= $hasList ? '' : ' disabled' ?><?= $inv('state') ?>>
                                    <option value="" data-placeholder<?= $data['state'] === '' ? ' selected' : '' ?>>Choose your state</option>
                                    <?php foreach ($stateList as $st): ?><option value="<?= e($st) ?>"<?= $data['state'] === $st ? ' selected' : '' ?>><?= e($st) ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <input class="input" id="j-state-text" name="state" value="<?= $hasList ? '' : e($data['state']) ?>" placeholder="State or region" autocomplete="address-level1" data-state-text<?= $hasList ? ' hidden disabled' : ' required' ?><?= $inv('state') ?>>
                            <?= $err('state') ?>
                        </div>
                        <?php
                        $cityList = location_cities($data['country'] ?: 'India', $data['state']);
                        $cityKnown = $data['city'] !== '' && in_array($data['city'], $cityList, true);
                        // India before a state is chosen: a disabled list that says so.
                        $cityWait = !$cityList && in_array($data['country'] ?: 'India', location_city_countries(), true) && $data['state'] === '';
                        ?>
                        <div class="field<?= $has('city') ?>" data-city-field>
                            <label for="<?= $cityList || $cityWait ? 'j-city-pick' : 'j-city' ?>" data-city-label>City</label>
                            <div data-city-select-wrap<?= $cityList || $cityWait ? '' : ' hidden' ?>>
                                <select class="select" id="j-city-pick" data-city-select data-picker data-picker-search data-picker-placeholder="Search cities" data-picker-title="City"<?= $cityList ? '' : ' disabled' ?>>
                                    <option value="" data-placeholder<?= !$cityKnown && $data['city'] === '' ? ' selected' : '' ?>><?= $cityWait ? 'Choose your state first' : 'Choose your city' ?></option>
                                    <?php foreach ($cityList as $ct): ?><option value="<?= e($ct) ?>"<?= $data['city'] === $ct ? ' selected' : '' ?>><?= e($ct) ?></option><?php endforeach; ?>
                                    <option value="__other" data-sub="Type it in"<?= !$cityKnown && $data['city'] !== '' ? ' selected' : '' ?>>Other city</option>
                                </select>
                            </div>
                            <input class="input" id="j-city" name="city" value="<?= e($data['city']) ?>" autocomplete="address-level2" placeholder="Your city" required data-city data-preview="city"<?= ($cityList && ($cityKnown || $data['city'] === '')) || $cityWait ? ' hidden' : '' ?><?= $inv('city') ?>>
                            <?= $err('city') ?>
                        </div>
                        <div class="field">
                            <label for="j-lang">Languages <span class="opt">(optional)</span></label>
                            <?php $langSel = csv_list($data['languages']); ?>
                            <select class="select" id="j-lang" name="languages[]" multiple data-picker data-picker-search data-picker-placeholder="Search languages" data-placeholder="Choose languages" data-languages>
                                <?php foreach (language_options() as $lg): ?><option value="<?= e($lg) ?>"<?= in_array($lg, $langSel, true) ? ' selected' : '' ?>><?= e($lg) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field full">
                            <span class="label">Gender <span class="opt">(optional)</span></span>
                            <div class="seg seg-fill" role="radiogroup" aria-label="Gender">
                                <?php foreach (['Female' => 'Female', 'Male' => 'Male', 'Non-binary' => 'Non-binary', '' => 'Skip'] as $v => $l): ?>
                                    <label><input type="radio" name="gender" value="<?= e($v) ?>"<?= $data['gender'] === (string) $v ? ' checked' : '' ?>><span><?= e($l) ?></span></label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </fieldset>

                <!-- STEP 2: CHANNELS AND CONTENT -->
                <fieldset class="jstep" data-jstep>
                    <legend class="jstep-title">Your channels <small>Where you post and what you post about</small></legend>
                    <div class="smart-link js-only">
                        <i class="ph-bold ph-link-simple" aria-hidden="true"></i>
                        <label class="sr-only" for="j-smart">Paste a profile link</label>
                        <input id="j-smart" type="url" inputmode="url" placeholder="Paste a profile link to add it fast" autocomplete="off" data-smart-link>
                        <span class="smart-plats" aria-hidden="true"><?php foreach (array_slice($platforms, 0, 4) as $p): ?><i class="ph-fill <?= e($p['icon']) ?>"></i><?php endforeach; ?></span>
                    </div>
                    <p class="field-help" style="margin-bottom:14px">Add a profile link or handle. We’ll look up available public stats for Instagram, YouTube and TikTok. You can edit the numbers or leave unavailable stats blank.</p>
                    <div class="channels<?= $has('channels') ?>" data-channels>
                        <?php foreach ($platforms as $key => $p):
                            $on = $data[$key] !== '' || in_array($key, $campaignPlatforms, true) && !$errors && !$posted; ?>
                            <div class="channel<?= $on ? ' is-on' : '' ?><?= isset($errors[$key]) || isset($errors[$key . '_followers']) ? ' has-error' : '' ?>" data-channel="<?= e($key) ?>" data-label="<?= e($p['label']) ?>" data-icon="<?= e($p['icon']) ?>" data-domains="<?= e(implode(',', $p['domains'])) ?>">
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
                                        <label for="j-<?= e($key) ?>-f"><?= $key === 'youtube' ? 'Subscribers' : 'Followers' ?> <span class="opt">(optional if unavailable)</span></label>
                                        <input class="input" id="j-<?= e($key) ?>-f" name="<?= e($key) ?>_followers" value="<?= e($data[$key . '_followers']) ?>" inputmode="decimal" placeholder="e.g. 45K" data-followers data-count<?= $inv($key . '_followers') ?>>
                                        <p class="count-hint" data-count-hint></p>
                                        <?= $err($key . '_followers') ?>
                                    </div>
                                    <div class="field">
                                        <label for="j-<?= e($key) ?>-v"><?= e($p['views']) ?> <span class="opt">(optional)</span></label>
                                        <input class="input" id="j-<?= e($key) ?>-v" name="<?= e($key) ?>_avg_views" value="<?= e($data[$key . '_avg_views']) ?>" inputmode="decimal" placeholder="e.g. 12K" data-views data-count>
                                        <p class="count-hint" data-count-hint></p>
                                        <?= $err($key . '_avg_views') ?>
                                    </div>
                                    <div class="social-lookup js-only" data-lookup-url="<?= e(url('api/social-profile')) ?>">
                                        <button type="button" class="link-btn" data-fetch-metrics>Fetch stats</button>
                                        <p class="field-help" data-metrics-status role="status" aria-live="polite"></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?= $err('channels') ?>
                    <div class="reach-bar js-only" data-reach-bar aria-live="polite">
                        <div><span>Total followers</span><b data-total-followers>0</b></div>
                        <div><span>Combined channel averages</span><b data-total-views>0</b></div>
                        <div><span>Creator size</span><b data-tier>-</b></div>
                    </div>

                    <div class="field<?= $has('niches') ?>" style="margin-top:26px">
                        <span class="label">What do you post about? <span class="opt">(pick up to 5)</span></span>
                        <div class="chips" data-niches data-max="5">
                            <?php foreach (niche_catalog() as $n): ?>
                                <label class="chip"><input type="checkbox" name="niches[]" value="<?= e($n['name']) ?>"<?= in_array($n['name'], $data['niches'], true) ? ' checked' : '' ?>><span><i class="ph-bold <?= e($n['icon']) ?>" aria-hidden="true"></i><?= e($n['name']) ?></span></label>
                            <?php endforeach; ?>
                        </div>
                        <?= $err('niches') ?>
                    </div>

                    <details class="more-box"<?= $openIf(['primary_platform', 'engagement_rate', 'monthly_impressions', 'website', 'content_types', 'insights_image']) ?>>
                        <summary><span class="mb-ic"><i class="ph-bold ph-chart-line-up" aria-hidden="true"></i></span><span class="mb-txt"><b>Add more stats</b><small>Engagement, impressions, insights screenshot</small></span><span class="mb-tag">Optional</span><i class="ph-bold ph-plus mb-plus" aria-hidden="true"></i></summary>
                        <div class="more-body">
                            <div class="form-grid">
                                <div class="field">
                                    <label for="j-primary">Main platform</label>
                                    <select class="select" id="j-primary" name="primary_platform" data-primary data-picker>
                                        <option value="">Your biggest one</option>
                                        <?php foreach ($platforms as $key => $p): ?><option value="<?= e($key) ?>"<?= $data['primary_platform'] === $key ? ' selected' : '' ?>><?= e($p['label']) ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="field">
                                    <label for="j-er">Average engagement rate</label>
                                    <div class="input-group input-suffix"><input class="input" id="j-er" name="engagement_rate" value="<?= e($data['engagement_rate']) ?>" inputmode="decimal" placeholder="4.5" data-er><span class="suffix">%</span></div>
                                </div>
                                <div class="field">
                                    <label for="j-mi">Impressions, last 30 days</label>
                                    <input class="input" id="j-mi" name="monthly_impressions" value="<?= e($data['monthly_impressions']) ?>" inputmode="decimal" placeholder="e.g. 2.5M" data-monthly data-count>
                                    <p class="field-help">Copy this from your account insights. Public video views do not measure impressions.</p>
                                    <p class="count-hint" data-count-hint></p>
                                </div>
                                <div class="field">
                                    <label for="j-web">Website or media kit link</label>
                                    <input class="input" id="j-web" name="website" value="<?= e($data['website']) ?>" type="url" inputmode="url" placeholder="https://">
                                </div>
                            </div>
                            <div class="field" style="margin-top:18px">
                                <span class="label">Content you make</span>
                                <div class="chips">
                                    <?php foreach (content_type_options() as $t): ?>
                                        <label class="chip"><input type="checkbox" name="content_types[]" value="<?= e($t) ?>"<?= in_array($t, $data['content_types'], true) ? ' checked' : '' ?>><span><?= e($t) ?></span></label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="field<?= $has('insights_image') ?>" style="margin-top:18px">
                                <span class="label">Insights screenshot <span class="opt">(gets you verified faster)</span></span>
                                <label class="file-drop" data-file-drop>
                                    <input type="file" name="insights_image" accept="image/jpeg,image/png,image/webp" data-file-input>
                                    <span class="fd-ic"><i class="ph-bold ph-chart-bar" aria-hidden="true"></i></span>
                                    <span class="fd-txt"><b data-file-name>Upload a screenshot of your insights</b><small>Your app's account insights page, showing followers and reach.</small></span>
                                    <span class="fd-btn" aria-hidden="true">Choose</span>
                                </label>
                                <?= $err('insights_image') ?>
                            </div>
                        </div>
                    </details>

                    <details class="more-box"<?= $openIf(['audience_country', 'audience_age', 'audience_gender']) ?>>
                        <summary><span class="mb-ic"><i class="ph-bold ph-users-three" aria-hidden="true"></i></span><span class="mb-txt"><b>Add your audience</b><small>Where they live, age and gender</small></span><span class="mb-tag">Optional</span><i class="ph-bold ph-plus mb-plus" aria-hidden="true"></i></summary>
                        <div class="more-body">
                            <div class="form-grid">
                                <div class="field full">
                                    <label for="j-acountry">Where most of your audience lives</label>
                                    <select class="select" id="j-acountry" name="audience_country" data-picker data-picker-search data-picker-placeholder="Search countries">
                                        <option value="" data-placeholder<?= $data['audience_country'] === '' ? ' selected' : '' ?>>Choose a country</option>
                                        <?= country_options_html($data['audience_country'], true, ['Worldwide' => ['Worldwide, no single country', 'Spread across many countries']]) ?>
                                    </select>
                                </div>
                                <div class="field full">
                                    <span class="label">Main age group</span>
                                    <div class="seg seg-fill" role="radiogroup" aria-label="Main audience age group">
                                        <?php foreach (audience_age_options() as $a): ?>
                                            <label><input type="radio" name="audience_age" value="<?= e($a) ?>"<?= $data['audience_age'] === $a ? ' checked' : '' ?>><span><?= e($a) ?></span></label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="field full">
                                    <span class="label">Audience gender</span>
                                    <div class="seg seg-fill" role="radiogroup" aria-label="Audience gender split">
                                        <?php foreach (audience_gender_options() as $g): ?>
                                            <label><input type="radio" name="audience_gender" value="<?= e($g) ?>"<?= $data['audience_gender'] === $g ? ' checked' : '' ?>><span><?= e($g) ?></span></label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </details>
                </fieldset>

                <!-- STEP 3: RATES AND FINISH -->
                <fieldset class="jstep" data-jstep>
                    <legend class="jstep-title">Your rates <small>Private. Only the Peakilo team sees them.</small></legend>
                    <div class="fee-box" data-fee>
                        <div class="fee-head">
                            <div>
                                <span class="label">Fee per sponsored post</span>
                                <p class="field-help">Drag the slider or type it. Lowest for a simple post, highest for your biggest format.</p>
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
                                <label for="j-feemin">From</label>
                                <div class="input-group"><span class="prefix" data-sym><?= e($sym) ?></span><input class="input" id="j-feemin" name="fee_min" value="<?= e($data['fee_min']) ?>" inputmode="decimal" placeholder="5,000" data-fee-min<?= $inv('fee_min') ?>></div>
                                <?= $err('fee_min') ?>
                            </div>
                            <div class="field<?= $has('fee_max') ?>">
                                <label for="j-feemax">Up to</label>
                                <div class="input-group"><span class="prefix" data-sym><?= e($sym) ?></span><input class="input" id="j-feemax" name="fee_max" value="<?= e($data['fee_max']) ?>" inputmode="decimal" placeholder="25,000" data-fee-max<?= $inv('fee_max') ?>></div>
                                <?= $err('fee_max') ?>
                            </div>
                        </div>
                    </div>

                    <div class="field" style="margin-top:16px">
                        <label for="j-feenotes">What is included <span class="opt">(optional)</span></label>
                        <input class="input" id="j-feenotes" name="fee_notes" value="<?= e($data['fee_notes']) ?>" placeholder="e.g. Reel + 3 stories, 1 revision, 30 days usage">
                    </div>

                    <details class="more-box rate-card"<?= $rateOpen ? ' open' : '' ?>>
                        <summary><span class="mb-ic"><i class="ph-bold ph-list-checks" aria-hidden="true"></i></span><span class="mb-txt"><b>Add a rate card</b><small>Price each format, or upload the one you have</small></span><span class="mb-tag">Optional</span><i class="ph-bold ph-plus mb-plus" aria-hidden="true"></i></summary>
                        <div class="more-body">
                            <div class="rc-grid">
                                <?php foreach (rate_card_options() as $k => $r): ?>
                                    <div class="field rc-row">
                                        <label for="j-rate-<?= e($k) ?>"><i class="ph-bold <?= e($r[1]) ?>" aria-hidden="true"></i><?= e($r[0]) ?></label>
                                        <div class="input-group"><span class="prefix" data-sym><?= e($sym) ?></span><input class="input" id="j-rate-<?= e($k) ?>" name="rate_<?= e($k) ?>" value="<?= e($data['rate_' . $k]) ?>" inputmode="decimal" placeholder="-" data-rate></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="field<?= $has('rate_card_image') ?>" style="margin-top:14px">
                                <label class="file-drop" data-file-drop>
                                    <input type="file" name="rate_card_image" accept="image/jpeg,image/png,image/webp" data-file-input aria-label="Rate card image">
                                    <span class="fd-ic"><i class="ph-bold ph-file-image" aria-hidden="true"></i></span>
                                    <span class="fd-txt"><b data-file-name>Upload your rate card image</b><small>JPG, PNG or WEBP up to <?= (int) cfg('upload_max_mb', 5) ?> MB.</small></span>
                                    <span class="fd-btn" aria-hidden="true">Choose</span>
                                </label>
                                <?= $err('rate_card_image') ?>
                            </div>
                        </div>
                    </details>

                    <label class="toggle-row">
                        <input type="checkbox" name="barter_ok" value="1"<?= $data['barter_ok'] ? ' checked' : '' ?>>
                        <span class="tr-switch" aria-hidden="true"></span>
                        <span><b>Open to product-only collaborations</b><small>Gifted products or experiences instead of a fee, for brands you love.</small></span>
                    </label>

                    <details class="more-box"<?= $openIf(['past_brands', 'bio']) ?>>
                        <summary><span class="mb-ic"><i class="ph-bold ph-handshake" aria-hidden="true"></i></span><span class="mb-txt"><b>Brands you worked with and bio</b><small>Helps brands say yes faster</small></span><span class="mb-tag">Optional</span><i class="ph-bold ph-plus mb-plus" aria-hidden="true"></i></summary>
                        <div class="more-body">
                            <div class="form-grid">
                                <div class="field full">
                                    <label for="j-brands">Brands you have worked with</label>
                                    <input class="input" id="j-brands" name="past_brands" value="<?= e($data['past_brands']) ?>" maxlength="500" placeholder="e.g. Nykaa, boAt, Myntra">
                                </div>
                                <div class="field full">
                                    <label for="j-bio">Short bio</label>
                                    <textarea class="textarea" id="j-bio" name="bio" rows="3" maxlength="600" placeholder="Who you are, what you create and who watches it" data-counter data-preview="bio"><?= e($data['bio']) ?></textarea>
                                    <p class="field-help"><span data-count-for="j-bio">0</span>/600</p>
                                </div>
                            </div>
                        </div>
                    </details>

                    <?php if ($campaign): ?>
                        <div class="field" style="margin-top:18px">
                            <label for="j-pitch">Why are you a good fit for <?= e($campaign['title']) ?>? <span class="opt">(optional)</span></label>
                            <textarea class="textarea" id="j-pitch" name="pitch" rows="3" maxlength="1500" placeholder="Share a content idea or a past campaign that went well"><?= e($data['pitch']) ?></textarea>
                        </div>
                    <?php endif; ?>

                    <div class="pay-note">
                        <i class="ph-bold ph-shield-check" aria-hidden="true"></i>
                        <p><b>You never pay anything.</b> Peakilo agrees the budget with the brand, sends you the brief and pays you on the date we agree before you start.</p>
                    </div>
                    <div class="consents">
                        <label class="check<?= $has('consent_terms') ?>"><input type="checkbox" name="consent_terms" value="1"<?= $data['consent_terms'] ? ' checked' : '' ?> required<?= $inv('consent_terms') ?>><span>My details are accurate and I agree to the <a href="<?= e(url('terms')) ?>" target="_blank">terms</a> and <a href="<?= e(url('privacy')) ?>" target="_blank">privacy policy</a>.</span></label>
                        <?= $err('consent_terms') ?>
                        <label class="check"><input type="checkbox" name="consent_feature" value="1"<?= $data['consent_feature'] ? ' checked' : '' ?>><span>Peakilo may show my photo, creator name and audience stats to brands. My rates and contact details stay private. <span class="opt">(optional)</span></span></label>
                    </div>
                    <button class="btn btn-primary btn-lg btn-block js-hide-until-last" type="submit" data-submit><?= $campaign ? 'Submit and get my creator card' : 'Create my profile and card' ?> <i class="ph-bold ph-arrow-right"></i></button>
                </fieldset>

                <div class="jnav" data-jnav hidden>
                    <button class="btn btn-ghost" type="button" data-jprev><i class="ph-bold ph-arrow-left"></i> Back</button>
                    <span class="jnav-count" data-jcount>Step 1 of <?= count($stepNames) ?></span>
                    <button class="btn btn-dark" type="button" data-jnext>Continue <i class="ph-bold ph-arrow-right"></i></button>
                </div>
            </form>
        </div>

        <aside class="join-preview" aria-label="Live preview of your creator card">
            <p class="jp-label"><span class="live-dot" aria-hidden="true"></span>Live preview of your card</p>
            <div class="jp-card" data-preview-card>
                <div class="jp-media">
                    <img data-pv-photo alt="" hidden>
                    <span class="jp-initials" data-pv-initials>P</span>
                    <span class="jp-tier" data-pv-tier hidden></span>
                </div>
                <div class="jp-body">
                    <div class="jp-name"><span data-pv-name>Your name</span></div>
                    <div class="jp-loc" data-pv-loc>Your city</div>
                    <div class="jp-niches" data-pv-niches><span class="tag">Your niches</span></div>
                    <div class="jp-stats">
                        <div><b data-pv-followers>0</b><span>followers</span></div>
                        <div><b data-pv-views>-</b><span>combined averages</span></div>
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
                <li><i class="ph-bold ph-identification-card"></i>After you submit, you get this card as an image to save and share.</li>
                <li><i class="ph-bold ph-lock-simple"></i>Your rates and contact details are private. Brands only see them when you apply to a campaign.</li>
            </ul>
        </aside>
    </div>
</section>
