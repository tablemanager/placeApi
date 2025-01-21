<?php
/*
 *
 * @brief 야놀자 스펙에 따른 티켓 사용처리/복원(회수) 인터페이스
 * @doc https://placem.atlassian.net/wiki/spaces/QI2201/pages/85426896
 * @author ila
 * @date 22.05.24 ~
 *
 */

// error_reporting(0);
require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
require_once ('/home/placedev/php_script/lib/placemlib.php');
require_once ('/home/sparo.cc/yanolja_script/lib/YanoljaDB.php');


/*
==== 적용 url example =======
https://{채널대행사IP}:{채널대행사PORT}/channel-agency/v1/orders/use
https://gateway.sparo.cc/yanoljaticket/channel-agency/v1/orders/use
*/


$yanoljaDB = new YanoljaDB();

header("Content-type:application/json");
$conn_rds->query("set names utf8");

$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

$jsonreq = trim(file_get_contents('php://input'));
$decodedreq = json_decode($jsonreq);
$body = $decodedreq->body;
$couponno = $body->partnerOrderChannelPin;
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
        $result = array("code"=>"400000","message"=>"IP 인증 오류");
        $results = array(
            'body' => $result,
            'contentType'=>"ErrorResult"
        );
        echo $jsonres = json_encode($results,JSON_UNESCAPED_UNICODE);
        updateLogResult($jsonres);
        exit;
    }
}
// API키 확인
if(!$authrow->authkey){

    header("HTTP/1.0 401 Unauthorized");
    $result = array("code"=>"400000","message"=>"APIkey 인증 오류");
    $results = array(
        'body' => $result,
        'contentType'=>"ErrorResult"
    );
    echo json_encode($results);
    updateLogResult($jsonres);
    exit;

}

// 핀번호 기본 validation 체크
if($couponno == '' || strlen($couponno) < 6 || strlen($couponno) > 30){
	  header("HTTP/1.0 400 Bad Request");
    $result = array("code"=>"400000","message"=>"잘못된 요청(파라미터)");
    $results = array(
        'body' => $result,
        'contentType'=>"ErrorResult"
    );
	  echo $jsonres = json_encode($results,JSON_UNESCAPED_UNICODE);
	  updateLogResult($jsonres);
    exit;
}
// echo $grmtId;

// url parameter 분기
if($para)

switch($para){
    case 'use': // spadb.ordermts + 해당 시설 테이블 사용처리
		switch($grmtId){ // 업체 아이디 분기
            case '3871': // 야놀자(시설)(에버랜드 등)
                $state = $yanoljaDB->getState($couponno);
                if($state == 'N'){ // 미사용일 경우에만 사용처리
                    $updateResult = $yanoljaDB->use($couponno);
                    if($updateResult){
                        usecouponno($couponno);
                        $orderno = $yanoljaDB->getOrderno($couponno);
                        $yanoljaDB->updateCouponStateY($couponno);
                        $yanoljaDB->updateUsegu1($orderno);
                        $state = 'S';
                    } else {
                        $state = 'E';
                    }
                }
                break;
            default:
            $state = '';
        }
        break;


    case 'revert': //복원 처리, 복원처리 날짜는 syncmsg에 입력
		switch($grmtId){ // 업체 아이디 분기
            case '3871': // 야놀자(시설)(에버랜드 등)
                $state = $yanoljaDB->getState($couponno);
                if($state == 'Y'){ // 사용일 경우에만 회수처리
                    $updateResult = $yanoljaDB->restore($couponno);
                    if($updateResult){
                        $orderno = $yanoljaDB->getOrderno($couponno);
                        $usedPins = $yanoljaDB->getUsedPins($orderno);
                        $yanoljaDB->updateCouponStateN($couponno);
                        if(empty($usedPins)){
                            $yanoljaDB->updateUsegu2($orderno);
                        }
                        $state = 'S';
                    } else {
                        $state = 'E';
                    }
                }
                break;
            default:
            $state = '';
        }
        break;


    default:
        header("HTTP/1.0 400 Bad Request");
        $result = array("code"=>"400006","message"=>"파라미터 오류 : ".$para);
        $results = array(
            'body' => $result,
            'contentType'=>"ErrorResult"
        );
        echo $jsonres = json_encode($results,JSON_UNESCAPED_UNICODE);
        updateLogResult($jsonres);
        exit;
}

switch($state){
    case 'S':
        //$res = array("body"=>null,"contentType"=>null); //성공
        $results = array("body"=>null,"contentType"=>null);
        break;

    case 'Y':
        $result = array("code"=>"400000","message"=>"잘못된요청(기사용티켓)");
        $results = array(
            'body' => $result,
            'contentType'=>"ErrorResult"
        );
        break;
    case 'N':
        $result = array("code"=>"400000","message"=>"잘못된요청(미사용티켓)");
        $results = array(
            'body' => $result,
            'contentType'=>"ErrorResult"
        );
        break;
    case 'C':
        $result = array("code"=>"400000","message"=>"잘못된요청(환불티켓)");
        $results = array(
            'body' => $result,
            'contentType'=>"ErrorResult"
        );
        break;
    case 'E':
        $result = array("code"=>"400006","message"=>"주문정보를 찾을 수 없습니다");
        $results = array(
            'body' => $result,
            'contentType'=>"ErrorResult"
        );
        break;
    default:
        $result = array("code"=>"400006","message"=>"주문정보를 찾을 수 없습니다");
        $results = array(
            'body' => $result,
            'contentType'=>"ErrorResult"
        );
}
header("HTTP/1.0 200 OK");
echo $jsonres = json_encode($results,JSON_UNESCAPED_UNICODE);
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
                    SET apinm='야놀자사용복원',tran_id= ? ,
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
