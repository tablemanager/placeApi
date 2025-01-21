<?php
/*
 *
 * @brief 야놀자 제공용 티켓 사용처리/복원(회수) 인터페이스 -> 야놀자 제공 안함 (요청스펙으로 인터페이스 재개 - 22.05.24 ila)
 * @doc https://placemticketapiv1.docs.apiary.io/
 * @author Jason
 * @date 22.03.08 ~ 03.24
 *
 */
 // 다른업체 사용시 아래 11-13 line 주석처리 후 사용하세요 (ila)
// $res = array("result"=>"4000","msg"=>"잘못된요청(파라미터)");
// echo $jsonres = json_encode($res,JSON_UNESCAPED_UNICODE);
// exit;

// error_reporting(0);
require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
require_once ('/home/placedev/php_script/lib/placemlib.php');
require_once ('/home/sparo.cc/yanolja_script/lib/YanoljaDB.php');

$yanoljaDB = new YanoljaDB();

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
        $res = array("result"=>"4100","msg"=>"인증오류(ip)");
        echo $jsonres = json_encode($res,JSON_UNESCAPED_UNICODE);
        updateLogResult($jsonres);
        exit;
    }
}

// 핀번호 기본 validation 체크
$param_err = false;
switch($grmtId){
    case '3959':
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
    $res = array("result"=>"4000","msg"=>"잘못된요청(파라미터)");
	echo $jsonres = json_encode($res,JSON_UNESCAPED_UNICODE);
	updateLogResult($jsonres);
    exit;
}
// echo $grmtId;

