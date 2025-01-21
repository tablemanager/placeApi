<?php

/*
 *
 * 에버랜드 연동 인터페이스
 *
 * 작성자 : 이정진
 * 작성일 : 2018-06-03
 *
 * 조회(GET)			: https://gateway.sparo.cc/everland/sync/{BARCODE}
 * 발권(POST)		: https://gateway.sparo.cc/everland/sync/{BARCODE}
 * 취소(PATCH)	: https://gateway.sparo.cc/everland/sync/{BARCODE}
 */

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
header("Content-type:application/json");

$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

// 파라미터
$itemreq = explode("/",$para);
$jsonreq = trim(file_get_contents('php://input'));

$couponno = $itemreq[0];

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



switch($apimethod){
    case 'POST':
        $syncmode = "PS";
    break;

	case 'PATCH':
        $syncmode = "PC";
    break;

	default:
        $syncmode = "CP";
    break;
}

switch(substr($couponno,0,2)){
    case 'CB':
    case 'EL':
		$enc_no = evencrypt($couponno);
		$vcd = substr($couponno,5,2);
		$re = syncev($enc_no,$vcd,$syncmode);
		$xml=simplexml_load_string($re);
        echo json_encode($xml,JSON_UNESCAPED_UNICODE);
    break;
	case 'S0':
        $enc_no = evencrypt($couponno);
        $re = syncev($enc_no,"06",$syncmode);
        $xml=simplexml_load_string($re);
        echo json_encode($xml,JSON_UNESCAPED_UNICODE);
    break;
    default:
        echo '{"RCODE":"E","PIN_STATUS":"","RMSG":""}';
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

function syncev($eno,$vcd,$emode) {

    $pincode=urlencode(trim($eno));

    $url="http://sp.everland.com:8100/everSocial/Everland.do";
    $uri="?PIN_NO=".$pincode."&SC_VCD=".$vcd."&VPROCESS=".$emode; //실서버

    $apiurl = $url.$uri;

    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, $apiurl);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_USERAGENT,"Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1" );
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 5);

    $data = curl_exec($curl);
    $info = curl_getinfo($curl);
    curl_close($curl);
    if($info['http_code'] == "200"){
        return $data;
    }else{
        return false;
    }
}


function evencrypt($str) {
	$curl = curl_init();
    $url = "http://115.68.42.2:3030/encrypt/".urlencode($str);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_USERAGENT,"Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1" );
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 5);

    $data = curl_exec($curl);
    $info = curl_getinfo($curl);
    curl_close($curl);
    if($info['http_code'] == "200"){
        return $data;
    }else{
        return false;
    }
}

?>
