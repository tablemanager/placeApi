<?php
// ==================================================================
// TOAST 전송 API 연동
// 2018-05-02 코너(김민태)
// ==================================================================
require_once ('lib/sms_lib.php');
require_once ('lib/curl.php');

$header = array("Content-Type: application/json;charset=UTF-8");        // 헤더

// ==================================================================
//  LGUPlus 일반 SMS
//  $item => 데이터
// ==================================================================
function Send_LGUPlus_SMS($item)
{
        //$ret = insertSMS($item);
}

// ==================================================================
// TOAST 기본 셋팅
// ==================================================================
$host = "https://api-sms.cloud.toast.com/";                                                     // HOST
$api_key = "IawwIe8TbQ9S037o";                                                                          // KEY

// ==================================================================
//  일반 SMS
//  $item => 데이터
// ==================================================================
function Send_SMS($item)
{
    global $host, $api_key, $header;

    $result = array();
    //$url = $host . "/sms/v2.0/appKeys/" . $api_key . "/sender/sms";
    $url = "https://message.tblm.co/messages";

    /*
    // 기본 셋팅 가즈아ㅏㅏㅏㅏㅏ
    $data = array();
    $data['templateId'] = "";                                 // 발송 템플릿 아이디
    $data['body'] = trim($item['msgText']);                   // 본문 내용('EUC-KR' 기준으로 90 Byte 제한)
    $data['sendNo'] = trim($item['callBack']);                // 발신번호
    $data['requestDate'] = "";                                // 예약일시(yyyy-MM-dd HH:mm)
    $data['userId'] = "system";                               // 발송 구분자 ex)admin,system

    // 번호 셋팅..
    $recipientList = array();
    $recipientList['recipientNo'] = $item['dstAddr'];         // 수신번호 (countryCode와 조합하여 사용 가능)
    $recipientList['countryCode'] = "82";                     // 국가번호 [기본값: 82(한국)]
    $recipientList['internationalRecipientNo'] = "";          // 국가번호가 포함된 수신번호 예)821012345678
    $data['recipientList'] = array($recipientList);
    */


    $externalId = genRandomStr(10);
    $data = [
        "sender" => [
            "type" => "PHONE",
            "id" => "15448262"#trim($item['callBack'])
        ],
        "receiverList" => [
            [
                "phone" => $item['dstAddr']
            ]
        ],
        "template" => [
                "content" => [
                    "text" => trim($item['msgText']),
                "name" => "SMS"
            ],
            "retryRequests" => [],
            "externalId" => $externalId
        ]
    ];

    // 전송 데이터 마지막 JSON
    $Jdata = json_encode($data);
    $res = send_url($url, "post", $Jdata, $http_status, $header);
    $Jres = json_decode($res, true);

    $status_url = "https://message.tblm.co/messages/get-msgLogs-by-externalIds";
    $status_payload = array();
    $status_payload['externalIds'] = [$externalId];

    $statusData = json_encode(array($status_payload));
    $stres = send_url($status_url, "POST", $statusData, $http_status, $header);
    $statusRes = json_decode($stres, TRUE);
    $result['data'] = $statusRes;

    $stepValue = null; // 기본값

    if (!empty($statusRes['messageHistories'][0]['history']) && count($statusRes['messageHistories'][0]['history']) > 0) {
        $stepValue = $statusRes['messageHistories'][0]['history'][0]['step'];
    }

    if($stepValue !== null || $stepValue == "REQUEST_COMPLETED"){
        $result['result'] = TRUE;
    }
    else{
        $result['result'] = FALSE;
    }
    return $result;
}

