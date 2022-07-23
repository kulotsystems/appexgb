<?php

    require '__init.php';

    // admin login
    if(isset($_POST['login_usermane']) && isset($_POST['login_paswsord'])) {
        if(!isset($_SESSION[SESSION_LOGIN_ACCESS_KEY]))
            $response['error'] = 'UNABLE TO LOGIN THIS TIME.';
        else {
            $username = addslashes(trim($_POST['login_usermane']));
            $password = trim(($_POST['login_paswsord']));

            if($username != "" && $password != "") {
                $query = "SELECT `IsActive`, `Password` ";
                $query .= "FROM `lending_users` WHERE `Username`='$username'";
                $result = mysqli_query($con, $query);
                if (has_no_db_error('attempting to login')) {
                    if (mysqli_num_rows($result) < 1)
                        $response['success']['message'] = 'INVALID USERNAME OR PASSWORD';
                    else {
                        $row = mysqli_fetch_assoc($result);
                        $is_active = $row['IsActive'];

                        if ($is_active == '0')
                            $response['success']['message'] = 'INACTIVE ACCOUNT';
                        else {
                            $arr_password = explode('ebpls', $row['Password']);
                            if (sizeof($arr_password) <= 1)
                                $response['success']['message'] = 'INVALID USERNAME OR PASSWORD';
                            else {
                                require INDEX . 'php/models/Data.php';
                                if (Data::shift($arr_password[1], false) == $password) {
                                    $_SESSION[SESSION_ADMIN_USERNAME] = $username;
                                    $_SESSION[SESSION_ADMIN_PASSWORD] = $row['Password'];

                                    unset($_SESSION[SESSION_LOGIN_ACCESS_KEY]);
                                }
                                else {
                                    $response['success']['message'] = 'INVALID USERNAME OR PASSWORD';
                                }
                            }
                        }
                    }
                }
            }
            else {
                $response['success']['message'] = 'INVALID USERNAME OR PASSWORD';
            }
        }
    }

    // admin logout
    else if(isset($_POST['logout_user'])) {
        session_destroy();
        $response['success'] = '1';
    }

    require '__fin.php';

?>
