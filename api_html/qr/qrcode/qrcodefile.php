<?php
/**
 * Created by IntelliJ IDEA.
 * User: Connor
 * Date: 2018-10-17
 * Time: 오후 2:25
 */


// 호출 URL : https://gateway.sparo.cc/assets/CB5000651498097510.jpg
$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders();            // http 헤더

$itemreq = explode("/",$para);
$code = str_replace(".jpg","", $itemreq[0]);

//if (mb_strlen($code) != 18) {
//    header("HTTP/1.1 400 Bad Request");
//    http_response_code(400);
//    exit;
//}

// QR lib 불러오기...
require('../lib/phpqrcode/qrlib.php');
//require_once('/home/placedev/cmsapps/common/conf/dbconn.conf.php');

img_make($code);
exit;


// ===============================================
// 기본 QR 그려내기.
// ===============================================
function img_make($code, $debug = false)
{
    if ($debug) Header("content-type: image/png");

    // 순차적으로..
    // $code : QR 데이터
    // $path = 경로 (fasle 로 할경우, 바로출력으로 디버깅용)
    // error correction level QR_ECLEVEL_L, QR_ECLEVEL_M, QR_ECLEVEL_Q or QR_ECLEVEL_H
    // 크기, 마진값 순으로

    Header("content-type: image/png");

    QRcode::png($code, false, QR_ECLEVEL_H, 9, 5);
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