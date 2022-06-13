<?php
class DataAccess {

    // Set the records per page.
    private const ITEMS_PER_PAGE = 10;

    // Connect to MySQL.
    private static $conn = null;
    private static function connect() {
        if (self::$conn) {
            return self::$conn;
        } else {
            self::$conn = new mysqli($GLOBALS['DB_HOST'], $GLOBALS['DB_USERNAME'], $GLOBALS['DB_PASSWORD'], $GLOBALS['DB_NAME']);
            if(self::$conn->connect_error){
                throw new Exception('Cannot connect to database: ' . self::$conn->connect_error);
            }
            return self::$conn;
        }
    }
 
    /**
     * Query helper.
     *
     * @param string $sql The query SQL.
     * @param array $params The query parameters to bind.
     * @param boolean $return If we're expecting a result.
     * @return mysqli_result|boolean
     */
    private static function query($sql, $params, $return) {
        $connection = self::connect();
        if (empty($params)) {
            $results = $connection->query($sql);
        } else {
            $statement = $connection->prepare($sql);
            if ($statement === false) {
                throw new Exception('Unable to prepare SQL: ' . $connection->error);
            }
            $statement->bind_param(str_repeat('s', count($params)), ...$params);
            $results = $statement->execute();
            if ($return) {
                $results = $statement->get_result();
            }
        }

        return $results;
    }

    /**
     * Get All Pages
     * @param array filters [ array ('name' => $name, 'value' => $value) ]
     */
    public static function get_pages($filters = []){
    
        // SQL
        $sql = 'SELECT * FROM `pages`';
        $params = array();
    
        // Add optional filters
        $filter_count = count($filters);
        if($filter_count > 0){
            $sql.= 'WHERE ';
    
            $filter_iteration = 0;
            foreach ($filters as $filter){
                $sql.= '`'.$filter['name'].'` = ?';
                $params[] = $filter['value'];
                if(++$filter_iteration != $filter_count)
                    $sql.= ' AND ';
        
            }
        }
        $sql.= ';';
    
        // Query
        $results = self::query($sql, $params, true);
    
        // Result
        $data = [];
        if($results->num_rows > 0){
            while($row = $results->fetch_object()){
                $data[] = $row;
            }
        }
    
        // We're adding a condition so that we don't loop
        // when there is nothing to return.
        if($results->num_rows == 0){
            return NULL;
        }else{
            return $data;
        }
    }

    /**
     * Get Sites
     * @param array filters [ array ('name' => $name, 'value' => $value) ]
     */
    public static function get_sites($filters = []){
    
        // SQL
        $sql = 'SELECT DISTINCT `site` FROM `pages`';
        $params = array();

        // Add optional filters
        $filter_count = count($filters);
        if($filter_count > 0){
            $sql.= ' WHERE ';
    
            $filter_iteration = 0;
            foreach ($filters as $filter){
                $sql.= '`'.$filter['name'].'` = ?';
                $params[] = $filter['value'];
                if(++$filter_iteration != $filter_count)
                    $sql.= ' AND ';
        
            }
        }
        $sql.= ' ORDER BY `site`;';

    
        // Query
        $results = self::query($sql, $params, true);
    
        // Result
        $data = [];
        if($results->num_rows > 0){
            while($row = $results->fetch_object()){
                array_push($data, $row->site);
            }
        }
        return $data;
    
    }
    
    /**
     * Get Scans
     * @param array filters [ array ('name' => $name, 'value' => $value) ]
     */
    public static function get_scans($filters = []){
    
        // SQL
        $sql = 'SELECT * FROM `scans`';
        $params = array();
        

    
        // Add optional filters
        $filter_count = count($filters);
        if($filter_count > 0){
            $sql.= ' WHERE ';
            $filter_iteration = 0;
            foreach ($filters as $filter){
                $sql.= '`'.$filter['name'].'` = ?';
                $params[] = $filter['value'];
                if(++$filter_iteration != $filter_count)
                    $sql.= ' AND ';
        
            }
        }
        $sql.= ' ORDER BY STR_TO_DATE(`time`,"%Y-%m-%d %H:%i:%s") DESC;';
    
        // Query
        $results = self::query($sql, $params, true);
    
        // Result
        $data = [];
        if($results->num_rows > 0){
            while($row = $results->fetch_object()){
                $data[] = $row;
            }
        }
        return $data;
    }

