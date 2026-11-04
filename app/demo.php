<?php
/**
 * Demo mode. While the creator network is still growing, the website can
 * show sample creators and example homepage numbers so it does not look
 * empty. Turn it off in Admin > Settings > Demo mode and only real data is
 * shown again. Sample creators are flagged with is_demo = 1, use illustrated
 * avatars (never photos of real people) and carry a "Sample" label, so no
 * visitor mistakes them for real creators.
 */

function demo_on()
{
    return setting('demo_mode', '0') === '1';
}

/** Example homepage figures shown only while demo mode is on. */
function demo_stats()
{
    return [
        ['value' => 1200, 'decimals' => 0, 'suffix' => '+', 'label' => 'vetted creators in our network'],
        ['value' => 340, 'decimals' => 0, 'suffix' => '+', 'label' => 'campaigns delivered for brands'],
        ['value' => 85, 'decimals' => 0, 'suffix' => 'M+', 'label' => 'people reached across platforms'],
        ['value' => 92, 'decimals' => 0, 'suffix' => '%', 'label' => 'of brands book a second campaign'],
    ];
}

function demo_creator_count()
{
    try {
        return (int) val('SELECT COUNT(*) FROM influencers WHERE is_demo = 1');
    } catch (Throwable $e) {
        return 0;
    }
}

/** The sample creators. Neutral channel names; mock portraits from a placeholder service. */
function demo_creator_rows()
{
    // creator name, city, country, niches, [platform => [followers, avg views]], ER, fee min, fee max, audience age, audience gender, languages, portrait no.
    return [
        ['GlowDiaries', 'Mumbai', 'India', 'Beauty, Lifestyle', ['instagram' => [182000, 41000], 'youtube' => [54000, 18000]], 4.2, 15000, 45000, '18-24', 'Mostly women', 'English, Hindi', 5],
        ['ByteSizedTech', 'Bengaluru', 'India', 'Tech, Gaming', ['youtube' => [640000, 155000], 'instagram' => [91000, 22000]], 3.6, 40000, 120000, '18-24', 'Mostly men', 'English, Hindi, Kannada', 12],
        ['WanderInStyle', 'Dubai', 'United Arab Emirates', 'Travel, Fashion', ['instagram' => [410000, 96000], 'tiktok' => [220000, 70000]], 4.8, 30000, 95000, '25-34', 'Mostly women', 'English, Arabic, Hindi', 32],
        ['FitFuelDaily', 'Delhi', 'India', 'Fitness, Health', ['instagram' => [96000, 26000], 'youtube' => [31000, 9500]], 5.1, 8000, 25000, '18-24', 'Balanced', 'English, Hindi, Punjabi', 47],
        ['SpiceAndStories', 'Chennai', 'India', 'Food, Lifestyle', ['instagram' => [58000, 17000], 'youtube' => [12000, 4800]], 6.3, 5000, 18000, '25-34', 'Mostly women', 'English, Tamil', 25],
        ['RoadTripReels', 'Kochi', 'India', 'Travel, Automotive', ['youtube' => [1250000, 310000], 'instagram' => [300000, 64000]], 3.1, 90000, 250000, '25-34', 'Mostly men', 'English, Malayalam', 59],
        ['TheShadeEdit', 'London', 'United Kingdom', 'Beauty, Fashion', ['tiktok' => [870000, 240000], 'instagram' => [150000, 30000]], 4.4, 60000, 180000, '18-24', 'Mostly women', 'English, Urdu', 44],
        ['MoneyMadeSimple', 'Ahmedabad', 'India', 'Finance, Business', ['youtube' => [230000, 52000], 'x' => [45000, 12000]], 3.9, 25000, 70000, '25-34', 'Balanced', 'English, Gujarati, Hindi', 15],
        ['HappyHomeNotes', 'Pune', 'India', 'Parenting, Home & Decor', ['instagram' => [38000, 11000], 'facebook' => [22000, 6000]], 5.8, 4000, 15000, '25-34', 'Mostly women', 'English, Hindi, Marathi', 9],
        ['LaughTrackLive', 'Jaipur', 'India', 'Comedy, Music', ['instagram' => [520000, 180000], 'youtube' => [140000, 45000]], 6.1, 45000, 140000, '18-24', 'Balanced', 'Hindi, English', 68],
        ['SketchSchool', 'Kolkata', 'India', 'Art & Design, Education', ['instagram' => [27000, 9000], 'youtube' => [9000, 3500]], 7.2, 3000, 12000, '18-24', 'Balanced', 'English, Bengali', 20],
        ['GameDayGrind', 'Riyadh', 'Saudi Arabia', 'Sports, Fitness', ['tiktok' => [330000, 110000], 'instagram' => [88000, 21000]], 5.4, 20000, 60000, '18-24', 'Mostly men', 'Arabic, English', 53],
    ];
}

