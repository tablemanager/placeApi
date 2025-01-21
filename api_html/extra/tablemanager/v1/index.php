<?php
/*
 *
 * @brief 테이블매니저(4364) 시설에서의 사용처리 수신용 인터페이스 입니다.
 * @doc 
 * @author tony
 * @date 2023.01.15~
 *
 */
/*
JSON_UNESCAPED_UNICODE    // 유니코드 문자열을 escape 하지 않습니다.
JSON_FORCE_OBJECT        // 배열을 강제로 object로 변환합니다.
JSON_NUMERIC_CHECK        // 숫자로된 문자열을 INT 형으로 변환합니다.
JSON_HEX_TAG            // 태그기호를 HEX로 인코딩 합니다.
JSON_PRETTY_PRINT       // json array 형태로 변환한다.
*/
$json_opt = JSON_UNESCAPED_UNICODE; // 한글 나옴
//$json_opt = JSON_HEX_TAG;

/*
 // 다른업체 사용시 아래 11-13 line 주석처리 후 사용하세요 (ila)
 $res = array("result"=>"4000","msg"=>"잘못된요청(파라미터)-");
 echo $jsonres = json_encode($res, $json_opt);
 exit;
*/

// 테이블매니저 키
// 개발키
// $_tm_api_key = 'F6E1167F8B1369DDC93FCF9DAFB64AC581636A27';
// $_tm_serviceId = 'placem-live-commerce';
// 키
// $_key = 'oVWQ2T4sqNfkpIGHdMqhglgy8GZ3TWbd';
// $_iv = '46rss4NR2WNiwDbV';
// 개발 host
// $_tm_host = "https://dev-tablemanager-bos-app.tblm.co/api/v1.0/voucher/{$_tm_serviceId}/";

// 운영 키
$_tm_api_key = '6C875FF14CD7FA7B5A521BF47B34E35DD8A17A31';
$_tm_serviceId = 'placem-live-commerce';
// 키
$_key = 'o1SRvzKqdDP0XkwlKUhf1UxRpFzmfRGm';
$_iv = 'Z8ihfZxSFqUPu1zD';
// host
$_tm_host = "https://tablemanager-bos-app.tblm.co/api/v1.0/voucher/{$_tm_serviceId}/";

// error_reporting(0);
require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
require_once ('/home/placedev/php_script/lib/placemlib.php');

header("Content-type:application/json");
$conn_rds->query("set names utf8");

$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

// 파라미터
$itemreq = explode("/",$para);
$jsonreq = json_decode(trim(file_get_contents('php://input')));
//var_dump($jsonreq);

$proc = $itemreq[0];

list($microtime,$timestamp) = explode(' ',microtime());
$tranid = $timestamp . substr($microtime, 2, 3);
insertLog($apiheader, $apimethod, $para, json_encode($jsonreq, $json_opt));

if(get_ip() =="106.254.252.100"){
    // var_dump($jsonreq);
	// echo $apimethod;
}


// 인증 정보 조회
$auth = $apiheader['Authorization'];
if(!$auth) $auth = $apiheader['authorization'];
// dzFhdgRvy886JUz5xacFvOo2AzgFCNZPmE7AUY5s7ezR98WGj9lPXKRyj8dV

$authqry = "SELECT * FROM spadb.extapi_config WHERE authkey = ? limit 1";
$authstmt = $conn_cms3->prepare($authqry);
$authstmt->bind_param("s", $auth);
$authstmt->execute();
$authres = $authstmt->get_result();
$authrow = $authres->fetch_object();
// var_dump($authrow);
$aclmode = $authrow->aclmode;
$grmtId = $authrow->cp_grmtid;

// OFF 이므로 이 루틴은 적용되지 않는다.
if($aclmode == "IP"){
// ACL 확인
    if(!in_array(get_ip(),json_decode($authrow->accessip,false))){
        header("HTTP/1.0 401 Unauthorized");
        $res = array("result"=>"4100","msg"=>"인증오류(ip)");
        echo $jsonres = json_encode($res, $json_opt);
        updateLogResult($jsonres);
        exit;
    }
}

