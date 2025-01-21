<?php
/*
네이버 정보 조회
*/
error_reporting(0);
date_default_timezone_set('Asia/Seoul');

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
$conn_cms3->query("set names utf8");
$conn_rds->query("set names utf8");

$authkey = "c6f2db1f7784e1788877078d8513cb0f2b516a2ebd51c5dc38d6159728ed6876e0763d260a68c9c79c58acbf7d851a5fe8d90339496e5c3a8c1adbb15683da3a8ee22b9a55d5fb2d85478f4aea7a29e8d8ec668d5c3d4f59f1ca61021d2580dd6a1e7fcb4af3b32fb9b56a8dff980d60ec422bad11c5ad7eb1fb6a0ad5fd03c6dc0d1d02bad752a14570c4f23e0f814efde37eee52da968f3b3b65a5b82fa9980ff1d0c8efce246c78e55db799681dc0a9a7bb8efdb496bf8b0bf33b1d5878d4485f5ae07839c41eb5832ac84888246fb371d4fa53a4dca72c7e5023dd307b02";
header("Content-type:application/json");

$bookingStatusCode = array("RC02"=>"예약신청",
                          "RC03"=>"예약확정",
                          "RC04"=>"예약취소",
                          "RC08"=>"이용완료"
                          );

$nPayChargedStatusCode = array("CT01"=>"결제대기",
                          		"CT02"=>"결제완료",
                          		"CT03"=>"임금대기",
															"CT04"=>"환불완료",
													    "CT05"=>"입금대기취소",
													    "CT99"=>"페이미사용"
);

$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

// 파라미터
$itemreq = explode("/",$para);
$jsonReq = json_decode(trim(file_get_contents('php://input')), true);

$_res = array("serverCd"=>"api-toc","tranCd"=>genRandomStr(),"ipAddr"=>get_ip(), "item"=>$itemreq);

if(get_ip() == "121.138.151.1"){
	//echo json_encode($_res);
}else{
	//echo json_encode($_res);
}

$logStr = "";

if(!is_numeric($itemreq[0]))exit;


$row = $conn_rds->query("select * from cmsdb.nbooking_orderdatails where bookingId = '$itemreq[0]'")->fetch_object();


switch($apimethod){
	case 'POST':
	break;
	case 'PATCH':
  exit;
    //$itemreq[0] 부킹아이디 $itemreq[1] 쿠폰번호 $itemreq[2] 교체번호
    $res =  update_naverCouponno($itemreq[0], $itemreq[1], $itemreq[2]);
    header("HTTP/1.0 204");
	break;
  case 'GET':
			$res = get_BookingInfo($row->agencyBizId ,$row->bookingId);
      header("HTTP/1.0 200");
    //  echo json_encode($res);
			$_result= array(
				"bookingId" => $row->bookingId,
        "bookingStatusCode" => $bookingStatusCode[$res->bookingStatusCode],
        "nPayChargedStatusCode"=> $nPayChargedStatusCode[$res->nPayChargedStatusCode],
				"isPartialCancelUsed" => $res->business->isPartialCancelUsed,
				"nPayOrderJson" => $res->nPayOrderJson,
			);
		//	print_r($_result);
		echo json_encode($_result,JSON_UNESCAPED_UNICODE);
      exit;
	break;
	}







function genRandomStr($length = 10) {
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  $characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
  $characters = '0123456789';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
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


function get_BookingInfo($bizid ,$bookingId){
global $authkey;
$curl = curl_init();

curl_setopt_array($curl, array(
//  CURLOPT_URL => 'https://api.booking.naver.com/v3.0/businesses/'.$bizid.'/bookings/'.$bookingId.'/readable-codes/'.$couponno,
  CURLOPT_URL => 'https://api.booking.naver.com/v3.0/businesses/'.$bizid.'/bookings/'.$bookingId,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'GET',
  CURLOPT_HTTPHEADER => array(
    'X-Booking-Naver-Role: AGENCY',
    'Authorization: '.$authkey)
));

$response = curl_exec($curl);

return json_decode($response);

}


function update_naverCouponno($bookingId, $readableCodeId, $newcoupon){
    global $authkey, $conn_rds;
    // Request Body json

    $req = array(
        "readableCodeId" => $newcoupon,
        "typeCode" => "BARCODE"
    );
    print_r($req);

    //if(!is_numeric(bookingId)) exit;
    $sql = "select * from cmsdb.nbooking_orderdatails where bookingId='$bookingId' and couponNo= '$readableCodeId' limit 1";

    $row = $conn_rds->query($sql)->fetch_object();
    $bizid = $row->agencyBizId;
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.booking.naver.com/v3.0/businesses/'.$bizid.'/bookings/'.$bookingId.'/readable-codes/'.$readableCodeId,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'PATCH',
        CURLOPT_POSTFIELDS => json_encode($req),
        CURLOPT_HTTPHEADER => array(
            'X-Booking-Naver-Role: AGENCY',
            'Content-Type: application/json',
            'Authorization: '.$authkey
        ),
    ));

    $response = curl_exec($curl);


    // 업데이트 이후 네이버 쿠폰 테이블 업데이트
    $usql = "update cmsdb.nbooking_orderdatails couponNo= '$newcoupon' set where bookingId='$bookingId' and couponNo= '$readableCodeId' limit 1";
    $row = $conn_rds->query($usql);
    return json_decode($response);
}

?>
