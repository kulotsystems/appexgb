<?php
    require '__init.php';

    if(!class_exists('Area')) {
        class Area extends __init {

            // class variables
            public static $tab_singular         = 'AREA';
            public static $table                = 'lending_areas';
            public static $primary_key          = 'ID';
            public static $foreign_key          = 'AreaID';
            public static $primary_zero_allowed = false;
            public static $list_order           = 'ORDER BY `Code`, `Description`';

            public static $params = array(
                'list'  => array(
                    'key'     => 'list_area',
                    'enabled' => true
                ),
                'create' => array(
                    'key'     => 'create_area',
                    'enabled' => true
                ),
                'select' => array(
                    'key'     => 'select_area',
                    'enabled' => true
                ),
                'update' => array(
                    'key'     => 'update_area',
                    'enabled' => true
                ),
                'delete' => array(
                    'key'     => 'delete_area',
                    'enabled' => true
                ),
                'search' => array(
                    'key'     => 'search_area',
                    'enabled' => true
                )
            );


            /****************************************************************************************************
             * Area :: CONSTRUCTOR
             *
             * Initialize the Area object.
             * @access public
             *
             * @param int    $area_id - the Area id
             * @param string $purpose - the usage of this operation
             * @param bool   $metadata
             */
            public function __construct($area_id = 0, $purpose = '', $metadata = true) {
                $this->data = array(
                    'id' => 0,
                );

                $area_id = intval($area_id);
                if(($area_id > 0 || self::$primary_zero_allowed) && $purpose != 'for class usage') {
                    $purpose = 'getting Area info' . cascade_purpose($purpose);
                    $GLOBALS['query']  = "SELECT ";
                    $GLOBALS['query'] .= " `Code`, ";
                    $GLOBALS['query'] .= " `Description`, ";
                    $GLOBALS['query'] .= "  DATE_FORMAT(`CreatedAt`, '%M %e, %Y &middot; %h:%i %p') AS CreatedAt, ";
                    $GLOBALS['query'] .= "  DATE_FORMAT(`UpdatedAt`, '%M %e, %Y &middot; %h:%i %p') AS UpdatedAt ";
                    $GLOBALS['query'] .= "FROM ";
                    $GLOBALS['query'] .= " `" . self::$table . "` ";
                    $GLOBALS['query'] .= "WHERE ";
                    $GLOBALS['query'] .= " `" . self::$primary_key . "`=$area_id";

                    $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                    if(has_no_db_error($purpose)) {
                        $row = mysqli_fetch_assoc($result);

                        // get locations
                        $arr_locations = array();
                        if($metadata) {
                            require INDEX . 'php/models/PhBarangay.php';
                            $arr_locations = array(
                                array(
                                    'id'       => 0,
                                    'barangay' => (new PhBarangay(0, 'for creating blank location of Area'))->get_data()
                                )
                            );
                            $GLOBALS['query']  = "SELECT `ID`, `BarangayID` FROM `lending_area_barangays` ";
                            $GLOBALS['query'] .= "WHERE `AreaID`=$area_id ";
                            $GLOBALS['query'] .= "ORDER BY `ID`";
                            $result2 = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                            if(has_no_db_error('for getting locations of Area')) {
                                while($row2 = mysqli_fetch_assoc($result2)) {
                                    $barangay = new PhBarangay($row2['BarangayID'], 'for getting locations of Area');
                                    array_push($arr_locations, array(
                                        'id'       => $row2['ID'],
                                        'barangay' => $barangay->get_data()
                                    ));
                                }
                            }
                        }

                        $this->data = array(
                            'id'             => $area_id,
                            'code'           => $row['Code'],
                            'description'    => $row['Description'],
                            'locations'      => $arr_locations,
                            'date_created'   => $row['CreatedAt'],
                            'date_updated'   => $row['UpdatedAt']
                        );
                    }
                }
            }


            /****************************************************************************************************
             * Area :: get_item
             *
             * Get the data to be displayed on items list
             * @access public
             *
             * @return array
             */
            public function get_item() {
                $locations = $this->get_data('locations');
                $arr = array();
                if(sizeof($locations) > 0) {
                    foreach ($locations as $location) {
                        $barangay = $location['barangay'];
                        $muncity  = $barangay['muncity'];
                        if($barangay['name'] != '')
                            array_push($arr, $barangay['name']);
                    }
                }
                $subtitle = sizeof($arr) > 0 ? implode(' &middot; ', $arr) : '';

                return $this->get_item_helper(
                    $this->get_data('id'),
                    '',
                    $this->get_data('code'),
                    $subtitle,
                    $this->get_data('date_updated')
                );
            }


            /****************************************************************************************************
             * Area :: create
             *
             * Create Area item for create.js
             * @access public static
             *
             */
            public static function create() {
                $area_code = '[new_area]';
                $GLOBALS['query'] = "INSERT INTO `" . self::$table . "`(`Code`) VALUES('$area_code')";
                mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                if (has_no_db_error('for creating new Area')) {
                    parent::create_helper(new Area(mysqli_insert_id($GLOBALS['con']), 'for getting newly created Area', false));
                }
            }


            /****************************************************************************************************
             * Area :: get_form
             *
             * Generate form for Area object
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
                                    'grid'     => 'col-md-12',
                                    'type'     => 'text',
                                    'id'       => 'txt-code',
                                    'label'    => 'AREA CODE',
                                    'value'    => isset($data['code']) ? $data['code'] : '',
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
                                    'value'    => isset($data['description']) ? $data['description'] : '',
                                    'required' => false
                                )
                            )
                        )
                    ),

                    // form_data[1]
                    array(
                        'class' => 'form forms form-locations',
                        'forms' => array()
                    ),

                    // form_data[2]
                    array(
                        'class' => 'form form-group-break',
                        'rows'  => array(
                            // form_data[1]['rows'][0]
                            array(
                                array(
                                    'grid'     => 'col-md-12',
                                    'type'     => 'label',
                                    'id'       => 'lbl-add-location',
                                    'value'    => '<span class="text-success btn-span btn-add-location" data-ref=".form.form-locations"><span class="fa fa-plus-circle fa-fw"></span>' . ' ADD LOCATION</span>'
                                )
                            )
                        )
                    )
                );

                // populate form_data[1]
                if(isset($data['locations'])) {
                    for($i=0; $i<sizeof($data['locations']); $i++) {
                        $location = $data['locations'][$i];
                        array_push($arr[1]['forms'], array(
                            'class' => 'form form-group-break' . ($i <= 0 ? ' hidden' : ''),
                            'rows'  => array(
                                // form_data[1]['rows'][0]
                                array(
                                    array(
                                        'grid'     => 'col-md-12',
                                        'type'     => 'label',
                                        'id'       => 'lbl-location-' . $i,
                                        'value'    => '<span class="btn-span btn-remove-location"><span class="fa fa-times-circle fa-fw text-danger"></span></span>' . ' LOCATION <span class="n-location">' . $i . "</span>"
                                    )
                                )
                            )
                        ));
                        $index = intval($location['id']);
                        array_push($arr[1]['forms'], array(
                            'class' => 'form form-group-attached' . ($i <= 0 ? ' hidden' : ''),
                            'rows'  => array(
                                array(
                                    array(
                                        'grid'     => 'col-md-6',
                                        'type'     => 'searchbox',
                                        'role'     => 'dropdown',
                                        'model'    => 'PhBarangay',
                                        'id'       => 'srch-barangay-' . $index,
                                        'ref'      => '#srch-muncity-' . $index,
                                        'label'    => 'BARANGAY',
                                        'value'    => isset($location['barangay']['name']) ? $location['barangay']['name'] : '',
                                        'key'      => isset($location['barangay']['id']) ? $location['barangay']['id'] : 0,
                                        'disabled' => $for_logs
                                    ),
                                    array(
                                        'grid'     => 'col-md-6',
                                        'type'     => 'searchbox',
                                        'role'     => 'dropdown',
                                        'model'    => 'PhMuncity',
                                        'id'       => 'srch-muncity-' . $index,
                                        'ref'      => '#srch-province-' . $index,
                                        'label'    => 'CITY / MUNICIPALITY',
                                        'value'    => isset($location['barangay']['muncity']['name']) ? $location['barangay']['muncity']['name'] : '',
                                        'key'      => isset($location['barangay']['muncity']['id']) ? $location['barangay']['muncity']['id'] : 0,
                                        'disabled' => $for_logs
                                    )
                                ),

                                array(
                                    array(
                                        'grid'     => 'col-md-6',
                                        'type'     => 'searchbox',
                                        'role'     => 'dropdown',
                                        'model'    => 'PhProvince',
                                        'id'       => 'srch-province-' . $index,
                                        'ref'      => '#srch-region-' . $index,
                                        'label'    => 'PROVINCE',
                                        'value'    => isset($location['barangay']['muncity']['province']['name']) ? $location['barangay']['muncity']['province']['name'] : '',
                                        'key'      => isset($location['barangay']['muncity']['province']['id']) ? $location['barangay']['muncity']['province']['id'] : 0,
                                        'disabled' => $for_logs
                                    ),
                                    array(
                                        'grid'     => 'col-md-6',
                                        'type'     => 'searchbox',
                                        'role'     => 'dropdown',
                                        'model'    => 'PhRegion',
                                        'id'       => 'srch-region-'. $index,
                                        'label'    => 'REGION',
                                        'value'    => isset($location['barangay']['muncity']['province']['region']['name']) ? $location['barangay']['muncity']['province']['region']['name'] : '',
                                        'key'      => isset($location['barangay']['muncity']['province']['region']['id']) ? $location['barangay']['muncity']['province']['region']['id'] : 0,
                                        'disabled' => $for_logs
                                    )
                                )
                            )
                        ));
                    }
                }

                return $arr;
            }


            /****************************************************************************************************
             * Area :: update
             *
             * Update Area item for update.js
             * @access public static
             *
             */
            public static function update() {
                $area_id = intval($_POST[self::$params['update']['key']]);
                $area = new Area($area_id, 'for update');
                $form_data = $_POST['data'];
                $code        = mysqli_real_escape_string($GLOBALS['con'], strip_tags(trim($form_data[0]['rows'][0]['txt-code'])));
                $description = mysqli_real_escape_string($GLOBALS['con'], strip_tags(trim($form_data[0]['rows'][1]['txt-desc'])));

                // get locations
                $got_all_locations = false;
                $barangay_ids      = array();
                $i = 3;
                while(!$got_all_locations) {
                    if($i < sizeof($form_data)) {
                        $form = $form_data[$i];
                        if($form['class'] == 'form-group-attached')
                            array_push($barangay_ids, array_values($form['rows'][0])[0]);
                        else {
                            if(isset($form['rows'][0]['lbl-add-location'])) {
                                $got_all_locations = true;
                                break;
                            }
                        }
                        $i += 1;
                    }
                    else
                        $got_all_locations = true;
                }

                // remove duplicate locations
                $barangay_ids = array_unique($barangay_ids);

                // delete previous locations
                $GLOBALS['query']  = "DELETE FROM `lending_area_barangays` WHERE `AreaID`=$area_id";
                mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                if(has_no_db_error('for deleting previous locations upon updating Area data')) {

                    // insert new locations
                    $GLOBALS['query']  = "INSERT INTO `lending_area_barangays`(`AreaID`, `BarangayID`) ";
                    $GLOBALS['query'] .= "VALUES ";
                    for($i=0; $i<sizeof($barangay_ids); $i++) {
                        $GLOBALS['query'] .= "(" . $area_id . ", " . $barangay_ids[$i] . ")";
                        if($i < sizeof($barangay_ids)-1)
                            $GLOBALS['query'] .= ",";
                        $GLOBALS['query'] .= " ";
                    }
                    if(sizeof($barangay_ids) > 0)
                        mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                    if(has_no_db_error('for inserting new locations upon updating Area data')) {

                        // update Area data
                        $GLOBALS['query']  = "UPDATE `" . self::$table . "` ";
                        $GLOBALS['query'] .= "SET ";
                        $GLOBALS['query'] .= " `Code`='$code', ";
                        $GLOBALS['query'] .= " `Description`='$description' ";
                        $GLOBALS['query'] .= "WHERE ";
                        $GLOBALS['query'] .= " `" . self::$primary_key . "`=$area_id";
                        mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                        if (has_no_db_error('for updating Area data')) {
                            parent::update_helper(new Area($area_id, 'for getting newly updated Area'), $area->get_item());
                        }
                    }
                }
            }


            /****************************************************************************************************
             * Area :: delete
             *
             * Delete Area item for delete.js
             * @access public
             *
             */
            public function delete() {
                if(false) {
                    // TODO :: Related records check
                }
                else
                    parent::delete_helper($this);
            }


            /****************************************************************************************************
             * Area :: search
             *
             * Search through Area records
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
                $GLOBALS['query'] .= " OR `Code` LIKE '%$q%' ";
                $GLOBALS['query'] .= " OR `Description` LIKE '%$q%' ";
                $GLOBALS['query'] .= self::$list_order . " ";
                parent::search_helper(get_class());
            }


            /****************************************************************************************************
             * Area :: get_all
             *
             * Get all Area
             * @access public static
             *
             * @return Array of Area data
             *
             */
            public static function get_all() {
                $arr = array();
                $GLOBALS['query']  = "SELECT `" . self::$primary_key . "` ";
                $GLOBALS['query'] .= "FROM `" . self::$table . "` ";
                $GLOBALS['query'] .= "WHERE 1 ";
                $GLOBALS['query'] .= self::$list_order;
                $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                if(has_no_db_error('for getting all Area')) {
                    while($row = mysqli_fetch_assoc($result)) {
                        $area = new Area($row[self::$primary_key], 'for getting Area data upon getting all Area');
                        array_push($arr, $area->get_data());
                    }
                }
                return $arr;
            }
        }

        if(basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
            $object = new Area(0, 'for class usage');
            require '__fin.php';
        }
    }
?>
