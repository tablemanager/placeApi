<?php
// 벨포레2024 신시스템 사용처리
// 미개발 상태임
exit;
require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
header("Content-type:application/json");

$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

$jsonreq = trim(file_get_contents('php://input'));
$decodedreq = json_decode($jsonreq);

//echo json_encode(array($decodedreq->couponno,$para));

$cprow = $conn_rds->query("select * from cmsdb.m12_orders where couponNo = '$decodedreq->couponno' limit 1")->fetch_object();

if(empty($cprow)){

  $_res = array(
    "result" => "4001",
    "msg" => "조회결과없음"
  );

}else{
  if($cprow->useState =='Y'){
    $_res = array(
      "result" => "4003",
      "msg" => "잘못된요청(기사용티켓)"
    );
  }elseif($cprow->orderState =='C'){
    $_res = array(
      "result" => "4002",
      "msg" => "잘못된요청(환불티켓)"
    );
  }else{
    $_res = array(
      "result" => "1000",
      "msg" => "성공"
    );

    $_usql = "update cmsdb.m12_orders set useState='Y', useDate = now() where couponNo = '$decodedreq->couponno' limit 1";
    $conn_rds->query($_usql);

    $_osql = "update spadb.ordermts set usegu = 1, usegu_at = now() where usegu = 2 and state= '예약완료' and orderno = '$cprow->cmsOrderNo' limit 1";
    $conn_cms3->query($_osql);
  }



}

  echo json_encode($_res);
?>