    /**
     * Get Next Scan
     */
    public static function get_next_scan(){
    
        // SQL
        $sql = 'SELECT * FROM `scans` WHERE status = "queued" ORDER BY `time` ASC LIMIT 1';
        $params = array();
    
        // Query
        $results = self::query($sql, $params, false);
    
        // Results
        $data = $results->fetch_object();
        if($data == NULL || empty($data)){

            // Returns "false" if no data exists.
            return NULL;

        }else{

            // Returns meta_value.
            return $data;

        }
    
    }

    /**
     * Get Alerts
     * @param array filters [ array ('name' => $name, 'value' => $value, 'page' => $page) ]
     * @param string page
     */
    public static function get_alerts($filters = [], $page = 1){

        // Set alerts per page.
        $alerts_per_page = self::ITEMS_PER_PAGE;
        $page_offset = ($page-1) * $alerts_per_page;

        // Create 'total_pages' SQL.
        $total_pages_sql = "SELECT COUNT(*) FROM `alerts`";

        // Create 'content' SQL.
        $content_sql = "SELECT * FROM `alerts`";
        $params = array();

        // Add optional filters to content and total_pages.
        $filter_count = count($filters);
        $filters_sql = '';
        if($filter_count > 0){
            $filters_sql = ' WHERE ';
            $filter_iteration = 0;
            foreach ($filters as $filter){
                $filters_sql.= '`'.$filter['name'].'` = ?';
                $params[] = $filter['value'];
                if(++$filter_iteration != $filter_count)
                    $filters_sql.= ' AND ';
        
            }
        }

        // Add filters and page limit.
        $total_pages_sql.= $filters_sql;
        $content_sql.= $filters_sql.' LIMIT '.$page_offset.', '.$alerts_per_page;

        // Run 'total_pages' SQL.
        $total_pages_result = self::query($total_pages_sql, $params, true);
        $total_pages_rows = $total_pages_result->fetch_array()[0];
        $total_pages = ceil($total_pages_rows / $alerts_per_page);
    
        // Run 'content' SQL
        $content_results = self::query($content_sql, $params, true);
        $content = [];
        if($content_results->num_rows > 0){
            while($row = $content_results->fetch_object()){
                $content[] = $row;
            }
        }
    
        // Create and return data.
        $data = [
            'total_alerts' => $total_pages_rows,
            'total_pages' => $total_pages,
            'content' => $content
        ];
        return $data;

    }

    /**
     * Count Alerts
     * @param array filters [ array ('name' => $name, 'value' => $value, 'page' => $page) ]
     * @param string page
     */
    public static function count_alerts($filters = []){

        // SQL
        $sql = 'SELECT COUNT(*) AS TOTAL FROM `alerts`';
        $params = array();

        // Add optional filters to content and total_pages.
        $filter_count = count($filters);
        $filters_sql = '';
        if($filter_count > 0){
            $filters_sql = ' WHERE ';
            $filter_iteration = 0;
            foreach ($filters as $filter){
                $filters_sql.= '`'.$filter['name'].'` = ?';
                $params[] = $filter['value'];
                if(++$filter_iteration != $filter_count)
                    $filters_sql.= ' AND ';
        
            }
        }
        $sql.= $filters_sql;


        // Query
        $results = self::query($sql, $params, true);

        // Result
        $data = $results->fetch_object()->TOTAL;
        return $data;

    }

    /**
     * Get Alerts By Site
     */
    public static function get_alerts_by_site($site){
    
        // SQL
        $sql = 'SELECT * FROM `alerts` WHERE `site` = ?';
        $params = array($site);
    
        // Query
        $results = self::query($sql, $params, true);
    
        // Result
        $data = [];
        if($results->num_rows > 0){
            while($row = $results->fetch_object()){
                $data[] = $row;
            }
        }
        return $data;

        // Close
        $results->close();
    
    }

