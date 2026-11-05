<?php
/**
 * States / regions per country and the main cities in each Indian state.
 * Countries without a list get a free "State or region" box on the form.
 */

function state_catalog()
{
    return [
        'India' => [
            'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh', 'Goa', 'Gujarat', 'Haryana', 'Himachal Pradesh',
            'Jharkhand', 'Karnataka', 'Kerala', 'Madhya Pradesh', 'Maharashtra', 'Manipur', 'Meghalaya', 'Mizoram', 'Nagaland', 'Odisha',
            'Punjab', 'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana', 'Tripura', 'Uttar Pradesh', 'Uttarakhand', 'West Bengal',
            'Andaman and Nicobar Islands', 'Chandigarh', 'Dadra and Nagar Haveli and Daman and Diu', 'Delhi', 'Jammu and Kashmir',
            'Ladakh', 'Lakshadweep', 'Puducherry',
        ],
        'United States' => [
            'Alabama', 'Alaska', 'Arizona', 'Arkansas', 'California', 'Colorado', 'Connecticut', 'Delaware', 'District of Columbia', 'Florida',
            'Georgia', 'Hawaii', 'Idaho', 'Illinois', 'Indiana', 'Iowa', 'Kansas', 'Kentucky', 'Louisiana', 'Maine', 'Maryland', 'Massachusetts',
            'Michigan', 'Minnesota', 'Mississippi', 'Missouri', 'Montana', 'Nebraska', 'Nevada', 'New Hampshire', 'New Jersey', 'New Mexico',
            'New York', 'North Carolina', 'North Dakota', 'Ohio', 'Oklahoma', 'Oregon', 'Pennsylvania', 'Rhode Island', 'South Carolina',
            'South Dakota', 'Tennessee', 'Texas', 'Utah', 'Vermont', 'Virginia', 'Washington', 'West Virginia', 'Wisconsin', 'Wyoming',
        ],
        'United Kingdom' => ['England', 'Scotland', 'Wales', 'Northern Ireland'],
        'United Arab Emirates' => ['Abu Dhabi', 'Dubai', 'Sharjah', 'Ajman', 'Umm Al Quwain', 'Ras Al Khaimah', 'Fujairah'],
        'Canada' => ['Alberta', 'British Columbia', 'Manitoba', 'New Brunswick', 'Newfoundland and Labrador', 'Nova Scotia', 'Ontario',
            'Prince Edward Island', 'Quebec', 'Saskatchewan', 'Northwest Territories', 'Nunavut', 'Yukon'],
        'Australia' => ['New South Wales', 'Victoria', 'Queensland', 'Western Australia', 'South Australia', 'Tasmania',
            'Australian Capital Territory', 'Northern Territory'],
        'Pakistan' => ['Punjab', 'Sindh', 'Khyber Pakhtunkhwa', 'Balochistan', 'Islamabad Capital Territory', 'Gilgit-Baltistan', 'Azad Kashmir'],
        'Bangladesh' => ['Barishal', 'Chattogram', 'Dhaka', 'Khulna', 'Mymensingh', 'Rajshahi', 'Rangpur', 'Sylhet'],
        'Sri Lanka' => ['Central', 'Eastern', 'North Central', 'Northern', 'North Western', 'Sabaragamuwa', 'Southern', 'Uva', 'Western'],
        'Nepal' => ['Koshi', 'Madhesh', 'Bagmati', 'Gandaki', 'Lumbini', 'Karnali', 'Sudurpashchim'],
        'Saudi Arabia' => ['Riyadh', 'Makkah', 'Madinah', 'Eastern Province', 'Asir', 'Tabuk', 'Hail', 'Northern Borders', 'Jazan', 'Najran',
            'Al Bahah', 'Al Jawf', 'Qassim'],
        'Malaysia' => ['Johor', 'Kedah', 'Kelantan', 'Melaka', 'Negeri Sembilan', 'Pahang', 'Penang', 'Perak', 'Perlis', 'Sabah', 'Sarawak',
            'Selangor', 'Terengganu', 'Kuala Lumpur', 'Labuan', 'Putrajaya'],
    ];
}

