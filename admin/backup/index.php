<?php

    /****************************************************************************************************
     * PERFORM DATABASE ENCRYPTION AND BACKUP
     * Imported database to look for: 'appexgb'
     *
     */
    define('INDEX'  , '../../');
    define('DB_NAME', 'appexgb');
    require INDEX . 'admin/backup/inc.php';


    /****************************************************************************************************
     * ENCRYPTION FUNCTION
     *
     */
    function encrypt($data) {
        $public_key = file_get_contents('public.pem');
        if (openssl_public_encrypt($data, $encrypted, $public_key))
            $data = base64_encode($encrypted);
        else
            throw new Exception('Unable to encrypt data. Perhaps it is bigger than the key size?');
        return $data;
    }

    export_database($db_host, $db_user, $db_pass, DB_NAME, $tables, $except, $crypt, true);

?>
