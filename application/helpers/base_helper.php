<?php

function getRawInput()
{
    static $rawInput = null;
    if ($rawInput === null) {
        $rawInput = file_get_contents('php://input');
    }
    return $rawInput;
}

/**
 * @return string | array | any | null
 */
function commonRequest($key)
{
    static $cachedObj = null;
    if (isset($_GET[$key])) return is_string($_GET[$key]) ? trim($_GET[$key]) : $_GET[$key];
    if (isset($_POST[$key])) return is_string($_POST[$key]) ? trim($_POST[$key]) : $_POST[$key];
    if (isset($_FILES[$key])) return is_string($_FILES[$key]) ? trim($_FILES[$key]) : $_FILES[$key];


    if ($cachedObj === null) {
        $json = getRawInput();
        $cachedObj = json_decode($json);
    }

    if (isset($cachedObj->$key)) {
        return is_string($cachedObj->$key) ? trim($cachedObj->$key) : $cachedObj->$key;
    }
    return null;
}

function commonRequestAll()
{
    static $cachedData = null;
    $data = [];

    // Lấy dữ liệu từ $_GET và $_POST
    foreach ($_GET as $key => $value) {
        $data[$key] = is_string($value) ? trim($value) : $value;
    }

    foreach ($_POST as $key => $value) {
        $data[$key] = is_string($value) ? trim($value) : $value;
    }

    // Lấy dữ liệu từ $_FILES
    foreach ($_FILES as $key => $file) {
        $data[$key] = $file;
    }

    // Lấy dữ liệu từ JSON input (nếu có)
    if ($cachedData === null) {
        $json = getRawInput();
        $cachedData = json_decode($json, true);
    }

    if (is_array($cachedData)) {
        foreach ($cachedData as $key => $value) {
            $data[$key] = is_string($value) ? trim($value) : $value;
        }
    }

    return $data;
}


if (!function_exists('dd')) {
    /**
     * Dump and Die
     *
     * @param mixed $data
     */
    function dd($data)
    {
        if (is_array($data)) {
            print_r('<pre>');
            print_r($data);
            die();
        } else {
            var_dump($data);
            die();
        }
    }
}
/**
 * Hàm loại bỏ dấu tiếng Việt
 */
function vnToStr($str)
{

    $unicode = array(

        'a' => 'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ',

        'd' => 'đ',

        'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',

        'i' => 'í|ì|ỉ|ĩ|ị',

        'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',

        'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',

        'y' => 'ý|ỳ|ỷ|ỹ|ỵ',

        'A' => 'Á|À|Ả|Ã|Ạ|Ă|Ắ|Ặ|Ằ|Ẳ|Ẵ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',

        'D' => 'Đ',

        'E' => 'É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',

        'I' => 'Í|Ì|Ỉ|Ĩ|Ị',

        'O' => 'Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',

        'U' => 'Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',

        'Y' => 'Ý|Ỳ|Ỷ|Ỹ|Ỵ',

    );

    foreach ($unicode as $nonUnicode => $uni) {

        $str = preg_replace("/($uni)/i", $nonUnicode, $str);
    }
    $str = str_replace(' ', '_', $str);

    return $str;
}

/**
 * Hàm kiểm tra ngày tháng đúng định dạng dd/mm/yyyy
 */
function isValidDate($date)
{
    // Kiểm tra định dạng có đúng dd/mm/yyyy hay không
    if (preg_match("/^([0-9]{2})\/([0-9]{2})\/([0-9]{4})$/", $date, $matches)) {
        // Tách ngày, tháng, năm từ kết quả preg_match
        $day = $matches[1];
        $month = $matches[2];
        $year = $matches[3];

        // Sử dụng hàm checkdate để kiểm tra tính hợp lệ của ngày tháng
        if (checkdate($month, $day, $year)) {
            return true;
        }
    }

    return false;  // Trả về false nếu không hợp lệ
}

/*
* Import excel
* Replace các kí tự đặc biệt được đọc từ file excel
*/
function _htmlSpecialChars($str = '')
{
    $specials = ["\t", "\n", "\r"];
    return trim(str_replace($specials, '', $str));
}


