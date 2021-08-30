<?php

function respond_error_to_api_v1($message, $return_array = array(), $error_code = 400, $applyFilter = false)
{
    log_message('error', "Respond Error To API [Message: " . $message . "]");
    $res["success"] = 0;
    $res["status_code"]   = $error_code;
    // Uppercase after dot
    preg_match_all("/\.\s*\w/", $message, $matches);
    foreach ($matches[0] as $match) {
        $message = str_replace($match, strtoupper($match), $message);
    }
    $res["message"] = str_replace("otp", "OTP", ucfirst(strtolower(str_replace("_", " ", $message))));
    $res["error"]   = $return_array;
    respond_to_api($res, $applyFilter);
}

function respond_server_validation_v1($message, $fieldName, $message_validation, $return_array = array(), $error_code = 400, $applyFilter = false)
{
    log_message('error', "Respond Server validation To API [Message: " . $message . "]");
    $res["success"] = 0;
    $res["status_code"]   = $error_code;
    $res["message"] = trim(str_replace("emr", "EMR", str_replace("otp", "OTP", ucfirst(strtolower($message)))));
    $res["error"]   = $return_array;
    $res["error"]['errorData'][0]['index']       = 0;
    $res["error"]['errorData'][0]['fieldName']   = $fieldName;
    // Uppercase after dot
    preg_match_all("/\.\s*\w/", $message_validation, $matches);
    foreach ($matches[0] as $match) {
        $message_validation = str_replace($match, strtoupper($match), $message_validation);
    }
    $res["error"]['errorData'][0]['message']     = trim(str_replace("emr", "EMR", str_replace("otp", "OTP", ucfirst(($message_validation)))));

    respond_to_api($res, $applyFilter);
}

function respond_success_to_api_v1($message, $return_array = array(), $applyFilter = false)
{
    
    log_message('error', "Respond Success To API [Message: " . $message . "]");
    $res["success"]       = 1;
    $res["status_code"]   = 200;
    $res["message"]       = str_replace("otp", "OTP", ucfirst(strtolower(str_replace("_", " ", $message))));
    $res["data"]          = $return_array;

    respond_to_api($res, $applyFilter);
}

function check_api_keys_v1($keys, $mydata, $is_filter = false)
{
    $ci = &get_instance();
    $data = array();
    //filter keys array
    $keys = filter_input_param($keys);
    foreach ($keys as $index => $key) {
        if (array_key_exists($key, $mydata)) {
            if (is_array($mydata[$key])) {
                $data[$key] = $mydata[$key];
            } else {
                $data[$key] = encode_php_tags(strip_tags(trim($mydata[$key])));
                if ($is_filter)
                    $data[$key] = encode_php_tags((trim($mydata[$key])));
            }
        } else {
            $arr = array($key);
            $message = "key missing ". $key;
            $res["errorData"][] = array(
                "index" => $index,
                "fieldName" => $key,
                "message" => str_replace("otp", "OTP", ucfirst(strtolower(str_replace("_", " ", $message))))
            );
        
            log_message('error', "key missing" . json_encode($res, true));
            
            respond_error_to_api_v1($message, $res);
        }
    }

    unset($keys);
    return $data;
}


function slugify($text)
{
    // replace non letter or digits by -
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);

    // transliterate
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

    // remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);

    // trim
    $text = trim($text, '-');

    // remove duplicate -
    $text = preg_replace('~-+~', '-', $text);

    // lowercase
    $text = strtolower($text);

    if (empty($text)) {
        return 'n-a';
    }

    return $text;
}


/**
 * @param $res
 * common function to send response and exit
 */
function respond_to_api($res, $applyFilter = false)
{
    //filter tags added in validation
    if ($applyFilter) {
        $res = filter_input_param($res);
    }

    header("Content-Type: application/json;");
    echo json_encode($res);
    unset($res);
    die;
}

/**
 *  Return array with encodes Ids
 * @param array
 */
function getEncodesids($array)
{
    if (!empty($array)) {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = getEncodesids($value);
            } else {
                $array[$key] = is_null($array[$key]) ? "" : $array[$key];
                //encodes all key if id found in key
                if (stristr($key, "id") && $key != "validat" && $key != "identifier") {
                    $array[$key] = encodes($value);
                }
            }
        }
    }
    return $array;
}
/**
 *  Return array with encodes Ids
 * @param array
 */
function getEncodesObjectIds($array)
{
    if (!empty($array)) {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = getEncodesObjectIds($value);
            } else if(is_object($value)){

                $array[$key] = (object)getEncodesObjectIds((array)$value);
            } else {
                $array[$key] = is_null($array[$key]) ? "" : $array[$key];
                //encodes all key if id found in key
                if (stristr($key, "id") && $key != "validat") {
                    $array[$key] = encodes($value);
                }
            }
        }
    }
    return $array;
}

