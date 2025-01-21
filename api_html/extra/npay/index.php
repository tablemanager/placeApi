<?php
/**
 * 생성자: JAMES
 * 마지막 수정 : JAMES
 * 생성일: 2019-07-22
 * 수정일: 2019-07-05
 * 사용 유무: release (test, release,inactive,dev)
 * 파일 용도: 위즈돔(이버스) API 연동
 * 설명 : https://docs.google.com/document/d/17wfmtUD1OS7pe4z-b-_Uiqo7v-F94K3YkWkQZW2YdCg/edit
 */
header("Content-type:application/json");
require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
require_once ('/home/placedev/php_script/lib/placemlib.php');

include '/home/sparo.cc/wizdome_script/class/class.wizdome.php';


$_curl = new wizdome();

// ACL 확인
//$accessip = array("115.68.42.2",
//	"115.68.42.8",
//	"115.68.42.130",
//	"52.78.174.3",
//	"106.254.252.100",
//	"115.68.182.165",
//	"13.124.139.14",
//	"218.39.39.190",
//	"114.108.179.112",
//	"13.209.232.254",
//	"13.124.215.30",
//	"221.141.192.124",
//	"103.60.126.37"
//);
//__accessip($accessip);


$tdate=date('Y-m-d');
$ydate=date('Y-m-d', strtotime('-1 day', strtotime($tdate)));
$mdate = date("Y-m-d H:i:s", strtotime('-1 day', strtotime($tdate)));
$nowdate = date("Y-m-d H:i:s");

$isql = "SELECT sum(rprice) as tot FROM cmsdb.refund WHERE state in ('S','T')";


$irow = $conn_rds->query($isql)->fetch_object();

$isql2 = "SELECT sum(price) as tsum FROM cmsdb.refund WHERE state != 'J'";
$irow2 = $conn_rds->query($isql2)->fetch_object();

$rr = round(($irow->tot / $irow2->tsum) * 100);

$msg = "$nowdate {$rr}%\n".number_format($irow->tot)." / ".number_format($irow2->tsum);
//echo $msg = "22";
//echo "\n";


$_curl->setNPAY("" ,$msg);

/*
 *
	send_report('01073741491',$msg); //조미현
	send_report('01090901678',$msg); //이정진
	send_report('01076149671',$msg); //이정진
	send_report('01030042209',$msg); //이미진
	send_report('01099263955',$msg); //박은진
	send_report('01077590360',$msg); //신홍민
*/

function __accessip($_ip){
	if(!in_array(__get_ip(),$_ip)){
		header("HTTP/1.0 401 Unauthorized");
		$res = array("RESULT"=>false,"MSG"=>"아이피 인증 오류 : ".get_ip());
		echo json_encode($res);
		exit;
	}
}

function __get_ip(){

	if(empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
		$ip= $_SERVER["REMOTE_ADDR"].",";
	}else{
		$ip= $_SERVER["HTTP_X_FORWARDED_FOR"];
	}

	$res = explode(",",$ip);

	return trim($res[0]);
}