//echo $proc;
// url parameter 분기
switch($proc){
    case 'use': // spadb.ordermts + 해당 시설 테이블 사용처리
        $tmOrderId = $jsonreq->OrderId;
        // 핀번호 기본 validation 체크
        if($tmOrderId == '' || strlen($tmOrderId) < 6 || strlen($tmOrderId) > 40){
            header("HTTP/1.0 400 Bad Request");
            $res = array("result"=>"4000","msg"=>"잘못된요청(파라미터)");
            echo $jsonres = json_encode($res, $json_opt);
            updateLogResult($jsonres);
            exit;
        }
        // echo $grmtId;
        // 주문번호 찾기
        $curDt = date("Y-m-d H:i:s");
        $ssql = "select * from cmsdb.tablemanager_extcoupon where orderId = '$tmOrderId' limit 1";
        $sres = $conn_rds->query($ssql);
        $srow = $sres->fetch_object();
        // 없으면 에러
        if(empty($srow)){
            $state = 'E';
        }else{
            // transactionId
            $transactionId = $srow->transactionId;
            // 주문번호로 상태 조회
            $orderno = $srow->orderno;
            $osql = "select * from spadb.ordermts where orderno = '$orderno' limit 1";
            $ores = $conn_cms3->query($osql);
            $orow = $ores->fetch_object();
            // 못찾으면 에러
            if(empty($orow)){
                $state = 'E';
            }else{
                // 사용상태
                switch($orow->state){
                    case '취소':
                        // 이미 취소된 티켓
                        $state = 'C';
                    break;
                    case '예약완료':
                        // 사용
                        if($orow->usegu == '1'){
                            // 기 사용 티켓
                            $state = 'Y';
                        // 미사용
                        }elseif($orow->usegu == '2'){
                            // 확장 테이블 갱신
                            $usql = "update cmsdb.tablemanager_extcoupon set useYn = 'Y', useDt = '$curDt' where orderId = '$tmOrderId' and useYn = 'N' limit 1";
                            $ures = $conn_rds->query($usql); 
                           
                            // 주문내역 사용처리 
                            $usesql = "UPDATE ordermts A INNER JOIN ordermts_coupons B ON A.id = B.order_id SET A.usegu ='1' , A.usegu_at = now() WHERE A.usegu = '2' and B.couponno = '$transactionId'";
                            $useres = $conn_cms3->query($usesql); 
                            // 통합 쿠폰목록에 사용처리
                            $usesql = "UPDATE spadb.ordermts_coupons set dt_use=now(), state='Y' where state='N' and couponno = '$transactionId' limit 1";
                            $useres = $conn_cms3->query($usesql); 
                            // 길이가 너무 길어서 usecouponno 함수 사용 불가
                            ///usecouponno($transactionId);
                            $state = 'S';
                        }else{
                            $state = 'E';
                        }
                    break;
                    default:
                        $state = 'E';
                    break;
                }
            } 
        }

        $res = returnmsg($state); 

/*
		switch($grmtId){ // 업체 아이디 분기
            case '4364': // 테이블매니저 시설
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
*/
        break;
    // 예약상품권 목록
    case 'product':
        $rtn = _tm_product($itemreq[1]);
        $rtn = json_decode($rtn);
        if($rtn->result == true){
            $state = 'S';
        }else{
            $state = 'E';
        }
        $res = returnmsg($state);
        // 수신된 코드(메시지 유추가능함) ex) REQUEST_SUCCESS, REQUEST_TOKEN_MISSING
        $res['code'] = $rtn->code; 
        // 수신한 데이터
        $res['data'] = json_encode($rtn->data, $json_opt); 
/*
        foreach($rtn->data as $dd){
            print_r($dd->productId);
            print("\n");
        }
*/
        break;
    // 예약상품권 목록
    case 'product2':
        $rtn = _tm_product($itemreq[1]);
        $rtn = json_decode($rtn);
        if($rtn->result == true){
            $state = 'S';
        }else{
            $state = 'E';
        }
        $res = returnmsg($state);
        // 수신된 코드(메시지 유추가능함) ex) REQUEST_SUCCESS, REQUEST_TOKEN_MISSING
        $res['code'] = $rtn->code;
        // 수신한 데이터
        $res['data'] = json_encode($rtn->data, $json_opt|JSON_PRETTY_PRINT);
        print_r($res);
        exit;
/*
        foreach($rtn->data as $dd){
            print_r($dd->productId);
            print("\n");
        }
*/
        break;
    // 주문
    case 'order':
        // pcms 상품id 유효성 검사
        if(count($itemreq) < 2){
            header("HTTP/1.0 400 Bad Request");
            $res = array("result"=>"4000","msg"=>"잘못된요청(파라미터):pcms_itemid");
            echo $jsonres = json_encode($res, $json_opt);
            updateLogResult($jsonres);
            exit;
        }else{
            // pcms 상품id
            $itemid = $itemreq[1];
            // 상품번호로 테이블매니저 상품ID 조회
            $psql = "SELECT * from CMSDB.CMS_ITEMS where item_id = '$itemid' AND item_state = 'Y' AND item_edate >= '".date('Y-m-d H:i:s')."'";
            $pres = $conn_cms->query($psql);
            //var_dump($pres);
            $prow = $pres->fetch_object();
            // 상품정보에 등록된 테이블매니저 상품id
            $tm_product_id = trim($prow->item_cd);

            if(empty($tm_product_id)){
                header("HTTP/1.0 400 Bad Request");
                $res = array("result"=>"4000","msg"=>"잘못된요청(파라미터):tm_itemid");
                echo $jsonres = json_encode($res, $json_opt);
                updateLogResult($jsonres);
                exit;
            }
        } 
        //echo "$tm_product_id\n";
        // 조회된 테이블매니저 상품ID로 상품정보 조회
        $prtn = _tm_product($tm_product_id);
        $prtn = json_decode($prtn);

        // 호출을 실패하거나, 수신한 데이터가 없으면
        if($prtn->result != true || count($prtn->data) < 1){
            // 상품정보 테이블매니저에서 조회 실패
            header("HTTP/1.0 400 Bad Request");
            $res = array("result"=>"4000","msg"=>"잘못된요청(파라미터):tm_itemid_fail");
            echo $jsonres = json_encode($res, $json_opt);
            updateLogResult($jsonres);
            exit;
        }

        // 호출된 json 내용
        // $jsonreq = array( "orderno"=> $orderno, "qty" => $qty, "usernm" => $usernm, "userhp" => $userhp);
        $req = array( "userName" => $jsonreq->usernm, 
                    "userPhone" => $jsonreq->userhp,
                    "productId" => $tm_product_id,
                    "quantity" => $jsonreq->qty,
                    "requestDate" => date("Y-m-d H:i:s"),
                    //"expectedDate" => date("Y-m-d H:i:s"),    // 데이터 없을시에는 즉시 발급 요청
                );
        $jsonreq = json_encode($req, $json_opt);

        //print_r($jsonreq);

        // 주문등록
        $rtn = _tm_order($jsonreq);
        $rtn = json_decode($rtn);
        if($rtn->result == true){
            $state = 'S';
        }else{
            $state = 'E';
        }
        $res = returnmsg($state);
        // 수신된 코드(메시지 유추가능함) ex) REQUEST_SUCCESS, REQUEST_TOKEN_MISSING
        $res['code'] = $rtn->code;
        // 수신한 데이터
        $res['data'] = json_encode($rtn->data, $json_opt); 

        // 입력파라미터도 리턴한다.
        $res['productId'] = $tm_product_id;
        $res['requestDate'] = $req['requestDate'];
/*
        foreach($rtn->data as $dd){
            print_r($dd);
            print("\n");
        }
*/
        break;
    // 주문상태 조회
    case 'status':
        $transactionId = $jsonreq->transactionId;
        //var_dump($jsonreq);
        //var_dump($transactionId);
        // 핀번호 기본 validation 체크
        if($transactionId == '' || strlen($transactionId) < 6 || strlen($transactionId) > 40){
            header("HTTP/1.0 400 Bad Request");
            $res = array("result"=>"4000","msg"=>"잘못된요청(파라미터)-tm_tr_id");
            echo $jsonres = json_encode($res, $json_opt);
            updateLogResult($jsonres);
            exit;
        }

        $rtn = _tm_status($transactionId);
        //print_r($rtn);
        $rtn = json_decode($rtn);
        if($rtn->result == true){
            $state = 'S';
        }else{
            $state = 'E';
        }
        $res = returnmsg($state);
        // 수신된 코드(메시지 유추가능함) ex) REQUEST_SUCCESS, REQUEST_TOKEN_MISSING
        $res['code'] = $rtn->code;
        // 수신한 데이터
        $res['data'] = json_encode($rtn->data, $json_opt); 
/*
        foreach($rtn->data as $dd){
            print_r($dd);
            print("\n");
        }
*/
        break;
    // 주문취소
    case 'cancel':
        $orderId = $jsonreq->orderId;
        // 핀번호 기본 validation 체크
        if($orderId == '' || strlen($orderId) < 6 || strlen($orderId) > 40){
            header("HTTP/1.0 400 Bad Request");
            $res = array("result"=>"4000","msg"=>"잘못된요청(파라미터)");
            echo $jsonres = json_encode($res, $json_opt);
            updateLogResult($jsonres);
            exit;
        }

        $rtn = _tm_cancel($orderId);
        $rtn = json_decode($rtn);
        if($rtn->result == true){
            $state = 'S';
        }else{
            $state = 'E';
        }
        $res = returnmsg($state);
        // 수신된 코드(메시지 유추가능함) ex) REQUEST_SUCCESS, REQUEST_TOKEN_MISSING
        $res['code'] = $rtn->code;
        // 수신한 데이터
        $res['data'] = json_encode($rtn->data, $json_opt); 
/*
        foreach($rtn->data as $dd){
            print_r($dd);
            print("\n");
        }
*/
        break;
/*
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
            default:
            $state = '';
        }
        break;
*/
    default:
        header("HTTP/1.0 400 Bad Request");
        $res = array("result"=>"4000","msg"=>"잘못된요청(파라미터)");
        echo $jsonres = json_encode($res, $json_opt);
        updateLogResult($jsonres);
        exit;
}


