<?php
/*
 *
 * 이브릿지, 더 라운지, THE LOUNGE, the lounge
 *
 * 작성자 : 이용준(토니)
 * 작성일 : 2022-12-22
 *
 *
 */

require_once ('/home/sparo.cc/api_html/extra/ebridge/const_ebridge.php');
require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');

$conn_rds->query("set names utf8");

$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

// 파라미터
$itemreq = explode("/",$para);
$jsonreq = json_decode(trim(file_get_contents('php://input')));

$proc = $itemreq[0];

list($microtime, $timestamp) = explode(' ', microtime());
$tranid = $timestamp.substr($microtime, 2, 3);
insertLog($apiheader, $apimethod, $para, json_encode($jsonreq, JSON_UNESCAPED_UNICODE));

// 60175 이브릿지 DEV:343, 운영:802 공항 라운지(테스트)
// 60176 이브릿지 DEV:344, 운영:803 K리무진(테스트)
// 시험 호출
//echo setOrderCoupon('', '60175', '1', '테스트-토니', '01067934084');
//setCancelCoupon('1234567890123456');
//getCouponInfo('1234567890123456');
//exit;

// ACL 확인-개발망, 스크립트 서버에서만 접속 가능
$accessip = array("106.254.252.100","52.78.174.3","13.124.215.30");

if(!in_array(get_ip(),$accessip)){
    header("HTTP/1.0 401 Unauthorized");
    $res = array("resultCode"=>"9996","resultMessage"=>"아이피 인증 오류 : ".get_ip());
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    exit;
}


header("Content-type:application/json");

$mdate = date("Y-m-d");

$_resjson = json_encode(array("resultCode"=>"9998","resultMessage"=>"파라미터 오류"), JSON_UNESCAPED_UNICODE);

/*
  1. 파라미터 체크가 필요함(코드)
*/
//echo $proc;
switch($proc){
    // 이용권 발행
	case 'order':
        // pcms 상품코드
        $itemcode = $itemreq[1];
        if($apimethod == "POST") $_resjson = setOrderCoupon($jsonreq->orderno, $itemcode, $jsonreq->qty, $jsonreq->usernm, $jsonreq->userhp);
	break;
    // 이용권 발행 취소:핀폐기
	case 'cancel':
        $couponno = $itemreq[1];
		if($apimethod == "GET") $_resjson = setCancelCoupon($couponno);
	break;
    // 정보 조회
	case 'info':
        $couponno = $itemreq[1];
		if($apimethod == "GET") $_resjson = getCouponInfo($couponno);
	break;
    // 사용처리 싱크(시설에서 사용처리된 경우 cm에도 사용처리시켜 줌)
    // 일배치로 사용상태 일괄체크하는데 사용한다. 통신량이 많아 현재는 사용하지 않고, 이브릿지에서 플엠의 사용처리 표준api를 호출한다.
    case 'syncuse':
        // ordermts 주문번호
        $orderno = $itemreq[1];
        // 시설 쿠폰번호
        $couponno = $itemreq[2];
        // 시설이 이브릿지이고
        // 채널이 위메프, 쿠팡이면 사용처리 전문에 채널 쿠폰번호로(ordermts.barcode_no) 처리한다.
        // rds.cmsdb.ebridge_excoupon에는 시설쿠폰번호(ordermts.couponno, itemreq[2]로 처리한다.
        if($apimethod == "GET") $_resjson = syncCouponUse($orderno, $couponno);
	break;
	default:
		header("HTTP/1.0 400");
}

echo $_resjson;

