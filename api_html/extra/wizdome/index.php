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
$_wiz = new wizdome();
$_model = new wizdome_model();


switch($apimethod)
{
	case 'POST':

		$json_result['cmd'] = '1000';
		$json_result['status'] = '0000';
		$json_result['message'] = '성공';

		for($i = 0; $i < count($data['coupon']);$i++){

			$_pin_no['coupon_ebus'] = $data['coupon'][$i]['pin'];
			$_data_coupon = $_model->getWizdomeCoupon($_pin_no);

			$_state = $_data_coupon[0]['state'];
			$_everland_coupon = $_data_coupon[0]['coupon_pm'];
			$_coupon_ebus = $_data_coupon[0]['coupon_ebus'];
			$_edate = $_data_coupon[0]['edate'];
			$_date_coupon = $_data_coupon[0]['date_coupon'];

			if (empty($_everland_coupon) ){
				$json_result['cmd'] = '1000';
				$json_result['status'] = '8001';
				$json_result['message'] = 'everland pin fail';
				$res = json_encode($json_result);
				echo $res;
				exit;
			} else if ($_everland_coupon == $_pin_no['coupon_ebus']){
//				print_r($_data_coupon);
				switch ($_state){
					case 'N' : $json_result['result']['coupon'][$i]['status'] = '0'; break;
					case 'Y' : $json_result['result']['coupon'][$i]['status'] = '2'; break;
					case 'C' : $json_result['result']['coupon'][$i]['status'] = '3';break;
					default  : $json_result['result']['coupon'][$i]['status'] = '1';break;
				}
			} else {
				$_ever_result = $_wiz->get_everland_coupon($_everland_coupon);

				/*
				 *  status = 0 사용가능 쿠폰
					status = 2 사용완료(캐리비안)
					status = 3 취소 쿠폰
					status = 1 기타 사용불가
				 */


				if ($_pin_no['coupon_ebus'] == "2791dcac"){

				} else if ($_pin_no['coupon_ebus'] == "5e27dedf"){
					$_ever_result['PIN_STATUS'] = 'CR';
				} else if ($_pin_no['coupon_ebus'] == "f893e555"){
					$_ever_result['PIN_STATUS'] = 'UR';
					$_date_coupon = '2019-07-10';
				} else if ($_pin_no['coupon_ebus'] == "19cd597c"){
					$_ever_result['PIN_STATUS'] = 'PC';
				}

				switch ($_ever_result['PIN_STATUS'])
				{
					case 'CR' : //미사용 (회수)
					case 'PS' : //미사용
						$json_result['result']['coupon'][$i]['status'] = '0';
						break;
					case 'UR' :  //확인이고 사용이지만 플엠으로 안넘어온
					case 'UC' :  //확인이고 사용 완료된
						$json_result['result']['coupon'][$i]['status'] = '2';
						break;
					case 'PC' :  //구매취소 핀폐기
						$json_result['result']['coupon'][$i]['status'] = '3';
						break;
					default  :
						$json_result['result']['coupon'][$i]['status'] = '1';
						break;
				}
			}

			if ($_edate == null){
				$_edate = "";
			} else {
				$_edate = date( "Ymd", strtotime($_edate));
			}
			if ($_date_coupon == null){
				$_date_coupon = "";
			} else {
				$_date_coupon = date( "Ymd", strtotime($_date_coupon));
			}

			$json_result['result']['coupon'][$i]['pin'] = $_pin_no['coupon_ebus'];

			$json_result['result']['coupon'][$i]['use_dt'] = $_date_coupon;
			$json_result['result']['coupon'][$i]['expire_dt'] = $_edate;

		}

		if (count($data['coupon']) == 0){
			$json_result['cmd'] = '8001';
			$json_result['message'] = '파라미터 부족';
		}

		break;

	case 'PATCH':

		$_pin_no['coupon_ebus'] = $data['coupon'];
//		$_result = $_wiz->search_ever_coupon($_pin_no['coupon_ebus']);
		$json_result = $_wiz->search_ever_coupon($_pin_no['coupon_ebus']);

//		$json_result = $_result['result'];

		break;

	default  :
		$json_result['cmd'] = '1000';
		$json_result['status'] = '8001';
		$json_result['message'] = 'http method fail';

		break;
}

$res = json_encode($json_result);

echo $res;
