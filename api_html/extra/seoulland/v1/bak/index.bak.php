<?php
/**
 *
 * @brief 스마트인피니(서울랜드) 제공용 티켓 사용처리/복원(회수) 인터페이스
 *
 * @author Jason
 * @date 22.03.24
 * 
 */

error_reporting(0);
require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
require_once ('/home/placedev/php_script/lib/placemlib.php');
require_once ('/home/sparo.cc/seoulland_script/lib/SeoullandDB.php');

$seoullandDB = new SeoullandDB();

header("Content-type:application/json");
$conn_rds->query("set names utf8");

$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

$jsonreq = trim(file_get_contents('php://input'));
$decodedreq = json_decode($jsonreq);
$order_div = $decodedreq->order_div;
$ticket_code = $decodedreq->ticket_code;
$pin_seoul = $decodedreq->coupon_no;


list($microtime,$timestamp) = explode(' ',microtime());
$tranid = $timestamp . substr($microtime, 2, 3);
insertLog($apiheader,$apimethod,$para,$jsonreq);


if(get_ip() =="106.254.252.100"){
    // var_dump($jsonreq);
	// echo $apimethod;
}


// 인증 정보 조회
$auth = $apiheader['Authorization'];
if(!$auth) $auth = $apiheader['authorization'];

$authqry = "SELECT * FROM spadb.extapi_config WHERE authkey = ? limit 1";
$authstmt = $conn_cms3->prepare($authqry);
$authstmt->bind_param("s", $auth);
$authstmt->execute();
$authres = $authstmt->get_result();
$authrow = $authres->fetch_object();
// var_dump($authrow);
$aclmode = $authrow->aclmode;
$grmtId = $authrow->cp_grmtid;

if($aclmode == "IP"){
// ACL 확인
    if(!in_array(get_ip(),json_decode($authrow->accessip,false))){
        header("HTTP/1.0 401 Unauthorized");
        $res = array("order_no"=>"","return_div"=>"E","return_msg"=>"인증오류(ip)");
        echo $jsonres = json_encode($res,JSON_UNESCAPED_UNICODE);
        updateLogResult($jsonres);
        exit;
    }
}

// 핀번호 기본 validation 체크
if($ticket_code == '' || strlen($ticket_code) < 6 || strlen($ticket_code) > 25){
	header("HTTP/1.0 400 Bad Request");
    $res = array("order_no"=>"","return_div"=>"E","return_msg"=>"잘못된요청(파라미터){$ticket_code}");
	echo $jsonres = json_encode($res,JSON_UNESCAPED_UNICODE);
	updateLogResult($jsonres);
    exit;
}

$orderno = $seoullandDB->getOrderno($pin_seoul);

$order = $seoullandDB->getStateByOrderno($orderno);

