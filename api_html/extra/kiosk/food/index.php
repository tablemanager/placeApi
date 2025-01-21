<?php
/*
 *
 * APOS 키오스크 인터페이스
 * 
 * 작성자 : 이정진
 * 작성일 : 2019-04-17
 * 
 *
 *
 */
//242,3327,3463,3584,3088,218

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
require_once ('/home/placedev/php_script/lib/placemlib.php');

header("Content-type:application/json");
$mdate = date("Y-m-d");

$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더
$para = $_GET['val']; // URI 파라미터 

// 파라미터 
$itemreq = explode("/",$para);
$jsonreq = trim(file_get_contents('php://input'));

// 인터페이스 로그
$tranid = date("Ymd").genRandomStr(10); // 트렌젝션 아이디

$logsql = "insert cmsdb.extapi_log set  apinm='ACOMFOOD(kiosk)',tran_id='$tranid', ip='".get_ip()."', logdate= now(), apimethod='$apimethod', querystr='".$para."', header='".json_encode($apiheader)."', body='".$jsonreq."'";
$conn_rds->query($logsql);

$_json=json_decode($jsonreq);

$no = $_json->callno;
$hp = $_json->custhp;

$msg = get_msg($itemreq[0],$no);
send_bgfmsg($hp,"FOOD",$msg);


$_result = array("result"=>"0000",
				 "msg"=>"발송성공"
				 );

echo json_encode($_result);


function str2hash($no){

	return strtoupper(hash("sha256", $no));

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


function genRandomStr($length = 10) {
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}

function get_msg($tcode,$no){
	switch($tcode){
	 case 'J0001':
		 $shopnm = "청시행 주문진점 (매장코드: J0001)";
	 	 $info_url = "https://bit.ly/30O4LBe"; 
		 $info_call = "1544-4816"; 
	 break;
	 case 'M0002':
		 $shopnm = "청시행 명동점 (매장코드: M0002)";
	 	 $info_url = "https://"; 
		 $info_call = "02-1811-9660"; 
	 break;
	 case 'G0003':
		 $shopnm = "청시행 광안리점 (매장코드: G0003)";
	 	 $info_url = "https://"; 
		 $info_call = "070-4001-9205";
	 break;
	 default:

	}

	$msg = "{$shopnm}

	★호출번호 : {$no}

	고객님께서 주문하신 음식이 준비 되었습니다.
	교환권을 제출하신 후 음식을 수령하여 
	주시기 바랍니다. 
	감사합니다. 
	
	안내지도 : {$info_url}
	
	★연락처: {$info_call}";

	return $msg;
}

?>