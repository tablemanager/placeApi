<?php

/*
 *
 * 휘닉스파크 시즌권 취소 인터페이스
 *
 * 작성일 : 2018-12-13
 *
 * 조회(GET)			: https://gateway.sparo.cc/everland/sync/{BARCODE}
 * 발권(POST)		: https://gateway.sparo.cc/everland/sync/{BARCODE}
 * 발권취소(PATCH)	: https://gateway.sparo.cc/everland/sync/{BARCODE}
 */

//http://gateway.sparo.cc/phoenix/cancel/18100409982
require_once ('/home/sparo.cc/phoenix_script/lib/phoenixApi.php');
require_once ('/home/sparo.cc/phoenix_script/lib/ConnSparo2.php');
header("Content-type:application/json");

// ACL 확인
$accessip = array("115.68.42.2",
    "115.68.42.8",
    "115.68.42.130",
    "52.78.174.3",
    "106.254.252.100",
    "115.68.182.165",
    "13.124.139.14",
    "13.209.232.254"
);

if(!in_array(get_ip(),$accessip)){
    header("HTTP/1.0 401 Unauthorized");
    $res = array("Result"=>"4100","Msg"=>"아이피 인증 오류 : ".get_ip());
    echo json_encode($res);
    exit;
}

$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

// 파라미터
$itemreq = explode("/",$para);
$jsonreq = trim(file_get_contents('php://input'));

$rprsSellNo = $itemreq[0];

$phoenixApi = new phoenixApi();
$connSparo2 = new ConnSparo2();

$get_access = $phoenixApi->get_access_token();

$_tokn = $connSparo2->get_authtoken();


if(empty($_tokn)){
    $get_access = $phoenixApi->get_access_token();
    // 토큰정보 저장
    $connSparo2->logAuthToken($get_access);
    $get_token = json_decode($get_access);
}else{
    // 저장된 토큰 사용
    $get_token = json_decode($_tokn['tokens']);
}


if($get_token->access_token){
    $get_token = json_decode($get_access);
    //18100409982
    $fields = array(
        'rprsSellNo'=>$rprsSellNo      //휘닉스 대표 판매 번호
    );
    $fields = json_encode($fields);

    $order_responce = $phoenixApi->IF_SM_204_cancel_order($get_token->access_token, $fields);
    //echo $order_responce;
    echo json_encode($order_responce);
}else{
    print_r($get_access);
}

// 클라이언트 아아피
function get_ip(){
    $res = explode(",",$_SERVER["HTTP_X_FORWARDED_FOR"]);
    return trim($res[0]);
}
?>
