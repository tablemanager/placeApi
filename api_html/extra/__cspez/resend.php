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

//$_POST['cspCd'];
//$_POST['cspGoodsCd'];
//$_POST['ecpnTrid'];
//$_POST['rcvPNum'];
//$_POST['sendPNum'];
//$_POST['mmsTitle'];
//$_POST['addMsg'];

//$_data['result'] = '0000';
//$_data['message'] = 'SUCCESS';
//$_data['returnValue'] = '';
//$_data['cspCd'] = '0';
//$_data['cspGoodsCd'] = '0';
//
//echo __makeXML("ResponseEzwel" , $_data);

$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드

switch($apimethod) {
	case 'POST':

		$_model = new ezwel_model();
		$crypto = new Crypto();
		$_ezwel = new ezwel();

		$_ezwel_data = array();

		$_para_where = array("cspCd" , "cspGoodsCd" , "ecpnTrid" ,"rcvPNum","sendPNum","mmsTitle" , "addMsg" );
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

		if (count($_result) > 0){
			$_model->setSmsReSend($_result[0]['orderno'] , 'N');

			$_return_data = array();
			$_return_data['result'] = $_ezwel->set_encrypt_base64('0000');
			$_return_data['message'] = $_ezwel->set_encrypt_base64('SUCCESS');
			$_return_data['returnValue'] = $_ezwel->set_encrypt_base64('');
			$_return_data['cspCd'] = $_ezwel->set_encrypt_base64('30000068');
			$_return_data['cspGoodsCd'] = $_POST['cspGoodsCd'];
			echo __makeXML("ResponseEzwel" , $_return_data);

		} else {
			$_return_data = array();
			$_return_data['result'] = $_ezwel->set_encrypt_base64('0001');
			$_return_data['message'] = $_ezwel->set_encrypt_base64('FAIL');
			$_return_data['returnValue'] = $_ezwel->set_encrypt_base64('');
			$_return_data['cspCd'] = $_ezwel->set_encrypt_base64('30000068');
			$_return_data['cspGoodsCd'] = $_POST['cspGoodsCd'];
			echo __makeXML("ResponseEzwel", $_return_data);
		}

		break;

		default :

		break;
}