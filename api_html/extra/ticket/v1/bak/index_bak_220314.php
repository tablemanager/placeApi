<?php
/*
 *
 * @brief 야놀자/스마트인피니(서울랜드) 제공용 티켓 사용처리/복원(회수) 인터페이스
 * @doc https://placemticketapiv1.docs.apiary.io/
 * @author Jason
 * @date 22.03.08 ~ 03.14
 * 
 */

error_reporting(0);
require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
require_once ('/home/placedev/php_script/lib/placemlib.php');
require_once ('/home/sparo.cc/Library/Ordermts.php');
require_once ('/home/sparo.cc/seoulland_script/lib/SeoullandDB.php');
require_once ('/home/sparo.cc/yanolja_script/lib/YanoljaSpadb.php');

$yanoljaDB = new YanoljaSpadb();
$seoullandDB = new SeoullandDB();
$ordermts = new Ordermts();


header("Content-type:application/json");
$conn_rds->query("set names utf8");

$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

$jsonreq = trim(file_get_contents('php://input'));
$decodedreq = json_decode($jsonreq);
$couponno = $decodedreq->couponno;

list($microtime,$timestamp) = explode(' ',microtime()); 
$tranid = $timestamp . substr($microtime, 2, 3);
$logsql = "INSERT cmsdb.extapi_log_ticket
				SET apinm='ticket/v1',tran_id='$tranid',
					ip='".get_ip()."', logdate= now(), apimethod='$apimethod',
					querystr='".$para."', header='".json_encode($apiheader)."',
					body='".$jsonreq."'";
$conn_rds->query($logsql);


if(get_ip() =="106.254.252.100"){
    // var_dump($jsonreq);
	// echo $apimethod;
}


// 인증 정보 조회
$auth = $apiheader['Authorization'];
if(!$auth) $auth = $apiheader['authorization'];

$authqry = "SELECT * FROM spadb.extapi_config WHERE authkey = '$auth' limit 1";
$authres = $conn_cms3->query($authqry);
$authrow = $authres->fetch_object();

$aclmode = $authrow->aclmode;
$grmtId = $authrow->cp_grmtid;
if($aclmode == "IP"){
// ACL 확인
    if(!in_array(get_ip(),json_decode($authrow->accessip,false))){
        header("HTTP/1.0 401 Unauthorized");
        $res = array("Result"=>"4100","Msg"=>"인증오류(ip)");
        echo $jsonres = json_encode($res);
        updateLogResult($jsonres);
        exit;
    }
}

// 핀번호 기본 validation 체크
if($couponno == '' || strlen($couponno) < 6 || strlen($couponno) > 25){
	header("HTTP/1.0 400 Bad Request");
    $res = array("Result"=>"4000","Msg"=>"잘못된요청(파라미터)");
	echo $jsonres = json_encode($res);
	updateLogResult($jsonres);
    exit;
}

// url parameter 분기
switch($para){
    case 'use': // spadb.ordermts + 해당 시설 테이블 사용처리
		switch($grmtId){ // 업체 아이디 분기
            case '106': // 스마트인피니(서울랜드)
                $state = $seoullandDB->getState($couponno);
                if($state == 'N'){ // 미사용일 경우에만 사용처리
                    $updateResult = $seoullandDB->use($couponno);
                    if($updateResult){
                        usecouponno($couponno);
                        $orderno = $seoullandDB->findOrderno($couponno);
                        $ordermts->updateUsegu1($orderno);
                        $state = 'S';
                    } else {
                        $state = 'E';
                    }
                }
                break;
            case '3871': // 야놀자(시설)(에버랜드 등)
                $state = $yanoljaDB->getState($couponno);
                if($state == 'N'){ // 미사용일 경우에만 사용처리
                    $updateResult = $yanoljaDB->use($couponno);
                    if($updateResult){
                        usecouponno($couponno);
                        $orderno = $yanoljaDB->findOrderno($couponno);
                        $ordermts->updateUsegu1($orderno);
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


    case 'restore': //복원 처리
		switch($grmtId){ // 업체 아이디 분기
            case '106': // 스마트인피니(서울랜드)
                $state = $seoullandDB->getState($couponno);
                if($state == 'Y'){ // 사용일 경우에만 회수처리
                    $updateResult = $seoullandDB->restore($couponno);
                    if($updateResult){
                        $orderno = $seoullandDB->findOrderno($couponno);
                        $usedPins = $seoullandDB->findUsedPins($orderno);
                        if(empty($usedPins)){
                            $ordermts->updateUsegu0($orderno);
                        }
                        $state = 'S'
                    } else {
                        $state = 'E';
                    }
                }
                break;
            case '3871': // 야놀자(시설)(에버랜드 등)
                $state = $yanoljaDB->getState($couponno);
                if($state == 'Y'){ // 사용일 경우에만 회수처리
                    $updateResult = $yanoljaDB->restore($couponno);
                    if($updateResult){
                        $orderno = $yanoljaDB->findOrderno($couponno);
                        $usedPins = $yanoljaDB->findUsedPins($orderno);
                        if(empty($usedPins)){
                            $ordermts->updateUsegu0($orderno);
                        }
                        $state = 'S'
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
        $res = array("Result"=>"4000","Msg"=>"잘못된요청(파라미터)");
        echo $jsonres = json_encode($res);
        updateLogResult($jsonres);
}

switch($state){
    case 'S':
        $res = array("Result"=>"1000","Msg"=>"성공");
        break;
    case 'Y':
        $res = array("Result"=>"4003","Msg"=>"잘못된요청(기사용티켓)");
        break;
    case 'N':
        $res = array("Result"=>"4004","Msg"=>"잘못된요청(미사용티켓)");
        break;
    case 'C':
        $res = array("Result"=>"4002","Msg"=>"잘못된요청(환불티켓)");
        break;
    case 'E':
        $res = array("Result"=>"9999","Msg"=>"알수없는오류");
        break;
    default:
        $res = array("Result"=>"4001","Msg"=>"조회결과없음");

}
header("HTTP/1.0 200 OK");
echo $jsonres = json_encode($res);
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
