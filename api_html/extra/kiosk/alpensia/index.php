<?php
/*
 *
 * 알펜시아
 *
 * 작성자 : 이용준(토니)
 * 작성일 : 2022-05-17
 *
 *
 */

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
$conn_rds->query("set names utf8");
$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

// 파라미터
$itemreq = explode("/",$para);
$jsonreq = json_decode(trim(file_get_contents('php://input')));

$proc = $itemreq[0];


// ACL 확인
$accessip = array("106.254.252.100","52.78.174.3","13.124.215.30","13.209.184.223", "13.209.232.254");

list($microtime,$timestamp) = explode(' ',microtime());
$tranid = $timestamp . substr($microtime, 2, 3);
$logsql = "insert cmsdb.extapi_log set apinm='alpensia/index', chnm='ALPENSIA request[$proc]', tran_id='$tranid', ip='".get_ip()."', logdate=now(), apimethod='$apimethod', header='".addslashes(json_encode($apiheader))."', querystr='$para', body ='".addslashes(json_encode($jsonreq))."'";
$conn_rds->query($logsql);

if(!in_array(get_ip(),$accessip)){
    header("HTTP/1.0 401 Unauthorized");
    $res = array("resultCode"=>"9996","resultMessage"=>"아이피 인증 오류 : ".get_ip());
    echo json_encode($res);

    // 결과 로그기록
    $logsql = "update cmsdb.extapi_log set apiresult='".addslashes(json_encode($res))."' where tran_id='$tranid' limit 1";
    $conn_rds->query($logsql);

    exit;
}


header("Content-type:application/json");


$mdate = date("Y-m-d");

$_resjson = json_encode(array("resultCode"=>"9998","resultMessage"=>"파라미터 오류"));

/*
  1. 파라미터 체크가 필요함(코드)
*/
//echo $proc;
switch($proc){
	case 'order':
        //쿠폰번호 조회
        $itemcode = $itemreq[1];

        if($apimethod == "POST") $_resjson = setOrderCoupon($jsonreq->orderno,$itemcode,$jsonreq->qty,$jsonreq->usernm,$jsonreq->userhp);

	break;
	case 'cancel':
        //핸드폰번호로 조회
        $couponno = $itemreq[1];

		if($apimethod == "GET") $_resjson = setCancelCoupon($couponno);
	break;
	case 'search':
        // 사용내역 조회(일자)
        $couponno = $itemreq[1];

		if($apimethod == "GET") $_resjson = getCouponInfo($couponno);
	break;
	default:
		header("HTTP/1.0 400");
}

echo $_resjson;

// 결과 로그기록
$logsql = "update cmsdb.extapi_log set apiresult='".addslashes($_resjson)."' where tran_id='$tranid' limit 1";
$conn_rds->query($logsql);

function setCancelCoupon($couponno){
    global $conn_rds;

    $csql = "update cmsdb.alpensia_extcoupon set state='C' , cancelDt = now() where state != 'Y' and barcode ='$couponno' limit 1";
    $conn_rds->query($csql);

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://work.alpensia.net/interface/placem/alpenrecv.asp?type=cancel&barcode='.$couponno,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        // connect to the link via SSL without checking certificate
        // 20230711 tony https://placem.atlassian.net/browse/DD2201-77  긴급 [알펜시아 오션700] 바코드 오류 확인 요청건
        // 인증서 검증 안하게 변경
        CURLOPT_SSL_VERIFYPEER => false, 
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    return json_encode(explode(";",$response));
}

function getCouponInfo($couponno){

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://work.alpensia.net/interface/placem/alpenrecv.asp?type=search&barcode='.$couponno,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
          'Cookie: ASPSESSIONIDQECSBBDS=ECBDOIJCDBKFFEAOCKFAFGHJ'
        ),
        // connect to the link via SSL without checking certificate
        // 20230711 tony https://placem.atlassian.net/browse/DD2201-77  긴급 [알펜시아 오션700] 바코드 오류 확인 요청건
        // 인증서 검증 안하게 변경
        CURLOPT_SSL_VERIFYPEER => false,

    ));

    $response = curl_exec($curl);

    curl_close($curl);
    // return json_encode(explode(";",$response));
    return json_encode(explode(";",$response), JSON_UNESCAPED_UNICODE);

}

// 사용처리 내역 조회
function getUsedOrders($sdate,$edate){
    global $conn_rds;
	return json_encode($result);
}

function setOrderCoupon($orderno,$itemcode,$qty,$usernm,$userhp){

    global $conn_rds;
    global $conn_cms;

    $curl = curl_init();

    // 알펜시아 상품코드 구하기
    $_row = $conn_cms->query("select * from CMSDB.CMS_ITEMS where item_id = '$itemcode' limit 1")->fetch_object();
    $pcode = $_row->item_cd;

    if(empty($pcode)) return false;
    if(empty($usernm)) return false;
    if(empty($userhp)) return false;

    // 문자열의 모든 공백 제거(2022-07-18 알펜시아에서 요청, Bad Request 방지)
    $usernm = preg_replace("/\s+/", "", $usernm);

    $_ordres = $conn_rds->query("select * from cmsdb.alpensia_extcoupon where orderno= '$orderno'");

    $_cp = array();

    if($_ordres->num_rows < $qty){
        $ccnt = $qty - $_ordres->num_rows;

        for($i=0;$i<$ccnt;$i++){

            curl_setopt_array($curl, array(
                //CURLOPT_URL => 'https://work.alpensia.net/interface/placem/alpenrecv.asp?type=reserv&pcode='.$pcode.'&mobiltel='.$userhp.'&guestname='.$usernm,
                CURLOPT_URL => "https://work.alpensia.net/interface/placem/alpenrecv.asp?type=reserv&pcode=$pcode&mobiltel=$userhp&guestname=$usernm",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                // connect to the link via SSL without checking certificate
                // 20230711 tony https://placem.atlassian.net/browse/DD2201-77  긴급 [알펜시아 오션700] 바코드 오류 확인 요청건
                // 인증서 검증 안하게 변경
                CURLOPT_SSL_VERIFYPEER => false,
            ));
            $r = curl_exec($curl);
            $cpres = explode(";",$r);

            if(!empty($cpres)) $_cp[] = $cpres['1'];

            $_isql = "insert cmsdb.alpensia_extcoupon
                        set barcode = '$cpres[1]', orderno ='$orderno', seq='$i', regDt = now(), usernm = '$usernm', userhp='$userhp'";
            $conn_rds->query($_isql);
        }
    }else{
        while($_ordrow = $_ordres->fetch_object()){
            $_cp[] = $_ordrow->barcode;
        }
    }

    return json_encode($_cp);
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
