<?php
/**
 * Created by PhpStorm.
 * User: PLACEM
 * Date: 2019-07-08
 * Time: 오후 5:41
 */

header('Content-type: text/xml');

include '/home/sparo.cc/lib/placem_helper.php';
include '/home/sparo.cc/ezwel_script/class/class.ezwel.php';
include '/home/sparo.cc/ezwel_script/class/ezwel_model.php';

// ACL 확인
$accessip = array(
	"115.68.42.2",
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
	"106.254.252.100",
	"211.180.161.90",
	"52.78.174.3",
	"103.60.126.37"
);
__accessip($accessip);

$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드

switch($apimethod) {

	case 'POST':

		$_model = new ezwel_model();
		$crypto = new Crypto();
		$_ezwel = new ezwel();

		$_ezwel_data = array();

		$_para_where = array("cspCd" , "cspGoodsCd" , "ecpnTrid" );

		foreach ($_para_where as $ss => $_field){
			if (array_key_exists($_field, $_POST)) {
				if (!empty($_POST[$_field])){
					$is_insert = true;
					$_ezwel_data[$_field] = base64_decode($crypto->decrypt($_POST[$_field]));
				}
			}
		}

		$_fillter['ch_orderno'] = $_ezwel_data['ecpnTrid'];
		$_fillter['ch_id'] = '144';

		$_result = $_model->getOrderMtsList($_fillter);
//		print_r($_result);
		if (count($_result) > 0){
			//예약있음

			$useYn = $_result[0]['usegu'] == "2" ?"N":"Y";
			$accountYn = $_result[0]['usegu'] == "2" ?"Y":"N";

			if ($_result[0]['state'] == "예약취소"){
				$useYn = "C";
				$accountYn = "C";
			}

			$useEndDt = trim(str_replace("-","",$_result[0]['usedate']));
			$useDate = trim(str_replace("-","",$_result[0]['usegu_at']));
//			useStartDt  유효기간 시작일은 일단 패스
			$_return_data = array();
			$_return_data['result'] = $_ezwel->set_encrypt_base64('0000');
			$_return_data['message'] = $_ezwel->set_encrypt_base64('SUCCESS');
			$_return_data['returnValue'] = $_ezwel->set_encrypt_base64('');
			$_return_data['cspCd'] = $_ezwel->set_encrypt_base64('30000068');
			$_return_data['cspGoodsCd'] = $_POST['cspGoodsCd'];
			$_return_data['useYn'] = $_ezwel->set_encrypt_base64($useYn);
			$_return_data['accountYn'] = $_ezwel->set_encrypt_base64($accountYn);
			$_return_data['sendYn'] = $_ezwel->set_encrypt_base64($_result[0]['smsgu']);
			$_return_data['useStartDt'] = $_ezwel->set_encrypt_base64('');
			$_return_data['useEndDt'] = $_ezwel->set_encrypt_base64($useEndDt);
			$_return_data['useDate'] = $_ezwel->set_encrypt_base64($useDate);

			echo __makeXML("ResponseEzwel" , $_return_data);

		} else {
			//예약없음

			$_return_data = array();
			$_return_data['result'] = $_ezwel->set_encrypt_base64('0001');
			$_return_data['message'] = $_ezwel->set_encrypt_base64('FAIL');
			$_return_data['returnValue'] = $_ezwel->set_encrypt_base64('');
			$_return_data['cspCd'] = $_ezwel->set_encrypt_base64('30000068');
			$_return_data['cspGoodsCd'] = $_POST['cspGoodsCd'];
			$_return_data['useYn'] = $_ezwel->set_encrypt_base64("N");
			$_return_data['accountYn'] = $_ezwel->set_encrypt_base64("N");
			$_return_data['sendYn'] = $_ezwel->set_encrypt_base64($_result[0]['smsgu']);
			$_return_data['useStartDt'] = $_ezwel->set_encrypt_base64('');
			$_return_data['useEndDt'] = $_ezwel->set_encrypt_base64('');
			$_return_data['useDate'] = $_ezwel->set_encrypt_base64('');

			echo __makeXML("ResponseEzwel" , $_return_data);

		}


		break;
}


//$_data['result'] = '0000';
//$_data['message'] = 'SUCCESS';
//$_data['returnValue'] = '';
//$_data['cspCd'] = '0';
//$_data['cspGoodsCd'] = '0';
//$_data['useYn'] = 'N';
//$_data['accountYn'] = 'Y';
//$_data['sendYn'] = 'Y';
//$_data['useStartDt'] = 'yyyymmdd';
//$_data['useEndDt'] = 'yyyymmdd';
//$_data['useDate'] = 'yyyymmdd';
//
//echo __makeXML("ResponseEzwel" , $_data);