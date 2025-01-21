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


$_type = strtolower($_GET['type']);
$_goods_no = strtolower($_GET['goods_no']);
$_values = strtolower($_GET['values']);

$apiheader = getallheaders(); // http 헤더

$_log_header = "S";

$_date2 = date("Y-m-d");
//사용(51), 미사용(50), 취소(54)
if ($_type == "phone") {
//    $_barcode_search = "select * from resom_reservation where user_hp = '".$_values."' ";
//    $_barcode_search = "select * from resom_reservation where user_hp = '".$_values."' and itemtype = '$_goods_no'";
    $_barcode_search = "select * from resom_reservation where RIGHT(user_hp,4) = '".$_values."' and itemtype = '$_goods_no'";
} else if ($_type == "name") {
//    $_barcode_search = "select * from resom_reservation where user_name = '".$_values."' and itemtype = '$_goods_no'";
    $_barcode_search = "select * from resom_reservation where user_name like '%".$_values."%' and itemtype = '$_goods_no'";
//    $_barcode_search = "select * from resom_reservation where user_name = '".$_values."' ";
} else if ($_type == "coupon") {
//    $_barcode_search = "select * from resom_reservation where user_name = '".$_values."' ";
} else if ($_type == "barcode") {
    $_barcode_search = "select * from resom_reservation where barcode = '".$_values."' and itemtype = '$_goods_no'";
} else {
    $json_result['return_div'] = "0006";
    $json_result['return_msg'] = "no coupon_no";
//    $res = json_encode($json_result);
    $res = json_encode($json_result , JSON_UNESCAPED_UNICODE);
    echo $res;
    exit;
}
//echo $_barcode_search;
if (!empty($_barcode_search)){

    $conn_rds->query("set names utf8");

    $itemListResult = $conn_rds->query($_barcode_search);
    $_is_flag = false;
    $_response['list'] = [];
    $i = 0;
    while($row = $itemListResult->fetch_object()){
        $_response['list'][$i]['barcode'] = $row->barcode;
        $_response['list'][$i]['sales_no'] = $row->order_no;
        $_response['list'][$i]['sales_status'] = $row->sales_status;
        $_response['list'][$i]['user_name'] = $row->user_name;
        $_response['list'][$i]['cnt'] = $row->product_cnt;
        $_response['list'][$i]['goods_name'] = $row->goods_name;
        $_response['list'][$i]['valid_date1'] = $row->valid_date1;
        $_response['list'][$i]['valid_date2'] = $row->valid_date2;
        $_response['list'][$i]['use_time'] = $row->use_time;
        $_response['list'][$i]['cancel_time'] = $row->cancel_time;
        $_response['list'][$i]['user_hp'] = $row->user_hp;
        $_plucode = "";
        if ($row->plucode == "411447"){
            $_plucode = "ma-411447,fa-411448";
        } else if ($row->plucode == "411449") {
//            $_plucode = "mc-411449,fc-411450";
//            $_plucode = "ma-411447,fa-411448,mc-411449,fc-411450";
            $_plucode = "ma-411449,fa-411450";
        } else if ($row->plucode == "4110E2"){
            $_plucode = "ma-4110E2";
        } else if ($row->plucode == "851012"){
            $_plucode = "ma-851012";
        } else if ($row->plucode == "851013"){
            $_plucode = "ma-851013 ";
        } else {
            $_plucode = $row->plucode;
        }
//        $_response['list'][$i]['plucode'] = $row->plucode;
        $_response['list'][$i]['plucode'] = $_plucode;
        $i++;
    }

    if ($i == 0){
        $json_result['return_div'] = "0006";
        $json_result['return_msg'] = "no coupon_no";
//        $res = json_encode($json_result);
        $res = json_encode($json_result , JSON_UNESCAPED_UNICODE);
        echo $res;
        exit;
    } else {
        $json_result = $_response;
    }

}
$res = json_encode($json_result , JSON_UNESCAPED_UNICODE);

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