// 이용권 발행
function setOrderCoupon($orderno, $itemcode, $qty, $usernm, $userhp)
{
    global $conn_rds;
    global $conn_cms;

    $curl = curl_init();

    // 이브릿지 상품코드 구하기
    $_row = $conn_cms->query("select * from CMSDB.CMS_ITEMS where item_id = '$itemcode' AND item_state = 'Y' limit 1")->fetch_object();
    // 이브릿지 상품코드
    $pcode = $_row->item_cd;

    if(empty($pcode)){
        // 결과 로깅
        updateLogResult("이브릿지 상품코드 없음");
        return false;
    }
    if(empty($usernm)){
        // 결과 로깅
        updateLogResult("고객 이름 없음");
        return false;
    }
    if(empty($userhp)){
        // 결과 로깅
        updateLogResult("고객 핸드폰번호 없음");
        return false;
    }

    $chkcp = $conn_rds->query("select * from cmsdb.ebridge_extcoupon where orderno='$orderno'");
    // 중복요청
    if($chkcp->num_rows > 0){
        // 결과 로깅
        updateLogResult("중복요청 orderno[$orderno]");
        return false;
    }

    // issue_code : 시설 상품코드
    // issue_count : 매수, capacity_count=1 => 1인권 매수만큼 발권하도록 설정
    // send_coupon : N 시설에서 안보내는 것으로 설정하고 싶은데 N으로 하면 되는지 일단 해봄
    // 전화번호를 빼면 이브릿지에서 문자를 보내지 않는다.
    //$api_url = API_ISSUE."?user_id=".USER_ID."&password=".USER_PASSWORD."&issue_code=$pcode&issue_count=$qty&capacity_count=1&send_coupon=N&receiver_phone_num=$userhp";
    $api_url = API_ISSUE."?user_id=".USER_ID."&password=".USER_PASSWORD."&issue_code=$pcode&issue_count=$qty&capacity_count=1&send_coupon=N";

    //yjlee
    //echo "$api_url\n";

    curl_setopt_array($curl, array(
        CURLOPT_URL => $api_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
    ));
    $response = curl_exec($curl);

    $res = json_decode($response);

    //yjlee
    //print_r($res);

    // 결과 로깅
    updateLogResult($response);

    // 응답 예시
    // 1장
    // {"response":{"transaction_id":"f8cc9d90b3956d4b208bdd4ce33b72b9","action_result":"success","action_success_message":"Coupon was successfully issued."},"content":{"coupon_list":[{"name":"\uad6d\ub0b4 \ub77c\uc6b4\uc9c0 \uc774\uc6a9\uad8c","coupon_num":"8590300050763790","valid_start_date":"1970-01-01","valid_end_date":"2023-12-23","short_url":"http:\/\/qt7.kr\/t0eix"}]}}
    // 2장
    // {"response":{"transaction_id":"b39900e4aaa3899931f7b363db125f1d","action_result":"success","action_success_message":"Coupon was successfully issued."},"content":{"coupon_list":[{"name":"\uad6d\ub0b4 \ub77c\uc6b4\uc9c0 \uc774\uc6a9\uad8c","coupon_num":"8590300043077043","valid_start_date":"1970-01-01","valid_end_date":"2023-12-28","short_url":"http:\/\/qt7.kr\/1ofw3"},{"name":"\uad6d\ub0b4 \ub77c\uc6b4\uc9c0 \uc774\uc6a9\uad8c","coupon_num":"8590300076085371","valid_start_date":"1970-01-01","valid_end_date":"2023-12-28","short_url":"http:\/\/qt7.kr\/hg056"}]}}
    // json_decode 2장
    // stdClass Object ( [response] => stdClass Object ( [transaction_id] => 2d7ff7636ccb9dbd33e1bc729588b3e1 [action_result] => success [action_success_message] => Coupon was successfully issued. ) [content] => stdClass Object ( [coupon_list] => Array ( [0] => stdClass Object ( [name] => 국내 라운지 이용권 [coupon_num] => 8590300067551132 [valid_start_date] => 1970-01-01 [valid_end_date] => 2023-12-28 [short_url] => http://qt7.kr/oygbl ) [1] => stdClass Object ( [name] => 국내 라운지 이용권 [coupon_num] => 8590300094423907 [valid_start_date] => 1970-01-01 [valid_end_date] => 2023-12-28 [short_url] => http://qt7.kr/r4g87 ) ) ) )

/*
    stdClass Object ( 
        [response] => stdClass Object ( 
            [transaction_id] => 2d7ff7636ccb9dbd33e1bc729588b3e1 
            [action_result] => success 
            [action_success_message] => Coupon was successfully issued. ) 
        [content] => stdClass Object ( 
            [coupon_list] => 
                Array ( 
                    [0] => stdClass Object ( 
                        [name] => 국내 라운지 이용권 
                        [coupon_num] => 8590300067551132 
                        [valid_start_date] => 1970-01-01 
                        [valid_end_date] => 2023-12-28 
                        [short_url] => http://qt7.kr/oygbl ) 
                    [1] => stdClass Object ( 
                        [name] => 국내 라운지 이용권 
                        [coupon_num] => 8590300094423907 
                        [valid_start_date] => 1970-01-01 
                        [valid_end_date] => 2023-12-28 
                        [short_url] => http://qt7.kr/r4g87 ) 
                ) 
        ) 
    )
*/

/* 
    $info = curl_getinfo($curl);
    curl_close($curl);
    if ($info['http_code'] == "200") {
        return $data[0];
    } else {
        return false;
    }
*/

    $_cp = array();

    if ($res->response->action_result == "success"){
        foreach($res->content->coupon_list as $cpn){
            $_cp[] = $cpn;
        }
    }

    // 티켓 테이블에 저장
    foreach($_cp as $ccc)
    {   
        //print_r($ccc);
        $_isql = "insert cmsdb.ebridge_extcoupon set transaction_id='{$ccc->transaction_id}', coupon_num='{$ccc->coupon_num}', orderno='$orderno', regDt=now(), usernm='$usernm', userhp='$userhp', item_name='{$ccc->name}', valid_start_date='{$ccc->valid_start_date}', valid_end_date='{$ccc->valid_end_date}', short_url='".addslashes($ccc->short_url)."'";
        //echo $_isql;
        $conn_rds->query($_isql);
    }

    // 응답형식
    // 1장 
    // [{"name":"국내 라운지 이용권","coupon_num":"8590300047358576","valid_start_date":"1970-01-01","valid_end_date":"2023-12-28","short_url":"http:\/\/qt7.kr\/tjnxp"}]
    // 2장
    // [{"name":"국내 라운지 이용권","coupon_num":"8590300063167729","valid_start_date":"1970-01-01","valid_end_date":"2023-12-28","short_url":"http:\/\/qt7.kr\/l54vh"},{"name":"국내 라운지 이용권","coupon_num":"8590300034541556","valid_start_date":"1970-01-01","valid_end_date":"2023-12-28","short_url":"http:\/\/qt7.kr\/8mqis"}]

    return json_encode($_cp, JSON_UNESCAPED_UNICODE);
}

