<?php
/**
 * ������: JAMES
 * ������ ���� : JAMES
 * ������: 2019-07-22
 * ������: 2019-07-05
 * ��� ����: release (test, release,inactive,dev)
 * ���� �뵵: ���(�̹���) API ����
 * ���� : https://docs.google.com/document/d/17wfmtUD1OS7pe4z-b-_Uiqo7v-F94K3YkWkQZW2YdCg/edit
 */

include '/home/sparo.cc/lib/placem_helper.php';
require_once('/home/placedev/cmsapps/common/conf/dbconn.conf.php');

include '/home/sparo.cc/paradise_script/class/class.paradise.php';
include '/home/sparo.cc/paradise_script/class/paradise_model.php';

header("Content-type:application/json");

$para = $_GET['val']; // URI �Ķ����
$apimethod = $_SERVER['REQUEST_METHOD']; // http �޼���
$apiheader = getallheaders(); // http ���

// �Ķ����
$itemreq = explode("/",$para);
//$jsonreq = trim(file_get_contents('php://input'));

$coupon_code = $itemreq[0];
$sqcode = $itemreq[1];

print_r($itemreq);

//__usecouponno($coupon_code);


$json_result['result'] = '1';
//$json_result['result'] = '2';
$json_result['message'] = '����';
//$json_result['message'] = '����';

$res = json_encode($json_result);


echo $res;

function __usecouponno($no){
    // ���� ���ó��
    $curl = curl_init();
    $url = "http://172.31.30.15:3040/use/".$no;
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_USERAGENT,"Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1" );
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 5);
    $data = explode(";",curl_exec($curl));
    $info = curl_getinfo($curl);
    curl_close($curl);
    if($data[1] == "0"){
        return "N";
    }else{
        return "Y";
    }
}

