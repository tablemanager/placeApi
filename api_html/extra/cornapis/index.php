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

$_sql = "select * from corn_extcoupon where couponno = '$_value'";

$_row = $conn_rds->query($_sql)->fetch_object();

switch($_row->state){
  case 'Y':
    $rcode = "0004";
    $rmsg = "coupon that was used";
  break;
  case 'N':
    $rcode = "0000";
    $rmsg = "success";

    // 사용처리

  break;
  case 'C':
    $rcode = "0002";
    $rmsg = "cancel coupon";
  break;
  default:
    $rcode = "0006";
    $rmsg = "no coupon_no";
}

header("HTTP/1.0 200");
echo json_encode(array(
  $rcode => $rmsg,
  "list" => null));

 ?>
