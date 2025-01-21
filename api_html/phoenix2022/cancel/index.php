<?php

require_once('/home/sparo.cc/phoenix_script/lib/phoenixApi.php');
require_once('/home/sparo.cc/phoenix_script/lib/ConnSparo2.php');
header("Content-type:application/json");

// 클라이언트 아아피
function get_ip()
{
    $res = explode(",", $_SERVER["HTTP_X_FORWARDED_FOR"]);
    return trim($res[0]);
}

// ACL 확인
$accessip = array(
    "13.209.184.223",
);

if (!in_array(get_ip(), $accessip)) {
    echo json_encode([]);
    exit;
}

$connSparo2 = new ConnSparo2();
$phoenixApi = new phoenixApi();

// 최근 2시간 이내 토큰 발행 체크
$_tokn = $connSparo2->get_authtoken();

if (empty($_tokn)) {
    $get_access = $phoenixApi->get_access_token();
    // 토큰정보 저장
    $connSparo2->logAuthToken($get_access);
    $get_token = json_decode($get_access);
} else {
    // 저장된 토큰 사용
    $get_token = json_decode($_tokn['tokens']);
}

if (!$get_token) {
    echo json_encode([]);
    exit;
}

$reqData = $_POST['reqData'];

// $req_rese = [];
$req_rese = $phoenixApi->IF_SM_204_cancel_order($get_token->access_token, $reqData);
echo json_encode($req_rese);
