<?php
/*
 * QR코드 만드는 기능...
 * 개발자 : 김민태
 * 날짜 : 2018-05-18
 * URL : http://gateway.sparo.cc/assets/CB5000648000004422.jpg
 */


// 호출 URL : https://gateway.sparo.cc/assets/CB5000651498097510.jpg
$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders();            // http 헤더

$itemreq = explode("/",$para);
$code = str_replace(".jpg","", $itemreq[0]);

$cp_prefix = substr($code,0,2);
$cp_len = mb_strlen($code);

switch($cp_prefix){
	case 'EL':
	case 'CB':
	case '17':
		$_iscode = false;
	break;
	default:
		$_iscode = true;
}

if($_iscode) {
    header("HTTP/1.1 400 Bad Request");
    http_response_code(400);
    exit;
}

// QR lib 불러오기...
require('./lib/phpqrcode/qrlib.php');
require_once('/home/placedev/cmsapps/common/conf/dbconn.conf.php');

$qr_dir = dirname(__FILE__) . "/temp/";
$text = get_bar_option($code);

img_make($code);
get_text($code, $text);
combine_img($code, $text['addimg']);
exit;


// ===============================================
// 기본 QR 그려내기.
// ===============================================
function img_make($code, $debug = false)
{
    global $qr_dir;

    if ($debug) Header("content-type: image/png");

    // 순차적으로..
    // $code : QR 데이터
    // $path = 경로 (fasle 로 할경우, 바로출력으로 디버깅용)
    // error correction level QR_ECLEVEL_L, QR_ECLEVEL_M, QR_ECLEVEL_Q or QR_ECLEVEL_H
    // 크기, 마진값 순으로
    QRcode::png($code, $debug == false ? $qr_dir . $code . '.png' : false, QR_ECLEVEL_H, 9, 5);
}

function combine_img($code, $img_url = '')
{
    global $qr_dir;

    $addimg_path = '';
    if ($img_url != '') {
        $addimg_path = get_img($code, $img_url);
        //xmp(mime_content_type($addimg_path));
        $addimg = imagecreatefrompng($addimg_path);
    }

    $qr = imagecreatefrompng($qr_dir . $code . '.png');
    $qr_code = imagecreatefrompng($qr_dir . $code . "_qr.png");
    $text = imagecreatefrompng($qr_dir . $code . "_bottom.png");

    // 이미지의 따른 사이즈 변화를 위하여 설정함.
    list($top_width, $top_height) = getimagesize($qr_dir . $code . '.png');
    list($middle_width, $middle_height) = getimagesize($qr_dir . $code . "_qr.png");
    list($bottom_width, $bottom_height) = getimagesize($qr_dir . $code . "_bottom.png");

    $new_width = $top_width;
    $new_height = $top_height + $bottom_height + $middle_height - 25;

    if ($addimg_path != '') {
        list($addimg_width, $addimg_height) = getimagesize($addimg_path);
        $new_height = $new_height + $addimg_height;
    }

    $new = imagecreatetruecolor($new_width, $new_height);
    imagecopy($new, $qr, 0, 0, 0, 0, $top_width, $top_height);
    imagecopy($new, $qr_code, 0, $top_height - 25, 0, 0, $middle_width, $middle_height);
    imagecopy($new, $text, 0, $top_height + $middle_height - 25, 0, 0, $bottom_width, $bottom_height);

    if ($addimg_path != '') imagecopy($new, $addimg, 0, $top_height + $middle_height + $bottom_height - 25, 0, 0, $addimg_width, $addimg_height);

//    unlink($qr_dir . $code . '.png');
//    unlink($qr_dir . $code . "_qr.png");
//    unlink($qr_dir . $code . "_bottom.png");

    Header("content-type: image/jpg");
    // save to file
    ImageJPEG($new);
    //imagepng($new, './temp/merged_image.png');
}

function get_text($code, $text, $debug = false)
{
    global $qr_dir;

    $qr_path = $debug == true ? false : $qr_dir . $code . "_qr.png";
    $text_path = $debug == true ? false : $qr_dir . $code . "_bottom.png";
    if ($debug) Header("content-type: image/png");

    $font = "./font/NotoSansCJKkr-Medium.otf"; // 텍스트에 사용한 폰트파일을 지정합니다

    $qr_x = '60';
    // 채널이 CB, EL 인경우~ 캐비/에버에서... 이렇게 해달라고함.
    $ch_code = substr($code,'0','2');
    if ($ch_code == "EL" || $ch_code == "CB") {
        $code = substr($code,'0','6')."-".substr($code,'6','4')."-".substr($code,'10','4')."-".substr($code,'14','4');
        $qr_x = '50';
    }

    // 0 ~ 17 : 279, 18 ~ 이상은 : 315
    $width = strlen($code) >= 18 ? 315 : 279;

    // QR이미지에 Code값 출력하는...
    $qr_img = imagecreatetruecolor($width, 30);
    $qr_bc = imagecolorallocate($qr_img, 255, 255, 255);         // 백그라운드의 컬러를 지정합니다.
    $qr_tc = imagecolorallocate($qr_img, 0, 0, 0);               // 텍스트의 컬러를 지정합니다.

    imagefilledrectangle($qr_img, 0, 0, $width, 50, $qr_bc);                // BG-Color 지정하는 공간..
    imagettftext($qr_img, 14, 0, $qr_x, 21, $qr_tc, $font, $code);
    imagepng($qr_img, $qr_path);
    imagedestroy($qr_img);

    // $text가 array 이면 ( DB조회가 되었으면, 하는거임 )
    if (is_array($text)) {
        if (empty($text['textcolor'])) $text['textcolor'] = "#000000";
        list($bc_r, $bc_g, $bc_b) = hex2rgb($text['barcolor']);
        list($tc_r, $tc_g, $tc_b) = hex2rgb($text['textcolor']);

        // 이미지 생성 - ImageCreate와 같은 기능
        $img = imagecreatetruecolor($width, 50);
        $bc = imagecolorallocate($img, $bc_r, $bc_g, $bc_b);                // 백그라운드의 컬러를 지정합니다.
        $tc = imagecolorallocate($img, $tc_r, $tc_g, $tc_b);         // 텍스트의 컬러를 지정합니다.
				$fontsize = 12; //하단바 텍스트 크기 설정 - 2022.07.06 ila (18->12로 축소, 헤니요청)

        $box = imageftbbox($fontsize, 0, $font, $text['bartext']);
        $x = $width / 2 - ($box[4] - $box[0]) / 2;

        // 세로 사이즈는 고정해둠.. 가로는 유동적으로 변할 수 있음.
        imagefilledrectangle($img, 0, 0, $width, 50, $bc);                      // BG-Color 지정하는 공간..
        imagettftext($img, $fontsize, 0, $x, 32, $tc, $font, $text['bartext']);
        imagepng($img, $text_path);
        imagedestroy($img);
    }
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
    global $qr_dir;

    $im_chk = false;

    // 파일 다운로드
    $filename = $code . '_addimg.png';
    $path_filename = $qr_dir . $filename;

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

function xmp($text)
{
    echo "<xmp>";
    print_r($text);
    echo "</xmp>";
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