// ==================================================================
//  MMS (첨부 파일 포함, 미포함)
//  $item => 데이터
//  $type => 타입설정(LMS, MMS)
//  $file_id => 이미지 ID
// ==================================================================
function Send_MMS($item, $type, $file_id = null)
{

    global $host, $api_key, $header;

    $result = array();
    //$url = $host . "/sms/v2.0/appKeys/" . $api_key . "/sender/mms";
    $url = "https://message.tblm.co/messages";

    $title = trim($item['msgSubject']) == "" ? '모바일 이용권' : trim($item['msgSubject']);

    /*
    // 기본 셋팅 가즈아ㅏㅏㅏㅏㅏ
    $data = array();
    $data['templateId'] = "";                                                    // 발송 템플릿 아이디
    $data['title'] = $title;                                                     // 제목 ('EUC-KR' 기준으로 40Byte 제한),(영문: 1byte, 한글: 2byte)
    $data['body'] = trim($item['msgText']);                                      // 본문 내용 ('EUC-KR' 기준으로 2000Byte 제한), (영문: 1byte, 한글: 2byte)
    $data['sendNo'] = trim($item['callBack']);                                   // 발신번호
    $data['requestDate'] = "";                                                   // 예약일시(yyyy-MM-dd HH:mm)
    $data['userId'] = "system";                                                  // 발송 구분자 ex)admin,system

    // 번호 셋팅..
    $recipientList = array();
    $recipientList['recipientNo'] = $item['dstAddr'];                            // 수신번호 (countryCode와 조합하여 사용 가능)
    $recipientList['countryCode'] = "82";                                        // 국가번호 [기본값: 82(한국)]
    $recipientList['internationalRecipientNo'] = "";                             // 국가번호가 포함된 수신번호 예)821012345678
    $data['recipientList'] = array($recipientList);

    // MMS 는 파일 첨부하여, ID를 보내줘야함..
    if (strtoupper($type) == "MMS") $data['attachFileIdList'] = array($file_id);
    */

    $externalId = genRandomStr(10);
    $data = [
        "sender" => [
            "type" => "PHONE",
            "id" => "15448262"
        ],
        "receiverList" => [
            [
                "phone" => $item['dstAddr']
            ]
        ],
        "template" => [
                "content" => [
                    "title" => $title,
                    "text" => trim($item['msgText'])
		],
                "name" => "LMS"
            ],
            "retryRequests" => [],
            "externalId" => $externalId
    ];

    // 전송 데이터 마지막 JSON
    $Jdata = json_encode($data);
    $res = send_url($url, "post", $Jdata, $http_status, $header);
    $Jres = json_decode($res, TRUE);

    $status_url = "https://message.tblm.co/messages/get-msgLogs-by-externalIds";
    //$status_payload = array();
    $status_payload = [
	    "externalIds" => [$externalId]
    ];
    $statusData = json_encode($status_payload);
    $stres = send_url($status_url, "POST", $statusData, $http_status, $header);
    $statusRes = json_decode($stres, TRUE);
    $result['data'] = $statusRes;

    $stepValue = null; // 기본값

    if (!empty($statusRes['messageHistories'][0]['history']) && count($statusRes['messageHistories'][0]['history']) > 0) {
        $stepValue = $statusRes['messageHistories'][0]['history'][0]['step'];
    }

    if($stepValue !== null || $stepValue == "REQUEST_COMPLETED"){
        $result['result'] = TRUE;
    }
    else{
        $result['result'] = FALSE;
    }

    return $result;
}

// ==================================================================
//  파일 첨부한다... 그리고 ID를 추출한다!
//  TOAST 서버에 이미지를 올림..
//  $item => 데이터
//  파일 사이즈는.. 0 에서 300K
// ==================================================================
function Send_File($item)
{
    global $host, $api_key, $header;
    $result = array();

    $url = $host . "/sms/v2.0/appKeys/" . $api_key . "/attachfile/binaryUpload";
    //$url = "https://message.tblm.co/messages"

    $item['pinType'] = strtoupper($item['pinType']);
    if ($item['pinType'] == "QR") {
        $file_url = "http://gateway.ticketmanager.ai/assets/".$item['pinNo'].".jpg";
    }else if ($item['pinType'] == "BARCODE") {
        $file_url = "http://gateway.ticketmanager.ai/barcode/get/".$item['pinNo'].".jpg";
    }else {
        $file_url = $item['mmsFile'];
    }

    // 기본 셋팅 가즈아ㅏㅏㅏㅏㅏ
    $data = array();
    $data['fileName'] = basename($file_url);                                    // 파일이름(확장자 jpg,jpeg만 가능)
    $data['fileBody'] = base64_encode(file_get_contents($file_url));            // 파일의 Byte[] 값
    $data['createUser'] = "placM_system";                                       // 파일 업로드 유저 정보

    // 전송 데이터 마지막 JSON
    $Jdata = json_encode($data);
    $res = send_url($url, "post", $Jdata, $http_status, $header);
    $Jres = json_decode($res, TRUE);

    // 실패시
    if ($Jres['header']['isSuccessful'] != TRUE) {
        $result['result'] = FALSE;
        $result['data'] = $res;                                                       // 실패시 원본 데이터를 보냄..
        $result['err'] = "header-fail";                                              // 헤더에서 실패했다는..
    } // 성공했을 경우.. ( 이렇게하는 이유는.. 데이터가 정상적으로 안들어왔을때 경우를 몰라서..!!!!!!)
    else {
        // 혹시몰라.. fileId 가 없을때 에러
        if ($Jres['body']['data']['fileId'] == "") {
            $result['result'] = FALSE;
            $result['data'] = $res;                                                   // 실패시 원본 데이터를 보냄..
            $result['err'] = "fileId-fail";                                          // 헤더에서 실패했다는..
        } else {
            $result['result'] = TRUE;
            $result['data'] = $Jres['body']['data']['fileId'];
        }
    }

    return $result;
}

