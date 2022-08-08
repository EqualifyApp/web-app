<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document controls everything around site status. 
 * 
 * As always, we must remember that every function should 
 * be designed to be as effcient as possible so that 
 * Equalify works for everyone.
**********************************************************/
// Add DB Info
require_once '../config.php';
require_once '../models/db.php';

// Get URL variables and fallbacks.
$site_id = $_GET['id'];
if(empty($site_id))
    throw new Exception('Site is not specified');
$old_status = $_GET['old_status'];
if(empty($old_status))
    throw new Exception(
        'Status is not specfied for "'.$site.'"'
    );

// Toggle site status.
if($old_status == 'active'){

    // Change site status
    $filtered_to_id = array(
        array(
            'name' => 'id',
            'value'=> $site_id
        )
    );
    $fields_to_update = array(
        array(
            'name' => 'status',
            'value' => 'archived'
        )
    );
    DataAccess::update_db_rows(
        'sites', $fields_to_update, $filtered_to_id
    );

}
if($old_status == 'archived'){

    // Change site status
    $filtered_to_id = array(
        array(
            'name' => 'id',
            'value'=> $site_id
        )
    );
    $fields_to_update = array(
        array(
            'name' => 'status',
            'value' => 'active'
        )
    );
    DataAccess::update_db_rows(
        'sites', $fields_to_update, $filtered_to_id
    );

}

// Redirect
header('Location: ../index.php?view=sites');