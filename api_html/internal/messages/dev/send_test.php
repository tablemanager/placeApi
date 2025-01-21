<?php
require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
require_once ('../send_msg_api.php');
require_once ('../kakao_template.php');
require_once ('../lib/messages_db.php');

$msg='[롯데워터파크] 모바일 입장권 구매가 완료 되었습니다.

    김재현 고객님, 아래 "모바일 입장권 보기"를 눌러 입장권 확인 후 게이트에 제시 바랍니다.

    ▶상품명 : B쿠팡 8차 클린패스_G
    ▶쿠폰번호 : 0478574360562703
    ▶매수 : 1매
    ▶유효기간 : 2020-08-16 까지
    ▶유의사항 : 티켓 구매 페이지 참조
    ▶고객센터 : 1544-3913
';

$Send_type = "KAKAO";

$param = array();
$param["dstAddr"]="01028320196";
$param["callBack"]="15443913";
$param["msgSubject"]="HAMA-009";
$param["msgText"]=$msg;
$param['kakao_profile']="hamac";
$param['extVal1']=array(
	array(
					"name" => "모바일 입장권 보기",
					"type" => "WL",
					"url_mobile" => "https://sparo.cc/dnUp2yEQwFGnzgmwDF",
					"url_pc" => "https://sparo.cc/dnUp2yEQwFGnzgmwDF",
	)
);
$param['extVal1'] = json_encode($param['extVal1'], JSON_UNESCAPED_UNICODE);
//$item = json_decode($param,TRUE);
$item = $param;

    if ($item['extVal1'] != "") {
        $buttons = json_decode($item['extVal1'], true);

        $data['button1'] = $buttons[0];   

		$Jdata = json_encode(array($data));

		//xmp($Jdata);exit;
	}
//exit;

$check = check_parameter($Send_type, $item);
if ($check['result'] == false) {
    echo json_encode($check);
    exit;
}

$Send_type = "KAKAO";
//$Send_type = "LMS";

//$item = json_encode($item);
xmp($item);
if($Send_type == "KAKAO"){
	$res = Send_Kakao($item);
}else if($Send_type == "LMS"){
	$res = Send_MMS($item, "LMS");
}

addLog("kakao_".date("Ymd"), $item); //로그

// 결과 값이 있으면..
if (is_array($res) && $res["result"]) {
    insert_MSG_RESULT($item, $Send_type, $res);
	echo "<p>Result : 전송완료<p>";
    //echo json_encode($res);
}


xmp($res);exit;
?>