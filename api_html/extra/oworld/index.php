<?php
/*
 *
 * 오월드 키오스크 연동 인터페이스 
 * 
 * 작성자 : 이정진, 김민태
 * 작성일 : 2018-10-15
 * 
 * 티켓 조회(POST) https://gateway.sparo.cc/extra/oworld/ordered 
 * 티켓 사용(POST) https://gateway.sparo.cc/extra/oworld/used 
 * 티켓 회수(POST) https://gateway.sparo.cc/extra/oworld/unused
 * 공개키 PATH /home/sparo.cc/application/keys/oworld/placem.pub
 * 개인키 PATH /home/sparo.cc/application/keys/oworld/placem.pem 
 * https://gateway.sparo.cc/extra/oworld/testqr 테스트 QR 생성
 *
 * 
 */

//error_reporting(0);

require_once('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
header("Content-type:application/json");

$conn_rds->query("set names utf8");

$data = array();
$para = $_GET['val']; // URI 파라미터 
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

// 파라미터 
$itemreq = explode("/", $para);
$jsonreq = rawurldecode(trim(file_get_contents('php://input')));
parse_str($jsonreq, $data);
$tranid = date("Ymd") . genRandomStr(10); // 트렌젝션 아이디

$logsql = "insert cmsdb.extapi_log set apinm='오월드',tran_id='$tranid', ip='" . get_ip() . "', logdate= now(), apimethod='$apimethod', querystr='" . $para . "', header='" . json_encode($apiheader) . "', body='" . $jsonreq . "'";
$conn_rds->query($logsql);

//$data = trim('{"id": "12345678-1234-1234-1234-123456789012","date": 1535942463287,"pins": ["11001300A10202T001","11001300A10202T002"],"name": "테스트" ,"phone": "01090901678"}');
//$data['q'] = trim('{"id":"68C1F6ED-E50F-4926-8AFD-6045E41B35DB","date":"1543822547486","pins":["10072300000204007PQ00628"]}');
$request = json_decode($data['q'], true);
// REST Method 분기
switch ($apimethod) {

    case 'GET':
    case 'POST':
        // POST 처리
        switch ($itemreq[0]) {

            // 주문 데이터 생성
            case 'ordered':
                $orderinfo = make_order($request['pins'], $request['phone']);

                $res = array();
                $res['id'] = $request['id'];
                $res['date'] = milliseconds();
                $res['status'] = "200";

                if ($orderinfo == false) {
                    $res['message'] = '[]';
                } else {
                    $res['message'] = $orderinfo;
                }

                echo json_encode($res);
                break;

            // 사용처리, 사용처리 해제
            case 'used':
            case 'unused':

                $res = array();
                $res['id'] = $request['id'];
                $res['date'] = milliseconds();

                // 필수 파라미터 누락시
                if (count($request['pins']) > 0) {

                    if ($itemreq[0] == 'used') $status = set_use($request['pins']);
                    else if ($itemreq[0] == 'unused') $status = set_unuse($request['pins']);

                    $res['status'] = $status == true ? 200 : 422;
                    $res['message'] = $request['pins'];

                } else {
                    $res['status'] = 400;
                    $res['message'] = "Bad Request";
                }

                echo json_encode($res);
                break;


            case 'testqr':

                $temp = array(
                    '10010301010000007PQ00001' => '00012018110120181130',
                    '10020301020001007PQ00002' => '00022018110120181130',
                    '10030301030002007PQ00003' => '00012018110120181130',
                    '10040301040003007PQ00004' => '00032018110120181130',
                    '10050301050004007PQ00005' => '00012018110120181130',
                    '10060301060100007PQ00006' => '00012018110120181130',
                    '10070301070101007PQ00007' => '00012018110120181130',
                    '10080301080102007PQ00008' => '00042018110120181130',
                    '10090301090103007PQ00009' => '00012018110120181130',
                    '10100301100104007PQ00010' => '00022018110120181130',
                    '10110301110200007PQ00011' => '00012018110120181130',
                    '10120301120201007PQ00012' => '00012018110120181130',
                    '10130301130202007PQ00013' => '00032018110120181130',
                    '10140301140203007PQ00014' => '00012018110120181130',
                    '10150301150204007PQ00015' => '00032018110120181130',
                    '10160301160300007PQ00016' => '00012018110120181130',
                    '10170301170301007PQ00017' => '00042018110120181130',
                    '10180301180302007PQ00018' => '00012018110120181130',
                    '10190301190303007PQ00019' => '00082018110120181130',
                    '10200301200304007PQ00020' => '00012018110120181130',
                );

                foreach ($temp as $k => $v) {
                    $temp2[] = $k . $v . "PLACEM";
                }
                xmp($temp2);
                exit;
                break;

            default:
        }
        break;

//case 'POST':

//    break;

    default:
        $res = array();
        $res['id'] = $request['id'];
        $res['date'] = milliseconds();
        $res['status'] = 405;
        $res['message'] = "Method Not Allowed";

        echo json_encode($res);
        break;
}

// =================================================
// 소셜연동 조회
// pin : 핀노드
// phone : 전화번호
// =================================================
function get_orderinfo($pin = array(), $phone = "")
{
    global $conn_rds;
    global $conn_cms3;

    if (count($pin) > 0) {

        $sql = "Select * From cmsdb.`oworld_extcoupon` WHERE 1";

        // 선언된 Array에 SQL문을 합쳐줌.
        $codes = implode(', ', array_map(
            function ($v) {
                return "'" . $v . "'";
            },
            $pin
        ));

        $sql .= " AND `couponno` in ({$codes})";

        //if (!empty($phone)) $sql .= " AND `hp` = '{$phone}'";

        $result = $conn_rds->query($sql);

        if (mysqli_num_rows($result) > 0) {
            $temp = array();
            while ($row = $result->fetch_assoc()) {
                $temp[] = $row;
            }
            return $temp;
        }
    }

    return false;
}

// =================================================
// 소셜연동 조회
// pin : 핀노드
// phone : 전화번호
// =================================================
function make_order($pin = array(), $phone = "")
{

    $rows = get_orderinfo($pin, $phone);

    foreach ($rows as $index => $row) {
        // ===================================================
        // 초기 변수 선언
        // ===================================================
        $data = array();                    // 판매자주문정보
        $orders = array();                  // 주문데이터
        // ===================================================

		if(empty($row['tks_regdate'])) $row['tks_regdate'] = date("Y-m-d H:i:s");
		if(empty($row['usernm'])) $row['usernm'] ="플레이스엠";
		if(empty($row['hp'])) $row['hp'] ="010-0000-0000";
        $data['sale_dt'] = strtotime($row['tks_regdate']) * 1000;            // 판매일시, 1970/01/01 이후의 milliseconds 증분
        $data['order_dt'] = strtotime($row['tks_regdate']) * 1000;           // 주문일시, 1970/01/01 이후의 milliseconds 증분
//        $data['order_type_cd'] = $row['order_type_cd'];                      // 주문유형코드
        $data['order_type_cd'] = "10030000";                     // 주문유형코드(소셜 10030000 온라인 10020000)
        $data['name'] = $row['usernm'];                                      // 주문자명
        $data['phone'] = sha256tobase64($row['hp']);                         // 휴대폰번호
        $data['birthday'] = Null;                                            // 주문자생년월일(사용안함)
        $data['channel_nm'] = "소셜커머스";                                       // 주문채널명

        // 중간 주문데이터
        $temp = array();
        $temp['order_item_no'] = $row['couponno'];                       // 주문품목번호
//        $temp['product_type_cd'] = $row['order_goods_no'];               // 제품유형코드
        $temp['product_type_cd'] = "02010100";               // 제품유형코드
        $temp['applct_dm'] = strtotime($row['tks_sdate']) * 1000;        // 사용시작일자(시간정보 무시), 1970/01/01 이후의 milliseconds 증분, 제품유형코드가 이용권일 경우 필수항목
        $temp['expire_dm'] = strtotime($row['tks_edate']) * 1000;        // 사용종료일자(시간정보 무시), 1970/01/01 이후의 milliseconds 증분, 제품유형코드가 이용권일 경우 필수항목
        $temp['quantity'] = "1";                                         // 수량
        $temp['used_dt'] = empty($row['usedate']) ? Null : strtotime($row['usedate']) * 1000;          // 사용일시(시간정보 무시), 1970/01/01 이후의 milliseconds 증분

        $orders[] = $temp;

        $data['orders'] = $orders;
        $response[] = $data;
        // 최종값
//        $response['site'] = "PLACEM";                           // 생성자 구분값
//        $response['signature'] = "";                            // 사인(ECDSA-256/SHA1withECDSA to Base64)
//        $response['data'] = $data;
    }

    return $response;
}

// 사용처리
function set_use($pin)
{
    global $conn_rds;
	global $conn_cms3;

    $chk = true;
    $codes = implode(', ', array_map(function ($v) {
            return "'" . $v . "'";
    }, $pin));
	
	// ordermts에 주문 코드를 구함
    $obcode = $conn_cms3->query("select barcode_no,w_paygu from ordermts where state = '예약완료' and id in (select order_id from spadb.ordermts_coupons where couponno in ({$codes}))");
	$bars = "";
	while($obrow= $obcode->fetch_object()){
		if($obrow->w_paygu == "WEB") return false;
		$bars = $bars.$obrow->barcode_no.";"; 

	}

    $orderbars = implode(', ', array_map(function ($v) {
            return "'" . $v . "'";
    }, array_filter(explode(";",$bars))));

	// ordermts에 주문 코드를 구함
    $ores = $conn_rds->query("select couponno from cmsdb.oworld_extcoupon where state = 'N' and couponno in ({$orderbars})");
	
	$ocnt= $ores->num_rows;

	if($ocnt == '0'){
		return false;
	}else{
		$uflag= "N";
		while($orow=$ores->fetch_object()){
			foreach($pin as $p){
				if($p == $orow->couponno) $uflag= "Y";
			}
		}
		if($uflag == "N") return false;
	}


    $rows = get_orderinfo($pin);

    // 먼저 주문건 상태를 검사한다.
    foreach ($rows as $row) {
        // 미사용 상태가 아님
        if ($row['state'] != 'N') $chk = false;
        if (!$chk) break;
    }

    $wgu=date('w',mktime());

	if($wgu==0 or $wgu==6){
    	$wmode="W";
    }else{
    	$wmode="D";
    }

    // 기타 강제휴일
    $hdate = array("2019-12-25","2020-01-01","2020-01-24","2020-01-27");

    if(in_array(date("Y-m-d"),$hdate)){
      $wmode="W";
    }



    if ($chk) {


        $conn_rds->query("UPDATE cmsdb.`oworld_extcoupon` 
                SET state = 'Y', usedate = NOW() , term = 'POS' 
                WHERE 1 
                AND `couponno` in ({$codes}) 
                AND `state` = 'N'
        ");

		foreach($pin as $pp){
			usecouponno($pp);
		}
    }
	
    return $chk;
}

// 복구처리
function set_unuse($pin)
{
    global $conn_rds;

    $chk = true;
    $rows = get_orderinfo($pin);

    // 먼저 주문건 상태를 검사한다.
    foreach ($rows as $row) {
        // 미사용 상태가 아님
        if ($row['state'] != 'Y') $chk == false;
        if (!$chk) break;
    }

    if ($chk) {
        $codes = implode(', ', array_map(function ($v) {
            return "'" . $v . "'";
        }, $pin));

        $conn_rds->query("UPDATE cmsdb.`oworld_extcoupon` 
                SET `state` = 'N', `usedate` = NULL 
                WHERE 1 
                AND `couponno` in ({$codes}) 
                AND `state` = 'Y'
        ");
    }

    return $chk;
}

// 랜덤 스트링 
function genRandomStr($length = 10)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function get_signature($data)
{
    // 개인키로 사인 생성 (ECDSA-256/SHA1withECDSA to Base64)
    $private_key_pem = "file:///home/sparo.cc/application/keys/oworld/placem.pem";
    $pkeyid = openssl_pkey_get_private($private_key_pem);
    openssl_sign($data, $signature, $pkeyid, OPENSSL_ALGO_SHA1);
    openssl_free_key($pkeyid);

    return base64_encode($signature);
}

function get_qrcode($pincode)
{
    // 주문 코드와 생성자를 포함한 QR 코드 생성
    $qr = $pincode . get_signature($pincode) . "PLACEM";
    return $qr;
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

function xmp($t)
{
    echo "<xmp>";
    print_r($t);
    echo "</xmp>\n";
}

function milliseconds()
{
    $mt = explode(' ', microtime());
    return ((int)$mt[1]) * 1000 + ((int)round($mt[0] * 1000));
}

function sha256tobase64($str){
    // 전화번호 암호화 오월드 요청에 따라 2번 반복
    return base64_encode(hash("sha256", base64_encode(hash("sha256", $str, True)), True));  
}

function usecouponno($no){
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
    if($data[1] == "0"){
        return "N";
    }else{
        return "Y";
    }
}

?>