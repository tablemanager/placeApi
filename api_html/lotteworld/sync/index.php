<?php

/*
 *
 * 롯데티켓 연동 인터페이스
 *
 * 작성자 : 이정진
 * 작성일 : 2018-06-18
 *
 * 조회(GET)		: https://gateway.sparo.cc/lotteword/sync/{BARCODE}
 */

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');

header("Content-type:application/json");

$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

// 파라미터
$itemreq = explode("/",$para);
$jsonreq = trim(file_get_contents('php://input'));

$no = $itemreq[0];

$now = date('YmdHis');
$mdate = date('Ymd');

if(!Is_numeric($no)){
    exit;
}

$surl = "https://api.lotteworld.com";

$lmsqry="SELECT * from apidb.lotte_pincode where CouponNo = '$no' limit 1";
$_row = $conn_cms2->query($lmsqry)->fetch_object();

if(empty($_row)){
  exit;
}

$orders = array();
$arrpin = array();

$arrpin[] = array(
"BUKRS"=>$_row->BUKRS,
"GSBER"=>$_row->GSBER,
"EventCd"=>$_row->EventCd,
"EventSeq"=>$_row->EventSeq,
"AgencyCd"=>$_row->AgencyCd,
"ADV_IRType"=>$_row->ADV_IRType,
"CouponNo"=>$_row->CouponNo,
"EventStartDate"=>$_row->EventStartDate,
"EventEndDate"=>$_row->EventEndDate
);

$orders = array("SocIFId"=>"erpif006",
    "AgencyCd"=>"04",
    "CallDate"=>$now,
    "MainList"=> $arrpin);

$apiurl = $surl."/world/app/rs/rsErpIf006.do";
$poststr = json_encode($orders);

$result = curl_post($apiurl,$poststr);
$res = json_decode($result,true);

// 롯데워터 시즌권 행사코드
$lw2019=array(26002,26003,26004,26005,26006,26005,26001,26007);
if(in_array($_row->EventCd,$lw2019)){

}

echo json_encode($res);

function curl_post($url, $poststr)
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_PORT => "443",
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $poststr ,
        CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache",
            "content-type: application/json",
        ),
    ));

    $response = curl_exec($curl);
    $info = curl_getinfo($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        return false;
    } else {
        return $response;
    }

}


?>
