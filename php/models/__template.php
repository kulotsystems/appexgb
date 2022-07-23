<?php
    require '__init.php';

    if(!class_exists('Template')) {
        class Template extends __init {

            // class variables
            public static $tab_singular         = 'TEMPLATE';
            public static $table                = 'template';
            public static $primary_key          = 'ID';
            public static $primary_zero_allowed = false;
            public static $list_order           = 'ORDER BY `ID`';

            public static $params = array(
                'list'   => array(
                    'key'     => 'list_',
                    'enabled' => true
                ),
                'create' => array(
                    'key'     => 'create_',
                    'enabled' => true
                ),
                'select' => array(
                    'key'     => 'select_',
                    'enabled' => true
                ),
                'update' => array(
                    'key'     => 'update_',
                    'enabled' => true
                ),
                'delete' => array(
                    'key'     => 'delete_',
                    'enabled' => true
                ),
                'search' => array(
                    'key'     => 'search_',
                    'enabled' => true
                )
            );


            /****************************************************************************************************
             * Template :: CONSTRUCTOR
             *
             * Initialize the Template object.
             * @access public
             *
             * @param int    $template_id - the Template id
             * @param string $purpose     - the usage of this operation
             *
             */
            public function __construct($template_id = 0, $purpose = '') {
                $this->data = array(
                    'id' => 0,
                );

                $template_id = intval($template_id);
                if(($template_id > 0 || self::$primary_zero_allowed) && $purpose != 'for class usage') {
                    $purpose = 'getting Template info' . cascade_purpose($purpose);
                    $GLOBALS['query']  = "SELECT ";
                    $GLOBALS['query'] .= " ``, ";
                    $GLOBALS['query'] .= " ``, ";
                    $GLOBALS['query'] .= " ``, ";
                    $GLOBALS['query'] .= "  DATE_FORMAT(`CreatedAt`, '%M %e, %Y &middot; %h:%i %p') AS CreatedAt, ";
                    $GLOBALS['query'] .= "  DATE_FORMAT(`UpdatedAt`, '%M %e, %Y &middot; %h:%i %p') AS UpdatedAt ";
                    $GLOBALS['query'] .= "FROM ";
                    $GLOBALS['query'] .= " `" . self::$table . "` ";
                    $GLOBALS['query'] .= "WHERE ";
                    $GLOBALS['query'] .= " `" . self::$primary_key . "`=$template_id";
                    $result = mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                    if (has_no_db_error($purpose)) {
                        if (mysqli_num_rows($result) > 0) {
                            $row = mysqli_fetch_assoc($result);

                            $this->data = array(
                                'id'           => $template_id,
                                'title'        => $row['Title'],
                                'date_created' => $row['CreatedAt'],
                                'date_updated' => $row['UpdatedAt']
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
             * Template :: get_item
             *
             * Get the data to be displayed on items list
             * @access public
             *
             * @return array
             */
            public function get_item() {
                return $this->get_item_helper(
                    '',
                    '',
                    '',
                    '',
                    ''
                );
            }


            /****************************************************************************************************
             * Template :: create
             *
             * Create Template item for create.js
             * @access public static
             *
             */
            public static function create() {
                $value = '?';
                $GLOBALS['query'] = "INSERT INTO `" . self::$table . "`(`Field`) VALUES('$value')";
                mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                if (has_no_db_error('for creating new Template')) {
                    parent::create_helper(new Template(mysqli_insert_id($GLOBALS['con']), 'for getting newly created Template'));
                }
            }


            /****************************************************************************************************
             * Template :: get_form
             *
             * Generate form for Template object
             * @access public static
             *
             * @param array $data     - the item data
             * @param bool  $for_logs - for system logs or not
             *
             * @return array
             */
            public static function get_form($data, $for_logs) {
                $arr = array();

                return $arr;
            }


            /****************************************************************************************************
             * Template :: update
             *
             * Update Template item for update.js
             * @access public static
             *
             */
            public static function update() {
                $template_id = intval($_POST[self::$params['update']['key']]);
                $template    = new Template($template_id, 'for update');
                $form_data   = $_POST['data'];

                $value       = mysqli_real_escape_string($GLOBALS['con'], strip_tags(trim($form_data[0]['rows'][0]['id'])));

                // update Template data
                $GLOBALS['query']  = "UPDATE `" . self::$table . "` ";
                $GLOBALS['query'] .= "SET ";
                $GLOBALS['query'] .= " `Field`='$value', ";
                $GLOBALS['query'] .= " `Field`='$value' ";
                $GLOBALS['query'] .= "WHERE ";
                $GLOBALS['query'] .= " `" . self::$primary_key . "`=$template_id";
                mysqli_query($GLOBALS['con'], $GLOBALS['query']);
                if (has_no_db_error('for updating Template data')) {
                    parent::update_helper(new Template($template_id, 'for getting newly updated Template'), $template->get_item());
                }
            }


            /****************************************************************************************************
             * Template :: delete
             *
             * Delete Template item for delete.js
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
             * Template :: search
             *
             * Search through Template records
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
                $GLOBALS['query'] .= " OR `Field` LIKE '%$q%' ";
                $GLOBALS['query'] .= "ORDER BY ";
                $GLOBALS['query'] .= " `Field` ";
                parent::search_helper(get_class());
            }
        }

        if(basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
            $object = new Template(0, 'for class usage');
            require '__fin.php';
        }
    }
?>