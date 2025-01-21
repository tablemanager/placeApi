<?php
/*
 *
 * 전북 투어패스
 *
 * 작성자 : 이정진
 * 작성일 : 2020-02-03
 *
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
$jsonreq = trim(file_get_contents('php://input'));
$proc = $itemreq[0];
$_req = json_decode($jsonreq);
$tranid = mktime();
$logsql = "insert cmsdb.extapi_log set  apinm='전북키오스크',tran_id='$tranid', ip='".get_ip()."', logdate= now(), apimethod='$apimethod', querystr='".$para."', header='".json_encode($apiheader)."', body='".$jsonreq."'";
$conn_rds->query($logsql);




//print_r($_req);

// 키오스크 인증키 jpblpaacsesm1015
// ACL 확인
$accessip = array("106.254.252.100");

if(!in_array(get_ip(),$accessip)){
    //header("HTTP/1.0 401 Unauthorized");
    //$res = array("resultCode"=>"9996","resultMessage"=>"아이피 인증 오류 : ".get_ip());
    //echo json_encode($res);
    //exit;
}

header("Content-type:application/json");

$mdate = date("Y-m-d");

/*
  1. 파라미터 체크가 필요함(코드)
*/


switch($proc){
	case 'orders':
    //$_result = online_sales();
		switch($_req->comm_method){
				case 'online_sales':
				  $_result = online_sales($_req);
				break;
				case 'online_cancel':
				  $_result = online_cancel($_req);
				break;
				default:
			    $_result = online_err($_req);

		}
	break;
  default:
    $_result = online_err($_req);

}





echo json_encode($_result);

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

function online_err($req){
  $_res = array();

  // sales
  $_res['comm_method'] = $m;
  $_res['result'] = "99";
  $_res['result_msg'] = "기타 오류";
  $_res['result_info'] = null;

  return $_res;
}

function online_sales($req){

  $_res = array();

	// 주문 생성
	$_req= array(
	  "chid"=>"3700",
		"usernm"=>$req->comm_nm,
		"userhp"=>$req->comm_hp,
		"itemcode"=>$req->comm_itemcode,
		"qty"=>$req->comm_qty,
		"chorderno"=>$req->comm_seq,
		"usedate"=>null,
		"couponno"=>null);

	$_result = json_decode(insertorder($_req));


	$syncres = json_decode(sync_order_tourpass($_result->orderno));

	//39194

	//https://napi.sparo.cc/tourpass/rese/index.php
	//https://napi.sparo.cc/tourpass/cancel/index.php
	//{"orderno":"20200213_PM239200142011"}

  // sales
  $_res['comm_method'] = "online_sales";
  $_res['result'] = "00";
  $_res['result_msg'] = "성공";
  $_res['comm_seq'] = $req->comm_seq;
  $_res['couponCd'] = $syncres->BARCODE;

  return $_res;
}

function online_cancel($req){

	$syncres = json_decode(sync_cancel_tourpass($req->comm_seq));

  $_res = array();
  // cancel
  $_res ['comm_method'] = "online_cancel";
	$_res['comm_seq'] = $_req->comm_seq;
  $_res ['result'] = "00";
  $_res ['result_msg'] = "성공";

  return $_res;
}

function sync_cancel_tourpass($_chorderno){
	global $conn_cms3;

	if(strlen($_chorderno) < 4) return false;

	$_sql = "select * from spadb.ordermts where ch_orderno = '$_chorderno' limit 1";
	$_row = $conn_cms3->query($_sql)->fetch_object();

	if($_row->state == "예약완료" and $_row->usegu == "1"){
			// 취소 실패
	}else{

		 $uosql = "update spadb.ordermts set state = '취소', cancelgu = null where state= '예약완료' and usegu = '2' and id = '$_row->id' limit 1";
		 $conn_cms3->query($uosql);

	}


	$_orderno = $_row->orderno;

	$curl = curl_init();

	curl_setopt_array($curl, array(
	  CURLOPT_URL => "https://napi.sparo.cc/tourpass/cancel/index.php",
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => "",
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 0,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "POST",
	  CURLOPT_POSTFIELDS =>json_encode(array("orderno"=>"$_orderno")),
	  CURLOPT_HTTPHEADER => array(
	    "Content-Type: application/json"
	  )
	));

	return $response = curl_exec($curl);

}

function sync_order_tourpass($_orderno){


	$curl = curl_init();


	curl_setopt_array($curl, array(
	  CURLOPT_URL => "https://napi.sparo.cc/tourpass/rese/index.php",
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => "",
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 0,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "POST",
	  CURLOPT_POSTFIELDS =>json_encode(array("orderno"=>"$_orderno")),
	  CURLOPT_HTTPHEADER => array(
	    "Content-Type: application/json"
	  )
	));

	return $response = curl_exec($curl);

}
function insertorder($_req){
	$curl = curl_init();

	$poststr = json_encode($_req);
	curl_setopt_array($curl, array(
	  CURLOPT_URL => "https://gateway.sparo.cc/internal/cms7order/makeorder.php",
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => "",
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 0,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "POST",
	  CURLOPT_POSTFIELDS =>$poststr,
	  CURLOPT_HTTPHEADER => array(
	    "Content-Type: application/json"
	  )
	));

	$response = curl_exec($curl);

	return $response;

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

// 랜덤 10진수
function genRandomNum($length = 10) {
	$characters = '0123456789';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}
?>