// 이용권 발행 취소:핀폐기
function setCancelCoupon($couponno)
{
    global $conn_rds;

    $curl = curl_init();
    $api_url = API_ISSUE_CANCEL."?user_id=".USER_ID."&password=".USER_PASSWORD."&coupon_num=$couponno";

    //yjlee
    //uecho "$api_url\n";

    curl_setopt_array($curl, array(
        CURLOPT_URL => $api_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
    ));

    $response = curl_exec($curl);

    //yjlee
    //print_r($response);

    $res = json_decode($response);

    // 결과 로깅
    updateLogResult($response);

    curl_close($curl);

    // 실패
    // {"response":{"transaction_id":"24ed19bc17b5c25357c017f0e9fb65b3","action_result":"failure","action_failure_code":"E0204","action_failure_reason":"The account does not exist."}}
    // 성공
    // {"response":{"transaction_id":"fa90bef5b183e7d29440b57ab08489a9","action_result":"success","action_success_message":"Cancelled."}}

    return json_encode($res, JSON_UNESCAPED_UNICODE);
}

// 이용권 정보 조회
function getCouponInfo($couponno)
{
    $curl = curl_init();
    
    $api_url = API_INFO."?user_id=".USER_ID."&password=".USER_PASSWORD."&coupon_num=$couponno";

    //yjlee
    //echo "$api_url\n";

    curl_setopt_array($curl, array(
        CURLOPT_URL => $api_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
    ));

    $response = curl_exec($curl);

    //yjlee
    //print_r($response);

    $res = json_decode($response);

    // 결과 로깅
    updateLogResult($response);

    curl_close($curl);
    // return json_encode(explode(";",$response), JSON_UNESCAPED_UNICODE);
    
    // 실패
    // {"response":{"transaction_id":"fbe7c39157a165a5825b4830c48cc098","action_result":"failure","action_failure_code":"E0204","action_failure_reason":"The account does not exist."}}
    return json_encode($res, JSON_UNESCAPED_UNICODE);
}

