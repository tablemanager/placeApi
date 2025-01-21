<?php

/*
 *
 * 잔디 액티비디
 *
 * 작성자 : 이정진
 * 작성일 : 2019-06-28
 *
 * 사용(POST)			: https://gateway.sparo.cc/internal/sysnotice
 *
 */

header("Content-type:application/json");

$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

// 파라미터
$itemreq = explode("/",$para);

// 잔디만 전송할지 여부
$jandionly = false;
if(isset($itemreq[1]) && $itemreq[1]=="JANDIONLY"){
    $jandionly = true;
}

$jsonreq = trim(file_get_contents('php://input'));



$accessip = array("115.68.42.2",
                  "115.68.42.8",
                  "115.68.42.130",
                  "52.78.174.3",
                  "106.254.252.100",
                  "115.68.182.165",
                  "13.124.139.14",
                  "13.209.184.223",
		  "211.219.73.56",
		  "52.78.222.162",
		  "61.74.186.246"
		);

if(!in_array(get_ip(),$accessip)){
    header("HTTP/1.0 401 Unauthorized");
    $res = array("Result"=>"4100","Msg"=>"아이피 인증 오류 : ".get_ip());
    echo json_encode($res);
    exit;
}


$r = json_decode($jsonreq);
$r->description;

// 잔디 알람전송
send_notice($itemreq[0],null,$jsonreq);
// 잔디 알람 전송시 
// 개발채널에 무조건 추가로 보내게 되어 있는데, 슬렉으로 중복 전송 방지
if($itemreq[0] != 'DEV' && $itemreq[0] != 'DEV_RESEND' && $jandionly == false){
    // 슬렉 알람전송
    send_slack($r->description);
}


// 시스템 노티스 설정
function get_noticeconfig($msgtype){

	switch($msgtype){
		case 'DEV':
		case 'DEV_RESEND':
			$_result = array("title"=>"[시스템] 모니터링 노티스",
							 "msgcolor"=>"#FAC11B");
		break;
		case 'REFUND':
			$_result = array("title"=>"조회 결과",
							 "msgcolor"=>"#FAC11B");
		break;
		default:

			$_result = array("title"=>"[액티비티] 시스템 공지",
							 "msgcolor"=>"#FAC11B");
	}

	return $_result;

}

// 잔디 인터페이스 주소(토픽별 생성)
function get_apiurlinfo($gcode){
	switch($gcode){
		case 'MANAGER':
            // 20240516 tony 매니저급 이상 관리자에게 필요한 알람 전송
			$apiurl = "https://wh.jandi.com/connect-api/webhook/19526168/404bc1f1a181d6ab394fe24884748b3f";
			break;
		case 'ACT':
			//$apiurl = "https://wh.jandi.com/connect-api/webhook/19526168/7ad5e566e8ff7d4d5d3f0daf0a7f82bd";
            // 20240516 tony 기존 채널 사라져서 신규 작성
			$apiurl = "https://wh.jandi.com/connect-api/webhook/19526168/dda83e9656ed796fe8b1bea0c73a1c86";
			break;
		case 'EBUS':
			$apiurl = "https://wh.jandi.com/connect-api/webhook/19526168/9ccfd0232e8022ed1210aac05df69af5";
			break;
		case 'DEV':
		case 'DEV_RESEND':
			//$apiurl = "https://wh.jandi.com/connect-api/webhook/19526168/a3fe0d58dbbce86612ffc3841b69bff9";
            // 20240516 tony 기존 채널 사라져서 신규 작성
			$apiurl = "https://wh.jandi.com/connect-api/webhook/19526168/99dbcedaf23fb13b43cb20c0ef13a85c";
			break;
		case 'REFUND':
			$apiurl = "https://wh.jandi.com/connect-api/webhook/19526168/cc14154fceef1105b07ef89572e630cc";
			break;

		default:
			$apiurl = "https://wh.jandi.com/connect-api/webhook/19526168/7ad5e566e8ff7d4d5d3f0daf0a7f82bd";
	}

	return $apiurl;
}




function send_slack($msg) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, "https://hooks.slack.com/services/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");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["text" => $msg]));
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);

    $response = curl_exec($ch);

}


function send_notice($gcode,$msgtype,$msg){

	$curl = curl_init();

	$cfg = get_noticeconfig($gcode);


	$msgarr = array(
	"body"=>$cfg['title'],
	"connectColor"=>$cfg['msgcolor'],
	"connectInfo"=>array(json_decode($msg))
	);

	curl_setopt_array($curl, array(
	  CURLOPT_URL => get_apiurlinfo($gcode),
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => "",
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 30,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "POST",
	  CURLOPT_POSTFIELDS => json_encode($msgarr),
	  CURLOPT_HTTPHEADER => array(
		"Accept: application/vnd.tosslab.jandi-v2+json",
		"Cache-Control: no-cache",
		"Connection: keep-alive",
		"Content-Type: application/json",
		"Host: wh.jandi.com",
		"accept-encoding: gzip, deflate",
		"cache-control: no-cache"
	  ),
	));

	echo $response = curl_exec($curl);
	$err = curl_error($curl);

	curl_close($curl);

}

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
