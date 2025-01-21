<?php

/*
* brief 야놀자 대사 API
* author ila
* date 22.04.14 ~
*/


// error_reporting(0);
require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
header("Content-type:application/json");

$conn_rds->query("set names utf8");
$conn_cms->query("set names utf8");
/*
==== 적용 url example =======
https://{채널대행사IP}:{채널대행사PORT}/channel-agency/v1/orders/reconcile?reconciliationDate=20220101&orderStatus=CREATED&pageNumber=1&pageSize=1000
https://gateway.sparo.cc/yanolja/channel-agency/v1/orders/reconcile?reconciliationDate=20220422&orderStatus=CREATED&pageNumber=1&pageSize=3
*/
$para = $_SERVER['QUERY_STRING']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

// 파라미터
$itemreq1 = explode('&',$para);
$itemreq2 = implode($itemreq1,'=');
$itemreqs = explode('=',$itemreq2);


// 인터페이스 로그
list($microtime,$timestamp) = explode(' ',microtime());
$tranid = $timestamp . substr($microtime, 2, 3);
$logsql = "insert cmsdb.extapi_log set apinm='야놀자대사',tran_id='$tranid', ip='".get_ip()."', logdate= now(), apimethod='$apimethod', querystr='".$para."', header='".json_encode($apiheader)."', body='".$jsonreq."'";
$conn_rds->query($logsql);

$today = date("Ymd");
$now = date("Y-m-d H:i:s");


if($itemreqs[7] > '2000'){ // pagesize
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

} elseif ($itemreqs[5] <= '0') { // pageNumber
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
  if(!in_array(get_ip(),json_decode($authrow->accessip,false))){
      header("HTTP/1.0 401 Unauthorized");
      $result = array("code"=>"400000","message"=>"IP 인증 오류");
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
    $result = array("code"=>"400000","message"=>"APIkey 인증 오류");
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
    case 'CREATED':
        // 생성주문 : 구매일시 기준으로 모두 나오면 됨
        $res = get_order($itemreqs[1],$itemreqs[5],$itemreqs[7]); //날짜, 페이지번호, 페이지사이즈
        break;
    case 'CANCELED':
        // 취소주문
        $res = get_cancel($itemreqs[1],$itemreqs[5],$itemreqs[7]);
        break;
    case 'USED':
        // 사용주문
        $res = get_used($itemreqs[1],$itemreqs[5],$itemreqs[7]);
        break;
    case 'REVERTED':
        // 복원주문
        $res = get_reverted($itemreqs[1],$itemreqs[5],$itemreqs[7]);
        break;
    default:
        header("HTTP/1.0 400 Bad Request");
        $result = array("code"=>"400000","message"=>"파라미터 오류 : ".$itemreqs[3]);
        $results = array(
            'body' => $result,
            'contentType'=>"ErrorResult"
        );
        echo json_encode($results);
}



function get_order($created,$pageNumber,$pageSize){
    global $conn_rds;
    global $conn_cms;
    global $conn_cms3;

    //var_dump($created); //20220331
    //var_dump($pageSize); //500

    $created = date('Y-m-d',strtotime($created));
    // var_dump($created); // 2022-03-31

    // == ordermts 를 검색해서 ordermts_yanolja에 insert 하기 때문에 ordermts_yanolja 대상으로함
    // -- 야놀자와 연동된 해당날짜의 모든 주문의 갯수를 가져오기 - totalElementCount 값
    $totOrders = "SELECT *
                  FROM spadb.ordermts_yanolja
                  WHERE buy_date LIKE '$created%'
                  AND syncresult = 'Y'
                  limit 5000";
    $totOrders = $conn_cms3->query($totOrders);
    $totOrdersCount = mysqli_num_rows($totOrders);




    // - pagesize는 요청 값 그대로(단 2000개까지만) pageNumber는 1,2,3으로 시작하기
    $startIdx = ($pageNumber - 1) * $pageSize;
    // $endIdx = ($pageNumber * $pageSize) - 1;
    $ordermts_Yja = "SELECT *
                      FROM spadb.ordermts_yanolja
                      WHERE buy_date LIKE '$created%'
                      AND syncresult = 'Y'
                      LIMIT $startIdx,$pageSize";

    $res = $conn_cms3->query($ordermts_Yja);
    $orders = array();
    while($row = $res->fetch_object()){

        $created = explode(" ",$row->buy_date);
        // var_dump($created);
        // exit;

        switch($row->ch_id){
          case '3384': //여기어때
              $channelCode = 'ABOUTHERE';
              break;
          case '2859': //한유망
              $channelCode = 'HANYOUWANG';
              break;
          case '3694': //씨트립
              $channelCode = 'TRIP';
              break;

        }


// ======== Response Body
        $order = [
            'reconciliationDate' => $created[0],
            'partnerOrderChannelCode' => $channelCode,
            'partnerOrderId' => $row->orderno,
            'productId' => $row->product_id,
            'variantId' => $row->variant_id,
            'pin' => $row->pin_yanolja,
            'partnerOrderChannelPin'=> $row->pin_pm,
            'unitPrice'=> $row->unit_price,
            'status'=> 'CREATED',
        ];
        array_push($orders, $order);

        $cntOrders = count($orders); // 반환 주문 갯수
        //$cntPages = count($pageSize); // 몇 개 검색할지
        //$totCnt = ($count - ($count % $cntPages)) / $cntPages; //총 페이지 수
        $totCnt = ceil($totOrdersCount / $pageSize); // 총 페이징 수

        $pages = array(
            'pageNumber' => (int)$pageNumber, // 반환된 페이지번호 = 요청한 페이지번호
            'pageSize' => $cntOrders, // 반환된 주문 갯수
            'totalElementCount' => $totOrdersCount, //요청한 날짜 해당하는 모든 주문
            'totalPageCount' => $totCnt // 요청한 모든 주문에 대한 페이징 수
        );


        $fields = array(
            'page' => $pages,
            'orders' => $orders
        );

        $bodys = array(
             'body' => $fields,
             'contentType'=>"null"
        );
    }//while
    // 해당날짜 관련 주문이 없을때 0으로 반환
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
           'orders'=>null
      );

      header("HTTP/1.0 202"); //No Content
      echo json_encode($results);
    } else {
      header("HTTP/1.0 200"); // OK
      echo json_encode($bodys);

    }
}//function get_order



