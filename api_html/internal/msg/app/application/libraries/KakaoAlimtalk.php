<?php
if( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 쿠팡 연동 라이브러리
 * @author larry
 * @since 2020.02.21
 *
 */
class KakaoAlimtalk 
{
    private $CI = null;

	//알림톡 발송 정보
	private $userid = "placem";
	private $kakao_url = "https://alimtalk-api.sweettracker.net";
    private $profile_array = array(
        'hamac' => 'f11c00d8beafedf5fc03b65a473613a8294f67a1',
        'high1' => '326bd99cea2f7d92f2e9355684f6c9afa57ec5bd',
        'playdoci' => 'd2b75d73d0231b6119433e6942426db0082da5e6',
        'pension' => '490fad42f6bb4f7916819b85f30d32f2c819141b',
		'wherego' => 'ca84655646311927c99453332c120782126aa76b'
    );
	

    public function __construct($aParams = array())
    {	
        $this->CI = &get_instance();

        if (class_exists('curl') === false) {
            $this->CI->load->library('util/curl/curl', null, 'curl'); // curl
        }
		$this->CI->load->library('Common', null, 'commLib');

	}
    public function __destruct(){}

    public function send($aData){
		
		if (array_key_exists($aData['kakao_profile'], $this->profile_array)) {
			$profile_key = $this->profile_array[strtolower($aData['kakao_profile'])];
		}else{
			return "발송키가 잘못되었습니다.";
		}

		$aData["dstAddr"] = str_replace("-", "", $aData["dstAddr"]);
		$aData["dstAddr"] = str_replace(" ", "", $aData["dstAddr"]);

		$msg = array();
		$msg["msgid"] = date('YmdHis');
		$msg["message_type"] = "AT";
		$msg["profile_key"] = $profile_key;		
		$msg["receiver_num"] = "82".intval($aData["dstAddr"]);
		$msg["template_code"] = trim($aData['msgSubject']);
		$msg["message"] = trim($aData['msgText']);
		$msg["reserved_time"] = "00000000000000";

		$msg["sms_message"] = trim($aData['msgText']); // 카카오 비즈메시지 발송이 실패했을 때 SMS전환발송을 위한 메시지
		$msg["sms_title"] = mb_substr(trim($aData['msgText']), 0, 120, 'UTF-8');	// LMS발송을 위한 제목
		$msg["sms_kind"] = "L"; // 전환발송 시 SMS/LMS 구분(SMS : S, LMS : L, 발송안함 : N) SMS 대체발송을 사용하지 않는 경우 : N
		
		if($aData['kakao_button']){
			//$buttons = json_decode($aData['kakao_button'], true); 
			$buttons = $aData['kakao_button']; 
			if (isset($buttons[0])) $msg['button1'] = $buttons[0];		// 메시지에 첨부할 버튼 1
			if (isset($buttons[1])) $msg['button2'] = $buttons[1];        // 메시지에 첨부할 버튼 2
			if (isset($buttons[2])) $msg['button3'] = $buttons[2];        // 메시지에 첨부할 버튼 3
			if (isset($buttons[3])) $msg['button4'] = $buttons[3];        // 메시지에 첨부할 버튼 4
			if (isset($buttons[4])) $msg['button5'] = $buttons[4];        // 메시지에 첨부할 버튼 4
		}

		$msg = array($msg);
		$msg = json_encode($msg, JSON_UNESCAPED_UNICODE);

		$url = $this->kakao_url . "/v2/" . $profile_key . "/sendMessage";
		$headers[] = 'Accept: application/json;';
		$headers[] = 'Content-type: application/json;';
		$headers[] = 'userid: '.$this->userid;
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);			
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $msg);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);			
		$result = curl_exec($ch);
		curl_close($ch);
		$result = json_decode($result, true);

		$code = isset($result[0]) && $result[0]["result"] == "Y" ? "ok" : "예기치 않은 오류";
		if($code != "ok"){
			$fileName = date('Ymd').'_kakao_error';
		}else{
			$fileName = date('Ymd').'_kakao';
		}
	
		$this->CI->commLib->addLog($fileName, print_r(array(
			'date' => date('Y.m.d H:i:s'),
			'url' => $url,
			'aData' => $aData,
			'msg' => $msg,
			'msg_decode' => json_decode($msg, true),
			'result' => $result
		), true), 'api');
		

		return $code;
	}

	//등록된 카카오 탬플릿 리스트
	public function template_list($aData){
		/*
		$this->CI->commLib->addLog(date('Ymd').'_template_list_log', print_r(array(
			'errorLog' => date('Y.m.d H:i:s'),
			'kakao_profile' => $aData['kakao_profile'],
			'profile_array' => $this->profile_array
		), true), 'api');	
		*/

		if (array_key_exists($aData['kakao_profile'], $this->profile_array)) {
			$profile_key = $this->profile_array[strtolower($aData['kakao_profile'])];
		}else{
			return 0;
		}

		$url = "https://alimtalk-api.bizmsg.kr/v2/template/list?senderKey=".$profile_key;
		$headers[] = 'userid: '.$this->userid;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);			
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);	
		curl_setopt($ch, CURLOPT_POST, false);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
		$res = curl_exec($ch);
		curl_close($ch);	
		$result = json_decode($res, true);

		if(isset($result) && @$result["code"]=="success"){
			return $result;
		}else{
			$this->CI->commLib->addLog(date('Ymd').'_error_log', print_r(array(
				'template_list' => date('Y.m.d H:i:s'),
				'url' => $url,
				'headers' => $headers,
				'result' => $result
			), true), 'api');	
			return 0;
		}
		

	}

}