header("HTTP/1.0 200 OK");
echo $jsonres = json_encode($res, $json_opt);
updateLogResult($jsonres);

exit;

// 공통 리턴 메시지 생성
function returnmsg($state){
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

    return $res;
}

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

// 테이블매니저 암호화
function _tm_encrypt($json){
    global $_key, $_iv;

    $en = openssl_encrypt($json, 'aes-256-cbc', $_key, false, $_iv);
    return $en;
}
// 테이블매니저 복호화
function _tm_decrypt($json){
    global $_key, $_iv;

    $de = openssl_decrypt($json, 'aes-256-cbc', $_key, false, $_iv);
    return $de;
}

function _tm_product($product_id){
    global $_tm_host, $_tm_api_key;

    $curl = curl_init();
    $api = $_tm_host."product";

    if(!empty($product_id)){
        $jsonreq = json_encode(array("productId" => "$product_id"));
    }
    //print_r($jsonreq);

    curl_setopt_array($curl, array(
        CURLOPT_URL => $api,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_POSTFIELDS =>$jsonreq,
        CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json",
            "TM-API-KEY: $_tm_api_key",
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    return $response;

    // error 응답
    // {"result":false,"code":"REQUEST_TOKEN_MISSING","data":[]}

    // 정상 응답
/*
{
    "result": true,
    "code": "REQUEST_SUCCESS",
    "data": {
        "tgif_placem-live-commerce_season2_50000": {
            "productId": "tgif_placem-live-commerce_season2_50000",
            "productName": "티지아이프라이데이 예약상품권 50,000원권",
            "amount": 50000,
            "salePrice": 100,
            "saleYn": "Y",
            "startDate": "2022-12-05 14:27:06",
            "endDate": "2023-05-24 23:59:59",
            "expiry": "P1Y",
            "isEnable": "Y"
        },
            "oppachicken_placem-live-commerce_50000": {
            "productId": "oppachicken_placem-live-commerce_50000",
            "productName": "오븐에빠진닭 예약상품권 50,000원권",
            "amount": 50000,
            "salePrice": 2000,
            "saleYn": "Y",
            "startDate": "2022-10-24 10:00:00",
            "endDate": "2023-10-23 23:59:59",
            "expiry": "P1Y",
            "isEnable": "Y"
        }
    }
}
*/
}

// 상태조회
function _tm_status($transcationId){
    global $_tm_host, $_tm_api_key;

    $curl = curl_init();
    $api = $_tm_host."order/status";

    $jsonreq = json_encode(array("transactionId"=>$transcationId));

    curl_setopt_array($curl, array(
        CURLOPT_URL => $api,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_POSTFIELDS => $jsonreq,
        CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json",
            "TM-API-KEY: $_tm_api_key",
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    return $response;

    // error 응답
    // {"result":false,"code":"REQUEST_TOKEN_MISSING","data":[]}
}

// 주문취소
function _tm_cancel($orderId){
    global $_tm_host, $_tm_api_key;

    $curl = curl_init();
    $api = $_tm_host."order/cancel";

    // 주문번호를 json으로 변환
    $jsonOrderId = json_encode(array("orderId"=>"$orderId"));
    // json을 암호화함
    $encOrderId = _tm_encrypt($jsonOrderId);
    // json의 data 항목에 암호화된 문자열을 넣음
    $jsonreq = json_encode(array("data"=>"$encOrderId"));
//print_r($jsonOrderId);
//print_r($jsonreq);print_r($_tm_api_key);
    curl_setopt_array($curl, array(
        CURLOPT_URL => $api,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'PATCH',
        CURLOPT_POSTFIELDS =>$jsonreq,
        CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json",
            "TM-API-KEY: $_tm_api_key",
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    return $response;

    // error 응답
    // {"result":false,"code":"REQUEST_TOKEN_MISSING","data":[]}
}

function _tm_order($jsonreq){
    global $_tm_host, $_tm_api_key;

    $curl = curl_init();
    $api = $_tm_host."order";

    // json을 암호화함
    $enc = _tm_encrypt($jsonreq);
    // json의 data 항목에 암호화된 문자열을 넣음
    $encjsonreq = json_encode(array("data"=>"$enc"));

    curl_setopt_array($curl, array(
      CURLOPT_URL => $api,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS =>$encjsonreq,
      CURLOPT_HTTPHEADER => array(
        "Content-Type: application/json",
        "TM-API-KEY: $_tm_api_key",
      ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    return $response;

}
/*
header("HTTP/1.0 401 Unauthorized");
header("HTTP/1.0 400 Bad Request");
header("HTTP/1.0 200 OK");
header("HTTP/1.0 500 Internal Server Error");

*/
