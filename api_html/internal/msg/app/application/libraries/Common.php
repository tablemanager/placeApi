<?php
if( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 공통 라이브러리
 * @author larry
 * @since 2020.08.18
 *
 */
class Common 
{
    private $CI = null;

    public function __construct()
    {
        $this->CI = &get_instance();
        
    }

    public function __destruct() {}

    public function index()
    {
        //
    }


    /**
     * json 결과 표시 후 종료
     * @author larry
     * @since 2020.08.18
     * @param int $code 결과 코드 (0 : 정상, 1 : 실패)
     * @param string $message 결과 메시지
     * @param array $assignData 전달 배열
     * @param string $callback 콜백 함수 이름
     * @return json
     */
    public function json_exit($code = 0, $message = '', $assignData = array(), $callback = null)
    {
        $result = array(
            'code' => (int)$code,
            'msg' => $message
        );

        if (empty($assignData) === false || is_array($assignData) === true) {
            $result = array_merge($result, $assignData);
        }

        // Buffer all upcoming output...
        ob_start();

        // Send your response.
        echo json_encode($result);

        log_message('debug', print_r(array(
            'function' => __CLASS__.'->'.__FUNCTION__.' [ '.__LINE__.' ]',
            'message' => json_encode($result)
        ), true));

        // Get the size of the output.
        $size = ob_get_length();

        header('Content-Encoding: none'); // Disable compression (in case content length is compressed).
        header('Content-Length: ' . $size); // Set the content length of the response.
        header('Connection: close'); // Close the connection.

        // Flush all output.
        ob_end_flush();
        if (ob_get_level() > 0) ob_flush();
        flush();

        // Close current session (if it exists).
        if (session_id()) {
            session_write_close();
        }

        // Start your background work here.
        if (empty($callback) === false && method_exists($this->CI, $callback) === true) {
            log_message('debug', print_r(array(
                'function' => __CLASS__.'->'.__FUNCTION__.' [ '.__LINE__.' ]',
                'message' => 'callback function execute',
                'callback' => $callback
            ), true));

            $this->CI->$callback();
        }

        exit;
    }


	// =========================================================
	// 파라미터 체크용..
	// 한글-2byte, 총 90byte 발송가능
	// LSM, MMS는 2000byte 까지 발송되며, 딱 2000btye가 되어도 MMS 파일첨부가 정상적으로 됨..
	// =========================================================
	public function check_parameter($type, &$item)
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

	public function TOAST_ErrMsg($text){

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

		$msg = @$TOAST_ERR[$text];
		if ($msg == "") $msg = "알 수 없는 에러발생";

		return $msg;
	}

	public function KAKAO_ErrMsg($text){

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

		$msg = @$KAKAO_ERR[$text];
		if ($msg == "") $msg = "알 수 없는 에러발생";

		return $msg;
	}

	public function genRandomStr($length = 10) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}

	/*
     * 로그 생성 
     * @author larry
     * @since 2020.08.18
     * @param $file
     * @param array $message
     * @param $target
     */
	public function addLog($file, $message = array(), $target='api')
	{
        if ($target == 'api') {
            $filename = API_LOG;
        } else {
            //$filename = API_LOG;
        }

		$fp = fopen($filename.'/'.$file.'.txt', 'a+');
		fwrite($fp, $message.PHP_EOL);
		fclose($fp);
	}

    /**
     * @클라이언트 아이피 찾기
     * @return array|bool|false|string
     */
    public function getClientIp() {
        $ipAddress = false;

        if (getenv('HTTP_CLIENT_IP')) {
            $ipAddress = getenv('HTTP_CLIENT_IP');
        } elseif(getenv('HTTP_X_FORWARDED_FOR')) {
            $ipAddress = getenv('HTTP_X_FORWARDED_FOR');
        } elseif(getenv('HTTP_X_FORWARDED')) {
            $ipAddress = getenv('HTTP_X_FORWARDED');
        } elseif(getenv('HTTP_FORWARDED_FOR')) {
            $ipAddress = getenv('HTTP_FORWARDED_FOR');
        } elseif(getenv('HTTP_FORWARDED')) {
            $ipAddress = getenv('HTTP_FORWARDED');
        } elseif(getenv('REMOTE_ADDR')) {
            $ipAddress = getenv('REMOTE_ADDR');
        }

        return $ipAddress;
    }

    /**
     * 문자열에 한글이 포함되어 있는 지 확인
     * @author larry
     * @since 2020.08.18
     * @param string $str 문자열
     * @return bool
     */
    public function isIncludeHangul($str = '')
    {
        if (preg_match("/[\xE0-\xFF][\x80-\xFF][\x80-\xFF]/", $str)) {
            return true;
        }
        return false;
    }

	public function xmp($aData, $exit=''){
		echo "<xmp>"; 
		print_r($aData); 
		echo "</xmp>";
		if($exit)exit;
	}

	public function sql($sql, $act, $bind){
		$bcnt = 0 ;
		$fullsql = '';
		for($i = 0;$i < strlen($sql);$i++){
			if(substr($sql,$i,1) == '?'){
				$fullsql .= "'".$bind[$bcnt]."'";
				$bcnt++;
			}else{
				$fullsql .= substr($sql,$i,1);
			}
			
		}
		if($_SERVER["REMOTE_ADDR"]=="106.254.252.100" || strpos($_SERVER["REMOTE_ADDR"],"172.31.") !== false){
			print "<pre>";
			print_r($fullsql);
			print "</pre>";
		}
		if($act!="sql")exit;
	}

}
