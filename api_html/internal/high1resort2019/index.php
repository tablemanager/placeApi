<?php
require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
$conn_rds->query("set names utf8");    
// https://gateway.sparo.cc/internal/cpconfig/AQ/TP23810_31
header("Content-type:application/json");
$para = $_GET['val']; // URI 파라미터 
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

// 파라미터 
$itemreq = explode("/",$para);
$jsonreq = trim(file_get_contents('php://input'));


// ACL 확인
/*
$ip = explode(",",$_SERVER["HTTP_X_FORWARDED_FOR"]);
$accessip = array("115.68.42.2",
                  "115.68.42.8",
                  "115.68.42.130",
                  "52.78.174.3",
                  "106.254.252.100",
                  "115.68.182.165",
                  "13.124.139.14",
                  "13.209.232.254",
                  "118.131.208.123" 
                  );

if(!in_array(trim($ip[0]),$accessip)){
//    header("HTTP/1.0 401 Unauthorized");
//    $res = array("Result"=>"4100","Msg"=>"아이피 인증 오류 : ".get_ip());
//    echo json_encode($res);
//    exit;
}
*/
$tcode = $itemreq[0];

$tsql = "select * from cmsdb.high_reservation where coupon = '$tcode'";

$trow = $conn_rds->query($tsql)->fetch_object();

$water = array();
$lift = array();
$sky = array();
$benefit = array();

// 워터 정보
foreach(json_decode($trow->coupon_water) as $wc){	
	$water[] = get_high1info($wc);
}

// 워터 정보
foreach(json_decode($trow->coupon_sky) as $sc){
	$sky[] = get_high1info($sc);
}

// 워터 정보
foreach(json_decode($trow->coupon_lift) as $lc){
	$lift[] = get_high1info($lc);
}

// 워터 정보
foreach(json_decode($trow->coupon_benefit) as $bc){
	$benefit[] = get_high1info($bc);
}

$jsonres = array("water"=>$water,
				 "lift"=>$lift,
				 "sky"=>$sky,
				 "benefit" =>$benefit);
//print_r($jsonres);
echo json_encode($jsonres);


function get_high1info($no){
	
	global $conn_rds; 
	
	$_sql = "select * from cmsdb.high1_extcoupon where couponno = '$no'";
	$_row = $conn_rds->query($_sql)->fetch_object();


		$_result = array("couponno" => $_row->couponno,
						  "state" => $_row->state,
						  "usedate" => $_row->usedate);

	return $_result;

}

?>