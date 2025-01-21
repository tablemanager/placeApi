<?php
//error_reporting(E_ALL & ~E_NOTICE);

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');

require_once ('send_msg_api.php');
require_once ('kakao_template.php');
require_once ('../lib/messages_db.php');

header("Content-type:application/json");

$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders();            // http 헤더

// 추후 API 인증키 방식으로 이용할때,
// $auth = $apiheader['Authorization'];

// 파라미터
$itemreq = explode("/",$para);
$jsonreq = trim(file_get_contents('php://input'));
$item = json_decode($jsonreq,TRUE);
// 타입 선언..
$Send_type = strtoupper($itemreq[0]);

// 인터페이스 로그 ( 전송하기전에 보내기 )
//$logsql = "insert CMSSMS.MSG_RESULT_201805 set ip='".get_ip()."', logdate= now(), header='".json_encode($apiheader)."', body='".$para." ".$jsonreq."'";
//$conn_rds->query($logsql);

// ====================================
// 테이블 셋팅 및 전송데이터 검증 ( 실패시 값 리턴함. )
// ====================================
$setting = setting_table();
if ($setting['result'] == false) echo json_encode($setting);

$check = check_parameter($Send_type, $item);
if ($check['result'] == false) echo json_encode($check);
// ====================================

// 비어있으면, LMS로 보냄..
if ($Send_type == "MMS" && empty($item['mmsFile']) && $item['pinType'] != "QR") {
//    $Send_type = 'LMS';
}

// ====================================
// 어떠한 타입으로 들어왔는지..
// URL : http://gateway.sparo.cc/internal/messages/
// ====================================
switch($Send_type) {

    case 'SMS':
        $res = Send_SMS($item);
        break;

    // MMS 전송방식으로 LMS를 보낸다. ( 파일 미첨부로 하면댐 )
    case 'LMS':
        $res = Send_MMS($item, $Send_type);
        break;

    // 파일을 미리 담아야함..
    case 'MMS':
        // 파일 이미지를 TOAST 서버에다가 올린다..!!
        $file_ID = Send_File($item);

        // 파일업로드가 실패되었을때, 일단은 로그에 저장함. (에러가 발생시 1번배열에 전문넘어옴)
        if ($file_ID['result'] == FALSE) {
            insert_MSG_RESULT($item, $Send_type, $file_ID);
            return json_encode($file_ID);
        } else {
            $res = Send_MMS($item, $Send_type, $file_ID['data']);
        }
        break;

    case 'KAKAO':
        $res = Send_Kakao($item, $Send_type);
        break;

    // 임시용.. 아에 따로 뺄끄임..
    case 'KAKAO_TEMPLATE':

        //$temp = template_create($a);

        // 자동 승인처리...
        //if ($temp[0] == TRUE) $template_request($item);

        template_list();

        break;

    default:
        echo json_encode(array(false, "메세지 타입이 존재하지 않습니다."));
}

// 결과 값이 있으면..
if (is_array($res)) {
    insert_MSG_RESULT($item, $Send_type, $res);
    echo json_encode($res);
}

?>
