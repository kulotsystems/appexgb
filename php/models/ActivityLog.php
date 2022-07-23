<?php
    require '__init.php';

    if(!class_exists('ActivityLog')) {
        class ActivityLog extends __init {

            // class variables
            public static $tab_singular         = 'ACTIVITY LOG';
            public static $table                = 'lending_activity_logs';
            public static $primary_key          = 'ID';
            public static $primary_zero_allowed = false;
            public static $list_order           = 'ORDER BY `ID` DESC';

            public static $params = array(
                'list'   => array(
                    'key'     => 'list_activitylog',
                    'enabled' => true
                ),
                'select' => array(
                    'key'     => 'select_activitylog',
                    'enabled' => true
                ),
                'search' => array(
                    'key'     => 'search_activitylog',
                    'enabled' => true
                )
            );


            /****************************************************************************************************
             * ActivityLog :: CONSTRUCTOR
             *
             * Initialize the ActivityLog object.
             * @access public
             *
             * @param int    $activity_log_id - the ActivityLog id
             * @param string $purpose         - the usage of this operation
             * @param bool   $metadata
             */
            public function __construct($activity_log_id = 0, $purpose = '', $metadata = true) {
                $this->data = array(
                    'id' => 0,
                );

                $activity_log_id = intval($activity_log_id);
                if(($activity_log_id > 0 || self::$primary_zero_allowed) && $purpose != 'for class usage') {
                    $purpose = 'getting ActivityLog info' . cascade_purpose($purpose);
                    $GLOBALS['query']  = "SELECT ";
                    $GLOBALS['query'] .= " `User`, ";
                    $GLOBALS['query'] .= " `Action`, ";
                    $GLOBALS['query'] .= " `Model`, ";
                    $GLOBALS['query'] .= " `Item`, ";
                    $GLOBALS['query'] .= " `ItemData`, ";
                    $GLOBALS['query'] .= "  DATE_FORMAT(`CreatedAt`, '%M %e, %Y &middot; %h:%i %p') AS CreatedAt, ";
                    $GLOBALS['query'] .= "  DATE_FORMAT(`UpdatedAt`, '%M %e, %Y &middot; %h:%i %p') AS UpdatedAt ";
                    $GLOBALS['query'] .= "FROM ";
                    $GLOBALS['query'] .= " `" . self::$table . "` ";
                    $GLOBALS['query'] .= "WHERE ";
                    $GLOBALS['query'] .= " `" . self::$primary_key . "`=$activity_log_id";
                    $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                    if (has_no_db_error($purpose)) {
                        if (mysqli_num_rows($result) > 0) {
                            $row = mysqli_fetch_assoc($result);

                            $model = $row['Model'];
                            $model_tab = $model;
                            $model_exists = false;
                            if(file_exists(INDEX . 'php/models/' . $model . '.php')) {
                                require INDEX . 'php/models/' . $model . '.php';
                                $model_tab = $model::$tab_singular;
                                $model_exists = true;
                            }

                            $action = $row['Action'];
                            $past   = $action . 'D';
                            $color  = 'default';
                            if($action == 'CREATE')
                                $color = 'success';
                            else if($action == 'UPDATE')
                                $color = 'primary';
                            else if($action == 'DELETE')
                                $color = 'danger';

                            $user      = json_decode($row['User'], true);
                            $item      = json_decode($row['Item'], true);
                            $item_data = array();
                            if($metadata) {
                                $item_data = json_decode($row['ItemData'], true);

                                // normalize avatars
                                if(isset($user['item_avatar']))
                                    $user['item_avatar']            = __init::normalize_avatar($user['item_avatar']);
                                if(isset($item['item_avatar']))
                                    $item['item_avatar']            = __init::normalize_avatar($item['item_avatar']);
                                if(isset($item_data['avatar']))
                                    $item_data['avatar']            = __init::normalize_avatar($item_data['avatar']);
                                if(isset($item_data['citizen']['avatar']))
                                    $item_data['citizen']['avatar'] = __init::normalize_avatar($item_data['citizen']['avatar']);
                            }
                            $this->data = array(
                                'id'           => $activity_log_id,
                                'user'         => $user,
                                'action'       => $row['Action'],
                                'action_past'  => $past,
                                'color'        => $color,
                                'model'        => $row['Model'],
                                'model_tab'    => $model_tab,
                                'model_exists' => $model_exists,
                                'item'         => $item,
                                'item_data'    => $item_data,
                                'date_created' => $row['CreatedAt'],
                                'date_updated' => $row['UpdatedAt']
                            );
                        }
                        else {
                            $GLOBALS['response']['error'] = get_class($this) .' NOT FOUND ' . $purpose;
                            fin();
                        }
                    }
                    else
                        fin();
                }
            }


            /****************************************************************************************************
             * ActivityLog :: get_item
             *
             * Get the data to be displayed on items list
             * @access public
             *
             * @return array
             */
            public function get_item() {
                $user = $this->get_data('user');
                $item = $this->get_data('item');

                return $this->get_item_helper(
                    $this->get_data('id'),
                    $user['item_avatar'],
                    '<span class="activity-action text-' . $this->get_data('color') . '">' .  $this->get_data('action_past')  . ' <span class="activity-model">' . $this->get_data('model_tab') . '</span></span>',
                    $item['item_maintitle'],
                    $this->get_data('date_created')
                );
            }


            /****************************************************************************************************
             * ActivityLog :: append
             *
             * Append record to system log
             * @access public static
             *
             * @param array $arr - the activity log data
             *
             */
            public static function append($arr) {
                require INDEX . 'php/models/UserAccount.php';
                $user = (new UserAccount($GLOBALS['arr_admin_details']['citizen_id'], 'for appending activity log'))->get_item();

                array_walk($user, 'utf8encode');
                array_walk($arr['item'], 'utf8encode');
                array_walk($arr['item_data'], 'utf8encode');
                if($arr['model'] == 'UserAccount') {
                    if(isset($arr['item_data']['password'])) {
                        $arr['item_data']['password'] = '****************';
                    }
                }
                $GLOBALS['query'] = "INSERT INTO `" . self::$table . "` ";
                $GLOBALS['query'] .= " (`User`, `Action`, `Model`, `Item`, `ItemData`) ";
                $GLOBALS['query'] .= "VALUES(";
                $GLOBALS['query'] .= "'" . mysqli_real_escape_string($GLOBALS['con'], json_encode($user)) . "', ";
                $GLOBALS['query'] .= "'" . mysqli_real_escape_string($GLOBALS['con'], $arr['action']) . "', ";
                $GLOBALS['query'] .= "'" . mysqli_real_escape_string($GLOBALS['con'], $arr['model']) . "', ";
                $GLOBALS['query'] .= "'" . mysqli_real_escape_string($GLOBALS['con'], json_encode($arr['item'])) . "', ";
                $GLOBALS['query'] .= "'" . mysqli_real_escape_string($GLOBALS['con'], json_encode($arr['item_data'])) . "'";
                $GLOBALS['query'] .= ")";

                mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                has_no_db_error('for appending activity log [' . $arr['action'] . ' at ' . $arr['model'] . ']');
            }


            /****************************************************************************************************
             * ActivityLog :: get_form
             *
             * Generate form for ActivityLog object
             * @access public static
             *
             * @param array $data     - the item data
             * @param bool  $for_logs - for system logs or not
             *
             * @return array
             */
            public static function get_form($data, $for_logs) {
                $arr = array(
                    // form_data[0]
                    array(
                        'class' => 'div',
                        'rows'  => array(
                            array(
                                array(
                                    'grid'     => 'col-md-6',
                                    'type'     => 'div',
                                    'id'       => 'div-item',
                                    'value'    => "<p class='text-montserrat text-bold no-margin text-uppercase'>This " . $data['model_tab'] . " was " . $data['action_past'] . ":</p>" . get_item_html($data['item'])
                                ),
                                array(
                                    'grid'     => 'col-md-6',
                                    'type'     => 'div',
                                    'id'       => 'div-item',
                                    'value'    => "<p class='text-montserrat text-bold no-margin text-uppercase'>By the following user:</p>" . get_item_html($data['user'])
                                )
                            ),

                            array(
                                array(
                                    'grid'     => 'col-md-12',
                                    'type'     => 'div',
                                    'id'       => 'div-item',
                                    'value'    => "<br><p class='text-montserrat text-bold no-margin text-uppercase'>" . $data['model_tab'] . " data " . ($data['action'] == 'DELETE' ? 'before' : 'after') . " this operation:</p>"
                                )
                            )
                        )
                    )
                );

                if($data['model_exists']) {
                    require INDEX . 'php/models/' . $data['model'] . '.php';
                    if($data['item_data'] != null) {
                        $forms = $data['model']::get_form($data['item_data'], true);
                        foreach ($forms as $form) {
                            array_push($arr, $form);
                        }
                    }
                }

                return $arr;
            }

            /****************************************************************************************************
             * ActivityLog :: search
             *
             * Search through ActivityLog records
             * @access public static
             *
             * @param string $q - the search query
             *
             */
            public static function search($q) {
                $q = mysqli_real_escape_string($GLOBALS['con'], $q);
                $GLOBALS['query']  = "SELECT ";
                $GLOBALS['query'] .= " `" . self::$primary_key . "` ";
                $GLOBALS['query'] .= "FROM ";
                $GLOBALS['query'] .= " `" . self::$table . "` ";
                $GLOBALS['query'] .= "WHERE ";
                $GLOBALS['query'] .= "   `" . self::$primary_key . "`=" . intval($q) . " ";
                $GLOBALS['query'] .= " OR `User` LIKE '%$q%' ";
                $GLOBALS['query'] .= " OR `Action` LIKE '%$q%' ";
                $GLOBALS['query'] .= " OR `Item` LIKE '%$q%' ";
                $GLOBALS['query'] .= " OR `ItemData` LIKE '%$q%' ";
                $GLOBALS['query'] .= " OR `CreatedAt` LIKE '%$q%' ";
                $GLOBALS['query'] .= self::$list_order . " ";
                parent::search_helper(get_class());
            }
        }

        if(basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
            $object = new ActivityLog(0, 'for class usage');
            require '__fin.php';
        }
    }

?>
