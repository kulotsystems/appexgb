<?php

    $db_hosts = array(
        'local' => array(
            'host'  => 'localhost',
            'user'  => 'root',
            'pass'  => '',
            'dbase' => 'appexgb'
        )
    );

    // === EDIT ==================================

    $active_db_host = 'local';

    // ===========================================

    $db_host = $db_hosts[$active_db_host]['host'];
    $db_user = $db_hosts[$active_db_host]['user'];
    $db_pass = $db_hosts[$active_db_host]['pass'];
    $dbase   = $db_hosts[$active_db_host]['dbase'];

    $con = mysqli_connect($db_host, $db_user, $db_pass, $dbase);
?>