/** Mock portrait URL for a sample creator. Loads in the visitor's browser. */
function demo_photo_url($n)
{
    return 'https://i.pravatar.cc/600?img=' . (int) $n;
}

/** Illustration shown if a mock portrait cannot load. */
function demo_fallback_photo($i)
{
    return url('assets/img/demo/creator-' . str_pad((string) (((int) $i % 12) + 1), 2, '0', STR_PAD_LEFT) . '.svg');
}

/** onerror attribute that swaps a broken sample photo for its illustration. */
function photo_fallback_attr(array $c)
{
    if (empty($c['demo'])) {
        return '';
    }
    return ' onerror="this.onerror=null;this.src=\'' . e(demo_fallback_photo($c['id'])) . '\'"';
}

/**
 * 36 more sample creators on top of the 12 above, 48 in all, built from
 * fixed lists so every install gets the same roster. Creators in India use
 * Instagram, YouTube, Facebook and X (TikTok is not available there).
 */
function demo_creator_rows_extra()
{
    $names = ['CityCravings', 'PixelPlayground', 'StyleSutra', 'DesiDiaries', 'TrailTales', 'LensAndLight', 'KitchenChronicles', 'GadgetGuruLive',
        'ZenFitLife', 'CouchComedyClub', 'BeatDropDaily', 'PawsAndPlay', 'DecorDreamsHQ', 'WealthWiseWeekly', 'StudyBuddyHQ', 'StreetStyleSaga',
        'SkinFirstClub', 'MotorMornings', 'PlayThroughPro', 'HealthyHabitsHub', 'TinyTravellers', 'BrewAndBites', 'InkAndCanvas', 'MatchPointDaily',
        'FounderFiles', 'LittleLearners', 'MelodyMakers', 'GlamOnABudget', 'CodeAndCoffee', 'RunWithRhythm', 'SoulfulEats', 'UrbanNomadLife',
        'HomeChefDaily', 'LaughRiotTV', 'ShelfieStories', 'PetPalsIndia'];
    $niches = ['Food, Travel', 'Gaming, Tech', 'Fashion, Lifestyle', 'Lifestyle, Comedy', 'Travel, Lifestyle', 'Art & Design, Travel', 'Food, Health',
        'Tech, Education', 'Fitness, Health', 'Comedy, Lifestyle', 'Music, Lifestyle', 'Pets, Lifestyle', 'Home & Decor, Lifestyle', 'Finance, Education',
        'Education, Tech', 'Fashion, Beauty', 'Beauty, Health', 'Automotive, Tech', 'Gaming, Comedy', 'Health, Fitness', 'Parenting, Travel',
        'Food, Lifestyle', 'Art & Design, Education', 'Sports, Fitness', 'Business, Finance', 'Parenting, Education', 'Music, Comedy', 'Beauty, Fashion',
        'Tech, Business', 'Fitness, Sports', 'Food, Health', 'Travel, Fashion', 'Food, Parenting', 'Comedy, Music', 'Education, Lifestyle', 'Pets, Comedy'];
    $places = [['Mumbai', 'India'], ['Delhi', 'India'], ['Bengaluru', 'India'], ['Hyderabad', 'India'], ['Chennai', 'India'], ['Kolkata', 'India'],
        ['Pune', 'India'], ['Ahmedabad', 'India'], ['Jaipur', 'India'], ['Lucknow', 'India'], ['Chandigarh', 'India'], ['Indore', 'India'],
        ['Goa', 'India'], ['Kochi', 'India'], ['Dubai', 'United Arab Emirates'], ['Singapore', 'Singapore'], ['London', 'United Kingdom'], ['Toronto', 'Canada']];
    $langs = ['India' => 'English, Hindi', 'United Arab Emirates' => 'English, Arabic, Hindi', 'Singapore' => 'English, Mandarin', 'United Kingdom' => 'English', 'Canada' => 'English, Punjabi'];
    $sizes = [14000, 26000, 43000, 68000, 95000, 140000, 230000, 380000, 620000, 1100000];
    $ages = ['18-24', '25-34', '18-24', '25-34', '35-44'];
    $genders = ['Mostly women', 'Balanced', 'Mostly men'];
    $used = array_column(demo_creator_rows(), 11);
    $portraits = array_values(array_diff(range(1, 70), $used));
    $out = [];
    foreach ($names as $i => $name) {
        list($city, $country) = $places[($i * 7) % count($places)];
        $main = $sizes[($i * 3) % count($sizes)];
        $india = $country === 'India';
        $pair = $india ? [['instagram', 'youtube'], ['youtube', 'instagram'], ['instagram', 'facebook'], ['instagram', 'x']][$i % 4]
                       : [['instagram', 'tiktok'], ['tiktok', 'instagram'], ['youtube', 'instagram']][$i % 3];
        $second = (int) round($main * [0.18, 0.32, 0.45, 0.25][$i % 4]);
        $rate = [0.21, 0.28, 0.17, 0.34, 0.24][$i % 5];
        $channels = [
            $pair[0] => [$main, (int) round($main * $rate)],
            $pair[1] => [$second, (int) round($second * $rate * 0.8)],
        ];
        $er = round(2.4 + (($i * 37) % 50) / 10, 1);
        $fee = (int) (round($main * 0.12 / 500) * 500);
        $out[] = [$name, $city, $country, $niches[$i], $channels, $er, max(3000, $fee), max(8000, $fee * 3), $ages[$i % 5], $genders[$i % 3], $langs[$country], $portraits[$i % count($portraits)]];
    }
    return $out;
}

