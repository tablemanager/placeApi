<?php
exit;
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

$_data['ecpnTrid'] = '1231222233';
$_data['cspCd'] = '30000068';
$_data['goodsCd'] = 'PRODS331';
$_data['optionSeq'] = '1243422';
$_data['cspGoodsCd'] = '31676';
$_data['orderNum'] = '33332';
$_data['rcvPNum'] = 'rcvPNum1';
$_data['sendPNum'] = '010-9979-6534';
$_data['mmsTitle'] = 'title';
$_data['addMsg'] = 'msg';
$_data['optionQty'] = '3';
$_insert_data = array();

$crypto = new Crypto();

foreach ($_data as $ss => $_field) {
	$_insert_data[$ss] = $crypto->encrypt(base64_encode( $_data[$ss]));
}

print_r($_insert_data);