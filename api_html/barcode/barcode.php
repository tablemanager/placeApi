<?php
require_once ('/home/sparo.cc/order_script/lib/SendData_Script.php');

//error_reporting(E_ALL);
//ini_set("display_errors", 1);

$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders();            // http 헤더

$itemreq = explode("/",$para);

$type = $itemreq[0];
$code = str_replace(".jpg","", $itemreq[1]);

require_once('./src/BarcodeGenerator.php');
require_once('./src/BarcodeGeneratorPNG.php');
require_once('/home/placedev/cmsapps/common/conf/dbconn.conf.php');

$bar_dir = dirname(__FILE__) . "/temp/";

// debug 모드를 따로둠..
switch (strtolower($type)) {

    case 'debug' :
        $path = make_bar($code);
        set_bar($path);
        break;

        // 단순 이미지만 출력하는 기능
    case 'get' :
//        $path = make_bar($code, true, 400,150);
        $path = make_bar($code, true);

        Header("content-type: image/jpg");
        $img = imagecreatefrompng($path);
        ImageJPEG($img);
        break;

        // 문자 발송시키는 기능
    case 'set' :
        $path = make_bar($code, true);
//        Header("content-type: image/jpg");
//        $img = imagecreatefrompng($path);
//        ImageJPEG($img);
//        exit;

        set_bar($path, $code);

        Header("content-type: image/jpg");
        $img = imagecreatefrompng($path);
        ImageJPEG($img);
        break;

    default :
        echo "METHOD Err";
        exit;
        break;
}

// 바코드 출력시, 텍스트 노출할것인가.
function make_bar($code, $flag = FALSE, $width = 400, $height = 100)
{
    global $bar_dir;

    $generatorPNG = new Picqer\Barcode\BarcodeGeneratorPNG();
    $img = base64_encode($generatorPNG->getBarcode($code, $generatorPNG::TYPE_CODE_128, 1.8, 100, array('0', '0', '0')));
    $path = $bar_dir . $code . ".png";
    file_put_contents($path, base64_decode($img)); //덮어쓰기(저장)

    // 이미지 전체 크기 (현재는 400)
//    $width = 400;
    // ===================================
    // 흰 배경화면 만들기..
    // ===================================
    $b_img = imagecreatetruecolor($width, $height);
    $back_gu = imagecolorallocate($b_img, 255, 255, 255);         // 백그라운드의 컬러를 지정합니다.
    imagefilledrectangle($b_img, 0, 0, $width, $height, $back_gu);                // BG-Color 지정하는 공간..
    imagepng($b_img, $bar_dir . $code . "_bg.png");
    imagedestroy($b_img);
    // ===================================

    // ===================================
    // 배경화면과, 바코드 합성해서 흰바탕에 바코드 출력
    // ===================================
    $img = imagecreatefrompng($path);

    // 이거.. 문제가 있음.. 좀더 봐야함..
    $add = abs($width - imagesx($img) ) / 2;

    $bg_img = imagecreatefrompng($bar_dir . $code . "_bg.png");
    $new = imagecreatetruecolor($width, $height);
    imagecopy($new, $bg_img, 0, 0, 0, 0, $width, $height);
    imagecopy($new, $img, $add, 10, 0, 0, 315, 100);
    imagepng($new, $path);
    imagedestroy($new);
    // ===================================

    // 플래그 여부를 통하여, 텍스트를 출력해서 붙일것인지..
    if ($flag == TRUE) {

        $font = "./font/NotoSansCJKkr-Medium.otf"; // 텍스트에 사용한 폰트파일을 지정합니다
        $path2 = $bar_dir . $code . "_bar.png";

//        $ch_code = substr($code, '0', '2');
//        if ($ch_code == "EL" || $ch_code == "CB") {
//            $code = substr($code, '0', '6') . "-" . substr($code, '6', '4') . "-" . substr($code, '10', '4') . "-" . substr($code, '14', '4');
//            $qr_x = '50';
//        }

        // 0 ~ 17 : 279, 18 ~ 이상은 : 315
        $box = imageftbbox(15, 0, $font, $code);
        $x = $width / 2 - ($box[4] - $box[0]) / 2;

        // ===================================
        // 텍스트 생성
        // ===================================
        $text_img = imagecreatetruecolor($width, 30);
        $bar_bc = imagecolorallocate($text_img, 255, 255, 255);         // 백그라운드의 컬러를 지정합니다.
        $bar_tc = imagecolorallocate($text_img, 0, 0, 0);               // 텍스트의 컬러를 지정합니다.

        imagefilledrectangle($text_img, 0, 0, $width, 50, $bar_bc);                // BG-Color 지정하는 공간..
        imagettftext($text_img, 15, 0, $x, 21, $bar_tc, $font, $code);
        imagepng($text_img, $path2);
        imagedestroy($text_img);
        // ===================================

        // ===================================
        // 바코드와 생성한 글자 합성.
        // ===================================
        $bar = imagecreatefrompng($path);
        $bar_text = imagecreatefrompng($path2);

        $new = imagecreatetruecolor($width, 100 + 30);
        imagecopy($new, $bar, 0, 0, 0, 0, $width, 100);
        imagecopy($new, $bar_text, 0, 100, 0, 0, $width, 30);
        imagepng($new, $path);
        imagedestroy($new);
        // ===================================
    }

    return $path;
}

