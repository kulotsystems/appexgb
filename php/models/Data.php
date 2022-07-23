<?php

    if(!class_exists('Data')) {

        class Data {

            /****************************************************************************************************
             * Data :: shift
             *
             * Custom encryption algorithm using character shifting
             * @access public static
             *
             * @param string $input_str  - the string to be encrypted / decrypted
             * @param string $encrypting - true for encrypting, false for decrypting
             *
             * @return string
             *
             */
            public static function shift($input_str, $encrypting = true) {
                if (!$encrypting) $input_str = base64_decode($input_str);
                $symbols = array('E','B','P','L','S','d','W','c','X','b','Y','a','Z','z','0','1','y','2','R','3','x','4','5','w','6','D','7','v','8','A','9','u','~','`','&','q','*','J','(','p',')','K','_','o','-','I','+','n','=','M',',','m','<','N','.','l','>','O','/','k','?','V','[','j',']','Q',';','i',':','F','!','t','@','G','#','s','H','%','r','^',"'", '"','h','|','\\','C',' ','g','T','f','U','e');
                $symbols_length = sizeof($symbols);
                $input_length   = strlen($input_str);
                $k = intval($input_length / 3);
                $k = ($encrypting) ? $k : $symbols_length - $k;
                $output_text = "";
                for ($i = 0; $i < $input_length; $i++) {
                    $input_char = substr($input_str, $i, 1);
                    $append_char = $input_char;
                    for ($j = 0; $j < $symbols_length; $j++) {
                        $symbols_char = $symbols[$j];
                        if ($input_char == $symbols_char) {
                            $append_char_pos = ($j + $k) % $symbols_length;
                            $append_char = $symbols[$append_char_pos];
                            break;
                        }
                    }
                    $output_text = $output_text . $append_char;
                }
                if ($encrypting) $output_text = trim(base64_encode($output_text), '=');
                return $output_text;
            }
        }
    }

?>