/** Main cities for each Indian state and union territory (suggestions; any city can be typed). */
function india_cities()
{
    return [
        'Andhra Pradesh' => ['Visakhapatnam', 'Vijayawada', 'Guntur', 'Nellore', 'Kurnool', 'Rajahmundry', 'Tirupati', 'Kakinada', 'Kadapa', 'Anantapur', 'Eluru', 'Ongole', 'Vizianagaram', 'Srikakulam', 'Machilipatnam', 'Amaravati'],
        'Arunachal Pradesh' => ['Itanagar', 'Naharlagun', 'Pasighat', 'Tawang', 'Ziro', 'Bomdila', 'Tezu'],
        'Assam' => ['Guwahati', 'Silchar', 'Dibrugarh', 'Jorhat', 'Nagaon', 'Tinsukia', 'Tezpur', 'Bongaigaon', 'Dhubri', 'Diphu', 'North Lakhimpur', 'Karimganj', 'Sivasagar', 'Goalpara'],
        'Bihar' => ['Patna', 'Gaya', 'Bhagalpur', 'Muzaffarpur', 'Darbhanga', 'Purnia', 'Arrah', 'Begusarai', 'Katihar', 'Munger', 'Chapra', 'Sasaram', 'Hajipur', 'Bihar Sharif', 'Motihari', 'Siwan'],
        'Chhattisgarh' => ['Raipur', 'Bhilai', 'Bilaspur', 'Korba', 'Durg', 'Rajnandgaon', 'Raigarh', 'Jagdalpur', 'Ambikapur', 'Dhamtari'],
        'Goa' => ['Panaji', 'Margao', 'Vasco da Gama', 'Mapusa', 'Ponda', 'Calangute', 'Bicholim'],
        'Gujarat' => ['Ahmedabad', 'Surat', 'Vadodara', 'Rajkot', 'Bhavnagar', 'Jamnagar', 'Gandhinagar', 'Junagadh', 'Anand', 'Navsari', 'Morbi', 'Nadiad', 'Bharuch', 'Mehsana', 'Bhuj', 'Porbandar', 'Vapi', 'Valsad', 'Palanpur', 'Gandhidham'],
        'Haryana' => ['Gurugram', 'Faridabad', 'Panipat', 'Ambala', 'Yamunanagar', 'Rohtak', 'Hisar', 'Karnal', 'Sonipat', 'Panchkula', 'Bhiwani', 'Sirsa', 'Bahadurgarh', 'Jind', 'Kurukshetra', 'Rewari'],
        'Himachal Pradesh' => ['Shimla', 'Dharamshala', 'Mandi', 'Solan', 'Kullu', 'Manali', 'Hamirpur', 'Una', 'Bilaspur', 'Chamba', 'Kangra', 'Palampur'],
        'Jharkhand' => ['Ranchi', 'Jamshedpur', 'Dhanbad', 'Bokaro Steel City', 'Deoghar', 'Hazaribagh', 'Giridih', 'Ramgarh', 'Dumka', 'Phusro'],
        'Karnataka' => ['Bengaluru', 'Mysuru', 'Mangaluru', 'Hubballi', 'Dharwad', 'Belagavi', 'Kalaburagi', 'Davanagere', 'Ballari', 'Vijayapura', 'Shivamogga', 'Tumakuru', 'Udupi', 'Hassan', 'Raichur', 'Bidar', 'Chikkamagaluru', 'Mandya', 'Hosapete', 'Karwar'],
        'Kerala' => ['Thiruvananthapuram', 'Kochi', 'Kozhikode', 'Thrissur', 'Kollam', 'Kannur', 'Alappuzha', 'Palakkad', 'Kottayam', 'Malappuram', 'Kasaragod', 'Pathanamthitta', 'Idukki', 'Wayanad', 'Munnar', 'Varkala'],
        'Madhya Pradesh' => ['Indore', 'Bhopal', 'Jabalpur', 'Gwalior', 'Ujjain', 'Sagar', 'Dewas', 'Satna', 'Ratlam', 'Rewa', 'Katni', 'Singrauli', 'Burhanpur', 'Khandwa', 'Chhindwara', 'Morena', 'Vidisha', 'Guna'],
        'Maharashtra' => ['Mumbai', 'Pune', 'Nagpur', 'Thane', 'Nashik', 'Navi Mumbai', 'Aurangabad', 'Solapur', 'Kolhapur', 'Amravati', 'Kalyan', 'Vasai-Virar', 'Pimpri-Chinchwad', 'Sangli', 'Jalgaon', 'Akola', 'Latur', 'Dhule', 'Ahmednagar', 'Chandrapur', 'Parbhani', 'Nanded', 'Ichalkaranji', 'Satara', 'Ratnagiri', 'Panvel', 'Bhiwandi', 'Lonavala', 'Wardha', 'Yavatmal'],
        'Manipur' => ['Imphal', 'Thoubal', 'Bishnupur', 'Churachandpur', 'Ukhrul', 'Kakching'],
        'Meghalaya' => ['Shillong', 'Tura', 'Jowai', 'Nongpoh', 'Cherrapunji'],
        'Mizoram' => ['Aizawl', 'Lunglei', 'Champhai', 'Serchhip', 'Kolasib'],
        'Nagaland' => ['Kohima', 'Dimapur', 'Mokokchung', 'Wokha', 'Tuensang', 'Mon'],
        'Odisha' => ['Bhubaneswar', 'Cuttack', 'Rourkela', 'Berhampur', 'Sambalpur', 'Puri', 'Balasore', 'Bhadrak', 'Baripada', 'Jharsuguda', 'Angul', 'Jeypore', 'Bargarh', 'Rayagada'],
        'Punjab' => ['Ludhiana', 'Amritsar', 'Jalandhar', 'Patiala', 'Bathinda', 'Mohali', 'Hoshiarpur', 'Pathankot', 'Moga', 'Batala', 'Abohar', 'Phagwara', 'Firozpur', 'Kapurthala', 'Sangrur', 'Barnala', 'Rajpura'],
        'Rajasthan' => ['Jaipur', 'Jodhpur', 'Kota', 'Bikaner', 'Ajmer', 'Udaipur', 'Bhilwara', 'Alwar', 'Bharatpur', 'Sikar', 'Sri Ganganagar', 'Pali', 'Tonk', 'Kishangarh', 'Beawar', 'Hanumangarh', 'Jaisalmer', 'Chittorgarh', 'Pushkar', 'Mount Abu', 'Barmer', 'Jhunjhunu'],
        'Sikkim' => ['Gangtok', 'Namchi', 'Gyalshing', 'Mangan', 'Rangpo', 'Singtam'],
        'Tamil Nadu' => ['Chennai', 'Coimbatore', 'Madurai', 'Tiruchirappalli', 'Salem', 'Tirunelveli', 'Tiruppur', 'Vellore', 'Erode', 'Thoothukudi', 'Dindigul', 'Thanjavur', 'Hosur', 'Nagercoil', 'Kanchipuram', 'Kumbakonam', 'Karur', 'Cuddalore', 'Rajapalayam', 'Pollachi', 'Ooty', 'Kodaikanal', 'Tambaram'],
        'Telangana' => ['Hyderabad', 'Warangal', 'Nizamabad', 'Karimnagar', 'Khammam', 'Ramagundam', 'Mahbubnagar', 'Nalgonda', 'Adilabad', 'Suryapet', 'Siddipet', 'Secunderabad', 'Miryalaguda'],
        'Tripura' => ['Agartala', 'Udaipur', 'Dharmanagar', 'Kailashahar', 'Belonia', 'Ambassa'],
        'Uttar Pradesh' => ['Lucknow', 'Kanpur', 'Ghaziabad', 'Agra', 'Varanasi', 'Meerut', 'Prayagraj', 'Noida', 'Greater Noida', 'Bareilly', 'Aligarh', 'Moradabad', 'Saharanpur', 'Gorakhpur', 'Firozabad', 'Jhansi', 'Muzaffarnagar', 'Mathura', 'Vrindavan', 'Ayodhya', 'Rampur', 'Shahjahanpur', 'Farrukhabad', 'Mau', 'Hapur', 'Etawah', 'Mirzapur', 'Bulandshahr', 'Sambhal', 'Amroha', 'Hardoi', 'Fatehpur', 'Raebareli', 'Orai', 'Sitapur', 'Bahraich', 'Unnao', 'Jaunpur', 'Lakhimpur', 'Ballia', 'Azamgarh', 'Sultanpur'],
        'Uttarakhand' => ['Dehradun', 'Haridwar', 'Roorkee', 'Haldwani', 'Rudrapur', 'Kashipur', 'Rishikesh', 'Nainital', 'Mussoorie', 'Almora', 'Pithoragarh', 'Kotdwar'],
        'West Bengal' => ['Kolkata', 'Howrah', 'Durgapur', 'Asansol', 'Siliguri', 'Bardhaman', 'Malda', 'Kharagpur', 'Haldia', 'Darjeeling', 'Krishnanagar', 'Baharampur', 'Bally', 'Barasat', 'Jalpaiguri', 'Cooch Behar', 'Bankura', 'Purulia', 'Shantiniketan', 'Kalyani'],
        'Andaman and Nicobar Islands' => ['Port Blair', 'Havelock Island', 'Neil Island', 'Car Nicobar', 'Mayabunder', 'Diglipur'],
        'Chandigarh' => ['Chandigarh'],
        'Dadra and Nagar Haveli and Daman and Diu' => ['Daman', 'Diu', 'Silvassa'],
        'Delhi' => ['New Delhi', 'Delhi', 'Dwarka', 'Rohini', 'Saket', 'Karol Bagh', 'Connaught Place', 'Lajpat Nagar', 'Janakpuri', 'Pitampura', 'Laxmi Nagar', 'Vasant Kunj', 'Mayur Vihar', 'Rajouri Garden', 'Hauz Khas'],
        'Jammu and Kashmir' => ['Srinagar', 'Jammu', 'Anantnag', 'Baramulla', 'Sopore', 'Kathua', 'Udhampur', 'Pahalgam', 'Gulmarg', 'Pulwama', 'Kupwara'],
        'Ladakh' => ['Leh', 'Kargil'],
        'Lakshadweep' => ['Kavaratti', 'Agatti', 'Minicoy', 'Amini'],
        'Puducherry' => ['Puducherry', 'Karaikal', 'Mahe', 'Yanam'],
    ];
}

