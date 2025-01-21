<?php
/**
 * Created by tony.
 * User: Tony
 * Date: 2022-01-18
 * 일회용로그인용 QR 바코드 출력
 */

define('URL_FORMAT',
'/^(https?):\/\/'.                                         // 프로토콜
'(([a-z0-9$_\.\+!\*\'\(\),;\?&=-]|%[0-9a-f]{2})+'.         // 사용자 이름
'(:([a-z0-9$_\.\+!\*\'\(\),;\?&=-]|%[0-9a-f]{2})+)?'.      // 비밀번호
'@)?(?#'.                                                  // 인증에는 @가 필요합니다.
')((([a-z0-9]\.|[a-z0-9][a-z0-9-]*[a-z0-9]\.)*'.                      // 도메인 세그먼트 AND
'[a-z][a-z0-9-]*[a-z0-9]'.                                 // 최상위 도메인 또는
'|((\d|[1-9]\d|1\d{2}|2[0-4][0-9]|25[0-5])\.){3}'.
'(\d|[1-9]\d|1\d{2}|2[0-4][0-9]|25[0-5])'.                 // IP 주소
')(:\d+)?'.                                                // 포트
')(((\/+([a-z0-9$_\.\+!\*\'\(\),;:@&=-]|%[0-9a-f]{2})*)*'. // 경로
'(\?([a-z0-9$_\.\+!\*\'\(\),;:@&=-]|%[0-9a-f]{2})*)'.      // 쿼리 문자열
'?)?)?'.                                                   // 경로 및 쿼리 문자열 선택 사항
'(#([a-z0-9$_\.\+!\*\'\(\),;:@&=-]|%[0-9a-f]{2})*)?'.      // 조각
'$/i');

/**
 * 주어진 URL의 구문을 확인하십시오.
 *
 * @access public
 * @param $ url 확인할 URL입니다.
 * @return 부울
 */
function is_valid_url($url) {
  if (str_starts_with(strtolower($url), 'http://localhost')) {
    return true;
  }
  return preg_match(URL_FORMAT, $url);
}

/**
 * String starts with something
 *
 *이 함수는 입력 문자열이
 * niddle
 *
 * @param string $string 입력 문자열
 * @param string $niddle Needle string
 * @return boolean $niddle로 시작하는 경우에만 true를 반환합니다.
 */
function str_starts_with($string, $niddle) {
      return substr($string, 0, strlen($niddle)) == $niddle;
}

// 호출 URL : https://gateway.sparo.cc/assets/CB5000651498097510.jpg
// 호출 URL : https://gateway.sparo.cc/assets/qrcode/qrcodeonlogin.php?val=<URL>
// $url = $_REQUEST['val']; // URI 파라미터
$url = "";
// URL encoded 상태로 와야 하는데, 그냥 텍스트로 들어온것도 처리해 주기 위해 파라미터 한줄로 붙이기
foreach($_REQUEST as $k => $v){
	if($k == "val") $url = $v;
	else $url = $url."&$k=".$v;
}
$url = urldecode($url);
//echo $url;exit;

// URL 형식인지 검사
if (is_valid_url($url) != true)
{
	echo "url fail : $url\n";
	exit;
}

$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders();            // http 헤더

//if (mb_strlen($code) != 18) {
//    header("HTTP/1.1 400 Bad Request");
//    http_response_code(400);
//    exit;
//}

// QR lib 불러오기...
require('../lib/phpqrcode/qrlib.php');
//echo $url;
img_make($url);

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
    //echo $code;
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
