<?php
// 클라이언트 아아피
function get_ip(){
    $res = explode(",",$_SERVER["HTTP_X_FORWARDED_FOR"]);
    return $res[0];
}

//접근허용 IP
function checkIP($tkn){

	$allow_ip = array(
		"106.254.252.100", //플레이스엠 2층
		"118.131.208.123",
        "118.131.208.124",
        "118.131.208.125",
	    "118.131.208.126",
		"115.89.22.27",
		"218.39.39.190",
		"115.92.242.187",
		"115.92.242.18",
		"1.223.90.211",
		"52.78.222.162",
    
    "115.89.139.31",
    "115.89.139.33",
    "115.89.139.34",
    "115.89.139.35",
    "115.89.139.36",
    "115.89.139.39",
    "115.89.139.30",
    "115.89.139.14",
    "1.241.218.192",
	);
	$my_ip = get_ip();

	if (in_array($my_ip, $allow_ip) === true or $tkn != null) {
		return true;
	}else{
		header("HTTP/1.0 404 Not Found");
		echo "Your IP : ".$ip;
		exit;
	}
}

//접근허용 key
function checkAuth(){
	$apiheader = getallheaders();            // http 헤더
	$auth = $apiheader['Authorization'];

	$allow_key = array(
		"testKey" //
	);
	if (in_array($auth, $allow_key) === true) {
		return true;
	}else{
		return false;
	}
}

// 디버깅용
function xmp($text) {
	echo "<xmp>";
	print_r($text);
	echo "</xmp>";
}

function addLog($filename, $message = array()){
	$fp = fopen('/home/sparo.cc/api_html/internal/messages/log/'.$filename.'.log', 'a+');
	fwrite($fp, print_r($message,true).PHP_EOL);
	fclose($fp);
}

// =========================================================
// 파라미터 체크용..
// 한글-2byte, 총 90byte 발송가능
// LSM, MMS는 2000byte 까지 발송되며, 딱 2000btye가 되어도 MMS 파일첨부가 정상적으로 됨..
// =========================================================
function check_parameter($type, &$item)
{
    $check = true;
    $msg = "";

    // 발신번호가 안들어오면 1544-3913 대표번호로 나게가함.
    $item['callBack'] = $item['callBack'] == "" ? "15443913" : $item['callBack'];
    $item['dstAddr'] = str_replace("-", "", $item['dstAddr']);
    $item['callBack'] = str_replace("-", "", $item['callBack']);

    // 메세지 내용 비어있을 경우...
    if (empty($item['msgText'])) {
        $check = false;
        $msg = "발송불가. 메세지 내용이 비어있습니다.";
    }

    // 일단 카카오 발송톡은 나중에..
    if ($type == "SMS") {
        if (mb_strwidth($item['msgText'], 'UTF-8') > 90) {
            $check = false;
            $msg = "발송불가. 메세지 내용이 90byte를 초과 하였습니다. (총 byte : " . mb_strwidth($item['msgText'], 'UTF-8') . ")";
        }

    } else if ($type == "MMS" || $type == "LMS") {
        // MMS, LMS는 2000byte 까지 가능함.
        if (mb_strwidth($item['msgText'], 'UTF-8') > 2000) {
            $check = false;
            $msg = "발송불가. 메세지 내용이 2000byte를 초과 하였습니다. (총 byte : " . mb_strwidth($item['msgText'], 'UTF-8') . ")";
        } else if (mb_strwidth($item['msgSubject'], 'UTF-8') > 40) {
            $check = false;
            $msg = "발송불가. 메세지 내용이 2000byte를 초과 하였습니다. (총 byte : " . mb_strwidth($item['msgSubject'], 'UTF-8') . ")";
        }

        /*
        // 307,200 이미지 크기 검증로직..
        if ($type == "MMS") {
            // Jpg, Jpeg만, 업로드가 가능하다고 하여...
            $img_type = array_pop(explode(".", strtolower($item['mmsFile'])));

            if (in_array($img_type, array('jpg', 'jpeg')) != true) {
                $check = false;
                $msg = "발송불가. 이미지는 jpg, jpeg만 발송 할 수 있습니다.";
            } else if (strlen(file_get_contents($item['mmsFile'])) == 0) {
                $check = false;
                $msg = "발송불가. 이미지가 존재하지 않습니다. (이미지 파일 확인요망)";
            } else if (strlen(file_get_contents($item['mmsFile'])) > 307200) {
                $check = false;
                $msg = "발송불가. 파일크기가 300kb를 초과 하였습니다. (총 kb : " . round(strlen(file_get_contents($item['mmsFile'])) / 1024) . ")";
            }
        }
        */

    }

    return array("result" => $check, "msg" => $msg);
}