// url parameter 분기
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
            // 20230419 tony https://placem.atlassian.net/browse/RL2201-1
            // [신규개발] 강릉 또 강릉 API 시설연동 개발 
            case '3959': // 강릉또강릉
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
                        if($sorow->state == '취소'){
                            $state = 'C';
                        }elseif($sorow->usegu == '1'){
                            $state = 'Y';   // 기사용
                        }elseif($sorow->usegu == '2'){
//                            echo date("Y-m-d H:i:s");
                            $rtn_use = usecouponnov2($couponno);
// echo $rtn_use;
                            if ($rtn_use == 'Y'){
                                $state = 'S';   // 사용처리 성공
                            }else{
                                $state = $rtn_use;
                            }
                        } 
                    }else{
                        $state = '';
                    }
                }else{
                    $state = '';
                } 
                break;
            // 20230615 tony https://placem.atlassian.net/browse/BD2201-12  [이브릿지]플레이스 엠 사용처리 / 복원 API 작업 요청으로 인한 문의 건
            // 신규개발 이브릿지 사용처리 API 시설연동 개발
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
                        if($sorow->state == '취소'){
                            $state = 'C';
                        }elseif($sorow->usegu == '1'){
                            $state = 'Y';   // 기사용
                            $sebgsql = "select * from ebridge_extcoupon where orderno='{$sorow->orderno}' and coupon_num='$couponno' limit 1";
                            $sebgres = $conn_rds->query($sebgsql);
                            $sebgrow = $sebgres->fetch_object();
                            if(!empty($sebgrow)){
                                // 쿠폰번호가 미사용이면 다건구매건이다.
                                if($sebgrow->state == "N"){
                                    // 사용처리 한다.
                                    $rtn_use = usecouponnov2($couponno);
// echo $rtn_use;
                                    if ($rtn_use == 'Y'){
                                        $state = 'S';   // 사용처리 성공
               
                                        // 이브릿지 쿠폰 테이블 사용처리 갱신 
                                        $usesql = "update ebridge_extcoupon set state='Y', useDt=NOW() where orderno='{$sorow->orderno}' and coupon_num='$couponno' and state='N' limit 1";
                                        $useres = $conn_rds->query($usesql);
 
                                    }
                                }
                            } 
                        }elseif($sorow->usegu == '2'){
//                            echo date("Y-m-d H:i:s");
                            $rtn_use = usecouponnov2($couponno);
// echo $rtn_use;
                            if ($rtn_use == 'Y'){
                                $state = 'S';   // 사용처리 성공
       
                                // 이브릿지 쿠폰 테이블 사용처리 갱신 
                                $usesql = "update ebridge_extcoupon set state='Y', useDt=NOW() where orderno='{$sorow->orderno}' and coupon_num='$couponno' and state='N' limit 1";
                                $useres = $conn_rds->query($usesql);
                                //echo "갱신 : [$conn_rds->affected_rows]\n";
                            }else{
                                $state = $rtn_use;
                            }
                        } 
                    }else{
                        $state = '';
                    }
                }else{
                    // 소셜3사:쿠팡(150), 티몬(154), 위메프(142) 는 통합쿠폰번호에 채널 핀번호가 들어갈 수 있으므로
                    // 주문테이블에서 다시한번 검사한다.(속도 느리므로 채널 제한을 건다)
                    // 20221109 tony KKDAY(2901) 추가 : KKDAY 도 couponno 에 json으로 들어가고 barcode_no에는 가짜 핀번호가 들어간다.
                    $sosql = "select * from spadb.ordermts where couponno like '%$couponno%' and ch_id in ('150', '154', '142', '2901') and grmt_id='$grmtId' and usedate>='".date("Y-m-d")."' limit 1";
//echo $sosql;exit;
                    $sores = $conn_cms3->query($sosql);
                    $sorow = $sores->fetch_object();

                    if(!empty($sorow)){
                        if($sorow->state == '취소'){
                            $state = 'C';
                        }elseif($sorow->usegu == '1'){
                            $state = 'Y';   // 기사용
                            // 소셜3사에서 2개이상 주문건이 없어서 일단 패스
                        }elseif($sorow->usegu == '2'){
//                          echo date("Y-m-d H:i:s");
                            // 시설 쿠폰번호로 사용처리 한번
                            $rtn_use = usecouponnov2($couponno);
                            // 채널 쿠폰번호로 사용처리 한번 더
                            $tmp_couponno = explode(";", $sorow->barcode_no);
                            if(isset($tmp_couponno[0]) && !empty($tmp_couponno[0])){
                                if($tmp_couponno[0] != $couponno){
                                    $rtn_use = usecouponnov2($tmp_couponno[0]);
                                }
                            } 
                            //$rtn_use = usecouponnov2($couponno);
// echo $rtn_use;
                            if ($rtn_use == 'Y'){
                                $state = 'S';   // 사용처리 성공

                                // 이브릿지 쿠폰 테이블 사용처리 갱신 
                                $usesql = "update ebridge_extcoupon set state='Y', useDt=NOW() where orderno='{$sorow->orderno}' and coupon_num='$couponno' and state='N' limit 1";
                                $useres = $conn_rds->query($usesql);
                                //echo "갱신 : [$conn_rds->affected_rows]\n";
                            }else{
                                $state = $rtn_use;
                            }
                        }
                    }else{
                        $state = '';
                    }
                } 
                break;
                 
            default:
            $state = '';
        }
        break;


    case 'restore': //복원 처리, 복원처리 날짜는 syncmsg에 입력
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
                        $sebgsql = "select * from ebridge_extcoupon where orderno='{$sorow->orderno}' and coupon_num='$couponno' limit 1";
                        $sebgres = $conn_rds->query($sebgsql);
                        $sebgrow = $sebgres->fetch_object();
                        if(!empty($sebgrow)){
                            switch($sebgrow->state){
                                case "Y":
                                    // 사용된 티켓 복원
                                    $bigomsg = "/".date("Y-m-d H:i:s")." 시설에서 복원API 호출되어 복원함[$couponno]";
                                    // 복원기록만 저장
                                    $uosql = "update spadb.ordermts set bigo=concat(ifnull(bigo,''), '$bigomsg') where id={$scrow->order_id} limit 1";
                                    $uores = $conn_cms3->query($uosql);
                                    //echo "갱신 : [$conn_cms3->affected_rows]<br>\n";
         
                                    $uesql = "update spadb.ordermts_coupons set state='N' where order_id='{$sorow->id}' and couponno='$couponno' limit 1";  
                                    $ueres = $conn_cms3->query($uesql); 
                                    //echo "갱신 : [$conn_cms3->affected_rows]<br>\n"; 
         
                                    $uesql = "update cmsdb.ebridge_extcoupon set state='N' where orderno='{$sorow->orderno}' and coupon_num='$couponno' limit 1"; 
                                    $ueres = $conn_rds->query($uesql); 
                                    //echo "갱신 : [$conn_rds->affected_rows]<br>\n"; 
                                    
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
                        }else{
                            // 티켓 못 찾음
                            $state = ''; 
                        }

                        // 주문 사용처리 된 경우에만 변경
                        if($sorow->usegu == '1'){
                            $sebgsql = "select * from ebridge_extcoupon where orderno='{$sorow->orderno}'";
                            $sebgres = $conn_rds->query($sebgsql);
                            // 하나라도 조회 되야 true
                            $ebgstate = ($conn_rds->affected_rows > 0)?true:false;
                            // 모두 미사용이어야 주문건을 미사용으로 바꾼다.
                            while($sebgrow = $sebgres->fetch_object()){
                                if($sebgrow->state != "N"){
                                    $ebgstate = false;
                                }
                            }

                            if($ebgstate){
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
                            $uosql = "update spadb.ordermts set usegu='2', bigo=concat(ifnull(bigo,''), '$bigomsg') where id={$sorow->id} limit 1";
                            $uores = $conn_cms3->query($uosql);
                            //echo "갱신 : [$conn_cms3->affected_rows]<br>\n";

                            $uesql = "update spadb.ordermts_coupons set state='N' where order_id='{$sorow->id}' and couponno='$couponno' limit 1"; 
                            $ueres = $conn_cms3->query($uesql);
                            //echo "갱신 : [$conn_cms3->affected_rows]<br>\n";

                            // 채널 쿠폰번호로 복원처리 : 주석처리
/*
                            $tmp_couponno = explode(";", $sorow->barcode_no);
                            if(isset($tmp_couponno[0]) && !empty($tmp_couponno[0])){
                                if($tmp_couponno[0] != $couponno){
                                    $uesql = "update spadb.ordermts_coupons set state='N' where order_id='{$sorow->id}' and couponno='$tmp_couponno[0]' limit 1"; 
                                    $ueres = $conn_cms3->query($uesql);
                                    //echo "갱신 : [$conn_cms3->affected_rows]<br>\n";
                                }
                            }
*/
                            $uesql = "update cmsdb.ebridge_extcoupon set state='N' where orderno='{$sorow->orderno}' and coupon_num='$couponno' limit 1";
                            $ueres = $conn_rds->query($uesql);
                            //echo "갱신 : [$conn_rds->affected_rows]<br>\n";

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
            $state = '';
        }
        break;


    default:
        header("HTTP/1.0 400 Bad Request");
        $res = array("result"=>"4000","msg"=>"잘못된요청(파라미터)");
        echo $jsonres = json_encode($res,JSON_UNESCAPED_UNICODE);
        updateLogResult($jsonres);
}

switch($state){
    case 'S':
        $res = array("result"=>"1000","msg"=>"성공");
        break;
    case 'Y':
        $res = array("result"=>"4003","msg"=>"잘못된요청(기사용티켓)");
        break;
    case 'N':
        $res = array("result"=>"4004","msg"=>"잘못된요청(미사용티켓)");
        break;
    case 'C':
        $res = array("result"=>"4002","msg"=>"잘못된요청(환불티켓)");
        break;
    case 'E':
        $res = array("result"=>"9999","msg"=>"알수없는오류");
        break;
    default:
        $res = array("result"=>"4001","msg"=>"조회결과없음");

}
header("HTTP/1.0 200 OK");
echo $jsonres = json_encode($res,JSON_UNESCAPED_UNICODE);
updateLogResult($jsonres);

exit;


// 2번 서버에 있는 구버전
function usecouponno($no){

	$curl = curl_init();
    $url = "http://115.68.42.2:3040/use/".$no;
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_USERAGENT,"Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1" );
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

// aws 3번 서버에 있는 사용처리 nodejs
// 신버전임
function usecouponnov2($no){
    // 쿠폰 사용처리
    $curl = curl_init();
    $url = "http://172.31.30.15:3040/use/".$no;
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_USERAGENT,"Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1" );
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

	$res = explode(",",$ip);

    return trim($res[0]);
}

function insertLog($apiheader, string $apimethod, string $para, string $jsonreq){
    global $conn_rds;
    global $tranid;

    $apiheader = json_encode($apiheader);
    $qry = "INSERT cmsdb.extapi_log_ticket
                    SET apinm='ticket/v1',tran_id= ? ,
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
