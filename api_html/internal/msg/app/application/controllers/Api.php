<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api extends CI_Controller
{

	private $API_URL = 'http://gateway.sparo.cc/internal/msg/api/';

    function __construct()
    {
        parent::__construct();
		$this->CI = &get_instance();

		if (class_exists('curl') === false) {
            $this->CI->load->library('util/curl/curl', null, 'curl'); // curl
        }
		$this->CI->load->library('Common', null, 'commLib');
		$this->CI->load->library('KakaoAlimtalk', null, 'kakaoLib');
        $this->load->model('CommModel', 'commModel'); // 공통 모델
		$this->load->model('MsgModel', 'msgModel'); // 메세지 모델
    }

    public function __destruct() {}

    public function test()
    {
		//echo CI_VERSION;
		//echo $_SERVER["HTTP_X_FORWARDED_FOR"];
		$this->load->view('test');
	}

	public function errorLog($msg){
		$this->CI->commLib->addLog(date('Ymd').'_error_log', print_r(array(
			'errorLog' => date('Y.m.d H:i:s'),
			'ip' => $this->input->ip_address(),
			'file_get_contents' => file_get_contents('php://input'),
			'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'],
			'REQUEST_URI' => $_SERVER['REQUEST_URI'],
			'msg' => $msg,
			'header' => getallheaders(),
			'Authorization' => $this->input->get_request_header('Authorization')
		), true), 'api');		
		$this->commLib->json_exit(9, $msg);
	}

	public function checkParam($type){
		$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
		if($apimethod != "POST"){
			$this->errorLog('POST방식이 아닙니다. (REQUEST_METHOD)');
		}
		$Authorization = $this->input->get_request_header('Authorization'); //$apiheader['Authorization'];
		if(empty($Authorization)===true){
			$this->errorLog('발급키가 존재하지 않습니다.(Authorization)');
		}
		$authResult = $this->CI->commModel->getAuth($Authorization);
		if(empty($authResult)===true){
			$this->errorLog('발급키가 등록되어 있지 않습니다.(Authorization)');
		}

		$rData = trim(file_get_contents('php://input'));
		$rData = json_decode($rData,TRUE);
		$dstAddr = $rData["dstAddr"];                           // 수신번호
		$callBack = $rData["callBack"];                        // 발신자번호
		$msgSubject = $rData["msgSubject"];             // 템플릿 코드 또는 제목
		$msgText = $rData["msgText"];                // 내용
		$mmsFile = @$rData["mmsFile"];                // 파일 위치
		$kakao_profile = @$rData["kakao_profile"];                // 카카오 알림톡 키
		$kakao_button = @$rData["kakao_button"];                // 카카오 버튼

		if(empty($dstAddr)===true){
			$this->errorLog('수신번호가 존재하지 않습니다.(dstAddr)');
		}
		if(empty($callBack)===true){
			$this->errorLog('발신자 번호가 존재하지 않습니다.(callBack)');
		}
		if(empty($msgSubject)===true){
			$this->errorLog('제목이 존재하지 않습니다.(msgSubject)');
		}
		if(empty($msgText)===true){
			$this->errorLog('내용이 존재하지 않습니다.(msgText)');
		}
		if(empty($type)===true){			
			$this->errorLog('메세지 타입이 존재하지 않습니다.(type)');
		}
		if(strtoupper($type)!="SMS" && strtoupper($type)!="MMS" && strtoupper($type)!="LMS" && strtoupper($type)!="KAKAO"){
			$this->errorLog('메세지 타입이 정확하지 않습니다.(type)');
		}
		/*
		$FILE_PATH1 = "";
		if($mmsFile){
			$ext = pathinfo($mmsFile, PATHINFO_EXTENSION); 
			$FILE_PATH1 = MMS_FILE_PATH.date("YmdHis").".".$ext;
			$this->CI->commLib->addLog(date('Ymd').'_mmsfile_log', print_r(array(
				'date' => date('Y.m.d H:i:s'),
				'ip' => $this->input->ip_address(),
				'mmsFile' => $mmsFile,
				'FILE_PATH1' => $FILE_PATH1
			), true), 'api');
			$this->CI->curl->download($mmsFile, $FILE_PATH1); // 파일 서버에 다운로드
		}
		*/
		$FILE_PATH1 = $mmsFile;

		$aData = array(
			"type" => $type,
			"dstAddr" => $dstAddr,
			"callBack" => $callBack,
			"msgSubject" => $msgSubject,
			"msgText" => $msgText,
			"Authorization" => $Authorization,
			"FILE_CNT" => $FILE_PATH1 ? 1 : 0, //클라이언트가 실제로 체크한 전송파일 개수
			"FILE_PATH1" => $FILE_PATH1, //전송파일1 위치
			"kakao_profile" => $kakao_profile, // 카카오 알림톡 키
			"kakao_button" => $kakao_button // 카카오 버튼
		);

		return $aData;
	}
	
	public function SMS()
	{		
		$aData = $this->checkParam('SMS');
		$insert_id = $this->CI->msgModel->addSMS($aData);			

		if($insert_id){
			$log_id = $this->CI->msgModel->addMsgResult($aData, "S");
			if($log_id){
				$this->commLib->json_exit(100, '발송완료');			
			}else{
				$this->errorLog('발송 완료되었지만 로그 기록에 실패하였습니다.');			
			}		
		}else{
			$log_id = $this->CI->msgModel->addMsgResult($aData, "E");
			$this->errorLog('SMS 발송실패');			
		}		
    }
	
	public function MMS() //파일전송이 있으면 MMS, 없으면 LMS
	{	
		$aData = $this->checkParam('MMS');
		$insert_id = $this->CI->msgModel->addMMS($aData);

		if($insert_id){			
			$log_id = $this->CI->msgModel->addMsgResult($aData, "S");
			if($log_id){
				$this->commLib->json_exit(100, '발송완료');			
			}else{
				$this->errorLog('발송 완료되었지만 로그 기록에 실패하였습니다.');			
			}
		}else{
			$log_id = $this->CI->msgModel->addMsgResult($aData, "E");
			$this->errorLog('MMS 발송실패');			
		}	


	}

	public function LMS()
	{	
		$aData = $this->checkParam('LMS');
		$insert_id = $this->CI->msgModel->addMMS($aData);

		if($insert_id){
			$log_id = $this->CI->msgModel->addMsgResult($aData, "S");
			if($log_id){
				$this->commLib->json_exit(100, '발송완료');			
			}else{
				$this->errorLog('발송 완료되었지만 로그 기록에 실패하였습니다.');			
			}
		}else{
			$log_id = $this->CI->msgModel->addMsgResult($aData, "E");
			$this->errorLog('LMS 발송실패');			
		}	
	}

	public function KAKAO()
	{	
		$aData = $this->checkParam('KAKAO');
		$result = $this->CI->kakaoLib->send($aData);

		if($result == "ok"){
			$log_id = $this->CI->msgModel->addMsgResult($aData, "S");
			if($log_id){
				$this->commLib->json_exit(100, '발송완료');			
			}else{
				$this->errorLog('발송 완료되었지만 로그 기록에 실패하였습니다.');			
			}		
		}else{
			$log_id = $this->CI->msgModel->addMsgResult($aData, "E");
			$this->errorLog($result);			
		}	
	}
	

	public function KAKAO_TEMPLATE()
	{	
		$rData = trim(file_get_contents('php://input'));
		$rData = json_decode($rData,TRUE);
		if(empty($rData["kakao_profile"])===true){
			$rData["kakao_profile"] = $this->input->post("kakao_profile");
		}
		if(empty($rData["kakao_profile"])===true){
			$this->errorLog('kakao_profile 이 존재하지 않습니다.');
		}
		$result = $this->CI->kakaoLib->template_list($rData);

		if($result){
			echo json_encode($result);	
		}else{			
			$this->errorLog($rData["kakao_profile"]." 키 오류");			
		}	
	}

}
