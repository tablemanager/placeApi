<?php
/*
* brief 야놀자(시설) 주문 취소를 위한 내부인터페이스(야놀자 취소api를 통해 핀폐기 처리)
*
* author Jason
* date 22.03.11 ~ 03.29
*/

require_once ('/home/sparo.cc/yanolja_script/lib/YanoljaApi.php');
require_once ('/home/sparo.cc/yanolja_script/lib/YanoljaDB.php');
require_once('/home/placedev/cmsapps/common/conf/dbconn.conf.php');

header("Content-type:application/json");


$db = new YanoljaDB();
$api = new YanoljaApi(); // 본서버에 날리기
//$api = new YanoljaApi("y"); // 테스트서버에 날리기
$now = date("Y-m-d H:i:s");

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

$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders();            // http 헤더


$jsonreq = json_decode(trim(file_get_contents('php://input')));

$orderno = $jsonreq->orderno;
$pin_yanolja = $jsonreq->couponno;

//토큰 가져오기
$accessToken = $db->getAccessToken();
if (!$accessToken){
    $refreshToken = $db->getRefreshToken();
    $db->expireToken();
    $newToken = $api->refreshToken($refreshToken);
    $db->insertNewToken($newToken);
    $accessToken = $db->getAccessToken();
}

$channelPin = $db->getPlacemPin($pin_yanolja);

$response = $api->cancelOrder($orderno, $channelPin, $accessToken);
$result = json_decode($response);
var_dump($result);



//정상적으로 취소되면 null반환, 아니면 리턴 response log를 db에 update 시킴 - 22.06.17 ila 
if($result->contentType === NULL){
    $upRes = $db->updateStateC($pin_yanolja);
    $upRes = $db->updateres($pin_yanolja,"\n {$now} \n {$response} \n/");
    $upRes = $db->updateCancelguY($orderno, " /{$now} 취소 핀폐기처리 완료/ ");
    var_dump($upRes);
}else{
    $upRes = $db->updateres($pin_yanolja,"\n {$now} \n {$response} \n/");
}


// //리턴 돌아오는 거 확인하여 아래 플엠 DB 취소처리 추가 개발필요 - Jason 22.03.11
// foreach($result->pinList as $pin){
//     $code = $pin->code;
//     $pin = $pin->pin;
//     // 조회시 code 0 미사용, 1 사용, 2 취소, 3 내역없음, 99 기타 오류
//     // 취소시 code 0 취소성공, 1 사용한 티켓, 2 이미 취소된 티켓, 3 내역없음, 99 기타 오류
//     if($code == '0' || $code == '2'){
//         $cancelRes = cancelOrderPlacem($pin);
//         // var_dump($cancelRes);
//         if($cancelRes){
//             echo "취소 성공! - $pin";
//         } else {
//             echo "취소 실패!";
//         }
//     } elseif($code == '1'){
//         echo "취소불가 - 이미 사용한 티켓입니다.";
//     } elseif($code == '3'){
//         echo "취소불가 - 내역이 없는 티켓번호입니다.";
//     } elseif($code == '99'){
//         echo "취소불가 - 알 수 없는 오류가 발생하였습니다.";
//     } else {
//         echo "알 수 없는 오류가 발생하였습니다.";
//     }
// }

// echo $res;



function get_ip(){

    if(empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
        $ip= $_SERVER["REMOTE_ADDR"].",";
    }else{
        $ip= $_SERVER["HTTP_X_FORWARDED_FOR"];
    }

    $res = explode(",",$ip);

    return trim($res[0]);
}


// function cancelOrderPlacem($pin){
//     global $conn_cms3;

//     $upQry = "UPDATE spadb.ordermts_yanolja
//             SET state = 'C',
//                 can_date = NOW()
//             WHERE pin_pm = '$pin'
//                 AND syncresult = 'Y'
//                 AND state = 'N'
//             LIMIT 1";
//     $upRes = $conn_cms3->query($upQry);
//     $upQry2 = "UPDATE spadb.ordermts_coupons
//         SET state = 'C',
//             dt_cancel = NOW()
//         WHERE couponno = '$pin'
//             AND state = 'N'
//         LIMIT 1";
//     $conn_cms3->query($upQry2);

//     if($upRes){
//         $pinSelect = "SELECT orderno
//                     FROM spadb.ordermts_yanolja
//                     WHERE pin_pm = '$pin'
//                         AND syncresult = 'Y'
//                         AND state = 'C'
//                     LIMIT 1";
//         $pinResult = $conn_cms3->query($pinSelect);
//         $pinRow = $pinResult->fetch_object();
//         $ordermtsUp = "UPDATE spadb.ordermts
//                 SET cancelgu = 'Y',
//                     canceldate = NOW()
//                 WHERE orderno = '$pinRow->orderno'
//                     AND state IN('취소','취소접수')
//                     AND usegu = '2'
//                     AND cancelgu != 'Y'
//                 LIMIT 1
//                 ";
//         return $conn_cms3->query($ordermtsUp);
//     }
//     return FALSE;
// }


?>
