<?php
    require '__init.php';

    if(!class_exists('PhProvince')) {
        class PhProvince extends __init {

            // class variables
            public static $tab_singular         = 'PROVINCE';
            public static $table                = 'lending_ph_provinces';
            public static $primary_key          = 'ID';
            public static $foreign_key          = 'ProvinceID';
            public static $primary_zero_allowed = true;
            public static $list_order           = 'ORDER BY `ID`';

            // default :: Camarines Sur
            public static $default_key          = 20;

            public static $params = array(
                'list'   => array(
                    'key'     => 'list_phprovince',
                    'enabled' => true
                ),
                'select' => array(
                    'key'     => 'select_phprovince',
                    'enabled' => true
                ),
                'search' => array(
                    'key'     => 'search_phprovince',
                    'enabled' => true
                )
            );


            /****************************************************************************************************
             * PhProvince :: CONSTRUCTOR
             *
             * Initialize the province object.
             * @access public
             *
             * @param int    $province_id - the province id
             * @param string $purpose     - the usage of this operation
             * @param bool   $metadata
             */
            public function __construct($province_id = 0, $purpose = '', $metadata = true) {
                require INDEX . 'php/models/PhRegion.php';
                $this->data = array(
                    'id'     => 0,
                    'region' => new PhRegion()
                );

                $province_id = intval($province_id);
                if($province_id <= 0)
                    $province_id = self::$default_key;
                if(($province_id > 0 || self::$primary_zero_allowed) && $purpose != 'for class usage') {
                    $purpose = 'for getting province info' . cascade_purpose($purpose);
                    $GLOBALS['query'] = "SELECT `RegionID`, `Description` FROM `" . self::$table ."` WHERE `" . self::$primary_key . "`=$province_id";
                    $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                    if (has_no_db_error($purpose)) {
                        if (mysqli_num_rows($result) > 0) {
                            $row    = mysqli_fetch_assoc($result);
                            $region = new PhRegion($row['RegionID'], $purpose);
                            $this->data = array(
                                'id'     => $province_id,
                                'name'   => $row['Description'],
                                'region' => $region->get_data(),
                                'address'  => $region->get_data('id') > 0 ? $row['Description'] . ', ' . $region->get_data('num') : ''
                            );
                        }
                    }
                    else
                        fin();
                }
            }


            /****************************************************************************************************
             * PhProvince :: get_item
             *
             * Get the data to be displayed on items list
             * @access public
             *
             * @return array
             */
            public function get_item() {
                $region = $this->get_data('region');

                return $this->get_item_helper(
                    $this->get_data('id'),
                    '',
                    $this->get_data('name'),
                    $region['name'],
                    ''
                );
            }


            /****************************************************************************************************
             * PhProvince :: search
             *
             * Search through province records
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
            $object = new PhProvince(0, 'for class usage');
            require '__fin.php';
        }
    }
?>