function set_bar($path, $code, $item = array())
{
    global $bar_dir;

    $width = 400;
    $font = "./font/NotoSansCJKkr-Medium.otf"; // 텍스트에 사용한 폰트파일을 지정합니다
    $path2 = $bar_dir . $code . "_text.png";

    $text = "[지류] CB 11월휴일권(~11/30) @24.9";
    list($bg_r, $bg_g, $bg_b) = hex2rgb('#27bfe0');

    $addimg_path = '';
    $item['add_img'] = "http://pcms.placem.co.kr/uploads/7_20180814025458.png";
    if ($item['add_img'] != '') {
        $addimg_path = get_img($code, $item['add_img']);
//        xmp(mime_content_type($addimg_path));
        $addimg = imagecreatefrompng($addimg_path);
    }

    // 0 ~ 17 : 279, 18 ~ 이상은 : 315
    $box = imageftbbox(15, 0, $font, $text);
    $x = $width / 2 - ($box[4] - $box[0]) / 2;

    // ===================================
    // 텍스트 생성
    // ===================================
    $text_img = imagecreatetruecolor($width, 30);
    $bar_bc = imagecolorallocate($text_img, $bg_r, $bg_g, $bg_b);                            // 백그라운드의 컬러를 지정합니다.
    $bar_tc = imagecolorallocate($text_img, 0, 0, 0);                     // 텍스트의 컬러를 지정합니다.

    imagefilledrectangle($text_img, 0, 0, $width, 50, $bar_bc);                 // BG-Color 지정하는 공간..
    imagettftext($text_img, 15, 0, $x, 21, $bar_tc, $font, $text);
    imagepng($text_img, $path2);
    imagedestroy($text_img);
    // ===================================

    // ===================================
    // 바코드와 생성한 글자 합성.
    // ===================================
    $bar = imagecreatefrompng($path);
    $bar_text = imagecreatefrompng($path2);

    $height = 100 + 30 + 30;
    if ($addimg_path != '') $height += imagesy($addimg);

    $new = imagecreatetruecolor($width, $height);
    imagecopy($new, $bar, 0, 0, 0, 0, $width, 130);
    imagecopy($new, $bar_text, 0, 130, 0, 0, $width, 30);
    if ($addimg_path != '') imagecopy($new, $addimg, 0, 160, 0, 0, $width, $height);

    imagepng($new, $path);
    imagedestroy($new);
}



// ===============================================
// 쿠폰코드를 통하여, 바코드 옵션 구하기
// `pcms_extcoupon` => `cms_coupon` => `pcms_sticket`
// 조회한 순서대로.. 간다
// ===============================================
function get_bar_option($code)
{
    global $conn_cms, $conn_cms3;

    // $conn_cms3 => 7번서버
    $pcms_extcoupon_sql = @"SELECT couponno,sellcode
                                FROM spadb.`pcms_extcoupon`
                                WHERE `couponno` = '" . mysqli_real_escape_string($conn_cms3, $code) . "'
                           ";
    $result = $conn_cms3->query($pcms_extcoupon_sql);
    $sellcode = $result->fetch_array()['sellcode'];
    if (is_null($sellcode)) return false;

    // $conn_cms => 2번서버
    $cms_coupon_sql = @"SELECT ccode,items_id
                            FROM pcmsdb.`cms_coupon`
                            WHERE `ccode` = '" . mysqli_real_escape_string($conn_cms, $sellcode) . "'
                       ";
    $result2 = $conn_cms->query($cms_coupon_sql);
    $itemid = $result2->fetch_array()['items_id'];
    if (is_null($itemid)) return false;

    // $conn_cms3 => 7번서버
    $pcms_sticket_sql = @"SELECT pcms_id, bar_option, addimg
                            FROM spadb.`pcms_sticket`
                            WHERE `pcms_id` = '" . mysqli_real_escape_string($conn_cms3, $itemid) . "' and code_visible = 'Y' and mms_visible = 'Y'
                        ";

    $result3 = $conn_cms3->query($pcms_sticket_sql);

    $row = $result3->fetch_array();
    $bar_option = json_decode($row['bar_option'],true);

    // 하단이미지 추가..
    if ($row['addimg'] != "") $bar_option['addimg'] = $row['addimg'];

    return is_null($bar_option) ? false : $bar_option;
}

function get_img($code, $url)
{
    global $bar_dir;

    $im_chk = false;

    // 파일 다운로드
    $filename = $code . '_addimg.png';
    $path_filename = $bar_dir . $filename;

    // 1 = GIF, 2 = JPG, 3 = PNG
    list($url_width, $url_height, $url_type) = getimagesize($url);

    switch ($url_type) {
        case '2' :
            $im = imagecreatefromjpeg($url);
            imagepng($im, $path_filename);
            imagedestroy($im);
            $im_chk = true;
            break;

        // 1 = GIF, 2 = JPG, 3 = PNG
        // 굳이 다른작업을 안해도되는데.. 확인차 함..
        case '1' :
        case '3' :
            $im = imagecreatefrompng($url);
            imagepng($im, $path_filename);
            imagedestroy($im);
            $im_chk = true;
            break;
    }

    if($im_chk == false) $path_filename = '';

    return $path_filename;
}


function hex2rgb($hex)
{
    $hex = str_replace("#", "", $hex);

    if (strlen($hex) == 3) {
        $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
        $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
        $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    $rgb = array($r, $g, $b);

    return $rgb; // returns an array with the rgb values
}


?>
