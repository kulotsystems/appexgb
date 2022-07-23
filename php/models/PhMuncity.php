<?php
    require '__init.php';

    if(!class_exists('PhMuncity')) {
        class PhMuncity extends __init {

            // class variables
            public static $tab_singular         = 'MUN./CITY';
            public static $table                = 'lending_ph_muncities';
            public static $primary_key          = 'ID';
            public static $foreign_key          = 'MunCityID';
            public static $primary_zero_allowed = true;
            public static $list_order           = 'ORDER BY `ID`';

            // default :: Iriga City
            public static $default_key          = 362;

            public static $params = array(
                'list'   => array(
                    'key'     => 'list_phmuncity',
                    'enabled' => true
                ),
                'select' => array(
                    'key'     => 'select_phmuncity',
                    'enabled' => true
                ),
                'search' => array(
                    'key'     => 'search_phmuncity',
                    'enabled' => true
                )
            );


            /****************************************************************************************************
             * PhMuncity :: CONSTRUCTOR
             *
             * Initialize the muncity object.
             * @access public
             *
             * @param int    $muncity_id - the muncity id
             * @param string $purpose    - the usage of this operation
             * @param bool   $metadata
             */
            public function __construct($muncity_id = 0, $purpose = '', $metadata = true) {
                require INDEX . 'php/models/PhProvince.php';
                $this->data = array(
                    'id'       => 0,
                    'province' => new PhProvince()
                );

                $muncity_id = intval($muncity_id);
                if($muncity_id <= 0)
                    $muncity_id = self::$default_key;
                if(($muncity_id > 0 || self::$primary_zero_allowed) && $purpose != 'for class usage') {
                    $purpose = 'for getting muncity info' . cascade_purpose($purpose);
                    $GLOBALS['query']  = "SELECT `ProvinceID`, `Type`, `Description` FROM `" . self::$table . "` WHERE `" . self::$primary_key . "`=$muncity_id";
                    $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                    if (has_no_db_error($purpose)) {
                        if (mysqli_num_rows($result) > 0) {
                            $row      = mysqli_fetch_assoc($result);
                            $province = new PhProvince($row['ProvinceID'], $purpose);
                            $this->data = array(
                                'id'       => $muncity_id,
                                'name'     => $row['Description'],
                                'type'     => $row['Type'],
                                'province' => $province->get_data(),
                                'address'  => $province->get_data('id') > 0 ? $row['Description'] . ', ' . $province->get_data('name') : ''
                            );
                        }
                    }
                    else
                        fin();
                }
            }


            /****************************************************************************************************
             * PhMuncity :: get_item
             *
             * Get the data to be displayed on items list
             * @access public
             *
             * @return array
             */
            public function get_item() {
                $province = $this->get_data('province');

                return $this->get_item_helper(
                    $this->get_data('id'),
                    '',
                    $this->get_data('name'),
                    $province['name'] . ', ' .  $province['region']['num'],
                    ''
                );
            }


            /****************************************************************************************************
             * PhMuncity :: search
             *
             * Search through muncity records
             * @access public static
             *
             * @param string $q - the search query
             * @param string $ref_model - the reference model (if any)
             * @param string $ref_key   - the key of reference model (if any)
             *
             */
            public static function search($q, $ref_model, $ref_key) {
                $ref_found = false;
                if($ref_model != '') {
                    $ref_path = INDEX . 'php/models/' . $ref_model . '.php';
                    if(file_exists($ref_path)) {
                        require $ref_path;
                        $ref_found = true;
                    }
                }

                $q = mysqli_real_escape_string($GLOBALS['con'], $q);
                $GLOBALS['query']  = "SELECT ";
                $GLOBALS['query'] .= " `" . self::$primary_key . "` ";
                $GLOBALS['query'] .= "FROM ";
                $GLOBALS['query'] .= " `" . self::$table . "` ";
                $GLOBALS['query'] .= "WHERE ";
                $GLOBALS['query'] .= " `Description` LIKE '%$q%' ";
                if($ref_found)
                    $GLOBALS['query'] .= "AND `" . self::$table . "`.`" . $ref_model::$foreign_key . "` = $ref_key ";
                $GLOBALS['query'] .= " AND `" . self::$primary_key . "` != 0 ";
                $GLOBALS['query'] .= self::$list_order . " ";
                parent::search_helper(get_class());
            }
        }

        if(basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
            $object = new PhMuncity(0, 'for class usage');
            require '__fin.php';
        }
    }
?>