// ==================================================================
//  KAKAO 알림톡 전송하기. (이미지 전송하는 부분은 미존재)
//  $item => 데이터
// ==================================================================
function Send_Kakao($item)
{
    global $profile_key, $header;

    // 플레이스엠...만 일단 등록하니까.. 이상태로 전송함. ( 추후에 카카오톡 발송이 다른 것도 사용시 변경해야함 )
    $header[] = "userid: placem";

    // 하마톡톡 알림톡 발송key
    $profile_key = "f11c00d8beafedf5fc03b65a473613a8294f67a1";
    $profile_array = array(
        'hamac' => 'f11c00d8beafedf5fc03b65a473613a8294f67a1',
        'high1' => '326bd99cea2f7d92f2e9355684f6c9afa57ec5bd',
        'playdoci' => 'd2b75d73d0231b6119433e6942426db0082da5e6',
        'pension' => '490fad42f6bb4f7916819b85f30d32f2c819141b',
        'seoulmobility' => 'c2e98e159bc01e3154c6e0d6b211b3022a4b30eb'
    );

    // ==================================================================
    // KAKAO 기본 셋팅
    // ==================================================================
    //$kakao_host = "https://dev-alimtalk-api.sweettracker.net";
    //$profile_key = "89823b83f2182b1e229c2e95e21cf5e6301eed98";                          // 알림톡 테스트키

    // 하마 톡톡배열에 없으면..
    if (array_key_exists($item['kakao_profile'], $profile_array)) {
        $profile_key = $profile_array[strtolower($item['kakao_profile'])];
    }

    //$kakao_host = "https://alimtalk-api.sweettracker.net";

    $result = array();
    //$url = $kakao_host . "/v2/" . $profile_key . "/sendMessage";
    $url = "https://message.tblm.co/messages";
    // ==================================================================

    /*
    // 기본 셋팅 가즈아ㅏㅏㅏㅏㅏ
    $data = array();
    $data['msgid'] = genRandomStr(10);                                // 메시지 일련번호(메시지에 대해 고유한 값이어야 함) - 필수 / 20자
    $data['message_type'] = "at";                                     // 메시지 타입(at: 알림톡, ft: 친구톡)
    $data['message_group_code'] = "";                                 // 메시지 유형을 구분하기 위한 값
    $data['profile_key'] = $profile_key;                              // 발신프로필키(메시지 발송 주체인 플러스친구에 대한키) - 필수
    $data['template_code'] = trim($item['msgSubject']);               // 메시지 유형을 확인할 템플릿 코드(사전에 승인된 템플릿의 코드) - 필수
    $data['receiver_num'] = "82".mb_substr($item['dstAddr'], 1);      // 사용자 전화번호(국가코드(대한민국:82)를 포함한 전화번호) - 필수
    $data['message'] = trim($item['msgText']);                        // 사용자에게 전달될 메시지(공백 포함 1000자) - 필수
    $data['reserved_time'] = "00000000000000";                        // 메시지 예약발송을 위한 시간 값 (yyyyMMddHHmmss) / 즉시전송 : 00000000000000, 예약전송 : 20160310210000) - 필수
    $data['sms_only'] = "";                                           // 카카오 비즈메시지 발송과 관계 없이 무조건 SMS발송요청
    $data['sms_message'] = trim($item['msgText']);    // 카카오 비즈메시지 발송이 실패했을 때 SMS전환발송을 위한 메시지
    $data['sms_title'] = mb_substr(trim($item['msgText']), 0, 60)."...";                                     // LMS발송을 위한 제목
    $data['sms_kind'] = "L";                                          // 전환발송 시 SMS/LMS 구분(SMS : S, LMS : L, 발송안함 : N) SMS 대체발송을 사용하지 않는 경우 : N
    $data['sender_num'] = "";                                         // SMS발신번호
    $data['parcel_company'] = "";                                     // 택배사 코드(부록 택배사 코드 참조)
    $data['parcel_invoice'] = "";                                     // 운송장번호
    $data['s_code'] = "";                                             // 쇼핑몰 코드(부록 쇼핑몰 코드 참조)
    $data['image_url'] = "";                                          // 친구톡 메시지에 첨부할 이미지 ur
    $data['image_link'] = "";                                         // 이미지 클릭시 이동할 url
    $data['ad_flag'] = "";                                            // 친구톡 메시지에 광고성 메시지 필수 표기 사항을 노출 (노출 여부 Y/N, 기본값 Y)

    // 버튼생성함..............
    if ($item['extVal1'] != "") {
        $buttons = json_decode($item['extVal1'], true);

        $data['button1'] = $buttons[0];                               // 메시지에 첨부할 버튼 1
        if ($buttons[1] != "") $data['button2'] = $buttons[1];        // 메시지에 첨부할 버튼 2
        if ($buttons[2] != "") $data['button3'] = $buttons[2];        // 메시지에 첨부할 버튼 3
        if ($buttons[3] != "") $data['button4'] = $buttons[3];        // 메시지에 첨부할 버튼 4
        if ($buttons[4] != "") $data['button5'] = $buttons[4];        // 메시지에 첨부할 버튼 4
        if ($buttons[5] != "") $data['button6'] = $buttons[5];        // 메시지에 첨부할 버튼 4
        if ($buttons[6] != "") $data['button7'] = $buttons[6];        // 메시지에 첨부할 버튼 4
                //if($item['msgSubject']=="ooozoooo_20")addLog("kakao_".date("Ymd"), array("buttons"=>$buttons)); //로그
    }

        //if($item['msgSubject']=="ooozoooo_20")addLog("kakao_".date("Ymd"), array("data"=>$data, "item"=>$item)); //로그
        //addLog("kakao_".date("Ymd"), $item); //로그
    */
    $externalId = genRandomStr(10);
    $data = [
        "sender" => [
            "type" => "SENDER_KEY",
            "id" => $profile_key
        ],
        "receiverList" => [
            [
                "phone" => $item['dstAddr']
            ]
        ],
        "template" => [
            "content" => [
                "templateCode" => trim($item['msgSubject']),
                "message" => trim($item['msgText']),
                "messageType" => "AT",
                /*
                "header" => "예약 안내 메시지",
                "attachment" => [
                    "button" => [
                        [
                            "name" => "자세히 보기",
                            "type" => "WL",
                            "url_mobile" => "https://theyeyak.com/my/676289456bca9d001128c2b5"
                        ],
                        [
                            "name" => "공유하기",
                            "type" => "WL",
                            "url_mobile" => "https://theyeyak.com/my/676289456bca9d001128c2b5"
                        ],
                        [
                            "name" => "위치보기",
                            "type" => "WL",
                            "url_mobile" => "http://naver.me/xiv5t439"
                        ]
                    ]
                ]
                */
            ],
            "name" => "KAKAO_ALIM_TALK"
        ],
        "retryRequests" => [],
        "externalId" => $externalId
    ];

    // 전송 데이터 마지막 JSON
    $Jdata = json_encode(array($data));
    $res = send_url($url, "POST", $Jdata, $http_status, $header);
    $Jres = json_decode($res, TRUE);

    /*
    // 전문과 데이터 담고.. 아래와 같이 전송실패시에만.. 첫번째 플래그 False 처리
    $result['result'] = $Jres[0]['result'] != "Y" || $Jres[0]['code'] != "K000" ? FALSE : TRUE;
    $result['data'] = $res;
    */
    $status_url = "https://message.tblm.co/messages/get-msgLogs-by-externalIds";
    $status_payload = array();
    $status_payload['externalIds'] = [$externalId];

    $statusData = json_encode(array($status_payload));
    $res = send_url($status_url, "POST", $statusData, $http_status, $header);
    $statusRes = json_decode($res, TRUE);
    $result['data'] = $statusRes;

    $stepValue = null; // 기본값

    if (!empty($statusRes['messageHistories'][0]['history']) && count($statusRes['messageHistories'][0]['history']) > 0) {
        $stepValue = $statusRes['messageHistories'][0]['history'][0]['step'];
    }

    if($stepValue !== null || $stepValue == "REQUEST_COMPLETED"){
        $result['result'] = TRUE;
    }
    else{
        $result['result'] = FALSE;
    }

    return $result;
}

