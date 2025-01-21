<?php
include '/home/sparo.cc/lib/placem_helper.php';
require_once('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
include '/home/sparo.cc/vmaker_script/model/vmaker_model.php';

header("Content-type:application/json");
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
    "52.79.75.133",
    "15.164.17.130",
    "13.124.161.61",
    "103.60.126.37"
);
__accessip($accessip);

//$para = $_GET['barcode']; // URI 파라미터
$para = $_GET['val']; // URI 파라미터

$_vmaker_model = new vmaker_model();
//$_fillter['barcode_no'] = $para;
//$_fillter['jpmt_id'] = '4080';

//$_fillter['jpmt_id_ary'][0] = '4073';
//$_fillter['jpmt_id_ary'][1] = '4095';
//$_fillter['jpmt_id_ary'][2] = '4067';


$_vmaker_result = $_vmaker_model->getOrderVmaker($para);

$_fillter['orderno'] = $_vmaker_result[0]['order_id'];

$_rese_result = $_vmaker_model->getOrderMtsList($_fillter);

//if ($_rese_result[0]['state'] == "예약완료" &&  ($_rese_result[0]['usegu'] == "2" || $_rese_result[0]['usegu'] == "1")){
if ($_rese_result[0]['state'] == "예약완료" && $_rese_result[0]['usegu'] == "2") {
//    __usecouponno($para);
//    __usecouponno($_rese_result[0]['barcode_no']);
    $usegu_at = date("Y-m-d H:i:s");
    $_vmaker_model->setOrderMtsCouponUse($_fillter['orderno'] ,"1" , $usegu_at  ,'');

    $result['result'] = "SUCCESS";
    $result['code'] = "0000";
    $result['msg'] = "성공";
    $_order_id = $_fillter['orderno'];
    __usecouponno($_rese_result[0]['barcode_no']);
    $_vmaker_model->setUseOrder($_order_id ,"Y" );

}  else if ($_rese_result[0]['state'] == "예약완료" && $_rese_result[0]['usegu'] == "1") {
    $result['result'] = "FAIL";
    $result['code'] = "0001";
    $result['msg'] = "이미 사용된 바코드";
} else if ($_rese_result[0]['state'] == "취소" ){
    $result['result'] = "FAIL";
    $result['code'] = "0002";
    $result['msg'] = "취소 된 바코드";
} else {
    $result['result'] = "FAIL";
    $result['code'] = "0003";
    $result['msg'] = "예악 정보가 없는 바코드";
}



$res = json_encode($result);

$tranid = date("Ymd") . genRandomStrtemp(10); // 트렌젝션 아이디
$logsql = "insert cmsdb.extapi_log_para set apinm='vmaker',tran_id='$tranid', ip='" . get_ip_temp() . "', logdate= now(),  apiresult='$res', apimethod='use', querystr='" . $para . "'";
$conn_rds->query($logsql);


echo $res;
exit;

function get_ip_temp(){

    if(empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
        $ip= $_SERVER["REMOTE_ADDR"].",";
    }else{
        $ip= $_SERVER["HTTP_X_FORWARDED_FOR"];
    }

    $res = explode(",",$ip);

    return trim($res[0]);
}

// 랜덤 스트링
function genRandomStrtemp($length = 10)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

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

