<?php

    if(defined('INDEX')) {
        $served = false;
        $disabled_param = '';

        /** __fin :: LIST *************************************************************************************/
        if(!$served && isset($object::$params['list'])) {
            if(isset($_POST[$object::$params['list']['key']])) {
                if($object::$params['list']['enabled']) {
                    $served = true;
                    $object::_list($object);
                }
                else
                    $disabled_param = $object::$params['list']['key'];
            }
        }


        /** __fin :: CREATE ***********************************************************************************/
        if(!$served && isset($object::$params['create'])) {
            if(isset($_POST[$object::$params['create']['key']])) {
                if($object::$params['create']['enabled']) {
                    $served = true;
                    $object::create();
                }
                else
                    $disabled_param = $object::$params['create']['key'];
            }
        }


        /** __fin :: SELECT ***********************************************************************************/
        if(!$served && isset($object::$params['select'])) {
            if(isset($_POST[$object::$params['select']['key']])) {
                if($object::$params['select']['enabled']) {
                    $served = true;
                    $object = new $object($_POST[$object::$params['select']['key']], 'for SELECT');
                    $object->select();
                }
                else
                    $disabled_param = $object::$params['select']['key'];
            }
        }


        /** __fin :: UPDATE ***********************************************************************************/
        if(!$served && isset($object::$params['update'])) {
            if(isset($_POST[$object::$params['update']['key']])) {
                if($object::$params['update']['enabled']) {
                    $served = true;
                    $object::update();
                }
                else
                    $disabled_param = $object::$params['update']['key'];
            }
        }


        /** __fin :: DELETE ***********************************************************************************/
        if(!$served && isset($object::$params['delete'])) {
            if(isset($_POST[$object::$params['delete']['key']])) {
                if($object::$params['delete']['enabled']) {
                    $served = true;
                    $object = new $object($_POST[$object::$params['delete']['key']], 'for DELETE');
                    $object->delete();

                }
                else
                    $disabled_param = $object::$params['update']['key'];
            }
        }


        /** __fin :: SEARCH ***********************************************************************************/
        if(!$served && isset($object::$params['search'])) {
            if(isset($_POST[$object::$params['search']['key']])) {
                if($object::$params['search']['enabled']) {
                    $served    = true;
                    $query     = $_POST[$object::$params['search']['key']];
                    $ref_model = isset($_POST['ref_model']) ? $_POST['ref_model'] : '';
                    $ref_key   = isset($_POST['ref_key']) ? intval($_POST['ref_key']) : 0;
                    $object::search($query, $ref_model, $ref_key);
                }
                else
                    $disabled_param = $object::$params['search']['key'];
            }
        }


        /** __fin :: UPLOAD ***********************************************************************************/
        if(!$served && isset($object::$params['upload'])) {
            if(isset($_FILES[$object::$params['upload']['key']])) {
                if($object::$params['upload']['enabled']) {
                    $served = true;
                    $file   = $_FILES[$object::$params['upload']['key']];
                    $dir    = $_POST['dir'];
                    $type   = $_POST['type'];

                    $name      = '';
                    $extension = '';

                    // check upload error
                    $err = $file['error'];
                    if($err != UPLOAD_ERR_OK) {
                        if($err == UPLOAD_ERR_FORM_SIZE)
                            $GLOBALS['response']['success']['sub_message'] = "The file exceeds the <b class='text-info'>MAX_FILE_SIZE</b> directive that was specified in the HTML form.";
                        else if($err == UPLOAD_ERR_INI_SIZE)
                            $GLOBALS['response']['success']['sub_message'] = "The file exceeds the <b class='text-info'>upload_max_filesize</b> directive in <b class='text-info'>php.ini</b>.";
                        else if($err == UPLOAD_ERR_PARTIAL)
                            $GLOBALS['response']['success']['sub_message'] = "The file was only partially uploaded.";
                        else if($err != UPLOAD_ERR_NO_TMP_DIR)
                            $GLOBALS['response']['success']['sub_message'] = "Missing a temporary folder.";
                        else if($err != UPLOAD_ERR_CANT_WRITE)
                            $GLOBALS['response']['success']['sub_message'] = "Failed to write file to disk.";
                        else if($err != UPLOAD_ERR_EXTENSION)
                            $GLOBALS['response']['success']['sub_message'] = "A PHP extension stopped the file upload.";
                        else if($err != UPLOAD_ERR_NO_FILE)
                            $GLOBALS['response']['success']['sub_message'] = "An unknown error occured.";
                    }

                    // check upload path
                    else if(!file_exists(INDEX . $dir))
                        $GLOBALS['response']['success']['sub_message'] = "Upload directory <b class='text-info'>" . $dir . "</b> does not exist.";

                    // check file type
                    else {
                        $fname  = $file['name'];
                        $length = strlen($fname);
                        for ($i = ($length - 1); $i >= 0; $i--) {
                            if (substr($fname, $i, 1) == '.') {
                                $extension = strtolower(substr($fname, $i + 1, $length - ($i + 1)));
                                $name = str_replace(' ', '-', substr($fname, 0, $i));
                                break;
                            }
                        }

                        $allowed_file_types = array();
                        if($type == 'image')
                            $allowed_file_types = array('jpg', 'jpeg', 'png', 'gif');
                        if (!in_array(strtolower($extension), $allowed_file_types))
                            $response['success']['sub_message'] = "Please choose a file with the following file types:<br>{ " . implode(', ', $allowed_file_types) . " }";

                    }
                    if($GLOBALS['response']['success']['sub_message'] != '')
                        $GLOBALS['response']['success']['message'] = 'UNABLE TO UPLOAD FILE';

                    // get final name and upload
                    else {
                        $final_name = '';
                        $ctr = 0;
                        while (true) {
                            $suffix = ($ctr <= 0) ? '' : '-' . str_pad($ctr, 3, '0', STR_PAD_LEFT);
                            $final_name = $name . $suffix . '.' . $extension;
                            if (!file_exists(INDEX . $dir . '/' . $final_name))
                                break;
                            $ctr += 1;
                        }

                        move_uploaded_file($file['tmp_name'], INDEX . $dir . '/' . $final_name);
                        $GLOBALS['response']['success']['data'] = $final_name;
                    }
                }
                else
                    $disabled_param = $object::$params['upload']['key'];
            }
        }


        /** __fin :: CALLBACK *********************************************************************************/
        if(!$served && isset($object::$params['callback'])) {
            if(isset($_POST[$object::$params['callback']['key']])) {
                if($object::$params['callback']['enabled']) {
                    $served = true;
                    $param  = $_POST[$object::$params['callback']['key']];
                    $value  = isset($_POST['value']) ? $_POST['value'] : '';
                    $vals   = isset($_POST['vals']) ? $_POST['vals'] : array();
                    $object::callback($param, $value, $vals);
                }
                else
                    $disabled_param = $object::$params['callback']['key'];
            }
        }


        /** __fin :: (NO REQUEST PROVIDED) ********************************************************************/
        if(!$served) {
            if(isset($_GET['print']) && isset($_GET['id']) && isset($_GET['form'])) {
                $object::print_html(intval($_GET['id']), json_decode('{' . rtrim($_GET['form'], ', ') . '}', true));
                exit();
            }
            else {
                $GLOBALS['response']['success']['message'] = get_class($object) . ': No request provided.';
                if($disabled_param != '')
                    $GLOBALS['response']['success']['sub_message'] = "<code>" . $disabled_param . "</code> parameter is disabled.";
            }
        }

        require INDEX . 'php/db/close.php';
        array_walk($GLOBALS['response'], 'utf8encode');
        echo json_encode($GLOBALS['response']);
    }

?>