<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * Let's setup all the tables that Equalify needs to run!
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
**********************************************************/

// Wave key is required for activation.
if(empty($GLOBALS['wave_key']))
    throw new Exception('Equalify requires a WAVE key. Get your key at https://wave.webaim.org/api/ and add it to the config.php file.');

// All the tables are created with this action.
if(DataAccess::table_exists('alerts') == false)
    DataAccess::create_alerts_table();
if(DataAccess::table_exists('queued_alerts') == false)
    DataAccess::create_queued_alerts_table();
if(DataAccess::table_exists('sites') == false)
    DataAccess::create_sites_table();
if(DataAccess::table_exists('tags') == false)
    DataAccess::create_tags_table();
if(DataAccess::table_exists('meta') == false)
    DataAccess::create_meta_table();