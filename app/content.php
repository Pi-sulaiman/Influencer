<?php
/**
 * Editable site content: page photos, homepage numbers and testimonials.
 * Managed in Admin > Site content.
 */

/**
 * Photo slots used on the public pages. The default is a stock placeholder
 * until you upload your own photo for that slot.
 */
function site_image_slots()
{
    return [
        'home_brands'    => ['Homepage: "For brands" panel', 'https://picsum.photos/id/856/1000/1080', '1000 x 1080, portrait'],
        'home_creators'  => ['Homepage: "For creators" panel', 'https://picsum.photos/id/64/1000/1080', '1000 x 1080, portrait'],
        'home_campaigns' => ['Homepage: main services tile', 'https://picsum.photos/id/836/1200/1100', '1200 x 1100'],
        'home_talent'    => ['Homepage: talent management tile', 'https://picsum.photos/id/1027/700/600', '700 x 600'],
        'about'          => ['About page', 'https://picsum.photos/id/1005/1100/880', '1100 x 880, landscape'],
        'services'       => ['Services page', 'https://picsum.photos/id/91/1100/880', '1100 x 880, landscape'],
        'brands'         => ['For brands page', 'https://picsum.photos/id/692/1100/880', '1100 x 880, landscape'],
        'creators'       => ['For creators page', 'https://picsum.photos/id/669/1100/880', '1100 x 880, landscape'],
    ];
}

function site_image($slot)
{
    $custom = setting('img_' . $slot);
    if ($custom) {
        return upload_url($custom);
    }
    $slots = site_image_slots();
    return $slots[$slot][1] ?? '';
}

function site_image_is_custom($slot)
{
    return setting('img_' . $slot) !== '';
}

/** Live figures straight from the database. */
function live_stats()
{
    return [
        'creators'  => (int) val("SELECT COUNT(*) FROM influencers WHERE status = 'approved'"),
        'campaigns' => (int) val("SELECT COUNT(*) FROM campaigns WHERE status = 'closed'"),
        'reach'     => (int) val("SELECT SUM(total_followers) FROM influencers WHERE status = 'approved'"),
    ];
}

/**
 * 'custom' shows the numbers typed in Admin > Site content (good while the
 * network is still growing). 'live' counts straight from the database.
 */
function stats_mode()
{
    return setting('stats_mode', 'custom') === 'live' ? 'live' : 'custom';
}

/** Creator total shown in the hero matchmaker and elsewhere. */
function display_creator_total()
{
    if (stats_mode() === 'custom') {
        return (int) setting('stat_creators', '0');
    }
    return live_stats()['creators'];
}

/** Numbers for the homepage and About page stat band. Zeros are hidden. */
function site_stats()
{
    $out = [];
    if (stats_mode() === 'custom') {
        $num = function ($key) { return (float) preg_replace('/[^\d.]/', '', (string) setting($key)); };
        $r = $num('stat_reach');
        $out = [
            ['value' => $num('stat_creators'), 'decimals' => 0, 'suffix' => '+', 'label' => 'vetted creators in our network'],
            ['value' => $num('stat_campaigns'), 'decimals' => 0, 'suffix' => '+', 'label' => 'campaigns delivered for brands'],
            ['value' => $r, 'decimals' => $r == floor($r) ? 0 : 1, 'suffix' => 'M+', 'label' => 'people reached across platforms'],
            ['value' => $num('stat_retention'), 'decimals' => 0, 'suffix' => '%', 'label' => 'of brands book a second campaign'],
        ];
    } else {
        $live = live_stats();
        $n = $live['reach'];
        $out[] = ['value' => $live['creators'], 'decimals' => 0, 'suffix' => '', 'label' => 'vetted creators in our network'];
        $out[] = ['value' => $live['campaigns'], 'decimals' => 0, 'suffix' => '', 'label' => 'campaigns delivered for brands'];
        if ($n >= 1000000) $out[] = ['value' => round($n / 1000000, 1), 'decimals' => 1, 'suffix' => 'M', 'label' => 'combined followers across our creators'];
        elseif ($n >= 1000) $out[] = ['value' => round($n / 1000, 1), 'decimals' => 1, 'suffix' => 'K', 'label' => 'combined followers across our creators'];
        else $out[] = ['value' => $n, 'decimals' => 0, 'suffix' => '', 'label' => 'combined followers across our creators'];
    }
    return array_values(array_filter($out, function ($s) { return $s['value'] > 0; }));
}

/** Visible testimonials, in your chosen order. */
function site_testimonials()
{
    try {
        $rows = all('SELECT * FROM testimonials WHERE is_visible = 1 ORDER BY sort_order ASC, id ASC');
    } catch (Throwable $e) {
        return [];
    }
    return array_map(function ($r) {
        return ['quote' => $r['quote'], 'name' => $r['name'], 'role' => $r['role'], 'img' => $r['photo'] ? upload_url($r['photo']) : ''];
    }, $rows);
}
