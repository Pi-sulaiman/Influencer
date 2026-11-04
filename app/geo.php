<?php
/**
 * Countries, currencies and exchange rates.
 * Rates are "units per 1 USD" and can be edited in Admin > Settings.
 * India (INR) is the default; visitors elsewhere get their own currency
 * automatically when "Auto-detect currency" is on.
 */

function currency_catalog()
{
    return [
        'INR' => ['Indian Rupee', '₹'], 'USD' => ['US Dollar', '$'], 'EUR' => ['Euro', '€'], 'GBP' => ['British Pound', '£'],
        'AED' => ['UAE Dirham', 'AED '], 'SAR' => ['Saudi Riyal', 'SAR '], 'QAR' => ['Qatari Riyal', 'QAR '], 'KWD' => ['Kuwaiti Dinar', 'KWD '],
        'OMR' => ['Omani Rial', 'OMR '], 'BHD' => ['Bahraini Dinar', 'BHD '], 'AUD' => ['Australian Dollar', 'A$'], 'NZD' => ['New Zealand Dollar', 'NZ$'],
        'CAD' => ['Canadian Dollar', 'C$'], 'SGD' => ['Singapore Dollar', 'S$'], 'MYR' => ['Malaysian Ringgit', 'RM '], 'IDR' => ['Indonesian Rupiah', 'Rp '],
        'PHP' => ['Philippine Peso', '₱'], 'THB' => ['Thai Baht', '฿'], 'VND' => ['Vietnamese Dong', '₫'], 'PKR' => ['Pakistani Rupee', 'Rs '],
        'BDT' => ['Bangladeshi Taka', '৳'], 'LKR' => ['Sri Lankan Rupee', 'Rs '], 'NPR' => ['Nepalese Rupee', 'Rs '], 'NGN' => ['Nigerian Naira', '₦'],
        'KES' => ['Kenyan Shilling', 'KSh '], 'ZAR' => ['South African Rand', 'R '], 'EGP' => ['Egyptian Pound', 'E£'], 'TRY' => ['Turkish Lira', '₺'],
        'BRL' => ['Brazilian Real', 'R$'], 'MXN' => ['Mexican Peso', 'MX$'], 'JPY' => ['Japanese Yen', '¥'], 'KRW' => ['South Korean Won', '₩'],
        'CNY' => ['Chinese Yuan', 'CN¥'], 'HKD' => ['Hong Kong Dollar', 'HK$'], 'CHF' => ['Swiss Franc', 'CHF '], 'SEK' => ['Swedish Krona', 'kr '],
        'NOK' => ['Norwegian Krone', 'kr '], 'DKK' => ['Danish Krone', 'kr '], 'PLN' => ['Polish Zloty', 'zł '], 'CZK' => ['Czech Koruna', 'Kč '],
        'ILS' => ['Israeli Shekel', '₪'],
    ];
}

function default_fx_text()
{
    return "USD=1\nINR=84\nEUR=0.92\nGBP=0.79\nAED=3.67\nSAR=3.75\nQAR=3.64\nKWD=0.31\nOMR=0.385\nBHD=0.376\nAUD=1.52\nNZD=1.66\nCAD=1.38\nSGD=1.34\nMYR=4.5\nIDR=15800\nPHP=57\nTHB=35\nVND=25000\nPKR=280\nBDT=120\nLKR=300\nNPR=134\nNGN=1550\nKES=129\nZAR=18.2\nEGP=49\nTRY=34\nBRL=5.6\nMXN=19.5\nJPY=150\nKRW=1380\nCNY=7.2\nHKD=7.8\nCHF=0.88\nSEK=10.6\nNOK=10.8\nDKK=6.9\nPLN=4\nCZK=23\nILS=3.7";
}