// ==================================================================
//  KAKAO 알림톡 전송하기. (이미지 전송하는 부분은 미존재)
//  카카오알림톡 전송 실패시 LMS 전송하는 루틴으로 사용
//  $item => 데이터
// ==================================================================
function Send_KakaoV2($item)
{
    global $profile_key, $header;

    // 플레이스엠...만 일단 등록하니까.. 이상태로 전송함. ( 추후에 카카오톡 발송이 다른 것도 사용시 변경해야함 )
    $header[] = "userid: placem";

    // 하마톡톡 알림톡 발송key
    $profile_key = "f11c00d8beafedf5fc03b65a473613a8294f67a1";
    $profile_array = array(
        'hamac' => 'f11c00d8beafedf5fc03b65a473613a8294f67a1',
        'high1' => '326bd99cea2f7d92f2e9355684f6c9afa57ec5bd',
        'playdoci' => 'd2b75d73d0231b6119433e6942426db0082da5e6',
        'pension' => '490fad42f6bb4f7916819b85f30d32f2c819141b',
        'seoulmobility' => 'c2e98e159bc01e3154c6e0d6b211b3022a4b30eb'
    );

    // ==================================================================
   // KAKAO 기본 셋팅
    // ==================================================================
    //$kakao_host = "https://dev-alimtalk-api.sweettracker.net";
    //$profile_key = "89823b83f2182b1e229c2e95e21cf5e6301eed98";                          // 알림톡 테스트키

    // 하마 톡톡배열에 없으면..
    if (array_key_exists($item['kakao_profile'], $profile_array)) {
        $profile_key = $profile_array[strtolower($item['kakao_profile'])];
    }

    //$kakao_host = "https://alimtalk-api.sweettracker.net";

    $result = array();
    //$url = $kakao_host . "/v2/" . $profile_key . "/sendMessage";
    $url = "https://message.tblm.co/messages";
    // ==================================================================

    /*
    // 기본 셋팅 가즈아ㅏㅏㅏㅏㅏ
    $data = array();
    $data['msgid'] = genRandomStr(10);                                // 메시지 일련번호(메시지에 대해 고유한 값이어야 함) - 필수 / 20자
    $data['message_type'] = "at";                                     // 메시지 타입(at: 알림톡, ft: 친구톡)
    $data['message_group_code'] = "";                                 // 메시지 유형을 구분하기 위한 값
    $data['profile_key'] = $profile_key;                              // 발신프로필키(메시지 발송 주체인 플러스친구에 대한키) - 필수
    $data['template_code'] = trim($item['msgSubject']);               // 메시지 유형을 확인할 템플릿 코드(사전에 승인된 템플릿의 코드) - 필수
    $data['receiver_num'] = "82".mb_substr($item['dstAddr'], 1);      // 사용자 전화번호(국가코드(대한민국:82)를 포함한 전화번호) - 필수
    $data['message'] = trim($item['msgText']);                        // 사용자에게 전달될 메시지(공백 포함 1000자) - 필수
    $data['reserved_time'] = "00000000000000";                        // 메시지 예약발송을 위한 시간 값 (yyyyMMddHHmmss) / 즉시전송 : 00000000000000, 예약전송 : 20160310210000) - 필수
    $data['sms_only'] = trim($item['smsOnly']);                       // 카카오 비즈메시지 발송과 관계 없이 무조건 SMS발송요청
    $data['sms_message'] = trim($item['smsText']);                    // 카카오 비즈메시지 발송이 실패했을 때 SMS전환발송을 위한 메시지
    $data['sms_title'] = mb_substr(trim($item['msgText']), 0, 60)."...";      // LMS발송을 위한 제목
    $data['sms_kind'] = "L";                                          // 전환발송 시 SMS/LMS 구분(SMS : S, LMS : L, 발송안함 : N) SMS 대체발송을 사용하지 않는 경우 : N
    $data['sender_num'] = trim($item['senderNum']);                   // SMS발신번호
    $data['parcel_company'] = "";                                     // 택배사 코드(부록 택배사 코드 참조)
    $data['parcel_invoice'] = "";                                     // 운송장번호
    $data['s_code'] = "";                                             // 쇼핑몰 코드(부록 쇼핑몰 코드 참조)
    $data['image_url'] = "";                                          // 친구톡 메시지에 첨부할 이미지 ur
    $data['image_link'] = "";                                         // 이미지 클릭시 이동할 url
    $data['ad_flag'] = "";                                            // 친구톡 메시지에 광고성 메시지 필수 표기 사항을 노출 (노출 여부 Y/N, 기본값 Y)

    // 버튼생성함..............
    if ($item['extVal1'] != "") {
        $buttons = json_decode($item['extVal1'], true);

        $data['button1'] = $buttons[0];                               // 메시지에 첨부할 버튼 1
        if ($buttons[1] != "") $data['button2'] = $buttons[1];        // 메시지에 첨부할 버튼 2
        if ($buttons[2] != "") $data['button3'] = $buttons[2];        // 메시지에 첨부할 버튼 3
        if ($buttons[3] != "") $data['button4'] = $buttons[3];        // 메시지에 첨부할 버튼 4
        if ($buttons[4] != "") $data['button5'] = $buttons[4];        // 메시지에 첨부할 버튼 4
        if ($buttons[5] != "") $data['button6'] = $buttons[5];        // 메시지에 첨부할 버튼 4
        if ($buttons[6] != "") $data['button7'] = $buttons[6];        // 메시지에 첨부할 버튼 4
                //if($item['msgSubject']=="ooozoooo_20")addLog("kakao_".date("Ymd"), array("buttons"=>$buttons)); //로그
    }
    */
    // 전송 데이터 마지막 JSON
    $Jdata = json_encode(array($data));
    $res = send_url($url, "POST", $Jdata, $http_status, $header);
    $Jres = json_decode($res, TRUE);

    /*
    // 전문과 데이터 담고.. 아래와 같이 전송실패시에만.. 첫번째 플래그 False 처리
    //$result['result'] = $Jres[0]['result'] != "Y" || $Jres[0]['code'] != "K000" ? FALSE : TRUE;
    // 알림톡전송 실패하고 LMS를 전송하면 응답코드가 M000(정상-본적없음), M001(전송중-대게이걸로 수신됨)로 수신됨
    $result['result'] = ($Jres[0]['result'] == "Y" &&
                        ($Jres[0]['code'] == "K000" || $Jres[0]['code'] == "M000" || $Jres[0]['code'] == "M001"))
                        ? TRUE : FALSE;
    $result['data'] = $res;
    */
    $status_url = "https://message.tblm.co/messages/get-msgLogs-by-externalIds";
    $status_payload = array();
    $status_payload['externalIds'] = [$externalId];

    $statusData = json_encode(array($status_payload));
    $res = send_url($status_url, "POST", $statusData, $http_status, $header);
    $statusRes = json_decode($res, TRUE);
    $result['data'] = $statusRes;

    $stepValue = null; // 기본값

    if (!empty($statusRes['messageHistories'][0]['history']) && count($statusRes['messageHistories'][0]['history']) > 0) {
        $stepValue = $statusRes['messageHistories'][0]['history'][0]['step'];
    }

    if($stepValue !== null || $stepValue == "REQUEST_COMPLETED"){
        $result['result'] = TRUE;
    }
    else{
        $result['result'] = FALSE;
    }

    return $result;
}
?>
