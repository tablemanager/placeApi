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
include '/home/sparo.cc/wizdome_script/class/class.wizdome.php';
include '/home/sparo.cc/wizdome_script/class/wizdome_model.php';

header("Content-type:application/json");


// ACL 확인
$accessip = array("115.68.42.2",
	"115.68.42.8",
	"115.68.42.130",
	"115.89.22.27",
	"52.78.174.3",
	"106.254.252.100",
	"115.68.182.165",
	"13.124.139.14",
	"218.39.39.190",
	"114.108.179.112",
	"13.209.232.254",
	"13.124.215.30",
	"118.131.208.123",
	"221.141.192.124",
	"103.60.126.37"
);
__accessip($accessip);

$para = $_GET['val']; // URI 파라미터

$apiheader = getallheaders();            // http 헤더

$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$_wiz = new wizdome();
$_model = new wizdome_model();

switch($apimethod)
{
	case 'GET':

		$_fillter['coupon_pm'] = $_REQUEST['var'];

		if (empty($_fillter['coupon_pm'])){
			echo "존재하지 않는 쿠폰 입니다.";
			exit;
		}

		$_cpn = substr($_fillter['coupon_pm'], 0, 2);

		if ($_cpn == "CB" || $_cpn == "EL"){
			$_result = $_model->getWizdomeCoupon($_fillter);
			$_ebus = $_result[0]['coupon_ebus'];
		} else {
			$_ebus = $_fillter['coupon_pm'];
		}

//		$_result = $_wiz->search_coupon($_ebus);
		$_result = $_wiz->search_ever_coupon($_ebus);

		print_r($_result);
//		if ($_result['result'][0]['status'] == "0"){
//			echo "Y";
//			echo "\n";
//			echo "Ebus : ".$_result['result'][0]['pin'];
//		} else {
//			echo "N";
//			echo "\n";
//			echo "Ebus : ".$_result['result'][0]['message'];
//		}

		break;

}