/** Exchange rates (per 1 USD) from settings, falling back to the defaults. */
function fx_rates()
{
    static $rates = null;
    if ($rates !== null) {
        return $rates;
    }
    $rates = [];
    foreach ([default_fx_text(), (string) setting('fx_rates')] as $text) {
        foreach (preg_split('/[\r\n,]+/', $text) as $line) {
            if (preg_match('/^\s*([A-Z]{3})\s*[=:]\s*([\d.]+)\s*$/i', $line, $m) && (float) $m[2] > 0) {
                $rates[strtoupper($m[1])] = (float) $m[2];
            }
        }
    }
    return $rates;
}

function currency_symbol($code)
{
    $c = currency_catalog();
    return $c[$code][1] ?? ($code . ' ');
}

/** Rounds to two significant figures so converted budgets read naturally. */
function nice_round($n)
{
    if ($n <= 0) return 0;
    $mag = pow(10, floor(log10($n)) - 1);
    return round($n / $mag) * $mag;
}

/** Short money label in any currency: ₹4.2L, ₹1.3Cr, $15K, €1.2M. */
function money_compact($amount, $code)
{
    $sym = currency_symbol($code);
    $n = (float) $amount;
    if ($code === 'INR') {
        if ($n >= 10000000) return $sym . rtrim(rtrim(number_format($n / 10000000, 1), '0'), '.') . 'Cr';
        if ($n >= 100000) return $sym . rtrim(rtrim(number_format($n / 100000, 1), '0'), '.') . 'L';
    }
    if ($n >= 1000000000) return $sym . rtrim(rtrim(number_format($n / 1000000000, 1), '0'), '.') . 'B';
    if ($n >= 1000000) return $sym . rtrim(rtrim(number_format($n / 1000000, 1), '0'), '.') . 'M';
    if ($n >= 1000) return $sym . rtrim(rtrim(number_format($n / 1000, 1), '0'), '.') . 'K';
    return $sym . number_format($n);
}

function usd_to($usd, $code)
{
    $r = fx_rates();
    return $usd * ($r[$code] ?? 1);
}

/** Campaign budget bands, defined in USD and shown in the visitor's currency. */
function budget_tiers()
{
    return [
        'lt1k'    => [null, 1000],
        '1-5k'    => [1000, 5000],
        '5-15k'   => [5000, 15000],
        '15-50k'  => [15000, 50000],
        '50-150k' => [50000, 150000],
        '150k+'   => [150000, null],
        'unsure'  => [null, null],
    ];
}

function budget_label($key, $code)
{
    $t = budget_tiers();
    if (!isset($t[$key]) || $key === 'unsure') {
        return 'Not sure yet';
    }
    list($min, $max) = $t[$key];
    $f = function ($usd) use ($code) { return money_compact(nice_round(usd_to($usd, $code)), $code); };
    if ($min === null) return 'Under ' . $f($max);
    if ($max === null) return $f($min) . '+';
    return $f($min) . ' - ' . $f($max);
}

