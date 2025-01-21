<?php

/*
 *
 * 쿠폰테이블 쿠폰입력
 *
 * 작성자 : 이정진
 * 작성일 : 2018-07-02
 *
 * 사용(POST)			: https://gateway.sparo.cc/internal/apicouponno
 *
 * JSON {"CHCODE":"채널코드", "ORDERNO":"20180802", "SELLCODE":"P10101_1", "UNIT":"1", "USERNM":"테스트","USERHP":"01090901678"} ;
 */

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
header("Content-type:application/json");

mysqli_set_charset($conn_cms, 'utf8');
mysqli_set_charset($conn_rds, 'utf8');
mysqli_set_charset($conn_cms3, 'utf8');

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
				  "211.219.73.56");

if(!in_array(get_ip(),$accessip)){
    header("HTTP/1.0 401 Unauthorized");
    $res = array("Result"=>"4100","Msg"=>"아이피 인증 오류 : ".get_ip());
    echo json_encode($res);
    exit;
}


$order=json_decode($jsonreq);
$sellcode = $order->sellcode;
$expdate = $order->expdate;
$couponno = $order->couponno;

if(strlen($sellcode) < 4) exit;
if(strlen($expdate) < 8) exit;
if(strlen($couponno) < 5) exit;

$icpsql = "insert spadb.pcms_extcoupon set couponno='$couponno', sellcode='$sellcode', expdate='$expdate'";
$conn_cms3->query($pksql2);
}

echo json_encode($cpns);

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



?>
