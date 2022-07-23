<?php

    session_start();

    define('INDEX', '../../');
    $page = '';

    require INDEX . "php/inc/APP.php";
    require INDEX . 'php/misc.cookies.php';
    require INDEX . 'php/db/open.php';

    $query = "";
    $response = array(
        'error'   => '',
        'success' => array(
            'message'     => '',
            'sub_message' => '',
            'data'        => ''
        )
    );

    /**
     * has_no_db_error
     *
     * Check for sql error in the most recent query
     * of a mysqli object
     * @param string $str_activity - action description of the query
     *
     * @return boolean
     */
    function has_no_db_error($str_activity) {
        $error_tag = '';
        $error_msg = '';
        if(mysqli_error($GLOBALS['con'])) {
            $error_tag = 'DEFAULT';
            $error_msg = mysqli_error($GLOBALS['con']);
        }
        if($error_tag != '') {

            // get the first parameter of the request
            $request = '&dollar;_';
            $first_param = '';
            foreach($_POST as $key => $val) {
                $request .= 'POST';
                $first_param = $key;
                break;
            }
            foreach($_GET as $key => $val) {
                $request .= 'GET';
                $first_param = $key;
                break;
            }

            // configure the response
            $GLOBALS['response']['success']['message'] = "<b class='monospace'>" . $request . "['" . implode(' ', explode('_', $first_param)) . "']</b>";
            $GLOBALS['response']['success']['sub_message'] = "<span style='font-size: 1.2em'>" . $error_tag . " DB CONNECTION ERROR in " .  $str_activity . ".</span><br><br><span class='monospace'>" . "<span class='text-primary'>" . $GLOBALS['query'] . "</span><br><span class='text-danger'>" . $error_msg . "</span></span>";
            return false;

        }
        else
            return true;
    }

    /**
     * utf8encode
     *
     * utf8_encode array elements in order to properly
     * json_encode strings with spanish characters such as ñ
     *
     * USAGE SAMPLE: array_walk($YOUR_ARRAY_VARIABLE, 'utf8encode');
     */
    function utf8encode(&$value, $key) {
        if(is_array($value)) {
            array_walk($value, 'utf8encode');
        }
        else {
            if(is_string($key)) {
                if(!mb_check_encoding($key, 'UTF-8'))
                    $key = utf8_encode($key);
            }
            if(is_string($value)) {
                if(!mb_check_encoding($value, 'UTF-8'))
                    $value = utf8_encode($value);
            }
        }
    }

    /**
     * append_to_system_log
     *
     * Append record to system log
     * @param array $arr - the system log details
     *
     */
    function append_to_system_log($arr) {
        array_walk($arr['item'], 'utf8encode');
        array_walk($arr['item_data'], 'utf8encode');
        if($_POST['tab'] == 'USER_ACCOUNTS') {
            if(isset($arr['item_data']['password'])) {
                $arr['item_data']['password'] = '****************';
            }
        }

        $GLOBALS['query'] = "INSERT INTO `system_logs` ";
        $GLOBALS['query'] .= "(`CitizenID`, `UserTypeAcronym`, `UserTypeTitle`, `Action`, `Location`, `Menu`, `Tab`, `Item`, `ItemData`) ";
        $GLOBALS['query'] .= "VALUES(";
        $GLOBALS['query'] .= $GLOBALS['arr_admin_details']['citizen_id'] . ", ";
        $GLOBALS['query'] .= "'" . mysqli_real_escape_string($GLOBALS['con'], $GLOBALS['arr_admin_details']['user_type_acronym']) . "', ";
        $GLOBALS['query'] .= "'" . mysqli_real_escape_string($GLOBALS['con'], $GLOBALS['arr_admin_details']['user_type_title']) . "', ";
        $GLOBALS['query'] .= "'" . mysqli_real_escape_string($GLOBALS['con'], $arr['action']) . "', ";
        $GLOBALS['query'] .= "'" . mysqli_real_escape_string($GLOBALS['con'], $_POST['location']) . "', ";
        $GLOBALS['query'] .= "'" . mysqli_real_escape_string($GLOBALS['con'], $_POST['menu']) . "', ";
        $GLOBALS['query'] .= "'" . mysqli_real_escape_string($GLOBALS['con'], $_POST['tab']) . "', ";
        $GLOBALS['query'] .= "'" . mysqli_real_escape_string($GLOBALS['con'], json_encode($arr['item'])) . "', ";
        $GLOBALS['query'] .= "'" . mysqli_real_escape_string($GLOBALS['con'], json_encode($arr['item_data'])) . "'";
        $GLOBALS['query'] .= ")";

        mysqli_query($GLOBALS['con'], $GLOBALS['query']);
        $str = ($arr['action'] != 'DELETE') ? 'to' : 'from';
        $x = has_no_db_error('writing to system log [' . $arr['action'] . ' ' . $str . ' ' . $_POST['tab'] . ']');
    }


    /**
     * fin
     *
     * Force exit
     *
     */
    function fin() {
        require INDEX . 'php/ajax/__fin.php';
        exit();
    }

?>
