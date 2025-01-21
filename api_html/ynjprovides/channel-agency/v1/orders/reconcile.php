<?php

/*
* brief ynj유동성공급 일대사 API
* author tony
* 20240415 tony [야놀자] 프로바이더 연동 일대사 API 개발 요청 https://placem.atlassian.net/browse/QI2201-51
*/


// error_reporting(0);
require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
header("Content-type:application/json");

$conn_rds->query("set names utf8");
$conn_cms->query("set names utf8");
/*
==== 적용 url example =======
https://{채널대행사IP}:{채널대행사PORT}/channel-agency/v1/orders/reconcile?reconciliationDate=20240415&orderStatus=CREATED&pageNumber=1&pageSize=1000
https://gateway.sparo.cc/ynjprovides/channel-agency/v1/orders/reconcile?reconciliationDate=20240415&orderStatus=CREATED&pageNumber=1&pageSize=3
*/
$para = $_SERVER['QUERY_STRING']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더
$jsonreq = "";

// 파라미터
$itemreq1 = explode('&', $para);
$itemreq2 = implode($itemreq1, '=');
$itemreqs = explode('=', $itemreq2); 

//print_r($itemreqs); 
//print_r($para);
//print_r($apiheader);

//exit;

// 인터페이스 로그
list($microtime, $timestamp) = explode(' ', microtime());
$tranid = $timestamp . substr($microtime, 2, 3);
$logsql = "insert cmsdb.extapi_log set apinm='YNJ유동성일대사',tran_id='$tranid', ip='".get_ip()."', logdate= now(), apimethod='$apimethod', querystr='".$para."', header='".json_encode($apiheader)."', body='".$jsonreq."'";
//yjlee $conn_rds->query($logsql);

// pagesize
if($itemreqs[7] > '2000'){
    $result = array(
        "code"=>"400000",
        "message" => "주문 조회 실패(pagesize 오류)"
    );
    $results = array(
        'body' => $result,
        'contentType'=>"ErrorResult"
    );
    header("HTTP/1.0 200");
    echo json_encode($results);

    exit;

// pageNumber
} elseif ($itemreqs[5] <= '0') {
    $result = array(
        "code"=>"400000",
        "message" => "주문 조회 실패(pageNumber 오류)"
    );
    $results = array(
        'body' => $result,
        'contentType'=>"ErrorResult"
    );
    echo json_encode($results);

    exit;
}


// 인증 정보 조회
$auth = $apiheader['Authorization'];
if(!$auth) $auth = $apiheader['authorization'];

$authqry = "SELECT * FROM spadb.extapi_config WHERE authkey = '$auth' and state = 'Y' limit 1";
$authres = $conn_cms3->query($authqry);
$authrow = $authres->fetch_object();

$aclmode = $authrow->aclmode; 

if($aclmode == "IP"){
    // ACL 확인
    if(!in_array(get_ip(), json_decode($authrow->accessip, false))){
        header("HTTP/1.0 401 Unauthorized");
        $result = array("code"=>"400000", "message"=>"IP 인증 오류");
        $results = array(
            'body' => $result,
            'contentType'=>"ErrorResult"
        );
        echo json_encode($results);

        exit;
    }
}

// API키 확인
if(!$authrow->authkey){

    header("HTTP/1.0 401 Unauthorized");
    $result = array("code"=>"400000", "message"=>"APIkey 인증 오류");
    $results = array(
        'body' => $result,
        'contentType'=>"ErrorResult"
    );
    echo json_encode($results);

    exit;

}else{

    $cpcode = $authrow->cp_code; // 채널코드
    $cpname = $authrow->cp_name; // 채널명
    $grmt_id = $authrow->cp_grmtid; // 채널 업체코드

}


//var_dump($itemreqs);
/*
array(8) {
    [
        0
    ]=>
  string(18) "reconciliationDate"
  [
        1
    ]=>
  string(8) "20220331"
  [
        2
    ]=>
  string(11) "orderStatus"
  [
        3
    ]=>
  string(7) "CREATED"
  [
        4
    ]=>
  string(10) "pageNumber"
  [
        5
    ]=>
  string(1) "1"
  [
        6
    ]=>
  string(8) "pageSize"
  [
        7
    ]=>
  string(4) "1000"
}


*/
// 파라미터 분기
switch($itemreqs[3]){
    case 'USED':
        // 사용주문
        $res = get_used($itemreqs[1], $itemreqs[5], $itemreqs[7]);
        break;
    case 'CREATED':
    case 'CANCELED':
    case 'REVERTED':
    //default:
        header("HTTP/1.0 400 Bad Request");
        $result = array("code"=>"400000", "message"=>"파라미터 오류 : ".$itemreqs[3]);
        $results = array(
            'body' => $result,
            'contentType'=>"ErrorResult"
        );
        echo json_encode($results);
        break;
}