    /**
     * Get Meta
     * @param array filters [ array ('name' => $name, 'value' => $value) ]
     */
    public static function get_meta($filters = []){
    
        // SQL
        $sql = 'SELECT * FROM `meta`';
        $params = array();
    
        // Add optional filters
        $filter_count = count($filters);
        if($filter_count > 0){
            $sql.= ' WHERE ';
    
            $filter_iteration = 0;
            foreach ($filters as $filter){
                $sql.= '`'.$filter['name'].'` = ?';
                $params[] = $filter['value'];
                if(++$filter_iteration != $filter_count)
                    $sql.= ' AND ';
        
            }
        }
        $sql.= ';';
    
        // Query
        $results = self::query($sql, $params, true);
    
        // Result
        $data = [];
        if($results->num_rows > 0){
            while($row = $results->fetch_object()){
                $data[] = $row;
            }
        }
        return $data;
    }
    
    /**
     * Get Meta Value
     */
    public static function get_meta_value($meta_name){

        // SQL
        $sql = 'SELECT * FROM `meta` WHERE `meta_name` = ?';
        $params = array($meta_name);
    
        // Query
        $results = self::query($sql, $params, true);

        // Results
        $data = $results->fetch_object();
        if($data == NULL){

            // Returns "false" if no data exists.
            return false; 

        }else{

            // Returns meta_value.
            return $data->meta_value;

        }
    
    }
    
    /**
     * Get Page
     */
    public static function get_page($id){
    
        // SQL
        $sql = 'SELECT * FROM pages WHERE id = ?';
        $params = array($id);
    
        // Query
        $results = self::query($sql, $params, true);
    
        // Result
        $data = $results->fetch_object();
        return $data;
        
    }

    /**
     * Get Page Site
     */
    public static function get_page_site($url){
    
        // SQL
        $sql = 'SELECT `site` FROM `pages` WHERE `url` = ?';
        $params = array($url);
    
        // Query
        $results = self::query($sql, $params, true);
    
        // Result
        $data = $results->fetch_object()->site;
        return $data;
        
    }
    
    /**
     * Get Page ID
     */
    public static function get_page_id($url){
    
        // SQL
        $sql = 'SELECT `id` FROM `pages` WHERE `url` = ?';
        $params = array($url);
    
        // Query
        $results = self::query($sql, $params, true);
    
        // Result
        $data = $results->fetch_object()->id;
        return $data;
        
    }
    
    /**
     * Get Site Status
     */
    public static function get_site_status($site){
    
        // SQL
        $sql = 'SELECT `status` FROM `pages` WHERE `site` = ?';
        $params = array($site);
    
        // Query
        $results = self::query($sql, $params, true);
    
        // Result
        $data = $results->fetch_object()->status;
        return $data;
        
    }

    /**
     * Get Site Type
     */
    public static function get_site_type($site){
    
        // SQL
        $sql = 'SELECT `type` FROM `pages` WHERE `site` = ?';
        $params = array($site);
    
        // Query
        $results = self::query($sql, $params, true);
    
        // Result
        $data = $results->fetch_object()->type;
        return $data;
        
    }
    
    /**
     * Get Column Names
     */
    public static function get_column_names($table){
    
        // SQL
        $sql = 'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS ';
        $sql.= 'WHERE TABLE_NAME = ? AND TABLE_SCHEMA = ?';
        $params = array($table, $GLOBALS['DB_NAME']);
    
        // Query
        $results = self::query($sql, $params, true);
    
        // Result
        $data = [];
        if($results->num_rows > 0){
            while($row = $results->fetch_object()){
                $data[] = $row;
            }
        }
        return $data;
    
    }
    
    
    /**
     * Is Unique Site
     */
    public static function is_unique_site($site_url){
    
        // Require unique URL
        $sql = 'SELECT * FROM `pages` WHERE `site` = ?';
    
        // We don't consider a page with a '/' a unique url
        // so we will also search for them.
        // Possible injection point:
        // INSERT INTO `equalify`.`meta` (`usage`, `wave_key`) VALUES ('1', 'c');
        $sql.= ' OR `site` = ?';
    
        $params = array($site_url, $site_url . '/');
        $results = self::query($sql, $params, true);
        if($results->num_rows > 0){
            return false;
        }else{
            return true;
        }
    
    }
    
