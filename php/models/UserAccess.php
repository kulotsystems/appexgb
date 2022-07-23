<?php
    require '__init.php';

    if(!class_exists('UserAccess')) {

        class UserAccess {

            /****************************************************************************************************
             * UserAccess :: get_all
             *
             * Get all user access.
             * @access public static
             *
             * @return array
             *
             */
            public static function get_all() {
                $arr = array();
                require INDEX . 'ui/meta.admin.menu.php';
                for($i=0; $i<sizeof($arr_menu_items); $i++) {
                    $arr_tabs = $arr_menu_items[$i]['tabs'];
                    for($j=0; $j<sizeof($arr_tabs); $j++) {
                        array_push($arr, $arr_tabs[$j]);
                    }
                }
                return $arr;
            }
        }
    }
?>