/** Country name => [ISO code, currency]. */
function country_catalog()
{
    $rows = [
        'India' => ['IN', 'INR'], 'United States' => ['US', 'USD'], 'United Kingdom' => ['GB', 'GBP'], 'United Arab Emirates' => ['AE', 'AED'],
        'Saudi Arabia' => ['SA', 'SAR'], 'Qatar' => ['QA', 'QAR'], 'Kuwait' => ['KW', 'KWD'], 'Oman' => ['OM', 'OMR'], 'Bahrain' => ['BH', 'BHD'],
        'Canada' => ['CA', 'CAD'], 'Australia' => ['AU', 'AUD'], 'New Zealand' => ['NZ', 'NZD'], 'Singapore' => ['SG', 'SGD'],
        'Malaysia' => ['MY', 'MYR'], 'Indonesia' => ['ID', 'IDR'], 'Philippines' => ['PH', 'PHP'], 'Thailand' => ['TH', 'THB'],
        'Vietnam' => ['VN', 'VND'], 'Pakistan' => ['PK', 'PKR'], 'Bangladesh' => ['BD', 'BDT'], 'Sri Lanka' => ['LK', 'LKR'],
        'Nepal' => ['NP', 'NPR'], 'Nigeria' => ['NG', 'NGN'], 'Kenya' => ['KE', 'KES'], 'South Africa' => ['ZA', 'ZAR'], 'Egypt' => ['EG', 'EGP'],
        'Germany' => ['DE', 'EUR'], 'France' => ['FR', 'EUR'], 'Spain' => ['ES', 'EUR'], 'Italy' => ['IT', 'EUR'], 'Netherlands' => ['NL', 'EUR'],
        'Ireland' => ['IE', 'EUR'], 'Portugal' => ['PT', 'EUR'], 'Belgium' => ['BE', 'EUR'], 'Austria' => ['AT', 'EUR'], 'Finland' => ['FI', 'EUR'],
        'Greece' => ['GR', 'EUR'], 'Sweden' => ['SE', 'SEK'], 'Norway' => ['NO', 'NOK'], 'Denmark' => ['DK', 'DKK'], 'Poland' => ['PL', 'PLN'],
        'Czech Republic' => ['CZ', 'CZK'], 'Switzerland' => ['CH', 'CHF'], 'Turkey' => ['TR', 'TRY'], 'Israel' => ['IL', 'ILS'],
        'Brazil' => ['BR', 'BRL'], 'Mexico' => ['MX', 'MXN'], 'Argentina' => ['AR', 'USD'], 'Colombia' => ['CO', 'USD'],
        'Japan' => ['JP', 'JPY'], 'South Korea' => ['KR', 'KRW'], 'China' => ['CN', 'CNY'], 'Hong Kong' => ['HK', 'HKD'],
    ];
    return $rows;
}

/** Front-end config printed once per page for the currency switcher and pickers. */
function geo_js_config()
{
    $cur = [];
    foreach (currency_catalog() as $code => $c) {
        $cur[$code] = [$c[0], $c[1]];
    }
    return [
        'currencies' => $cur,
        'rates' => fx_rates(),
        'def' => setting('default_currency', 'INR'),
        'auto' => setting('currency_auto', '1') === '1',
    ];
}

/** <option> list for a country <select>, enhanced into a themed picker by site.js. */
function country_options_html($selected = '', $withOther = true, $extra = [])
{
    $curs = currency_catalog();
    $out = '';
    foreach ($extra as $value => $label) {
        $out .= '<option value="' . e($value) . '" data-sub="' . e($label[1]) . '"' . ($selected === $value ? ' selected' : '') . '>' . e($label[0]) . '</option>';
    }
    foreach (country_catalog() as $name => $c) {
        $sub = $c[1] . ' · ' . ($curs[$c[1]][0] ?? $c[1]);
        $sel = ($selected === $name || $selected === $c[0]) ? ' selected' : '';
        $out .= '<option value="' . e($name) . '" data-code="' . e($c[0]) . '" data-currency="' . e($c[1]) . '" data-sub="' . e($sub) . '"' . $sel . '>' . e($name) . '</option>';
    }
    if ($withOther) {
        $isOther = $selected !== '' && !isset(country_catalog()[$selected]) && !isset($extra[$selected]);
        $out .= '<option value="' . ($isOther ? e($selected) : 'Other') . '" data-sub="Not listed"' . ($isOther ? ' selected' : '') . '>' . ($isOther ? e($selected) : 'Other country') . '</option>';
    }
    return $out;
}

function currency_options_html($selected)
{
    $out = '';
    foreach (currency_catalog() as $code => $c) {
        $out .= '<option value="' . e($code) . '" data-symbol="' . e($c[1]) . '" data-sub="' . e($c[0]) . '"' . ($selected === $code ? ' selected' : '') . '>' . e(trim($c[1]) . ' ' . $code) . '</option>';
    }
    return $out;
}
