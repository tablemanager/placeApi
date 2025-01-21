<?php
/**
 * Created by PhpStorm.
 * User: PLACEM
 * Date: 2019-07-08
 * Time: 오후 5:41
 */

// 로그기록 함수
include './logutil.php';

// 로그기록 패스. 맨뒤에 / 포함
$logpath = "/home/sparo.cc/api_html/extra/etickets/ezwel/txt/";

// 2달지난 로그 지운다.
$logtp = "resend";
dellog($logpath, $logtp);

// 로그 기록
$fnm = 'log_'.date('Ymd')."{$logtp}.log";
$fp = fopen("{$logpath}{$fnm}", 'a+');
fwrite($fp, "\n\n================================================================================\n");
fwrite($fp, date("Y-m-d H:i:s")." 주문조회 접수\n");
// 입력값 기록
fwrite($fp, "_SERVER\n");
fwrite($fp, print_r($_SERVER, true));
fwrite($fp, "_POST\n");
fwrite($fp, print_r($_POST, true));
fwrite($fp, "_GET\n");
fwrite($fp, print_r($_GET, true));

//===================================================================
// 업무프로세스 시작
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
	"103.60.126.37",
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
//echo __makeXMLa("ResponseEzwel" , $_data);

$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드

switch($apimethod) {
	case 'POST':

		$_model = new ezwel_model();
		$crypto = new Crypto();
		$_ezwel = new ezwel();

		$_ezwel_data = array();

		//$_para_where = array("cspCd", "cspGoodsCd", "ecpnTrid", "rcvPNum", "sendPNum", "mmsTitle", "addMsg", "orderNum" );
		$_para_where = array("ecpnTrid", "rcvPNum", "sendPNum", "mmsTitle", "addMsg", "orderNum" );
		foreach ($_para_where as $ss => $_field){
			if (array_key_exists($_field, $_POST)) {
				if (!empty($_POST[$_field])){
					$is_insert = true;
					//$_ezwel_data[$_field] = base64_decode($crypto->decrypt($_POST[$_field]));
					$_ezwel_data[$_field] = $crypto->decrypt($_POST[$_field]);
				}
			}
		}

		//$_fillter['ch_orderno'] = $_ezwel_data['ecpnTrid'];
		$_fillter['ch_orderno'] = $_ezwel_data['orderNum'];
		//$_fillter['ch_id'] = '144';
        // 채널번호 :  현대이지웰(API) : 3944
        $_fillter['ch_id'] = '3944';

		$_result = $_model->getOrderMtsList($_fillter);

		if (count($_result) > 0){
			$_model->setSmsReSend($_result[0]['orderno'] , 'N');

			$_return_data = array();
			//$_return_data['result'] = $_ezwel->set_encrypt_base64('0000');
			//$_return_data['cspCd'] = $crypto->encrypt('30000068');
/*
			$_return_data['result'] = $crypto->encrypt('0000');
			$_return_data['message'] = $crypto->encrypt('SUCCESS');
			$_return_data['returnValue'] = $crypto->encrypt('');
			$_return_data['cspCd'] = $_POST['cspCd'];
			$_return_data['cspGoodsCd'] = $_POST['cspGoodsCd'];
*/
            $_return_data['result'] = '0000';
			$_return_data['message'] = $crypto->encrypt('SUCCESS');
			$_return_data['returnValue'] = '';
			$_return_data['cspCd'] = $crypto->decrypt($_POST['cspCd']);
			$_return_data['cspGoodsCd'] = $crypto->decrypt($_POST['cspGoodsCd']);

			echo __makeXMLa("ResponseEzwel", $_return_data);

		} else {
			$_return_data = array();
			//$_return_data['result'] = $_ezwel->set_encrypt_base64('0001');
			//$_return_data['cspCd'] = $crypto->encrypt('30000068');
/*
			$_return_data['result'] = $crypto->encrypt('0001');
			$_return_data['message'] = $crypto->encrypt('FAIL');
			$_return_data['returnValue'] = $crypto->encrypt('');
			$_return_data['cspCd'] = $_POST['cspCd'];
			$_return_data['cspGoodsCd'] = $_POST['cspGoodsCd'];
*/
			$_return_data['result'] = '0001';
			$_return_data['message'] = $crypto->encrypt('FAIL');
			$_return_data['returnValue'] = '';
			$_return_data['cspCd'] = $crypto->decrypt($_POST['cspCd']);
			$_return_data['cspGoodsCd'] = $crypto->decrypt($_POST['cspGoodsCd']);

			echo __makeXMLa("ResponseEzwel", $_return_data);
		}

		break;

		default :

		break;
}

fwrite($fp, "응답 데이터 : [");
logresult($fp, $crypto, $_return_data);
fwrite($fp, "]");

?>
