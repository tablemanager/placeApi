<?php
/**
 * 문자 재발송 API
 * http://gateway.sparo.cc/internal/messages/search/resend_api.php
 */

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
//require_once ('/home/sparo.cc/order_script/lib/SendData_Script.php');
//require_once ('../lib/messages_db.php');
require_once ('../lib/sms_lib.php');

mysqli_query($conn_rds, "set session character_set_connection=utf8");
mysqli_query($conn_rds, "set session character_set_results=utf8");
mysqli_query($conn_rds, "set session character_set_client=utf8");

//$param = $_POST;
$param = $_REQUEST;
$yyyymm = $param['yyyymm'];
$orderno = $param['orderno'];
$hp = $param['hp'];
if (empty($yyyymm) || empty($orderno) || empty($hp)) {
	//echo "데이타 정보가 부족합니다.";echo "<xmp>";print_r($_REQUEST);echo "</xmp>";exit;
	echo json_encode(array('code'=>0, 'msg'=>'데이타 정보가 부족합니다'));
	exit;
}
$hp = str_replace("-", "", $hp);

$msg_type_arr = array("K" => "KAKAO", "S" => "SMS", "L" => "LMS", "M" => "MMS");
$sql = "select * from CMSSMS.MSG_RESULT_".$yyyymm." where ORDERNO='".$orderno."' and DSTADDR='".$hp."'";//echo $sql;exit;
$result = $conn_rds->query($sql);
/*
addLog("resend_".date("Ymd"), array(
	"date" => date("Y.m.d H:i:s"), 
	"sql" => $sql, 
	"result" => $result
)); //로그
*/

$tot = $cnt = 0;
while ($row = mysqli_fetch_assoc($result)) {

	$msgarr = array(
		"dstAddr" => $row["DSTADDR"],
		"callBack" => $row["CALLBACK"],
		"msgSubject" => $row["MSG_SUBJECT"],
		"msgText" => $row["MSG_TEXT"],
		"mmsFile" => $row["FILELOC1"],
		"kakao_profile" => $row["PROFILE"],
		"orderNo" => $row["ORDERNO"],
		"pinType" => $row["COUPON_TYPE"],
		"pinNo" => $row["COUPONNO"],
		"extVal1" => $row["EXTVAL1"],
		"extVal2" => $row["EXTVAL2"],
		"extVal3" => $row["EXTVAL3"],
		"extVal4" => $row["EXTVAL4"]
	);
	$url = "http://gateway.sparo.cc/internal/messages/".$msg_type_arr[$row['MSG_TYPE']];

	//echo "url=".$url."<xmp>";print_r($msgarr);echo "</xmp>"; //test
	$cnt++; //test

	$headers = array();
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);			
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);	
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($msgarr));
	$result = curl_exec($ch);
	curl_close($ch);	
	$res = json_decode($result,true);

/*
	$data = send_url($ch, $url , "POST", json_encode($msgarr));
	$ch = curl_close();
	$res = json_decode($data,true);
*/	
	if ($res['result'] == true) {
		$cnt++;
		addLog("resend_".date("Ymd"), array(
			"date" => date("Y.m.d H:i:s"), 
			"MSG_TYPE" => $msg_type_arr[$row['MSG_TYPE']], 
			"dstAddr" => $row["DSTADDR"],
			"orderNo" => $row["ORDERNO"],
			"send result" => $res
		)); //로그
	}else{
		addLog("resend_error_".date("Ymd"), array(
			"date" => date("Y.m.d H:i:s"), 
			"send result" => $res,
			"msgarr" => $msgarr
		)); //로그
	}
	
	$tot++;

	usleep(500000); // 0.5초 딜레이
	

}

if($cnt){
	echo json_encode(array('code'=>1, 'msg'=>'총 '.$tot.' 건중에 '.$cnt.' 건 발송 성공'));
}else{
	echo json_encode(array('code'=>0, 'msg'=>'발송 실패'));
}
?>