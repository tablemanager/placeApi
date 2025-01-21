<?php

/**
 *
 * @brief 씨트립 쿠폰번호 발권 인터페이스
 *
 * @author Jason
 * @date 2022.03.31
 *
 * 사용(POST)			: https://gateway.sparo.cc/internal/getctripcoupon
 *
 * JSON {"CHCODE":"채널코드", "ORDERNO":"20180802", SELLCODE:"P10101_1", "UNIT":"1", "USERNM":"테스트","USERHP":"01090901678"} ;
 * 원본 3@home/sparo.cc/api_html/internal/getcouponno/index.php
 * 이 인터페이스를 사용하는 곳 -> 64@home/ctrip/public_html/ctriplive/index.php
 */

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
header("Content-type:application/json");

$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

// 파라미터
$itemreq = explode("/",$para);
$jsonreq = trim(file_get_contents('php://input'));

//$couponno = $itemreq[0];


$accessip = array("115.68.42.2",
                  "115.68.42.8",
                  "115.68.42.130",
                  "52.78.174.3",
                  "106.254.252.100",
                  "115.68.182.165",
                  "13.124.139.14",
                  "18.163.36.64"
                );

if(!in_array(get_ip(),$accessip)){
    header("HTTP/1.0 401 Unauthorized");
    $res = array("Result"=>"4100","Msg"=>"아이피 인증 오류 : ".get_ip());
    echo json_encode($res);
    exit;
}

$order=json_decode($jsonreq);


$facode = $order->FACCODE;
$chcode = $order->CHCODE;
$orderno = $order->ORDERNO;
$sellcode = $order->SELLCODE;
$unit = $order->UNIT;
$usernm = $order->USERNM;
$userhp = $order->USERHP;

if(!$unit) $unit = 1;
if(strlen($orderno) < 5){
    //header("HTTP/1.0 401");
    exit;
}

// 쿠폰 생성이 없고 핀자동생성 상품의 경우
$sellinfo = $conn_cms->query("select * from pcmsdb.cms_coupon where items_id = '$sellcode' and chgu = 'R' ORDER BY ccode DESC LIMIT 1")->fetch_object();
$ctype = $conn_cms->query("select type_coupon from CMSDB.CMS_ITEMS where item_id = '$sellcode'")->fetch_object();

if($ctype->type_coupon == "NUM" or $ctype->type_coupon == "CHAR" ) $facode = $ctype->type_coupon;

switch($facode){
	case 'NUM':
		$_results = json_encode(array($sellcode.genRandomNum(11)));
	break;
	case 'CHAR':
		$_results = json_encode(array(genRandomStr(16)));
	break;
	default:
		$_results = getpcmscoupon($order, $sellinfo->ccode);

}

echo $_results;
//echo $facode;
// 랜덤 스트링
function genRandomStr($length = 10) {
	$characters = '123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}

// 랜덤 10진수
function genRandomNum($length = 10) {
	$characters = '0123456789';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
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

function getpcmscoupon($order,$sellcode){
    global $conn_cms3;

    $facode = $order->FACCODE;
    $chcode = $order->CHCODE;
    $orderno = $order->ORDERNO;
    $unit = $order->UNIT;
    $usernm = $order->USERNM;
    $userhp = $order->USERHP;

    if(!$unit) $unit = 1;

    if(strlen($orderno) < 5){
        exit;
    }

    $cpsql = "UPDATE
                spadb.pcms_extcoupon
              SET
                order_no = '$orderno',
                cus_nm='$usernm',
                cus_hp='$userhp',
                syncfac_result='R',
                date_order = now()
              WHERE
                    syncfac_result = 'N'
                AND order_no is null
                AND state_use  = 'N'
                AND sellcode = '$sellcode'
              LIMIT $unit";

    $res = $conn_cms3->query($cpsql);

    $ordsql = "select * from spadb.pcms_extcoupon where order_no = '$orderno' and sellcode='$sellcode' limit $unit";
    $result = $conn_cms3->query($ordsql);

    //if($res->num_rows > 0)
    $cpns = array();

    while($row = $result->fetch_object()){
        $cpns[] = $row->couponno;
    }

    return json_encode($cpns);

}

?>