/**
 * Adds any sample creators that are missing (48 in all). Safe to call again:
 * it never duplicates them.
 */
function seed_demo_creators()
{
    $have = array_flip(array_column(all("SELECT email FROM influencers WHERE is_demo = 1"), 'email'));
    $t = now();
    $n = 0;
    foreach (array_merge(demo_creator_rows(), demo_creator_rows_extra()) as $i => $d) {
        list($display, $city, $country, $niches, $channels, $er, $min, $max, $age, $gender, $langs, $portrait) = $d;
        $num = str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT);
        if (isset($have['sample' . $num . '@demo.invalid'])) {
            continue;
        }
        $row = [
            'full_name' => 'Sample creator ' . $num, 'display_name' => $display,
            'email' => 'sample' . $num . '@demo.invalid',
            'city' => $city, 'country' => $country, 'languages' => $langs,
            'niches' => $niches, 'content_types' => 'Reels / Shorts, Stories, Feed posts',
            'engagement_rate' => $er, 'fee_min' => $min, 'fee_max' => $max, 'currency' => 'INR',
            'fee_notes' => 'Sample profile', 'audience_country' => $country, 'audience_age' => $age, 'audience_gender' => $gender,
            'bio' => 'This is a sample profile that shows how creators appear to brands on Peakilo.',
            'photo' => demo_photo_url($portrait),
            'status' => 'approved', 'featured' => $i < 3 ? 1 : 0, 'consent_feature' => 1, 'barter_ok' => $i % 2,
            'source' => 'demo', 'is_demo' => 1,
            'created_at' => date('Y-m-d H:i:s', strtotime($t) - $i * 3600), 'updated_at' => $t,
        ];
        $total = 0;
        $views = 0;
        foreach ($channels as $p => $c) {
            $row[$p] = '@' . strtolower($display);
            $row[$p . '_followers'] = $c[0];
            $row[$p . '_avg_views'] = $c[1];
            $total += $c[0];
            $views += $c[1];
        }
        $row['primary_platform'] = array_keys($channels)[0];
        $row['total_followers'] = $total;
        $row['avg_impressions'] = $views;
        $row['monthly_impressions'] = $views * 12;
        insert('influencers', $row);
        $n++;
    }
    return $n;
}

/** Removes the sample creators and their campaign applications for good. */
function delete_demo_creators()
{
    $ids = array_map('intval', array_column(all('SELECT id FROM influencers WHERE is_demo = 1'), 'id'));
    if (!$ids) {
        return 0;
    }
    q('DELETE FROM applications WHERE influencer_id IN (' . in_placeholders($ids) . ')', $ids);
    q('DELETE FROM influencers WHERE is_demo = 1');
    return count($ids);
}
