<?php
/**
 * Created by PhpStorm.
 * User: PLACEM
 * Date: 2019-07-15
 * Time: ���� 10:33
 */

// �αױ�� �Լ�
include '/home/sparo.cc/api_html/extra/etickets/ezwel/logutil.php';

// �αױ�� �н�. �ǵڿ� / ����
$logpath = "/home/sparo.cc/api_html/extra/etickets/ezwel/txt/";

// 2������ �α� �����.
$logtp = "cancel";
dellog($logpath, $logtp);

// �α� ���
$fnm = 'log_'.date('Ymd')."{$logtp}.log";
$fp = fopen("{$logpath}{$fnm}", 'a+');
fwrite($fp, "\n\n================================================================================\n");
fwrite($fp, date("Y-m-d H:i:s")." �ֹ���� ����\n");
// �Է°� ���
fwrite($fp, "_SERVER\n");
fwrite($fp, print_r($_SERVER, true));
fwrite($fp, "_POST\n");
fwrite($fp, print_r($_POST, true));
fwrite($fp, "_GET\n");
fwrite($fp, print_r($_GET, true));

//===================================================================
// �������μ��� ����
header('Content-type: text/xml');

include '/home/sparo.cc/lib/placem_helper.php';
include '/home/sparo.cc/ezwel_script/class/class.ezwel.php';
include '/home/sparo.cc/ezwel_script/class/ezwel_model.php';
//include '/home/sparo.cc/ezwel_script/class/class.crypto.php';

// ACL Ȯ��
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

    "211.180.161.66", // ������ �����
);
__accessip($accessip);

$apimethod = $_SERVER['REQUEST_METHOD']; // http �޼���

