<?php
// Add DB Info
require_once '../config.php';
require_once '../models/db.php';
$db = connect(
    DB_HOST, 
    DB_USERNAME,
    DB_PASSWORD,
    DB_NAME
);

// Get URL Variables and fallbacks
$property_url = filter_input(INPUT_GET, 'url', FILTER_VALIDATE_URL);
if($property_url == false)
    throw new Exception('URL format is invalid.');

// Check if URL is unique
if(!is_unique_property_url($db, $property_url))
    throw new Exception('Site has already been added.');

// Query WP API
$override_https = array(
    "ssl"=>array(
        "verify_peer"=> false,
        "verify_peer_name"=> false,
    )
);
$wp_api_url = $property_url.'/?rest_route=/wp/v2/pages';

// Save API Contents
$curl = curl_init($wp_api_url);
curl_setopt($curl, CURLOPT_URL, $wp_api_url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
$headers = array(
   "Accept: application/json",
);
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($curl, CURLOPT_USERAGENT, 'Equalify');
$wp_api_contents = curl_exec($curl);
curl_close($curl);
if($wp_api_contents == false)
    throw new Exception('Contents cannot be loaded.');
    
// Create JSON
$wp_api_json = json_decode($wp_api_contents, true);
if(empty($wp_api_json[0]))
    throw new Exception('URL does link to valid WordPress API.');

// Create Pages Array
$pages = [];
foreach ($wp_api_json as $page):
    array_push($pages, array('url' => $page['link'], 'wcag_errors' => NULL));
endforeach;

// Set Account Info
$account_info = get_account($db, USER_ID);

// Check if user has credits 
if($account_info->credits < count($pages))
    die('You do not have enough credits.');

// Conditional WAVE Accessibility Test
if($account_info->accessibility_testing_service == 'WAVE'){

    // Loop Pages
    foreach ($pages as &$page):
        
        // Get Little Forrest page errors.
        $wave_url = 'https://wave.webaim.org/api/request?key='.$account_info->wave_key.'&url='.$page['url'];
        $wave_json = file_get_contents($wave_url, false, stream_context_create($override_https));
        $wave_json_decoded = json_decode($wave_json, true);        
        $wave_errors = $wave_json_decoded['categories']['error']['count'];

        // Update post meta.
        $page['wcag_errors'] = $wave_errors;
    
    endforeach;

// Conditional Little Forrest Accessibility Test
}elseif($account_info->accessibility_testing_service == 'Little Forrest'){

    // Loop Pages
    foreach ($pages as &$page):
        
        // Get Little Forrest page errors.
        $little_forrest_url = 'https://inspector.littleforest.co.uk/InspectorWS/Accessibility?url='.$page['url'].'&level=WCAG2AA';
        $little_forrest_json = file_get_contents($little_forrest_url, false, stream_context_create($override_https));
        $little_forrest_json_decoded = json_decode($little_forrest_json, true);
        $little_forrest_errors = count($little_forrest_json_decoded['Errors']);

        // Update post meta.
        $page['wcag_errors'] = $little_forrest_errors;
    
    endforeach;

// Fallback if No Testing Service is provided
}else{

    throw new Exception('No testing service is specified.');

}

// Insert Site Record
$property_record = [
    'url' => $property_url
];
insert_property($db, $property_record);

// Insert Pages Records
foreach ($pages as $page):
    $pages_record = [
        'wcag_errors' => $page['wcag_errors'],
        'url' => $page['url'],
        'property_id' => get_property_id($db, $property_url)
    ];
    insert_page($db, $pages_record);
endforeach;

// Subtract Used credit
subtract_account_credits($db, USER_ID, count($pages) );

// Redirect
header("Location: ../index.php?view=properties&status=success");