function resSuccess($data = null, $message = 'Success', $status = 200, $success = true, $options = [])
{
    $CI = &get_instance();
    $response = [
        'status' => $status,
        'message' => $message,
        'success' => $success,
        'data' => $data
    ];
    if (!empty($options)) {
        $response = array_merge($response, $options);
    }
    $CI->response($response, $status);
}

function resError($message = 'Error', $status = 500, $data = null, $success = false)
{
    $CI = &get_instance();
    $CI->response([
        'status' => $status,
        'message' => $message,
        'success' => $success,
        'data' => $data
    ], $status);
}

function resBadrequest($error = null, $message = 'Bad Request', $status = 400, $success = false)
{
    $CI = &get_instance();
    $CI->response([
        'status' => $status,
        'message' => $message,
        'success' => $success,
        'error' => $error
    ], $status);
}

//Chuyển đổi dung lượng lớn hơn kb
function exchangeFromKbToLargerCapacity($value)
{
    $value = floatval($value);
    $mb = 1024;
    if ($value >= ($mb * $mb * $mb)) {
        return round($value / ($mb * $mb * $mb), 2) . ' TB';
    } elseif ($value >= ($mb * $mb)) {
        return round($value / ($mb * $mb), 2) . ' GB';
    } elseif ($value >= $mb) {
        return round($value / $mb, 2) . ' MB';
    } else {
        return $value . ' KB';
    }
}

function urlSafeBase64Encode($data)
{
    // Thay đổi `+` thành `-`, `/` thành `_`, và loại bỏ `=`
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

// Hàm giải mã URL-safe không chứa ký tự đặc biệt
function urlSafeBase64Decode($data)
{
    // Thay đổi `-` thành `+`, `_` thành `/`, và thêm `=` nếu thiếu
    return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
}



// Hàm mã hóa user_id
function encryptString($string)
{
    $CI = &get_instance();
    $key = $CI->config->item('encrypt_key');
    $cipher = "AES-128-CTR";
    // $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));
    //giữ $iv cố định
    $iv = str_repeat('0', openssl_cipher_iv_length($cipher));
    $encrypted = openssl_encrypt($string, $cipher, $key, 0, $iv);
    return urlSafeBase64Encode($iv . $encrypted);
}

// Hàm giải mã user_id
// function decryptString($encryptedData)
// {
//     $CI = &get_instance();
//     $key = $CI->config->item('encrypt_key');
//     $cipher = "AES-128-CTR";
//     $encryptedData = urlSafeBase64Decode($encryptedData);
//     $iv_length = openssl_cipher_iv_length($cipher);
//     $iv = substr($encryptedData, 0, $iv_length);
//     $encrypted = substr($encryptedData, $iv_length);
//     return openssl_decrypt($encrypted, $cipher, $key, 0, $iv);
// }
function decryptString($encryptedData)
{
    $CI = &get_instance();
    $key = $CI->config->item('encrypt_key');
    $cipher = "AES-128-CTR";
    $ivLength = openssl_cipher_iv_length($cipher);

    try {
        $decoded = urlSafeBase64Decode($encryptedData);

        // Check if decoded string is long enough
        if (strlen($decoded) < $ivLength) {
            throw new Exception("Chuỗi mã hóa không hợp lệ (IV quá ngắn).");
        }

        $iv = substr($decoded, 0, $ivLength);
        $encrypted = substr($decoded, $ivLength);

        $decrypted = openssl_decrypt($encrypted, $cipher, $key, 0, $iv);
        if ($decrypted === false) {
            throw new Exception("Giải mã thất bại.");
        }

        return $decrypted;
    } catch (Exception $e) {
        log_message('error', 'decryptString(): ' . $e->getMessage());
        return false;
    }
}


function convertDateToISO($value)
{
    if (empty($value)) return null;
    $date = DateTime::createFromFormat('d/m/Y', $value);
    if ($date && $date->format('d/m/Y') === $value) {
        return $date->format('Y-m-d');
    }
    return $value;
}
