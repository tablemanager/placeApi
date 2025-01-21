<?php
/*
* brief 코어웍스(시설) 주문 취소를 위한 내부인터페이스(코어웍스 취소api를 통해 핀폐기 처리)
*
* author Jason
* date 22.03.07
* 리팩토링 date 22.03.16
*/

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
require_once ('/home/sparo.cc/coreworks_script/lib/CoreworksApi.php');
require_once ('/home/sparo.cc/coreworks_script/lib/CoreworksDB.php');

$api = new CoreworksApi();
$db = new CoreworksDB();

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
    "13.124.215.30",
    "13.209.184.223"
);

if(!in_array(get_ip(),$accessip)){
    header("HTTP/1.0 401 Unauthorized");
    $res = array("RESULT"=>false,"MSG"=>"아이피 인증 오류 : ".get_ip());
    echo json_encode($res);
    exit;
}

$para = $_GET['type']; // URI 파라미터

$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders();            // http 헤더

$jsonreq = trim(file_get_contents('php://input'));

switch ($para){

    case 'order':

        break;

    case 'info':



        break;
    case 'cancel':
        // var_dump($jsonreq);
        $response = $api->cancelOrder($jsonreq);
        // echo $response;
        $result = json_decode($response);
        // var_dump($result->pinList[0]);

        foreach($result->pinList as $pin){
            $code = $pin->code;
            $pin = $pin->pin;
            // 조회시 code 0 미사용, 1 사용, 2 취소, 3 내역없음, 99 기타 오류
            // 취소시 code 0 취소성공, 1 사용한 티켓, 2 이미 취소된 티켓, 3 내역없음, 99 기타 오류
            if($code == '0' || $code == '2'){
                $cancelRes = $db->cancelPin($pin, $response);
                // var_dump($cancelRes);
                if($cancelRes){
                    echo "취소 성공! - $pin";
                    $db->updateCouponStateC($pin);
                    $orderno = $db->getOrdernoByPin($pin);
                    $db->updateCancelguY($orderno, "코어웍스 취소 api");
                } else {
                    echo "취소 실패!";
                }
            } elseif($code == '1'){
                echo "취소불가 - 이미 사용한 티켓입니다.";
            } elseif($code == '3'){
                echo "취소불가 - 내역이 없는 티켓번호입니다.";
            } elseif($code == '99'){
                echo "취소불가 - 알 수 없는 오류가 발생하였습니다.";
            } else {
                echo "알 수 없는 오류가 발생하였습니다.";
            }
        }

        break;

    default:
        $res = json_encode(array(false, "API 타입이 존재하지 않습니다."));
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



?>
