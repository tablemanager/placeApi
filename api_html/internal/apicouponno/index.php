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
				  "211.219.73.56");

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


$ordsql = "select * from cmsdb.pcms_extcoupon_api where order_no = '$orderno' and sellcode='$sellcode' limit $unit";
$result = $conn_rds->query($ordsql);

if($result->num_rows > 0){

}else{
    $cpsql = "UPDATE  
                cmsdb.pcms_extcoupon_api 
              SET 
                order_no = '$orderno',
                cus_nm='$usernm',
                cus_hp='$userhp',
                syncfac_result='R',
                date_order = now()
              WHERE 
                    syncfac_result = 'N' AND sellcode = '$sellcode'  
              LIMIT $unit";

    $res = $conn_rds->query($cpsql);

    $ordsql = "select * from cmsdb.pcms_extcoupon_api where order_no = '$orderno' and sellcode='$sellcode' limit $unit";
    $result = $conn_rds->query($ordsql);
}

$cpns = array();

while($row = $result->fetch_object()){
    $cpns[] = $row->couponno;
    $cpflag= substr($row->couponno,0,2);
    
    // 발권 플래그 
    switch($cpflag){
        case 'EL':
        case 'CB':
            $syncflag = "R";
        break;
        default:
            $syncflag = "O";
    }

    // 7번
    $pksql = "update spadb.pcms_extcoupon set 
            order_no = '$orderno',
            cus_nm='$usernm',
            cus_hp='$userhp',
            syncfac_result='$syncflag',
            date_order = now() 
          WHERE couponno = '".$row->couponno."' limit 1";
    $conn_cms3->query($pksql);

    // 2번
    $pksql2 = "update pcmsdb.cms_extcoupon set 
            order_no = '$orderno',
            cus_nm='$usernm',
            cus_hp='$userhp',
            syncfac_result='$syncflag',
            date_order = now() 
          WHERE no_coupon = '".$row->couponno."' limit 1";
    $conn_cms->query($pksql2);
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