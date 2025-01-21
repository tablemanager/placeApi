<?php

include '/home/sparo.cc/lib/placem_helper.php';
include '/home/sparo.cc/fujifilm_script/class/class.fuji.php';
include '/home/sparo.cc/fujifilm_script/class/fuji_model.php';

header("Content-type:application/json");

// ACL 확인
$accessip = array("115.68.42.2",
    "115.68.42.8",
    "115.68.42.130",
    "52.78.174.3",
    "106.254.252.100",
    "115.68.182.165",
    "13.124.139.14",
    "218.39.39.190",
    "13.209.232.254",
    "13.124.215.30"
);
__accessip($accessip);

//if(!in_array(get_ip(),$accessip)){
//    header("HTTP/1.0 401 Unauthorized");
//    $res = array("RESULT"=>false,"MSG"=>"아이피 인증 오류 : ".get_ip());
//    echo json_encode($res);
//    exit;
//}

$para = $_GET['val']; // URI 파라미터

$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders();            // http 헤더

$jsonreq = trim(file_get_contents('php://input'));
$data = json_decode($jsonreq,TRUE);

switch ($para){
    case 'info':

        $_model = new fuji_model();
        $_curl = new fuji();

        if (!empty($data['couponno'])){
            $_curl_result = $_curl->search_coupon($data['couponno']);
            $res = json_encode($_curl_result);
        }

        break;

    case 'cancel':

        $_model = new fuji_model();
        $_curl = new fuji();

        if (!empty($data['couponno'])){
            $_curl_result = $_curl->search_coupon($data['couponno']);

            if ($_curl_result['rscode'] == "0000"){
                $_cancel_result = $_curl->search_coupon($data['couponno']);
            } else {
                $_cancel_result['rscode'] = "0001";
                $_cancel_result['rsmsg'] = $_curl_result['rsmsg'];
//                $_cancel_result['api'] = $_curl_result;
            }

            $res = json_encode($_cancel_result);

        } else {
            $_cancel_result['rscode'] = "0001";
            $_cancel_result['rsmsg'] = "파라미터 누락";

            $res = json_encode($_cancel_result);
        }

        break;

    default:
//        $res = json_encode(array(false, "API 타입이 존재하지 않습니다."));

        $_cancel_result['rscode'] = "0001";
        $_cancel_result['rsmsg'] = "API 타입이 존재하지 않습니다.";

        $res = json_encode($_cancel_result);

        break;
}

echo $res;

function get_ip(){

    if(empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
        $ip= $_SERVER["REMOTE_ADDR"].",";
    }else{
        $ip= $_SERVER["HTTP_X_FORWARDED_FOR"];
    }

    $res = explode(",",$ip);

    return trim($res[0]);
}

/**
 * 전송 방식 : POST

파라미터는 헤더와 바디로 나누어 전송 해야 합니다.
Header : skey : 암호화 인증코드 à  hezcpO283gl/Cc2sc7Gdzg==

Body : sCode : 쿠폰번호

리턴 메시지는 Json 데이터 입니다.

Ex) { "rscode": "0004", "rsmsg": "파라미터 누락" }

 *리턴 코드
 * 0000 : 등록 대기중인 쿠폰, 삭제 처리 완료
 * 0001 : 등록 완료된 쿠폰, 사용 중지 완료
 * 0002 : 사용 중지된 쿠폰
 * 0003 : 사용 완료된 쿠폰, 삭제 중지 불가
 * 0004 : 파라미터 누락
 * 0005 : 찾을수 없는 쿠폰
 * 0006 : 시스템 내부 오류
 * 0007 : 인증코드 오류
 */
?>