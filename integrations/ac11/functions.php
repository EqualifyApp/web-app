<?php
/**
 * Name: ac11
 * Description: An automated accessibility scan.
 */

/**
 * ac11 Fields
 */
function ac11_fields(){

    $ac11_fields = array(
        
        // These fields are added to the database.
        'db' => [

                // Meta values.
                'meta' => [
                    array(
                        'name'     => 'ac11_token',
                        'value'     => '',
                    )
                ]
            
        ],

        // These fields are HTML fields on the settings view.
        'settings' => [

            // Meta settings.
            'meta' => [
                array(
                    'name'     => 'ac11_token',
                    'label'    => 'ac11 Token (Available at <a href="https://ac11.com/api-info" target="_blank">https://ac11.com/api-info</a>)',
                    'type'     => 'text',
                )
            ]

        ]

    );

    // Return fields
    return $ac11_fields;

}

/**
 * ac11 Tags
 */
function ac11_tags(){

    // We don't know where helpers are being called, so we
    // have to set the directory if it isn't already set.
    if(!defined('__DIR__'))
        define('__DIR__', dirname(dirname(__FILE__)));
    
    // Read the JSON file - pulled from https://ac11.webaim.org/api/docs?format=json
    $ac11_tag_json = file_get_contents(__DIR__.'/ac11_tags.json');
    $ac11_tags = json_decode($ac11_tag_json,true);

    // Convert ac11 format into Equalify format:
    // tags [ array('slug' => $value, 'name' => $value, 'description' => $value) ]
    $tags = array();
    if(!empty($ac11_tags)){
        foreach($ac11_tags as $ac11_tag){

            // First, let's prepare the description, which is
            // the summary and guidelines.
            $description = '<p class="lead">'.$ac11_tag['description'].'</p>';
            
            // Now lets put it all together into the Equalify format.
            array_push(
                $tags, array(
                    'title' => $ac11_tag['title'],
                    'category' => $ac11_tag['category'],
                    'description' => $description,

                    // ac11 uses periods, which get screwed up
                    // when equalify serializes them, so we're
                    // just not going to use periods
                    'slug' => str_replace('.', '', $ac11_tag['slug'])

                )
            );

        }
    }

    // Return tags.
    return $tags;

}

 /**
  * ac11 URLs
  * Maps site URLs to ac11 URLs for processing.
  */
function ac11_urls($page_url) {

    // Require ac11_token
    $ac11_token = DataAccess::get_meta_value('ac11_uri');
    if(empty($ac11_token)){
        throw new Exception('AC11 Token is not entered. Visit <a href="https://ac11.io" target="_blank">ac11.io</a> for a token.');
    }else{

        // Lets specify everything Guzzle needs to create request.
        return array(
            'type' => 'POST',
            'url'  => 'https://api.a11ywatch.com/api/scan',
            'headers' => array(
                'Authorization' => $ac11_token,
                'Content-Type' => 'application/json'
            ),
            'data' => array(
                'url' => $page_url
            )
        );

    }

}

/**
 * ac11 Alerts
 * @param string response_body
 * @param string page_url
 */
function ac11_alerts($response_body, $page_url){

    // Our goal is to return alerts.
    $ac11_alerts = [];
    $ac11_json = $response_body; 

    // Decode JSON.
    $ac11_json_decoded = json_decode($ac11_json);

    // Sometimes ac11 can't read the json.
    if(!empty($ac11_json_decoded)){

        // We add violations to this array.
        $ac11_violations = array();

        // Show ac11 violations
        foreach($ac11_json_decoded[0]->violations as $violation){

            // Only show violations.
            $ac11_violations[] = $violation;

        }

        // Add alerts.
        if(!empty($ac11_violations)) {

            // Setup alert variables.
            foreach($ac11_violations as $violation){

                // Default variables.
                $alert = array();
                $alert['source'] = 'ac11';
                $alert['url'] = $page_url;

                // Setup tags.
                $alert['tags'] = '';
                if(!empty($violation->tags)){

                    // We need to get rid of periods so Equalify
                    // wont convert them to underscores and they
                    // need to be comma separated.
                    $tags = $violation->tags;
                    $copy = $tags;
                    foreach($tags as $tag){
                        $alert['tags'].= str_replace('.', '', 'ac11_'.$tag);
                        if (next($copy ))
                            $alert['tags'].= ',';
                    }
                }                

                // Setup message.
                $alert['message'] = '"'.$violation->id.'" violation: '.$violation->help;

                // Setup more info.
                $alert['more_info'] = '';
                if($violation->nodes)
                    $alert['more_info'] = $violation->nodes;

                // Push alert.
                $ac11_alerts[] = $alert;
                
            }

        }

    }
    // Return alerts.
    return $ac11_alerts;

}