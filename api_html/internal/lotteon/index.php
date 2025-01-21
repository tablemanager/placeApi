<?php
/*
롯데온 사용처리 / 사용 해제 처리
*/
error_reporting(0);
date_default_timezone_set('Asia/Seoul');

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
$conn_cms3->query("set names utf8");
$conn_rds->query("set names utf8");

header("Content-type:application/json");


$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

// 파라미터
$itemreq = explode("/",$para);
$jsonReq = json_decode(trim(file_get_contents('php://input')), true);

$_res = array("serverCd"=>"api-toc","tranCd"=>genRandomStr(),"ipAddr"=>get_ip(), "item"=>$itemreq);

if(get_ip() == "121.138.151.22"){
	//echo json_encode($_res);
}else{
	//echo json_encode($_res);
}
$mode =  $itemreq[0];
$chorderno = $itemreq[1];
$sendDt = date('YmdHis');
$sdate = "";
$edate = "";
$couponno = "";

$_osql = "select * from chmsdb.crowling_order where order_num = '$chorderno' limit 1";
$o_row = $conn_rds->query($_osql)->fetch_object();

$_dump = json_decode($o_row->response_data);


if($apimethod != "POST") exit;
switch($mode){
  case 'use':
      $_res = setuse($_dump);
  break;
  case 'unuse':
      $_res = setunuse($_dump);
  break;
  case 'send':
    //  $_res = sendeCoupon($chorderno,$_dump);
  break;
  default:
  exit;

  }

 echo json_encode($_res,JSON_UNESCAPED_UNICODE);


$logStr = "";

function setuse($_dump){
  $_req = array(
     "cpnIfTyp" => "S",
     "procCd"=>  "0000",
     "procMsg"=>  "정상 발송",
     "odNo"=>  $_dump->odNo,
     "odSeq"=>  "$_dump->odSeq",
     "procSeq"=>  "$_dump->procSeq",
     "spdNo"=>  "$_dump->spdNo",
     "odQty"=>  "$_dump->odQty",
     "odrNm"=>  "$_dump->odrNm",
     "odrMphnNo"=>  "$_dump->odrMphnNo",
     "rcvrNm"=>  "$_dump->rcvrNm",
     "rcvrMphnNo"=>  "$_dump->rcvrMphnNo",
     "cpnNo" => $couponno,
     "pinNo"=>  null,
     "ecpnVldStrtDt"=>  $sdate,
     "ecpnVldEndDt"=>  $edate,
     "msgSndDttm"=>  $sendDt
  );

  return $_req;
}

function setunuse($_dump){
  $_req = array(
     "cpnIfTyp" => "S",
     "procCd"=>  "0000",
     "procMsg"=>  "정상 발송",
     "odNo"=>  $_dump->odNo,
     "odSeq"=>  "$_dump->odSeq",
     "procSeq"=>  "$_dump->procSeq",
     "spdNo"=>  "$_dump->spdNo",
     "odQty"=>  "$_dump->odQty",
     "odrNm"=>  "$_dump->odrNm",
     "odrMphnNo"=>  "$_dump->odrMphnNo",
     "rcvrNm"=>  "$_dump->rcvrNm",
     "rcvrMphnNo"=>  "$_dump->rcvrMphnNo",
     "cpnNo" => $couponno,
     "pinNo"=>  null,
     "ecpnVldStrtDt"=>  $sdate,
     "ecpnVldEndDt"=>  $edate,
     "msgSndDttm"=>  $sendDt
  );
  return $_req;

}

function sendeCoupon($chorderno ,$_dump){

  global $conn_cms3;

  $_osql = "select * from spadb.ordermts where ch_orderno = '$chorderno' and ch_id = '3717'";
  $o_row = $conn_cms3->query($_osql)->fetch_object();

  $sendDt = date('YmdHis');
  $sdate = date('Ymd');
  $edate = str_replace("-","",$o_row->usedate);

  $_req = array(
     "cpnIfTyp" => "S",
     "procCd"=>  "0000",
     "procMsg"=>  "정상 발송",
     "odNo"=>  $_dump->odNo,
     "odSeq"=>  "$_dump->odSeq",
     "procSeq"=>  "$_dump->procSeq",
     "spdNo"=>  "$_dump->spdNo",
     "odQty"=>  "$_dump->odQty",
     "odrNm"=>  "$_dump->odrNm",
     "odrMphnNo"=>  "$_dump->odrMphnNo",
     "rcvrNm"=>  "$_dump->rcvrNm",
     "rcvrMphnNo"=>  "$_dump->rcvrMphnNo",
     "cpnNo" => $couponno,
     "pinNo"=>  null,
     "ecpnVldStrtDt"=>  $sdate,
     "ecpnVldEndDt"=>  $edate,
     "msgSndDttm"=>  $sendDt
  );
  return $_req;

/*
  $curl = curl_init();

  curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://openapi.lotteon.com/v1/openapi/delivery/v1/eCouponSentInform',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS =>json_encode($_req),
    CURLOPT_HTTPHEADER => array(
      'Authorization: Bearer 5d5b2cb498f3d20001665f4e1b7ed2884bb24ef69d096cb77cf2a1eb',
      'Content-Type: application/json'
    ),
  ));

  $response = curl_exec($curl);

  curl_close($curl);
  return $response;
*/
}



function genRandomStr($length = 10) {
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  $characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
  $characters = '0123456789';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}

function get_ip(){

	if(empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
		 $ip= $_SERVER["REMOTE_ADDR"].",";
	}else{
		$ip= $_SERVER["HTTP_X_FORWARDED_FOR"];
	}

	$res = explode(",",$ip);

    return trim($res[0]);
}
