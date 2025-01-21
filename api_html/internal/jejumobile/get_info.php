<?php
/**
 * Created by IntelliJ IDEA.
 * User: Connor
 * Date: 2018-11-26
 * Time: 오후 2:01
 */

require_once('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
require_once('/home/sparo.cc/order_script/lib/SendData_Script.php');

$header = array();
$host = "https://api.jejumobile.kr"; // 라이브
//$host = "https://devapi.jejumobile.kr"; // 개발

$accessip = array(
    "115.68.42.2",
    "115.68.42.8",
    "115.68.42.130",
    "52.78.174.3",
    "106.254.252.100",
    "115.68.182.165",
    "13.124.139.14"
);

if (!in_array(get_ip(), $accessip)) {
    http_response_code('404');
    exit;
}else if (strlen($_GET['barcode']) != 16){
    http_response_code('500');
    exit;
}

$name = array(
    'resCd' => '응답코드',
    'resMsg' => '응답메세지',
    'barcode' => '바코드번호',
    'status' => '승인상태',
    'useAbleDate' => '바코드유효일',
    'prdType' => '상품타입',
    'mobileNo' => '휴대폰번호',
    'buyerName' => '구매자 이름',
    'buyerEmail' => '구매자이메일',
    'tid' => '주문번호',
    'svcId' => '제휴사 업체아이디',
    'svcName' => '제휴사 업체명',
    'svcTel' => '발신번호',
    'prdNo' => '상품번호',
    'prdName' => '상품명',
    'optNo' => '옵션번호',
    'optName' => '옵션명',
    'cntA' => '구매한 성인수',
    'cntB' => '구매한 청소년수',
    'cntC' => '구매한 소인수',
    'useCntA' => '총사용한 성인수',
    'useCntB' => '총사용한 청소년수',
    'useCntC' => '총사용한 소인수',
    'priceRA' => '성인 단가',
    'priceRB' => '청소년 단가',
    'priceRC' => '소인 단가',
    'sendDate' => '발행일',
    'cancelDate' => '취소일',
    'totalPrice' => '총구매금액',
    'approveInfos' => '사용승인내역',
    'approveInfo' => '사용승인정보',
    'approveNo' => '승인번호',
    'type' => '인원구분',
    'count' => '수량',
    'approveDate' => '승인일',
    'usePlace' => '사용 관광지명',
);



$ch = curl_init();

// 업체코드
$sellerCode = "R0167";
$barcode = $_GET['barcode'];

$req = array();
$req['sCode'] = $sellerCode;                                    // 업체코드*
$req['barcode'] = $barcode;                             // 바코드*
$res = send_url($ch, $host . '/info', 'GET', $req, $ck, $header);
$res = str_replace(array_keys($name), array_values($name), $res);

$res = str_replace("<![CDATA[","",$res);
$res = str_replace("]]>","",$res);

xmp($res);
exit;

$Xres = simplexml_load_string($res, null, LIBXML_NOCDATA);
xmp($Xres);
exit;
$resCd = (string)$Xres->resCd;

// 작업코드.. 0000이 아니면 다 실패
if ($resCd == "0000") {
    xmp($Xres);
} else {
    xmp("관리자에게 문의 해주시기 바랍니다.");
    exit;
}

// cancelgu = 'Y'
// 로그 처리
//$log = array();
//$log['Order_No'] = $row['orderno'];
//$log['Method'] = "get";
//$log['Request'] = http_build_query($req);
//$log['Respons'] = $conn_rds->escape_string($res);
//insert_log($conn_rds, '`cmsdb`.`jejumobile_log`', $log);

echo "\nEND";


?>