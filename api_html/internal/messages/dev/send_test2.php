<?php
require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
require_once ('../send_msg_api.php');
require_once ('../kakao_template.php');
require_once ('../lib/messages_db.php');

/*
$msg='[Test]김재현님 모바일 입장권 구매가 완료 되었습니다.';
$param = array();
$param["dstAddr"]="01028320196";
$param["callBack"]="15443913";
$param["msgSubject"]="HAMA-009";
$param["msgText"]=$msg;

//$ret = insertSMS($param);
$ret = insertMMS($param);
echo "return=".$ret;
*/
$app_key = "123456";
$headers[] = "Content-type: application/json";
$headers[] = "Authorization: ".$app_key;
$msg="";
$url = "http://gateway.sparo.cc/internal/messages/dev/receive_test.php";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);			
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $msg);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);			
$result = curl_exec($ch);
echo $result;exit;
curl_close($ch);
$result = json_decode($result, true);

?>