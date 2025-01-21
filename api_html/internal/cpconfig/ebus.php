<?php
require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
$conn_rds->query("set names utf8"); 
$now = date('Ymd');


$ip = explode(",",$_SERVER["HTTP_X_FORWARDED_FOR"]);
$accessip = array("115.68.42.2",
                  "115.68.42.8",
                  "115.68.42.130",
                  "52.78.174.3",
                  "106.254.252.100",
                  "115.68.182.165",
                  "13.124.139.14",
                  "13.209.232.254",
                  "118.131.208.123","218.39.39.190" 
                  );

if(!in_array(trim($ip[0]),$accessip)){
    header("HTTP/1.0 401 Unauthorized");
    $res = array("Result"=>"4100","Msg"=>"아이피 인증 오류 : ".get_ip());
    echo json_encode($res);
    exit;
}


$no = $_GET['no']; // URI 파라미터 
if(strlen($no) < 10){
	echo "Err";
	exit;
}

$tsql = "select * from cmsdb.ebus_reservation where sellcode = 'PEL50006981_656' and coupon_pm = '$no'";
$tres = $conn_rds->query($tsql);

$tcnt = $tres->num_rows;

if($tcnt == 0){

	$conn_rds->query("update cmsdb.ebus_reservation set coupon_pm = '$no' where sellcode = 'PEL50006981_656' and coupon_pm is null limit 1");
	$sql = "select * from cmsdb.ebus_reservation where sellcode = 'PEL50006981_656' and coupon_pm = '$no'";
	$trow = $conn_rds->query($sql)->fetch_object();
}else{
	$trow = $tres->fetch_object();
}

	echo $trow->coupon_ebus;

?>