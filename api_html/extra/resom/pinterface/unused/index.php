<?php
/**
 * 생성자: JAMES
 * 마지막 수정 : JAMES
 * 생성일:  2020-07-03
 * 수정일: 2020-07-03
 * 파일 용도: 리솜리조트 검색
 */

include '/home/sparo.cc/lib/placem_helper.php';
require_once('/home/placedev/cmsapps/common/conf/dbconn.conf.php');

//header("Content-type:application/json");
header('Content-Type: application/json; charset=utf-8');

// ACL 확인
//$accessip = array("115.68.42.2",
//	"115.68.42.8",
//	"115.68.42.130",
//	"52.78.174.3",
//	"106.254.252.100",
//	"115.68.182.165",
//	"13.124.139.14",
//	"218.39.39.190",
//	"114.108.179.112",
//	"13.209.232.254",
//	"13.124.215.30",
//	"221.141.192.124",
//	"103.60.126.37"
//);
//__accessip($accessip);


//$_type = $_GET['type'];
//$_goods_no = $_GET['goods_no'];
//$_values = $_GET['values'];

$_type = strtolower($_GET['type']);
$_goods_no = strtolower($_GET['goods_no']);
$_values = strtolower($_GET['values']);

$apiheader = getallheaders(); // http 헤더

$_log_header = "S";

$_date2 = date("Y-m-d");
//사용(51), 미사용(50), 취소(54)
if ($_type == "barcode") {
    $_barcode_search = "select * from resom_reservation where barcode = '".$_values."' and itemtype = '$_goods_no'";
} else if ($_type == "sales") {

} else if ($_type == "coupon") {

} 

if (!empty($_barcode_search)){
    $conn_rds->query("set names utf8");
    $itemListResult = $conn_rds->query($_barcode_search);
    $_is_flag = false;
    while($row = $itemListResult->fetch_object()){
        $_is_flag = true;

        if ($row->sales_status == "51"){

            if ($row->valid_date1 <=  $_date2 && $row->valid_date2 >=  $_date2){

                $json_result['return_div'] = "0006";
                $json_result['return_msg'] = "no coupon_no";

                if (!empty($row->order_no)){
                    $_use_search = "SELECT * FROM ordermts  WHERE orderno = '".$row->order_no."' ";

                    $itemUseResult = $conn_cms3->query($_use_search);
                    while($row2 = $itemUseResult->fetch_object()){

                        if ($row2->state == "예약완료"){
                            if ($row2->usegu == "1"){
                                $_use_time = date("Y-m-d H:i:s");
                                $useg_sparo_usql = "update ordermts set usegu = '2' where  orderno = '".$row->order_no."' limit 1 ";
                                $conn_cms3->query($useg_sparo_usql);

                                $usegusql = "insert ordermts_recovery set ch_id='$row2->ch_id', sync_flag= 'N' ,couponno = '$_values' ,order_id='$row2->id', log_ip='" . get_ip_temp() . "', logdate= now()";
                                $conn_cms3->query($usegusql);

                                $update_sql = "UPDATE cmsdb.resom_reservation  SET usegu = 'N' , sales_status = '50' WHERE barcode = '".$_values."'";
                                $conn_rds->query($update_sql);

                                $json_result['return_div'] = "0000";
                                $json_result['return_msg'] = "success";

                                //사용취소 해지가 들어왔는데 이미 해지면 성공으로 응답
                            } else if ($row2->usegu == "2"){

                                $update_sql = "UPDATE cmsdb.resom_reservation  SET usegu = 'N' , sales_status = '50' WHERE barcode = '".$_values."'";
                                $conn_rds->query($update_sql);

                                $json_result['return_div'] = "0000";
                                $json_result['return_msg'] = "success";
                            }
                        } else {
                            $json_result['return_div'] = "0002";
                            $json_result['return_msg'] = "cancel coupon";
                        }

                    }
                }

            } else {
                $json_result['return_div'] = "0010";
                $json_result['return_msg'] = "not a period of use coupon";
            }
        } else if ($row->sales_status == "50"){
            $json_result['return_div'] = "0000";
            $json_result['return_msg'] = "success";
        } else if ($row->sales_status == "54"){
            $json_result['return_div'] = "0002";
            $json_result['return_msg'] = "cancel coupon";
        } else {
            $json_result['return_div'] = "0002";
            $json_result['return_msg'] = "cancel coupon";
        }
    }
    if ($_is_flag == false){
        $json_result['return_div'] = "0006";
        $json_result['return_msg'] = "no coupon_no";
    }

//    $res = json_encode($json_result);
    $res = json_encode($json_result , JSON_UNESCAPED_UNICODE);
    echo $res;
} else {
    $json_result['return_div'] = "0001";
    $json_result['return_msg'] = "system error";
//    $res = json_encode($json_result);
    $res = json_encode($json_result , JSON_UNESCAPED_UNICODE);
    echo $res;
}

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

