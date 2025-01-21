<?php

// 여기 어때 연동 인터페이스
//error_reporting(0);

/*

주문 : https://gateway.sparo.cc/extra/withinapi/order
취소 : https://gateway.sparo.cc/extra/withinapi/cancel

d000gE00ThI9rKSY0HEB.gjehtFFghtgC6I8voSUIj.9Qi1VHQw0DksddsjS29H.4jxYq23Xx42EdJPhr8b6.Bco3motFtyXGyVGBQyjT

0 성공
8000 서버 오류
8001 인증 오류
8002 파라미터 오류

0 성공
8000 서버 오류
8001 인증 오류
8002 파라미터 오류
8007 이미 사용된 핀번호입니다. 
8008 핀번호가 없습니다.
8010 이미취소된 핀번호입니다.(루틴 개발중)

*/

exit;
// 20240205 tony 폐쇄. 플레이스엠 표준API로 주문 등록함.

date_default_timezone_set('Asia/Seoul');
require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
$conn_rds->query("set names utf8");
$conn_cms3->query("set names utf8");

header("Content-type:application/json");


$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더
$auth = $apiheader['Authorization'];

// 파라미터
$itemreq = explode("/",$para);
$type = $itemreq[0];
$jsonReq = json_decode(trim(file_get_contents('php://input')), true);

$authArr = array('857239.768343.EGRWDD.98655', 'Bearer 857239.768343.EGRWDD.98655');
$ipArr = array('106.254.252.100','52.78.78.253', '13.209.189.240', '58.151.26.42', '13.124.26.130', '13.124.17.189', '13.124.193.123', '13.124.58.2',"54.86.50.139");

if(!in_array(get_ip(), $ipArr) || !in_array($auth, $authArr)){
  $res = array(
    "code"=>"8001",
    "message"=> "인증 오류 ",get_ip()
  );


  header("HTTP/1.0 401 Unauthorized");
  echo json_encode($res);
  exit;
} else if(get_ip() != '106.254.252.100'){

}

// 로그 저장
list($microtime,$timestamp) = explode(' ',microtime());
$tranid = $timestamp . substr($microtime, 2, 3);
$logsql = "insert cmsdb.extapi_log set apinm='여기어때_신API',tran_id='$tranid', ip='".get_ip()."', logdate= now(), apimethod='$apimethod', querystr='".$para."', header='".json_encode($apiheader)."', body='".json_encode($jsonreq,JSON_UNESCAPED_UNICODE)."'";
$conn_rds->query($logsql);

usleep(rand(1000, 5000));

$logStr = "";
$pinList = array();
switch($type){
  case '' : //health check
    header("HTTP/1.0 200");
    exit;
  break;
	case 'order':
		// 주문등록
		$logStr .="주문등록";
		$res = setOrder($jsonReq);

    header("HTTP/1.0 200");
    echo json_encode($res);
    exit;
		break;

	case 'cancel':
		// 주문취소
		$logStr .="주문취소";
		$res = cancelOrder($jsonReq); //부분 취소

    header("HTTP/1.0 200");
    echo json_encode($res);
    exit;
	  break;
  default :
    $res = array(
      "code"=>"8002",
      "message"=> "파라미터 오류"
    );

    header("HTTP/1.0 400 Bad Request");
    echo json_encode($res);
    exit;
}

