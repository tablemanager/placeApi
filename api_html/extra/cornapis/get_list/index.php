<?php
// 리라이트가 귀찮아서 디렉토리 처리
date_default_timezone_set('Asia/Seoul');
require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
$conn_rds->query("set names utf8");
$conn_cms3->query("set names utf8");

header("Content-type:application/json");

$_type = $_GET['type'];
$_goods_no = $_GET['goods_no'];
$_value = $_GET['values'];


switch($_type){
  case 'PHONE':
    $_result = array();
    $_sql = "select * from corn_extcoupon where userhp = '$_value'";
  break;
  case 'NAME':
    $_sql = "select * from corn_extcoupon where usernm = '$_value'";
  break;
  case 'COUPON':
    $_sql = "select * from corn_extcoupon where couponno = '$_value'";
  break;
}

$_res = $conn_rds->query($_sql);
while($_row = $_res->fetch_object()){
  switch($_row->state){
    case 'N':
      $_st = "미사용";
    break;
    case 'Y':
      $_st = "사용";
    break;
    case 'C':
      $_st = "취소";
    break;
  }
  $_result[] = array(
    "reserv_barcode" => $_row->couponno,
    "sales_no"=> $_row->orderno,
    "plucode"=> $_row->plucode,
    "sales_status"=> $_st ,
    "user_name"=> $_row->usernm,
    "cnt"=> $_row->qty,
    "goods_name"=> $_row->title,
    "user_term1"=> $_row->sdate,
    "user_term2"=> $_row->edate,
    "return_use_time"=> $_row->date_use,
    "return_cancel_time"=> $_row->date_cancel

  );
}

if(count($_result) == 0){
    $rcode = "0000";
    $rmsg = "success";
}else{
    $rcode = "0000";
    $rmsg = "success";
}

header("HTTP/1.0 200");
echo json_encode(array(
  $rcode => $rmsg,
  "list" =>  $_result), JSON_UNESCAPED_UNICODE);

 ?>
