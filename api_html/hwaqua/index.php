<?php
/*
* brief 한화 63 아쿠아(스마트인피니) 주문 취소를 위한 내부인터페이스
  (스마트인피니 취소api를 통해 핀폐기 처리)
*
* author ila
* date 22.06.10
*/


require_once ('/home/sparo.cc/hanwha63aqua_script/lib/Hw63aquaDB.php');
require_once ('/home/sparo.cc/hanwha63aqua_script/lib/Hw63aquaApi.php');
header("Content-type:application/json");

$db = new Hw63aquaDB();
$api = new Hw63aquaApi(); // 본서버(라이브서버) api


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
$para = $_GET['type']; // URI 파라미터
//print_r($para);

if($para != 'cancel'){
    header("HTTP/1.0 401 Unauthorized");
    $res = array("RESULT"=>false,"MSG"=>" 허용되지 않은 파라미터 ");
    echo json_encode($res);
    exit;
}

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
$couponno = $jsonreq->couponno;

$orderSales = $db->getOrderSales($couponno);

$result = $api->cancelOrder($orderno, $orderSales);





$code = $result->return_div;
if($code == "0000"){

    $cancelRes = $db->cancelHwaquaPin($orderSales);

    if($cancelRes){
        echo "\n한화아쿠아63 테이블 취소 성공! - $pin \n";
        // $db->cancelOrdermtsCoupons($couponno);
    } else {
        echo "\n한화아쿠아63 테이블 취소 실패!\n";
    }
    $notCancelledPins = $db->getNotCancelledPins($orderno);
    if(empty($notCancelledPins)){
        $ordermtsRes = $db->updateCancelguY($orderno);
        var_dump($ordermtsRes);
        if($ordermtsRes){
            echo "\n ordermts 테이블 cancelgu 변경 성공! - $orderno \n";
        } else {
            echo "\n ordermts 테이블 변경 실패!\n";
        }
    }

} elseif($code == '0003'){
    echo "취소불가 - 이미 취소된 티켓입니다.";
} elseif($code == '0004'){
    echo "취소불가 - 이미 사용한 티켓입니다.";
} elseif($code == '0006'){
    echo "취소불가 - 내역이 없는 티켓번호입니다.";
} elseif($code == '0008'){
    echo "취소불가 - 구매내역을 찾을 수 없습니다.";
} elseif($code == '0001'){
    echo "취소불가 - 시스템 에러가 발생하였습니다.";
} else {
    echo "알 수 없는 오류가 발생하였습니다.";
}


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
