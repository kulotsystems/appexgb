<?php
    require '__init.php';

    if(!class_exists('Posting')) {
        class Posting extends __init {

            // class variables
            public static $tab_singular         = 'POSTING';
            public static $table                = 'lending_collection_postings';
            public static $primary_key          = 'ID';
            public static $primary_zero_allowed = false;
            public static $list_order           = 'ORDER BY `ID`';

            public static $params = array(
                'list'   => array(
                    'key'     => 'list_posting',
                    'enabled' => true
                ),
                'select' => array(
                    'key'     => 'select_posting',
                    'enabled' => true
                ),
                'update' => array(
                    'key'     => 'update_posting',
                    'enabled' => true
                ),
                'search' => array(
                    'key'     => 'search_posting',
                    'enabled' => true
                ),
                'callback' => array(
                    'key'     => 'callback_posting',
                    'enabled' => true
                )
            );


            /****************************************************************************************************
             * Posting :: CONSTRUCTOR
             *
             * Initialize the Posting object.
             * @access public
             *
             * @param int    $posting_id - the Posting id
             * @param string $purpose    - the usage of this operation
             * @param bool   $metadata
             */
            public function __construct($posting_id = 0, $purpose = '', $metadata = true) {
                $this->data = array(
                    'id' => 0,
                );

                $posting_id = intval($posting_id);
                if(($posting_id > 0 || self::$primary_zero_allowed) && $purpose != 'for class usage') {
                    $purpose = 'getting Posting info' . cascade_purpose($purpose);
                    $GLOBALS['query']  = "SELECT ";
                    $GLOBALS['query'] .= " `Title`, ";
                    $GLOBALS['query'] .= " `Description`, ";
                    $GLOBALS['query'] .= " `LastAccessedArea`, ";
                    $GLOBALS['query'] .= " `LastAccessedDate`, ";
                    $GLOBALS['query'] .= "  DATE_FORMAT(`CreatedAt`, '%M %e, %Y &middot; %h:%i %p') AS CreatedAt, ";
                    $GLOBALS['query'] .= "  DATE_FORMAT(`UpdatedAt`, '%M %e, %Y &middot; %h:%i %p') AS UpdatedAt ";
                    $GLOBALS['query'] .= "FROM ";
                    $GLOBALS['query'] .= " `" . self::$table . "` ";
                    $GLOBALS['query'] .= "WHERE ";
                    $GLOBALS['query'] .= " `" . self::$primary_key . "`=$posting_id";
                    $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                    if (has_no_db_error($purpose)) {
                        if (mysqli_num_rows($result) > 0) {
                            $row = mysqli_fetch_assoc($result);

                            require INDEX . 'php/models/Area.php';
                            $last_accessed_area = new Area($row['LastAccessedArea'], 'for getting Posting last accessed area');
                            $this->data = array(
                                'id'                 => $posting_id,
                                'title'              => $row['Title'],
                                'description'        => $row['Description'],
                                'last_accessed_area' => $last_accessed_area->get_data(),
                                'last_accessed_date' => intval($row['LastAccessedDate']) > 0 ? $row['LastAccessedDate'] : '',
                                'collection'         => array(),
                                'date_created'       => $row['CreatedAt'],
                                'date_updated'       => $row['UpdatedAt']
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
             * Posting :: get_item
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
                    $this->get_data('title'),
                    $this->get_data('description'),
                    $this->get_data('date_updated')
                );
            }

            /****************************************************************************************************
             * Posting :: get_form
             *
             * Generate form for Posting object
             * @access public static
             *
             * @param array $data     - the item data
             * @param bool  $for_logs - for system logs or not
             *
             * @return array
             */
            public static function get_form($data, $for_logs) {
                $arr = array();

                // get_form :: WEEKLY Posting
                if($data['id'] == 1) {
                    $arr = array(
                        array(
                            'class' => 'form form-group-attached',
                            'rows'  => array(
                                array(
                                    array(
                                        'grid'     => 'col-md-5 col-sm-5 print-param',
                                        'type'     => 'searchbox',
                                        'role'     => 'dropdown',
                                        'model'    => 'Area',
                                        'id'       => 'srch-post-area',
                                        'label'    => 'POST AREA',
                                        'value'    => isset($data['last_accessed_area']['code']) ? $data['last_accessed_area']['code'] : '',
                                        'key'      => isset($data['last_accessed_area']['id']) ? $data['last_accessed_area']['id'] : 0,
                                        'disabled' => $for_logs,
                                        'trigger_callback' => 'get_data_for_posting'
                                    ),
                                    array(
                                        'grid'     => 'col-md-7 col-sm-7 print-param',
                                        'type'     => 'date',
                                        'id'       => 'txt-cutoff-date',
                                        'label'    => 'CUTOFF DATE',
                                        'value'    => isset($data['last_accessed_date']) ? $data['last_accessed_date'] : '',
                                        'required' => true,
                                        'trigger_callback' => 'get_data_for_posting'
                                    ),
                                    array(
                                        'grid'     => 'col-md-2 col-sm-2 hidden',
                                        'type'     => 'button-callback',
                                        'model'    => 'Posting',
                                        'class'    => 'btn btn-default text-success',
                                        'icon'     => 'fa-arrow-down',
                                        'id'       => 'txt-cutoff-date',
                                        'param'    => 'get_data_for_posting',
                                        'value'    => 'FILTER'
                                    )
                                )
                            )
                        ),

                        // form_data[1]
                        array(
                            'class' => 'callback',
                            'model' => 'Posting',
                            'param' => 'get_data_for_posting',
                            'id'    => 'callback-' . str_replace(' ', '', strtolower(self::$tab_singular)) . '-' . $data['id'],
                            'value' => $data['id'],
                            'hrefs' => json_encode(array('#srch-post-area', '#txt-cutoff-date')),
                            'attrs' => json_encode(array('data-key', 'value')),
                            'icon'  => 'fa-calendar',
                            'label' => 'COLLECTIONS',
                            'style' => 'padding: 6px; background-color: #eef0f0',
                            'form'  => array(
                                'class'  => 'form forms form-collection',
                                'forms'  => self::get_collection_form($data['collection'])
                            )
                        )
                    );
                }

                return $arr;
            }


            /****************************************************************************************************
             * Posting :: update
             *
             * Update Posting item for update.js
             * @access public static
             *
             */
            public static function update() {
                $posting_id = intval($_POST[self::$params['update']['key']]);
                $posting = new Posting($posting_id, 'for update');

                // update :: WEEKLY Posting
                if($posting_id == 1) {
                    $form_data = $_POST['data'];

                    // get area id and posting dates
                    $area_id       = intval($form_data[0]['rows'][0]['srch-post-area']);
                    $cutoff_date   = $form_data[0]['rows'][0]['txt-cutoff-date'];

                    // update Posting
                    $GLOBALS['query']  = "UPDATE `" . self::$table . "` ";
                    $GLOBALS['query'] .= "SET ";
                    $GLOBALS['query'] .= " `LastAccessedArea`=$area_id, ";
                    $GLOBALS['query'] .= " `LastAccessedDate`='" . date('Y-m-d', strtotime($cutoff_date)) . "' ";
                    $GLOBALS['query'] .= "WHERE ";
                    $GLOBALS['query'] .= " `" . self::$primary_key . "`=$posting_id";
                    mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                    if(has_no_db_error('for updating Posting data')) {
                        // iterate through the remaining forms
                        $posting_dates = self::get_weekly_posting_dates($cutoff_date);
                        $form_index    = 0;
                        while(true) {
                            $form_index += 1;
                            $form = $form_data[$form_index];
                            if($form['class'] == 'form-collection-end')
                                break;
                            else {
                                // get release data
                                $arr = array_values($form['rows'][0]);
                                $release_id = intval($arr[0]);

                                // get collection data
                                $form_index += 1;
                                $form = $form_data[$form_index];
                                $arr  = array_values($form['rows'][0]);
                                for($i=0; $i<sizeof($posting_dates); $i++) {
                                    $collection_amount = doubleval($arr[$i]);
                                    $collection_date   = date('Y-m-d', strtotime($posting_dates[$i]));

                                    // check if collection already exist
                                    $GLOBALS['query']  = "SELECT `ID` ";
                                    $GLOBALS['query'] .= "FROM `lending_loan_collections` ";
                                    $GLOBALS['query'] .= "WHERE ";
                                    $GLOBALS['query'] .= "     `ReleaseID`=$release_id ";
                                    $GLOBALS['query'] .= " AND `Date`='$collection_date'";
                                    $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                                    if(has_no_db_error('for checking if Release collection already exists')) {
                                        if(mysqli_num_rows($result) > 0) {
                                            $row           = mysqli_fetch_assoc($result);
                                            $collection_id = intval($row['ID']);

                                            // update collection
                                            $GLOBALS['query']  = "UPDATE `lending_loan_collections` ";
                                            $GLOBALS['query'] .= "SET ";
                                            $GLOBALS['query'] .= " `Amount`=$collection_amount ";
                                            $GLOBALS['query'] .= "WHERE `ID`=$collection_id";
                                            mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                                            if(!has_no_db_error('for updating Release collection amount')) {
                                                fin();
                                                break;
                                            }
                                        }
                                        else {
                                            // insert new collection
                                            if($collection_amount != 0) {
                                                $GLOBALS['query'] = "INSERT INTO `lending_loan_collections`(`ReleaseID`, `Amount`, `Date`) ";
                                                $GLOBALS['query'] .= "VALUES($release_id, $collection_amount, '$collection_date')";
                                                mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                                                if (!has_no_db_error('for inserting Release collection amount')) {
                                                    fin();
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                    else {
                                        fin();
                                        break;
                                    }
                                }

                                // delete stored cache of Release starting from $posting_dates[0]
                                $GLOBALS['query']  = "DELETE FROM ";
                                $GLOBALS['query'] .= " `lending_loan_cache` ";
                                $GLOBALS['query'] .= "WHERE ";
                                $GLOBALS['query'] .= "     `ReleaseID`=$release_id ";
                                $GLOBALS['query'] .= " AND `StartDate` >= DATE('" . date('Y-m-d', strtotime($posting_dates[0])) . "')";
                                mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                                if(!has_no_db_error('for deleting previous Release cache')) {
                                    fin();
                                    break;
                                }
                            }
                        }

                        // delete stored cutoff cache
                        $GLOBALS['query']  = "DELETE FROM ";
                        $GLOBALS['query'] .= " `lending_cutoff_cache` ";
                        $GLOBALS['query'] .= "WHERE ";
                        $GLOBALS['query'] .= "     `AreaID`=$area_id ";
                        $GLOBALS['query'] .= " AND DATE(`StartDate`) >= '" . date('Y-m-d', strtotime($posting_dates[0])) . "'";
                        mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                        if(has_no_db_error('for deleting related cutoff cache')) {
                            $posting_updated = new Posting($posting_id, 'for getting newly updated Posting');
                            $posting_updated->set_data('collection', $posting_updated->get_collection_data());
                            parent::update_helper($posting_updated, $posting->get_item());
                        }
                    }
                }
            }

            /****************************************************************************************************
             * Posting :: search
             *
             * Search through Posting records
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
                $GLOBALS['query'] .= "    `" . self::$primary_key . "`=" . intval($q) . " ";
                $GLOBALS['query'] .= " OR `Title` LIKE '%$q%' ";
                $GLOBALS['query'] .= " OR `Description` LIKE '%$q%' ";
                $GLOBALS['query'] .= self::$list_order;
                parent::search_helper(get_class());
            }


            /****************************************************************************************************
             * Release :: callback
             *
             * Get additional Posting data
             * @access public static
             *
             * @param string $param - the callback parameter
             * @param string $value - the callback value
             *
             */
            public static function callback($param, $value, $vals) {
                // get collections to post by area and date
                if($param == 'get_data_for_posting') {
                    require INDEX . 'php/models/Area.php';
                    $area = new Area($vals[0], 'for getting data for posting');
                    $posting = new Posting($value, 'for getting data for posting');
                    $posting->set_data('last_accessed_area', $area->get_data());
                    $posting->set_data('last_accessed_date', date('Y-m-d', strtotime($vals[1])));
                    $collection_data = $posting->get_collection_data();

                    $GLOBALS['response']['success']['data'] = array(
                        'id'    => 'callback-' . str_replace(' ', '', strtolower(self::$tab_singular)) . '-' . $value,
                        'forms' => array(
                            array(
                                'class'  => 'form forms form-collection',
                                'forms'  => self::get_collection_form($collection_data)
                            )
                        )
                    );
                }
            }


            /****************************************************************************************************
             * Posting :: get_weekly_posting_dates
             *
             * Get weekly posting dates based from the given cutoff date
             * @access public static
             *
             * @param string $cutoff_date - the cutoff date
             *
             * @return array of date strings
             *
             */
            public static function get_weekly_posting_dates($cutoff_date) {
                $cutoff_date = strtotime($cutoff_date);
                $M = date('F', $cutoff_date);
                $D = date('d', $cutoff_date);
                $Y = date('Y', $cutoff_date);
                $T = date('t', $cutoff_date);

                $weekly_cutoffs = array(
                    array( 1,  7),
                    array( 8, 15),
                    array(16, 23),
                    array(24, $T)
                );

                $posting_dates = array();
                for($i=0; $i<sizeof($weekly_cutoffs); $i++) {
                    $d = intval($D);
                    if($d >= $weekly_cutoffs[$i][0] && $d <= $weekly_cutoffs[$i][1]) {
                        for($j=$weekly_cutoffs[$i][0]; $j<=$weekly_cutoffs[$i][1]; $j++) {
                            $posting_date_str = $M.' '.$j.', ' .$Y;
                            array_push($posting_dates, $posting_date_str);
                        }
                        break;
                    }
                }
                return $posting_dates;
            }


            /****************************************************************************************************
             * Posting :: get_collection_data
             *
             * Get the collection data of Posting
             * @access public
             *
             * @return array of Release data
             *
             */
            public function get_collection_data() {
                $posting     = $this;
                $cutoff_date = date('F d, Y', strtotime($posting->get_data('last_accessed_date')));
                $area_id     = intval($posting->get_data('last_accessed_area')['id']);
                $arr         = array();

                // get_collection_data :: WEEKLY Posting
                if($posting->get_data('id') == 1) {
                    // initialize $arr that will be returned
                    $arr = array(
                        'releases'           => array(),
                        'sub_totals'         => array(),
                        'total_total'        => 0,
                        'total_due'          => 0,
                        'total_overdue'      => 0,
                        'total_advance'      => 0,
                        'total_collectibles' => 0,
                        'total_unpaid'       => 0,
                        'total_overpaid'     => 0,
                        'total_balance'      => 0,
                        'beg_balance'        => 0,
                        'new_account'        => 0,
                        'posting_dates'      => array()
                    );

                    require INDEX . 'php/models/Release.php';
                    require INDEX . 'php/models/PhCitizen.php';
                    $posting_dates = self::get_weekly_posting_dates($cutoff_date);
                    $arr['posting_dates'] = $posting_dates;

                    $first_posting_date = date('Y-m-d', strtotime($posting_dates[0]));
                    $last_posting_date  = date('Y-m-d', strtotime($posting_dates[sizeof($posting_dates)-1]));
                    $last_posting_date_plus_one_day  = date('Y-m-d', strtotime($last_posting_date) + __init::$DAY_TOTAL_SECONDS);
                    $last_posting_date_plus_two_days = date('Y-m-d', strtotime($last_posting_date) + (__init::$DAY_TOTAL_SECONDS * 2));

                    // initialize $arr['sub_totals']
                    for($i=0; $i<sizeof($posting_dates); $i++) {
                        array_push($arr['sub_totals'], 0);
                    }

                    // get Releases within $posting_dates
                    $unique_release_ids = array();
                    $GLOBALS['query']  = "SELECT ";
                    $GLOBALS['query'] .= " `" . Release::$table . "`.`" . Release::$primary_key . "`, ";
                    $GLOBALS['query'] .= " `lending_loan_amounts`.`Amount` AS ReleaseAmount, ";
                    $GLOBALS['query'] .= " `lending_loan_amounts`.`Date` AS ReleaseDate ";
                    $GLOBALS['query'] .= "FROM ";
                    $GLOBALS['query'] .= " `" . Release::$table . "`, ";
                    $GLOBALS['query'] .= " `" . PhCitizen::$table . "`, ";
                    $GLOBALS['query'] .= " `lending_loan_amounts`, ";
                    $GLOBALS['query'] .= " ( ";
                    $GLOBALS['query'] .= "   SELECT ";
                    $GLOBALS['query'] .= "     `lending_loan_collections`.`" . Release::$foreign_key . "`, ";
                    $GLOBALS['query'] .= "     `lending_loan_amounts`.`ID` AS AmountID, ";
                    $GLOBALS['query'] .= "     SUM(`lending_loan_collections`.`Amount`) AS TotalCollection, ";
                    $GLOBALS['query'] .= "     MAX(`lending_loan_collections`.`Date`) AS LastPaymentDate ";
                    $GLOBALS['query'] .= "   FROM ";
                    $GLOBALS['query'] .= "     `lending_loan_collections`, ";
                    $GLOBALS['query'] .= "     `lending_loan_amounts` ";
                    $GLOBALS['query'] .= "   WHERE ";
                    $GLOBALS['query'] .= "         `lending_loan_amounts`.`" . Release::$foreign_key . "` = `lending_loan_collections`.`" . Release::$foreign_key . "` ";
                    $GLOBALS['query'] .= "     AND `lending_loan_amounts`.`AreaID`=$area_id ";
                    $GLOBALS['query'] .= "     AND `lending_loan_collections`.`Date` > `lending_loan_amounts`.`Date` ";
                    $GLOBALS['query'] .= "     AND";
                    $GLOBALS['query'] .= "     ( ";
                    $GLOBALS['query'] .= "       ( ";
                    $GLOBALS['query'] .= "             `lending_loan_amounts`.`Date` = '" . $last_posting_date . "' ";
                    $GLOBALS['query'] .= "         AND `lending_loan_collections`.`Date` <= '" . $last_posting_date_plus_one_day . "' ";
                    $GLOBALS['query'] .= "       ) ";
                    $GLOBALS['query'] .= "       OR ";
                    $GLOBALS['query'] .= "       ( ";
                    $GLOBALS['query'] .= "             `lending_loan_amounts`.`Date` < '" . $last_posting_date . "' ";
                    $GLOBALS['query'] .= "         AND `lending_loan_collections`.`Date` <= '" . $last_posting_date . "' ";
                    $GLOBALS['query'] .= "       ) ";
                    $GLOBALS['query'] .= "     )";
                    $GLOBALS['query'] .= "   GROUP BY ";
                    $GLOBALS['query'] .= "     `lending_loan_amounts`.`" . Release::$foreign_key . "`, ";
                    $GLOBALS['query'] .= "     `lending_loan_amounts`.`Date` ";
                    $GLOBALS['query'] .= "   ORDER BY ";
                    $GLOBALS['query'] .= "     DATE(`lending_loan_amounts`.`Date`) DESC";
                    $GLOBALS['query'] .= " ) A ";
                    $GLOBALS['query'] .= "WHERE ";
                    $GLOBALS['query'] .= "     A.`" . Release::$foreign_key . "`=`" . Release::$table . "`.`" . Release::$primary_key  . "` ";
                    $GLOBALS['query'] .= " AND A.`AmountID`=`lending_loan_amounts`.`ID` ";
                    $GLOBALS['query'] .= " AND ";
                    $GLOBALS['query'] .= " ( ";
                    $GLOBALS['query'] .= "        A.`TotalCollection` < `lending_loan_amounts`.`Amount` ";
                    $GLOBALS['query'] .= "     OR ";
                    $GLOBALS['query'] .= "     ( ";
                    $GLOBALS['query'] .= "            A.`LastPaymentDate` >= '" . $first_posting_date . "' ";
                    $GLOBALS['query'] .= "        AND A.`LastPaymentDate` <= '" . $last_posting_date . "' ";
                    $GLOBALS['query'] .= "     ) ";
                    $GLOBALS['query'] .= " ) ";
                    $GLOBALS['query'] .= " AND `" . PhCitizen::$table . "`.`" . PhCitizen::$primary_key . "`=`" . Release::$table . "`.`" . PhCitizen::$foreign_key . "` ";
                    $GLOBALS['query'] .= " AND `" . Release::$table . "`.`" . Release::$primary_key . "`=`lending_loan_amounts`.`" . Release::$foreign_key . "` ";
                    $GLOBALS['query'] .= " AND `" . Release::$table . "`.`Cycle`!='' ";
                    $GLOBALS['query'] .= " AND `lending_loan_amounts`.`Date` <= DATE('" . $last_posting_date_plus_one_day . "') ";
                    $GLOBALS['query'] .= " AND `lending_loan_amounts`.`AreaID`=$area_id ";
                    //*** TROUBLESHOOT INDIVIDUAL ACCOUNT *************************************************************/
                        // $GLOBALS['query'] .= " AND `lending_loan_releases`.`ID` IN (2916) ";
                    //*************************************************************************************************/
                    $GLOBALS['query'] .= "ORDER BY ";
                    $GLOBALS['query'] .= " `" . PhCitizen::$table . "`.`LastName`, `" . PhCitizen::$table . "`.`FirstName`, `" . PhCitizen::$table . "`.`MiddleName`, `" . Release::$table . "`.`Cycle`, DATE(`lending_loan_amounts`.`Date`) DESC";

                    $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                    if(has_no_db_error('for getting Release within a cutoff')) {
                        while($row = mysqli_fetch_assoc($result)) {
                            if(!in_array($row[Release::$primary_key], $unique_release_ids)) {
                                array_push($unique_release_ids, $row[Release::$primary_key]);
                                $release = new Release($row[Release::$primary_key], 'for getting Release weekly cutoff collections');

                                // get individual Release collections within the cutoff
                                $release->set_data('collection_dates', $posting_dates);
                                $collection_data = $release->gather_collection_data($cutoff_date);
                                if($collection_data['balance'] <= 0 && $collection_data['countdown'] <= 0 && $collection_data['collectibles'] <= 0)
                                    continue;
                                else {
                                    // push $release to $arr['releases']
                                    array_push($arr['releases'], $release->get_data());

                                    for ($i = 0; $i < sizeof($posting_dates); $i++) {
                                        $arr['sub_totals'][$i] += $collection_data['collections'][$i]['amount'];
                                        $arr['total_total'] += $collection_data['collections'][$i]['amount'];
                                    }
                                    $arr['total_due']          += $collection_data['due'];
                                    $arr['total_overdue']      += $collection_data['overdue'];
                                    $arr['total_advance']      += $collection_data['advance'];
                                    $arr['total_collectibles'] += $collection_data['collectibles'];
                                    $arr['total_unpaid']       += $collection_data['unpaid'];
                                    $arr['total_overpaid']     += $collection_data['overpaid'];
                                    $arr['total_balance'] += $collection_data['balance'];
                                    if ($collection_data['is_new_account'])
                                        $arr['new_account'] += $collection_data['loan_amount'];
                                }
                            }
                        }
                    }
                    else
                        fin();
                }
                return $arr;
            }


            /****************************************************************************************************
             * Posting :: get_collection_form
             *
             * Get the collection form of Posting
             * @access public static
             *
             * @param array $collection_data - the collection data
             *
             * @return array of Release
             *
             */
            public static function get_collection_form($collection_data) {
                $forms = array();
                $arr   = $collection_data;
                for($i=0; $i<sizeof($arr['releases']); $i++) {
                    $release_data = $arr['releases'][$i];

                    // push release info to $forms
                    array_push($forms, array(
                        'class' => 'form form-group-attached form-group-hover form-group-hover-top',
                        'rows'  => array(
                            array(
                                array(
                                    'grid'        => 'hidden',
                                    'type'        => 'hidden',
                                    'id'          => 'txt-id-'.$i,
                                    'label'       => 'ID',
                                    'value'       => $release_data['id']
                                ),
                                array(
                                    'grid'        => 'col-md-3 col-sm-12',
                                    'type'        => 'labelbox',
                                    'id'          => 'inp-citizen-' . $i,
                                    'label'       => str_pad(($i+1), 3, '0', STR_PAD_LEFT)  . '.' . (isset($release_data['citizen']['account_number']) ? ' <span class="text-primary">' . str_pad($release_data['citizen']['account_number'], 8, '0', STR_PAD_LEFT) . '</span>' : ''),
                                    'avatar'      => isset($release_data['citizen']['avatar']) ? $release_data['citizen']['avatar'] : '',
                                    'value'       => isset($release_data['citizen']['full_name_3']) ? $release_data['citizen']['full_name_3'] : '',
                                    'key'         => isset($release_data['citizen']['id']) ? $release_data['citizen']['id'] : ''
                                ),
                                array(
                                    'grid'        => 'col-md-1 col-sm-2 col-2',
                                    'type'        => 'text',
                                    'text_class'  => 'text-info',
                                    'id'          => 'txt-cycle-' . $i,
                                    'label'       => '<span class="text-info">CYCLE</span>',
                                    'value'       => isset($release_data['cycle']) ? $release_data['cycle'] : '',
                                    'required'    => false,
                                    'readonly'    => true,
                                    'disabled'    => true
                                ),
                                array(
                                    'grid'        => 'col-md-3 col-sm-6 col-6',
                                    'type'        => 'currency',
                                    'text_class'  => 'text-info',
                                    'id'          => 'txt-loan-amount-'.$i,
                                    'label'       => '<span class="text-info">' . ($release_data['collection_data']['is_restructured'] ? 'RESTRUCT.' : 'RELEASED') . ' <span class="text-success">(' . date('M.d, Y', strtotime($release_data['collection_data']['release_date'])) . ')</span></span>',
                                    'value'       => $release_data['collection_data']['loan_amount'],
                                    'is_int'      => true,
                                    'required'    => false,
                                    'readonly'    => true,
                                    'disabled'    => true
                                ),
                                array(
                                    'grid'        => 'col-md-1 col-sm-4 col-4',
                                    'type'        => 'currency',
                                    'text_class'  => 'text-info',
                                    'id'          => 'txt-daily-'.$i,
                                    'label'       => '<span class="text-info">DAILY</span>',
                                    'value'       => $release_data['collection_data']['daily_payment'],
                                    'is_int'      => true,
                                    'required'    => false,
                                    'readonly'    => true,
                                    'disabled'    => true,
                                    'show_symbol' => false
                                ),

                                array(
                                    'grid'        => 'col-md-1 col-sm-4 col-4',
                                    'type'        => 'currency',
                                    'text_class'  => 'text-info',
                                    'id'          => 'txt-due-'.$i,
                                    'label'       => '<span class="text-info">DUE</span>',
                                    'value'       => $release_data['collection_data']['due'],
                                    'is_int'      => true,
                                    'required'    => false,
                                    'readonly'    => true,
                                    'disabled'    => true,
                                    'show_symbol' => false
                                ),

                                array(
                                    'grid'        => 'col-md-1 col-sm-12 col-12',
                                    'type'        => 'currency',
                                    'text_class'  => 'text-danger',
                                    'id'          => 'txt-collectibles-'.$i,
                                    'label'       => '<span class="text-danger">COLLECT</span>',
                                    'value'       => $release_data['collection_data']['collectibles'],
                                    'is_int'      => true,
                                    'required'    => false,
                                    'readonly'    => true,
                                    'disabled'    => true,
                                    'show_symbol' => false
                                ),

                                array(
                                    'grid'        => 'col-md-1 col-sm-4 col-4',
                                    'type'        => 'currency',
                                    'text_class'  => 'text-primary',
                                    'id'          => 'txt-overdue-'.$i,
                                    'label'       => '<span class="text-primary">OVERD.</span>',
                                    'value'       => $release_data['collection_data']['overdue'],
                                    'is_int'      => true,
                                    'required'    => false,
                                    'readonly'    => true,
                                    'disabled'    => true,
                                    'show_symbol' => false
                                ),

                                array(
                                    'grid'        => 'col-md-1 col-sm-4 col-4',
                                    'type'        => 'currency',
                                    'text_class'  => 'text-primary',
                                    'id'          => 'txt-advance-'.$i,
                                    'label'       => '<span class="text-primary">ADV.</span>',
                                    'value'       => $release_data['collection_data']['advance'],
                                    'is_int'      => true,
                                    'required'    => false,
                                    'readonly'    => true,
                                    'disabled'    => true,
                                    'show_symbol' => false
                                )
                            )
                        )
                    ));

                    // generate $form_collection
                    $form_collection = array(
                        'class' => 'form form-group-attached form-group-hover form-group-hover-bottom',
                        'rows'  => array(
                            array()
                        )
                    );
                    // daily collection
                    for($j=0; $j<sizeof($release_data['collection_data']['collections']); $j++) {
                        $collection = $release_data['collection_data']['collections'][$j];
                        array_push($form_collection['rows'][0], array(
                            'grid'        => 'col-md-1 col-sm-4 col-3 cell-' . ($j + 1) . ($collection['active'] ? ' bg-active' : ''),
                            'type'        => 'currency',
                            'class'       => 'daily-collection',
                            'text_class'  => 'txt-' . intval($release_data['citizen']['account_number']) . '-' . $release_data['cycle'] . '-' . ($j+1),
                            'id'          => 'txt-amount-' . $i . '-' . $j,
                            'label'       => '<span class="text-success">' . $collection['date'] . '</span>',
                            'value'       => isset($collection['amount']) ? $collection['amount'] : 0,
                            'is_int'      => true,
                            'required'    => false,
                            'disabled'    => !$collection['enabled'],
                            'show_symbol' => false,
                            'cell_row'    => $i,
                            'cell_col'    => $j
                        ));
                    }
                    // daily collection fillers
                    for($j=sizeof($release_data['collection_data']['collections']); $j<8; $j++) {
                        array_push($form_collection['rows'][0], array(
                            'grid'     => 'col-md-1 col-sm-4 col-3 cell-' . ($j+1),
                            'type'     => 'text',
                            'id'       => 'txt-amount-'.$i.'-'.$j,
                            'label'    => '&nbsp;',
                            'value'    => '',
                            'required' => false,
                            'readonly' => true,
                            'disabled' => true
                        ));
                    }

                    array_push($form_collection['rows'][0], array(
                        'grid'       => 'col-md-2 col-sm-12',
                        'type'       => 'currency',
                        'class'      => 'total-daily-collection',
                        'text_class' => 'text-success',
                        'id'         => 'txt-total-'.$i,
                        'label'      => '<span class="text-success">TOTAL</span>',
                        'value'      => $release_data['collection_data']['total'],
                        'is_int'     => true,
                        'required'   => false,
                        'readonly'   => true,
                        'disabled'   => true,
                        'cell_row'   => $i,
                    ));

                    array_push($form_collection['rows'][0], array(
                        'grid'        => 'col-md-1 col-sm-10 col-10',
                        'type'        => 'currency',
                        'class'       => 'current-balance',
                        'text_class'  => 'text-info',
                        'id'          => 'txt-balance-'.$i,
                        'label'       => '<span class="text-info">BAL.</span>',
                        'value'       => $release_data['collection_data']['balance'],
                        'value_init'  => ($release_data['collection_data']['balance'] + $release_data['collection_data']['total']),
                        'is_int'      => true,
                        'required'    => false,
                        'readonly'    => true,
                        'disabled'    => true,
                        'show_symbol' => false,
                        'cell_row'    => $i
                    ));

                    array_push($form_collection['rows'][0], array(
                        'grid'       => 'col-md-1 col-sm-2 col-2',
                        'type'       => 'text',
                        'class'      => 'text-right',
                        'text_class' => 'text-info',
                        'id'         => 'txt-countdown-' . $i,
                        'label'      => '<span class="text-info">C.D.</span>',
                        'value'      => $release_data['collection_data']['countdown'],
                        'required'   => false,
                        'readonly'   => true,
                        'disabled'   => true
                    ));

                    array_push($forms, $form_collection);

                    // Break
                    if($i < sizeof($arr['releases'])-1) {
                        array_push($forms, array(
                            'class' => 'div',
                            'rows' => array(
                                array(
                                    array(
                                        'grid' => 'col-md-12',
                                        'type' => 'div',
                                        'id' => 'lbl-break-' . $i,
                                        'value' => '<div style="padding: 3px;"></div>'
                                    )
                                )
                            )
                        ));
                    }
                }

                if(sizeof($forms) <= 0) {
                    array_push($forms, array(
                        'class' => 'div',
                        'rows'  => array(
                            array(
                                array(
                                    'grid'     => 'col-md-12',
                                    'type'     => 'div',
                                    'id'       => 'lbl-break-' . $i,
                                    'value'    => '<div class="text-danger" style="padding: 3px;">No RELEASE found for the specified AREA and CUTOFF DATE.</div>'
                                )
                            )
                        )
                    ));
                }

                // collection end marker
                array_push($forms, array(
                    'class' => 'form form-collection-end',
                    'rows'  => array(
                        array(
                            array(
                                'grid'     => 'hidden',
                                'type'     => 'hidden',
                                'id'       => 'txt-collection-end',
                                'label'    => '',
                                'value'    => ''
                            )
                        )
                    )
                ));

                // Summary
                if(isset($arr['posting_dates'])) {
                    array_push($forms, array(
                        'class' => 'form form-group-break',
                        'rows'  => array(
                            array(
                                array(
                                    'grid'     => 'col-md-12',
                                    'type'     => 'label',
                                    'id'       => 'lbl-summary',
                                    'value'    => '<span class="fa fa-info-circle fa-fw"></span> SUMMARY'
                                )
                            )
                        )
                    ));

                    $form_summary = array(
                        'class' => 'form form-group-attached',
                        'rows'  => array(
                            array()
                        )
                    );
                    // Summary :: Daily
                    for($j=0; $j<sizeof($arr['sub_totals']); $j++) {
                        $subtotal = $arr['sub_totals'][$j];
                        array_push($form_summary['rows'][0], array(
                            'grid'        => 'col-md-1 col-sm-4 col-3',
                            'type'        => 'currency',
                            'class'       => 'txt-subtotal',
                            'id'          => 'txt-subtotal-' . $j,
                            'label'       => '<span class="text-success">' . date('M.d', strtotime($arr['posting_dates'][$j])) . '</span>',
                            'value'       => $subtotal,
                            'is_int'      => true,
                            'required'    => false,
                            'readonly'    => true,
                            'show_symbol' => true
                        ));
                    }
                    // Summary :: Daily Fillers
                    for($j=sizeof($arr['sub_totals']); $j<8; $j++) {
                        array_push($form_summary['rows'][0], array(
                            'grid'     => 'col-md-1 col-sm-4 col-3',
                            'type'     => 'text',
                            'id'       => 'txt-subtotal-' . $j,
                            'label'    => '&nbsp;',
                            'value'    => '',
                            'required' => false,
                            'readonly' => true,
                            'disabled' => true
                        ));
                    }
                    // Summary :: Total
                    array_push($form_summary['rows'][0], array(
                        'grid'       => 'col-md-2 col-sm-12',
                        'type'       => 'currency',
                        'text_class' => 'text-success',
                        'id'         => 'txt-total-total',
                        'label'      => '<span class="text-success">TOTAL</span>',
                        'value'      => $arr['total_total'],
                        'is_int'     => true,
                        'required'   => false,
                        'readonly'   => true,
                        'disabled'   => true
                    ));
                    // Summary :: Balance
                    array_push($form_summary['rows'][0], array(
                        'grid'       => 'col-md-2 col-sm-12',
                        'type'       => 'currency',
                        'text_class' => 'text-info',
                        'id'         => 'txt-total-balance',
                        'label'      => '<span class="text-info">BAL.</span>',
                        'value'      => $arr['total_balance'],
                        'value_init' => ($arr['total_balance'] + $arr['total_total']),
                        'is_int'     => true,
                        'required'   => false,
                        'readonly'   => true,
                        'disabled'   => true
                    ));

                    array_push($forms, $form_summary);
                }

                return $forms;
            }


            /****************************************************************************************************
             * Posting :: print_html
             *
             * Print posting data as HTML
             * @access public static
             *
             * @param int $posting_id   - the Posting id
             * @param string $form_data - the form data to base the data to be printed
             *
             * @return string (html)
             *
             */
            public static function print_html($posting_id, $form_data) {
                // print WEEKLY Collection data
                if($posting_id == 1) {
                    // get area and date for printing
                    require INDEX . 'php/models/Area.php';
                    $area = new Area($form_data['srch-post-area'], 'for printing Posting data');
                    $date = date('Y-m-d', strtotime($form_data['txt-cutoff-date']));
                    $posting_dates = self::get_weekly_posting_dates($date);

                    // create and modify posting data
                    $posting = new Posting($posting_id, 'for printing posting data');
                    $posting->set_data('last_accessed_area', $area->get_data());
                    $posting->set_data('last_accessed_date', $date);

                    // get_collection_data
                    $arr = $posting->get_collection_data();

                    // get_collection_data last week
                    $posting_prev = $posting;
                    $date_prev    = date('Y-m-d', strtotime($posting_dates[0]) - __init::$DAY_TOTAL_SECONDS);
                    $posting_prev->set_data('last_accessed_date', $date_prev);
                    $arr_2 = $posting_prev->get_collection_data();

                    echo "<!DOCTYPE html>\n";
                    echo "<html lang='en'>\n";
                    echo "\t<head>\n";
                        echo "\t\t<title> COLLECTION REPORT (" . date('Y-m-d', strtotime($posting_dates[0])) . ' to ' . date('Y-m-d', strtotime($posting_dates[sizeof($posting_dates)-1])) . ') | ' .  APP_NAME . "</title>";
                        require INDEX . "ui/imports.global.php";
                        echo "<style>";
                        echo "tr, td {vertical-align: middle !important; padding: 1px 3px 1px 3px !important;}";
                        echo ".tr-header > td, .tr-footer > td {padding-top: 4px !important; padding-bottom: 4px !important;}";
                        echo "td.border-top {border-top: 2px solid #aaa !important;}";
                        echo "td.border-right {border-right: 2px solid #aaa !important;}";
                        echo "td.border-bottom {border-bottom: 2px solid #aaa !important;}";
                        echo "td.border-left {border-left: 2px solid #aaa !important;}";

                        echo "tr.tr-odd > td {background-color: #ffffff !important; }";
                        echo "tr.tr-even  > td {background-color: #eeeeee !important; }";
                        echo "tr.tr-odd > td.td-gray {background-color: #f0f0f0 !important; }";
                        echo "tr.tr-even  > td.td-gray {background-color: #dfdfdf !important; }";

                        echo "tr.tr-odd:hover td { background-color: #fdfdd6 !important; border-bottom: 1px solid #aaa; }";
                        echo "tr.tr-odd:hover td.td-gray { background-color: #fcfce6 !important; border-bottom: 1px solid #aaa; }";
                        echo "tr.tr-even:hover td { background-color: #f4f4cf !important; border-bottom: 1px solid #aaa; }";
                        echo "tr.tr-even:hover td.td-gray { background-color: #f4f4df !important; border-bottom: 1px solid #aaa; }";

                        echo ".tr-yellow  > td {background-color: yellow !important; }";
                        echo ".tbl-transparent td, .tbl-transparent td, .tbl-transparent td {background-color: transparent !important; border: 0 !important;}";
                        echo ".tr-footer-opac {opacity: 0.5 !important;}";
                        echo "@media print { ";
                            echo ".text-primary, .text-info, .text-success, .text-danger, .text-warning {color: #000 !important;}";
                            echo "tr.tr-odd:hover > td {background-color: #ffffff !important; }";
                            echo "tr.tr-even:hover  > td {background-color: #eeeeee !important; }";
                            echo "tr.tr-odd:hover > td.td-gray {background-color: #f0f0f0 !important; }";
                            echo "tr.tr-even:hover  > td.td-gray {background-color: #dfdfdf !important; }";
                        echo "}";
                        echo "</style>";
                    echo "\t</head>\n";
                    echo "\t<body style='background: white !important; color: #000 !important; padding: 5px !important;'>\n";
                        echo "<table style='width: 100%;'>";
                            echo "<tr>";
                                echo "<td style='width: 50%' align='left'>";
                                    echo "<p class='no-margin'><big><b>COLLECTION REPORT</b></big></p>";
                                    echo "<p>From <b>" . $posting_dates[0] . "</b> to <b>" . $posting_dates[sizeof($posting_dates)-1] . "</b></p>";
                                    echo "<p>Area: <big><b>" . $area->get_data('code') . "</b></big></p>";
                                echo "</td>";
                                echo "<td style='width: 50%' align='right'>";
                                    echo "<div style='display: inline; float: right'>";
                                        $banner_text_class = 'text-black';
                                        $app_logo = 'app_logo_1.png';
                                        $brand_id = 'banner1';
                                        require INDEX . 'ui/layout.banner.php';
                                    echo "</div>";
                                echo "</td>";
                            echo "</tr>";
                        echo "</table>";

                        echo "<table class='table table-bordered table-sm'>";
                            echo "<thead>";
                                echo "<tr class='tr-header'>";
                                    echo "<td align='center' colspan='2' class='border-left border-top border-bottom'><b>ACCT. NO.</b></td>";
                                    echo "<td align='center' class='border-top border-bottom'><b>ACCT. NAME</b></td>";
                                    echo "<td align='center' class='border-top border-bottom'><b>CYCLE</b></td>";
                                    echo "<td align='center' class='border-right border-top border-bottom'><b>TYPE</b></td>";
                                    for($i=0; $i<sizeof($posting_dates); $i++) {
                                        echo "<td align='center' class='td-gray border-top border-bottom";
                                        if($i == 0)
                                            echo ' border-left';
                                        else if($i == sizeof($posting_dates)-1)
                                            echo ' border-right';
                                        echo "'>";

                                        $posting_date_int = strtotime($posting_dates[$i]);
                                        $day_abbrev       = strtoupper(date('D', strtotime($posting_dates[$i])));
                                        $text_class       = $day_abbrev == 'SUN' ? 'text-danger' : 'text-default';
                                        echo "<p class='no-margin'><b class='$text_class'>" . $day_abbrev . "</b></p>";
                                        echo "<p class='no-margin'><b class='$text_class'>" . date('M.d', $posting_date_int) . "</b></p>";
                                        echo "</td>";
                                    }
                                    echo "<td align='center' class='border-left border-top border-bottom border-right'><b class='text-success'>TOTAL</b></td>";
                                    echo "<td align='center' class='border-top border-bottom border-left'><b class='text-danger'>COLLECT</b></td>";
                                    echo "<td align='center' class='border-top border-bottom'><b class='text-primary'>DUE</b></td>";
                                    echo "<td align='center' class='border-top border-bottom'><b class='text-primary'>OVERD.</b></td>";
                                    echo "<td align='center' class='border-top border-bottom border-right'><b class='text-success'>ADV.</b></td>";
                                    echo "<td align='center' class='border-top border-bottom border-left'><b class='text-primary'>UNPAID</b></td>";
                                    echo "<td align='center' class='border-top border-bottom border-right'><b class='text-success'>OVERP.</b></td>";
                                    echo "<td align='center' class='border-top border-bottom border-right border-left'><b class='text-info'>BALANCE</b></td>";
                                    echo "<td align='center' class='border-top border-bottom border-left'><b>DAILY</b></td>";
                                    echo "<td align='center' class='border-right border-top border-bottom'><b>C.D.</b></td>";
                                echo "</tr>";
                            echo "</thead>";
                            echo "<tbody>";
                                $count = 0;
                                for($i=0; $i<sizeof($arr['releases']); $i++) {
                                    $count += 1;
                                    $release_data = $arr['releases'][$i];
                                    $class = ($i % 2 == 0) ? 'tr-even' : 'tr-odd';
                                    echo "<tr class='$class'>";
                                        $collection_data = $release_data['collection_data'];
                                        echo "<td align='right' class='border-left'><small><b>" . ($i + 1) . "</b>.</small></td>";
                                        echo "<td align='left'><small>" . $release_data['citizen']['account_number'] . "</small></td>";
                                        echo "<td align='left'><small>" . $release_data['citizen']['full_name_3'] . "</small></td>";
                                        echo "<td align='center'><small>" . $release_data['cycle'] . "</small></td>";
                                        echo "<td align='center' class='border-right'>";
                                            $text_disp  = 'RELEASED';
                                            $text_style = '';
                                            if($collection_data['is_restructured']) {
                                                $text_disp  = 'RESTRUCT';
                                                $text_style = ' font-weight: bold; opacity: 0.8';
                                            }
                                            echo "<small style='font-family: monospace;" . $text_style . "'>" . $text_disp . ": " . date('M.d, Y', strtotime($collection_data['release_date'])) . "</small>";
                                        echo "</td>";
                                        for($j=0; $j<sizeof($collection_data['collections']); $j++) {
                                            $collection = $collection_data['collections'][$j];
                                            echo "<td align='right' class='td-gray";
                                            if($j == 0)
                                                echo ' border-left';
                                            else if($j == sizeof($collection_data['collections'])-1)
                                                echo ' border-right';
                                            echo "'><small>" . number_format($collection['amount']) . "</small></td>";
                                        }
                                        echo "<td align='right' class='border-left border-right'><small class='text-success'><b>" . number_format($collection_data['total']) . "</b></small></td>";
                                        echo "<td align='right' class=' border-left'><small class='text-danger'><b>" . number_format($collection_data['collectibles']) . "</b></small></td>";
                                        echo "<td align='right'><small class='text-primary'>" . number_format($collection_data['due']) . "</small></td>";
                                        echo "<td align='right'><small class='text-primary'>" . number_format($collection_data['overdue']) . "</small></td>";
                                        echo "<td align='right' class='border-right'><small class='text-success'>" . number_format($collection_data['advance']) . "</small></td>";
                                        echo "<td align='right' class='border-left'><small class='text-primary'>" . number_format($collection_data['unpaid']) . "</small></td>";
                                        echo "<td align='right' class='border-right'><small class='text-success'>" . number_format($collection_data['overpaid']) . "</small></td>";
                                        echo "<td align='right' class='border-right border-left'><small class='text-info'><b>" . number_format($collection_data['balance']) . "</b></small></td>";
                                        echo "<td align='right' class='border-left'><small>" . number_format($collection_data['daily_payment']) . "</small></td>";
                                        echo "<td align='right' class='border-right'><small>" . number_format($collection_data['countdown']) . "</small></td>";
                                    echo "</tr>";
                                }

                                // footer
                                echo "<tr class='tr-footer tr-yellow'>";
                                    echo "<td align='right' colspan='5'  class='border-left border-bottom border-top'>";
                                        echo "<table style='width: 100%' class='tbl-transparent'>";
                                            echo "<tr>";
                                                echo "<td style='width: 28%'>";
                                                    echo "COUNT : <b>" . number_format(sizeof($arr['releases'])) . "</b>";
                                                echo "</td>";
                                                echo "<td style='width: 36%'>";
                                                    echo "NEW ACCT. : <b>" . number_format($arr['new_account']) . "</b>";
                                                echo "</td>";
                                                echo "<td style='width : 36%'>";
                                                    echo "BEG. BAL. : <b>" . number_format($arr_2['total_balance'] + $arr['new_account']) . "</b>";
                                                echo "</td>";
                                            echo "</tr>";
                                        echo "</table>";
                                    echo "</td>";
                                    for($i=0; $i<sizeof($arr['sub_totals']); $i++) {
                                        echo "<td align='right' class='td-gray border-bottom border-top";
                                        if($i == 0)
                                            echo ' border-left';
                                        else if($i == sizeof($arr['sub_totals'])-1)
                                            echo ' border-right';
                                        echo "'><span>" . number_format($arr['sub_totals'][$i]) . "</span></td>";
                                    }
                                    echo "<td align='right' class='border-bottom border-top border-right'><span class='text-success'><b>" . number_format($arr['total_total']) . "</b></span></td>";
                                    echo "<td align='right' class='border-bottom border-top border-left'><span class='text-danger'><b>" . number_format($arr['total_collectibles']) . "</b></span></td>";
                                    echo "<td align='right' class='border-bottom border-top'><span class='text-primary'><b>" . number_format($arr['total_due']) . "</b></span></td>";
                                    echo "<td align='right' class='border-bottom border-top'><span class='text-primary'><b>" . number_format($arr['total_overdue']) . "</b></span></td>";
                                    echo "<td align='right' class='border-bottom border-top border-right'><span class='text-success'><b>" . number_format($arr['total_advance']) . "</b></span></td>";
                                    echo "<td align='right' class='border-bottom border-top border-left'><span class='text-primary'><b>" . number_format($arr['total_unpaid']) . "</b></span></td>";
                                    echo "<td align='right' class='border-bottom border-top border-right'><span class='text-success'><b>" . number_format($arr['total_overpaid']) . "</b></span></td>";
                                    echo "<td align='right' class='border-bottom border-top border-right border-left'><span class='text-info'><b>" . number_format($arr['total_balance']) . "</b></span></td>";
                                    echo "<td colspan='2' class='border-right border-bottom border-top border-left' align='center'>";
                                        echo "<table style='width: 100%' class='tbl-transparent'>";
                                            echo "<tr>";
                                                echo "<td>";
                                                    echo "<span class='fa fa-arrow-left fa-fw'></span>";
                                                echo "</td>";
                                                echo "<td>";
                                                    echo "<b>THIS WEEK</b>";
                                                echo "</td>";
                                            echo "</tr>";
                                        echo "</table>";
                                    echo "</td>";
                                echo "</tr>";
                                echo "<tr class='tr-footer tr-footer-opac'>";
                                    echo "<td align='right' colspan='5'  class='border-left border-bottom border-top'>";
                                        echo "<table style='width: 100%' class='tbl-transparent'>";
                                            echo "<tr>";
                                                echo "<td style='width: 28%'>";
                                                    echo "COUNT : <b>" . number_format(sizeof($arr_2['releases'])) . "</b>";
                                                echo "</td>";
                                                echo "<td style='width: 36%'>";
                                                    echo "NEW ACCT. : <b>" . number_format($arr_2['new_account']) . "</b>";
                                                echo "</td>";
                                                echo "<td style='width : 36%'>";

                                                echo "</td>";
                                            echo "</tr>";
                                        echo "</table>";
                                    echo "</td>";
                                    for($i=0; $i<sizeof($arr['sub_totals']); $i++) {
                                        echo "<td align='right' class='td-gray border-bottom border-top";
                                        if($i == 0)
                                            echo ' border-left';
                                        else if($i == sizeof($arr['sub_totals'])-1)
                                            echo ' border-right';
                                        echo "'></td>";
                                    }
                                    echo "<td align='right' class='border-bottom border-top border-right'><span class='text-success'><b>" . number_format($arr_2['total_total']) . "</b></span></td>";
                                    echo "<td align='right' class='border-bottom border-top border-left'><span class='text-danger'><b>" . number_format($arr_2['total_collectibles']) . "</b></span></td>";
                                    echo "<td align='right' class='border-bottom border-top'><span class='text-primary'><b>" . number_format($arr_2['total_due']) . "</b></span></td>";
                                    echo "<td align='right' class='border-bottom border-top'><span class='text-primary'><b>" . number_format($arr_2['total_overdue']) . "</b></span></td>";
                                    echo "<td align='right' class='border-bottom border-top border-right'><span class='text-success'><b>" . number_format($arr_2['total_advance']) . "</b></span></td>";
                                    echo "<td align='right' class='border-bottom border-top border-left'><span class='text-primary'><b>" . number_format($arr_2['total_unpaid']) . "</b></span></td>";
                                    echo "<td align='right' class='border-bottom border-top border-right'><span class='text-success'><b>" . number_format($arr_2['total_overpaid']) . "</b></span></td>";
                                    echo "<td align='right' class='border-bottom border-top border-right border-left'><span class='text-info'><b>" . number_format($arr_2['total_balance']) . "</b></span></td>";
                                    echo "<td colspan='2' class='border-right border-bottom border-top border-left' align='center'>";
                                        echo "<table style='width: 100%' class='tbl-transparent'>";
                                            echo "<tr>";
                                                echo "<td>";
                                                    echo "<span class='fa fa-arrow-left fa-fw'></span>";
                                                echo "</td>";
                                                echo "<td>";
                                                    echo "<b>LAST WEEK</b>";
                                                echo "</td>";
                                            echo "</tr>";
                                        echo "</table>";
                                    echo "</td>";
                                echo "</tr>";
                            echo "</tbody>";
                        echo "</table>";

                        //echo "<script>window.print();</script>";
                    echo "\t</body>\n";
                    echo "</html>";
                }
            }
        }

        if(basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
            $object = new Posting(0, 'for class usage');
            require '__fin.php';
        }
    }
?>
