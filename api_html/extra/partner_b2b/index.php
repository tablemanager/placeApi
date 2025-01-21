<?php

/*
 *
 * CMS 채널 주문 인터페이스
 *
 * 작성자 : 김재현
 * 작성일 : 2020-12-10
 *
 *
 * 주문 취소(PATCH) https://gateway.sparo.cc/extra/partner_b2b/cancel/{주문번호}
 */
error_reporting(0);

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
require_once ('/home/placedev/php_script/lib/placemlib.php');
header("Content-type:application/json");

$conn_rds->query("set names utf8");

$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

// 파라미터
$itemreq = explode("/",$para);
$jsonreq = trim(file_get_contents('php://input'));
//print_r($itemreq);exit;

/*
// 인터페이스 로그
$tranid = date("Ymd").genRandomStr(10); // 트렌젝션 아이디
$logsql = "insert cmsdb.extapi_log set  apinm='파트너B2B',tran_id='$tranid', ip='".get_ip()."', logdate= now(), apimethod='$apimethod', querystr='".$para."', header='".json_encode($apiheader)."', body='".$jsonreq."'";
//echo $logsql;exit;
$conn_rds->query($logsql);
*/

// 인증 정보 조회
$auth = $apiheader['Authorization'];
if(!$auth) $auth = $apiheader['authorization'];

$authqry = "SELECT * FROM spadb.extapi_config WHERE authkey = '$auth' limit 1";
$authres = $conn_cms3->query($authqry);
$authrow = $authres->fetch_object();
$aclmode = $authrow->aclmode;

if($aclmode == "IP"){
// ACL 확인
    if(!in_array(get_ip(),json_decode($authrow->accessip,false))){
        header("HTTP/1.0 401 Unauthorized");
        $res = array("Result"=>"4100","Msg"=>"아이피 인증 오류 : ".get_ip());
        echo json_encode($res);
        exit;
    }
}

// API키 확인
if(!$authrow->cp_code){
    header("HTTP/1.0 401 Unauthorized");
    $res = array("Result"=>"4101","Msg"=>"인증 오류");
    echo json_encode($res);
    //exit;
}else{
    $cpcode = $authrow->cp_code; // 채널코드
    $cpname = $authrow->cp_name; // 채널명
    $grmt_id = $authrow->cp_grmtid; // 채널 업체코드
    $ch_id = $grmt_id;
}

// REST Method 분기
switch($apimethod){
	//case 'GET':
    case 'PATCH':
        // 주문 취소, 변경
        switch($itemreq[0]){
            case 'cancel':
                // 주문 취소
				$cusql = "update spadb.ordermts set state='취소',canceldate = now() where barcode_no = '".$itemreq[1]."' limit 1";
				//echo $cusql;exit;
				$conn_cms3->query($cusql);

				$res = array("Result"=>"0000","Msg"=>"취소완료");
				echo json_encode($res);
				break;          
            default:
				header("HTTP/1.0 400 Bad Request");
				$res = array("Result"=>"4001","Msg"=>"파라미터 오류");
				echo json_encode($res);
        }
		break;
    default:
        header("HTTP/1.0 400 Bad Request");
        $res = array("Result"=>"4002","Msg"=>"Method 오류");
        echo json_encode($res);
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


// 랜덤 스트링
function genRandomStr($length = 10) {
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}