// 사용처리(시설에 사용처리되어 있으므로 CM을 업데이트한다.)
function syncCouponUse($orderno, $couponno)
{
    global $conn_cms3;

    $ordsql = "select * from ordermts where orderno='$orderno'";
    $ordres = $conn_cms3->query($ordsql);
    $ordrow = $ordres->fetch_object();
    if(empty($ordrow)){
        $res = array("rtn"=>"N", "msg"=>"주문정보 조회 실패");
    }
    // 네이버예약 은 일단 패스
    elseif($ordrow->ch_id == '2984'){
        $res = array("rtn"=>"N", "msg"=>"네이버예약 채널 구매건은 일단 패스-플엠에연락바람");
    // 이브릿지 업체코드
    }elseif($ordrow->grmt_id == '3909'){
        $rtn = usecouponno($couponno);
        $res = array("rtn"=>$rtn, "msg"=>"사용처리응답");
/*
        Array
        (
            [rtn] => Y
            [msg] => 사용처리응답
        )
        {"rtn":"Y","msg":"사용처리응답"}
*/
    }
//    print_r($res);
    
    // 결과 로깅
    updateLogResult(json_decode($res)); 

    return json_encode($res, JSON_UNESCAPED_UNICODE);
}

// 클라이언트 아아피
function get_ip()
{
    if(empty($_SERVER["HTTP_X_FORWARDED_FOR"]))
    {
        $ip= $_SERVER["REMOTE_ADDR"].",";
    }
    else
    {
        $ip= $_SERVER["HTTP_X_FORWARDED_FOR"];
    }

    $res = explode(",",$ip);

    return trim($res[0]);
}

function insertLog($apiheader, $apimethod, $para, $jsonreq){
    global $conn_rds;
    global $tranid;

    $apiheader = json_encode($apiheader, JSON_UNESCAPED_UNICODE);
    $qry = "INSERT cmsdb.extapi_log_ticket
                    SET apinm='ebridge',tran_id= ? ,
                        ip='".get_ip()."', logdate= now(), apimethod= ?,
                        querystr= ?, header= ?,
                        body= ?";

    $stmt = $conn_rds->prepare($qry);
    $stmt->bind_param("sssss", $tranid,$apimethod,$para,$apiheader,$jsonreq);
    $logrtn = $stmt->execute();

    //echo "로그 결과[$logrtn]\n";
}

// api 리턴 결과를 로그에 기록 - Jason 22.03.09
function updateLogResult($msg){
    global $conn_rds;
    global $tranid;
    $msg = date("Y-m-d H:i:s").": $msg\n";

    $sql = "UPDATE cmsdb.extapi_log_ticket SET apiresult = concat(ifnull(apiresult, ''), '$msg') WHERE tran_id = '$tranid'";
    $conn_rds->query($sql);
}


function usecouponno($no)
{
	// 쿠폰 사용처리
	$curl = curl_init();
    $url = "http://172.31.30.15:3040/use/".$no;
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_USERAGENT,"Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1" );
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 5);
    $data = explode(";",curl_exec($curl));
    $info = curl_getinfo($curl);
    curl_close($curl);

    if($data[1] == "0")
    {
        return "N";
    }
    else
    {
        return "Y";
    }
}

?>
