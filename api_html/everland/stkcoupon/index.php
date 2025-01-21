<?php

/*
 *
 * 에버랜드 에스티켓 쿠폰 생성 인터페이스
 *
 * 작성자 : 이정진
 * 작성일 : 2018-06-03
 *
 */

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
header("Content-type:application/json");

$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

// 파라미터
$itemreq = explode("/",$para);
$jsonreq = trim(file_get_contents('php://input'));



// ACL 확인
$accessip = array("115.68.42.2",
                  "115.68.42.8",
                  "115.68.42.130",
                  "52.78.174.3",
                  "106.254.252.100",
                  "115.68.182.165",
                  "13.124.139.14",
                  "13.209.232.254",
				          "218.39.39.190",
				          "13.124.215.30",
                  "18.163.36.64",
                  "13.209.184.223");

if(!in_array(get_ip(),$accessip)){
    header("HTTP/1.0 401 Unauthorized");
    $res = array("Result"=>"4100","Msg"=>"아이피 인증 오류 : ".get_ip());
    echo json_encode($res);
    exit;
}

$_req = json_decode($jsonreq);

$itemcode = $_req->itemcode;
$chorderno = $_req->chorderno;
$chcode = $_req->chcode;

switch($apimethod){
    case 'POST':
      echo _get_sticketcoupon($itemcode,$chcode,$chorderno);
    break;
	  default:
    break;
}


function _get_sticketcoupon($itemcode,$chcode,$chorderno){
  global $conn_cms3;
  global $conn_rds;

  $sql = "select * from spadb.pcms_sticket where pcms_id ='$itemcode'";
  $row = $conn_cms3->query($sql)->fetch_object();

  if(empty($row)) return '["0"]';

  $ctype = $row->gp;
  $ccode = $row->code_c.$row->code_i;

        $headflag = $ctype."500";
        $itemcode = $ccode;
        $chhid = rand(4,5);

        // 야놀자 코드일 경우 숫자로 구성되게
        if(get_ip() == "106.254.252.100") {
        //echo " -  ".substr($itemcode,0,2);
        //echo $itemcode;
        }

        $cno = $headflag.$itemcode.$placemcode.genRandomChar(7).$chhid;
        $crow = $conn_rds->query("select * from cmsdb.pcms_extorder where ch_orderno='$chorderno' and ch_code = '$chcode'")->fetch_object();

        if(strlen($crow->couponno) < 5){
            // 없으면 발급후 업데이트
            $ucsql = "update cmsdb.pcms_extorder set couponno= '$cno' where ch_orderno='$chorderno' and ch_code = '$chcode' limit 1";
            $conn_rds->query($ucsql);
            return json_encode(array($cno));

        }else{

            return json_encode(array($crow->couponno));
        }
}

function genRandomChar($length = 10) {
	$characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}

function genRandomNum($length = 10) {
	$characters = '1234567890';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
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

?>
