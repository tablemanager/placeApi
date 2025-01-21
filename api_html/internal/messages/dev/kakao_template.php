<?php
/**
 * Created by PhpStorm.
 * User: Connor
 * Date: 2018-05-11
 * Time: 오후 1:16
 */

require_once ('../lib/messages_db.php');
require_once ('../lib/sms_lib.php');
require_once ('../lib/curl.php');

$header = array("Content-Type: application/json;charset=UTF-8");	// 헤더

$kakao_host = "https://alimtalk-api.bizmsg.kr";
$profile_key = "f11c00d8beafedf5fc03b65a473613a8294f67a1";

// 카카오 템플릿 맹글자..
function template_create($item, $auto_request = TRUE)
{
    global $header, $kakao_host, $profile_key;

    //=======================================================
    $result = array();
    //$buttons = array();
    //$item['buttons1'] ="";
    //$item['buttons2'] ="";
    $url = $kakao_host."/v2/template/create";
    //=======================================================

    $item['create_cd'] = "TEST-HAMA01";
    $item['create_nm'] = "테스트-템플릿";
    $item['content'] = "템플릿 테스트입니다.";

    // 기본 셋팅 가즈아ㅏㅏㅏㅏㅏ
    $data = array();
    $data['senderKey'] = $profile_key;              // 발신프로필키 - 필수
    $data['senderKeyType'] = "S";                   // 발신프로필타입(G: 그룹, S: 기본(default))
    $data['templateCode'] = $item['create_cd'];     // 템플릿 코드 - 필수
    $data['templateName'] = $item['create_nm'];     // 템플릿 명
    $data['templateContent'] = $item['content'];    // 내용

    //$data['buttons'] = "";                          // 버튼 정보 (최대 5개 등록 가능)

    // 전송 데이터 마지막 JSON
    $Jdata = json_encode(array($data));
    $res = send_url($url, "post", $Jdata, $http_status, $header);
    $Jres = json_decode($res, TRUE);

    // 성공하면.. 성공처리..
    $result[0] = $Jres[0]['code'] == "success" ? TRUE : FALSE;
    $result[1] = $Jres[0]['message'];

    // 자동승인 관련내용인데.. 과연 여기서 사용할것인가..
    if ($auto_request) {
        //$template_request($data);
        insert_Template($data);
    }

    return $result;
}

// 카카오 템플릿 검수요청
function template_request($item)
{
    global $header, $kakao_host, $profile_key;

    $result = array();
    $url = $kakao_host."/v2/template/request";

    // 기본 셋팅 가즈아ㅏㅏㅏㅏㅏ
    $data = array();
    $data['senderKey'] = $profile_key;              // 발신프로필키 - 필수
    $data['senderKeyType'] = "S";                   // 발신프로필타입(G: 그룹, S: 기본(default))
    $data['templateCode'] = $item['create_cd'];     // 템플릿 코드 - 필수

    // 전송 데이터 마지막 JSON
    $Jdata = json_encode(array($data));
    $res = send_url($url, "post", $Jdata, $http_status, $header);
    $Jres = json_decode($res, TRUE);

    // 성공하면.. 성공처리..
    $result[0] = $Jres[0]['code'] == "success" ? TRUE : FALSE;
    $result[1] = $Jres[0]['message'];

    return $result;
}

// ==================================================
// 카카오 템플릿 리스트 조회
// inspectionStatus => 승인상태 (REG:등록, REQ:심사요청, APR:승인, REJ:반려)
// status => 템플릿 상태 (S:중단, A:정상, R:대기(발송전))
// ==================================================
function template_list()
{
    global $kakao_host, $profile_key;

    $result = array();
    $url = $kakao_host."/v2/template/list";

    // 기본 셋팅 가즈아ㅏㅏㅏㅏㅏ
    $data = array();
    $data['senderKey'] = $profile_key;              // 발신프로필키 - 필수

    $res = send_url($url, "get", $data, $http_status);
    $Jres = json_decode($res, TRUE);

    if ($Jres['code'] != 'success') {
        $result['result'] = FALSE;
        $result['data'] = $Jres['message'];
    }else {
//        $result[0] = TRUE;
//        $result[1] = json_encode($Jres['data']);

        foreach ($Jres['data'] as $k => $v) {
            insert_Template($v);
            update_Template($v);
        }
    }

    return $result;
}

?>