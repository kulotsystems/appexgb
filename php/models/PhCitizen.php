<?php
    require '__init.php';

    if(!class_exists('PhCitizen')) {
        class PhCitizen extends __init {

            // class variables
            public static $tab_singular         = 'CITIZEN';
            public static $table                = 'lending_ph_citizens';
            public static $primary_key          = 'ID';
            public static $foreign_key          = 'CitizenID';
            public static $primary_zero_allowed = false;
            public static $list_order           = 'ORDER BY `ID` DESC';

            public static $params = array(
                'list'   => array(
                    'key'     => 'list_phcitizen',
                    'enabled' => true
                ),
                'create' => array(
                    'key'     => 'create_phcitizen',
                    'enabled' => true
                ),
                'select' => array(
                    'key'     => 'select_phcitizen',
                    'enabled' => true
                ),
                'update' => array(
                    'key'     => 'update_phcitizen',
                    'enabled' => true
                ),
                'delete' => array(
                    'key'     => 'delete_phcitizen',
                    'enabled' => true
                ),
                'search' => array(
                    'key'     => 'search_phcitizen',
                    'enabled' => true
                )
            );


            /****************************************************************************************************
             * PhCitizen :: CONSTRUCTOR
             *
             * Initialize the citizen object.
             * @access public
             *
             * @param int    $citizen_id - the citizen id
             * @param string $purpose    - the usage of this operation
             * @param bool   $metadata
             */
            public function __construct($citizen_id = 0, $purpose = '', $metadata = true) {
                $this->data = array(
                    'id'          => 0,
                    'first_name'  => '[CITIZEN_FIRSTNAME]',
                    'middle_name' => '[CITIZEN_MIDDLENAME]',
                    'last_name'   => '[CITIZEN_LASTNAME]',
                    'full_name_1' => '[NO CITIZEN]',
                    'full_name_2' => '[NO CITIZEN]',
                    'full_name_3' => '[NO CITIZEN]',
                    'full_name_4' => '[NO CITIZEN]',
                    'avatar'      => CITIZEN_AVATAR_URL.'___.jpg'
                );

                $citizen_id = intval($citizen_id);
                if(($citizen_id > 0 || self::$primary_zero_allowed) && $purpose != 'for class usage') {
                    $purpose = 'getting citizen info' . cascade_purpose($purpose);
                    $GLOBALS['query'] = "SELECT ";
                    $GLOBALS['query'] .= " `AccountNumber`, ";
                    $GLOBALS['query'] .= " `Title`, ";
                    $GLOBALS['query'] .= " `FirstName`, ";
                    $GLOBALS['query'] .= " `MiddleName`, ";
                    $GLOBALS['query'] .= " `LastName`, ";
                    $GLOBALS['query'] .= " `NameSuffix`, ";
                    $GLOBALS['query'] .= " `isMale`, ";
                    $GLOBALS['query'] .= " DATE_FORMAT(`DateOfBirth`, '%M %e, %Y') AS DateOfBirth, ";
                    $GLOBALS['query'] .= " `Mobile`, ";
                    $GLOBALS['query'] .= " `StreetAddress`, ";
                    $GLOBALS['query'] .= " `BarangayID`, ";
                    $GLOBALS['query'] .= " `Citizenship`, ";
                    $GLOBALS['query'] .= " `CivilStatus`, ";
                    $GLOBALS['query'] .= " `Avatar` ";
                    $GLOBALS['query'] .= "FROM ";
                    $GLOBALS['query'] .= " `" . self::$table . "` ";
                    $GLOBALS['query'] .= "WHERE ";
                    $GLOBALS['query'] .= " `" . self::$primary_key . "`=$citizen_id";

                    $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                    if (has_no_db_error($purpose)) {
                        if (mysqli_num_rows($result) > 0) {
                            $row = mysqli_fetch_assoc($result);

                            // process full_name
                            $title       = $row['Title'];      // MR.
                            $first_name  = mb_strtoupper($row['FirstName']);  // JUAN
                            $middle_name = mb_strtoupper($row['MiddleName']); // REYES
                            $last_name   = mb_strtoupper($row['LastName']);   // DELA CRUZ
                            $name_suffix = $row['NameSuffix']; // JR.

                            $full_name_1 = ''; // MR. JUAN R. DELA CRUZ JR.
                            $full_name_2 = ''; // MR. JUAN REYES DELA CRUZ JR.
                            $full_name_3 = ''; // DELA CRUZ, JUAN R., JR.
                            $full_name_4 = ''; // DELA CRUZ, JUAN REYES, JR.
                            if($title != '') {
                                $full_name_1 .= $title . ' ';
                                $full_name_2 .= $title . ' ';
                            }
                            $full_name_1 .= $first_name;
                            $full_name_2 .= $first_name;
                            $full_name_3 .= $last_name . ', ' . $first_name;
                            $full_name_4 .= $full_name_3;
                            if($middle_name != '') {
                                $middle_initial = substr($middle_name, 0, 1);
                                $full_name_1 .= ' ' . $middle_initial . '. ';
                                $full_name_2 .= ' ' . $middle_name . ' ';

                                $full_name_3 .= ' ' . $middle_initial . '.';
                                $full_name_4 .= ' ' . $middle_name;

                            }
                            $full_name_1 .= $last_name;
                            $full_name_2 .= $last_name;
                            if($name_suffix != '') {
                                $full_name_1 .= ' ' . $name_suffix;
                                $full_name_2 .= ' ' . $name_suffix;
                                $full_name_3 .= ', ' . $name_suffix;
                                $full_name_4 .= ', ' . $name_suffix;
                            }


                            // process avatar
                            $avatar = $row['Avatar'];
                            if($avatar == null || $avatar == '')
                                $avatar = '___.jpg';
                            else {
                                if(!file_exists(INDEX.CITIZEN_AVATAR_DIR . $avatar)) {
                                    $avatar = '___.jpg';
                                }
                            }

                            // process address
                            require INDEX . 'php/models/PhBarangay.php';
                            $barangay = new PhBarangay($row['BarangayID'], $purpose);
                            $muncity  = $barangay->get_data('muncity');
                            $province = $muncity['province'];

                            $address_line_1   = '';
                            $barangay_address = '';
                            if ($row['StreetAddress'] != '') {
                                $address_line_1 .= $row['StreetAddress'];
                                if(strpos(strtolower($row['StreetAddress']), ' st.') < 0) {
                                    $address_line_1 .= ' St.,';
                                }
                                $address_line_1 .= ', ';
                            }
                            $address_line_1   .= $barangay->get_data('name');
                            $barangay_address .= $barangay->get_data('name') . ", ";
                            $address_line_2    = $muncity['name'] . ', ' . $province['name'];
                            $barangay_address .= $muncity['name'] . ', ' . $province['name'];


                            $this->data = array(
                                'id'               => $citizen_id,
                                'account_number'   => $row['AccountNumber'],
                                'title'            => $title,
                                'first_name'       => $first_name,
                                'middle_name'      => $middle_name,
                                'last_name'        => $last_name,
                                'name_suffix'      => $name_suffix,
                                'full_name_1'      => $full_name_1,
                                'full_name_2'      => $full_name_2,
                                'full_name_3'      => $full_name_3,
                                'full_name_4'      => $full_name_4,

                                'avatar'           => CITIZEN_AVATAR_URL.$avatar,

                                'address_line1'    => $address_line_1,
                                'address_line2'    => $address_line_2,
                                'full_address'     => $address_line_1 . ', ' . $address_line_2,
                                'barangay_address' => $barangay_address,

                                'street_address'   => $row['StreetAddress'],
                                'barangay'         => $barangay->get_data(),

                                'gender'           => (intval($row['isMale'])) ? 'Male' : 'Female',
                                'date_of_birth'    => $row['DateOfBirth'],
                                'mobile'           => $row['Mobile'],
                                'citizenship'      => $row['Citizenship'],
                                'civil_status'     => $row['CivilStatus'],
                            );
                        }
                    }
                    else
                        fin();
                }
            }


            /****************************************************************************************************
             * PhCitizen :: get_item
             *
             * Get the data to be displayed on items list
             * @access public
             *
             * @return array
             */
            public function get_item() {
                return $this->get_item_helper(
                    $this->get_data('id'),
                    $this->get_data('avatar'),
                    $this->get_data('full_name_3'),
                    str_pad($this->get_data('account_number'), 8, '0', STR_PAD_LEFT) . " &middot; " . $this->get_data('address_line2'),
                    ''
                );
            }


            /****************************************************************************************************
             * PhCitizen :: create
             *
             * Create PhCitizen item for create.js
             * @access public static
             *
             */
            public static function create() {
                $firstname = '[FirstName]';
                $lastname  = '[LastName]';
                $GLOBALS['query'] = "INSERT INTO `" . self::$table . "`(`FirstName`, `LastName`) VALUES('$firstname', '$lastname')";
                mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                if (has_no_db_error('for creating new PhCitizen')) {
                    parent::create_helper(new PhCitizen(mysqli_insert_id($GLOBALS['con']), 'for getting newly created PhCitizen'));
                }
            }


            /****************************************************************************************************
             * PhCitizen :: get_form
             *
             * Generate form for PhCitizen object
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
                        'class' => 'form form-group-img',
                        'rows'  => array(
                            // form_data[0]['rows'][0]
                            array(
                                array(
                                    'grid'     => 'col-md-12',
                                    'type'     => 'img_upload',
                                    'alt_text' => 'CITIZEN PHOTO',
                                    'id'       => 'img-citizen-avatar',
                                    'value'    => isset($data['avatar']) ? $data['avatar'] : '',
                                    'size'     => '148px',
                                    'dir'      => 'img/citizens',
                                    'default'  => '___.jpg',
                                    'f_type'   => 'image',
                                    'readonly' => $for_logs
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
                                    'id'       => 'lbl-account',
                                    'value'    => 'ACCOUNT'
                                )
                            )
                        )
                    ),


                    // form_data[2]
                    array(
                        'class' => 'form form-group-attached',
                        'rows'  => array(
                            // form_data[2]['rows'][0]
                            array(
                                array(
                                    'grid'     => 'col-md-12',
                                    'type'     => 'text',
                                    'id'       => 'txt-account-number',
                                    'label'    => 'ACCOUNT NUMBER',
                                    'value'    => isset($data['account_number']) ? $data['account_number'] : '00000000',
                                    'required' => true
                                )
                            )
                        )
                    ),


                    // form_data[3]
                    array(
                        'class' => 'form form-group-break',
                        'rows'  => array(
                            // form_data[3]['rows'][0]
                            array(
                                array(
                                    'grid'     => 'col-md-12',
                                    'type'     => 'label',
                                    'id'       => 'lbl-full-name',
                                    'value'    => 'FULL NAME'
                                )
                            )
                        )
                    ),

                    // form_data[4]
                    array(
                        'class' => 'form form-group-attached',
                        'rows'  => array(
                            // form_data[4]['rows'][0]
                            array(
                                array(
                                    'grid'     => 'col-md-2',
                                    'type'     => 'text',
                                    'id'       => 'txt-title',
                                    'label'    => 'TITLE',
                                    'value'    => isset($data['title']) ? $data['title'] : '',
                                    'required' => false
                                ),
                                array(
                                    'grid'     => 'col-md-3',
                                    'type'     => 'text',
                                    'id'       => 'txt-lastname',
                                    'label'    => 'LAST NAME',
                                    'value'    => isset($data['last_name']) ? $data['last_name'] : '',
                                    'required' => true
                                ),
                                array(
                                    'grid'     => 'col-md-3',
                                    'type'     => 'text',
                                    'id'       => 'txt-firstname',
                                    'label'    => 'FIRST NAME',
                                    'value'    => isset($data['first_name']) ? $data['first_name'] : '',
                                    'required' => true
                                ),
                                array(
                                    'grid'     => 'col-md-3',
                                    'type'     => 'text',
                                    'id'       => 'txt-middlename',
                                    'label'    => 'MIDDLE NAME',
                                    'value'    => isset($data['middle_name']) ? $data['middle_name'] : '',
                                    'required' => false
                                ),
                                array(
                                    'grid'     => 'col-md-1',
                                    'type'     => 'text',
                                    'id'       => 'txt-name-suffix',
                                    'label'    => 'EXT.',
                                    'value'    => isset($data['name_suffix']) ? $data['name_suffix'] : '',
                                    'required' => false
                                )
                            )

                        )
                    ),

                    // form_data[5]
                    array(
                        'class' => 'form form-group-break',
                        'rows'  => array(
                            // form_data[5]['rows'][0]
                            array(
                                array(
                                    'grid'     => 'col-md-12',
                                    'type'     => 'label',
                                    'id'       => 'lbl-home-address',
                                    'value'    => 'HOME ADDRESS'
                                )
                            )
                        )
                    ),

                    // form_data[6]
                    array(
                        'class' => 'form form-group-attached',
                        'rows' => array(
                            // form_data[6]['rows'][0]
                            array(
                                array(
                                    'grid'     => 'col-md-12',
                                    'type'     => 'text',
                                    'id'       => 'txt-street-address',
                                    'label'    => 'STREET ADDRESS',
                                    'value'    => isset($data['street_address']) ? $data['street_address'] : '',
                                    'required' => false
                                ),
                            ),

                            // form_data[6]['rows'][1]
                            array(
                                array(
                                    'grid'     => 'col-md-6',
                                    'type'     => 'searchbox',
                                    'role'     => 'dropdown',
                                    'model'    => 'PhBarangay',
                                    'id'       => 'srch-barangay',
                                    'ref'      => '#srch-muncity',
                                    'label'    => 'BARANGAY',
                                    'value'    => isset($data['barangay']['name']) ? $data['barangay']['name'] : '',
                                    'key'      => isset($data['barangay']['id']) ? $data['barangay']['id'] : 0,
                                    'disabled' => $for_logs
                                ),
                                array(
                                    'grid'     => 'col-md-6',
                                    'type'     => 'searchbox',
                                    'role'     => 'dropdown',
                                    'model'    => 'PhMuncity',
                                    'id'       => 'srch-muncity',
                                    'ref'      => '#srch-province',
                                    'label'    => 'CITY / MUNICIPALITY',
                                    'value'    => isset($data['barangay']['muncity']['name']) ? $data['barangay']['muncity']['name'] : '',
                                    'key'      => isset($data['barangay']['muncity']['id']) ? $data['barangay']['muncity']['id'] : 0,
                                    'disabled' => $for_logs
                                )
                            ),

                            // form_data[6]['rows'][2]
                            array(
                                array(
                                    'grid'     => 'col-md-6',
                                    'type'     => 'searchbox',
                                    'role'     => 'dropdown',
                                    'model'    => 'PhProvince',
                                    'id'       => 'srch-province',
                                    'ref'      => '#srch-region',
                                    'label'    => 'PROVINCE',
                                    'value'    => isset($data['barangay']['muncity']['province']['name']) ? $data['barangay']['muncity']['province']['name'] : '',
                                    'key'      => isset($data['barangay']['muncity']['province']['id']) ? $data['barangay']['muncity']['province']['id'] : 0,
                                    'disabled' => $for_logs
                                ),
                                array(
                                    'grid'     => 'col-md-6',
                                    'type'     => 'searchbox',
                                    'role'     => 'dropdown',
                                    'model'    => 'PhRegion',
                                    'id'       => 'srch-region',
                                    'label'    => 'REGION',
                                    'value'    => isset($data['barangay']['muncity']['province']['region']['name']) ? $data['barangay']['muncity']['province']['region']['name'] : '',
                                    'key'      => isset($data['barangay']['muncity']['province']['region']['id']) ? $data['barangay']['muncity']['province']['region']['id'] : 0,
                                    'disabled' => $for_logs
                                )
                            )
                        )
                    )
                );

                return $arr;
            }


            /****************************************************************************************************
             * PhCitizen :: update
             *
             * Update PhCitizen item for update.js
             * @access public static
             *
             */
            public static function update() {
                $citizen_id = intval($_POST[self::$params['update']['key']]);
                $citizen    = new PhCitizen($citizen_id, 'for update');
                $form_data = $_POST['data'];
                $account_number = intval($form_data[2]['rows'][0]['txt-account-number']);

                // verify account number
                $GLOBALS['query']  = "SELECT ";
                $GLOBALS['query'] .= " `" . self::$primary_key . "` ";
                $GLOBALS['query'] .= "FROM ";
                $GLOBALS['query'] .= " `" . self::$table . "` ";
                $GLOBALS['query'] .= "WHERE ";
                $GLOBALS['query'] .= "     `AccountNumber`=$account_number ";
                $GLOBALS['query'] .= " AND `" . self::$primary_key . "`!=$citizen_id";
                $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                if(has_no_db_error('for verifying new account number upon updating PhCitizen data')) {
                    if(mysqli_num_rows($result) > 0) {
                        $GLOBALS['response']['success']['message']     = 'UNABLE TO UPDATE ' . self::$tab_singular;
                        $GLOBALS['response']['success']['sub_message'] = 'The <b class="text-info">ACCOUNT NUMBER</b> you entered is not available.';
                    }
                    else {
                        // get remaining data
                        $avatar      = mysqli_real_escape_string($GLOBALS['con'], strip_tags(trim($form_data[0]['rows'][0]['img-citizen-avatar'])));
                        $title       = mysqli_real_escape_string($GLOBALS['con'], strip_tags(trim($form_data[4]['rows'][0]['txt-title'])));
                        $firstname   = mysqli_real_escape_string($GLOBALS['con'], strip_tags(trim($form_data[4]['rows'][0]['txt-firstname'])));
                        $middle_name = mysqli_real_escape_string($GLOBALS['con'], strip_tags(trim($form_data[4]['rows'][0]['txt-middlename'])));
                        $lastname    = mysqli_real_escape_string($GLOBALS['con'], strip_tags(trim($form_data[4]['rows'][0]['txt-lastname'])));
                        $suffix      = mysqli_real_escape_string($GLOBALS['con'], strip_tags(trim($form_data[4]['rows'][0]['txt-name-suffix'])));
                        $street      = mysqli_real_escape_string($GLOBALS['con'], strip_tags(trim($form_data[6]['rows'][0]['txt-street-address'])));
                        $barangay_id = intval($form_data[6]['rows'][1]['srch-barangay']);

                        // update PhCitizen data
                        $GLOBALS['query']  = "UPDATE ";
                        $GLOBALS['query'] .= " `" . self::$table . "` ";
                        $GLOBALS['query'] .= "SET ";
                        $GLOBALS['query'] .= " `AccountNumber`=$account_number, ";
                        $GLOBALS['query'] .= " `Title`='$title', ";
                        $GLOBALS['query'] .= " `FirstName`='$firstname', ";
                        $GLOBALS['query'] .= " `MiddleName`='$middle_name', ";
                        $GLOBALS['query'] .= " `LastName`='$lastname', ";
                        $GLOBALS['query'] .= " `NameSuffix`='$suffix', ";
                        $GLOBALS['query'] .= " `StreetAddress`='$street', ";
                        $GLOBALS['query'] .= " `BarangayID`=$barangay_id, ";
                        $GLOBALS['query'] .= " `Avatar`='$avatar' ";
                        $GLOBALS['query'] .= "WHERE ";
                        $GLOBALS['query'] .= " `" . self::$primary_key . "`=$citizen_id";
                        mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                        if (has_no_db_error('for updating PhCitizen data')) {
                            parent::update_helper(new PhCitizen($citizen_id, 'for getting newly updated PhCitizen'), $citizen->get_item());
                        }
                    }
                }
            }


            /****************************************************************************************************
             * PhCitizen :: delete
             *
             * Delete PhCitizen item for delete.js
             * @access public
             *
             */
            public function delete() {
                require INDEX . 'php/models/UserAccount.php';
                if(UserAccount::exists($this->get_data('id'))) {
                    $GLOBALS['response']['success']['message']     = '<span class="text-danger">CANNOT DELETE ' . self::$tab_singular . '</span>';
                    $GLOBALS['response']['success']['sub_message'] =  '<b>' . $this->get_data('full_name_3') . '</b> is registered as a user of this system.';
                }
                else
                    parent::delete_helper($this);
            }


            /****************************************************************************************************
             * PhCitizen :: search
             *
             * Search through citizen records
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
                $GLOBALS['query'] .= " OR `FirstName` LIKE '%$q%' ";
                $GLOBALS['query'] .= " OR `MiddleName` LIKE '%$q%' ";
                $GLOBALS['query'] .= " OR `LastName` LIKE '%$q%' ";
                $GLOBALS['query'] .= " OR `AccountNumber` LIKE '%$q%' ";
                $GLOBALS['query'] .= " OR CONCAT(`Title`, ' ', `FirstName`, ' ', `LastName`, ' ', `NameSuffix`) LIKE '%$q%' ";
                $GLOBALS['query'] .= " OR CONCAT(`Title`, ' ', `FirstName`, ' ', `MiddleName`, ' ', `LastName`, ' ', `NameSuffix`) LIKE '%$q%' ";
                $GLOBALS['query'] .= " OR CONCAT(`Title`, ' ', `FirstName`, ' ', MID(`MiddleName`, 1, 1), '. ', `LastName`, ' ', `NameSuffix`) LIKE '%$q%' ";
                $GLOBALS['query'] .= " OR CONCAT(`LastName`, ' ', `FirstName`, ', ', `NameSuffix`) LIKE '%$q%' ";
                $GLOBALS['query'] .= " OR CONCAT(`LastName`, ', ', `FirstName`, ', ', `NameSuffix`) LIKE '%$q%' ";
                $GLOBALS['query'] .= " OR CONCAT(`LastName`, ', ', `FirstName`, ' ', `MiddleName`, ', ', `NameSuffix`) LIKE '%$q%' ";
                $GLOBALS['query'] .= " OR CONCAT(`LastName`, ', ', `FirstName`, ' ', MID(`MiddleName`, 1, 1), '.', ', ', `NameSuffix`) LIKE '%$q%' ";
                $GLOBALS['query'] .= "ORDER BY ";
                $GLOBALS['query'] .= " `LastName`, `FirstName`, `MiddleName` ";
                parent::search_helper(get_class());
            }
        }

        if(basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
            $object = new PhCitizen(0, 'for class usage');
            require '__fin.php';
        }
    }

?>