    /**
     * Add Page
     */
    public static function add_page($url, $type, $status, $site){
    
        // SQL
        $sql = 'INSERT INTO `pages` (`url`, `type`, `status`, `site`) VALUES';
        $sql.= '(?, ?, ?, ?)';
        $params = array($url, $type, $status, $site);
        
        // Query
        $result = self::query($sql, $params, false);
    
        //Fallback
        if(!$result)
            throw new Exception('Cannot insert page with values "'.$url.',"'.$url.',"'.$type.',"'.$status.',"'.$site.'"');
        
        // Complete Query
        return $result;
    }
    
    /**
     * Add Pages 
     */
    public static function add_pages($pages_records){
    
        // SQL
        $sql = 'INSERT INTO `pages` (`site`, `url`, `status`, `type`) VALUES';
        
        // Insert Each Record
        $record_count = count($pages_records);
        $record_iteration = 0;
        $params = array();
        foreach ($pages_records as $record){
    
            // SQL
            $sql.= "(?, ?, ?, ?)";
            $params[] = $record['site'];
            $params[] = $record['url'];
            $params[] = $record['status'];
            $params[] = $record['type'];

            if(++$record_iteration != $record_count)
                $sql.= ",";
    
        }
        $sql.= ";";
        
        // Query
        $result = self::query($sql, $params, false);
    
        //Fallback
        if(!$result)
            throw new Exception('Cannot insert page records "'.$pages_records.'"');
    
    }

    /**
     * Delete Pages
     * @param array filters [ array ('name' => $name, 'value' => $value) ]
     */
    public static function delete_pages($filters = []){
    
        // SQL
        $sql = 'DELETE FROM `pages`';
        $params = array();
    
        // Add optional filters
        $filter_count = count($filters);
        if($filter_count > 0){
            $sql.= 'WHERE ';
    
            $filter_iteration = 0;
            foreach ($filters as $filter){
                $sql.= '`'.$filter['name'].'` = ?';
                $params[] = $filter['value'];
                if(++$filter_iteration != $filter_count)
                    $sql.= ' AND ';
        
            }
        }
        $sql.= ';';
    
        // Query
        $result = self::query($sql, $params, false);
    
        // Result
        if(!$result)
            throw new Exception('Cannot delete alert using filters "'.$filters.'"');
    
    }
    
    /**
     * Add Scan
     */
    public static function add_scan($status, $time){
    
        // SQL
        $sql = 'INSERT INTO `scans` (`status`, `time`) VALUES';
        $sql.= '(?, ?)';
        $params = array($status, $time);
        
        // Query
        $result = self::query($sql, $params, false);
    
        //Fallback
        if(!$result)
            throw new Exception('Cannot insert scan with status "'.$status.'" and time "'.$time.'"');
        
        // Complete Query
        return $result;
        
    }

    /**
     * Update Scan Status 
     */
    public static function update_scan_status($scan_id, $new_status){
    
        // SQL
        $sql = 'UPDATE `scans` SET `status` = ? WHERE `id` = ?';
        $params = array($new_status, $scan_id);
    
        // Query
        $result = self::query($sql, $params, false);
    
        // Result
        if(!$result)
            throw new Exception('Cannot update scan id "'.$scan_id.'" to new status "'.$new_status.'"');
    
    }
    
    /**
     * Update Page Scanned Time 
     */
    public static function update_page_scanned_time($url){
    
        // SQL
        $sql = 'UPDATE `pages` SET `scanned` = CURRENT_TIMESTAMP() WHERE `url` = ?';
        $params = array($url);
    
        // Query
        $result = self::query($sql, $params, false);
    
        // Result
        if(!$result)
            throw new Exception('Cannot update scan time for scan with url "'.$url.'"');
    
    }
    
    /**
     * Update Page Site Status 
     */
    public static function update_site_status($site, $new_status){
    
        // SQL
        $sql = 'UPDATE `pages` SET `status` = ? WHERE `site` = ?';
        $params = array($new_status, $site);
    
        // Query
        $result = self::query($sql, $params, false);
    
        // Result
        if(!$result)
            throw new Exception('Cannot update "'.$site.'" site status to "'.$new_status.'"');
    }
    
    /**
     * Update Page Data 
     */
    public static function update_page_data($url, $column, $value){
    
        // SQL
        $sql = 'UPDATE `pages` SET `'.$column.'` = ? WHERE `url` = ?';
        $params = array($value, $url);
    
        // Query
        $result = self::query($sql, $params, false);
    
        // Result
        if(!$result)
            throw new Exception('Cannot update data column "'.$column.'" to "'.$value.'" for page "'.$url.'"');
    
    }
    
