<?php
    require '__init.php';

    if(!class_exists('PhBarangay')) {
        class PhBarangay extends __init {

            // class variables
            public static $tab_singular         = 'BARANGAY';
            public static $table                = 'lending_ph_barangays';
            public static $primary_key          = 'ID';
            public static $foreign_key          = 'BarangayID';
            public static $primary_zero_allowed = true;
            public static $list_order           = 'ORDER BY `ID`';

            public static $params = array(
                'list'   => array(
                    'key'     => 'list_phbarangay',
                    'enabled' => true
                ),
                'select' => array(
                    'key'     => 'select_phbarangay',
                    'enabled' => true
                ),
                'search' => array(
                    'key'     => 'search_phbarangay',
                    'enabled' => true
                )
            );


            /****************************************************************************************************
             * PhBarangay :: CONSTRUCTOR
             *
             * Initialize the barangay object.
             * @access public
             *
             * @param int    $barangay_id - the barangay id
             * @param string $purpose     - the usage of this operation
             * @param bool   $metadata
             */
            public function __construct($barangay_id = 0, $purpose = '', $metadata = true) {
                require INDEX . 'php/models/PhMuncity.php';
                $this->data = array(
                    'id'      => 0,
                    'muncity' => new PhMuncity()
                );

                $barangay_id = intval($barangay_id);
                if(($barangay_id > 0 || self::$primary_zero_allowed) && $purpose != 'for class usage') {
                    $purpose = 'for getting barangay info' . cascade_purpose($purpose);
                    $GLOBALS['query']  = "SELECT `MunCityID`, `Description` FROM `" . self::$table . "` WHERE `" . self::$primary_key . "`=$barangay_id";
                    $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                    if (has_no_db_error($purpose)) {
                        if (mysqli_num_rows($result) > 0) {
                            $row     = mysqli_fetch_assoc($result);
                            $muncity = new PhMuncity($row['MunCityID'], $purpose);
                            $this->data = array(
                                'id'      => $barangay_id,
                                'name'    => $row['Description'],
                                'muncity' => $muncity->get_data(),
                                'address' => $muncity->get_data('id') > 0 ? $row['Description'] . ', ' . $muncity->get_data('name') . ', ' . $muncity->get_data('province')['name'] : ''
                            );
                        }
                    }
                    else
                        fin();
                }
            }


            /****************************************************************************************************
             * PhBarangay :: get_item
             *
             * Get the data to be displayed on items list
             * @access public
             *
             * @return array
             */
            public function get_item() {
                $muncity =  $this->get_data('muncity');

                return $this->get_item_helper(
                    $this->get_data('id'),
                    '',
                    $this->get_data('name'),
                    $muncity['name'] . ', ' .  $muncity['province']['name'],
                    ''
                );
            }


            /****************************************************************************************************
             * PhBarangay :: search
             *
             * Search through barangay records
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
                $GLOBALS['query'] .= " `" . self::$table . "`";
                $GLOBALS['query'] .= " ";
                $GLOBALS['query'] .= "WHERE ";
                $GLOBALS['query'] .= " `" . self::$table . "`.`Description` LIKE '%$q%' ";
                if($ref_found)
                    $GLOBALS['query'] .= "AND `" . self::$table . "`.`" . $ref_model::$foreign_key . "` = $ref_key ";
                $GLOBALS['query'] .= " AND `" . self::$primary_key . "` != 0 ";
                $GLOBALS['query'] .= self::$list_order . " ";
                parent::search_helper(get_class());
            }
        }

        if(basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
            $object = new PhBarangay(0, 'for class usage');
            require '__fin.php';
        }
    }
?>