switch($order_div){
    case '51': // spadb.ordermts + 해당 시설 테이블 사용처리
        $state = $seoullandDB->getState($pin_seoul);
        // 주문정보에서 미사용일 경우에만 사용처리하며, 서울랜드 전용 테이블에 사용처리
        if($state == 'N' && $order->state == '예약완료' && $order->usegu == '2'){
            // 쿠폰번호 서울랜드 전용 테이블 사용처리
            $updateResult = $seoullandDB->use($ticket_code);
            if($updateResult){
                // 쿠폰번호 사용처리
                usecouponno($ticket_code);
                // spadb.ordermts 사용처리 플레그 갱신
                $seoullandDB->updateUsegu1($orderno);
                $state = 'S';
            } else {
                $state = 'E';
            }
        }
        // 주문정보에서 사용일 경우에는 주문정보에서만 사용처리 된 것이므로, 서울랜드 전용 테이블에서 사용처리
        elseif($state == 'N' && $order->state == '예약완료' && $order->usegu == '1'){ 
        {
            // 쿠폰번호 서울랜드 전용 테이블 사용처리
            $updateResult = $seoullandDB->use($ticket_code);
            if($updateResult){
                // 쿠폰번호 사용처리
                usecouponno($ticket_code);
                $state = 'S';
            } else {
                $state = 'E';
            } 
        }

        if($order->state == '취소'||$order->state == '취소접수'){
            $state = 'C';
        }
        break;
    case '50': //복원 처리
        $state = $seoullandDB->getState($pin_seoul);
        if($state == 'Y' && $order->state == '예약완료' && $order->usegu == '1'){ // 사용일 경우에만 회수처리
            $updateResult = $seoullandDB->restore($ticket_code);
            if($updateResult){
                $usedPins = $seoullandDB->getUsedPins($orderno);
                if(empty($usedPins)){
                    $seoullandDB->updateUsegu2($orderno);
                }
                $state = 'S';
            } else {
                $state = 'E';
            }
        }
        if($order->state == '취소'||$order->state == '취소접수'){
            $state = 'C';
        }
        break;
    default:
        header("HTTP/1.0 400 Bad Request");
        $res = array("order_no"=>"","return_div"=>"E","return_msg"=>"잘못된요청(파라미터){$order_div}");
        echo $jsonres = json_encode($res,JSON_UNESCAPED_UNICODE);
        updateLogResult($jsonres);
}

switch($state){
    case 'S':
        $res = array("order_no"=>"{$orderno}","return_div"=>"{$state}","return_msg"=>"성공");
        break;
    case 'Y':
        $res = array("order_no"=>"{$orderno}","return_div"=>"{$state}","return_msg"=>"잘못된요청(기사용티켓)");
        break;
    case 'N':
        $res = array("order_no"=>"{$orderno}","return_div"=>"{$state}","return_msg"=>"잘못된요청(미사용티켓)");
        break;
    case 'C':
        $res = array("order_no"=>"{$orderno}","return_div"=>"{$state}","return_msg"=>"잘못된요청(환불티켓)");
        break;
    case 'E':
        $res = array("order_no"=>"{$orderno}","return_div"=>"{$state}","return_msg"=>"알수없는오류");
        break;
    default:
        $res = array("order_no"=>"{$orderno}","return_div"=>"E","return_msg"=>"조회결과없음");

}
header("HTTP/1.0 200 OK");
echo $jsonres = json_encode($res,JSON_UNESCAPED_UNICODE);
updateLogResult($jsonres);

exit;



function usecouponno($no){

	$curl = curl_init();
    $url = "http://115.68.42.2:3040/use/".$no;
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_USERAGENT,"Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1" );
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 5);

    $data = explode(";",curl_exec($curl));
    $info = curl_getinfo($curl);
    curl_close($curl);
    if($data[1] == "0"){
        return "N";
    }else{
        return "Y";
    }
}


// 클라이언트 아아피
function get_ip(){

	if(empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
		 $ip= $_SERVER["REMOTE_ADDR"].",";
	}else{
		$ip= $_SERVER["HTTP_X_FORWARDED_FOR"];
	}

	$res = explode(",",$ip);

    return trim($res[0]);
}

function insertLog($apiheader, string $apimethod, string $para, string $jsonreq){
    global $conn_rds;
    global $tranid;

    $apiheader = json_encode($apiheader);
    $qry = "INSERT cmsdb.extapi_log_ticket
                    SET apinm='seoulland/v1',tran_id= ? ,
                        ip='".get_ip()."', logdate= now(), apimethod= ?,
                        querystr= ?, header= ?,
                        body= ?";

    $stmt = $conn_rds->prepare($qry);
    $stmt->bind_param("sssss", $tranid,$apimethod,$para,$apiheader,$jsonreq);
    $stmt->execute();
}

// api 리턴 결과를 로그에 기록 - Jason 22.03.09
function updateLogResult($json){
	global $conn_rds;
    global $tranid;

    $sql = "UPDATE cmsdb.extapi_log_ticket SET apiresult = '$json' WHERE tran_id = '$tranid'";
    $conn_rds->query($sql);
}


// 날짜포맷 검증 함수
function isValidDate($date, $format= 'Y-m-d'){
    return $date == date($format, strtotime($date));
}




/*
header("HTTP/1.0 401 Unauthorized");
header("HTTP/1.0 400 Bad Request");
header("HTTP/1.0 200 OK");
header("HTTP/1.0 500 Internal Server Error");

*/

