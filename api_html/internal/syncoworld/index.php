<?php

/*
 *
 * 외부 연동 인터페이스용 쿠폰번호 발권 인터페이스
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
				  "211.219.73.56",
				  "13.124.215.30");

if(!in_array(get_ip(),$accessip)){
    header("HTTP/1.0 401 Unauthorized");
    $res = array("Result"=>"4100","Msg"=>"아이피 인증 오류 : ".get_ip());
    echo json_encode($res);
    exit;
}

$order=json_decode($jsonreq);
$chcode = $order->CHCODE;
$orderno = $order->ORDERNO;
$sellcode = $order->SELLCODE;
$unit = $order->UNIT;
$usernm = $order->USERNM;
$userhp = $order->USERHP;


if(!$unit) $unit = 1;
if(strlen($orderno) < 5){

    exit;
}


$ordsql = "select * from cmsdb.oworld_extcoupon where orderno = '$orderno' and sellcode='$sellcode' limit $unit";
$result = $conn_rds->query($ordsql);

if($result->num_rows > 0){
	$cpsql2 = "select couponno from cmsdb.oworld_extcoupon where orderno = '$orderno' and sellcode='$sellcode' limit $unit";
    $result2 = $conn_rds->query($cpsql2)->fetch_object();

	echo $result2->couponno;
}else{
    $cpsql = "UPDATE  
                cmsdb.oworld_extcoupon
              SET 
                orderno = '$orderno',
                usernm='$usernm',
                hp='$userhp',
                syncresult='P',
                tks_regdate = now()
              WHERE 
                    syncresult = 'N' AND sellcode = '$sellcode'  
              LIMIT $unit";

    $res = $conn_rds->query($cpsql);

    $cpsql2 = "select couponno from cmsdb.oworld_extcoupon where orderno = '$orderno' and sellcode='$sellcode' limit $unit";
    $result2 = $conn_rds->query($cpsql2)->fetch_object();

	echo $result2->couponno;
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



?>