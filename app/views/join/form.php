<?php
/** @var array|null $campaign @var array $data @var array $errors */
$err = function ($k) use ($errors) { return isset($errors[$k]) ? '<p class="field-error" id="err-' . $k . '"><i class="ph-bold ph-warning-circle"></i>' . e($errors[$k]) . '</p>' : ''; };
$has = function ($k) use ($errors) { return isset($errors[$k]) ? ' has-error' : ''; };
$inv = function ($k) use ($errors) { return isset($errors[$k]) ? ' aria-invalid="true" aria-describedby="err-' . $k . '"' : ''; };
$platforms = platform_options();
$campaignPlatforms = $campaign ? csv_list($campaign['platforms']) : [];
$currencies = currencies();
$sym = $currencies[$data['currency']] ?? '$';
?>
<section class="join">
    <div class="container join-grid">
        <div class="join-main">
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
                    </div>
                </header>
            <?php else: ?>
                <header class="join-head" data-reveal>
                    <h1 class="h1">Join the Peakilo creator network.</h1>
                    <p class="lead">Free to join. Add your channels and rate range once, and we will bring you paid campaigns that fit.</p>
                </header>
            <?php endif; ?>

            <?php if ($errors): ?>
                <div class="alert alert-error" role="alert" style="margin-bottom:20px" data-join-errors>
                    <i class="ph-fill ph-warning-circle"></i>
                    <span><?= isset($errors['form']) ? e($errors['form']) : 'Please fix the highlighted fields and submit again.' ?><?= isset($errors['photo']) ? '' : ' If you chose a photo, please select it again.' ?></span>
                </div>
            <?php endif; ?>

            <ol class="jsteps-nav" data-jsteps-nav aria-label="Form progress">
                <li class="is-current"><span>1</span>About you</li>
                <li><span>2</span>Channels</li>
                <li><span>3</span>Content and rates</li>
                <li><span>4</span>Finish</li>
                <span class="jsteps-bar" aria-hidden="true"><span></span></span>
            </ol>

            <form class="join-form" method="post" action="<?= e(url('join')) ?>" enctype="multipart/form-data" novalidate data-join>
                <?= csrf_field() ?>
                <?php if ($campaign): ?><input type="hidden" name="campaign_code" value="<?= e($campaign['code']) ?>"><?php endif; ?>
                <input class="hp-field" type="text" name="company_site" tabindex="-1" autocomplete="off" aria-hidden="true">

                <!-- STEP 1 -->
                <fieldset class="jstep is-current" data-jstep>
                    <legend class="jstep-title">About you</legend>
                    <div class="field<?= $has('photo') ?>">
                        <span class="label">Profile photo</span>
                        <label class="dropzone" data-dropzone>
                            <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" data-photo-input<?= $inv('photo') ?>>
                            <span class="dz-preview" data-photo-preview><i class="ph-bold ph-user"></i></span>
                            <span class="dz-text"><b>Drop a photo here or <u>browse</u></b><small>A clear, recent photo of your face. JPG, PNG or WEBP up to <?= (int) cfg('upload_max_mb', 5) ?> MB.</small></span>
                        </label>
                        <?= $err('photo') ?>
                    </div>
                    <div class="form-grid">
                        <div class="field full<?= $has('full_name') ?>">
                            <label for="j-name">Full name</label>
                            <input class="input" id="j-name" name="full_name" value="<?= e($data['full_name']) ?>" autocomplete="name" required data-preview="name"<?= $inv('full_name') ?>>
                            <?= $err('full_name') ?>
                        </div>
                        <div class="field<?= $has('email') ?>">
                            <label for="j-email">Email</label>
                            <input class="input" id="j-email" type="email" name="email" value="<?= e($data['email']) ?>" autocomplete="email" required<?= $inv('email') ?>>
                            <?= $err('email') ?>
                        </div>
                        <div class="field<?= $has('phone') ?>">
                            <label for="j-phone">Phone or WhatsApp <span class="opt">(optional)</span></label>
                            <input class="input" id="j-phone" name="phone" value="<?= e($data['phone']) ?>" autocomplete="tel" inputmode="tel" placeholder="+91 98765 43210"<?= $inv('phone') ?>>
                            <?= $err('phone') ?>
                        </div>
                        <div class="field">
                            <label for="j-city">City <span class="opt">(optional)</span></label>
                            <input class="input" id="j-city" name="city" value="<?= e($data['city']) ?>" autocomplete="address-level2" data-preview="city">
                        </div>
                        <div class="field<?= $has('country') ?>">
                            <label for="j-country">Country</label>
                            <input class="input" id="j-country" name="country" value="<?= e($data['country']) ?>" list="countries" autocomplete="country-name" required data-preview="country"<?= $inv('country') ?>>
                            <datalist id="countries"><?php foreach (country_options() as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?></datalist>
                            <?= $err('country') ?>
                        </div>
                        <div class="field">
                            <label for="j-gender">Gender <span class="opt">(optional)</span></label>
                            <select class="select" id="j-gender" name="gender">
                                <?php foreach (['' => 'Prefer not to say', 'Female' => 'Female', 'Male' => 'Male', 'Non-binary' => 'Non-binary', 'Other' => 'Other'] as $v => $l): ?>
                                    <option value="<?= e($v) ?>"<?= $data['gender'] === $v ? ' selected' : '' ?>><?= e($l) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="j-lang">Languages you create in <span class="opt">(optional)</span></label>
                            <input class="input" id="j-lang" name="languages" value="<?= e($data['languages']) ?>" placeholder="English, Hindi">
                        </div>
                    </div>
                </fieldset>

                <!-- STEP 2 -->
                <fieldset class="jstep" data-jstep>
                    <legend class="jstep-title">Your channels</legend>
                    <p class="field-help" style="margin-bottom:16px">Turn on the platforms you post on and add your handle and follower count. You can write counts like 45000, 45K or 1.2M.</p>
                    <div class="channels<?= $has('channels') ?>" data-channels>
                        <?php foreach ($platforms as $key => $p):
                            $on = $data[$key] !== '' || in_array($key, $campaignPlatforms, true) && !$errors && !is_post(); ?>
                            <div class="channel<?= $on ? ' is-on' : '' ?>" data-channel="<?= e($key) ?>">
                                <button type="button" class="channel-toggle" aria-expanded="<?= $on ? 'true' : 'false' ?>" data-channel-toggle>
                                    <i class="ph-fill <?= e($p['icon']) ?>" aria-hidden="true"></i><span><?= e($p['label']) ?></span>
                                    <span class="switch-mini" aria-hidden="true"></span>
                                </button>
                                <div class="channel-fields">
                                    <div class="field">
                                        <label for="j-<?= e($key) ?>"><?= e($p['label']) ?> handle or link</label>
                                        <input class="input" id="j-<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($data[$key]) ?>" placeholder="<?= e($p['placeholder']) ?>" autocapitalize="off" data-preview-handle="<?= e($key) ?>">
                                    </div>
                                    <div class="field">
                                        <label for="j-<?= e($key) ?>-f"><?= $key === 'youtube' ? 'Subscribers' : 'Followers' ?></label>
                                        <input class="input" id="j-<?= e($key) ?>-f" name="<?= e($key) ?>_followers" value="<?= e($data[$key . '_followers']) ?>" inputmode="decimal" placeholder="e.g. 45K" data-followers>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?= $err('channels') ?>
                    <div class="form-grid" style="margin-top:22px">
                        <div class="field">
                            <label for="j-primary">Main platform</label>
                            <select class="select" id="j-primary" name="primary_platform">
                                <option value="">Choose your main platform</option>
                                <?php foreach ($platforms as $key => $p): ?><option value="<?= e($key) ?>"<?= $data['primary_platform'] === $key ? ' selected' : '' ?>><?= e($p['label']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="j-er">Average engagement rate <span class="opt">(optional)</span></label>
                            <div class="input-group"><input class="input" id="j-er" name="engagement_rate" value="<?= e($data['engagement_rate']) ?>" inputmode="decimal" placeholder="4.5" style="padding-left:16px;padding-right:40px"><span class="prefix" style="left:auto;right:16px">%</span></div>
                        </div>
                        <div class="field full">
                            <label for="j-web">Website or media kit link <span class="opt">(optional)</span></label>
                            <input class="input" id="j-web" name="website" value="<?= e($data['website']) ?>" type="url" inputmode="url" placeholder="https://">
                        </div>
                    </div>
                </fieldset>

                <!-- STEP 3 -->
                <fieldset class="jstep" data-jstep>
                    <legend class="jstep-title">Content and rates</legend>
                    <div class="field<?= $has('niches') ?>">
                        <span class="label">Your niches <span class="opt">(pick up to 5)</span></span>
                        <div class="chips" data-niches data-max="5">
                            <?php foreach (niche_options() as $n): ?>
                                <label class="chip"><input type="checkbox" name="niches[]" value="<?= e($n) ?>"<?= in_array($n, $data['niches'], true) ? ' checked' : '' ?>><span><?= e($n) ?></span></label>
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

                    <div class="fee-box" data-fee>
                        <div class="fee-head">
                            <div>
                                <span class="label">Your fee range per deliverable</span>
                                <p class="field-help">What you usually charge for one sponsored post or video, from simplest to most involved.</p>
                            </div>
                            <div class="field" style="min-width:120px">
                                <label class="sr-only" for="j-cur">Currency</label>
                                <select class="select" id="j-cur" name="currency" data-currency>
                                    <?php foreach ($currencies as $code => $s): ?><option value="<?= e($code) ?>" data-symbol="<?= e($s) ?>"<?= $data['currency'] === $code ? ' selected' : '' ?>><?= e($code) ?></option><?php endforeach; ?>
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
                                <div class="input-group"><span class="prefix" data-sym><?= e(trim($sym)) ?></span><input class="input" id="j-feemin" name="fee_min" value="<?= e($data['fee_min']) ?>" inputmode="decimal" placeholder="200" data-fee-min<?= $inv('fee_min') ?>></div>
                                <?= $err('fee_min') ?>
                            </div>
                            <div class="field<?= $has('fee_max') ?>">
                                <label for="j-feemax">Maximum fee</label>
                                <div class="input-group"><span class="prefix" data-sym><?= e(trim($sym)) ?></span><input class="input" id="j-feemax" name="fee_max" value="<?= e($data['fee_max']) ?>" inputmode="decimal" placeholder="800" data-fee-max<?= $inv('fee_max') ?>></div>
                                <?= $err('fee_max') ?>
                            </div>
                            <div class="field full">
                                <label for="j-feenotes">What is included <span class="opt">(optional)</span></label>
                                <input class="input" id="j-feenotes" name="fee_notes" value="<?= e($data['fee_notes']) ?>" placeholder="e.g. Reel + 3 stories, 1 revision, 30 days usage">
                            </div>
                        </div>
                    </div>
                </fieldset>

                <!-- STEP 4 -->
                <fieldset class="jstep" data-jstep>
                    <legend class="jstep-title">Almost done</legend>
                    <div class="field">
                        <label for="j-bio">Short bio <span class="opt">(optional)</span></label>
                        <textarea class="textarea" id="j-bio" name="bio" rows="4" maxlength="600" placeholder="Who you are, what you create and who watches it" data-counter data-preview="bio"><?= e($data['bio']) ?></textarea>
                        <p class="field-help"><span data-count-for="j-bio">0</span>/600</p>
                    </div>
                    <?php if ($campaign): ?>
                        <div class="field" style="margin-top:18px">
                            <label for="j-pitch">Why are you a good fit for <?= e($campaign['title']) ?>? <span class="opt">(optional)</span></label>
                            <textarea class="textarea" id="j-pitch" name="pitch" rows="4" maxlength="1500" placeholder="Share a content idea or a past campaign that went well"><?= e($data['pitch']) ?></textarea>
                        </div>
                    <?php endif; ?>
                    <div class="consents">
                        <label class="check<?= $has('consent_terms') ?>"><input type="checkbox" name="consent_terms" value="1"<?= $data['consent_terms'] ? ' checked' : '' ?> required<?= $inv('consent_terms') ?>><span>I confirm these details are accurate and I agree to the <a href="<?= e(url('terms')) ?>" target="_blank">terms</a> and <a href="<?= e(url('privacy')) ?>" target="_blank">privacy policy</a>.</span></label>
                        <?= $err('consent_terms') ?>
                        <label class="check"><input type="checkbox" name="consent_feature" value="1"<?= $data['consent_feature'] ? ' checked' : '' ?>><span>Peakilo may feature my photo, name and channels on its website. <span class="opt">(optional)</span></span></label>
                    </div>
                    <button class="btn btn-primary btn-lg btn-block js-hide-until-last" type="submit" data-submit><?= $campaign ? 'Submit application' : 'Create my creator profile' ?> <i class="ph-bold ph-arrow-right"></i></button>
                </fieldset>

                <div class="jnav" data-jnav hidden>
                    <button class="btn btn-ghost" type="button" data-jprev><i class="ph-bold ph-arrow-left"></i> Back</button>
                    <span class="jnav-count" data-jcount>Step 1 of 4</span>
                    <button class="btn btn-dark" type="button" data-jnext>Continue <i class="ph-bold ph-arrow-right"></i></button>
                </div>
            </form>
        </div>

        <aside class="join-preview" aria-label="Live preview of your creator card">
            <p class="jp-label">Live preview</p>
            <div class="jp-card" data-preview-card>
                <div class="jp-media">
                    <img data-pv-photo alt="" hidden>
                    <span class="jp-initials" data-pv-initials>P</span>
                </div>
                <div class="jp-body">
                    <div class="jp-name"><span data-pv-name>Your name</span> <i class="ph-fill ph-seal-check"></i></div>
                    <div class="jp-loc" data-pv-loc>Your city</div>
                    <div class="jp-niches" data-pv-niches><span class="tag">Your niches</span></div>
                    <div class="jp-stats">
                        <div><b data-pv-followers>0</b><span>total reach</span></div>
                        <div><b data-pv-fee>Not set</b><span>fee range</span></div>
                    </div>
                    <div class="jp-platforms" data-pv-platforms></div>
                </div>
            </div>
            <ul class="jp-tips">
                <li><i class="ph-bold ph-lock-simple"></i>Your details are private. We only share them for campaigns you apply to.</li>
                <li><i class="ph-bold ph-clock"></i>We review new profiles within a few working days.</li>
            </ul>
        </aside>
    </div>
</section>
