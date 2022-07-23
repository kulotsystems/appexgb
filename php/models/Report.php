<?php
    require '__init.php';

    if(!class_exists('Report')) {
        class Report extends __init {

            // class variables
            public static $tab_singular         = 'REPORT';
            public static $table                = 'lending_collection_reports';
            public static $primary_key          = 'ID';
            public static $primary_zero_allowed = false;
            public static $list_order           = 'ORDER BY `ID`';

            public static $params = array(
                'list'   => array(
                    'key'     => 'list_report',
                    'enabled' => true
                ),
                'select' => array(
                    'key'     => 'select_report',
                    'enabled' => true
                ),
                'search' => array(
                    'key'     => 'search_report',
                    'enabled' => true
                ),
                'callback' => array(
                    'key'     => 'callback_report',
                    'enabled' => true
                )
            );


            /****************************************************************************************************
             * Report :: CONSTRUCTOR
             *
             * Initialize the Report object.
             * @access public
             *
             * @param int    $report_id - the Report id
             * @param string $purpose   - the usage of this operation
             * @param bool   $metadata
             */
            public function __construct($report_id = 0, $purpose = '', $metadata = true) {
                $this->data = array(
                    'id' => 0,
                );

                $report_id = intval($report_id);
                if(($report_id > 0 || self::$primary_zero_allowed) && $purpose != 'for class usage') {
                    $purpose = 'getting Report info' . cascade_purpose($purpose);
                    $GLOBALS['query']  = "SELECT ";
                    $GLOBALS['query'] .= " `Title`, ";
                    $GLOBALS['query'] .= " `Description`, ";
                    $GLOBALS['query'] .= " `LastAccessedArea`, ";
                    $GLOBALS['query'] .= " `LastAccessedDate`, ";
                    $GLOBALS['query'] .= " `LastAccessedRelease`, ";
                    $GLOBALS['query'] .= "  DATE_FORMAT(`CreatedAt`, '%M %e, %Y &middot; %h:%i %p') AS CreatedAt, ";
                    $GLOBALS['query'] .= "  DATE_FORMAT(`UpdatedAt`, '%M %e, %Y &middot; %h:%i %p') AS UpdatedAt ";
                    $GLOBALS['query'] .= "FROM ";
                    $GLOBALS['query'] .= " `" . self::$table . "` ";
                    $GLOBALS['query'] .= "WHERE ";
                    $GLOBALS['query'] .= " `" . self::$primary_key . "`=$report_id";
                    $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                    if (has_no_db_error($purpose)) {
                        if (mysqli_num_rows($result) > 0) {
                            $row = mysqli_fetch_assoc($result);

                            require INDEX . 'php/models/Area.php';
                            require INDEX . 'php/models/Release.php';
                            $last_accessed_area    = new Area($row['LastAccessedArea'], 'for getting Report last accessed Area');
                            $last_accessed_release = new Release($row['LastAccessedRelease'], 'for getting Report last accessed Release');

                            $this->data = array(
                                'id'                         => $report_id,
                                'title'                      => $row['Title'],
                                'description'                => $row['Description'],
                                'last_accessed_area'         => $last_accessed_area->get_data(),
                                'last_accessed_date'         => intval($row['LastAccessedDate']) > 0 ? $row['LastAccessedDate'] : '',
                                'last_accessed_release_item' => $last_accessed_release->get_item(),
                                'collection'                 => array(),
                                'date_created'               => $row['CreatedAt'],
                                'date_updated'               => $row['UpdatedAt']
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
             * Report :: get_item
             *
             * Get the data to be displayed on items list
             * @access public
             *
             * @return array
             */
            public function get_item() {
                $report_id = $this->get_data('id');
                $details   = date('F d, Y', strtotime($this->get_data('last_accessed_date')));

                // modify details :: STATEMENT OF ACCOUNT
                if($report_id == 2)
                    $details = $this->get_data('last_accessed_release_item')['item_searchtitle'];

                return $this->get_item_helper(
                    $report_id,
                    '',
                    $this->get_data('title'),
                    $this->get_data('description'),
                    $details
                );
            }


            /****************************************************************************************************
             * Report :: get_form
             *
             * Generate form for Report object
             * @access public static
             *
             * @param array $data     - the item data
             * @param bool  $for_logs - for system logs or not
             *
             * @return array
             */
            public static function get_form($data, $for_logs) {
                $arr = array();

                // get_form :: TARGET PERFORMANCE
                if($data['id'] == 1) {
                    $arr = array(
                        array(
                            'class' => 'form form-group-attached',
                            'rows'  => array(
                                array(
                                    array(
                                        'grid'     => 'col-md-12 print-param',
                                        'type'     => 'date',
                                        'id'       => 'txt-cutoff-date',
                                        'label'    => 'CUTOFF DATE',
                                        'value'    => isset($data['last_accessed_date']) ? $data['last_accessed_date'] : '',
                                        'required' => true,
                                        'trigger_callback' => 'get_data_for_report'
                                    )
                                )
                            )
                        )
                    );
                }

                // get_form :: STATEMENT OF ACCOUNT
                else if($data['id'] == 2) {
                    $arr = array(
                        array(
                            'class' => 'form form-group-attached',
                            'rows'  => array(
                                array(
                                    array(
                                        'grid'     => 'col-md-12 print-param',
                                        'type'     => 'searchbox',
                                        'model'    => 'Release',
                                        'id'       => 'srch-release',
                                        'label'    => 'RELEASE',
                                        'avatar'   => isset($data['last_accessed_release_item']['item_avatar']) ? $data['last_accessed_release_item']['item_avatar'] : '',
                                        'value'    => isset($data['last_accessed_release_item']['item_searchtitle']) ? $data['last_accessed_release_item']['item_searchtitle'] : '',
                                        'key'      => isset($data['last_accessed_release_item']['item_id']) ? $data['last_accessed_release_item']['item_id'] : '',
                                        'disabled' => $for_logs,
                                        'trigger_callback' => 'get_data_for_report'
                                    )
                                )
                            )
                        )
                    );
                }

                // get_form :: TOTAL DAILY COLLECTION
                else if($data['id'] == 3) {
                    $arr = array(
                        array(
                            'class' => 'form form-group-attached',
                            'rows'  => array(
                                array(
                                    array(
                                        'grid'     => 'col-md-12 print-param',
                                        'type'     => 'date',
                                        'id'       => 'txt-collection-date',
                                        'label'    => 'COLLECTION DATE',
                                        'value'    => isset($data['last_accessed_date']) ? $data['last_accessed_date'] : '',
                                        'required' => true,
                                        'trigger_callback' => 'get_data_for_report'
                                    )
                                )
                            )
                        )
                    );
                }

                return $arr;
            }


            /****************************************************************************************************
             * Report :: print_html
             *
             * Print report data as HTML
             * @access public static
             *
             * @param int $report_id   - the Report id
             * @param string $form_data - the form data to base the data to be printed
             *
             * @return string (html)
             *
             */
            public static function print_html($report_id, $form_data) {
                // print TARGET PERFORMANCE
                if($report_id == 1) {
                    // date for printing
                    require INDEX . 'php/models/Posting.php';
                    $date = date('Y-m-d', strtotime($form_data['txt-cutoff-date']));
                    $posting_dates = Posting::get_weekly_posting_dates($date);

                    // create and modify report data
                    $report = new Report($report_id, 'for printing target performance');
                    $report->set_data('last_accessed_date', $date);

                    // get report data
                    $arr = $report->get_report_data();

                    // update last_accessed_date of the report in the database
                    $GLOBALS['query']  = "UPDATE `" . self::$table . "` ";
                    $GLOBALS['query'] .= "SET `LastAccessedDate`='$date' ";
                    $GLOBALS['query'] .= "WHERE `" . self::$primary_key . "`=$report_id";
                    mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                    if(has_no_db_error('for updating last_accessed_date of Report')) {
                        echo "<!DOCTYPE html>\n";
                        echo "<html lang='en'>\n";
                        echo "\t<head>\n";
                            echo "\t\t<title> TARGET PERFORMANCE (" . date('Y-m-d', strtotime($posting_dates[0])) . ' to ' . date('Y-m-d', strtotime($posting_dates[sizeof($posting_dates)-1])) . ') | ' .  APP_NAME . "</title>";
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
                        echo "</head>";
                        echo "<body style='background: white !important; color: #000 !important; padding: 5px !important;'>";
                            echo "<table style='width: 100%;'>";
                                echo "<tr>";
                                    echo "<td style='width: 50%' align='left'>";
                                        echo "<p class='no-margin'><big><b>TARGET PERFORMANCE</b></big></p>";
                                        echo "<p>FOR THE PERIOD OF: <b>" . $posting_dates[0] . "</b> to <b>" . $posting_dates[sizeof($posting_dates)-1] . "</b></p>";
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
                                        echo "<td align='center' class='border-left border-top border-right border-bottom'><b>AREA</b></td>";
                                        echo "<td align='center' class='border-top border-right border-bottom'><b>NO. OF<br>ACCOUNTS<br></td>";
                                        echo "<td align='center' class='border-top border-left border-bottom'><b>DUE</b><br><small><i>DAILY &times; UNPAID DAYS<br>THIS WEEK</i></small></td>";
                                        echo "<td align='center' class='border-top border-bottom'><b>OVERDUE</b><br><small><i>UNCLOSED ACCOUNT<br>UNPAID LAST WEEK</i></small></td>";
                                        echo "<td align='center' class='border-top border-right border-bottom'><b>ADVANCE</b><br><small><i>UNCLOSED ACCOUNT<br>OVERPAID LAST WEEK</i></small></td>";
                                        echo "<td align='center' class='border-top border-left border-right border-bottom'><b>COLLECTIBLES</b><br><small><i>DUE + OVERDUE</i></small></td>";
                                        echo "<td align='center' class='border-top border-bottom'><b>PAYMENT FOR<br>COLLECTIBLES</td>";
                                        echo "<td align='center' class='border-top border-right border-bottom'><b>PAYMENT<br>PERCENTAGE</br></td>";
                                        echo "<td align='center' class='border-top border-left border-bottom'><b>UNPAID</b><br><small><i>(THIS WEEK)</i></small></td>";
                                        echo "<td align='center' class='border-top border-right border-bottom'><b>OVERPAID</b><br><small><i>(THIS WEEK)</i></small></td>";
                                        echo "<td align='center' class='border-top border-left border-right border-bottom'><b>TOTAL<br>COLLECTION<br></td>";
                                        echo "<td align='center' class='border-top border-right border-left border-bottom'><b>CURRENT<br>BALANCE<br></td>";
                                    echo "</tr>";
                                echo "</thead>";
                                echo "<tbody>";
                                    $TOTAL_COUNT     = 0;
                                    $TOTAL_DUE       = 0;
                                    $TOTAL_OVERDUE   = 0;
                                    $TOTAL_ADVANCE   = 0;
                                    $TOTAL_COLLECT   = 0;
                                    $TOTAL_PAYMENT   = 0;
                                    $TOTAL_UNPAID    = 0;
                                    $TOTAL_OVERPAID  = 0;
                                    $TOTAL_TOTAL     = 0;
                                    $TOTAL_BALANCE   = 0;
                                    $count = 0;
                                    for($i=0; $i<sizeof($arr); $i++) {
                                        $count += 1;
                                        $class  = ($i % 2 == 0) ? 'tr-even' : 'tr-odd';
                                        echo "<tr class='$class'>";
                                            $area_code      = $arr[$i]['area']['code'];
                                            $total_count    = $arr[$i]['total_count'];
                                            $total_due      = $arr[$i]['total_due'];
                                            $total_overdue  = $arr[$i]['total_overdue'];
                                            $total_advance  = $arr[$i]['total_advance'];
                                            $total_collect  = $arr[$i]['total_collectibles'];
                                            $total_payment  = $arr[$i]['total_total'] - $arr[$i]['total_overpaid'];
                                            $percentage     = ($total_collect > 0) ? ($total_payment / $total_collect) * 100 : 0;
                                            $total_unpaid   = $arr[$i]['total_unpaid'];
                                            $total_overpaid = $arr[$i]['total_overpaid'];
                                            $total_total    = $arr[$i]['total_total'];
                                            $total_balance  = $arr[$i]['total_balance'];


                                            echo "<td align='center' class='border-left border-right'><b>" . $area_code . "</b></td>";
                                            echo "<td align='right' class='border-left border-right'>" . number_format($total_count) . "</td>";
                                            echo "<td align='right' class='border-left'>" . number_format($total_due) . "</td>";
                                            echo "<td align='right' class=''>" . number_format($total_overdue) . "</td>";
                                            echo "<td align='right' class='border-right'>" . number_format($total_advance) . "</td>";
                                            echo "<td align='right' class='border-left border-right'>" . number_format($total_collect) . "</td>";
                                            echo "<td align='right' class='border-left'>" . number_format($total_payment) . "</td>";
                                            echo "<td align='right' class='border-right'>" . number_format($percentage, 2) . "%</td>";
                                            echo "<td align='right' class='border-left'>" . number_format($total_unpaid) . "</td>";
                                            echo "<td align='right' class='border-right'>" . number_format($total_overpaid) . "</td>";
                                            echo "<td align='right' class='border-left border-right'>" . number_format($total_total) . "</td>";
                                            echo "<td align='right' class='border-left border-right'>" . number_format($total_balance) . "</td>";

                                            $TOTAL_COUNT    += $total_count;
                                            $TOTAL_DUE      += $total_due;
                                            $TOTAL_OVERDUE  += $total_overdue;
                                            $TOTAL_ADVANCE  += $total_advance;
                                            $TOTAL_COLLECT  += $total_collect;
                                            $TOTAL_PAYMENT  += $total_payment;
                                            $TOTAL_UNPAID   += $total_unpaid;
                                            $TOTAL_OVERPAID += $total_overpaid;
                                            $TOTAL_TOTAL    += $total_total;
                                            $TOTAL_BALANCE  += $total_balance;
                                        echo "</tr>";
                                    }
                                    echo "<tr class='tr-footer tr-yellow'>";
                                        echo "<td align='center' class='border-top border-left border-right border-bottom'><b>GRAND TOTAL</b></td>";
                                        echo "<td align='right' class='border-top border-left border-right border-bottom'><b>" . number_format($TOTAL_COUNT) . "</b></td>";
                                        echo "<td align='right' class='border-top border-bottom'><b>" . number_format($TOTAL_DUE) . "</b></td>";
                                        echo "<td align='right' class='border-top border-bottom'><b>" . number_format($TOTAL_OVERDUE) . "</b></td>";
                                        echo "<td align='right' class='border-top border-right border-bottom'><b>" . number_format($TOTAL_ADVANCE) . "</b></td>";
                                        echo "<td align='right' class='border-top border-left border-right border-bottom'><b>" . number_format($TOTAL_COLLECT) . "</b></td>";
                                        echo "<td align='right' class='border-top border-left border-bottom'><b>" . number_format($TOTAL_PAYMENT) . "</b></td>";
                                        echo "<td align='right' class='border-top border-right border-bottom'><b>" . number_format(($TOTAL_PAYMENT / $TOTAL_COLLECT) * 100, 2) . "%</b></td>";
                                        echo "<td align='right' class='border-top border-left border-bottom'><b>" . number_format($TOTAL_UNPAID) . "</b></td>";
                                        echo "<td align='right' class='border-top border-right border-bottom'><b>" . number_format($TOTAL_OVERPAID) . "</b></td>";
                                        echo "<td align='right' class='border-top border-left border-right border-bottom'><b>" . number_format($TOTAL_TOTAL) . "</b></td>";
                                        echo "<td align='right' class='border-top border-left border-right border-bottom'><b>" . number_format($TOTAL_BALANCE) . "</b></td>";
                                    echo "</tr>";
                                echo "</tbody>";
                            echo "</table>";
                        echo "</body>";
                        echo "</html>";
                    }
                }

                // print STATEMENT OF ACCOUNT
                else if($report_id == 2) {
                    // release for printing
                    require INDEX . 'php/models/Release.php';
                    $release = new Release($form_data['srch-release'], 'for printing statement of account');

                    // create and modify report data
                    $report = new Report($report_id, 'for printing statement of account');
                    $report->set_data('last_accessed_release_item', $release->get_item());

                    // get report data
                    $arr = $report->get_report_data();

                    // update last_accessed_release of the report in the database
                    $GLOBALS['query']  = "UPDATE `" . self::$table . "` ";
                    $GLOBALS['query'] .= "SET `LastAccessedRelease`=" . $release->get_data('id') . " ";
                    $GLOBALS['query'] .= "WHERE `" . self::$primary_key . "`=$report_id";
                    mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                    if(has_no_db_error('for updating last_accessed_release of Report')) {
                        echo "<!DOCTYPE html>\n";
                        echo "<html lang='en'>\n";
                        echo "\t<head>\n";
                            echo "\t\t<title> STATEMENT OF ACCOUNT (" . $release->get_item()['item_searchtitle'] . ") | " . APP_NAME . "</title>";
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
                                echo ".text-monospace {font-family: monospace !important}";
                            echo "</style>";
                        echo "</head>";
                        echo "<body style='background: white !important; color: #000 !important; padding: 5px !important;'>";
                            echo "<table style='width: 100%;'>";
                                echo "<tr>";
                                    echo "<td style='width: 50%' align='left'>";
                                        echo "<p class='no-margin'><big><b>STATEMENT OF ACCOUNT as of " . date('F d, Y', CURRENT_TIME) . "</b></big></p>";
                                        echo "<p><b>" . $release->get_item()['item_searchtitle'] . "</b></p>";
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
                                        echo "<td class='border-left border-top border-right border-bottom' align='center' style='width: 80px !important;'><b>CUTOFF</b></td>";
                                        echo "<td class='border-left border-top border-right border-bottom' align='center'><b>DETAILS</b></td>";
                                        echo "<td class='border-left border-top border-right border-bottom' align='center'><b>DATES</b></td>";
                                        echo "<td class='border-left border-top border-right border-bottom' align='center'><b>DAILY COLLECTION</b></td>";
                                        echo "<td class='border-left border-top border-right border-bottom' align='center'><b>DAILY TOTAL</b></td>";
                                        echo "<td class='border-left border-top border-right border-bottom' align='center'><b>BALANCE</b></td>";
                                        echo "<td class='border-left border-top border-right border-bottom' align='center'><b>C.D.</b></td>";
                                    echo "</tr>";
                                echo "</thead>";
                                echo "<tbody>";
                                    for($i=0; $i<sizeof($arr); $i++) {
                                        $cutoff_dates = $arr[$i]['cutoff_dates'];
                                        $posting_data = $arr[$i]['posting_data'];
                                        for($j=0; $j<sizeof($cutoff_dates); $j++) {
                                            echo "<tr>";
                                                if($j==0) {
                                                    echo "<td class='border-left border-top border-right border-bottom' rowspan='" . sizeof($cutoff_dates) . "' align='center'><big><b>" . ($i+1) . "</b></big></td>";
                                                    echo "<td class='border-left border-top border-right border-bottom' rowspan='" . sizeof($cutoff_dates) . "' align='center'>";
                                                        echo "<p class='no-margin'><small>LOAN AMOUNT:</small></p>";
                                                        echo "<p class='no-margin'>" . number_format($posting_data['loan_amount']) . "</p>";
                                                        echo "<br>";
                                                        echo "<p class='no-margin'><small>" . ($posting_data['is_restructured'] ? 'RESTRUCT' : 'RELEASED') . ":</small></p>";
                                                        echo "<p class='no-margin'>" . date('M. d, Y', strtotime($posting_data['release_date'])) . "</p>";
                                                        echo "<br>";
                                                        echo "<p class='no-margin'><small>DAILY:</small></p>";
                                                        echo "<p class='no-margin'>" . number_format($posting_data['daily_payment']) . "</p>";
                                                    echo "</td>";
                                                }
                                                echo "<td class='text-monospace border-left border-right";
                                                if($j == 0)
                                                    echo ' border-top';
                                                else if($j == sizeof($cutoff_dates)-1)
                                                    echo ' border-bottom';
                                                echo "' align='center' style='padding-top: 4px !important; padding-bottom: 4px !important'>" . date('M. d, Y', strtotime($cutoff_dates[$j])) . "</td>";

                                                echo "<td class='";
                                                if($j == 0)
                                                    echo ' border-top';
                                                else if($j == sizeof($cutoff_dates)-1)
                                                    echo ' border-bottom';
                                                echo "' align='right' style='padding-top: 4px !important; padding-bottom: 4px !important'>" . number_format($posting_data['collections'][$j]['amount']) . "</td>";
                                                if($j==0) {
                                                    echo "<td class='border-left border-top border-right border-bottom' rowspan='" . sizeof($cutoff_dates) . "' align='right'><big><b>" . number_format($posting_data['total']) . "</b></big></td>";
                                                    echo "<td class='border-left border-top border-right border-bottom' rowspan='" . sizeof($cutoff_dates) . "' align='right'><big><b>" . number_format($posting_data['balance']) . "</b></big></td>";
                                                    echo "<td class='border-left border-top border-right border-bottom' rowspan='" . sizeof($cutoff_dates) . "' align='right'><big class='" . ($posting_data['countdown'] >= 0 ? 'text-default' : 'text-danger'). "'>" . number_format($posting_data['countdown']) . "</big></td>";
                                                }
                                            echo "</tr>";
                                        }
                                    }
                                echo "</tbody>";
                            echo "</table>";
                            echo "<br>";
                        echo "</body>";
                        echo "</html>";
                    }
                }

                // print TOTAL DAILY COLLECTION
                else if($report_id == 3) {
                    // date for printing
                    $date = date('Y-m-d', strtotime($form_data['txt-collection-date']));

                    // create and modify report data
                    $report = new Report($report_id, 'for printing total daily collection');
                    $report->set_data('last_accessed_date', $date);

                    // get report data
                    $arr = $report->get_report_data();

                    // update last_accessed_date of the report in the database
                    $GLOBALS['query']  = "UPDATE `" . self::$table . "` ";
                    $GLOBALS['query'] .= "SET `LastAccessedDate`='$date' ";
                    $GLOBALS['query'] .= "WHERE `" . self::$primary_key . "`=$report_id";
                    mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                    if(has_no_db_error('for updating last_accessed_date of Report')) {
                        echo "<!DOCTYPE html>\n";
                        echo "<html lang='en'>\n";
                        echo "\t<head>\n";
                            echo "\t\t<title> TOTAL DAILY COLLECTION (" . date('F d, Y', strtotime($date)) . ") | " .  APP_NAME . "</title>";
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
                        echo "</head>";
                        echo "<body style='background: white !important; color: #000 !important; padding: 5px !important;'>";
                            echo "<table style='width: 100%;'>";
                                echo "<tr>";
                                    echo "<td style='width: 50%' align='left'>";
                                        echo "<p class='no-margin'><big><b>TOTAL DAILY COLLECTION</b></big></p>";
                                        echo "<p>DATE: <b>" . date('F d, Y', strtotime($date))  . "</b></p>";
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
                                        echo "<td class='border-left border-top border-right border-bottom' align='center'><b>AREA</b></td>";
                                        echo "<td class='border-left border-top border-right border-bottom' align='center'><b>CLIENT</b></td>";
                                        echo "<td class='border-left border-top border-right border-bottom' align='center'><b>CYCLE</b></td>";
                                        echo "<td class='border-left border-top border-right border-bottom' align='center'><b>COLLECTION</b></td>";
                                        echo "<td class='border-left border-top border-right border-bottom' align='center'><b>AREA TOTAL</b></td>";
                                    echo "</tr>";
                                echo "</thead>";
                                echo "<tbody>";
                                    for($i=0; $i<sizeof($arr['areas']); $i++) {
                                        echo "<tr>";
                                            echo "<td class='border-left border-top border-right border-bottom' rowspan='" . (sizeof($arr['areas'][$i]['collections']) + 1) . "' style='vertical-align: top !important' align='center'><b><h1>" . $arr['areas'][$i]['code'] . "</h1></b></td>";
                                        echo "</tr>";

                                        for($j=0; $j<sizeof($arr['areas'][$i]['collections']); $j++) {
                                            echo "<tr>";
                                                echo "<td class='";
                                                if($j == 0)
                                                    echo 'border-top';
                                                else if($j == sizeof($arr['areas'][$i]['collections'])-1)
                                                    echo 'border-bottom';
                                                echo "'>" . ($j+1) . ". " . $arr['areas'][$i]['collections'][$j]['citizen']['full_name_3'] . "</td>";
                                                echo "<td class='";
                                                if($j == 0)
                                                    echo 'border-top';
                                                else if($j == sizeof($arr['areas'][$i]['collections'])-1)
                                                    echo 'border-bottom';
                                                echo "' align='center'>" .  $arr['areas'][$i]['collections'][$j]['cycle'] . "</td>";
                                                echo "<td class='";
                                                if($j == 0)
                                                    echo 'border-top';
                                                else if($j == sizeof($arr['areas'][$i]['collections'])-1)
                                                    echo 'border-bottom';
                                                echo "' align='right'>" . number_format($arr['areas'][$i]['collections'][$j]['amount']) . "</td>";
                                                if($j == 0) {
                                                    echo "<td class='border-left border-top border-right border-bottom' rowspan='" . (sizeof($arr['areas'][$i]['collections'])) . "' align='right' style='vertical-align: top !important;'><h2>" .  number_format($arr['areas'][$i]['total']) . "</h2></td>";
                                                }
                                            echo "</tr>";
                                        }
                                    }
                                    echo "<tr>";
                                        echo "<td class='border-left border-top border-right border-bottom' colspan='4' align='right'><h2>TOTAL COLLECTION</h2></td>";
                                        echo "<td class='border-left border-top border-right border-bottom' align='right'><b><h2>" . number_format($arr['total']) . "</h2></b></td>";
                                    echo "</tr>";
                                echo "</tbody>";
                            echo "</table>";
                            echo "<br>";
                        echo "</body>";
                        echo "</html>";
                    }
                }
            }


            /****************************************************************************************************
             * Report :: search
             *
             * Search through Report records
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
                $GLOBALS['query'] .= " OR `Title` LIKE '%$q%' ";
                $GLOBALS['query'] .= " OR `Description` LIKE '%$q%' ";
                $GLOBALS['query'] .= "ORDER BY ";
                $GLOBALS['query'] .= " `Title`, `Description` ";
                parent::search_helper(get_class());
            }


            /****************************************************************************************************
             * Report :: get_report_data
             *
             * Get the data of Report
             * @access public
             *
             * @return array
             *
             */
            public function get_report_data() {
                $report      = $this;
                $report_id   = $report->get_data('id');
                $cutoff_date = date('F d, Y', strtotime($report->get_data('last_accessed_date')));
                $arr         = array();

                // get_report_data :: TARGET PERFORMANCE
                if($report_id == 1) {
                    require INDEX . 'php/models/Area.php';
                    require INDEX . 'php/models/Posting.php';
                    $cutoff_dates = Posting::get_weekly_posting_dates($cutoff_date);
                    $areas        = Area::get_all();
                    for($i=0; $i<sizeof($areas); $i++) {
                        $area = $areas[$i];
                        $total_count        = 0;
                        $total_due          = 0;
                        $total_overdue      = 0;
                        $total_advance      = 0;
                        $total_collectibles = 0;
                        $total_unpaid       = 0;
                        $total_overpaid     = 0;
                        $total_total        = 0;
                        $total_balance      = 0;

                        // check stored cutoff cache from the database
                        $GLOBALS['query']  = "SELECT ";
                        $GLOBALS['query'] .= " * ";
                        $GLOBALS['query'] .= "FROM ";
                        $GLOBALS['query'] .= " `lending_cutoff_cache` ";
                        $GLOBALS['query'] .= "WHERE ";
                        $GLOBALS['query'] .= "     `AreaID`=" . $area['id'] . " ";
                        $GLOBALS['query'] .= " AND `StartDate`='" . date('Y-m-d', strtotime($cutoff_dates[0])) . "' ";
                        $GLOBALS['query'] .= " AND `EndDate`='" . date('Y-m-d', strtotime($cutoff_dates[sizeof($cutoff_dates)-1])) . "' ";
                        $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                        if(has_no_db_error('for checking stored cutoff cache')) {
                            if(mysqli_num_rows($result) > 0) {
                                // cutoff cache found
                                $row = mysqli_fetch_assoc($result);
                                $total_count        = intval($row['Accounts']);
                                $total_due          = doubleval($row['Due']);
                                $total_overdue      = doubleval($row['Overdue']);
                                $total_advance      = doubleval($row['Advance']);
                                $total_collectibles = doubleval($row['Collectibles']);
                                $total_unpaid       = doubleval($row['Unpaid']);
                                $total_overpaid     = doubleval($row['Overpaid']);
                                $total_total        = doubleval($row['Collection']);
                                $total_balance      = doubleval($row['Balance']);
                            }
                            else {
                                // cutoff cache not found
                                $posting = new Posting(1, 'for getting target performance of Area ' . $area['code']);
                                $posting->set_data('last_accessed_area', $area);
                                $posting->set_data('last_accessed_date', date('Y-m-d', strtotime($cutoff_date)));
                                $posting_collection_data = $posting->get_collection_data();

                                $total_count        = sizeof($posting_collection_data['releases']);
                                $total_due          = $posting_collection_data['total_due'];
                                $total_overdue      = $posting_collection_data['total_overdue'];
                                $total_advance      = $posting_collection_data['total_advance'];
                                $total_collectibles = $posting_collection_data['total_collectibles'];
                                $total_unpaid       = $posting_collection_data['total_unpaid'];
                                $total_overpaid     = $posting_collection_data['total_overpaid'];
                                $total_total        = $posting_collection_data['total_total'];
                                $total_balance      = $posting_collection_data['total_balance'];

                                // store new cutoff cache
                                $GLOBALS['query']  = "INSERT INTO ";
                                $GLOBALS['query'] .= " `lending_cutoff_cache` ";
                                $GLOBALS['query'] .= " (`AreaID`, `StartDate`, `EndDate`, `Accounts`, `Due`, `Overdue`, `Advance`, `Collectibles`, `Unpaid`, `Overpaid`, `Collection`, `Balance`) ";
                                $GLOBALS['query'] .= "VALUES ";
                                $GLOBALS['query'] .= " (" . $area['id'] . ", '" . date('Y-m-d', strtotime($cutoff_dates[0])) . "', '" . date('Y-m-d', strtotime($cutoff_dates[sizeof($cutoff_dates)-1])) . "', $total_count, $total_due, $total_overdue, $total_advance, $total_collectibles, $total_unpaid, $total_overpaid, $total_total, $total_balance) ";
                                mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                                if(!has_no_db_error('for storing new cutoff cache')) {
                                    fin();
                                    break;
                                }
                            }
                            array_push($arr, array(
                                'area'                => $area,
                                'total_count'         => $total_count,
                                'total_due'           => $total_due,
                                'total_overdue'       => $total_overdue,
                                'total_advance'       => $total_advance,
                                'total_collectibles'  => $total_collectibles,
                                'total_unpaid'        => $total_unpaid,
                                'total_overpaid'      => $total_overpaid,
                                'total_total'         => $total_total,
                                'total_balance'       => $total_balance
                            ));
                        }
                        else {
                            fin();
                            break;
                        }
                    }
                }

                // get_report_data :: STATEMENT OF ACCOUNT
                else if($report_id == 2) {
                    require INDEX . 'php/models/Release.php';
                    require INDEX . 'php/models/Posting.php';
                    $release = new Release($this->get_data('last_accessed_release_item')['item_id'], 'for getting statement of account');

                    // get MAX payment date
                    $max_payment_date = $release->get_data('release_date');
                    $GLOBALS['query']  = "SELECT ";
                    $GLOBALS['query'] .= " MAX(`Date`) AS MaxDate ";
                    $GLOBALS['query'] .= "FROM ";
                    $GLOBALS['query'] .= " `lending_loan_collections` ";
                    $GLOBALS['query'] .= "WHERE ";
                    $GLOBALS['query'] .= " `ReleaseID`=" . $release->get_data('id');
                    $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                    if(has_no_db_error('for getting MAX payment dates of Release')) {
                        while($row = mysqli_fetch_assoc($result)) {
                            $max_payment_date = $row['MaxDate'];
                            break;
                        }

                        // get unique cutoff dates
                        $unique_cutoff_dates_start = array();
                        $unique_postings           = array();
                        $min_payment_date_int = strtotime($release->get_data('release_date'));
                        $max_payment_date_int = strtotime($max_payment_date);
                        for($i=$min_payment_date_int; $i<=$max_payment_date_int; $i += __init::$DAY_TOTAL_SECONDS) {
                            $cutoff_dates = Posting::get_weekly_posting_dates(date('F d, Y', $i));
                            if(!in_array($cutoff_dates[0], $unique_cutoff_dates_start)) {
                                array_push($unique_cutoff_dates_start, $cutoff_dates[0]);
                                $release->set_data('collection_dates', $cutoff_dates);
                                array_push($unique_postings, array(
                                    'cutoff_dates' => $cutoff_dates,
                                    'posting_data' => $release->gather_collection_data()
                                ));
                            }
                        }

                        $arr = $unique_postings;
                    }
                    else
                        fin();
                }

                // get_report_data :: TOTAL DAILY COLLECTION
                else if($report_id == 3) {
                    require INDEX . 'php/models/Area.php';
                    require INDEX . 'php/models/PhCitizen.php';
                    $arr['areas'] = Area::get_all();
                    $arr['total'] = 0;

                    for($i=0; $i<sizeof($arr['areas']); $i++) {
                        $arr['areas'][$i]['collections'] = array();
                        $arr['areas'][$i]['total']       = 0;

                        $GLOBALS['query'] = "SELECT DISTINCT ";
                        $GLOBALS['query'] .= " `lending_loan_releases`.`CitizenID`, ";
                        $GLOBALS['query'] .= " `lending_loan_releases`.`Cycle`, ";
                        $GLOBALS['query'] .= " `lending_loan_collections`.`Amount` ";
                        $GLOBALS['query'] .= "FROM ";
                        $GLOBALS['query'] .= " `lending_loan_collections`, `lending_loan_amounts`, `lending_loan_releases`, `lending_areas`, `lending_ph_citizens` ";
                        $GLOBALS['query'] .= "WHERE ";
                        $GLOBALS['query'] .= "     `lending_loan_collections`.`Amount` != 0 ";
                        $GLOBALS['query'] .= " AND `lending_loan_collections`.`Date`='" . date('Y-m-d', strtotime($report->get_data('last_accessed_date'))) . "' ";
                        $GLOBALS['query'] .= " AND `lending_areas`.`ID`=" . $arr['areas'][$i]['id'] . " ";
                        $GLOBALS['query'] .= " AND `lending_loan_collections`.`ReleaseID`=`lending_loan_releases`.`ID` ";
                        $GLOBALS['query'] .= " AND `lending_loan_amounts`.`ReleaseID`=`lending_loan_releases`.`ID` ";
                        $GLOBALS['query'] .= " AND `lending_loan_amounts`.`AreaID`=`lending_areas`.`ID` ";
                        $GLOBALS['query'] .= " AND `lending_loan_releases`.`CitizenID`=`lending_ph_citizens`.`ID` ";
                        $GLOBALS['query'] .= "ORDER BY ";
                        $GLOBALS['query'] .= " `lending_ph_citizens`.`LastName`, `lending_ph_citizens`.`FirstName`, `lending_ph_citizens`.`MiddleName`";
                        $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                        if (has_no_db_error('for getting collections on specific date')) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                array_push($arr['areas'][$i]['collections'], array(
                                    'citizen' => (new PhCitizen($row['CitizenID'], 'for getting total collection on specific date'))->get_data(),
                                    'cycle'   => $row['Cycle'],
                                    'amount'  => $row['Amount']
                                ));

                                $arr['areas'][$i]['total'] += $row['Amount'];
                                $arr['total']              += $row['Amount'];
                            }
                        }
                        else {
                            fin();
                            break;
                        }
                    }
                }

                /*echo "<pre>";
                    print_r($arr);
                echo "</pre>";*/

                return $arr;
            }
        }

        if(basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
            $object = new Report(0, 'for class usage');
            require '__fin.php';
        }
    }
?>