/* ---------------- Database-backed lists ----------------
 * Full state and city lists live in the `locations` table, imported from
 * app/data/locations/{ISO}.json (Countries States Cities Database by dr5hn,
 * ODbL-1.0). The built-in lists above are the fallback and mark the popular
 * cities, which are listed first.
 */

/** Countries that have a full city list in the database. */
function location_city_countries()
{
    static $list = null;
    if ($list !== null) return $list;
    try {
        $list = array_column(all('SELECT DISTINCT country FROM locations'), 'country');
    } catch (Throwable $e) {
        $list = [];
    }
    if (!$list) $list = ['India'];
    return $list;
}

/** States for a country (empty when the country has no list). */
function states_for($country)
{
    static $cache = [];
    $country = (string) $country;
    if (isset($cache[$country])) return $cache[$country];
    $rows = [];
    try {
        $rows = array_column(all('SELECT DISTINCT state FROM locations WHERE country = ? ORDER BY state', [$country]), 'state');
    } catch (Throwable $e) {
        $rows = [];
    }
    return $cache[$country] = $rows ?: (state_catalog()[$country] ?? []);
}

/** Cities in a state, popular ones first. */
function location_cities($country, $state)
{
    static $cache = [];
    $key = $country . '|' . $state;
    if (isset($cache[$key])) return $cache[$key];
    if ((string) $state === '') return $cache[$key] = [];
    $rows = [];
    try {
        $rows = array_column(all('SELECT city FROM locations WHERE country = ? AND state = ? ORDER BY CASE WHEN popular > 0 THEN 0 ELSE 1 END, popular, city', [$country, $state]), 'city');
    } catch (Throwable $e) {
        $rows = [];
    }
    if (!$rows && $country === 'India') $rows = india_cities()[$state] ?? [];
    return $cache[$key] = $rows;
}