function get_cancel($cancel,$pageNumber,$pageSize){
    global $conn_rds;
    global $conn_cms;
    global $conn_cms3;
    $cancel = date('Y-m-d',strtotime($cancel));

    // -- 야놀자와 연동된 해당날짜의 모든 주문의 갯수를 가져오기 - totalElementCount 값
    $totCanceled = "SELECT *
                  FROM spadb.ordermts_yanolja
                  WHERE can_date LIKE '$cancel%'
                  AND syncresult = 'Y'
                  AND state = 'C'
                  limit 5000";
    $totCanceled = $conn_cms3->query($totCanceled);
    $totCanceledCount = mysqli_num_rows($totCanceled);


    $startIdx = ($pageNumber - 1) * $pageSize;
    $ordermts_Yja = "SELECT *
                      FROM spadb.ordermts_yanolja
                      WHERE can_date LIKE '$cancel%'
                      AND syncresult = 'Y'
                      AND state = 'C'
                      LIMIT $startIdx,$pageSize";

    $res = $conn_cms3->query($ordermts_Yja);
    $orders = array();
    while($row = $res->fetch_object()){
        $canceldate = explode(" ",$row->can_date);
        // var_dump($canceldate);
        // exit;

        switch($row->ch_id){
          case '3384': //여기어때
              $channelCode = 'ABOUTHERE';
              break;
          case '2859': //한유망
              $channelCode = 'HANYOUWANG';
              break;
          case '3694': //씨트립
              $channelCode = 'TRIP';
              break;

        }



    // ======== Response Body
          $order = [
              'reconciliationDate' => $canceldate[0],
              'partnerOrderChannelCode' => $channelCode,
              'partnerOrderId' => $row->orderno,
              'productId' => $row->product_id,
              'variantId' => $row->variant_id,
              'pin' => $row->pin_yanolja,
              'partnerOrderChannelPin'=> $row->pin_pm,
              'unitPrice'=> $row->unit_price,
              'status'=> 'CANCELED',
          ];

          array_push($orders, $order);

          $cntOrders = count($orders); // 반환 주문 갯수
          $totCnt = ceil($totCanceledCount / $pageSize); // 총 페이징 수

          $pages = array(
            'pageNumber' => (int)$pageNumber, // 반환된 페이지번호 = 요청한 페이지번호
            'pageSize' => $cntOrders, // 반환된 주문 갯수
            'totalElementCount' => $totCanceledCount, //요청한 날짜 해당하는 모든 주문
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
      // $result = array(
      //   "Code"=>400000,
      //   "message"=>"주문 조회 실패(시스템 에러 등, 기타 에러 포함)"
      // );
      // $results = array(
      //     'body' => $result,
      //     'contentType'=>"ErrorResult"
      // );
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
      header("HTTP/1.0 202"); //No Content
      echo json_encode($results);
  } else {
      header("HTTP/1.0 200"); // OK
      echo json_encode($bodys);
  }
}//function get_cancel


function get_used($used,$pageNumber,$pageSize){
    global $conn_rds;
    global $conn_cms;
    global $conn_cms3;
    $used = date('Y-m-d',strtotime($used));

    // -- 야놀자와 연동된 해당날짜의 모든 주문의 갯수를 가져오기 - totalElementCount 값
    $totUseds = "SELECT *
                  FROM spadb.ordermts_yanolja
                  WHERE use_date_re LIKE '$used%'
                  AND syncresult = 'Y'
                  limit 5000";
    $totUseds = $conn_cms3->query($totUseds);
    $totUsedsCount = mysqli_num_rows($totUseds);
    // - pagesize는 요청 값 그대로(단 2000개까지만) pageNumber는 1,2,3으로 시작하기
    $startIdx = ($pageNumber - 1) * $pageSize;

    $ordermts_Yja = "SELECT *
                      FROM spadb.ordermts_yanolja
                      WHERE use_date_re LIKE '$used%'
                      AND syncresult = 'Y'
                      LIMIT $startIdx,$pageSize";

    $res = $conn_cms3->query($ordermts_Yja);
    $orders = array();
    while($row = $res->fetch_object()){
        $usedate = explode(" ",$row->use_date_re);
        // var_dump($canceldate);
        // exit;

        switch($row->ch_id){
          case '3384': //여기어때
              $channelCode = 'ABOUTHERE';
              break;
          case '2859': //한유망
              $channelCode = 'HANYOUWANG';
              break;
          case '3694': //씨트립
              $channelCode = 'TRIP';
              break;

        }



    // ======== Response Body
          $order = [
              'reconciliationDate' => $usedate[0],
              'partnerOrderChannelCode' => $channelCode,
              'partnerOrderId' => $row->orderno,
              'productId' => $row->product_id,
              'variantId' => $row->variant_id,
              'pin' => $row->pin_yanolja,
              'partnerOrderChannelPin'=> $row->pin_pm,
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
}//function get_cancel


function get_reverted($reverted,$pageNumber,$pageSize){
    global $conn_rds;
    global $conn_cms;
    global $conn_cms3;
    $reverted = date('Y-m-d',strtotime($reverted));

    // -- 야놀자와 연동된 해당날짜의 모든 주문의 갯수를 가져오기 - totalElementCount 값
    $totReverted = "SELECT *
                    FROM spadb.ordermts_yanolja
                    WHERE revert_date_re LIKE '$reverted%'
                    AND syncresult = 'Y'
                    limit 5000";
    $totReverted = $conn_cms3->query($totReverted);
    $totRevertedCount = mysqli_num_rows($totReverted);


    $startIdx = ($pageNumber - 1) * $pageSize;

    $ordermts_Yja = "SELECT *
                      FROM spadb.ordermts_yanolja
                      WHERE revert_date_re LIKE '$reverted%'
                      AND syncresult = 'Y'
                      LIMIT $startIdx,$pageSize";

    $res = $conn_cms3->query($ordermts_Yja);
    $orders = array();
    while($row = $res->fetch_object()){
        $revertdate = explode(" ",$row->revert_date_re);
        // var_dump($canceldate);
        // exit;

        switch($row->ch_id){
          case '3384': //여기어때
              $channelCode = 'ABOUTHERE';
              break;
          case '2859': //한유망
              $channelCode = 'HANYOUWANG';
              break;
          case '3694': //씨트립
              $channelCode = 'TRIP';
              break;

        }



    // ======== Response Body
          $order = [
              'reconciliationDate' => $revertdate[0],
              'partnerOrderChannelCode' => $channelCode,
              'partnerOrderId' => $row->orderno,
              'productId' => $row->product_id,
              'variantId' => $row->variant_id,
              'pin' => $row->pin_yanolja,
              'partnerOrderChannelPin'=> $row->pin_pm,
              'unitPrice'=> $row->unit_price,
              'status'=> 'REVERTED',
          ];

          array_push($orders, $order);

          $cntOrders = count($orders); // 날짜 조회시 총 주문갯수
          $totCnt = ceil($totRevertedCount / $pageSize); // 총 페이징 수


          $pages = array(
            'pageNumber' => (int)$pageNumber,// 반환된 페이지번호 = 요청한 페이지번호
            'pageSize' => $cntOrders, // 반환된 주문 갯수
            'totalElementCount' => $totRevertedCount, //요청한 날짜 해당하는 모든 주문
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
         'orders'=>null
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
}//function get_reverted



// 클라이언트 아아피
function get_ip(){

	if(empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
		 $ip= $_SERVER["REMOTE_ADDR"].",";
	}else{
		$ip= $_SERVER["HTTP_X_FORWARDED_FOR"];
	}

	$res = explode(",",$ip);

    return trim($res[0]);
}