/**
 *  Return array with decodes Ids
 * @param array
 */
function getDecodeids($array, $is_1D = false)
{
    if ($is_1D == true) {
        foreach ($array as $key => $value) {
            $array[$key] = decodes($value);
        }
    } else {
        if (!empty($array)) {
            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    $array[$key] = getDecodeids($value);
                } else {
                    //encodes all key if id found in key
                    if ((stristr($key, "field_value") || stristr($key, "id") || $key == "field") && $key != "validat" && $key != "identifier" && is_numeric(decodes($value))) {
                        $array[$key] = decodes($value);
                    }
                }
            }
        }
    }
    return $array;
}


function encodes($string)
{
    $key               = sha1(DATA_ENCRIPTION_KEY);
    $strLen            = strlen($string);
    $keyLen            = strlen($key);
    $j                 = 0;
    $hash              = '';
    for ($i = 0; $i < $strLen; $i++) {
        $ordStr = ord(substr($string, $i, 1));
        if ($j == $keyLen) {
            $j = 0;
        }
        $ordKey = ord(substr($key, $j, 1));
        $j++;
        $hash .= strrev(base_convert(dechex($ordStr + $ordKey), 16, 36));
    }
    return $hash;
}
function decodes($string)
{
    $key               = sha1(DATA_ENCRIPTION_KEY);
    $strLen            = strlen($string);
    $keyLen            = strlen($key);
    $j                 = 0;
    $hash              = '';
    for ($i = 0; $i < $strLen; $i += 2) {
        $ordStr = hexdec(base_convert(strrev(substr($string, $i, 2)), 36, 16));
        if ($j == $keyLen) {
            $j = 0;
        }
        $ordKey = ord(substr($key, $j, 1));
        $j++;
        $hash .= chr($ordStr - $ordKey);
    }
    return $hash;
}

/**
 * @param $array
 * @return array
 * common function for filter inputted data
 */
function filter_input_param($array)
{
    $ret = array();
    if (is_array($array)) {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $ret[$key] = filter_input_param($value);
            } else {
                $ret[$key] = trim($value);
                $ret[$key] = remove_strip_unsafe($value);
                $ret[$key] = strip_tags($value);
                $ret[$key] = stripslashes($value);
                $ret[$key] = htmlentities($value, ENT_QUOTES, 'UTF-8');
                $ret[$key] = urldecode($value);
                $ret[$key] = htmlspecialchars($value);
                $ret[$key] = filter_var($value, FILTER_SANITIZE_STRING);
            }
        }
    } else {
        $ret = trim($array);
        $ret = remove_strip_unsafe($array);
        $ret = strip_tags($array);
        $ret = stripslashes($array);
        $ret = htmlentities($array, ENT_QUOTES, 'UTF-8');
        $ret = urldecode($array);
        $ret = htmlspecialchars($array);
        $ret = filter_var($array, FILTER_SANITIZE_STRING);
    }

    return $ret;
}

/**
 * @param $string
 * @return string
 * common function for filter xss
 */
function remove_strip_unsafe($string, $img = false)
{
    $unsafe = array(
        '/<iframe(.*?)<\/iframe>/is',
        '/<title(.*?)<\/title>/is',
        '/<pre(.*?)<\/pre>/is',
        '/<frame(.*?)<\/frame>/is',
        '/<frameset(.*?)<\/frameset>/is',
        '/<object(.*?)<\/object>/is',
        '/<script(.*?)<\/script>/is',
        '/<embed(.*?)<\/embed>/is',
        '/<applet(.*?)<\/applet>/is',
        '/<meta(.*?)>/is',
        '/<!doctype(.*?)>/is',
        '/<link(.*?)>/is',
        '/<body(.*?)>/is',
        '/<\/body>/is',
        '/<head(.*?)>/is',
        '/<\/head>/is',
        '/onload="(.*?)"/is',
        '/onclick="(.*?)"/is',
        '/onunload="(.*?)"/is',
        '/ondblclick="(.*?)"/is',
        '/onmouseover="(.*?)"/is',
        '/on[^=]+="(.*?)"/is',
        '/<html(.*?)>/is',
        '/<script(.*?)>/is',
        '/<\/html>/is'
    );
    if ($img == true) {
        $unsafe[] = '/<img(.*?)>/is';
    }

    $string = preg_replace($unsafe, "", $string);
    return $string;
}

function pr($arr)
{
    echo "<pre>";
    print_r($arr);
    echo "</pre>";
}


// record count
function get_total_count_record($table_name)
{
    $CI       = &get_instance();
    $CI->db->select('count(*) as cnt');
    if($table_name = "social_media_type")
    {
        $CI->db->where('is_custom', 0);
    }
    $CI->db->from($table_name);
    $query = $CI->db->get()->row();
    return $query->cnt;
}