    /**
     * Add DB Column
     */
    public static function add_db_column($table, $column_name, $column_type){
    
        // SQL.
        $sql = 'ALTER TABLE `'.$table.'` ';
        $sql.= 'ADD COLUMN '.$column_name.' '.$column_type.';';
        $params = array();
    
        // Query
        $result = self::query($sql, $params, false);
    
        // Result
        if(!$result)
            throw new Exception('Cannot add "'.$column_name.'" to "'.$table.'" with type "'.$column_type.'"');
    
    }
    
    /**
     * Add DB Column
     */
    public static function delete_db_column($table, $column_name){
    
        // SQL.
        $sql = 'ALTER TABLE `'.$table.'` ';
        $sql.= 'DROP COLUMN '.$column_name.';';
        $params = array();
    
        // Query
        $result = self::query($sql, $params, false);
    
        // Result
        if(!$result)
            throw new Exception('Cannot drop "'.$column_name.'" to "'.$table.'"');
    
    }
    
    /**
     * DB Column Exists
     */
    public static function db_column_exists($table, $column_name){
    
        // SQL.
        $sql = 'SELECT '.$column_name.' FROM `'.$table.'` ';
        $params = array();
    
        // Query
        $result = self::query($sql, $params, false);
    
        // Result
        if(!$result){
            return false;   
        }else{
            return true;
        }
    
    }
    
    /**
     * Delete Alerts
     * @param array filters [ array ('name' => $name, 'value' => $value) ]
     */
    public static function delete_alerts($filters = []){
    
        // SQL
        $sql = 'DELETE FROM `alerts`';
        $params = array();
    
        // Add optional filters
        $filter_count = count($filters);
        if($filter_count > 0){
            $sql.= ' WHERE ';
    
            $filter_iteration = 0;
            foreach ($filters as $filter){
                $sql.= '`'.$filter['name'].'` = ?';
                $params[] = $filter['value'];
                if(++$filter_iteration != $filter_count)
                    $sql.= ' AND ';
        
            }
        }
        $sql.= ';';
    
        // Query
        $result = self::query($sql, $params, false);
    
        // Result
        if(!$result)
            throw new Exception('Cannot delete alert using filters "'.$filters.'"');
    
    }
    
    /**
     * Add Alert
     */
    public static function add_alert($source, $url, $site, $integration_uri = NULL, $type = 'error', $message = NULL, $meta = NULL){
        
        // Sanitize items.
        $message = filter_var($message, FILTER_SANITIZE_STRING);
        if(is_array($meta)){
            $meta = filter_var(serialize($meta), FILTER_SANITIZE_STRING);
        }

        // Require certain alert types.
        $allowed_types = array('error', 'warning', 'notice');
        if(!in_array($type, $allowed_types))
            throw new Exception('Alert type, "'.$type.'," is not allowed');

        // Require certain alert sources.
        $allowed_sources = array('system', 'page');
        if(!in_array($source, $allowed_sources))
            throw new Exception('Alert source, "'.$source.'," is not allowed');

        // SQL
        $sql = 'INSERT INTO `alerts` (`source`, `url`, `site`, `integration_uri`, `type`, `message`, `meta`) VALUES';
        $sql.= '(?, ?, ?, ?, ?, ?, ?)';
        $params = array($source, $url, $site, $integration_uri, $type, $message, $meta);
        
        // Query
        $result = self::query($sql, $params, false);
    
        // Fallback
        if(!$result)
            throw new Exception('Cannot insert alert with the variables "'.$source.'", "'.$url.'", "'.$site.'", "'.$integration_uri.'", "'.$type.'", "'.$message.'", and "'.$meta.'"');
        
        // Complete Query
        return $result;
        
    }

    /**
     * Add Meta
     */
    public static function add_meta($meta_name, $meta_value = NULL){
    
        // Serialize meta_value.
        if(is_array($meta_value)){
            $meta_value = serialize($meta_value);
        }
    
        // SQL
        $sql = 'INSERT INTO `meta` (`meta_name`, `meta_value`) VALUES (?, ?)';
        $params = array($meta_name, $meta_value);
        
        // Query
        $result = self::query($sql, $params, false);
    
        // Fallback
        if(!$result)
            throw new Exception('Cannot add meta field "'.$meta_name.'" and value "'.$meta_value.'"');
        
        // Complete Query
        return $result;
    }