// 주문등록
function setOrder($jsonReq){

  global $pinList;

  if(empty($jsonReq['orderNumber']) || empty($jsonReq['ordererPhoneNumber']) || empty($jsonReq['ordererName']) || empty($jsonReq['products'])) {return array('code' => '0003', 'message' => '필수 파라미터 오류');}

  // 주문정보 조회
  $odersinfo = getOrderInfo($jsonReq['orderNumber']);

  if(!empty($odersinfo)){
    //이미 등록된 주문
    foreach($odersinfo as $row){
      array_push($pinList, array('productNumber' => $row['dealNo'], 'productOptionNumber' => $row['omsItemCode'], 'pinNumber' => $row['ticketCode']));
    }

    $res = array(
          'code' => '0',
          'message' => 'OK',
          'data' => array(
            'agencyApproveNumber' => strval($jsonReq['orderNumber']),
            'pins' => $pinList
          )
        );

    return $res;

  }

  $orderInfo['ordernum'] = $jsonReq['orderNumber'];
  $orderInfo['usernm'] = $jsonReq['ordererName'];
  $orderInfo['tel'] = preg_replace('/(^02.{0}|^01.{1}|^15.{2}|^16.{2}|^18.{2}|[0-9]{3})([0-9]+)([0-9]{4})/', '$1-$2-$3', preg_replace('/[^0-9]/', '', $jsonReq['ordererPhoneNumber']));

  foreach($jsonReq['products'] as $product){
    foreach($product['productDetails'] as $productDetail){ //옵션별 주문등록
      $productDetail['productNumber'] = $product['productNumber'];

      insertOrder($jsonReq, $orderInfo, $productDetail);
    }
  }


  $res = array(
        'code' => '0',
        'message' => 'OK',
        'data' => array(
          'agencyApproveNumber' => strval($orderInfo['ordernum']),
          'pins' => $pinList
        )
      );
  return $res;
}


function cancelOrder($jsonReq){

  if(empty($jsonReq['pins']) || empty($jsonReq['orderNumber'])) {return array('code' => '8002', 'message' => '파라미터 오류');}

  // 사용확인후 취소 연동 호출
  $orderno = $jsonReq['orderNumber'];
    foreach($jsonReq['pins'] as $pin){
      $ticketCode = $pin['pinNumber'];

      // 사용처리 확인후 취소처리
      $usestatus = "N";

      if($usestatus == "N"){
          // 취소 가능 쿠폰
            $res = array(
              'code' => '0',
              'message' => 'OK'
            );
      }else{
          // 취소 불가능 쿠폰
            $res = array(
              'code' => '0',
              'message' => 'OK'
            );
      }

    }




  return $res;
}


//주문정보 조회
function getOrderInfo($ch_ordernum){
  return "";
}

function insertOrder($req, $orderInfo, $orderDetail){
  global $pinList;
  $channelCd = 'WITHIN'; //여기어때

  $ordernum = $orderInfo['ordernum'];
  $usernm = $orderInfo['usernm'];
  $tel = $orderInfo['tel'];
  $product_id = $orderDetail['productNumber'];
  $type_id = $orderDetail['productOptionNumber'];
  $revDate = $orderDetail['reservationDate'];
  $qty = $orderDetail['count'];
  $sprice = (int)$orderDetail['amount'] / (int)$orderDetail['count'];

    //수량별로 등록
    $res = array();
    for($i=0; $i<$qty; $i++){
      $ticketCode = "50".genRandomNum(12);

      // 쿠폰번호가 발행되지 않거나 형식이 다를때 중지
      if(strlen($ticketCode) < 4){
        $res = array(
          "code"=>"9999",
          "message"=> "주문 생성 실패"
        );

        setApiLog($res, "1");
        header("HTTP/1.0 409 Conflict");
        exit;
      }

      array_push($pinList, array('productNumber' => $orderDetail['productNumber'], 'productOptionNumber' => $type_id, 'pinNumber' => $ticketCode));

      // 대매사 API 호출
      $reqjson = array( "orderNo" => $ordernum."_".$type_id."_".$i,
                    "userHp" =>  $tel,
                    "userName" => $usernm,
                    "Qty" => "1",
                    "couponNo" => $ticketCode);


      orders($type_id, json_encode($reqjson));

    }

}




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


// 랜덤 숫자
function genRandomNum($length = 10) {
	$characters = '0123456789';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}


function orders($itemcd, $reqjson){

    $curl = curl_init();
    $authkey = "d000gE00ThI9rKSY0HEB.gjehtFFghtgC6I8voSUIj.9Qi1VHQw0DksddsjS29H.4jxYq23Xx42EdJPhr8b6.Bco3motFtyXGyVGBQyjT";
    curl_setopt_array($curl, array(
        CURLOPT_URL => "http://extapi.sparo.cc/extra/agency/v2/dealcode/".$itemcd,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS =>$reqjson,
        CURLOPT_HTTPHEADER => array(
            "Authorization: ".$authkey,
            "Content-Type: application/json"
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);

    return $response;
}
