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

//		$_fillter['ecpnTrid'] = $_POST['ecpnTrid'];
		$_fillter['ecpnTrid'] = base64_decode($crypto->decrypt($_POST['ecpnTrid']));

		$_result = $_model->getEzwelRese($_fillter);

		if (count($_result) > 0){
			//예약있음

//			$_ezwel->set_encrypt_base64( $_POST[$_field]));

			$_data['result'] = $_ezwel->set_encrypt_base64('0001');
			$_data['message'] = $_ezwel->set_encrypt_base64('FAIL');
			$_data['returnValue'] = $_ezwel->set_encrypt_base64('');
			$_data['cspCd'] = $_ezwel->set_encrypt_base64('30000068');
			$_data['cspGoodsCd'] = $_POST['cspGoodsCd'];
			$_data['ecpnTrid'] = $_POST['ecpnTrid'];
			$_data['ecpnRn'] = $_POST['ecpnRn'];   //실패 시 이지웰에서 온 코드

			echo __makeXML("ResponseEzwel" , $_data);
			exit;

		} else {

			$_itemsExt = $_model->getPcmsItemsExt('EZWOW' , '' , base64_decode($crypto->decrypt($_POST['cspGoodsCd'])));
			//플레이스엠 상품코드 가져와서
			//https://{apiurl}/extra/agency/v2/dealcode/{플레이스엠 상품코드} 우리 API를 통해서 예약을 넣는다

			if (count($_itemsExt) == 0){

				$_data['result'] = $_ezwel->set_encrypt_base64('0002');
				$_data['message'] = $_ezwel->set_encrypt_base64('Internal Error');
				$_data['returnValue'] = $_ezwel->set_encrypt_base64('');
				$_data['cspCd'] = $_ezwel->set_encrypt_base64('30000068');
				$_data['cspGoodsCd'] = $_POST['cspGoodsCd'];
				$_data['ecpnTrid'] = $_POST['ecpnTrid'];
				$_data['ecpnRn'] = $_POST['ecpnRn'];   //실패 시 이지웰에서 온 코드

				echo __makeXML("ResponseEzwel" , $_data);
				exit;

			}

			$is_insert = false;
			$_insert_data = array();

			$_para_where = array("ecpnTrid" , "cspCd" , "goodsCd" ,"optionSeq","cspGoodsCd","orderNum" , "rcvPNum" ,"sendPNum","mmsTitle","addMsg","optionQty");
			foreach ($_para_where as $ss => $_field){
				if (array_key_exists($_field, $_POST)) {
					if (!empty($_POST[$_field])){
						$is_insert = true;
						$_insert_data[$_field] = base64_decode($crypto->decrypt($_POST[$_field]));
					}
				}
			}

			if ($is_insert === true){
				//step 1 이지웰 예약 테이블 insert
//				$_model->setReturn_mode(true);
				$_model->setEzwelRese($_insert_data);
				//step 2 ordermts insert

				$_order['orderNo'] = base64_decode($crypto->decrypt($_POST['ecpnTrid']));
				$_order['userName'] = base64_decode($crypto->decrypt($_POST['rcvPNum']));
				$_order['userHp'] = base64_decode($crypto->decrypt($_POST['sendPNum']));
				$_order['postCode'] = '';
				$_order['addr1'] = '';
				$_order['addr2'] = '';
				$_order['orderDesc'] = '';
				$_order['expDate'] = $_itemsExt[0]['usedate'];

				$_result_place_api_code = $_ezwel->set_placem_coupon($_itemsExt[0]['pcmsitem_id'] , $_order);

				if ($_result_place_api_code == "200"){
					//step 3 ezwel output XML
//					echo "SUCCESS";
					$_data = array();
					$_data['result'] = $_ezwel->set_encrypt_base64('0000');
					$_data['message'] = $_ezwel->set_encrypt_base64('SUCCESS');
					$_data['returnValue'] = $_ezwel->set_encrypt_base64('');
					$_data['cspCd'] = $_ezwel->set_encrypt_base64('30000068');
					$_data['cspGoodsCd'] = $_POST['cspGoodsCd'];
					$_data['ecpnTrid'] = $_POST['ecpnTrid'];
					$_data['ecpnRn'] = $_ezwel->set_encrypt_base64('234234234');   //플레이스엠 주문번호

					echo __makeXML("ResponseEzwel" , $_data);
					exit;

				} else {
					//step 3 ezwel output XML
					$_data = array();
					$_data['result'] = $_ezwel->set_encrypt_base64('0003');
					$_data['message'] = $_ezwel->set_encrypt_base64('FAIL');
					$_data['returnValue'] = $_ezwel->set_encrypt_base64('');
					$_data['cspCd'] = $_ezwel->set_encrypt_base64('30000068');
					$_data['cspGoodsCd'] = $_POST['cspGoodsCd'];
					$_data['ecpnTrid'] = $_POST['ecpnTrid'];
					$_data['ecpnRn'] = $_ezwel->set_encrypt_base64('234234234');   //플레이스엠 주문번호

					echo __makeXML("ResponseEzwel" , $_data);
					exit;

				}
			}
		}

		break;

}