/** "mumbai " -> "Mumbai"; known cities get their usual spelling. */
function normalize_city($city, $state = '', $country = 'India')
{
    $city = trim(preg_replace('/\s+/u', ' ', (string) $city));
    if ($city === '') return '';
    $pool = $state !== '' ? location_cities($country, $state) : [];
    foreach ($pool as $known) {
        if (mb_strtolower($known) === mb_strtolower($city)) return $known;
    }
    return mb_convert_case($city, MB_CASE_TITLE, 'UTF-8');
}

/** Import (or refresh) every app/data/locations/{ISO}.json file into the database. */
function import_locations()
{
    $files = glob(APP . '/data/locations/*.json') ?: [];
    $done = 0;
    foreach ($files as $file) {
        $data = json_decode((string) file_get_contents($file), true);
        if (!is_array($data) || empty($data['country']) || empty($data['states'])) continue;
        $country = $data['country'];
        $popular = $country === 'India' ? india_cities() : [];
        $pdo = db();
        $pdo->beginTransaction();
        try {
            q('DELETE FROM locations WHERE country = ?', [$country]);
            $ins = $pdo->prepare('INSERT INTO locations (country, state, city, popular) VALUES (?, ?, ?, ?)');
            foreach ($data['states'] as $state => $cities) {
                $rank = array_flip($popular[$state] ?? []);
                // The popular list adds well-known names the dataset may not have.
                $all = array_values(array_unique(array_merge($popular[$state] ?? [], $cities)));
                foreach ($all as $city) {
                    $ins->execute([$country, $state, mb_substr($city, 0, 160), isset($rank[$city]) ? $rank[$city] + 1 : 0]);
                    $done++;
                }
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    return $done;
}

/** Lists for the creator form script: states per country, and where to fetch cities. */
function location_js_config()
{
    $states = state_catalog();
    foreach (location_city_countries() as $c) {
        $states[$c] = states_for($c);
    }
    return ['states' => $states, 'cityCountries' => location_city_countries(), 'citiesUrl' => url('locations/cities')];
}

/** GET /locations/cities?country=India&state=Maharashtra -> {"cities": [...]} */
function locations_cities_json()
{
    $country = mb_substr((string) query('country'), 0, 100);
    $state = mb_substr((string) query('state'), 0, 120);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: public, max-age=86400');
    header('X-Robots-Tag: noindex');
    echo json_encode(['cities' => location_cities($country, $state)], JSON_UNESCAPED_UNICODE);
}
