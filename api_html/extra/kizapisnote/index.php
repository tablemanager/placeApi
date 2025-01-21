<?php

/*

키즈 노트 주문 연동

키즈노트 -> 플레이스엠

구매 : https://gateway.sparo.cc/extra/kizapisnote/order
조회 : https://gateway.sparo.cc/extra/kizapisnote/search
취소 : https://gateway.sparo.cc/extra/kizapisnote/cancel
재전송 : https://gateway.sparo.cc/extra/kizapisnote/resend

인증키 : 72340865908326.ERTYD.2346765348RESSSS.76457923212223

*/

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
$conn_rds->query("set names utf8");
$conn_cms3->query("set names utf8");

$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

// 파라미터
$itemreq = explode("/",$para);
$jsonreq = json_decode(trim(file_get_contents('php://input')));


list($microtime,$timestamp) = explode(' ',microtime());
$tranid = $timestamp . substr($microtime, 2, 3);
$logsql = "insert cmsdb.extapi_log set apinm='키즈노트',tran_id='$tranid', ip='".get_ip()."', logdate= now(), apimethod='$apimethod', querystr='".$para."', header='".json_encode($apiheader)."', body='".json_encode($jsonreq)."'";
$conn_rds->query($logsql);

$placecdc = $itemreq[0];
$proc = $itemreq[1];
// 인증 정보 조회
$auth = $apiheader['Authorization'];

if(!$auth) $auth = $apiheader['authorization'];

// ACL 확인
$accessip = array("106.254.252.100");

if(!in_array(get_ip(),$accessip)){
//    header("HTTP/1.0 401 Unauthorized");
//    $res = array("resultCode"=>"9996","resultMessage"=>"아이피 인증 오류 : ".get_ip());
//    echo json_encode($res);
//    exit;
}



if($auth != "72340865908326.ERTYD.2346765348RESSSS.76457923212223"){
    header("HTTP/1.0 401 Unauthorized");
    $res = array("resultCode"=>"9996","resultMessage"=>"인증 오류");
    echo json_encode($res);
    // 결과 로깅
    logresult($res); 
    exit;
}

header("Content-type:application/json");

$mdate = date("Y-m-d");
if(in_array(get_ip(),$accessip)){
	// var_dump($proc);
}
switch($apimethod){

	case 'POST':
		switch($placecdc){
			case 'order': // 주문

        $_res = setOrder($jsonreq);

			break;
      case 'cancel': // 취소

        $_res =  setCancel($jsonreq);

			break;
      case 'search': // 조회

        $_res = getOrderInfo($jsonreq);

		  break;
      case 'resend': // 재발송
        $_req = array(
          "order_no" => $jsonreq->order_no,
          "user_hp" => $jsonreq->user_hp
        );
        $_res = setResend($_req);
			break;
			default:
			break;
		}
	break;

	case 'PATCH':
	case 'GET':
	default:

      $_res = array(
        "result" => "0002",
        "return_msg" => "has no parameter"
      );

  }

echo json_encode($_res);
// 결과 로깅
logresult($_res);


exit;

