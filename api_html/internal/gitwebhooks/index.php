<?php
require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');

header("Content-type:application/json");

// �������̽� �α�
$tranid = date("Ymd").genRandomStr(10); // Ʈ������ ���̵�


$apimethod = $_SERVER['REQUEST_METHOD']; // http �޼���
$apiheader = getallheaders(); // http ���

// �Ķ���� 
$jsonreq = trim(file_get_contents('php://input'));

$logsql = "insert cmsdb.extapi_log set  apinm='GITHUB',tran_id='$tranid', ip='".get_ip()."', logdate= now(), apimethod='$apimethod', querystr='".$para."', header='".json_encode($apiheader)."', body='".$jsonreq."'";
 $conn_rds->query($logsql);

$conn_rds->query($logsql);

// ���� ��Ʈ�� 
function genRandomStr($length = 10) {
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}

// Ŭ���̾�Ʈ �ƾ���
function get_ip(){

    $res = explode(",",$_SERVER["HTTP_X_FORWARDED_FOR"]);
    return trim($res[0]);
}


?>