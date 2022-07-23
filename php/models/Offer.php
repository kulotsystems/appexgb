<?php
    require '__init.php';

    if(!class_exists('Offer')) {
        class Offer extends __init {

            // class variables
            public static $tab_singular         = 'OFFER';
            public static $table                = 'lending_loan_offers';
            public static $primary_key          = 'ID';
            public static $foreign_key          = 'OfferID';
            public static $primary_zero_allowed = false;
            public static $list_order           = 'ORDER BY `Title`';

            public static $params = array(
                'list'   => array(
                    'key'     => 'list_offer',
                    'enabled' => true
                ),
                'create' => array(
                    'key'     => 'create_offer',
                    'enabled' => true
                ),
                'select' => array(
                    'key'     => 'select_offer',
                    'enabled' => true
                ),
                'update' => array(
                    'key'     => 'update_offer',
                    'enabled' => true
                ),
                'delete' => array(
                    'key'     => 'delete_offer',
                    'enabled' => true
                ),
                'search' => array(
                    'key'     => 'search_offer',
                    'enabled' => true
                )
            );


            /****************************************************************************************************
             * Offer :: CONSTRUCTOR
             *
             * Initialize the Offer object.
             * @access public
             *
             * @param int    $offer_id - the Offer id
             * @param string $purpose  - the usage of this operation
             * @param bool   $metadata
             */
            public function __construct($offer_id = 0, $purpose = '', $metadata = true) {
                $this->data = array(
                    'id' => 0
                );

                $offer_id = intval($offer_id);
                if(($offer_id > 0 || self::$primary_zero_allowed) && $purpose != 'for class usage') {
                    $purpose = 'getting Offer info' . cascade_purpose($purpose);
                    $GLOBALS['query']  = "SELECT ";
                    $GLOBALS['query'] .= " `Title`, ";
                    $GLOBALS['query'] .= " `Description`, ";
                    $GLOBALS['query'] .= " `InterestRate`, ";
                    $GLOBALS['query'] .= " `CollectionType`, ";
                    $GLOBALS['query'] .= " `CollectionCount`, ";
                    $GLOBALS['query'] .= "  DATE_FORMAT(`CreatedAt`, '%M %e, %Y &middot; %h:%i %p') AS CreatedAt, ";
                    $GLOBALS['query'] .= "  DATE_FORMAT(`UpdatedAt`, '%M %e, %Y &middot; %h:%i %p') AS UpdatedAt ";
                    $GLOBALS['query'] .= "FROM ";
                    $GLOBALS['query'] .= " `" . self::$table . "` ";
                    $GLOBALS['query'] .= "WHERE ";
                    $GLOBALS['query'] .= " `" . self::$primary_key . "`=$offer_id";
                    $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                    if (has_no_db_error($purpose)) {
                        if (mysqli_num_rows($result) > 0) {
                            if(mysqli_num_rows($result) > 0) {
                                $row = mysqli_fetch_assoc($result);

                                $collection_type_noun_singular = 'Collection';
                                $collection_type_noun_plural   = 'Collections';
                                $collection_type_day_increment = 1;
                                if($row['CollectionType'] == 'Daily') {
                                    $collection_type_noun_singular = 'Day';
                                    $collection_type_noun_plural   = 'Days';
                                    $collection_type_day_increment = 1;
                                }
                                else if($row['CollectionType'] == 'Weekly') {
                                    $collection_type_noun_singular = 'Week';
                                    $collection_type_noun_plural   = 'Weeks';
                                    $collection_type_day_increment = 7;
                                }

                                $this->data = array(
                                    'id'               => $offer_id,
                                    'title'            => $row['Title'],
                                    'description'      => $row['Description'],
                                    'interest_rate'    => $row['InterestRate'],
                                    'collection_type'  => $row['CollectionType'],
                                    'collection_type_noun_singular' => $collection_type_noun_singular,
                                    'collection_type_noun_plural'   => $collection_type_noun_plural,
                                    'collection_type_day_increment' => $collection_type_day_increment,
                                    'collection_types' => parent::get_enum_values(self::$table, 'CollectionType'),
                                    'collection_count' => intval($row['CollectionCount']),
                                    'date_created'     => $row['CreatedAt'],
                                    'date_updated'     => $row['UpdatedAt']
                                );
                            }
                            else {
                                $GLOBALS['response']['error'] = get_class($this) .' NOT FOUND ' . $purpose;
                                fin();
                            }
                        }
                    }
                    else
                        fin();
                }
            }


            /****************************************************************************************************
             * Offer :: get_item
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
                    $this->get_data('interest_rate') . '%' . (($this->get_data('description') != '') ? ' &middot; ' . $this->get_data('description') : ''),
                    $this->get_data('date_updated')
                );
            }


            /****************************************************************************************************
             * Offer :: create
             *
             * Create Offer item for create.js
             * @access public static
             *
             */
            public static function create() {
                $title = '[new_offer]';
                $GLOBALS['query'] = "INSERT INTO `" . self::$table . "`(`Title`) VALUES('$title')";
                mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                if (has_no_db_error('for creating new Offer')) {
                    parent::create_helper(new Offer(mysqli_insert_id($GLOBALS['con']), 'for getting newly created Offer'));
                }
            }


            /****************************************************************************************************
             * Offer :: get_form
             *
             * Generate form for Offer object
             * @access public static
             *
             * @param array $data     - the item data
             * @param bool  $for_logs - for system logs or not
             *
             * @return array
             */
            public static function get_form($data, $for_logs) {

                /************************************************************************************************
                 * get_form helper :: get_collection_type_options
                 * Prepare select options for all collection types
                 * @param array $collection_types - all collection types data array
                 * @param array $collection_type  - the current collection type
                 * @return array
                 */
                function get_collection_type_options($collection_types, $collection_type) {
                    $arr = array();
                    for($i=0; $i<sizeof($collection_types); $i++) {
                        array_push($arr, array(
                            'label'    => $collection_types[$i],
                            'value'    => $collection_types[$i],
                            'selected' => $collection_types[$i] == $collection_type
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
                                    'grid'     => 'col-md-8',
                                    'type'     => 'text',
                                    'id'       => 'txt-title',
                                    'label'    => 'TITLE',
                                    'value'    => isset($data['title']) ? $data['title'] : '',
                                    'required' => true
                                ),
                                array(
                                    'grid'     => 'col-md-4',
                                    'type'     => 'percentage',
                                    'id'       => 'txt-interest-rate',
                                    'label'    => 'INTEREST RATE',
                                    'value'    => isset($data['interest_rate']) ? $data['interest_rate'] : 0,
                                    'required' => false
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
                            ),

                            // form_data[0]['rows'][2]
                            array(
                                array(
                                    'grid'     => 'col-md-8',
                                    'type'     => 'select',
                                    'id'       => 'slc-collection-type',
                                    'label'    => 'COLLECTION TYPE',
                                    'options'  => get_collection_type_options($data['collection_types'], $data['collection_type'])
                                ),
                                array(
                                    'grid'     => 'col-md-4',
                                    'type'     => 'number',
                                    'id'       => 'txt-collection-count',
                                    'label'    => 'COLLECTION COUNT',
                                    'value'    => isset($data['collection_count']) ? $data['collection_count'] : 0,
                                )
                            )
                        )
                    )
                );

                return $arr;
            }


            /****************************************************************************************************
             * Offer :: update
             *
             * Update Offer item for update.js
             * @access public static
             *
             */
            public static function update() {
                $offer_id  = intval($_POST[self::$params['update']['key']]);
                $offer     = new Offer($offer_id, 'for update');
                $form_data = $_POST['data'];

                $title            = mysqli_real_escape_string($GLOBALS['con'], strip_tags(trim($form_data[0]['rows'][0]['txt-title'])));
                $interest_rate    = doubleval($form_data[0]['rows'][0]['txt-interest-rate']);
                $description      = mysqli_real_escape_string($GLOBALS['con'], strip_tags(trim($form_data[0]['rows'][1]['txt-desc'])));
                $collection_type  = mysqli_real_escape_string($GLOBALS['con'], strip_tags(trim($form_data[0]['rows'][2]['slc-collection-type'])));
                $collection_count = intval($form_data[0]['rows'][2]['txt-collection-count']);

                // update Offer data
                $GLOBALS['query']  = "UPDATE `" . self::$table . "` ";
                $GLOBALS['query'] .= "SET ";
                $GLOBALS['query'] .= " `Title`='$title', ";
                $GLOBALS['query'] .= " `Description`='$description', ";
                $GLOBALS['query'] .= " `InterestRate`=$interest_rate, ";
                $GLOBALS['query'] .= " `CollectionType`='$collection_type', ";
                $GLOBALS['query'] .= " `CollectionCount`=$collection_count ";
                $GLOBALS['query'] .= "WHERE ";
                $GLOBALS['query'] .= " `" . self::$primary_key . "`=$offer_id";
                mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                if (has_no_db_error('for updating Offer data')) {
                    parent::update_helper(new Offer($offer_id, 'for getting newly updated Offer'), $offer->get_item());
                }
            }


            /****************************************************************************************************
             * Offer :: delete
             *
             * Delete Offer item for delete.js
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
             * Offer :: search
             *
             * Search through Offer records
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
                $GLOBALS['query'] .= " OR `InterestRate`=" . doubleval($q) . " ";
                $GLOBALS['query'] .= " OR `CollectionType` LIKE '%$q%' ";
                $GLOBALS['query'] .= self::$list_order . " ";
                parent::search_helper(get_class());
            }
        }

        if(basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
            $object = new Offer(0, 'for class usage');
            require '__fin.php';
        }
    }
?>
