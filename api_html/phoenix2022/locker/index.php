<?php

require_once('/home/sparo.cc/phoenix_script/lib/phoenixApi.php');
//require_once('./phoenixApi.php');
require_once('/home/sparo.cc/phoenix_script/lib/ConnSparo2.php');
header("Content-type:application/json");

// 휘닉스 개발서버 호출:true
$debug = false;

// 판매처 정보 설정 휘닉스 패키지리스트 정보에서 뽑아서 쓰기:true
// false 가 기본값:G마켓으로 고정하기로 협의됩. 
// 다른 채널에도 판매하게 변경되면 여기도 변경이 필요하다.
$new = false;

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
if($debug){
    $phoenixApi = new phoenixApi("y");
}else{
    $phoenixApi = new phoenixApi();
}

// 최근 2시간 이내 토큰 발행 체크
// 운영 전환시에 주석풀자
if ($debug != true){
    $_tokn = $connSparo2->get_authtoken();
}

if (empty($_tokn)) {
    $get_access = $phoenixApi->get_access_token();
    // 토큰정보 저장
    if ($debug != true){
        $connSparo2->logAuthToken($get_access);
    }
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

// G마켓만 하므로 판매거래처 코드를 찾을 필요가 없다
// 다른 채널이 추가되면 재검토 필요
/*
if ($new){
    $reqSelect = $_POST['reqSelect'];

    $phoenixApi->get_ph_sellClientCd_from_chID($reqSelect['ch_id']);


    foreach ($connSparo2->get_phoenix_pkglist_by_phoenixCd($reqSelect['phoenixCd']) as $row){

        $sellDate = date('Ymd');
        $clientList = json_decode($row['clientList']);      //거래처리스트
        $stdList = json_decode($row['stdList']);            //기준정보
        $stdGoodtList_ARR = json_decode($row['stdGoodtList']);  //기준상품리스트
        $chngGoodtList = json_decode($row['chngGoodtList']);

        $get_client_cd = $phoenixApi->getSellClientCdbyChId($reqSelect['ch_id']);
        $_sellClientNm = '';
        $_sellClientCd = '';
        $_agncyClientCd = '';
        $_arSttlClientCd = '';
        foreach ($clientList as $item) {
            if ($item->sellClientCd == $get_client_cd) {
                $_sellClientNm = $item->sellClientNm;
                $_sellClientCd = $item->sellClientCd;
                $_agncyClientCd = $item->agncyClientCd;
                $_arSttlClientCd = $item->arSttlClientCd;
                break;
            }
        }
        if (!$_sellClientCd || !$_agncyClientCd || !$_arSttlClientCd) {
            $_sellClientNm = $clientList[0]->sellClientNm;
            $_sellClientCd = $clientList[0]->sellClientCd;
            $_agncyClientCd = $clientList[0]->agncyClientCd;
            $_arSttlClientCd = $clientList[0]->arSttlClientCd;
        }

        $reqData['clientNm'] = $_sellClientNm;          // 거래처명:플레이스엠(B2BC)
        $reqData['sellClientCd'] = $_sellClientCd;      // 판매거래처코드:지정받은 거래처코드
        $reqData['agncyClientCd'] = $_agncyClientCd;    // 판매대행사코드:지정받은 거래처코드
        $reqData['arSttlClientCd'] = $_arSttlClientCd;  // :후불거래처코드:지정받은 거래처코드
    }

    //echo json_encode($reqData);exit;
    //$get_info = $phoenixApi->IF_SM_203_info_order($get_token->access_token, $reqData);
}
*/

$get_info = $phoenixApi->IF_SM_220_locker_reg($get_token->access_token, $reqData);

echo json_encode($get_info, JSON_UNESCAPED_UNICODE);

?>
