<?php
/**
 * Name: Little Forest
 * Description: Counts WCAG 2.1 errors and links to page reports.
 */

/**
 * Little Forest Fields
 */
function little_forest_fields(){

    $little_forest_fields = array(
        
        // These fields are added to the database.
        'db' => [

                // Pages fields.
                'pages' => [
                    array(
                        'name' => 'little_forest_wcag_2_1_errors',
                        'type'  => 'VARCHAR(20)'
                    )
                ]
            
        ]
        
    );

    // Return fields
    return $little_forest_fields;

}

/**
 * little_forest Scans
 */
function little_forest_scans($url){

    // Add DB info and required functions.
    require_once '../config.php';
    require_once '../models/db.php';

    // Get Little Forest data.
    $override_https = array(
        "ssl"=>array(
            "verify_peer"=> false,
            "verify_peer_name"=> false,
        )
    );
    $little_forest_url = 'https://inspector.littleforest.co.uk/InspectorWS/Accessibility?url='.$url.'&level=WCAG2AA&cache=false';
    $little_forest_json = file_get_contents($little_forest_url, false, stream_context_create($override_https));

    // Fallback if LF scan doesn't work.
    if(strpos($little_forest_json, 'NoSuchFileException'))
        throw new Exception('Little Forest error related to page "'.$little_forest_url.'"');

    // Decode JSON and count WCAG errors.
    $little_forest_json_decoded = json_decode($little_forest_json, true);
    $little_forest_errors = $little_forest_json_decoded['Errors'];
    $little_forest_notices = $little_forest_json_decoded['Notices'];
    $little_forest_warnings = $little_forest_json_decoded['Warnings'];
    if($little_forest_errors == NULL)
        $little_forest_errors = 0;
        
    // Remove previously saved alerts.
    $alerts_filter = [
        array(
            'name'   =>  'url',
            'value'  =>  $url
        ),
        array(
            'name'   =>  'integration_uri',
            'value'  =>  'little_forest'
        )
    ];
    DataAccess::delete_alerts($alerts_filter);

    // Prevent a bug that occurs because LF adds "0" when no notices or errors.
    if($little_forest_errors == 0)
        $little_forest_errors = [];
    if($little_forest_warnings == 0)
        $little_forest_warnings = [];

    // Set site title.
    $site = DataAccess::get_page_site($url);

    // Add errors.
    if(count($little_forest_errors) >= 1){
        foreach($little_forest_errors as $error){

            // Create Meta
            $meta = array(
                'guideline' => $error['Guideline'],
                'tag'       =>  $error['Tag']
            );

            // Create message.
            if(!empty($error['Code']) && $error['Code'] != 'null'){
                $code = htmlentities('[code]'.$error['Code'].'[/code]');
                $message = $code.$error['Message'];
            }else{
                $message = $error['Message'];
            }

            // Add notice.
            DataAccess::add_alert('page', $url, $site, 'little_forest', 'error', $message, $meta);

        }
    }

    // Add notices.
    if(count($little_forest_notices) >= 1){
        foreach($little_forest_notices as $notice){

            // Create Meta
            $meta = array(
                'guideline' => $notice['Guideline'],
                'tag'       => $notice['Tag']
            );

            // Create message.
            if(!empty($notice['Code']) && $notice['Code'] != 'null'){
                $code = htmlentities('[code]'.$notice['Code'].'[/code]');
                $message = $code.$notice['Message'];
            }else{
                $message = $notice['Message'];
            }

            // Add notice.
            DataAccess::add_alert('page', $url, $site, 'little_forest', 'notice', $message, $meta);

        }
    }

    // Add warnings.
    if(count($little_forest_warnings) >= 1){
        foreach($little_forest_warnings as $warning){

            // Create Meta
            $meta = array(
                'guideline' => $warning['Guideline'],
                'tag'       => $warning['Tag']
            );

            // Create message.
            if(!empty($warning['Code']) && $warning['Code'] != 'null'){
                $code = htmlentities('[code]'.$warning['Code'].'[/code]');
                $message = $code.$warning['Message'];
            }else{
                $message = $warning['Message'];
            }

            // Add warning.
            DataAccess::add_alert('page', $url, $site, 'little_forest', 'warning', $message, $meta);

        }
    }


    // Update page data.
    $total_alerts = count($little_forest_errors+$little_forest_notices+$little_forest_warnings);
    DataAccess::update_page_data($url, 'little_forest_wcag_2_1_errors', $total_alerts);

}