switch($apimethod) {
	case 'POST':

		$_model = new ezwel_model();
		$crypto = new Crypto();
		$_ezwel = new ezwel();

//// ��ȣȭ ������ �α� 
//if(__get_ip()=="106.254.252.100"){ 
fwrite($fp, "_POST ��ȣȭ\n"); 
$_dePOST = $_POST; 
foreach($_dePOST as $k => $v){ 
    $_dePOST[$k] = $crypto->decrypt($v); 
} 
fwrite($fp, print_r($_dePOST, true)); 
//print_r($_dePOST); 
//}
//exit;
		$_ezwel_data = array();

		$_para_where = array("cspCd", "cspGoodsCd", "ecpnTrid", "cancelText");

		foreach ($_para_where as $ss => $_field){
			if (array_key_exists($_field, $_POST)) {
				if (!empty($_POST[$_field])){
					$is_insert = true;
					//$_ezwel_data[$_field] = base64_decode($crypto->decrypt($_POST[$_field]));
                    $_ezwel_data[$_field] = $crypto->decrypt($_POST[$_field]);
				}
			}
		}

        // �ֹ����� ��ȸ
        $ticketinfo = $_model->getEzwelRese($_ezwel_data);
        //echo "�ֹ����� ��ȸ:";
        //print_r($ticketinfo);
        if(count($ticketinfo) != 1){
            $_return_data = array();

            $_return_data['result'] = '0002';
            $_return_data['message'] = $crypto->encrypt('FAIL-data row count('.count($ticketinfo).')');
            $_return_data['returnValue'] = $crypto->encrypt('');
            $_return_data['cspCd'] = $crypto->decrypt($_POST['cspCd']);
            $_return_data['cspGoodsCd'] = $crypto->decrypt($_POST['cspGoodsCd']);
            $_return_data['ecpnTrid'] = $crypto->decrypt($_POST['ecpnTrid']);
            // ���� ��ȸ ���� �����Ƿ� �Ⱥ�����.
            //$_return_data['useYn'] = "N";
            //$_return_data['accountYn'] = "N";
            //$_return_data['sendYn'] = "N";
            //$_return_data['useStartDt'] = '';
            //$_return_data['useEndDt'] = '';
            //$_return_data['useDate'] = '';

            echo __makeXMLa("ResponseEzwel", $_return_data);
        }else{
            //$_fillter['ch_orderno'] = $_ezwel_data['ecpnTrid'];
            // ä���ֹ���ȣ �����Ͽ� ã�Ƴ�
            $_fillter['ch_orderno'] = $ticketinfo[0]['orderNum']."_".$ticketinfo[0]['ecpnTrid'];
            // �ֹ���ȣ ������ �ȵǰ� �־��µ� ���߿� 20240131 ���ĺ��� ������
            // �÷��̽��� �ֹ���ȣ ã�Ƴ�
            $_fillter['orderno'] = $ticketinfo[0]['place_orderno'];
            // ä�ι�ȣ :  ����������(API) : 3944
            $_fillter['ch_id'] = '3944';
            //print_r($_fillter);exit; 


		    $_result = $_model->getOrderMtsList($_fillter);
//		    $_ezwel->setIsDecod(false);
		    //print_r($_result);exit;
		    if (count($_result) > 0){

		    	$_return_data = array();
                // ������
		    	if ($_result[0]['usegu'] == "1" ){
		    		//$_return_data['result'] = $_ezwel->set_encrypt_base64('0001');
		    		//$_return_data['result'] = $crypto->encrypt('0001');
		    		$_return_data['result'] = '0001';
		    		$_return_data['message'] = $crypto->encrypt('FAIL-Used ticket');
		    	} else if (!empty($_result[0]['orderno'])){
		    		$_model->setOrderMtsCouponCancel($_result[0]['orderno'] , 'C' ,  '');
		    		//$_return_data['result'] = $_ezwel->set_encrypt_base64('0000');
		    		//$_return_data['result'] = $crypto->encrypt('0000');
		    		$_return_data['result'] = '0000';
		    		$_return_data['message'] = $crypto->encrypt('SUCCESS');
		    	} else { 
		    		//$_return_data['result'] = $_ezwel->set_encrypt_base64('0002');
		    		//$_return_data['result'] = $crypto->encrypt('0002');
		    		$_return_data['result'] = '0002';
		    		$_return_data['message'] = $crypto->encrypt('FAIL-Not found.');
		    	}
		    	//$_return_data['returnValue'] = $_ezwel->set_encrypt_base64('');
		    	//$_return_data['cspCd'] = $crypto->encrypt('30000068');
		    	//$_return_data['returnValue'] = $crypto->encrypt('');
		    	//$_return_data['cspCd'] = $_POST['cspCd'];
		    	//$_return_data['cspGoodsCd'] = $_POST['cspGoodsCd'];

		    	$_return_data['returnValue'] = '';
		    	$_return_data['cspCd'] = $crypto->decrypt($_POST['cspCd']);
		    	$_return_data['cspGoodsCd'] = $crypto->decrypt($_POST['cspGoodsCd']);
		    } else {
		    	$_return_data = array();
		    	//$_return_data['result'] = $_ezwel->set_encrypt_base64('0002');
		    	//$_return_data['result'] = $crypto->encrypt('0002');
		    	//$_return_data['returnValue'] = $crypto->encrypt('');
		    	//$_return_data['cspCd'] = $crypto->encrypt('30000068');
		    	//$_return_data['cspCd'] = $_POST['cspCd'];
		    	//$_return_data['cspGoodsCd'] = $_POST['cspGoodsCd'];

		    	$_return_data['result'] = '0002';
		    	$_return_data['message'] = $crypto->encrypt('FAIL');
		    	$_return_data['returnValue'] = '';
		    	$_return_data['cspCd'] = $crypto->decrypt($_POST['cspCd']);
		    	$_return_data['cspGoodsCd'] = $crypto->decrypt($_POST['cspGoodsCd']);

		    }
		    echo __makeXMLa("ResponseEzwel", $_return_data);

            fwrite($fp, "���� ������ : [");
            logresult($fp, $crypto, $_return_data);
            fwrite($fp, "]");
		    exit;
        }
        break; 
    }   // switch

fwrite($fp, "���� ���� ����\n");

?>