// 주문
function setOrder($_req){
  global $conn_cms3;
  global $conn_rds;

  $usernm = $_req->user_name;
  $userhp = $_req->user_hp;
  $lists = $_req->list;
  $ordarr = array();
  foreach ($lists as $ord) {
    $itemcode = $ord->div_option_code;
    $chorderno = $_req->order_no."_".$ord->div_barcode;
    //echo "$usernm $userhp $itemcode $chorderno"


    $reqcms = json_encode(array(
            "userName"=>$usernm,
            "userHp"=>$userhp,
            "orderNo"=>$chorderno
    ));

    $_cmsres = cmsOrders($itemcode, $reqcms);

/*
    // 다른 로직과 꼬이므로 아예 보내지 말라고 키즈노트 권용성 님 레코멘디드 2023-05-25 (전화통화)
    $ordarr[] = array(
        // div_order_no 값을 주면 조회/취소 전문에서 div_barcode 를 이걸로 주므로 채널 주문번호를 찾을 수 없음
        // 따라서 삭제함 키즈노트 권용성 님 레코멘디드 2023-05-25
        //"div_order_no" => $_cmsres->Result[0]->OrderNo,
        //"div_barcode" => $_cmsres->Result[0]->CouponNo
    );
*/

  }
  return $ordres = array("result" => "0000",
                  "return_msg" => "성공",
                  "order_no" => $_req->order_no,
                  //"list"=>$ordarr
            );

}
// 조회
function getOrderInfo($_req){

    $chorderno = $_req->order_no."_".$_req->div_barcode;
    $_cmsres = cmsGetOrders($chorderno);

    if(isset($_cmsres->Code) && $_cmsres->Code == "1000"){
        $usegu = $_cmsres->Result[0]->use;
        $state  = $_cmsres->Result[0]->state;
/*
// 미사용/예약완료 상태 전문
{"Code":"1000","Msg":"\uc131\uacf5","Result":[{"orderno":"20230525_qliLtMKyEFFt","ch_orderno":"20230525-42C77_KN-TEST-4490-0","cus_nm":"\ub9ac\uc544\ub4dc\ub9ac\uc544\ub4dc\ub9ac\uc544\ub4dc","cus_hp":"01030661544","state":"\uc608\uc57d\uc644\ub8cc","itemcode":"62932","qty":"1","expdate":"2023-05-31","use":"2","usedate":null,"canceldate":null,"couponno":""}]}
*/
        if($usegu == "1"){
          $msgcode = "0004";
          $msgstr = "coupon that was used";
        }else{
          $msgcode = "0005";
          $msgstr = "un used coupon";
        }

        /*
// 취소상태 회신전문
{"Code":"1000","Msg":"\uc131\uacf5","Result":[{"orderno":"20230525_qliLtMKyEFFt","ch_orderno":"20230525-42C77_KN-TEST-4490-0","cus_nm":"\ub9ac\uc544\ub4dc\ub9ac\uc544\ub4dc\ub9ac\uc544\ub4dc","cus_hp":"01030661544","state":"\ucde8\uc18c","itemcode":"62932","qty":"1","expdate":"2023-05-31","use":"2","usedate":null,"canceldate":null,"couponno":""}]}
        */
        if($state == "취소"){
          $msgcode = "0003";
          $msgstr = "canceled coupon";
        }
    }elseif(isset($_cmsres->Code) && $_cmsres->Code == "4002"){
/*
// 티켓을 못찾은 경우
{"Code":"4002","Msg":"\uc870\ud68c \uacb0\uacfc\uac00 \uc5c6\uc2b5\ub2c8\ub2e4.","Result":null}
*/        
          $msgcode = "0006";
          $msgstr = "unknown coupon no";
    }else{
          $msgcode = "9999";
          $msgstr = "unknown error";
    }

    return $_res = array("result"=>$msgcode , "return_msg"=> $msgstr);
}

// 취소
function setCancel($_req){

  $chorderno = $_req->order_no."_".$_req->div_barcode;

  $_cmsres = cmsSetCancel($chorderno);

  if($_cmsres->Code == "1000"){
    $msgcode = "0000";
    $msgstr = "success";
  }else{
    $msgcode = "0001";
    $msgstr = "system error";
  }
 

  return $_res = array("result"=>$msgcode , "return_msg"=> $msgstr);

}

// 재전송 특별한 처리 없이 무조건 성공 처리
// 지원하지 않는 api
function setResend($_req){
    //$msgcode = "0000";
    //$msgstr = "success";

    // 지원하지 않는 api
    $msgcode = "0007";
    $msgstr = "unsupported api";

    return $_res = array("result"=>$msgcode , "return_msg"=> $msgstr);
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

// CMS주문연동
function cmsOrders($itemcd, $reqjson){
          $curl = curl_init();

          curl_setopt_array($curl, array(
            CURLOPT_URL => "http://extapi.sparo.cc/extra/agency/v2/dealcode/".$itemcd,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS =>$reqjson,
            CURLOPT_HTTPHEADER => array(
              "Authorization: 72340865908326.ERTYD.2346765348RESSSS.76457923212223",
              "Content-Type: application/json"
            ),
          ));

          return $response = json_decode(curl_exec($curl));

          curl_close($curl);

}

// CMS주문조회
function cmsGetOrders($chorderno){

  $curl = curl_init();

  curl_setopt_array($curl, array(
    CURLOPT_URL => 'http://gateway.sparo.cc/extra/agency/v2/chorderno/'.$chorderno,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'GET',

    CURLOPT_HTTPHEADER => array(
      'Content-Type: application/json',
      'Accept: application/vnd.tosslab.jandi-v2+json',
      'Authorization: 72340865908326.ERTYD.2346765348RESSSS.76457923212223',

    ),
  ));

  $response = json_decode(curl_exec($curl));

  curl_close($curl);
  return  $response;

}

// CMS 취소연동
function cmsSetCancel($chorderno){

  $curl = curl_init();

  curl_setopt_array($curl, array(
    CURLOPT_URL => 'http://gateway.sparo.cc/extra/agency/v2/chorderno/'.$chorderno,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'PATCH',

    CURLOPT_HTTPHEADER => array(
      'Content-Type: application/json',
      'Accept: application/vnd.tosslab.jandi-v2+json',
      'Authorization: 72340865908326.ERTYD.2346765348RESSSS.76457923212223',

    ),
  ));

  $response = json_decode(curl_exec($curl));

  curl_close($curl);
  return  $response;

}

function logresult($res){
    global $conn_rds;
    global $tranid;

    $logsql = "update cmsdb.extapi_log set apiresult='".json_encode($res)."' where tran_id='$tranid'";
    $conn_rds->query($logsql);
}

?>
