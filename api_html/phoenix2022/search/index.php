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
    "106.254.252.100",
);

if (!in_array(get_ip(), $accessip)) {
    echo json_encode([]);
    exit;
}

$param = explode('/', $_GET['val']);
$param_pkgcd = trim($param[0]);
$param_useDate = trim($param[1]);
$param_changeCd = trim($param[2]);

if (!$param_pkgcd || !$param_useDate) {
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

$get_pkglist = $phoenixApi->IF_SM_201_get_paglist($get_token->access_token, null, null, $param_pkgcd);
$get_pkglist = $get_pkglist['pkgList'][0];

if ($param_useDate < $get_pkglist['useFromDate'] || $param_useDate > $get_pkglist['useToDate']) {
    return [];
}

$check_step_1 = false;
$check_step_2 = false;
$check_step_3 = false;
$is_ok = false;

$midwkWkndDivCd = '';
$sellAmt = 0;  // 객실료(기본요금)
$addAmt = 0;  // 추가요금

$result = [];
$result['debug']['패키지코드'] = $get_pkglist['pkgCd'];
$result['debug']['패키지명'] = $get_pkglist['pkgNm'];
$result['debug']['사업장코드'] = $get_pkglist['bsuCd'] . " (" . (($get_pkglist['bsuCd'] == '1') ? '휘닉스평창' : '휘닉스제주') . ")";
$result['debug']['최소인원'] = $get_pkglist['minGcnt'];
$result['debug']['최대인원'] = $get_pkglist['maxGcnt'];
// $result['debug']['주중주말코드'] = $get_pkglist['midwkWkndList'];
// $result['debug']['test'] = $get_pkglist;

$result['phoenix']['pkgCd'] = $get_pkglist['pkgCd'];
$result['phoenix']['pkgNm'] = $get_pkglist['pkgNm'];
$result['phoenix']['bsuCd'] = $get_pkglist['bsuCd'];
$result['phoenix']['minGcnt'] = $get_pkglist['minGcnt'];
$result['phoenix']['maxGcnt'] = $get_pkglist['maxGcnt'];
$result['phoenix']['clientList'] = $get_pkglist['clientList'][0];

// 주중주말코드 찾기
foreach ($get_pkglist['midwkWkndList'] as $item) {
    if ($item['date'] == $param_useDate && $item['excpDateYn'] == 'N') {
        $check_step_1 = true;
        $midwkWkndDivCd = $item['midwkWkndDivCd'];
        $result['phoenix']['midwkWkndList'] = $item;
        break;
    }
}

// 찾아낸 주중주말코드의 금액 찾기
foreach ($get_pkglist['stdList'] as $item) {
    if ($item['midwkWkndDivCd'] == $midwkWkndDivCd && $item['sellAmt'] > 0) {
        $check_step_2 = true;
        $sellAmt = $item['sellAmt'];
        $result['phoenix']['stdList'] = $item;
        break;
    }
}

// 찾아낸 주중주말코드 상품 찾기
if ($check_step_2) {
    foreach ($get_pkglist['stdGoodtList'] as $item) {
        if ($item['midwkWkndDivCd'] == $midwkWkndDivCd) {
            $check_step_3 = true;
            $result['phoenix']['stdGoodtList'][] = $item;
        }
    }
}

// 상품(객실) 업그레이드가 있으면 찾기
if ($check_step_3 && $param_changeCd != '') {
    $check_step_3 = false;
    foreach ($get_pkglist['chngGoodtList'] as $item) {
        if ($item['midwkWkndDivCd'] == $midwkWkndDivCd && $item['chngMenuTypeCd'] == $param_changeCd) {
            $check_step_3 = true;
            $addAmt = $item['addAmt'];
            $result['phoenix']['chngGoodtList'] = $item;
            break;
        }
    }
}

$total_amt = $sellAmt + $addAmt;
if ($check_step_1 && $check_step_2 && $check_step_3 && $total_amt > 0) {
    $is_ok = true;
    $result['total_amt'] = $total_amt;
}

if ($is_ok) {
    echo json_encode($result);
} else {
    echo json_encode([]);
}