function get_used($used, $pageNumber, $pageSize){
    global $conn_rds;
    global $conn_cms;
    global $conn_cms3;
    $used = date('Y-m-d', strtotime($used));

    // -- 야놀자와 연동된 해당날짜의 모든 주문의 갯수를 가져오기 - totalElementCount 값
    // 20240514 tony https://placem.atlassian.net/browse/QI2201-64  [야놀자] 프로바이더 주문 대사 불일치 확인 요청 건
    // 5000건으로 제한되는 것을 막기 위하여 limit 삭제
    $totUseds = "SELECT *
                  FROM spadb.yanolja_order_provides
                  WHERE usegu_at LIKE '$used%'
                  AND syncresult = 'Y'
                  -- limit 5000
                ";
    $totUseds = $conn_cms3->query($totUseds);
    $totUsedsCount = mysqli_num_rows($totUseds);
    // - pagesize는 요청 값 그대로(단 2000개까지만) pageNumber는 1, 2, 3으로 시작하기
    $startIdx = ($pageNumber - 1) * $pageSize;

    $ordermts_Yja = "SELECT *
                      FROM spadb.yanolja_order_provides
                      WHERE usegu_at LIKE '$used%'
                      AND syncresult = 'Y'
                      LIMIT $startIdx, $pageSize";

    $res = $conn_cms3->query($ordermts_Yja);
    $orders = array();
    while($row = $res->fetch_object()){
        $usedate = explode(" ", $row->usegu_at);
        // var_dump($canceldate);
        // exit;

        switch($row->ch_id){
            // 네이버예약
            case '2984': 
                $channelCode = 'NAVER_BOOKING';
                break;
            // 나머지는 기타로 전송
            default:
                $channelCode = 'ETC';
                break;

        }
        $ynjres = $row->res_dump;
        //{"body":{"orderId":"20240410_N642715847_2","variants":[{"productId":"P10743765","variantId":"16842652","unitPrice":17500,"pin":"O4B6F7A17A73","channelPin":"N0REZG0S0UMA7QVF","reservationDate":null,"reservationTime":null}]},"contentType":"Response"}
        $ynjjson = json_decode($ynjres);
        $ynj_barcode = "EMPTY";
        if(isset($ynjjson->body->variants[0]->pin)){
            $ynj_barcode = $ynjjson->body->variants[0]->pin;
        } 
        // 20240422 tony [야놀자] 일대사 API 개발시 바코드 없을시 EMPTY 설정 요청 https://placem.atlassian.net/browse/QI2201-54
        // 야놀자 성남현  요청사항
        $pm_barcode = $row->barcode;
        if(isset($pm_barcode) == false || empty($pm_barcode)){
            $pm_barcode = "EMPTY";
        }

        // ======== Response Body
        $order = [
              'reconciliationDate' => $usedate[0],
              'partnerOrderChannelCode' => $channelCode,
              'partnerOrderId' => $row->orderno,
              'productId' => $row->product_id,
              'variantId' => $row->variant_id,
              //'pin' => $row->pin_yanolja,
              'pin' => $ynj_barcode,
              //'partnerOrderChannelPin'=> $row->pin_pm,
              'partnerOrderChannelPin'=> $pm_barcode,
              'unitPrice'=> $row->unit_price,
              'status'=> 'USED',
        ];

        array_push($orders, $order);

        // print_r($order);
        // exit;


        $cntOrders = count($orders); // 날짜 조회시 총 주문갯수
        $totCnt = ceil($totUsedsCount / $pageSize); // 총 페이징 수

        $pages = array(
            'pageNumber' => (int)$pageNumber, // 반환된 페이지번호 = 요청한 페이지번호
            'pageSize' => $cntOrders, // 반환된 주문 갯수
            'totalElementCount' => $totUsedsCount, //요청한 날짜 해당하는 모든 주문
            'totalPageCount' => $totCnt // 요청한 모든 주문에 대한 페이징 수
        );


        $fields = array(
            'page' => $pages,
            'orders' => $orders
        );

        $bodys = array(
            'body' => $fields,
            'contentType'=> null
        );
    }//while

    if(empty($bodys)){
        $cntOrders = count($orders); // 날짜 조회시 총 주문갯수
        $pages = array(
            'pageNumber' => 0, // 반환 페이지수
            'pageSize' => $cntOrders, // 반환 주문 갯수
            'totalElementCount' => 0, //총 주문갯수
            'totalPageCount' => 0 // 총 페이지갯수
        );

        $page = array(
            'page' => $pages
        );

        $results = array(
            'body' => $page,
            'orders'=> null
        );
        // $result = array(
        //   "Code"=>400000,
        //   "message"=>"주문 조회 실패(시스템 에러 등, 기타 에러 포함)"
        // );
        // $results = array(
        //     'body' => $result,
        //     'contentType'=>"ErrorResult"
        // );
        header("HTTP/1.0 202"); //No Content
        echo json_encode($results);
    } else {
        header("HTTP/1.0 200"); // OK
        echo json_encode($bodys);
    }
}//function get_used


// 클라이언트 아아피
function get_ip(){

	if(empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
		 $ip= $_SERVER["REMOTE_ADDR"].",";
	}else{
		$ip= $_SERVER["HTTP_X_FORWARDED_FOR"];
	}

	$res = explode(",", $ip);

    return trim($res[0]);
}
