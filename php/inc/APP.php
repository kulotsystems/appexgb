<?php

    define('DOMAINS'       , []);
    define('ROOT'          , 'appexgb');
    define('HOST_DIR'      , 'appexgb');
    define('HOST_IS_ONLINE', in_array($_SERVER['SERVER_NAME'], DOMAINS));
    define('HOST_PROTOCOL' , (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443 || HOST_IS_ONLINE) ? "https://" : "http://");
    define('HOST_DOMAIN'   , HOST_PROTOCOL . ( !HOST_IS_ONLINE ? $_SERVER['SERVER_NAME'].'/'.ROOT.'/' : $_SERVER['SERVER_NAME'].'/'));
    define('HOST_ROOT'     , HOST_DOMAIN);

    define('APP_NAME'   , 'Appex GB');
    define('APP_NAME_2' , 'Lending');
    define('APP_TITLE_1', '3rd Floor, AF Bldg., Alfelor St.,');
    define('APP_TITLE_2', 'Corner PNR St., San Miguel, Iriga City');

    define('SYSTEM_JS_ROOT'    , HOST_DOMAIN . 'js/system/');
    define('CITIZEN_AVATAR_DIR', INDEX . 'img/citizens/');
    define('CITIZEN_AVATAR_URL', HOST_DOMAIN . 'img/citizens/');

    date_default_timezone_set('Asia/Manila');
    define('CURRENT_TIME', time());

?>