function TOAST_ErrMsg($text){

    $TOAST_ERR = array
    (
        "-1000" => "유효하지 않은 appKey",
        "-1001" => "존재하지 않는 appKey",
        "-1002" => "사용 종료된 appKey",
        "-1003" => "프로젝트에 포함되지 않는 멤버",
        "-1004" => "허용되지 않는 아이피",
        "-9996" => "유효하지 않는 contectType. Only application/json",
        "-9997" => "유효하지 않는 json 형식",
        "-9998" => "존재하지 않는 API",
        "-9999" => "시스템 에러(예기치 못한 에러)",

        "-1006" => "유효하지 않는 발송 메세지(messageType) 타입",
        "-2000" => "유효하지 않는 날짜 포맷",
        "-2001" => "수신자가 비어있습니다.",
        "-2002" => "첨부파일 이름이 잘못되었습니다.",
        "-2003" => "첨부파일 확장자가 jpg,jpeg가 아닙니다.",
        "-2004" => "첨부파일이 존재하지 않습니다.",
        "-2005" => "첨부파일의 크기는 0보다 크고, 300K보다 작아야합니다.",
        "-2006" => "템플릿에 설정된 발송 타입과 요청온 발송 타입이 맞지 않습니다.",
        "-2008" => "요청 아이디(requestId)가 잘못 되었습니다.",
        "-2009" => "첨부파일 업로드 도중 서버에러로 인해 정상적으로 업로드되지 않았습니다.",
        "-2010" => "첨부파일 업로드 타입이 잘못된 되었습니다.(서버 에러)",
        "-2011" => "필수 조회 파라미터가 비어있습니다.(requestId 또는 startRequestDate, endRequestdate)",
        "-2012" => "상세조회 파라미터가 잘못되었습니다.(requestId 또는 mtPr)",
        "-2014" => "제목 또는 본문이 비어있습니다.",
        "-2016" => "수신자가 1000명이 넘었습니다.",
        "-2017" => "엑셀 생성이 실패하였습니다.",
        "-2018" => "수신자 번호가 비어있습니다.",
        "-2019" => "수신자 번호가 유효하지 않습니다.",
        "-2021" => "시스템 에러(큐 저장 실패)",
        "-4000" => "조회 범위가 한달이 넘었습니다."
    );

    $msg = $TOAST_ERR[$text];
    if ($msg == "") $msg = "알 수 없는 에러발생";

    return $msg;
}

function KAKAO_ErrMsg($text){

    $KAKAO_ERR = array
    (
        "E101" => "Request 데이터오류",
        "E102" => "발신 프로필 키가 없거나 유효하지 않음",
        "E103" => "템플릿 코드가 없음",
        "E104" => "잘못된 전화번호- 유효하지 않은 전화번호- 안심번호",
        "E105" => "유효하지 않은 SMS 발신번호",
        "E106" => "메세지 내용이 없음",
        "E107" => "카카오 발송 실패시 SMS전환발송을 하는 경우 SMS 메시지 내용이 없음",
        "E108" => "예약일자 이상(잘못된 예약일자 요청)",
        "E109" => "중복된 MsgId 요청",
        "E110" => "MsgId를 찾을 수 없음",
        "E111" => "첨부 이미지 URL 정보를 찾을 수 없음",
        "E112" => "메시지 길이제한 오류(메시지 제한길이 또는 1000 자 초과)",
        "E113" => "메시지ID 길이제한 오류(메시지ID 20자 초과)",
        "E998" => "최대 요청 수 초과",
        "E999" => "최대 요청 수 초과",

        "K101" => "메세지를 전송할 수 없음 (카카오톡 미사용 또는 휴면계정)",
        "K102" => "전화번호 오류",
        "K103" => "메시지 길이제한 오류(메시지 제한길이 또는 1000 자 초과)",
        "K104" => "템플릿을 찾을 수 없음",
        "K105" => "매세지 내용이 템플릿과 일치하지 않음",
        "K106" => "첨부 이미지 URL 또는 링크 정보가 올바르지 않음",
        "K999" => "시스템 오류 발생",
    );

    $msg = $KAKAO_ERR[$text];
    if ($msg == "") $msg = "알 수 없는 에러발생";

    return $msg;
}

function genRandomStr($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

?>
