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

include '/home/sparo.cc/lib/placem_helper.php';

header("Content-type:application/json");

// ACL 확인
$accessip = array("115.68.42.2",
	"115.68.42.8",
	"115.68.42.130",
	"52.78.174.3",
	"106.254.252.100",
	"115.68.182.165",
	"13.124.139.14",
	"218.39.39.190",
	"114.108.179.112",
	"13.209.232.254",
	"13.124.215.30",
	"221.141.192.124",
	"103.60.126.37"
);
__accessip($accessip);

$para = $_GET['val']; // URI 파라미터

$apiheader = getallheaders();            // http 헤더

$jsonreq = trim(file_get_contents('php://input'));
$data = json_decode($jsonreq,TRUE);

$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드

switch($apimethod)
{
	case 'POST':
		echo $apimethod;
		break;
	case 'PUT':
		echo $apimethod;
		break;
	case 'PATCH':
		echo $apimethod;
		break;
	case 'GET':
		echo $apimethod;
		break;

	default  :
		echo "error";
		break;
}

$res = json_encode($json_result);

echo $res;

?>