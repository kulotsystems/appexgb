<?php
    require '__init.php';

    if(!class_exists('UserAccount')) {
        class UserAccount extends __init {

            // class variables
            public static $tab_singular         = 'USER ACCOUNT';
            public static $table                = 'lending_users';
            public static $primary_key          = 'CitizenID';
            public static $primary_zero_allowed = true;
            public static $list_order           = 'ORDER BY `CitizenID`';

            public static $params = array(
                'list'  => array(
                    'key'     => 'list_useraccount',
                    'enabled' => true
                ),
                'create' => array(
                    'key'     => 'create_useraccount',
                    'enabled' => true
                ),
                'select' => array(
                    'key'     => 'select_useraccount',
                    'enabled' => true
                ),
                'update' => array(
                    'key'     => 'update_useraccount',
                    'enabled' => true
                ),
                'delete' => array(
                    'key'     => 'delete_useraccount',
                    'enabled' => true
                ),
                'search' => array(
                    'key'     => 'search_useraccount',
                    'enabled' => true
                )
            );


            /****************************************************************************************************
             * UserAccount :: CONSTRUCTOR
             *
             * Initialize the UserAccount object.
             * @access public
             *
             * @param int    $citizen_id - the PhCitizen id
             * @param string $purpose    - the usage of this operation
             * @param bool   $metadata
             */
            public function __construct($citizen_id = 0, $purpose = '', $metadata = true) {
                $this->data = array(
                    'id' => 0
                );

                $citizen_id = intval($citizen_id);
                if(($citizen_id > 0 || self::$primary_zero_allowed) && $purpose != 'for class usage') {
                    $purpose = 'getting UserAccount info' . cascade_purpose($purpose);
                    $GLOBALS['query']  = "SELECT ";
                    $GLOBALS['query'] .= " `UserTypeID`, ";
                    $GLOBALS['query'] .= " `Username`, ";
                    $GLOBALS['query'] .= " `Password`, ";
                    $GLOBALS['query'] .= " `IsActive`, ";
                    $GLOBALS['query'] .= "  DATE_FORMAT(`CreatedAt`, '%M %e, %Y &middot; %h:%i %p') AS CreatedAt, ";
                    $GLOBALS['query'] .= "  DATE_FORMAT(`UpdatedAt`, '%M %e, %Y &middot; %h:%i %p') AS UpdatedAt ";
                    $GLOBALS['query'] .= "FROM ";
                    $GLOBALS['query'] .= " `" . self::$table . "` ";
                    $GLOBALS['query'] .= "WHERE ";
                    $GLOBALS['query'] .= " `" . self::$primary_key . "`=$citizen_id";

                    $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                    if(has_no_db_error($purpose)) {
                        if(mysqli_num_rows($result) > 0) {
                            $row = mysqli_fetch_assoc($result);

                            require INDEX . 'php/models/UserType.php';
                            $user_type = new UserType($row['UserTypeID'], false);

                            if($user_type->get_data('id') > 0) {
                                require INDEX . 'php/models/PhCitizen.php';
                                $citizen = new PhCitizen($citizen_id, $purpose);

                                $password = '';
                                $arr_password = explode('ebpls', $row['Password']);
                                if (sizeof($arr_password) > 1) {
                                    require INDEX . 'php/models/Data.php';
                                    $password = Data::shift($arr_password[1], false);
                                }

                                $this->data = array(
                                    'id'            => $citizen_id,
                                    'citizen'       => $citizen->get_data(),
                                    'user_type'     => $user_type->get_data(),
                                    'username'      => $row['Username'],
                                    'password'      => $password,
                                    'is_active'     => $row['IsActive'],
                                    'user_types'    => $metadata ? UserType::get_all($purpose) : array(),
                                    'date_created'  => $row['CreatedAt'],
                                    'date_updated'  => $row['UpdatedAt']
                                );
                            }
                        }
                        else {
                            $GLOBALS['response']['error'] = get_class($this) .' NOT FOUND ' . $purpose;
                            fin();
                        }
                    }
                }
            }


            /****************************************************************************************************
             * UserAccount :: get_item
             *
             * Get the data to be displayed on items list
             * @access public
             *
             * @return array
             */
            public function get_item() {
                $citizen = $this->get_data('citizen');
                $user_type = $this->get_data('user_type');
                return $this->get_item_helper(
                    $this->get_data('id'),
                    $citizen['avatar'],
                    $citizen['full_name_3'],
                    '(' . $user_type['acronym'] . ') ' . $user_type['title'],
                    $this->get_data('date_updated')
                );
            }


            /****************************************************************************************************
             * UserAccount :: create
             *
             * Create UserAccount item for create.js
             * @access public static
             *
             */
            public static function create() {
                require INDEX . 'php/models/UserType.php';

                // check for first UserType record
                $first_user_type = parent::get_first_record('UserType', 'for creating new user account');
                if($first_user_type->get_data('id') <= 0) {
                    $GLOBALS['response']['success']['message']     = 'UNABLE TO CREATE UserAccount';
                    $GLOBALS['response']['success']['sub_message'] = '<span style="font-size: 1.2em">A UserAccount cannot be created because there is no <span class="text-info">UserType</span> available.</span>';
                }
                else {
                    // check for unprocessed UserAccount
                    if(self::has_unprocessed_record('for creating UserAccount')) {
                        $GLOBALS['response']['success']['message']     = 'UNABLE TO CREATE UserAccount';
                        $GLOBALS['response']['success']['sub_message'] = '<span style="font-size: 1.2em">A new UserAccount cannot be created because there is still a <span class="text-info">UserAccount</span> with <span class="text-danger">[NO CITIZEN]</span> assigned.</span>';
                    }
                    else {
                        require INDEX . "php/models/Data.php";
                        $new_password = mysqli_real_escape_string($GLOBALS['con'], password_hash(CURRENT_TIME, PASSWORD_DEFAULT) . 'ebpls' . Data::shift(''));
                        $GLOBALS['query']  = "INSERT INTO ";
                        $GLOBALS['query'] .= " `" . self::$table . "`";
                        $GLOBALS['query'] .= "  (";
                        $GLOBALS['query'] .= "   `UserTypeID`, ";
                        $GLOBALS['query'] .= "   `CitizenID`, ";
                        $GLOBALS['query'] .= "   `Password`";
                        $GLOBALS['query'] .= "  ) ";
                        $GLOBALS['query'] .= "VALUES";
                        $GLOBALS['query'] .= "  (";
                        $GLOBALS['query'] .= "  " .$first_user_type->get_data('id').", ";
                        $GLOBALS['query'] .= "    0, ";
                        $GLOBALS['query'] .= "    '$new_password'";
                        $GLOBALS['query'] .= " )";
                        mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                        if (has_no_db_error('for creating new UserAccount')) {
                            parent::create_helper(new UserAccount(mysqli_insert_id($GLOBALS['con']), 'for getting newly created UserAccount'));
                        }
                    }
                }
            }


            /****************************************************************************************************
             * UserAccount :: get_form
             *
             * Generate form for UserAccount object
             * @access public static
             *
             * @param array $data     - the item data
             * @param bool  $for_logs - for system logs or not
             *
             * @return array
             */
            public static function get_form($data, $for_logs) {

                /************************************************************************************************
                 * get_form helper :: get_user_type_details
                 * Generate UserType details
                 * @param array $user_type - the UserType data array
                 * @return string
                 */
                function get_user_type_details($user_type) {
                    $h = "<b class=\"text-montserrat\">DESCRIPTION</b>";
                    if($user_type['desc'] == '')
                        $h .= "<p>(No description)</p>";
                    else
                        $h .= "<p>" . $user_type['desc'] . "</p>";
                    $h .= "<br><b class=\"text-montserrat\">SYSTEM ACCESS</b>";
                    if(sizeof($user_type['access']) <= 0)
                        $h .= "<p>(No system access)</p>";
                    $h .= "<ol>";
                    for ($j=0; $j<sizeof($user_type['access']); $j++) {
                        $access = $user_type['access'][$j];
                        if($access['checked'] == 1) {
                            $h .= "<li>";
                            $h .= "<b class=\"text-montserrat text-success\"><span class=\"fa fa-fw " . $access['icon'] . "\"></span> " . $access['title'] . "</b>";
                            if ($access['desc'] != '')
                                $h .= "<p style=\"padding-left: 20px\">" . $access['desc'] . "</p>";
                            $h .= "</li>";
                        }
                    }
                    $h .= "</ol>";
                    return $h;
                }

                /************************************************************************************************
                 * get_form helper :: get_user_type_options
                 * Prepare select options for all UserTypes
                 * @param array $user_types - all UserType data array
                 * @param array $user_type  - the current UserType of this UserAccount
                 * @return array
                 */
                function get_user_type_options($user_types, $user_type) {
                    $arr = array();
                    for($i=0; $i<sizeof($user_types); $i++) {
                        array_push($arr, array(
                            'label'    => "(" . $user_types[$i]['acronym'] . ") " . $user_types[$i]['title'],
                            'value'    => $user_types[$i]['id'],
                            'selected' => $user_types[$i]['id'] == $user_type['id'],
                            'detail'   => get_user_type_details($user_types[$i])
                        ));
                    }
                    return $arr;
                }

                $arr = array(
                    // form_data[0]
                    array(
                        'class' => 'form form-group-attached',
                        'rows'  => array(
                            // form_data[0]['rows'][0]
                            array(
                                array(
                                    'grid'     => 'col-md-6',
                                    'type'     => 'searchbox',
                                    'model'    => 'PhCitizen',
                                    'id'       => 'srch-citizen',
                                    'label'    => 'CITIZEN',
                                    'avatar'   => isset($data['citizen']['avatar']) ? $data['citizen']['avatar'] : '',
                                    'value'    => isset($data['citizen']['full_name_3']) ? $data['citizen']['full_name_3'] : '',
                                    'key'      => isset($data['citizen']['id']) ? $data['citizen']['id'] : 0,
                                    'disabled' => $for_logs
                                ),
                                array(
                                    'grid'     => 'col-md-6',
                                    'type'     => 'toggle',
                                    'id'       => 'tog-isactive',
                                    'label'    => 'IS ACTIVE?',
                                    'value'    => isset($data['is_active']) ? $data['is_active'] : 0
                                )
                            ),

                            // form_data[0]['rows'][1]
                            array(
                                array(
                                    'grid'     => 'col-md-6',
                                    'type'     => 'text',
                                    'id'       => 'txt-username',
                                    'label'    => 'username',
                                    'value'    => isset($data['username']) ? $data['username'] : ''
                                ),
                                array(
                                    'grid'     => 'col-md-6',
                                    'type'     => 'password_toggle',
                                    'id'       => 'ptog-password',
                                    'label'    => 'PASSWORD',
                                    'value'    => isset($data['password']) ? $data['password'] : ''
                                )
                            ),

                            // form_data[0]['rows'][3]
                            array(
                                array(
                                    'grid'     => 'col-md-12',
                                    'type'     => 'select',
                                    'id'       => 'slc-usertype',
                                    'label'    => 'USER TYPE',
                                    'details'  => '#div-details',
                                    'options'  => get_user_type_options($data['user_types'], $data['user_type'])
                                )
                            )
                        )
                    ),

                    // form_data[1] :: div
                    array(
                        'class' => 'div',
                        'rows'  => array(
                            array(
                                array(
                                    'grid'     => 'col-md-12',
                                    'type'     => 'div',
                                    'id'       => 'div-details',
                                    'value'    => '<br>' . get_user_type_details($data['user_type'])
                                )
                            )
                        )
                    )
                );

                return $arr;
            }


            /****************************************************************************************************
             * UserAccount :: update
             *
             * Update UserAccount item for update.js
             * @access public static
             *
             */
            public static function update() {
                $citizen_id = intval($_POST[self::$params['update']['key']]);
                $form_data  = $_POST['data'];

                if($citizen_id == 1 && $GLOBALS['arr_admin_details']['citizen_id'] != 1)
                    $GLOBALS['response']['error'] = 'OPERATION NOT ALLOWED!';
                else {
                    $user_account   = new UserAccount($citizen_id, 'for update');
                    $new_citizen_id = intval($form_data[0]['rows'][0]['srch-citizen']);

                    // check if new citizen id has already a UserAccount associated with it
                    $GLOBALS['query'] = "SELECT `" . self::$primary_key . "` FROM `" . self::$table . "` WHERE `" . self::$primary_key . "`=$new_citizen_id AND `" . self::$primary_key . "`!=$citizen_id";
                    $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                    if (has_no_db_error('for checking if new citizen_id is valid')) {
                        if (mysqli_num_rows($result) > 0) {
                            $GLOBALS['response']['success']['message']     = 'UNABLE TO UPDATE UserAccount';
                            $GLOBALS['response']['success']['sub_message'] = '<span style="font-size: 1.2em">The <span class="text-info">CITIZEN</span> assigned has already an <span class="text-danger">EXISTING USER ACCOUNT</span>.</span>';
                        }
                        else {
                            // check if UserType is valid
                            require INDEX . 'php/models/UserType.php';
                            $user_type_id = intval($form_data[0]['rows'][2]['slc-usertype']);
                            $user_type    = new UserType($user_type_id, 'for checking if UserType is valid', false);

                            // check is username is available
                            $username = mysqli_real_escape_string($GLOBALS['con'], strip_tags(trim($form_data[0]['rows'][1]['txt-username'])));
                            if($username != "") {
                                $GLOBALS['query'] = "SELECT `Username` FROM `" . self::$table . "` WHERE `Username`='$username' AND `" . self::$primary_key . "`!=$citizen_id";
                                $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                                if (has_no_db_error('for checking if username is valid')) {
                                    if (mysqli_num_rows($result) > 0) {
                                        $GLOBALS['response']['success']['message']     = 'UNABLE TO UPDATE UserAccount';
                                        $GLOBALS['response']['success']['sub_message'] = '<span style="font-size: 1.2em">The <span class="text-danger">USERNAME</span> supplied is not available.</span>';
                                        fin();
                                    }
                                }
                            }
                            $is_active = intval($form_data[0]['rows'][0]['tog-isactive']);

                            require INDEX . "php/models/Data.php";
                            $password = mysqli_real_escape_string($GLOBALS['con'], password_hash($form_data[0]['rows'][1]['ptog-password'], PASSWORD_DEFAULT) . 'ebpls' . Data::shift($form_data[0]['rows'][1]['ptog-password']));

                            $GLOBALS['query']  = "UPDATE ";
                            $GLOBALS['query'] .= " `" . self::$table . "` ";
                            $GLOBALS['query'] .= "SET ";
                            $GLOBALS['query'] .= " `" . self::$primary_key . "`=$new_citizen_id, ";
                            $GLOBALS['query'] .= " `UserTypeID`=$user_type_id, ";
                            $GLOBALS['query'] .= " `Username`='$username', ";
                            $GLOBALS['query'] .= " `Password`='$password', ";
                            $GLOBALS['query'] .= " `IsActive`=$is_active ";
                            $GLOBALS['query'] .= "WHERE ";
                            $GLOBALS['query'] .= " `" .  self::$primary_key  . "`=$citizen_id";
                            mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                            if (has_no_db_error('for updating UserAccount')) {
                                parent::update_helper(new UserAccount($new_citizen_id, 'for getting newly updated UserAccount'), $user_account->get_item());
                            }
                        }
                    }
                }
            }


            /****************************************************************************************************
             * UserAccount :: delete
             *
             * Delete UserAccount item for delete.js
             * @access public
             *
             */
            public function delete() {
                $GLOBALS['response']['success']['message'] = 'No code for <code>' . __CLASS__ . ' :: ' . __FUNCTION__ . '()</code> yet.';
            }


            /****************************************************************************************************
             * UserAccount :: search
             *
             * Search through UserAccount records
             * @access public static
             *
             * @param string $q - the search query
             *
             */
            public static function search($q) {
                $q = mysqli_real_escape_string($GLOBALS['con'], $q);

                require INDEX . 'php/models/UserType.php';
                require INDEX . 'php/models/PhCitizen.php';
                $GLOBALS['query']  = "SELECT ";
                $GLOBALS['query'] .= " `".self::$table."`.`".self::$primary_key."` ";
                $GLOBALS['query'] .= "FROM ";
                $GLOBALS['query'] .= " `".self::$table."`, `".UserType::$table."`, `".PhCitizen::$table."` ";
                $GLOBALS['query'] .= "WHERE ";
                $GLOBALS['query'] .= "     `".self::$table."`.`UserTypeID`=`".UserType::$table."`.`".UserType::$primary_key."` ";
                $GLOBALS['query'] .= " AND `".self::$table."`.`CitizenID`=`".PhCitizen::$table."`.`".PhCitizen::$primary_key."` ";
                $GLOBALS['query'] .= " AND ";
                $GLOBALS['query'] .= " ( ";
                $GLOBALS['query'] .= "     `".UserType::$table."`.`".UserType::$primary_key."`=".intval($q);
                $GLOBALS['query'] .= "  OR `".UserType::$table."`.`Acronym` LIKE '%$q%' ";
                $GLOBALS['query'] .= "  OR `".UserType::$table."`.`Title` LIKE '%$q%' ";
                $GLOBALS['query'] .= "  OR `".UserType::$table."`.`Description` LIKE '%$q%' ";
                $GLOBALS['query'] .= "  OR `".PhCitizen::$table."`.`".PhCitizen::$primary_key."`=".intval($q);
                $GLOBALS['query'] .= "  OR `".PhCitizen::$table."`.`FirstName` LIKE '%$q%' ";
                $GLOBALS['query'] .= "  OR `".PhCitizen::$table."`.`MiddleName` LIKE '%$q%' ";
                $GLOBALS['query'] .= "  OR `".PhCitizen::$table."`.`LastName` LIKE '%$q%' ";
                $GLOBALS['query'] .= "  OR CONCAT(`".PhCitizen::$table."`.`Title`, ' ', `".PhCitizen::$table."`.`FirstName`, ' ', `".PhCitizen::$table."`.`LastName`, ' ', `".PhCitizen::$table."`.`NameSuffix`) LIKE '%$q%' ";
                $GLOBALS['query'] .= "  OR CONCAT(`".PhCitizen::$table."`.`Title`, ' ', `".PhCitizen::$table."`.`FirstName`, ' ', `".PhCitizen::$table."`.`MiddleName`, ' ', `".PhCitizen::$table."`.`LastName`, ' ', `".PhCitizen::$table."`.`NameSuffix`) LIKE '%$q%' ";
                $GLOBALS['query'] .= "  OR CONCAT(`".PhCitizen::$table."`.`Title`, ' ', `".PhCitizen::$table."`.`FirstName`, ' ', MID(`".PhCitizen::$table."`.`MiddleName`, 1, 1), '. ', `".PhCitizen::$table."`.`LastName`, ' ', `".PhCitizen::$table."`.`NameSuffix`) LIKE '%$q%' ";
                $GLOBALS['query'] .= "  OR CONCAT(`".PhCitizen::$table."`.`LastName`, ' ', `".PhCitizen::$table."`.`FirstName`, ', ', `".PhCitizen::$table."`.`NameSuffix`) LIKE '%$q%' ";
                $GLOBALS['query'] .= "  OR CONCAT(`".PhCitizen::$table."`.`LastName`, ', ', `".PhCitizen::$table."`.`FirstName`, ', ', `".PhCitizen::$table."`.`NameSuffix`) LIKE '%$q%' ";
                $GLOBALS['query'] .= "  OR CONCAT(`".PhCitizen::$table."`.`LastName`, ', ', `".PhCitizen::$table."`.`FirstName`, ' ', `".PhCitizen::$table."`.`MiddleName`, ', ', `".PhCitizen::$table."`.`NameSuffix`) LIKE '%$q%' ";
                $GLOBALS['query'] .= "  OR CONCAT(`".PhCitizen::$table."`.`LastName`, ', ', `".PhCitizen::$table."`.`FirstName`, ' ', MID(`".PhCitizen::$table."`.`MiddleName`, 1, 1), '.', ', ', `".PhCitizen::$table."`.`NameSuffix`) LIKE '%$q%' ";
                $GLOBALS['query'] .= " ) ";
                $GLOBALS['query'] .= "ORDER BY ";
                $GLOBALS['query'] .= " `".PhCitizen::$table."`.`LastName`, `".PhCitizen::$table."`.`FirstName`, `".PhCitizen::$table."`.`MiddleName` ";
                parent::search_helper(get_class());
            }


            /****************************************************************************************************
             * UserAccount :: has_unprocessed_record
             *
             * Check if there's a UserAccount with [PrimaryKey = 0]
             * @access public static
             *
             * @param string $purpose - the usage of this operation
             *
             * @return bool
             *
             */
            public static function has_unprocessed_record($purpose = '') {
                $purpose = 'for checking unprocessed UserAccount' . cascade_purpose($purpose);
                $GLOBALS['query'] = "SELECT `" . self::$primary_key . "` FROM `" . self::$table . "` WHERE `" . self::$primary_key . "`=0";
                $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                if(has_no_db_error($purpose)) {
                    return (mysqli_num_rows($result) > 0);
                }
            }


            /****************************************************************************************************
             * UserAccount :: exists
             *
             * Check if a citizen is registered as a user
             * @access public static
             *
             * @param int    $citizen_id - the citizen id
             * @param string $purpose    - the usage of this operation
             *
             * @return bool
             *
             */
            public static function exists($citizen_id, $purpose = '') {
                $GLOBALS['query'] = "SELECT `CitizenID` FROM `" . self::$table . "` WHERE `CitizenID`=$citizen_id";
                $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                if(has_no_db_error('for checking if citizen is registered as a user')) {
                    return mysqli_num_rows($result) > 0;
                }
            }
        }

        if(basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
            $object = new UserAccount(0, 'for class usage');
            require '__fin.php';
        }
    }
?>