    /**
     * Delete Meta
     */
    public static function delete_meta($meta_name){
    
        // SQL
        $sql = 'DELETE FROM `meta` WHERE `meta_name` = ?';
        $params = array($meta_name);
    
        // Query
        $result = self::query($sql, $params, false);
    
        // Result
        if(!$result)
            throw new Exception('Cannot delete alert using filters "'.$filters.'"');
    
        // Complete Query
        return $result;

    }

    /**
     * Update Meta 
     */
    public static function update_meta_value($meta_name, $meta_value){

        // SQL
        $sql = "UPDATE `meta` SET `meta_value` = ? WHERE `meta_name` = ?";
        $params = array($meta_value, $meta_name);

        // Query
        $result = self::query($sql, $params, false);

        // Result
        if(!$result)
            throw new Exception('Cannot update "'.$meta_name.'" with value "'.$meta_value.'"');
    
        // Complete Query
        return $result;

    }

    /**
     * Create Alerts Table
     */
    public static function create_alerts_table(){
    
        // SQL
        $sql = 
            'CREATE TABLE `alerts` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `type` varchar(200) NOT NULL,
                `source` varchar(200) NOT NULL,
                `url` text,
                `site` text,
                `integration_uri` varchar(200) DEFAULT NULL,
                `message` text,
                `meta` text,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;';
        $params = array();
        
        // Query
        $result = self::query($sql, $params, false);
    
        // Fallback
        if(!$result)
            throw new Exception('Error creating table: "'.$result->error.'"');
        
    }

    /**
     * Create Meta Table
     */
    public static function create_meta_table(){
    
        // SQL
        $sql_1 = 
        'CREATE TABLE `meta` (
            `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `meta_name` varchar(191) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT "",
            `meta_value` longtext COLLATE utf8mb4_unicode_520_ci,
            PRIMARY KEY (`meta_id`),
            UNIQUE KEY `option_name` (`meta_name`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
        ';
        $params = array();

        // Query 1
        $result = self::query($sql_1, $params, false);

        // Fallback
        if(!$result)
            throw new Exception('Error creating table: "'.$result->error.'"');

        // Little Forrest is activated here.
        self::add_meta('active_integrations', serialize(['little_forrest']));
    }
    
    /**
     * Create Pages Table
     */
    public static function create_pages_table(){
    
        // SQL 
        $sql = 
            'CREATE TABLE `pages` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `url` text COLLATE utf8mb4_bin NOT NULL,
                `type` varchar(20) COLLATE utf8mb4_bin NOT NULL DEFAULT "static",
                `status` varchar(20) COLLATE utf8mb4_bin NOT NULL DEFAULT "active",
                `site` text COLLATE utf8mb4_bin,
                `scanned` timestamp NULL DEFAULT NULL,
                `little_forrest_wcag_2_1_errors` varchar(20) COLLATE utf8mb4_bin NOT NULL DEFAULT "0", -- Little Forrest is activated here.
                PRIMARY KEY (`id`)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;';
        $params = array();
        
        // Query
        $result = self::query($sql, $params, false);
    
        // Fallback
        if(!$result)
            throw new Exception('Error creating table: "'.$result->error.'"');
        
    }
    
    /**
     * Create Scans Table
     */
    public static function create_scans_table(){
    
        // SQL
        $sql = 
            'CREATE TABLE `scans` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `time` timestamp NULL DEFAULT NULL,
                `status` varchar(20) NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;';
        $params = array();
        
        // Query
        $result = self::query($sql, $params, false);
    
        // Fallback
        if(!$result)
            throw new Exception('Error creating table: "'.$result->error.'"');
        
    }
    
    /**
     * Table Exists
     */
    public static function table_exists($table_name){
    
        // SQL
        $sql = 
            'SELECT 1 from '.$table_name;
        $params = array();
        
        // Query
        $result = self::query($sql, $params, false);
    
        // Results
        if($result !== FALSE){
           return true;
        }else{
            return false;
        }
    
    }

}
