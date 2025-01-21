<?php

/*
 *
 * 외부 연동 인터페이스용 쿠폰번호 발권 인터페이스
 *
 * 작성자 : 토니
 * 작성일 : 2023-08-08
 *
 * 사용(POST)			: https://gateway.sparo.cc/internal
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
$item_id = $itemreq[0];

if(!$unit) $unit = 1;
if(strlen($orderno) < 5){

    exit;
}

$ordsql = "select * from cmsdb.corn_extcoupon where orderno = '$orderno' limit $unit";
$result = $conn_rds->query($ordsql);

if($result->num_rows > 0){
	$cpsql2 = "select couponno from cmsdb.corn_extcoupon where orderno = '$orderno' limit $unit";
    $result2 = $conn_rds->query($cpsql2)->fetch_object();

	echo $result2->couponno;
}else{

  $_itemsql = "select * from CMSDB.CMS_ITEMS where item_id= '$item_id' limit 1";
  $_items = $conn_cms->query($_itemsql)->fetch_object();


  $orderno = $orderno;

  $plucode = $_items->item_cd;
  if(strlen($plucode) < 1){
    exit;
  }

  $qty = $unit;
  $sdate = $_items->item_sdate;
  $edate = $_items->item_edate;
  $usernm = $usernm;
  $hp = $userhp;
  $lashp = substr($userhp,-4);

  for($i=0;$i<$qty;$i++){


        $cp = "PM".$plucode.genRandomChar(10);

         $_isql = "insert cmsdb.corn_extcoupon set
                                    plucode = '$plucode',
                                    sdate ='$sdate',
                                    edate = '$edate',
                                    title = '$_items->item_nm',
                                    couponno = '$cp',
                                    qty = '1',
                                    coupon_pm = '$cp',
                                    orderno = '$orderno',
                                    usernm = '$usernm',
                                    userhp = '$lashp',
                                    state = 'P',
                                    date_order = now()";

    $res = $conn_rds->query($_isql);
  }

  $cpsql2 = "select couponno from cmsdb.corn_extcoupon where orderno = '$orderno' limit $unit";
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

function genRandomChar($length = 10) {
	$characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}

?>
