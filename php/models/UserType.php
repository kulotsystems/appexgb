<?php
    require '__init.php';

    if(!class_exists('UserType')) {
        class UserType extends __init {

            // class variables
            public static $tab_singular         = 'USER TYPE';
            public static $table                = 'lending_user_types';
            public static $primary_key          = 'ID';
            public static $primary_zero_allowed = false;
            public static $list_order           = 'ORDER BY `Acronym`, `Title`';

            public static $params = array(
                'list'   => array(
                    'key'     => 'list_usertype',
                    'enabled' => true
                ),
                'create' => array(
                    'key'     => 'create_usertype',
                    'enabled' => true
                ),
                'select' => array(
                    'key'     => 'select_usertype',
                    'enabled' => true
                ),
                'update' => array(
                    'key'     => 'update_usertype',
                    'enabled' => true
                ),
                'delete' => array(
                    'key'     => 'delete_usertype',
                    'enabled' => true
                ),
                'search' => array(
                    'key'     => 'search_usertype',
                    'enabled' => true
                )
            );


            /****************************************************************************************************
             * UserType :: CONSTRUCTOR
             *
             * Initialize the UserType object.
             * @access public
             *
             * @param int    $user_type_id - the UserType id
             * @param string $purpose      - the usage of this operation
             * @param bool   $metadata
             */
            public function __construct($user_type_id = 0, $purpose = '', $metadata = true) {
                $this->data = array(
                    'id' => 0
                );

                $user_type_id = intval($user_type_id);
                if(($user_type_id > 0 || self::$primary_zero_allowed) && $purpose != 'for class usage') {
                    $purpose = 'getting UserType info' . cascade_purpose($purpose);
                    $GLOBALS['query']  = "SELECT ";
                    $GLOBALS['query'] .= " `Acronym`, ";
                    $GLOBALS['query'] .= " `Title`, ";
                    $GLOBALS['query'] .= " `Description`, ";
                    $GLOBALS['query'] .= "  DATE_FORMAT(`CreatedAt`, '%M %e, %Y &middot; %h:%i %p') AS CreatedAt, ";
                    $GLOBALS['query'] .= "  DATE_FORMAT(`UpdatedAt`, '%M %e, %Y &middot; %h:%i %p') AS UpdatedAt ";
                    $GLOBALS['query'] .= "FROM ";
                    $GLOBALS['query'] .= " `" . self::$table . "` ";
                    $GLOBALS['query'] .= "WHERE ";
                    $GLOBALS['query'] .= " `" . self::$primary_key . "`=$user_type_id";

                    $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                    if(has_no_db_error($purpose)) {
                        if(mysqli_num_rows($result) > 0) {
                            $row = mysqli_fetch_assoc($result);

                            // get user access
                            $arr_user_access = array();
                            if($metadata) {
                                require INDEX . 'php/models/UserAccess.php';
                                $arr_tabs = UserAccess::get_all();

                                for($i=0; $i<sizeof($arr_tabs); $i++) {
                                    $proceed = false;
                                    if($arr_tabs[$i]['for_devs_only']) {
                                        if($GLOBALS['arr_admin_details']['citizen_id'] == 1)
                                            $proceed = true;
                                    }
                                    else
                                        $proceed = true;

                                    if($proceed) {
                                        $user_access_icon  = $arr_tabs[$i]['tab_icon'];
                                        $user_access_title = $arr_tabs[$i]['tab_text'];
                                        $user_access_desc  = $arr_tabs[$i]['desc'];
                                        $for_devs_only     = ($arr_tabs[$i]['for_devs_only']) ? '1' : '0';
                                        $is_checked        = 0;
                                        $GLOBALS['query']  = "SELECT `Access` FROM `" . self::$table . "` ";
                                        $GLOBALS['query'] .= "WHERE `ID`=$user_type_id";
                                        $result2 = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                                        if (has_no_db_error('for getting access of a UserType')) {
                                            $row2 = mysqli_fetch_assoc($result2);
                                            $arr_access = json_decode($row2['Access']);
                                            if (in_array($user_access_title, $arr_access)) {
                                                $is_checked = 1;
                                            }
                                        }
                                        array_push($arr_user_access, array(
                                            'icon'          => $user_access_icon,
                                            'title'         => $user_access_title,
                                            'desc'          => $user_access_desc,
                                            'checked'       => $is_checked,
                                            'for_devs_only' => $for_devs_only
                                        ));
                                    }
                                }
                            }

                            $this->data = array(
                                'id'           => $user_type_id,
                                'acronym'      => $row['Acronym'],
                                'title'        => $row['Title'],
                                'desc'         => $row['Description'],
                                'access'       => $arr_user_access,
                                'date_created' => $row['CreatedAt'],
                                'date_updated' => $row['UpdatedAt'],
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
             * UserType :: get_item
             *
             * Get the data to be displayed on items list
             * @access public
             *
             * @return array
             */
            public function get_item() {
                return $this->get_item_helper(
                    $this->get_data('id'),
                    '',
                    $this->get_data('acronym'),
                    $this->get_data('title'),
                    $this->get_data('date_updated')
                );
            }


            /****************************************************************************************************
             * UserType :: create
             *
             * Create UserType item for create.js
             * @access public static
             *
             */
            public static function create() {
                $user_type_acronym = '[new_user_type]';
                $GLOBALS['query'] = "INSERT INTO `" . self::$table . "`(`Acronym`) VALUES('$user_type_acronym')";
                mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                if (has_no_db_error('for creating new UserType')) {
                    parent::create_helper(new UserType(mysqli_insert_id($GLOBALS['con']), 'for getting newly created UserType', true));
                }
            }


            /****************************************************************************************************
             * UserType :: get_form
             *
             * Generate form for UserType object
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
                        'class' => 'form form-group-attached',
                        'rows'  => array(
                            // form_data[0]['rows'][0]
                            array(
                                array(
                                    'grid'     => 'col-md-4',
                                    'type'     => 'text',
                                    'id'       => 'txt-acronym',
                                    'label'    => 'ACRONYM',
                                    'value'    => isset($data['acronym']) ? $data['acronym'] : '',
                                    'required' => true
                                ),
                                array(
                                    'grid'     => 'col-md-8',
                                    'type'     => 'text',
                                    'id'       => 'txt-title',
                                    'label'    => 'TITLE',
                                    'value'    => isset($data['title']) ? $data['title'] : '',
                                    'required' => true
                                )
                            ),

                            // form_data[0]['rows'][1]
                            array(
                                array(
                                    'grid'     => 'col-md-12',
                                    'type'     => 'textarea',
                                    'id'       => 'txt-desc',
                                    'label'    => 'DESCRIPTION',
                                    'value'    => isset($data['desc']) ? $data['desc'] : '',
                                    'required' => false
                                )
                            )
                        )
                    ),

                    // form_data[1]
                    array(
                        'class' => 'form form-group-break',
                        'rows'  => array(
                            // form_data[1]['rows'][0]
                            array(
                                array(
                                    'grid'     => 'col-md-12',
                                    'type'     => 'label',
                                    'id'       => 'lbl-system-access',
                                    'value'    => 'SYSTEM ACCESS'
                                )
                            )
                        )
                    )
                );

                // form_data[2]
                if(isset($data['access'])) {
                    $form = array(
                        'class' => 'form form-group-checkbox',
                        'rows' => array(
                            array()
                        )
                    );

                    for($i=0; $i<sizeof($data['access']); $i++) {
                        $access = $data['access'][$i];
                        array_push($form['rows'][0], array(
                            'grid'        => 'col-md-12',
                            'type'        => 'checkbox',
                            'id'          => implode('_', explode(' ', $access['title'])),
                            'label'       => "<div class='bold text-montserrat text-uppercase text-success'><span class='fa fa-fw " . $access['icon'] . "'></span> " . $access['title'] . "</div>",
                            'description' => "<div style='padding-left: 20px'>" . ($access['for_devs_only'] == 1 ? "<b><i>For devs only: </i></b>" . $access['desc'] : $access['desc']) . "</div>",
                            'value'       => $access['checked']
                        ));
                    }
                    array_push($arr, $form);
                }

                return $arr;
            }


            /****************************************************************************************************
             * UserType :: update
             *
             * Update UserType item for update.js
             * @access public static
             *
             */
            public static function update() {
                $user_type_id = intval($_POST[self::$params['update']['key']]);
                $user_type = new UserType($user_type_id, 'for update');
                $form_data = $_POST['data'];

                $acronym     = mysqli_real_escape_string($GLOBALS['con'], strip_tags(trim($form_data[0]['rows'][0]['txt-acronym'])));
                $title       = mysqli_real_escape_string($GLOBALS['con'], strip_tags(trim($form_data[0]['rows'][0]['txt-title'])));
                $description = mysqli_real_escape_string($GLOBALS['con'], strip_tags(trim($form_data[0]['rows'][1]['txt-desc'])));
                $arr_access  = array();
                foreach($form_data[2]['rows'][0] as $id => $val) {
                    if(intval($val) == 1)
                        array_push($arr_access, implode(' ', explode('_', $id)));
                }
                array_walk($arr_access, 'utf8encode');

                // update UserType data
                $GLOBALS['query']  = "UPDATE `" . self::$table . "` ";
                $GLOBALS['query'] .= "SET ";
                $GLOBALS['query'] .= " `Acronym`='$acronym', ";
                $GLOBALS['query'] .= " `Title`='$title', ";
                $GLOBALS['query'] .= " `Description`='$description', ";
                $GLOBALS['query'] .= " `Access`='" . mysqli_real_escape_string($GLOBALS['con'], json_encode($arr_access)) . "' ";
                $GLOBALS['query'] .= "WHERE ";
                $GLOBALS['query'] .= " `" . self::$primary_key . "`=$user_type_id";
                mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                if (has_no_db_error('for updating UserType data')) {
                    parent::update_helper(new UserType($user_type_id, 'for getting newly updated UserType', true), $user_type->get_item());
                }
            }


            /****************************************************************************************************
             * UserType :: delete
             *
             * Delete UserType item for delete.js
             * @access public
             *
             */
            public function delete() {
                if($this->has_user_account()) {
                    $GLOBALS['response']['success']['message']     = 'UNABLE TO DELETE';
                    $GLOBALS['response']['success']['sub_message'] = '<span  style="font-size: 1.2em">This <b class="text-info">UserType</b> cannot be deleted because there is at least one <b class="text-danger">UserAccount</b> data associated with it.</span>';
                }
                else
                    parent::delete_helper($this);
            }


            /****************************************************************************************************
             * UserType :: search
             *
             * Search through UserType records
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
                $GLOBALS['query'] .= " OR `Acronym` LIKE '%$q%' ";
                $GLOBALS['query'] .= " OR `Title` LIKE '%$q%' ";
                $GLOBALS['query'] .= " OR `Description` LIKE '%$q%' ";
                $GLOBALS['query'] .= "ORDER BY ";
                $GLOBALS['query'] .= " `Acronym`, `Title`, `Description` ";
                parent::search_helper(get_class());
            }


            /****************************************************************************************************
             * UserType :: get_all
             *
             * Get all UserTypes
             * @access public static
             *
             * @param string $purpose - the usage of this operation
             * @param bool   get_data - to get data of individual UserType or not
             *
             * @return array
             *
             */
            public static function get_all($purpose = '', $get_data = true) {
                $purpose = 'for getting all usertypes' . cascade_purpose($purpose);
                $arr = array();
                $GLOBALS['query'] = "SELECT `ID` FROM `" . self::$table . "` WHERE 1 ORDER BY `Acronym`, `Title`";
                $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                if (has_no_db_error($purpose)) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $user_type = new UserType($row['ID'], 'for '. $purpose);
                        if($get_data)
                            array_push($arr, $user_type->get_data());
                        else
                            array_push($arr, $user_type);
                    }
                }
                else
                    fin();

                return $arr;
            }


            /****************************************************************************************************
             * UserType :: has_user_account
             *
             * Check if a UserType is associated with a UserAccount record
             * @access public
             *
             * @param string $purpose - the usage of this operation
             *
             * @return bool
             *
             */
            public function has_user_account($purpose = '') {
                $purpose = 'for checking if UserType has associated UserAccount' . cascade_purpose($purpose);

                require INDEX . 'php/models/UserAccount.php';
                $GLOBALS['query'] = "SELECT `" . UserAccount::$primary_key . "` FROM `" . UserAccount::$table . "` WHERE `UserTypeID`=" . $this->get_data('id');
                $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                if(has_no_db_error($purpose)) {
                    return mysqli_num_rows($result) > 0;
                }
            }
        }

        if(basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
            $object = new UserType(0, 'for class usage');
            require '__fin.php';
        }
    }
?>
