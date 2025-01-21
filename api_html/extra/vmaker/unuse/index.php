<?php
require_once('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
include '/home/sparo.cc/tourpass_script/class/class.jeonbuk.php';
include '/home/sparo.cc/tourpass_script/model/jeonbuk_model.php';

header("Content-type:application/json");
// ACL 확인
//$accessip = array("115.68.42.2",
//    "115.68.42.8",
//    "115.68.42.130",
//    "52.78.174.3",
//    "106.254.252.100",
//    "115.68.182.165",
//    "13.124.139.14",
//    "218.39.39.190",
//    "114.108.179.112",
//    "13.209.232.254",
//    "13.124.215.30",
//    "221.141.192.124",
//    "103.60.126.37"
//);
//__accessip($accessip);

$para = $_GET['barcode']; // URI 파라미터

$_jeon = new \Jeonbuk\Jeonbuk();
$_jeon_model = new \Jeonbuk\Jeonbuk_model();

$_fillter['barcode_no'] = $para;
//$_fillter['jpmt_id'] = '4080';
$_fillter['jpmt_id_ary'][0] = '4073';
$_fillter['jpmt_id_ary'][1] = '4095';

$_rese_result = $_jeon_model->getOrderMtsList($_fillter);

if ($_rese_result[0]['state'] == "예약완료" &&  ($_rese_result[0]['usegu'] == "2" || $_rese_result[0]['usegu'] == "1")){

    $result['result'] = "SUCCESS";
} else {
    $result['result'] = "FAIL";
}

$res = json_encode($result);

echo $res;
exit;

function __usecouponno($no){
    // 쿠폰 사용처리
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


