<?php
/**
 * Created by PhpStorm.
 * User: PLACEM
 * Date: 2019-07-15
 * Time: 오전 10:33
 */
header('Content-type: text/xml');

include '/home/sparo.cc/lib/placem_helper.php';
include '/home/sparo.cc/ezwel_script/class/class.ezwel.php';
include '/home/sparo.cc/ezwel_script/class/ezwel_model.php';
//include '/home/sparo.cc/ezwel_script/class/class.crypto.php';

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
	"106.254.252.100",
	"52.78.174.3",
	"211.180.161.90",
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

		$_para_where = array("cspCd" , "cspGoodsCd" , "ecpnTrid" ,"cancelText" );

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
//		$_ezwel->setIsDecod(false);
//		print_r($_result);
		if (count($_result) > 0){

			$_return_data = array();
			if ($_result[0]['usegu'] == "1" ){

				$_return_data['result'] = $_ezwel->set_encrypt_base64('0001');
				$_return_data['message'] = $_ezwel->set_encrypt_base64('FAIL');
			} else if (!empty($_result[0]['orderno'])){
				$_model->setOrderMtsCouponCancel($_result[0]['orderno'] , 'C' ,  '');
				$_return_data['result'] = $_ezwel->set_encrypt_base64('0000');
				$_return_data['message'] = $_ezwel->set_encrypt_base64('SUCCESS');
			} else {

				$_return_data['result'] = $_ezwel->set_encrypt_base64('0002');
				$_return_data['message'] = $_ezwel->set_encrypt_base64('FAIL');
			}
			$_return_data['returnValue'] = $_ezwel->set_encrypt_base64('');
			$_return_data['cspCd'] = $_ezwel->set_encrypt_base64('30000068');
			$_return_data['cspGoodsCd'] = $_POST['cspGoodsCd'];
		} else {
			$_return_data = array();
			$_return_data['result'] = $_ezwel->set_encrypt_base64('0002');
			$_return_data['message'] = $_ezwel->set_encrypt_base64('FAIL');
			$_return_data['returnValue'] = $_ezwel->set_encrypt_base64('');
			$_return_data['cspCd'] = $_ezwel->set_encrypt_base64('30000068');
			$_return_data['cspGoodsCd'] = $_POST['cspGoodsCd'];

		}
		echo __makeXML("ResponseEzwel" , $_return_data);
		exit;

		break;

}
