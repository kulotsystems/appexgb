<?php
    require '__init.php';

    if(!class_exists('Release')) {
        class Release extends __init {

            // class variables
            public static $tab_singular         = 'RELEASE';
            public static $table                = 'lending_loan_releases';
            public static $primary_key          = 'ID';
            public static $foreign_key          = 'ReleaseID';
            public static $primary_zero_allowed = false;
            public static $list_order           = 'ORDER BY `ID` DESC, CAST(`Cycle` AS UNSIGNED) DESC';

            public static $params = array(
                'list'   => array(
                    'key'     => 'list_release',
                    'enabled' => true
                ),
                'create' => array(
                    'key'     => 'create_release',
                    'enabled' => true
                ),
                'select' => array(
                    'key'     => 'select_release',
                    'enabled' => true
                ),
                'update' => array(
                    'key'     => 'update_release',
                    'enabled' => true
                ),
                'delete' => array(
                    'key'     => 'delete_release',
                    'enabled' => true
                ),
                'search' => array(
                    'key'     => 'search_release',
                    'enabled' => true
                ),
                'callback' => array(
                    'key'     => 'callback_release',
                    'enabled' => true
                )
            );


            /****************************************************************************************************
             * Release :: CONSTRUCTOR
             *
             * Initialize the Release object.
             * @access public
             *
             * @param int    $release_id - the Release id
             * @param string $purpose    - the usage of this operation
             * @param bool   $metadata
             */
            public function __construct($release_id = 0, $purpose = '', $metadata = true) {
                $this->data = array(
                    'id' => 0
                );

                $release_id = intval($release_id);
                if(($release_id > 0 || self::$primary_zero_allowed) && $purpose != 'for class usage') {
                    $purpose = 'getting Release info' . cascade_purpose($purpose);
                    $GLOBALS['query']  = "SELECT ";
                    $GLOBALS['query'] .= " `" . self::$table . "`.`CitizenID`, ";
                    $GLOBALS['query'] .= " `" . self::$table . "`.`Cycle`, ";
                    $GLOBALS['query'] .= " `lending_loan_amounts`.`AreaID`, ";
                    $GLOBALS['query'] .= " `lending_loan_amounts`.`OfferID`, ";
                    $GLOBALS['query'] .= " `lending_loan_amounts`.`Amount`, ";
                    $GLOBALS['query'] .= " `lending_loan_amounts`.`Date` AS ReleasedAt, ";
                    $GLOBALS['query'] .= "  DATE_FORMAT(`" . self::$table . "`.`CreatedAt`, '%M %e, %Y &middot; %h:%i %p') AS CreatedAt, ";
                    $GLOBALS['query'] .= "  DATE_FORMAT(`" . self::$table . "`.`UpdatedAt`, '%M %e, %Y &middot; %h:%i %p') AS UpdatedAt ";
                    $GLOBALS['query'] .= "FROM ";
                    $GLOBALS['query'] .= " `" . self::$table . "`, `lending_loan_amounts` ";
                    $GLOBALS['query'] .= "WHERE ";
                    $GLOBALS['query'] .= "     `" . self::$table . "`.`" . self::$primary_key . "`=$release_id ";
                    $GLOBALS['query'] .= " AND `" . self::$table . "`.`" . self::$primary_key . "`=`lending_loan_amounts`.`" . self::$foreign_key . "` ";
                    $GLOBALS['query'] .= " AND `lending_loan_amounts`.`IsRestructure`=0";
                    $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                    if (has_no_db_error($purpose)) {
                        if (mysqli_num_rows($result) > 0) {
                            $row = mysqli_fetch_assoc($result);
                            require INDEX . 'php/models/PhCitizen.php';
                            require INDEX . 'php/models/Offer.php';
                            require INDEX . 'php/models/Area.php';
                            $citizen = new PhCitizen($row['CitizenID'], $purpose, false);
                            $offer   = new Offer($row['OfferID'], $purpose, false);
                            $area    = new Area($row['AreaID'], $purpose, false);

                            // get restructures
                            $restructures = array();
                            if($metadata) {
                                $restructures = array(
                                    array(
                                        'id'     => 0,
                                        'offer'  => $offer->get_data(),
                                        'area'   => $area->get_data(),
                                        'amount' => 0,
                                        'date'   => '',
                                    )
                                );
                                $GLOBALS['query']  = "SELECT ";
                                $GLOBALS['query'] .= " `ID`, `OfferID`, `AreaID`, `Amount`, `Date` ";
                                $GLOBALS['query'] .= "FROM ";
                                $GLOBALS['query'] .= " `lending_loan_amounts` ";
                                $GLOBALS['query'] .= "WHERE ";
                                $GLOBALS['query'] .= "     `ReleaseID`=$release_id ";
                                $GLOBALS['query'] .= " AND `IsRestructure`=1 ";
                                $GLOBALS['query'] .= "ORDER BY ";
                                $GLOBALS['query'] .= " DATE(`Date`)";
                                $result2 = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                                if(!has_no_db_error('for getting loan restructuring upon getting Release info'))
                                    fin();
                                else {
                                    while($row2 = mysqli_fetch_assoc($result2)) {
                                        $restructure_offer = new Offer($row2['OfferID'], 'for getting Offer data of Release restructure');
                                        $restructure_area  = new Area($row2['AreaID'], 'for getting Area data of Release restructure');

                                        array_push($restructures, array(
                                            'id'     => intval($row2['ID']),
                                            'offer'  => $restructure_offer->get_data(),
                                            'area'   => $restructure_area->get_data(),
                                            'amount' => doubleval($row2['Amount']),
                                            'date'   => $row2['Date'] == '0000-00-00' ? '' : $row2['Date']
                                        ));
                                    }
                                }
                            }

                            $this->data = array(
                                'id'            => $release_id,
                                'citizen'       => $citizen->get_data(),
                                'cycle'         => ($row['Cycle'] != '') ? intval($row['Cycle']) : '',
                                'offer'         => $offer->get_data(),
                                'area'          => $area->get_data(),
                                'amount'        => doubleval($row['Amount']),
                                'release_date'  => $row['ReleasedAt'],
                                'restructures'  => $restructures,
                                'date_created'  => $row['CreatedAt'],
                                'date_updated'  => $row['UpdatedAt'],

                                'collection_dates' => array(),
                                'collection_data'  => array(),
                                'is_ghost'         => false,
                                'is_previous'      => false
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
             * Release :: get_item
             *
             * Get the data to be displayed on items list
             * @access public
             *
             * @return array
             */
            public function get_item() {
                $citizen = $this->get_data('citizen');
                $area    = $this->get_data('area');

                $cycle   = $this->get_data('cycle');
                $cycle_ordinal = '';
                if($cycle > 0) {
                    $ends = array('th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th');
                    if (($cycle % 100) >= 11 && ($cycle % 100) <= 13)
                        $cycle_ordinal = '<b class="text-bold">' . $cycle . 'th</b> Cycle, ';
                    else
                        $cycle_ordinal = '<b class="text-bold">' . $cycle . $ends[$cycle % 10] . '</b> Cycle, ';
                }

                return $this->get_item_helper(
                    $this->get_data('id'),
                    $citizen['avatar'],
                    $citizen['full_name_3'],
                    $cycle_ordinal . $area['code'],
                    date('F d, Y', strtotime($this->get_data('release_date'))),
                    $citizen['full_name_3'] . ' | ' . strip_tags($cycle_ordinal) . $area['code']
                );
            }


            /****************************************************************************************************
             * Release :: create
             *
             * Create Release item for create.js
             * @access public static
             *
             */
            public static function create() {
                require INDEX . 'php/models/PhCitizen.php';
                require INDEX . 'php/models/Offer.php';
                require INDEX . 'php/models/Area.php';

                $first_citizen = parent::get_first_record('PhCitizen', 'for creating new Release');
                $first_offer   = parent::get_first_record('Offer', 'for creating new Release');
                $first_area    = parent::get_first_record('Area', 'for creating new Release');

                if($first_citizen->get_data('id') <= 0)
                    $GLOBALS['response']['success']['sub_message'] = 'NO <span class="text-info">' . get_class($first_citizen) . '</span> found!';
                else if($first_offer->get_data('id') <= 0)
                    $GLOBALS['response']['success']['sub_message'] = 'NO <span class="text-info">' . get_class($first_offer) . '</span> found!';
                else if($first_area->get_data('id') <= 0)
                    $GLOBALS['response']['success']['sub_message'] = 'NO <span class="text-info">' . get_class($first_area) . '</span> found!';
                if($GLOBALS['response']['success']['sub_message'] != '')
                    $GLOBALS['response']['success']['message'] = 'UNABLE TO CREATE NEW <span class="text-info">Release</span>';
                else {
                    $GLOBALS['query'] = "INSERT INTO `" . self::$table . "`(`CitizenID`) VALUES(" . $first_citizen->get_data('id') . ")";
                    mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                    if (has_no_db_error('for creating new Release')) {
                        $release_id = mysqli_insert_id($GLOBALS['con']);
                        $GLOBALS['query'] = "INSERT INTO `lending_loan_amounts`(`ReleaseID`, `OfferID`, `AreaID`, `Date`, `IsRestructure`) VALUES($release_id, " . $first_offer->get_data('id') . ", " . $first_area->get_data('id') . ", NOW(), 0)";
                        mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                        if (has_no_db_error('for creating new non-restructure Release amount'))
                            parent::create_helper(new Release($release_id, 'for getting newly created Release'));
                    }
                }
            }


            /****************************************************************************************************
             * Release :: get_form
             *
             * Generate form for Release object
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
                                    'grid'     => 'col-md-6',
                                    'type'     => 'searchbox',
                                    'model'    => 'PhCitizen',
                                    'id'       => 'srch-citizen',
                                    'label'    => 'CITIZEN',
                                    'avatar'   => isset($data['citizen']['avatar']) ? $data['citizen']['avatar'] : '',
                                    'value'    => isset($data['citizen']['full_name_3']) ? $data['citizen']['full_name_3'] : '',
                                    'key'      => isset($data['citizen']['id']) ? $data['citizen']['id'] : '',
                                    'disabled' => $for_logs
                                ),
                                array(
                                    'grid'     => 'col-md-3',
                                    'type'     => 'verifier',
                                    'model'    => 'Release',
                                    'id'       => 'ver-cycle',
                                    'label'    => 'CYCLE',
                                    'href'     => '.input-group-search[data-href="#srch-citizen"]',
                                    'attr'     => 'data-key',
                                    'param'    => 'cycle_for_release',
                                    'value'    => isset($data['cycle']) ? $data['cycle'] : '',
                                    'required' => false,
                                    'disabled' => true
                                ),
                                array(
                                    'grid'     => 'col-md-3',
                                    'type'     => 'searchbox',
                                    'role'     => 'dropdown',
                                    'model'    => 'Area',
                                    'id'       => 'srch-area',
                                    'label'    => 'AREA',
                                    'value'    => isset($data['area']['code']) ? $data['area']['code'] : '',
                                    'key'      => isset($data['area']['id']) ? $data['area']['id'] : 0,
                                    'disabled' => $for_logs,
                                    'sync_num' => '1'
                                )
                            ),

                            // form_data[0]['rows'][1]
                            array(
                                array(
                                    'grid'     => 'col-md-6',
                                    'type'     => 'searchbox',
                                    'role'     => 'dropdown',
                                    'model'    => 'Offer',
                                    'id'       => 'srch-offer',
                                    'label'    => 'OFFER',
                                    'value'    => isset($data['offer']['title']) ? $data['offer']['title'] : '',
                                    'key'      => isset($data['offer']['id']) ? $data['offer']['id'] : 0,
                                    'disabled' => $for_logs
                                ),
                                array(
                                    'grid'     => 'col-md-3',
                                    'type'     => 'currency',
                                    'id'       => 'txt-amount',
                                    'label'    => 'LOAN AMOUNT',
                                    'value'    => isset($data['amount']) ? $data['amount'] : 0,
                                    'is_int'   => true,
                                    'required' => true
                                ),
                                array(
                                    'grid'     => 'col-md-3',
                                    'type'     => 'date',
                                    'id'       => 'txt-release-date',
                                    'label'    => 'RELEASE DATE',
                                    'value'    => isset($data['release_date']) ? $data['release_date'] : '',
                                    'required' => true
                                )
                            )
                        )
                    ),

                    // form_data[1]
                    array(
                        'class' => 'form forms form-restructures',
                        'forms' => array()
                    ),

                    // form_data[2]
                    array(
                        'class' => 'form form-group-break',
                        'rows'  => array(
                            // form_data[2]['rows'][0]
                            array(
                                array(
                                    'grid'     => 'col-md-12',
                                    'type'     => 'label',
                                    'id'       => 'lbl-add-restructure',
                                    'value'    => '<span class="text-success btn-span btn-add-restructure" data-ref=".form.form-restructures"><span class="fa fa-plus-circle fa-fw"></span>' . ' ADD RESTRUCTURE</span>'
                                )
                            )
                        )
                    ),

                    // form_data[3]
                    /*array(
                        'class' => 'callback',
                        'model' => 'Release',
                        'param' => 'get_release_collection',
                        'id'    => 'callback-' . str_replace(' ', '', strtolower(self::$tab_singular)) . '-' . $data['id'],
                        'value' => $data['id'],
                        'icon'  => 'fa-info-circle',
                        'label' => 'COLLECTION STATUS',
                        'form'  => array(
                            'class'  => 'form forms form-collection',
                            'forms'  => self::get_collection_form($data['collection'], $data)
                        )
                    ),*/
                );

                // populate form_data[1]
                if(isset($data['restructures'])) {
                    for($i=0; $i<sizeof($data['restructures']); $i++) {
                        $restructure = $data['restructures'][$i];
                        array_push($arr[1]['forms'], array(
                            'class' => 'form form-group-break' . ($i <= 0 ? ' hidden' : ''),
                            'rows'  => array(
                                // form_data[1]['rows'][0]
                                array(
                                    array(
                                        'grid'     => 'col-md-12',
                                        'type'     => 'label',
                                        'id'       => 'lbl-restructure-' . $i,
                                        'value'    => '<span class="btn-span btn-remove-restructure"><span class="fa fa-times-circle fa-fw text-danger"></span></span>' . ' RESTRUCTURING <span class="n-restructure">' . $i . "</span>"
                                    )
                                )
                            )
                        ));

                        array_push($arr[1]['forms'], array(
                            'class' => 'form form-group-attached' . ($i <= 0 ? ' hidden' : ''),
                            'rows'  => array(
                                array(
                                    array(
                                        'grid'     => 'hidden',
                                        'type'     => 'searchbox',
                                        'role'     => 'dropdown',
                                        'model'    => 'Area',
                                        'id'       => 'srch-restructure-area-' . $i,
                                        'label'    => 'AREA',
                                        'value'    => isset($restructure['area']['code']) ? $restructure['area']['code'] : '',
                                        'key'      => isset($restructure['area']['id']) ? $restructure['area']['id'] : 0,
                                        'disabled' => $for_logs,
                                        'sync_num' => '1'
                                    ),
                                    array(
                                        'grid'     => 'col-md-6',
                                        'type'     => 'searchbox',
                                        'role'     => 'dropdown',
                                        'model'    => 'Offer',
                                        'id'       => 'srch-restructure-offer-' . $i,
                                        'label'    => 'OFFER',
                                        'value'    => isset($restructure['offer']['title']) ? $restructure['offer']['title'] : '',
                                        'key'      => isset($restructure['offer']['id']) ? $restructure['offer']['id'] : 0,
                                        'disabled' => $for_logs
                                    ),
                                    array(
                                        'grid'     => 'col-md-3',
                                        'type'     => 'currency',
                                        'id'       => 'txt-restructure-amount-' . $i,
                                        'label'    => 'AMOUNT',
                                        'value'    => isset($restructure['amount']) ? $restructure['amount'] : 0,
                                        'is_int'   => true,
                                        'required' => false
                                    ),
                                    array(
                                        'grid'     => 'col-md-3',
                                        'type'     => 'date',
                                        'id'       => 'txt-restructure-date-' . $i,
                                        'label'    => 'DATE',
                                        'value'    => isset($restructure['date']) ? $restructure['date'] : '',
                                        'required' => true
                                    )
                                )
                            )
                        ));
                    }
                }

                return $arr;
            }


            /****************************************************************************************************
             * Release :: update
             *
             * Update Release item for update.js
             * @access public static
             *
             */
            public static function update() {
                $release_id = intval($_POST[self::$params['update']['key']]);
                $release    = new Release($release_id, 'for update');
                $form_data  = $_POST['data'];

                $citizen_id = intval($form_data[0]['rows'][0]['srch-citizen']);
                $cycle      = intval($form_data[0]['rows'][0]['ver-cycle']);
                if($cycle <= 0) {
                    require INDEX . 'php/models/PhCitizen.php';
                    $citizen = new PhCitizen($citizen_id, 'for creating new cycle for a citizen');
                    $GLOBALS['response']['success']['message'] = 'UNABLE TO UPDATE RELEASE';
                    $GLOBALS['response']['success']['sub_message'] = 'Please assign a new cycle for <b class="text-success">' . $citizen->get_data('full_name_3') . '</b>.';
                }
                else {
                    $area_id   = intval($form_data[0]['rows'][0]['srch-area']);
                    $offer_id  = intval($form_data[0]['rows'][1]['srch-offer']);
                    $amount    = doubleval($form_data[0]['rows'][1]['txt-amount']);
                    $date      = date('Y-m-d', strtotime(trim($form_data[0]['rows'][1]['txt-release-date'])));

                    // get restructures
                    $form_index = 2;
                    $proceed    = true;
                    $restructure_area_ids   = array();
                    $restructure_offer_ids  = array();
                    $restructure_amounts    = array();
                    $restructure_dates      = array();
                    while(true) {
                        $form_index += 1;
                        $form = $form_data[$form_index];
                        if(isset($form['rows'][0]['lbl-add-restructure']))
                            break;
                        if($form['class'] == 'form-group-attached') {
                            $restructure = array_values($form['rows'][0]);
                            array_push($restructure_area_ids, intval($restructure[0]));
                            array_push($restructure_offer_ids, intval($restructure[1]));
                            array_push($restructure_amounts  , doubleval($restructure[2]));
                            array_push($restructure_dates    , date('Y-m-d', strtotime(trim($restructure[3]))));
                        }
                    }
                    $unique_area_ids  = array();
                    $unique_offer_ids = array();
                    $unique_dates     = array_unique($restructure_dates);
                    $unique_amounts   = array();

                    // delete previous restructures
                    $GLOBALS['query'] = "DELETE FROM `lending_loan_amounts` WHERE `ReleaseID`=$release_id AND `IsRestructure`=1";
                    mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                    if(has_no_db_error('for deleting previous loan structures upon updating Release')) {
                        for ($x = 0; $x < sizeof($unique_dates); $x++) {
                            $m = 0;
                            $o = 1;
                            $a = 1;
                            for ($y = sizeof($restructure_dates) - 1; $y >= 0; $y--) {
                                if ($unique_dates[$x] == $restructure_dates[$y]) {
                                    $m = $restructure_amounts[$y];
                                    $o = $restructure_offer_ids[$y];
                                    $a = $restructure_area_ids[$y];
                                    break;
                                }
                            }
                            array_push($unique_amounts  , $m);
                            array_push($unique_offer_ids, $o);
                            array_push($unique_area_ids , $a);
                        }

                        // insert new restructures
                        $can_insert = false;
                        $GLOBALS['query'] = "INSERT INTO `lending_loan_amounts`(`ReleaseID`, `OfferID`, `AreaID`, `Amount`, `Date`, `IsRestructure`) ";
                        $GLOBALS['query'] .= "VALUES ";
                        for ($x = 0; $x < sizeof($unique_amounts); $x++) {
                            if ($unique_amounts[$x] > 0) {
                                $can_insert = true;
                                $GLOBALS['query'] .= "(" . $release_id . ", " . $unique_offer_ids[$x] . ", " . $unique_area_ids[$x] . ", " . $unique_amounts[$x] . ", '" . $unique_dates[$x] . "', 1)";
                                if ($x < sizeof($unique_amounts) - 1)
                                    $GLOBALS['query'] .= ",";
                                $GLOBALS['query'] .= " ";
                            }
                        }

                        if ($can_insert) {
                            mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                            if (!has_no_db_error('for inserting new loan structures upon updating Release'))
                                $proceed = false;
                            else {
                                for($x = 0; $x<sizeof($unique_dates); $x++) {
                                    // add collection of 0, if not already, for restructure release date, so that posting subquery will work
                                    $first_collection_date = date('Y-m-d', strtotime($unique_dates[$x]) + __init::$DAY_TOTAL_SECONDS);
                                    $release->insert_default_collection_during($first_collection_date);
                                }
                            }
                        }

                        if($proceed) {
                            // update Release data
                            $GLOBALS['query'] = "UPDATE `" . self::$table . "` ";
                            $GLOBALS['query'] .= "SET ";
                            $GLOBALS['query'] .= " `CitizenID`=$citizen_id, ";
                            $GLOBALS['query'] .= " `Cycle`=$cycle ";
                            $GLOBALS['query'] .= "WHERE ";
                            $GLOBALS['query'] .= " `" . self::$primary_key . "`=$release_id";
                            mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                            if (has_no_db_error('for updating Release data')) {
                                // add collection of 0, if not already, for non-restructure release date, so that posting subquery will work
                                $first_collection_date = date('Y-m-d', strtotime($date) + __init::$DAY_TOTAL_SECONDS);
                                $release->insert_default_collection_during($first_collection_date);

                                $GLOBALS['query']  = "SELECT `ID` FROM `lending_loan_collections` ";
                                $GLOBALS['query'] .= "WHERE `" . self::$foreign_key . "`=$release_id AND `Date`='$first_collection_date'";
                                $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                                if(has_no_db_error('for getting first collection for Release')) {
                                    if(mysqli_num_rows($result) <= 0) {
                                        $GLOBALS['query']  = "INSERT INTO ";
                                        $GLOBALS['query'] .= " `lending_loan_collections`(`ReleaseID`, `Amount`, `Date`) ";
                                        $GLOBALS['query'] .= "VALUES ";
                                        $GLOBALS['query'] .= " (" . $release_id . ", 0, '" . $first_collection_date . "') ";
                                        mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                                        if(!has_no_db_error('for inserting first collection for Release'))
                                            fin();
                                    }

                                    // update non-restructure Release amount data
                                    $GLOBALS['query'] = "UPDATE `lending_loan_amounts` ";
                                    $GLOBALS['query'] .= "SET ";
                                    $GLOBALS['query'] .= " `OfferID`=$offer_id, ";
                                    $GLOBALS['query'] .= " `AreaID`=$area_id, ";
                                    $GLOBALS['query'] .= " `Amount`=$amount, ";
                                    $GLOBALS['query'] .= " `Date`='$date' ";
                                    $GLOBALS['query'] .= "WHERE ";
                                    $GLOBALS['query'] .= "     `" . self::$foreign_key . "`=$release_id ";
                                    $GLOBALS['query'] .= " AND `IsRestructure`=0";
                                    mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                                    if (has_no_db_error('for updating non-restructure Release amount')) {
                                        // delete Release cache
                                        $GLOBALS['query']  = "DELETE FROM ";
                                        $GLOBALS['query'] .= " `lending_loan_cache` ";
                                        $GLOBALS['query'] .= "WHERE ";
                                        $GLOBALS['query'] .= " `ReleaseID`=$release_id";
                                        mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                                        if(has_no_db_error('for deleting previous Release cache')) {
                                            // delete cutoff cache
                                            require INDEX . 'php/models/Posting.php';
                                            $cutoff_dates = Posting::get_weekly_posting_dates(date('F d, Y', strtotime($date)));
                                            $GLOBALS['query']  = "DELETE FROM ";
                                            $GLOBALS['query'] .= " `lending_cutoff_cache` ";
                                            $GLOBALS['query'] .= "WHERE ";
                                            $GLOBALS['query'] .= "     `AreaID`=$area_id ";
                                            $GLOBALS['query'] .= " AND DATE(`StartDate`) >= '" . date('Y-m-d', strtotime($cutoff_dates[0])) . "'";
                                            mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                                            if(has_no_db_error('for deleting related cutoff cache')) {
                                                $release_updated = new Release($release_id, 'for getting newly updated Release');
                                                parent::update_helper($release_updated, $release->get_item());
                                            }
                                            else
                                                fin();
                                        }
                                        else
                                            fin();
                                    }
                                }
                                else
                                    fin();
                            }
                            else
                                fin();
                        }
                    }
                }
            }


            /****************************************************************************************************
             * Release :: delete
             *
             * Delete Release item for delete.js
             * @access public
             *
             */
            public function delete() {
                if(false) {
                    // TODO :: Related records check
                }
                else {
                    // delete cutoff cache
                    require INDEX . 'php/models/Posting.php';
                    $cutoff_dates = Posting::get_weekly_posting_dates(date('F d, Y', strtotime($this->get_data('release_date'))));
                    $GLOBALS['query']  = "DELETE FROM ";
                    $GLOBALS['query'] .= " `lending_cutoff_cache` ";
                    $GLOBALS['query'] .= "WHERE ";
                    $GLOBALS['query'] .= "     `AreaID`=" . $this->get_data('area')['id'] . " ";
                    $GLOBALS['query'] .= " AND DATE(`StartDate`) >= '" . date('Y-m-d', strtotime($cutoff_dates[0])) . "'";
                    mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                    if(has_no_db_error('for deleting related cutoff cache'))
                        parent::delete_helper($this);
                }
            }


            /****************************************************************************************************
             * Release :: search
             *
             * Search through Release records
             * @access public static
             *
             * @param string $q - the search query
             *
             */
            public static function search($q) {
                $q = mysqli_real_escape_string($GLOBALS['con'], $q);

                require INDEX . 'php/models/PhCitizen.php';
                require INDEX . 'php/models/Area.php';
                $GLOBALS['query']  = "SELECT ";
                $GLOBALS['query'] .= " `" . self::$table . "`.`" . self::$primary_key . "` ";
                $GLOBALS['query'] .= "FROM ";
                $GLOBALS['query'] .= " `" . self::$table . "`, ";
                $GLOBALS['query'] .= " `" . PhCitizen::$table . "` ";
                $GLOBALS['query'] .= "WHERE ";
                $GLOBALS['query'] .= "     `" . self::$table . "`.`" . PhCitizen::$foreign_key . "`=`" . PhCitizen::$table . "`.`" . PhCitizen::$primary_key . "` ";
                $GLOBALS['query'] .= " AND ( ";
                $GLOBALS['query'] .= "     `" . self::$table . "`.`" . self::$primary_key . "`=" . intval($q) . " ";
                $GLOBALS['query'] .= "   OR `" . PhCitizen::$table . "`.`" . PhCitizen::$primary_key . "`=" . intval($q) . " ";
                $GLOBALS['query'] .= "   OR `" . PhCitizen::$table . "`.`AccountNumber` LIKE '%$q%' ";
                $GLOBALS['query'] .= "   OR `" . PhCitizen::$table . "`.`FirstName` LIKE '%$q%' ";
                $GLOBALS['query'] .= "   OR `" . PhCitizen::$table . "`.`MiddleName` LIKE '%$q%' ";
                $GLOBALS['query'] .= "   OR `" . PhCitizen::$table . "`.`LastName` LIKE '%$q%' ";
                $GLOBALS['query'] .= "   OR CONCAT(`" . PhCitizen::$table . "`.`Title`, ' ', `" . PhCitizen::$table . "`.`FirstName`, ' ', `" . PhCitizen::$table . "`.`LastName`, ' ', `" . PhCitizen::$table . "`.`NameSuffix`) LIKE '%$q%' ";
                $GLOBALS['query'] .= "   OR CONCAT(`" . PhCitizen::$table . "`.`Title`, ' ', `" . PhCitizen::$table . "`.`FirstName`, ' ', `" . PhCitizen::$table . "`.`MiddleName`, ' ', `" . PhCitizen::$table . "`.`LastName`, ' ', `" . PhCitizen::$table . "`.`NameSuffix`) LIKE '%$q%' ";
                $GLOBALS['query'] .= "   OR CONCAT(`" . PhCitizen::$table . "`.`Title`, ' ', `" . PhCitizen::$table . "`.`FirstName`, ' ', MID(`" . PhCitizen::$table . "`.`MiddleName`, 1, 1), '. ', `" . PhCitizen::$table . "`.`LastName`, ' ', `" . PhCitizen::$table . "`.`NameSuffix`) LIKE '%$q%' ";
                $GLOBALS['query'] .= "   OR CONCAT(`" . PhCitizen::$table . "`.`LastName`, ' ', `" . PhCitizen::$table . "`.`FirstName`, ', ', `" . PhCitizen::$table . "`.`NameSuffix`) LIKE '%$q%' ";
                $GLOBALS['query'] .= "   OR CONCAT(`" . PhCitizen::$table . "`.`LastName`, ', ', `" . PhCitizen::$table . "`.`FirstName`, ', ', `" . PhCitizen::$table . "`.`NameSuffix`) LIKE '%$q%' ";
                $GLOBALS['query'] .= "   OR CONCAT(`" . PhCitizen::$table . "`.`LastName`, ', ', `" . PhCitizen::$table . "`.`FirstName`, ' ', `" . PhCitizen::$table . "`.`MiddleName`, ', ', `" . PhCitizen::$table . "`.`NameSuffix`) LIKE '%$q%' ";
                $GLOBALS['query'] .= "   OR CONCAT(`" . PhCitizen::$table . "`.`LastName`, ', ', `" . PhCitizen::$table . "`.`FirstName`, ' ', MID(`" . PhCitizen::$table . "`.`MiddleName`, 1, 1), '.', ', ', `" . PhCitizen::$table . "`.`NameSuffix`) LIKE '%$q%' ";
                $GLOBALS['query'] .= " ) ";
                $GLOBALS['query'] .= "ORDER BY ";
                $GLOBALS['query'] .= " `" . self::$table . "`.`" . PhCitizen::$foreign_key . "`, ";
                $GLOBALS['query'] .= " CAST(`" . self::$table . "`.`Cycle` AS UNSIGNED) DESC ";
                parent::search_helper(get_class());
            }


            /****************************************************************************************************
             * Release :: callback
             *
             * Get additional Release data
             * @access public static
             *
             * @param string $param - the callback parameter
             * @param string $value - the callback value
             * @param array  $vals  - the callback other values
             *
             */
            public static function callback($param, $value, $vals) {
                // create new cycle for a citizen
                if($param == 'cycle_for_release') {
                    $GLOBALS['query']  = "SELECT ";
                    $GLOBALS['query'] .= " `" . self::$primary_key . "`, ";
                    $GLOBALS['query'] .= " CAST(`Cycle` AS UNSIGNED) AS Cycle ";
                    $GLOBALS['query'] .= "FROM ";
                    $GLOBALS['query'] .= " `" . self::$table . "`";
                    $GLOBALS['query'] .= "WHERE ";
                    $GLOBALS['query'] .= " `CitizenID`=$value ";
                    $GLOBALS['query'] .= "ORDER BY Cycle";
                    $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                    if(has_no_db_error('for creating new cycle for a citizen')) {
                        $is_eligible_for_release = true;
                        $cycle      = 0;
                        $cycle_info = "<b class='text-success'>LOAN CYCLE</b>.";
                        while($row = mysqli_fetch_assoc($result)) {
                            $cycle   = $row['Cycle'];
                            $release = new Release($row[self::$primary_key], 'for checking if a Release is completed upon creating new cycle for a citizen');

                            $last_payment_date = date('F d, Y', CURRENT_TIME);
                            $GLOBALS['query']  = "SELECT `Date` FROM `lending_loan_collections` ";
                            $GLOBALS['query'] .= "WHERE `" . self::$foreign_key . "`=" . $row[self::$primary_key] . " ";
                            $GLOBALS['query'] .= "ORDER BY DATE(`Date`) DESC ";
                            $GLOBALS['query'] .= "LIMIT 1";
                            $result2 = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                            if(has_no_db_error('for getting last payment date of Release')) {
                                while($row2  = mysqli_fetch_assoc($result2)) {
                                    $last_payment_date = date('F d, Y', strtotime($row2['Date']));
                                }
                            }
                            else
                                fin();

                            require INDEX . 'php/models/Posting.php';
                            $release->set_data('collection_dates', Posting::get_weekly_posting_dates($last_payment_date));
                            $collection_data = $release->gather_collection_data();
                            if($collection_data['balance'] > 0) {
                                $is_eligible_for_release = false;
                                $cycle_info = '<b class="text-success"> CYCLE ' . $release->get_data('cycle') . '</b> with a balance of <b class="text-success">' . number_format($collection_data['balance']) . '</b>.';
                                break;
                            }
                        }
                        if(!$is_eligible_for_release) {
                            require INDEX . 'php/models/PhCitizen.php';
                            $citizen = new PhCitizen($value, 'for creating new cycle for a citizen');
                            $GLOBALS['response']['success']['message'] = 'UNABLE TO UPDATE CYCLE';
                            $GLOBALS['response']['success']['sub_message'] = '<b class="text-success">' . $citizen->get_data('full_name_3') . '</b> has an ongoing ' . $cycle_info . '<br>Please select another citizen.';
                        }
                        else {
                            $GLOBALS['response']['success']['data'] = ($cycle + 1);
                        }
                    }
                }

                // get release collection form
                else if($param == 'get_release_collection') {
                    $release = new Release($value, 'for getting collection form');
                    $collection_data = $release->get_collection_data();

                    $GLOBALS['response']['success']['data'] = array(
                        'id'    => 'callback-' . str_replace(' ', '', strtolower(self::$tab_singular)) . '-' . $value,
                        'forms' => array(
                            array(
                                'class'  => 'form forms form-collection',
                                'forms'  => self::get_collection_form($collection_data, $release->get_data())
                            )
                        )
                    );
                }
            }


            /****************************************************************************************************
             * Release :: insert_default_collection_during
             *
             * Insert default collection of 0 for Release during specified date
             * @access public
             *
             * @param string $date - the date of the collection
             *
             * @return array
             *
             */
            public function insert_default_collection_during($date) {
                $release    = $this;
                $release_id = $release->get_data('id');

                $GLOBALS['query']  = "SELECT `ID` FROM `lending_loan_collections` ";
                $GLOBALS['query'] .= "WHERE `" . self::$foreign_key . "`=$release_id AND `Date`='$date'";
                $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                if(has_no_db_error('for getting first collection for Release')) {
                    if (mysqli_num_rows($result) <= 0) {
                        $GLOBALS['query'] = "INSERT INTO ";
                        $GLOBALS['query'] .= " `lending_loan_collections`(`ReleaseID`, `Amount`, `Date`) ";
                        $GLOBALS['query'] .= "VALUES ";
                        $GLOBALS['query'] .= " (" . $release_id . ", 0, '" . $date . "') ";
                        mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                        if(!has_no_db_error('for inserting first collection for Release')) {
                            fin();
                            exit();
                        }
                    }
                }
            }


            /****************************************************************************************************
             * Release :: gather_collection_data
             *
             * Get the collection data of Release
             * @access public
             *
             * @param string $collection_date - optional collection date
             *
             * @return array
             *
             */
            public function gather_collection_data($collection_date = '') {
                $release  = $this;
                $is_ghost = $release->get_data('is_ghost');

                // get dates this week
                $dates   = $release->get_data('collection_dates');
                $n_dates = sizeof($dates);

                // prepare $arr to be returned
                $arr = array(
                    'collections' => array(),
                               // => will add additional other key-value pairs below
                );

                // loop through dates this week
                for($i=0; $i<sizeof($dates); $i++) {
                    $date_int = strtotime($dates[$i]);

                    // $collection
                    $collection = 0;
                    $GLOBALS['query']  = "SELECT ";
                    $GLOBALS['query'] .= " `Amount` ";
                    $GLOBALS['query'] .= "FROM ";
                    $GLOBALS['query'] .= " `lending_loan_collections` ";
                    $GLOBALS['query'] .= "WHERE ";
                    $GLOBALS['query'] .= "     `ReleaseID`=" . $release->get_data('id') . " ";
                    $GLOBALS['query'] .= " AND `Date` = DATE('" . date('Y-m-d', strtotime($dates[$i])) . "')";
                    $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                    if(has_no_db_error('for getting Release collection during specified date')) {
                        while($row = mysqli_fetch_assoc($result)) {
                            $collection += doubleval($row['Amount']);
                        }
                    }
                    else {
                        fin();
                        break;
                    }

                    $arr_temp = array(
                        'date'    => date('M.d', $date_int),
                        'amount'  => $collection,
                        'enabled' => true,
                        'active'  => false,
                    );
                    if ($collection_date != '') {
                        $bool = $date_int == strtotime($collection_date);
                        $arr_temp['enabled'] = $bool;
                        $arr_temp['active']  = $bool;
                    }
                    array_push($arr['collections'], $arr_temp);

                    // when last day of this week is reached
                    if($i == $n_dates-1) {
                        // initialize mysql-format start and end date
                        $start_date = date('Y-m-d', strtotime($dates[0]));
                        $end_date   = date('Y-m-d', strtotime($dates[$n_dates-1]));

                        // query for stored cache in the database
                        $GLOBALS['query']  = "SELECT * FROM `lending_loan_cache` ";
                        $GLOBALS['query'] .= "WHERE ";
                        $GLOBALS['query'] .= "     `" . self::$foreign_key . "`=" . $release->get_data('id') . " ";
                        $GLOBALS['query'] .= " AND `StartDate`='$start_date' ";
                        $GLOBALS['query'] .= " AND `EndDate`='$end_date' ";
                        $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                        if(has_no_db_error('for getting Release cache')) {
                            // initialize cache variables
                            $loan_amount       = 0;
                            $release_date      = '';
                            $is_restructured   = false;
                            $total_collection  = 0;
                            $balance           = 0;
                            $daily_payment     = 0;
                            $countdown         = 0;
                            $total             = 0;
                            $unpaid            = 0;
                            $overpaid          = 0;
                            $due               = 0;
                            $overdue           = 0;
                            $advance           = 0;
                            $collectibles      = 0;
                            $is_new_account    = 0;
                            $total             = 0;

                            // cache is found :: overwrite cache variables with values from database
                            if(mysqli_num_rows($result) > 0) {
                                $row = mysqli_fetch_assoc($result);
                                $loan_amount       = $row['LoanAmount'];
                                $release_date      = $row['ReleaseDate'];
                                $is_restructured   = $row['IsRestructured'];
                                $total_collection  = $row['TotalCollection'];
                                $balance           = $row['Balance'];
                                $daily_payment     = $row['DailyPayment'];
                                $countdown         = $row['Countdown'];
                                $total             = $row['Total'];
                                $unpaid            = $row['Unpaid'];
                                $overpaid          = $row['Overpaid'];
                                $due               = $row['Due'];
                                $overdue           = $row['Overdue'];
                                $advance           = $row['Advance'];
                                $collectibles      = $row['Collectibles'];
                                $is_new_account    = $row['IsNewAccount'];

                                // enable or disable collection input field
                                $release_date_int = strtotime($release_date);
                                for($j=0; $j<$n_dates; $j++) {
                                    if($release_date_int >= strtotime($dates[$j]))
                                        $arr['collections'][$j]['enabled'] = false;
                                    else
                                        break;
                                }
                            }

                            // cache is not found :: overwrite cache variables with processed values
                            else {
                                $offer           = $release->get_data('offer');
                                $restructures    = $release->get_data('restructures');
                                $loan_amount     = $release->get_data('amount');
                                $release_date    = $release->get_data('release_date');
                                $is_restructured = false;

                                // get amount_loaned, is_restructured, daily, countdown
                                if(!$release->get_data('is_previous')) {
                                    for ($j = sizeof($restructures) - 1; $j > 0; $j--) {
                                        if (strtotime($restructures[$j]['date']) <= $date_int) {
                                            $offer = $restructures[$j]['offer'];
                                            $loan_amount = $restructures[$j]['amount'];
                                            $release_date = $restructures[$j]['date'];
                                            $is_restructured = true;
                                            break;
                                        }
                                    }
                                }

                                $release_date_int  = strtotime($release_date);
                                $daily_payment     = $loan_amount / $offer['collection_count'];
                                $maturity_date     = date('Y-m-d', (strtotime($release_date) + ($offer['collection_count'] * $offer['collection_type_day_increment'] * __init::$DAY_TOTAL_SECONDS)));
                                $maturity_date_int = strtotime($maturity_date);
                                $countdown         = ($maturity_date_int - $date_int) / (__init::$DAY_TOTAL_SECONDS * $offer['collection_type_day_increment']);

                                /****************************************************************************************************
                                 * UPDATE Aug. 02, 2021
                                 * If $is_restructured, determine if $release_date falls within the cutoff,
                                 * then gather_collection_data of previous release to adjust $overpaid or $unpaid
                                 */
                                if($is_restructured) {
                                    $first_day_int = strtotime($dates[0]);
                                    $last_day_int  = strtotime($dates[$n_dates-1]);
                                    if ($release_date_int >= $first_day_int && $release_date_int < $last_day_int) {
                                        $previous_release = $release;
                                        $previous_collection_dates = array();
                                        for($d = $first_day_int; $d <= $release_date_int; $d += __init::$DAY_TOTAL_SECONDS) {
                                            array_push($previous_collection_dates, date('F d, Y', $d));
                                        }
                                        $previous_release->set_data('collection_dates', $previous_collection_dates);
                                        $previous_release->set_data('is_previous', true);
                                        $previous_collection_data = $previous_release->gather_collection_data();

                                        // prepare overpaid
                                        $overpaid -= $previous_collection_data['total'];
                                        if($overpaid < 0) {
                                            $unpaid   = $overpaid * -1;
                                            $overpaid = 0;
                                        }
                                    }
                                }
                                /***************************************************************************************************/

                                // $total_collection
                                $total_collection = 0;
                                $GLOBALS['query']  = "SELECT ";
                                $GLOBALS['query'] .= " SUM(`Amount`) AS TotalCollection ";
                                $GLOBALS['query'] .= "FROM ";
                                $GLOBALS['query'] .= " `lending_loan_collections` ";
                                $GLOBALS['query'] .= "WHERE ";
                                $GLOBALS['query'] .= "     `ReleaseID`=" . $release->get_data('id') . " ";
                                $GLOBALS['query'] .= " AND `Date` > DATE('" . date('Y-m-d',  $release_date_int) . "') ";
                                $GLOBALS['query'] .= " AND `Date` <= DATE('" . date('Y-m-d', $date_int) . "') ";
                                $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                                if(has_no_db_error('for getting Release total collections as of date')) {
                                    while($row = mysqli_fetch_assoc($result)) {
                                        $total_collection = $row['TotalCollection'];
                                    }
                                }
                                else
                                    fin();

                                // $total and $due
                                //$total = 0;
                                //$due   = 0;
                                $total_prev = 0;
                                for($j=0; $j<$n_dates; $j++) {
                                    $total += $arr['collections'][$j]['amount'];
                                    if($release_date_int < strtotime($dates[$j])) {
                                        if ($maturity_date_int >= strtotime($dates[$j]))
                                            $due += $daily_payment;
                                    }
                                    else {
                                        $arr['collections'][$j]['enabled'] = false;
                                        $total_collection += $arr['collections'][$j]['amount'];
                                        $total_prev       += $arr['collections'][$j]['amount'];
                                    }
                                }

                                // $balance
                                $balance = ($loan_amount + $total_prev) - $total_collection;

                                // $overdue
                                $overdue = 0;
                                require INDEX . 'php/models/Posting.php';
                                $dates_prev_last_day_int = strtotime($dates[0]) - __init::$DAY_TOTAL_SECONDS;
                                if($dates_prev_last_day_int > $maturity_date_int) {
                                    // get date of most recent payment
                                    $GLOBALS['query']  = "SELECT ";
                                    $GLOBALS['query'] .= " `Date` ";
                                    $GLOBALS['query'] .= "FROM ";
                                    $GLOBALS['query'] .= " `lending_loan_collections` ";
                                    $GLOBALS['query'] .= "WHERE ";
                                    $GLOBALS['query'] .= "     `" . Release::$foreign_key . "`=" . $release->get_data('id') . " ";
                                    $GLOBALS['query'] .= " AND `Amount` != 0 ";
                                    $GLOBALS['query'] .= " AND `Date` > DATE('" . date('Y-m-d', $maturity_date_int) . "')";
                                    $GLOBALS['query'] .= " AND `Date` <= DATE('" . date('Y-m-d', $dates_prev_last_day_int) . "')";
                                    $GLOBALS['query'] .= "ORDER BY ";
                                    $GLOBALS['query'] .= " `Date` DESC ";
                                    $GLOBALS['query'] .= "LIMIT 1";
                                    $result2 = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                                    if(has_no_db_error('for getting date of most recent payment')) {
                                        $dates_prev_last_day_int = $maturity_date_int;
                                        while($row2 = mysqli_fetch_assoc($result2)) {
                                            $dates_prev_last_day_int = strtotime($row2['Date']);
                                        }
                                    }
                                    else {
                                        fin();
                                        break;
                                    }
                                }
                                $dates_prev   = Posting::get_weekly_posting_dates(date('F d, Y', $dates_prev_last_day_int));
                                $n_dates_prev = sizeof($dates_prev);
                                if($release_date_int < strtotime($dates_prev[$n_dates_prev-1])) {
                                    // get cached 'Unpaid' value
                                    $GLOBALS['query']  = "SELECT ";
                                    $GLOBALS['query'] .= " `Unpaid`, ";
                                    $GLOBALS['query'] .= " `Overpaid` ";
                                    $GLOBALS['query'] .= "FROM ";
                                    $GLOBALS['query'] .= " `lending_loan_cache` ";
                                    $GLOBALS['query'] .= "WHERE ";
                                    $GLOBALS['query'] .= "     `" . self::$foreign_key . "`=" . $release->get_data('id') . " ";
                                    $GLOBALS['query'] .= " AND `StartDate`='" . date('Y-m-d', strtotime($dates_prev[0])) . "' ";
                                    $GLOBALS['query'] .= " AND `EndDate`='" . date('Y-m-d', strtotime($dates_prev[$n_dates_prev-1])) . "'";
                                    $result_cache = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                                    if(has_no_db_error('for getting cached Unpaid value of Release')) {
                                        if(mysqli_num_rows($result_cache) > 0) {
                                            // 'Unpaid' value found :: set as $overdue
                                            $row_cache = mysqli_fetch_assoc($result_cache);
                                            $overdue   = $row_cache['Unpaid'];
                                            $advance   = $row_cache['Overpaid'];
                                        }
                                        else {
                                            // 'Unpaid' value not found :: instantiate ghost Release
                                            $release_prev = $release;
                                            $release_prev->set_data('is_ghost', true);
                                            $release_prev->set_data('collection_dates', $dates_prev);
                                            $release_prev->gather_collection_data();
                                            $release_prev_collection_data = $release_prev->get_data('collection_data');
                                            $overdue = $release_prev_collection_data['unpaid'];
                                            $advance = $release_prev_collection_data['overpaid'];
                                        }
                                    }
                                    else {
                                        fin();
                                        break;
                                    }
                                }

                                // --- UPDATE --------------------------------------------------------------------------
                                if($advance >= $due) {
                                    $overpaid += ($advance - $due);
                                    $due = 0;
                                }
                                else
                                    $due -= $advance;
                                // -------------------------------------------------------------------------------------

                                // $collectibles
                                $collectibles = $due + $overdue;

                                // $unpaid and $overpaid
                                $unpaid += $collectibles - $total;
                                if($unpaid < 0) {
                                    $overpaid += ($unpaid * -1);
                                    $unpaid    = 0;
                                }

                                // $is_new_account
                                $is_new_account = ($release_date_int >= strtotime($dates[0]) && $release_date_int <= $date_int);

                                // delete previous cache, if any
                                $GLOBALS['query']  = "DELETE FROM ";
                                $GLOBALS['query'] .= " `lending_loan_cache` ";
                                $GLOBALS['query'] .= "WHERE ";
                                $GLOBALS['query'] .= "     `" . self::$foreign_key . "`=" . $release->get_data('id') . " ";
                                $GLOBALS['query'] .= " AND `StartDate`='$start_date' ";
                                $GLOBALS['query'] .= " AND `EndDate`='$end_date'";
                                mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                                if(has_no_db_error('for deleting previous Release cache')) {
                                    // insert new cache
                                    $GLOBALS['query']  = "INSERT INTO ";
                                    $GLOBALS['query'] .= " `lending_loan_cache` ";
                                    $GLOBALS['query'] .= " (`ReleaseID`, `StartDate`, `EndDate`, `LoanAmount`, `ReleaseDate`, `IsRestructured`, `TotalCollection`, `Balance`, `DailyPayment`, `Countdown`, `Total`, `Unpaid`, `Overpaid`, `Due`, `Overdue`, `Advance`, `Collectibles`, `IsNewAccount`) ";
                                    $GLOBALS['query'] .= "VALUES ";
                                    $GLOBALS['query'] .= " (" . $release->get_data('id') . ", '$start_date', '$end_date', $loan_amount, '$release_date', " . intval($is_restructured) . ", " . ($total_collection == '' ? 0 : $total_collection) . ", $balance, $daily_payment, $countdown, $total, $unpaid, $overpaid, $due, $overdue, $advance, $collectibles, " . intval($is_new_account) . ") ";

                                    mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                                    if(!has_no_db_error('for inserting new Release cache')) {
                                        fin();
                                        break;
                                    }
                                }
                                else {
                                    fin();
                                    break;
                                }
                            }

                            // save cache variables to $arr
                            $arr['loan_amount']      = $loan_amount;
                            $arr['release_date']     = $release_date;
                            $arr['is_restructured']  = $is_restructured;
                            $arr['total']            = $total;
                            $arr['total_collection'] = $total_collection;
                            $arr['balance']          = $balance;
                            $arr['daily_payment']    = $daily_payment;
                            $arr['countdown']        = $countdown;
                            $arr['unpaid']           = $unpaid;
                            $arr['overpaid']         = $overpaid;
                            $arr['due']              = $due;
                            $arr['overdue']          = $overdue;
                            $arr['advance']          = $advance;
                            $arr['collectibles']     = $collectibles;
                            $arr['is_new_account']   = $is_new_account;
                        }
                        else {
                            fin();
                            break;
                        }
                    }
                }

                $release->set_data('collection_data', $arr);
                return $arr;
            }


            /****************************************************************************************************
             * Release :: get_total_collection
             *
             * Get the total collection from a Release
             * @access public
             *
             * @return double
             *
             */
            public function get_total_collection() {
                $GLOBALS['query']  = "SELECT ";
                $GLOBALS['query'] .= " SUM(`Amount`) AS TotalCollection ";
                $GLOBALS['query'] .= "FROM ";
                $GLOBALS['query'] .= " `lending_loan_collections` ";
                $GLOBALS['query'] .= "WHERE ";
                $GLOBALS['query'] .= " `ReleaseID`=" . $this->get_data('id');
                $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                if(has_no_db_error('for getting total collection from a Release')) {
                    $total_collection = 0;
                    while($row = mysqli_fetch_assoc($result)) {
                        $total_collection = $row['TotalCollection'];
                    }
                    return doubleval($total_collection);
                }
            }

            /****************************************************************************************************
             * Release :: get_release_date_as_of
             *
             * Get release date as of a given date string
             * @access public
             *
             * @param string $date - the date string (ex: 'January 1, 2021')
             *
             * @return string
             *
             */
            public function get_release_date_as_of($date) {
                $date_released = $this->get_data('release_date');
                $restructures  = $this->get_data('restructures');
                for($i=sizeof($restructures)-1; $i>0; $i--) {
                    if(strtotime($restructures[$i]['date']) < strtotime($date)) {
                        $date_released = $restructures[$i]['date'];
                        break;
                    }
                }
                return $date_released;
            }


            /****************************************************************************************************
             * Release :: get_maturity_date_as_of
             *
             * Get maturity date as of a given date string
             * @access public
             *
             * @param string $date - the date string (ex: 'January 1, 2021')
             *
             * @return string
             *
             */
            public function get_maturity_date_as_of($date) {
                $release_date = $this->get_release_date_as_of($date);
                $offer        = $this->get_offer_as_of($date);
                return (date('Y-m-d', (strtotime($release_date) + ($offer['collection_count'] * $offer['collection_type_day_increment'] * __init::$DAY_TOTAL_SECONDS))));
            }


            /****************************************************************************************************
             * Release :: get_offer_as_of
             *
             * Get offer as of a given date string
             * @access public
             *
             * @param string $date - the date string (ex: 'January 1, 2021')
             *
             * @return array
             *
             */
            public function get_offer_as_of($date) {
                $offer        = $this->get_data('offer');
                $restructures = $this->get_data('restructures');
                for($i=sizeof($restructures)-1; $i>0; $i--) {
                    if(strtotime($restructures[$i]['date']) < strtotime($date)) {
                        $offer = $restructures[$i]['offer'];
                        break;
                    }
                }
                return $offer;
            }


            /****************************************************************************************************
             * Release :: get_is_restructured_as_of
             *
             * Get if release is restructured as of a given date string
             * @access public
             *
             * @param string $date - the date string (ex: 'January 1, 2021')
             *
             * @return bool
             *
             */
            public function get_is_restructured_as_of($date) {
                $bool = false;
                $restructures = $this->get_data('restructures');
                for($i=sizeof($restructures)-1; $i>0; $i--) {
                    if(strtotime($restructures[$i]['date']) < strtotime($date)) {
                        $bool = true;
                        break;
                    }
                }
                return $bool;
            }


            /****************************************************************************************************
             * Release :: get_amount_loaned_as_of
             *
             * Get amount loaned as of a given date string
             * @access public
             *
             * @param string $date - the date string (ex: 'January 1, 2021')
             *
             * @return double
             *
             */
            public function get_amount_loaned_as_of($date) {
                $amount       = $this->get_data('amount');
                $restructures = $this->get_data('restructures');
                for($i=sizeof($restructures)-1; $i>0; $i--) {
                    if(strtotime($restructures[$i]['date']) < strtotime($date)) {
                        $amount = $restructures[$i]['amount'];
                        break;
                    }
                }
                return $amount;
            }


            /****************************************************************************************************
             * Release :: get_area_id_as_of
             *
             * Get area id as of a given date string
             * @access public
             *
             * @param string $date - the date string (ex: 'January 1, 2021')
             *
             * @return int
             *
             */
            public function get_area_id_as_of($date) {
                $area_id = $this->get_data('area')['id'];
                $GLOBALS['query']  = "SELECT ";
                $GLOBALS['query'] .= " `AreaID` ";
                $GLOBALS['query'] .= "FROM ";
                $GLOBALS['query'] .= " `lending_loan_collections` ";
                $GLOBALS['query'] .= "WHERE ";
                $GLOBALS['query'] .= "     `ReleaseID`=" . $this->get_data('id') . " ";
                $GLOBALS['query'] .= " AND `Date` = DATE('" . date('Y-m-d', strtotime($date)) . "')";
                $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                if(has_no_db_error('for getting Release collection date as of date')) {
                    while($row = mysqli_fetch_assoc($result)) {
                        $area_id = $row['AreaID'];
                    }
                }
                else
                    fin();
                return $area_id;
            }


            /****************************************************************************************************
             * Release :: get_daily_payment_as_of
             *
             * Get daily payment as of a given date string
             * @access public
             *
             * @param string $date - the date string (ex: 'January 1, 2021')
             *
             * @return double
             *
             */
            public function get_daily_payment_as_of($date) {
                $offer  = $this->get_offer_as_of($date);
                $amount = $this->get_amount_loaned_as_of($date);
                return ($amount / $offer['collection_count']);
            }


            /****************************************************************************************************
             * Release :: get_collection_during
             *
             * Get collection during a given date string
             * @access public
             *
             * @param string $date - the date string (ex: 'January 1, 2021')
             *
             * @return double
             *
             */
            public function get_collection_during($date) {
                $collection = 0;
                $GLOBALS['query']  = "SELECT ";
                $GLOBALS['query'] .= " `Amount` ";
                $GLOBALS['query'] .= "FROM ";
                $GLOBALS['query'] .= " `lending_loan_collections` ";
                $GLOBALS['query'] .= "WHERE ";
                $GLOBALS['query'] .= "     `ReleaseID`=" . $this->get_data('id') . " ";
                $GLOBALS['query'] .= " AND `Date` = DATE('" . date('Y-m-d', strtotime($date)) . "')";
                $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                if(has_no_db_error('for getting Release collection during date')) {
                    while($row = mysqli_fetch_assoc($result)) {
                        $collection += doubleval($row['Amount']);
                    }
                }
                else
                    fin();
                return $collection;
            }


            /****************************************************************************************************
             * Release :: get_total_collection_as_of
             *
             * Get total collection as of a given date string
             * @access public
             *
             * @param string $date - the date string (ex: 'January 1, 2021')
             *
             * @return double
             *
             */
            public function get_total_collection_as_of($date) {
                $total_collection = 0;
                $GLOBALS['query']  = "SELECT ";
                $GLOBALS['query'] .= " `Amount` ";
                $GLOBALS['query'] .= "FROM ";
                $GLOBALS['query'] .= " `lending_loan_collections` ";
                $GLOBALS['query'] .= "WHERE ";
                $GLOBALS['query'] .= "     `ReleaseID`=" . $this->get_data('id') . " ";
                $GLOBALS['query'] .= " AND `Date` >= DATE('" . date('Y-m-d', strtotime($this->get_release_date_as_of($date))) . "') ";
                $GLOBALS['query'] .= " AND `Date` <= DATE('" . date('Y-m-d', strtotime($date)) . "') ";
                $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                if(has_no_db_error('for getting Release total collections as of date')) {
                    while($row = mysqli_fetch_assoc($result)) {
                        $total_collection += doubleval($row['Amount']);
                    }
                }
                else
                    fin();
                return $total_collection;
            }


            /****************************************************************************************************
             * Release :: get_balance_as_of
             *
             * Get balance as of a given date string
             * @access public
             *
             * @param string $date - the date string (ex: 'January 1, 2021')
             *
             * @return double
             *
             */
            public function get_balance_as_of($date) {
                return ($this->get_amount_loaned_as_of($date) - $this->get_total_collection_as_of($date));
            }


            /****************************************************************************************************
             * Release :: get_countdown_as_of
             *
             * Get countdown as of a given date string
             * @access public
             *
             * @param string $date - the date string (ex: 'January 1, 2021')
             *
             * @return int
             *
             */
            public function get_countdown_as_of($date) {
                $offer            = $this->get_offer_as_of($date);
                $collection_count = $offer['collection_count'];
                $day_increment    = $offer['collection_type_day_increment'];
                $maturity_date    = $this->get_maturity_date_as_of($date);
                return ((strtotime($maturity_date) - strtotime($date)) / (__init::$DAY_TOTAL_SECONDS * $day_increment));
            }


            /****************************************************************************************************
             * Release :: get_total_collection_within
             *
             * Get total collection within specified date strings
             * @access public
             *
             * @param array $dates - the date strings to compute total collection from
             *
             * @return double
             *
             */
            public function get_total_collection_within($dates) {
                $total_collection = 0;
                for($i=0; $i<sizeof($dates); $i++) {
                    $total_collection += $this->get_collection_during($dates[$i]);
                }
                return $total_collection;
            }


            /****************************************************************************************************
             * Release :: get_due_within
             *
             * Get amount due within specified date strings
             * @access public
             *
             * @param array $dates - the date strings to compute amount due from
             *
             * @return double
             *
             */
            public function get_due_within($dates) {
                $due = 0;
                $release_date      = $this->get_release_date_as_of($dates[sizeof($dates)-1]);
                $release_date_int  = strtotime($release_date);
                $maturity_date     = $this->get_maturity_date_as_of(date('F d, Y', $release_date_int + __init::$DAY_TOTAL_SECONDS));
                $maturity_date_int = strtotime($maturity_date);

                if(strtotime($dates[0]) < $maturity_date_int) {
                    for($i=0; $i<sizeof($dates); $i++) {
                        $date_int = strtotime($dates[$i]);
                        if(($date_int > strtotime($this->get_release_date_as_of($dates[$i]))) && ($date_int <= strtotime($this->get_maturity_date_as_of($dates[$i]))))
                            $due += $this->get_daily_payment_as_of($dates[$i]);
                    }
                }

                return $due;
            }


            /****************************************************************************************************
             * Release :: get_overdue_within
             *
             * Get amount overdue within specified date strings
             * @access public
             *
             * @param array $dates - the date strings to compute amount overdue from
             *
             * @return double
             *
             */
            function get_overdue_within($dates) {
                require INDEX . 'php/models/Posting.php';
                $last_cutoff_dates = Posting::get_weekly_posting_dates(date('F d, Y', strtotime($dates[0]) - __init::$DAY_TOTAL_SECONDS));
                $release_date      = $this->get_release_date_as_of($dates[sizeof($dates)-1]);
                if(strtotime($release_date) < strtotime($last_cutoff_dates[sizeof($last_cutoff_dates)-1])) {
                    return $this->get_collectibles_within($last_cutoff_dates) - $this->get_total_collection_within($last_cutoff_dates);
                }
                else
                    return 0;
            }


            /****************************************************************************************************
             * Release :: get_collectibles_within
             *
             * Get collectibles within specified date strings
             * @access public
             *
             * @param array $dates - the date strings to compute collectibles from
             *
             * @return double
             *
             */
            public function get_collectibles_within($dates) {
                return ($this->get_due_within($dates) + $this->get_overdue_within($dates));
            }


            /****************************************************************************************************
             * Release :: get_balance
             *
             * Get balance as of the last payment made
             * @access public
             *
             * @return double
             *
             */
            public function get_balance() {
                $date = date('F d, Y', CURRENT_TIME);
                $GLOBALS['query']  = "SELECT `Date` FROM `lending_loan_collections` ";
                $GLOBALS['query'] .= "ORDER BY DATE(`Date`) DESC ";
                $GLOBALS['query'] .= "LIMIT 1";
                $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                if(has_no_db_error('for getting last payment date')) {
                    while($row  = mysqli_fetch_assoc($result)) {
                        $date = date('F d, Y', strtotime($row['Date']));
                    }
                }
                else
                    fin();
                return $this->get_balance_as_of($date);
            }
        }

        if(basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
            $object = new Release(0, 'for class usage');
            require '__fin.php';
        }
    }
?>
