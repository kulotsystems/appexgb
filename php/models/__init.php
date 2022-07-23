<?php

    if(!defined('INDEX')) {
        define('INDEX', '../../');

        session_start();
        $page     = '';
        $query    = '';
        $response = array(
            'error'   => '',
            'success' => array(
                'message'     => '',
                'sub_message' => '',
                'data'        => ''
            )
        );

        require INDEX . "php/inc/APP.php";
        require INDEX . 'php/misc.cookies.php';
        require INDEX . 'php/db/open.php';


        /****************************************************************************************************
         * __init :: has_no_db_error
         *
         * Check for sql error in the most recent query
         * of a mysqli object
         * @param string $str_activity    - action description of the query
         * @param string $source_location - source code location where has_no_db_error() was invoked
         *
         *
         * @return boolean
         */
        function has_no_db_error($str_activity = '', $source_location = '') {
            $error_tag = '';
            $error_msg = '';
            if(mysqli_error($GLOBALS['con'])) {
                $error_tag = 'DEFAULT';
                $error_msg = mysqli_error($GLOBALS['con']);
            }
            if($error_tag != '') {

                // get the first parameter of the request
                $request = '&dollar;_';
                $first_param = '';
                foreach($_POST as $key => $val) {
                    $request .= 'POST';
                    $first_param = $key;
                    break;
                }
                foreach($_GET as $key => $val) {
                    $request .= 'GET';
                    $first_param = $key;
                    break;
                }

                // configure the response
                $GLOBALS['response']['success']['message']     = "<b class='monospace'>" . $request . "['" . implode(' ', explode('_', $first_param)) . "']</b>";
                $GLOBALS['response']['success']['sub_message'] = "<span style='font-size: 1.2em'>" . $error_tag . " DB CONNECTION ERROR ";
                if($str_activity != '')
                    $GLOBALS['response']['success']['sub_message'] .= $str_activity;
                if($source_location != '')
                    $GLOBALS['response']['success']['sub_message'] .= ' @ ' . $source_location;
                $GLOBALS['response']['success']['sub_message'] .= ".</span><br><br><span class='monospace'>" . "<span class='text-primary'>" . $GLOBALS['query'] . "</span><br><span class='text-danger'>" . $error_msg . "</span></span>";
                return false;

            }
            else
                return true;
        }


        /****************************************************************************************************
         * __init :: cascade_purpose
         *
         * Manage cascading $purpose to be passed on has_no_db_error()
         *
         * @param string $purpose - the purpose to append
         *
         * @return string
         */
        function cascade_purpose($purpose = '') {
            $str = '';
            if($purpose != '') {
                $str = ' upon ' . ltrim($purpose, 'for ');
            }
            return $str;
        }


        /****************************************************************************************************
         * __init :: utf8encode
         *
         * utf8_encode array elements in order to properly
         * json_encode strings with spanish characters such as ñ
         *
         * @param object $value - object value
         * @param string $key   - array key
         *
         * USAGE SAMPLE: array_walk($YOUR_ARRAY_VARIABLE, 'utf8encode');
         */
        function utf8encode(&$value, $key) {
            if(is_array($value)) {
                array_walk($value, 'utf8encode');
            }
            else {
                if(is_string($key)) {
                    if(!mb_check_encoding($key, 'UTF-8'))
                        $key = utf8_encode($key);
                }
                if(is_string($value)) {
                    if(!mb_check_encoding($value, 'UTF-8'))
                        $value = utf8_encode($value);
                }
            }
        }


        /****************************************************************************************************
         * __init :: get_item_html
         *
         * Perform backend version of ListItem.prototype.getHTML (system/__init.js)
         *
         * @param array $arr - the item data array
         *
         * @return string
         */
        function get_item_html($arr) {
            $h = "<div class='card social-card active share no-margin no-border w-100'>";
                $h .= "<div class='card-header clearfix no-border no-border-radius overflow-hidden'>";
                    if($arr['item_avatar'] != '') {
                        $h .= "<div class='item-avatar user-pic'>";
                            $h .= "<img alt='(img)' width='33' height='33' src='" . $arr['item_avatar'] . "'>";
                        $h .= "</div>";
                    }
                    $h .= "<h5 class='no-wrap text-montserrat'>";
                        $h .= "<span class='main-title'>" . $arr['item_maintitle'] . "</span>";
                    $h .= "</h5>";
                    if($arr['item_subtitle'] == '')
                        $arr['item_subtitle'] = '&nbsp;';
                    $h .= "<h6 class='no-wrap sub-title'>" . $arr['item_subtitle'] . "</h6>";
                    $h .= "<div class='icon-title label-hidden-bottom-right item-date'>" . $arr['item_update_date'] . "</div>";
                $h .= "</div>";
            $h .= "</div>";

            return $h;
        }


        /****************************************************************************************************
         * __init :: fin
         *
         * Force exit
         *
         */
        function fin() {
            require INDEX . 'php/ajax/__fin.php';
            exit();
        }


        require INDEX . 'php/admin.user_verifier.php';
        /*******************************************************************************************************/

        class __init {

            // class variables
            protected $data       = array();
            public static $params = array(
                'upload' => array(
                    'key'     => 'upload_file',
                    'enabled' => true
                )
            );
            public static $DAY_TOTAL_SECONDS = 86400;


            /****************************************************************************************************
             * __init :: get_data
             *
             * Get $data[$key] of an object or the entire $data if there's no specified $key.
             * @access public
             *
             * @param string $key - the array key
             *
             * @return array | string
             */
            public function get_data($key = '') {
                if($key != '') {
                    if (isset($this->data[$key]))
                        return $this->data[$key];
                    else
                        return '';
                }
                else
                    return $this->data;
            }


            /****************************************************************************************************
             * __init :: set_data
             *
             * Set $data[$key] of an object
             * @access public
             *
             * @param string $key - the array key
             * @param object $value - the value to be set
             *
             */
            public function set_data($key = '', $value = '') {
                if($key != '') {
                    if(isset($this->data[$key]))
                        $this->data[$key] = $value;
                }
            }


            /****************************************************************************************************
             * __init :: get_item_helper
             *
             * Generate the data to be displayed on items list
             * @access protected
             *
             * @param int    $id          - the item id
             * @param string $avatar      - the item photo
             * @param string $maintitle   - the item heading
             * @param string $subtitle    - the item subheading
             * @param string $update_date - the item update date
             * @param string $searchtitle - the main title for searched item
             *
             * @return array
             */
            protected function get_item_helper($id, $avatar, $maintitle, $subtitle, $update_date, $searchtitle = '') {
                return array(
                    'item_id'          => $id,
                    'item_avatar'      => $avatar,
                    'item_maintitle'   => $maintitle,
                    'item_subtitle'    => $subtitle,
                    'item_update_date' => $update_date,
                    'item_searchtitle' => $searchtitle
                );
            }


            /****************************************************************************************************
             * __init :: _list
             *
             * Get all items for list.js
             * @access public static
             *
             * @param object $obj - an empty instance of the child class
             *
             */
            public static function _list($obj) {
                // class variables
                $table       = $obj::$table;
                $primary_key = $obj::$primary_key;
                $list_order  = $obj::$list_order;

                // get all records
                $GLOBALS['query'] = "SELECT `$primary_key` FROM `$table` WHERE 1 " . $list_order;
                $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                if(has_no_db_error('for getting all ' . get_class($obj) . ' records')) {
                    $records = mysqli_fetch_all($result);
                    $total   = sizeof($records);
                    $items   = array();
                    $is_first_record_reached = false;
                    $is_last_record_reached  = false;
                    if($total > 0) {
                        // get the posted variables
                        $mode       = strtolower(trim($_POST[$obj::$params['list']['key']]));
                        $limit      = intval($_POST['limit']);
                        $first_item = intval($_POST['first_item']);
                        $last_item  = intval($_POST['last_item']);
                        $jump_to    = strtolower(trim($_POST['jump_to']));
                        if($jump_to == 'first') {
                            $first_item = $records[0][0];
                            $last_item  = -1;
                        }
                        else if($jump_to == 'last') {
                            $first_item = $records[$total-1][0];
                            $last_item  = -1;
                        }

                        // determine the array index of $first_item and $last_item
                        $first_index = intval(array_search(array($first_item), $records));
                        $last_index  = intval(array_search(array($last_item), $records));

                        // determine the $start and $end index depending on $mode
                        $start = 0;
                        $end   = 0;
                        if($mode == 'refresh') {                    // on refresh
                            $start = $first_index;
                            $end   = $last_index;
                        }
                        else if($mode == 'prepend') {               // on subsequent prepend
                            $start = $first_index - $limit;
                            $end   = $first_index - 1;
                        }
                        else if($mode == 'append') {
                            if($first_item < 0 || $last_item < 0) { // on first append
                                $start = ($first_item < 0) ? 0 : $first_index - 1;
                                $end   = $start + $limit - 1;

                                // ensure to fill in the limit
                                if($last_item < 0) {
                                    if($end >= $total-1)
                                        $start = ($total - 1 - $limit) + 1;
                                }
                            }
                            else {                                  // on subsequent append
                                $start = $last_index + 1;
                                $end   = $last_index + $limit;
                            }
                        }

                        // modify $start and $end to avoid 'index_out_of_bounds' error
                        if($start <= 0) {
                            $start = 0;
                            $is_first_record_reached = true;
                        }
                        if($end >= $total-1) {
                            $end = $total-1;
                            $is_last_record_reached = true;
                        }


                        // loop through $records and get the items
                        for($i=$start; $i<=$end; $i++) {
                            if($records[$i][0]<=0 && !$obj::$primary_zero_allowed)
                                continue;
                            array_push($items, array(
                                'item'  => (new $obj($records[$i][0], 'for listing ' . get_class($obj), false))->get_item(),
                                'index' => $i
                            ));
                        }
                    }
                    $GLOBALS['response']['success']['data'] = array(
                        'items' => $items,
                        'total' => $total,
                        'is_first_record_reached' => $is_first_record_reached,
                        'is_last_record_reached'  => $is_last_record_reached
                    );
                }
            }


            /****************************************************************************************************
             * __init :: create_helper
             *
             * Assist in creation of an object for create.js
             * @access protected static
             *
             * @param object $obj - an instance of the child class
             *
             */
            protected static function create_helper($obj) {
                $GLOBALS['response']['success']['data'] = $obj->get_data('id');

                // append to system log (CREATE)
                require INDEX .'php/models/ActivityLog.php';
                ActivityLog::append(array(
                    'action'    => 'CREATE',
                    'model'     => get_class($obj),
                    'item'      => $obj->get_item(),
                    'item_data' => $obj->get_data()
                ));
            }


            /****************************************************************************************************
             * __init :: select
             *
             * Get item inputs for select.js
             * @access public
             *
             */
            public function select() {
                // Release|Posting :: Update 'collection' property
                // require INDEX . 'php/models/Release.php';
                require INDEX . 'php/models/Posting.php';
                // if($this::$table == Release::$table)
                    // $this->set_data('collection', $this->get_collection_data());
                if($this::$table == Posting::$table)
                    $this->set_data('collection', $this->get_collection_data());

                $GLOBALS['response']['success']['data'] = array(
                    'counter'   => intval($_POST['counter']),
                    'item_id'   => $this->get_data('id'),
                    'item_data' => $this::get_form($this->get_data(), false)
                );
            }


            /****************************************************************************************************
             * __init :: update_helper
             *
             * Assist in updating an object for update.js
             * @access protected static
             *
             * @param object $obj      - an instance of the child class
             * @param array $prev_item - the pervious item data of $obj (for system log)
             *
             */
            protected static function update_helper($obj, $prev_item) {
                $GLOBALS['response']['success']['data'] = $obj->get_item();

                // append to system log (UPDATE)
                require INDEX . 'php/models/ActivityLog.php';
                ActivityLog::append(array(
                    'action'    => 'UPDATE',
                    'model'     => get_class($obj),
                    'item'      => $prev_item,
                    'item_data' => $obj->get_data()
                ));
            }


            /****************************************************************************************************
             * __init :: delete_helper
             *
             * Assist in deleting an object for remove.js
             * @access protected
             *
             * @param object $obj - an instance of the child class
             *
             */
            protected function delete_helper($obj) {
                $GLOBALS['query'] = "DELETE FROM `" . $obj::$table . "` WHERE `" . $obj::$primary_key ."`=" . $obj->get_data('id');
                mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                if(has_no_db_error('for deleting ' . get_class($obj))) {
                    $GLOBALS['response']['success']['data'] = '1';

                    // append to system log (DELETE)
                    require INDEX . 'php/models/ActivityLog.php';
                    ActivityLog::append(array(
                        'action'    => 'DELETE',
                        'model'     => get_class($obj),
                        'item'      => $obj->get_item(),
                        'item_data' => $obj->get_data()
                    ));
                }
            }


            /****************************************************************************************************
             * __init :: search_helper
             *
             * Assist in searching an object for search.js
             * @access protected
             *
             * @param string $Class - the class name of the child class
             *
             */
            protected static function search_helper($Class) {
                $GLOBALS['query'] .= "LIMIT 100";
                $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                if(has_no_db_error('for searching ' . get_class())) {
                    $arr = array();
                    while($row = mysqli_fetch_assoc($result)) {
                        array_push($arr, (new $Class($row[$Class::$primary_key], 'for SEARCH', false))->get_item());
                    }
                    $GLOBALS['response']['success']['data'] = $arr;
                }
            }


            /****************************************************************************************************
             * UserType :: get_first_record_helper
             *
             * Get first Record
             * @access protected
             *
             * @param string $Class   - the class name of the child class
             * @param string $purpose - the usage of this operation
             *
             * @return Object
             *
             */
            protected static function get_first_record($Class, $purpose) {
                $obj     = new $Class(0, 'for class usage');
                $purpose = "for getting first $Class" . cascade_purpose($purpose);
                $GLOBALS['query'] = "SELECT `" . $Class::$primary_key . "` FROM `" . $Class::$table . "` WHERE 1 ORDER BY " .  $Class::$primary_key . " LIMIT 1";
                $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                if(has_no_db_error($purpose)) {
                    while($row = mysqli_fetch_assoc($result)) {
                        $obj = new $Class($row[$Class::$primary_key], $purpose);
                    }
                }
                return $obj;
            }


            /****************************************************************************************************
             * UserType :: get_next_primary_key
             *
             * Get next primary key
             * @access public static
             *
             * @param string $Class   - the class name of the child class
             * @param string $purpose - the usage of this operation
             *
             * @return string
             *
             */
            public static function get_next_primary_key($Class, $purpose) {
                $key = 0;
                $purpose = "for getting next `" . $Class::$table . "`.`" . $Class::$primary_key . "`" . cascade_purpose($purpose);
                $GLOBALS['query'] = "SELECT `" . $Class::$primary_key . "` FROM `" . $Class::$table . "` WHERE 1 ORDER BY `" . $Class::$primary_key . "` DESC LIMIT 1";
                $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                if(has_no_db_error($purpose)) {
                    while($row = mysqli_fetch_assoc($result)) {
                        $key = $row[$Class::$primary_key];
                        break;
                    }
                }
                return str_pad(($key + 1), 10, '0', STR_PAD_LEFT);
            }


            /****************************************************************************************************
             * __init :: get_enum_values
             *
             * Get Field Enum values
             * @access public static
             *
             * @param string $table - the table name
             * @param string $field - the field name
             *
             * @return array
             */
            public static function get_enum_values($table, $field) {
                $arr = array();
                $GLOBALS['query']  = "SELECT ";
                $GLOBALS['query'] .= " COLUMN_TYPE ";
                $GLOBALS['query'] .= "FROM ";
                $GLOBALS['query'] .= " information_schema.`COLUMNS` ";
                $GLOBALS['query'] .= "WHERE ";
                $GLOBALS['query'] .= "      TABLE_NAME='$table' ";
                $GLOBALS['query'] .= "  AND COLUMN_NAME='$field' ";
                $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                if(has_no_db_error("for getting `$table`.`$field` enum values")) {
                    while($row = mysqli_fetch_assoc($result)) {
                        $type = ''.$row['COLUMN_TYPE'].'';
                        if(strpos($type, 'enum(') !== false)
                            $arr = explode(',', rtrim(ltrim( str_replace('"', '', str_replace("'", "", $type)), 'enum('), ')'));
                    }
                    $GLOBALS['response']['success']['data'] = $arr;
                }
                return $arr;
            }

            /****************************************************************************************************
             * __init :: normalize_avatar
             *
             * Normalize avatar url
             * @access public static
             *
             * @param string $avatar_url - the avatar url
             *
             * @return array
             */
            public static function normalize_avatar($avatar_url) {
                $new_avatar_url = '';
                if($avatar_url != null) {
                    if($avatar_url != '') {
                        $arr_url  = explode('/', $avatar_url);
                        $filename = $arr_url[sizeof($arr_url)-1];
                        if (file_exists(CITIZEN_AVATAR_DIR . $filename))
                            $new_avatar_url = CITIZEN_AVATAR_URL . $filename;

                    }
                }
                return $new_avatar_url;
            }
        }

        if(basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
            $object = new __init();
            require '__fin.php';
        }
    }

?>
