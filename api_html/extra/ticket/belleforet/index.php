<?php
/*
 *
 * @brief 벨포레(v2) 제공용 티켓 사용처리 인터페이스
 * @doc 
 * @author tony
 * @date 20240610
 *
 */

// 다른업체 사용시 아래 11-13 line 주석처리 후 사용하세요 (ila)
// $res = array("result"=>"4000", "msg"=>"잘못된요청(파라미터)");
// echo $jsonres = json_encode($res, JSON_UNESCAPED_UNICODE);
// exit;

// error_reporting(0);
require_once('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
require_once('/home/placedev/php_script/lib/placemlib.php');
// 잔디 알람 전송 라이브러리
require_once ('/home/sparo.cc/Library/noticelib.php');
// 파일로그 라이브러리(./txt 디렉토리 777 퍼미션 필요)
require_once ('/home/sparo.cc/Library/logutil.php');

// 벨포레 업체코드
define(__BELLEFORET_GRMT_ID_V1, '3859');
define(__BELLEFORET_GRMT_ID_V2, '4020');


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
$couponno = $decodedreq->couponno;

list($microtime, $timestamp) = explode(' ', microtime());
$tranid = $timestamp . substr($microtime, 2, 3);

_logI("[$tranid] --------------- START ---------------");
insertLog($apiheader, $apimethod, $para, $jsonreq);
// 로그 파일기록
//_logI("[$tranid] method:[$apimethod], param:[$para], header:[\n".print_r($apiheader, true)."]");
_logI("[$tranid] method:[$apimethod], param:[$para]");
_logI("[$tranid] body(json):[\n".print_r($jsonreq, true)."]");

$_is_devip = false;
if(get_ip() == "106.254.252.100"){
    // var_dump($jsonreq);
	// echo $apimethod;
    $_is_devip = true;
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
    if(!in_array(get_ip(), json_decode($authrow->accessip, false))){
        header("HTTP/1.0 401 Unauthorized");
        $res = array("result"=>"4100", "msg"=>"인증오류(ip)");
        echo $jsonres = json_encode($res, JSON_UNESCAPED_UNICODE);
        updateLogResult($jsonres);
        exit;
    }
}

// 핀번호 기본 validation 체크
$param_err = false;
switch($grmtId){
    // 벨포레(v2)
    case __BELLEFORET_GRMT_ID_V1:
    case __BELLEFORET_GRMT_ID_V2:
    if($couponno == '' || strlen($couponno) < 6 || strlen($couponno) > 36){
        $param_err = true;
    }
    break;
    default:
    if($couponno == '' || strlen($couponno) < 6 || strlen($couponno) > 30){
        $param_err = true;
    }
    break;
}
if($param_err == true){
	header("HTTP/1.0 400 Bad Request");
    $res = array("result"=>"4000", "msg"=>"잘못된요청(파라미터)");
	echo $jsonres = json_encode($res, JSON_UNESCAPED_UNICODE);
	updateLogResult($jsonres);

    exit;
}
//echo $grmtId;

//yjlee
//exit;

// url parameter 분기
switch($para){
    // spadb.ordermts + 해당 시설 테이블 사용처리
    case 'use': 
        // 업체 아이디 분기
		switch($grmtId){
            // [벨포레v2]플레이스엠 사용처리 / 복원 API 개발
            case __BELLEFORET_GRMT_ID_V1:
            case __BELLEFORET_GRMT_ID_V2:
                // 쿠폰정보 조회
                $scsql = "select * from spadb.ordermts_coupons where couponno='$couponno' limit 1";
                $scres = $conn_cms3->query($scsql);
                $scrow = $scres->fetch_object();
//echo "$scsql\n"; print_r($scrow); exit;

                // 쿠폰정보 조회성공
                if(!empty($scrow)){ 
                    // 주문정보 조회
                    //$sosql = "select * from spadb.ordermts where id='{$scrow->order_id}' and grmt_id='$grmtId' and usedate>='".date("Y-m-d")."'";
                    $sosql = "select * from spadb.ordermts where id='{$scrow->order_id}' and grmt_id in ('".__BELLEFORET_GRMT_ID_V1."', '".__BELLEFORET_GRMT_ID_V2."') and usedate>='".date("Y-m-d")."'";
                    $sores = $conn_cms3->query($sosql);
                    $sorow = $sores->fetch_object(); 
//echo "$sosql\n"; print_r($sorow); exit;

                    // 주문정보 조회 성공
                    if(!empty($sorow)){
                        // 상태 확인
                        if($sorow->state == '취소'){
                            $state = 'C';
                        // 주문정보에 사용상태
                        }elseif($sorow->usegu == '1'){
                            // 주문테이블에 이미 사용처리된 건
                            $state = 'Y';

                            // 쿠폰번호가 미사용이면 다건구매건이다.
                            if($scrow->state == "N"){
                                // 쿠폰별 사용처리
                                //yjlee
                                $rtn_use = usecouponnov2($couponno);
                                // echo $rtn_use;
                                if ($rtn_use == 'Y'){
                                    // 사용처리 성공
                                    $state = 'S';
                                }
                            }
                        // 주문정보에 미사용상태
                        }elseif($sorow->usegu == '2'){
                            // echo date("Y-m-d H:i:s");
                            //yjlee
                            $rtn_use = usecouponnov2($couponno);
                            // echo $rtn_use;
                            if ($rtn_use == 'Y'){
                                // 사용처리 성공
                                $state = 'S'; 
                            }else{
                                $state = $rtn_use;
                            }
                        } 
                    }else{
                        // 조회 실패
                        $state = '';
                    }
                }else{
                    // 소셜3사:쿠팡(150), 티몬(154), 위메프(142) 는 통합쿠폰번호에 채널 핀번호가 들어갈 수 있으므로
                    // 주문테이블에서 다시한번 검사한다.(속도 느리므로 채널 제한을 건다)
                    // 20221109 tony KKDAY(2901) 추가 : KKDAY 도 couponno 에 json으로 들어가고 barcode_no에는 가짜 핀번호가 들어간다.
                    //$sosql = "select * from spadb.ordermts where couponno like '%$couponno%' and ch_id in ('150', '154', '142', '2901') and grmt_id='$grmtId' and usedate>='".date("Y-m-d")."' limit 1";
                    $sosql = "select * from spadb.ordermts where couponno like '%$couponno%' and ch_id in ('150', '154', '142', '2901') and grmt_id in ('".__BELLEFORET_GRMT_ID_V1."', '".__BELLEFORET_GRMT_ID_V2."') and usedate>='".date("Y-m-d")."' limit 1";
//echo $sosql;exit;
                    $sores = $conn_cms3->query($sosql);
                    $sorow = $sores->fetch_object();

                    if(!empty($sorow)){
                        if($sorow->state == '취소'){
                            $state = 'C';
                        }elseif($sorow->usegu == '1'){
                            // 기사용
                            $state = 'Y';
                            // 소셜3사에서 2개이상 주문건이 없어서 일단 패스
                        }elseif($sorow->usegu == '2'){
                            // echo date("Y-m-d H:i:s");
                            // 시설 쿠폰번호로 사용처리 한번
                            //yjlee
                            $rtn_use = usecouponnov2($couponno);
                            // 채널 쿠폰번호로 사용처리 한번 더
                            $tmp_couponno = explode(";", $sorow->barcode_no);
                            if(isset($tmp_couponno[0]) && !empty($tmp_couponno[0])){
                                if($tmp_couponno[0] != $couponno){
                                    //yjlee
                                    $rtn_use = usecouponnov2($tmp_couponno[0]);
                                }
                            } 

                            // echo $rtn_use;
                            if ($rtn_use == 'Y'){
                                $state = 'S';   // 사용처리 성공
                            }else{
                                $state = $rtn_use;
                            }
                        }
                    }else{
                        // 조회 실패
                        $state = '';
                    }
                } 
                break;
                 
            default:
            // 조회 실패
            $state = '';
        }
        break;

    // 복원 처리, 복원처리 날짜는 syncmsg에 입력 
    // 기능은 없으나 원본 소스 보관
/*
    case 'restore': 
        // 업체 아이디 분기
		switch($grmtId){ 
            // 20230712 tony https://placem.atlassian.net/browse/BD2201-12  [이브릿지]플레이스 엠 사용처리 / 복원 API 작업 요청으로 인한 문의 건
            // 복원 기능 구현하기로 협의되어 작업함
            case '3909':
                $scsql = "select * from spadb.ordermts_coupons where couponno='$couponno' limit 1";
                $scres = $conn_cms3->query($scsql);
                $scrow = $scres->fetch_object();
                // 조회성공
                if(!empty($scrow)){
                    //$sosql = "select * from spadb.ordermts where id='{$scrow->order_id}' and grmt_id='3959' and usedate>='".date("Y-m-d")."'";
                    $sosql = "select * from spadb.ordermts where id='{$scrow->order_id}' and grmt_id='$grmtId' and usedate>='".date("Y-m-d")."'";
                    $sores = $conn_cms3->query($sosql);
                    $sorow = $sores->fetch_object();

                    if(!empty($sorow)){
                        switch($scrow->state){
                            case "Y":
                                // 사용된 티켓 복원
                                $bigomsg = "/".date("Y-m-d H:i:s")." 시설에서 복원API 호출되어 복원함[$couponno]";
                                // 복원기록만 저장
                                $uosql = "update spadb.ordermts set bigo=concat(ifnull(bigo, ''), '$bigomsg') where id={$scrow->order_id} limit 1";
                                $uores = $conn_cms3->query($uosql);
                                //echo "갱신 : [$conn_cms3->affected_rows]<br>\n";
         
                                $uesql = "update spadb.ordermts_coupons set state='N' where order_id='{$sorow->id}' and couponno='$couponno' limit 1";  
                                $ueres = $conn_cms3->query($uesql); 
                                //echo "갱신 : [$conn_cms3->affected_rows]<br>\n"; 
         
                                $state = 'S';
                                break;
                            case "N":
                                // 미사용 티켓
                                $state = 'N';
                                break;
                            default;
                                // 알수없는 상태??? 
                                break;
                        }

                        // 주문 사용처리 된 경우에만 변경
                        if($sorow->usegu == '1'){
                            $sc2sql = "select * from spadb.ordermts_coupons where order_id='{$sorow->id}'";
                            $sc2res = $conn_cms3->query($sc2sql);
                            // 하나라도 조회 되야 true
                            $cpnstate = ($conn_rds->affected_rows > 0)?true:false;
                            // 모두 미사용이어야 주문건을 미사용으로 바꾼다.
                            while($sc2row = $sc2res->fetch_object()){
                                if($sc2row->state != "N"){
                                    $cpnstate = false;
                                }
                            }

                            if($cpnstate){
                                // 모든핀이 다 미사용일 경우에만 주문건의 사용상태를 미사용으로 변경함
                                $uosql = "update spadb.ordermts set usegu='2' where id={$scrow->order_id} limit 1";
                                $uores = $conn_cms3->query($uosql);
                                //echo "갱신 : [$conn_cms3->affected_rows]<br>\n";
                            }
                        } 
                    }else{
                        // 티켓 못 찾음
                        $state = '';
                    }
                }else{
                    // 소셜3사:쿠팡(150), 티몬(154), 위메프(142) 는 통합쿠폰번호에 채널 핀번호가 들어갈 수 있으므로
                    // 주문테이블에서 다시한번 검사한다.(속도 느리므로 채널 제한을 건다)
                    $sosql = "select * from spadb.ordermts where couponno like '%$couponno%' and ch_id in ('150', '154', '142') and grmt_id='$grmtId' and usedate>='".date("Y-m-d")."' limit 1";
//echo $sosql;exit;
                    $sores = $conn_cms3->query($sosql);
                    $sorow = $sores->fetch_object();

                    if(!empty($sorow)){
                        // 사용처리 된 경우에만 변경
                        // 20230720 tony 현재 소셜3사는 수량(man1)이 1로만 처리되고 있음
                        if($sorow->usegu == '1'){
                            $bigomsg = "/".date("Y-m-d H:i:s")." 시설에서 복원API 호출되어 복원함[$couponno]";
                            $uosql = "update spadb.ordermts set usegu='2', bigo=concat(ifnull(bigo, ''), '$bigomsg') where id={$sorow->id} limit 1";
                            $uores = $conn_cms3->query($uosql);
                            //echo "갱신 : [$conn_cms3->affected_rows]<br>\n";

                            $uesql = "update spadb.ordermts_coupons set state='N' where order_id='{$sorow->id}' and couponno='$couponno' limit 1"; 
                            $ueres = $conn_cms3->query($uesql);
                            //echo "갱신 : [$conn_cms3->affected_rows]<br>\n";

//                            // 채널 쿠폰번호로 복원처리 : 주석처리
//                            $tmp_couponno = explode(";", $sorow->barcode_no);
//                            if(isset($tmp_couponno[0]) && !empty($tmp_couponno[0])){
//                                if($tmp_couponno[0] != $couponno){
//                                    $uesql = "update spadb.ordermts_coupons set state='N' where order_id='{$sorow->id}' and couponno='$tmp_couponno[0]' limit 1"; 
//                                    $ueres = $conn_cms3->query($uesql);
//                                    //echo "갱신 : [$conn_cms3->affected_rows]<br>\n";
//                                }
//                            }

                            $state = 'S';
                        }elseif($sorow->usegu == '2'){
                            // 미사용 티켓
                            $state = 'N';
                        }
                    }else{
                        // 티켓 못 찾음
                        $state = '';
                    }
                } 
                break;
            default:
            // 티켓 못 찾음
            $state = '';
        }
        break;
*/

    default:
        header("HTTP/1.0 400 Bad Request");
        $res = array("result"=>"4000", "msg"=>"잘못된요청(파라미터)");
        echo $jsonres = json_encode($res, JSON_UNESCAPED_UNICODE);
        updateLogResult($jsonres);
}

switch($state){
    case 'S':
        $res = array("result"=>"1000", "msg"=>"성공");
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
        $res = array("result"=>"9999", "msg"=>"알수없는오류");
        break;
    default:
        $res = array("result"=>"4001", "msg"=>"조회결과없음");

}
header("HTTP/1.0 200 OK");
echo $jsonres = json_encode($res, JSON_UNESCAPED_UNICODE);
updateLogResult($jsonres);

exit;

/*
// 20240612 tony 구버전이므로 사용 안하지만 일단 백업
// 2번 서버에 있는 구버전
function usecouponno($no){

	$curl = curl_init();
    $url = "http://115.68.42.2:3040/use/".$no;
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1" );
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 5);

    $res_str = curl_exec($curl);
    $data = explode(";", $res_str);
    $info = curl_getinfo($curl);
    curl_close($curl);
    if($data[0] == "E" && isset($data[1]) == false){
        return "E";
    }

    if($data[1] == "0"){
        return "N";
    }else{
        return "Y";
    }
}
*/

// aws 3번 서버에 있는 사용처리 nodejs
// 신버전임
function usecouponnov2($no){
    // 쿠폰 사용처리
    $curl = curl_init();
    $url = "http://172.31.30.15:3040/use/".$no;
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1" );
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 5);

    $res_str = curl_exec($curl);
    //echo $res_str;
    $data = explode(";", $res_str);
    $info = curl_getinfo($curl);
    curl_close($curl);

    if($data[0] == "E" && isset($data[1]) == false){
        return "E";
    }
/*
    if($data[1] == "0"){
        return "N";
    }else{
        return "Y";
    }
*/
    // 쿠폰번호가 리턴됨
    if ($no == $data[0]){
        return "Y";
    }else{
        return "N";
    }
}

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
    $qry = "INSERT cmsdb.extapi_log_ticket
                    SET apinm='ticket/belleforet', tran_id= ? ,
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

    $sql = "UPDATE cmsdb.extapi_log_ticket SET apiresult = '$json' WHERE tran_id = '$tranid'";
    $conn_rds->query($sql);
    
    
    _logI("[$tranid] ".print_r($json, true));
}

/*
header("HTTP/1.0 401 Unauthorized");
header("HTTP/1.0 400 Bad Request");
header("HTTP/1.0 200 OK");
header("HTTP/1.0 500 Internal Server Error");

*/
