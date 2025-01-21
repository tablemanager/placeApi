<?php
/*
 *
 * @brief 벨포레(v2) 내부 인터페이스-취소대상 티켓 상태조회, 취소(핀폐기)
 * @doc 
 * 파라미터 : json으로 본문으로 전송
 * 취소대상 조회 : 주문번호, 티켓번호 필요 -> 주문번호로 조회하고 응답에 티켓 리스트에서 파라미터의 티켓번호로 상태 체크 
 * 취소 : 주문번호와 티켓번호로 취소(핀폐기)
 * 응답을 응답코드 result:1000 에 세부데이터로 벨포레v2 응답을 그대로 전송함
 * @author tony
 * @date 20240703
 *
 */

// 다른업체 사용시 아래 11-13 line 주석처리 후 사용하세요 (ila)
// $res = array("result"=>"4000", "msg"=>"잘못된요청(파라미터)");
// echo $jsonres = json_encode($res, JSON_UNESCAPED_UNICODE);
// exit;

//error_reporting(0);
require_once('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
require_once('/home/placedev/php_script/lib/placemlib.php');
// 잔디 알람 전송 라이브러리
require_once ('/home/sparo.cc/Library/noticelib.php');
// 파일로그 라이브러리(./txt 디렉토리 777 퍼미션 필요)
require_once ('/home/sparo.cc/Library/logutil.php');
// 벨포레v2 상수
require_once ('/home/sparo.cc/belleforet_script/v2/const_belleforet_v2.php');

// 벨포레 업체코드
define(__BELLEFORET_GRMT_ID_V1, '3859');
define(__BELLEFORET_GRMT_ID_V2, '4020');

//$_is_api = false;
// 로그작성 초기화
_initlog("", "api");
// 오래된 로그파일 삭제
_dellog();


header("Content-type:application/json");

$conn_rds->query("set names utf8");

$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

$jsonreq = trim(file_get_contents('php://input'));
$decodedreq = json_decode($jsonreq);
// json으로 입력받은 데이터
$orderno = $decodedreq->orderno;
$couponno = $decodedreq->couponno;
$order_id = $decodedreq->order_id;

list($microtime, $timestamp) = explode(' ', microtime());
$tranid = $timestamp . substr($microtime, 2, 3);

_logI("[$tranid] --------------- START ---------------");
insertLog($apiheader, $apimethod, $para, $jsonreq);
// 로그 파일기록
_logI("[$tranid] method:[$apimethod], param:[$para], header:[\n".print_r($apiheader, true)."]");
_logI("[$tranid] body(json):[\n".print_r($jsonreq, true)."]");

// 개발상태 플레그
$_is_devip = false;
if(get_ip() == "106.254.252.100"){
    // var_dump($jsonreq);
	// echo $apimethod;
    $_is_devip = true;
}

// 내부서버(3번)에서만 접속 허용
if(
// 3번 배치서버
//get_ip() != "52.78.174.3" && 
// 개발망
get_ip() != "106.254.252.100" &&
// CMAdmin
get_ip() != "13.209.232.254"){
    $res = array("result"=>"4100", "msg"=>"인증오류(IP)");
    echo $jsonres = json_encode($res, JSON_UNESCAPED_UNICODE);
    _logI("[$tranid] res:[$jsonres]");
    exit;
} 

// 내부호출이므로 인증모듈 처리 안함
//// 인증 정보 조회
//$auth = $apiheader['Authorization'];
//if(!$auth) $auth = $apiheader['authorization'];
//
//$authqry = "SELECT * FROM spadb.extapi_config WHERE authkey = ? limit 1";
//$authstmt = $conn_cms3->prepare($authqry);
//$authstmt->bind_param("s", $auth);
//$authstmt->execute();
//$authres = $authstmt->get_result();
//$authrow = $authres->fetch_object();
//
//// var_dump($authrow);
//$aclmode = $authrow->aclmode;
//$grmtId = $authrow->cp_grmtid;
//
//// API키 확인
//if(!$authrow->cp_code){
//
//    header("HTTP/1.0 401 Unauthorized");
//    $res = array("Result"=>"4100","Msg"=>"인증 오류");
//    echo $jsonres = json_encode($res);
//    updateLogResult($jsonres);
//    exit;
//
//}else{
//
//    $cpcode = $authrow->cp_code; // 채널코드
//    $cpname = $authrow->cp_name; // 채널명
//    $grmt_id = $authrow->cp_grmtid; // 채널 업체코드
//    $ch_id = $grmt_id;
//    $logsql = "UPDATE cmsdb.extapi_log SET chnm = '$cpname' WHERE tran_id = '$tranid'";
//    $conn_rds->query($logsql); 
//}
//
//if($aclmode == "IP"){
//// ACL 확인
//    if(!in_array(get_ip(), json_decode($authrow->accessip, false))){
//        header("HTTP/1.0 401 Unauthorized");
//        $res = array("result"=>"4100", "msg"=>"인증오류(ip)");
//        echo $jsonres = json_encode($res, JSON_UNESCAPED_UNICODE);
//        updateLogResult($jsonres);
//        exit;
//    }
//}

// 핀번호 기본 validation 체크
$param_err = false;
switch($para){
    // 취소가능여부 체크
    case "info":
        // 주문번호만 있어도 된다.
        if(isset($orderno) == false || empty($orderno)){
            $param_err = true;
        }
        break; 
    case "cancel":
    case "cancel_force":
        // 주문번호와 티켓번호가 모두 있어야 한다.
        if(isset($orderno) == false || empty($orderno)){
            $param_err = true;
        }
        if(isset($couponno) == false || strlen($couponno)<5){
            $param_err = true;
        }
        // order_id 는 ordermts.id, 모두 숫자여야 함
        if(isset($order_id) == false || empty($order_id) || is_numeric($order_id) == false){
            $param_err = true;
        }
        break;
    default:
        $param_err = true;
        break;
}

if($param_err == true){
	header("HTTP/1.0 400 Bad Request");
    $res = array("result"=>"4000", "msg"=>"잘못된요청(파라미터)");
	echo $jsonres = json_encode($res, JSON_UNESCAPED_UNICODE);
	updateLogResult($jsonres);

    exit;
}

//yjlee
//exit;

// 상태 초기화
$state = "";
$info = array();

// url parameter 분기
switch($para){
    // 사용상태 조회 : 주문번호로 조회
    case 'info': 
        $resCheckCancel = checkCancel($orderno);
        // 취소가능 여부 체크 응답
        if (isset($resCheckCancel->status) == false){
            // 응답 없음
            $log = "    => 취소가능 여부 체크 응답 코드 없음";
            _logI($log);
    
            $state = "E";
            break;
        }else{ 
            $state = "S";
            $info = $resCheckCancel; 
        }
        break;
    case 'cancel': 
        // 상태조회를 다시한번 한다.
        $resCheckCancel = checkCancel($orderno);

        $can_cancel = "";
        // 시설과 통신 성공
        if($resCheckCancel->status == 0){
            foreach($resCheckCancel->data->pinList as $pin){
                // 같은 핀번호 상태 확인
                if($pin->pinNo == $couponno){
                    $can_cancel = $pin->pinStatCd;
            
                    $log = "    => 취소가능 여부 체크:[$pin->pinNo][$pin->pinStatCd][$pin->pinStatNm]";
                    _logI($log);
                    break;
                }
            }
        }
        
        if(empty($can_cancel)){
            $log = "    => 취소가능 여부 체크 실패:핀번호($couponno) 못찾음";
            _logI($log);
        }
//print_r($resCheckCancel);
//exit;
        // pinList에서 해당 티켓이 미사용일때만 취소한다.
        // N: 미사용, Y:사용건
        // 20240710 tony 사용건도 취소 요청하면 핀폐기 한다.(벨포레 
        if($can_cancel == "N" || $can_cancel == "Y"){
            // 취소 가능한 티켓일 경우 핀폐기 처리를 시작한다.
            $canceldata = array();
            $canceldata['orderno'] = $orderno;
            $canceldata['pinList'][] = $couponno;

            $resCancel = syncCancel($canceldata);
            // 취소가능 여부 체크 응답 코드 없음(체크결과 수신 안됨, 통신장애?)
            if (isset($resCancel->status) == false){
                // 응답 없음
                $log = "    => 취소가능 여부 체크 응답 코드 없음";
                _logI($log);
    
                $state = "E";
                break;
            }else{ 
                $state = "S";
                $info = $resCancel; 

                if(isset($resCancel->status) && ($resCancel->status == '0' || $resCancel->status == '192')){
                    $log = "    => 벨포레에 주문취소(핀폐기) 전송 성공";
                    _logI($log);
                    $jsonCancel = json_encode($resCancel, JSON_UNESCAPED_UNICODE);
                    $usql = "update cmsdb.belleforet_v2_extcoupon 
                            set 
                                SYNC_CANCEL_FLAG = 'Y',
                                SYNC_CANCEL_MSG = '".addslashes($jsonCancel)."' 
                            where 1 
                                AND orderno = '$orderno' 
                                order by id desc limit 1";

                    $cres = $conn_rds->query($usql);
                    $ccnt = $conn_rds->affected_rows;

                    $log = "    => rds.cmsdb.belleforet_v2_extcoupon [$couponno][$orderno] 갱신 결과 : [$cres] [$ccnt]";
                    _logI($log);

                    // 쿠폰 테이블에 상태 갱신
                    // 20240710 tony 사용건도 강제로 핀폐기가 되어야 하므로 기록한다.
                    $ucsql = "update ordermts_coupons set state='C', dt_cancel='".date("Y-m-d H:i:s")."' where order_id = '$order_id' and couponno = '$couponno' and state in ('N', 'Y')  limit 1";
                    $ucres = $conn_cms3->query($ucsql);
                    $uccnt = $conn_cms3->affected_rows;
                    $log = "    => ordermts_coupons [$couponno] 갱신 결과 : [$ucres] [$uccnt]";
                    _logI($log);
                }
            }
        }else{
            $state = "E";
            // 체크 결과를 응답에 넣어준다.
            $info = $resCheckCancel;
        }
        break;

    // 20241014 tony [CM] 벨포레 강제 취소 요청건 https://placem.atlassian.net/browse/P2CCA-693
    // 취소 가능여부를 확인하지 않고 그냥 강제 취소 전송함
    case 'cancel_force': 
        $canceldata = array();
        $canceldata['orderno'] = $orderno;
        $canceldata['pinList'][] = $couponno;

        // 취소(핀폐기 전송)
        $resCancel = syncCancel($canceldata);
        // 취소 응답 코드 없음(체크결과 수신 안됨, 통신장애?)
        if (isset($resCancel->status) == false){
            // 응답 없음
            $log = "    => 취소(핀폐기) 응답 코드 없음";
            _logI($log);
    
            $state = "E";
            break;
        }else{ 
            $state = "S";
            $info = $resCancel; 

            if(isset($resCancel->status) && ($resCancel->status == '0' || $resCancel->status == '192')){
                $log = "    => 벨포레에 주문취소(핀폐기) 전송 성공(code:{$resCancel->status})";
                _logI($log);
                $jsonCancel = json_encode($resCancel, JSON_UNESCAPED_UNICODE);
                $usql = "update cmsdb.belleforet_v2_extcoupon 
                        set 
                            SYNC_CANCEL_FLAG = 'Y',
                            SYNC_CANCEL_MSG = '".addslashes($jsonCancel)."' 
                        where 1 
                            AND orderno = '$orderno' 
                            order by id desc limit 1";

                $cres = $conn_rds->query($usql);
                $ccnt = $conn_rds->affected_rows;

                $log = "    => rds.cmsdb.belleforet_v2_extcoupon [$couponno][$orderno] 갱신 결과 : [$cres] [$ccnt]";
                _logI($log);

                // 쿠폰 테이블에 상태 갱신
                // 20240710 tony 사용건도 강제로 핀폐기가 되어야 하므로 기록한다.
                $ucsql = "update ordermts_coupons set state='C', dt_cancel='".date("Y-m-d H:i:s")."' where order_id = '$order_id' and couponno = '$couponno' and state in ('N', 'Y')  limit 1";
                $ucres = $conn_cms3->query($ucsql);
                $uccnt = $conn_cms3->affected_rows;
                $log = "    => ordermts_coupons [$couponno] 갱신 결과 : [$ucres] [$uccnt]";
                _logI($log);
            }
        }
        
        break; 

    default:
        header("HTTP/1.0 400 Bad Request");
        $res = array("result"=>"4000", "msg"=>"잘못된요청(파라미터)");
        echo $jsonres = json_encode($res, JSON_UNESCAPED_UNICODE);
        updateLogResult($jsonres);
        break;
}

switch($state){
    case 'S':
        $res = array("result"=>"1000", "msg"=>"성공", "detail" => $info);
        break;
    case 'Y':
        $res = array("result"=>"4003", "msg"=>"잘못된요청(기사용티켓)");
        break;
    case 'N':
        $res = array("result"=>"4004", "msg"=>"잘못된요청(미사용티켓)");
        break;
    case 'C':
        $res = array("result"=>"4002", "msg"=>"잘못된요청(환불티켓)");
        break;
    case 'E':
        $res = array("result"=>"9999", "msg"=>"알수없는오류", "detail" => $info);
        break;
    default:
        $res = array("result"=>"4001", "msg"=>"조회결과없음");

}
header("HTTP/1.0 200 OK");
echo $jsonres = json_encode($res, JSON_UNESCAPED_UNICODE);
updateLogResult($jsonres);

exit;

// 클라이언트 아아피
function get_ip(){

    if(empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
        $ip= $_SERVER["REMOTE_ADDR"].",";
    }else{
        $ip= $_SERVER["HTTP_X_FORWARDED_FOR"];
    }

    $res = explode(",", $ip);

    return trim($res[0]);
}

function insertLog($apiheader, string $apimethod, string $para, string $jsonreq){
    global $conn_rds;
    global $tranid;

    $apiheader = json_encode($apiheader);
    $qry = "INSERT cmsdb.extapi_log
                    SET apinm='$para/belleforet', tran_id= ? ,
                        ip='".get_ip()."', logdate= now(), apimethod= ?,
                        querystr= ?, header= ?,
                        body= ?";

    $stmt = $conn_rds->prepare($qry);
    $stmt->bind_param("sssss", $tranid, $apimethod, $para, $apiheader, $jsonreq);
    $stmt->execute();
}

// api 리턴 결과를 로그에 기록 - Jason 22.03.09
function updateLogResult($json){
    global $conn_rds;
    global $tranid;

    _logI("[$tranid] res:[$json]");

    $sql = "UPDATE cmsdb.extapi_log SET apiresult = '$json' WHERE tran_id = '$tranid'";
    $conn_rds->query($sql);
}

function checkCancel($orderNo){
    $curl = curl_init();

    $arHeader = array(
        'Content-type: application/json',
        //'Authorization: '.BELLEFORET_DEV_AUTH_KEY,
        'Authorization: '.BELLEFORET_AUTH_KEY,
        'Language: kr',
    );

    // 플레이스엠 주문번호
    $arParam = array(
        "orderNo" => $orderNo,
    );

    //$params = http_build_query($arParam, '', '&');
    $params = json_encode($arParam);
    $log = "취소가능여부 조회 params : [$params]";
    _logI($log);

    curl_setopt_array($curl, array(
    //CURLOPT_URL => BS_API_DEV_TICKETINFO,
    CURLOPT_URL => BS_API_TICKETINFO,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => $params,
    CURLOPT_HTTPHEADER => $arHeader,
    //CURLOPT_HTTPHEADER => array('Content-Type: application/x-www-form-urlencoded; charset=utf-8'),
    ));

    $response = curl_exec($curl);

    $log = "취소가능여부 조회 수신값 : [";
    $log .= print_r($response, true);
    $log .= "]";
    _logI($log);

    curl_close($curl);

    // json decode
    $response = json_decode($response);
    return $response;
}

// 벨포레에 판매(티켓) 취소(환불)
function syncCancel($canceldata){
    $curl = curl_init();
    $orderNo = $canceldata['orderno'];
    $pinList = $canceldata['pinList'];

    // 플레이스엠 주문번호
    $arParam = array();
    foreach ($pinList as $cp) {

        // 티켓 정보 
        $arParam['pinList'][] = array(
            // 주문번호
            'orderNo' => $orderNo,
            // 바코드번호
            'pinNo' => $cp
        );
    }

    $arHeader = array(
        'Content-type: application/json',
        //'Authorization: '.BELLEFORET_DEV_AUTH_KEY,
        'Authorization: '.BELLEFORET_AUTH_KEY,
        'Language: kr',
    );

    //$params = http_build_query($arParam, '', '&');
    $params = json_encode($arParam);
    $log = "벨포레 시설에 취소 전송 params : [$params]";
    _logI($log);
    //yjlee 
    //exit;

    curl_setopt_array($curl, array(
    //CURLOPT_URL => BS_API_DEV_DELETETICKET,
    CURLOPT_URL => BS_API_DELETETICKET,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => $params,
    CURLOPT_HTTPHEADER => $arHeader,
    //CURLOPT_HTTPHEADER => array('Content-Type: application/x-www-form-urlencoded; charset=utf-8'),
    ));

    $response = curl_exec($curl);
    $log = "벨포레 시설에 취소 전송 수신값 : [";
    $log .= print_r($response, true);
    $log .= "]";
    _logI($log);

    curl_close($curl);

    // json decode
    $response = json_decode($response);

    return $response;
}

/*
header("HTTP/1.0 401 Unauthorized");
header("HTTP/1.0 400 Bad Request");
header("HTTP/1.0 200 OK");
header("HTTP/1.0 500 Internal Server Error");

*/
