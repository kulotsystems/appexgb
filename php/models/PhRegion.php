<?php
    require '__init.php';

    if(!class_exists('PhRegion')) {
        class PhRegion extends __init {

            // class variables
            public static $tab_singular         = 'REGION';
            public static $table                = 'lending_ph_regions';
            public static $primary_key          = 'ID';
            public static $foreign_key          = 'RegionID';
            public static $primary_zero_allowed = true;
            public static $list_order           = 'ORDER BY `ID`';

            // default :: Region V: Bicol Region
            public static $default_key          = 7;

            public static $params = array(
                'list'   => array(
                    'key'     => 'list_phregion',
                    'enabled' => true
                ),
                'select' => array(
                    'key'     => 'select_phregion',
                    'enabled' => true
                ),
                'search' => array(
                    'key'     => 'search_phregion',
                    'enabled' => true
                )
            );


            /****************************************************************************************************
             * PhRegion :: CONSTRUCTOR
             *
             * Initialize the region object.
             * @access public
             *
             * @param int    $region_id - the region id
             * @param string $purpose   - the usage of this operation
             * @param bool   $metadata
             */
            public function __construct($region_id = 0, $purpose = '', $metadata = true) {
                $this->data = array(
                    'id' => 0
                );

                $region_id = intval($region_id);
                if($region_id <= 0)
                    $region_id = self::$default_key;
                if(($region_id > 0 || self::$primary_zero_allowed) && $purpose != 'for class usage') {
                    $purpose = 'for getting region info' . cascade_purpose($purpose);
                    $GLOBALS['query'] = "SELECT `Description` FROM `" . self::$table . "` WHERE `" . self::$primary_key . "`=$region_id";
                    $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                    if (has_no_db_error($purpose)) {
                        if (mysqli_num_rows($result) > 0) {
                            $row = mysqli_fetch_assoc($result);
                            $arr = explode(':', $row['Description']);
                            $region_num  = trim($arr[0]);
                            $region_desc = '';
                            if(sizeof($arr) > 1)
                                $region_desc = trim($arr[1]);
                            $this->data = array(
                                'id'   => $region_id,
                                'name' => $row['Description'],
                                'num'  => $region_num,
                                'desc' => $region_desc
                            );
                        }
                    }
                    else
                        fin();
                }
            }


            /****************************************************************************************************
             * PhRegion :: get_item
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
                    $this->get_data('name'),
                    '',
                    ''
                );
            }


            /****************************************************************************************************
             * PhRegion :: search
             *
             * Search through region records
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
                $GLOBALS['query'] .= " `Description` LIKE '%$q%' ";
                $GLOBALS['query'] .= " AND `" . self::$primary_key . "` != 0 ";
                $GLOBALS['query'] .= self::$list_order . " ";
                parent::search_helper(get_class());
            }
        }

        if(basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
            $object = new PhRegion(0, 'for class usage');
            require '__fin.php';
        }
    }

?>
