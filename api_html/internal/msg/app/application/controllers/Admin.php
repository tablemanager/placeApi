<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin extends CI_Controller
{
	private $CI = null;
	private $API_URL = 'https://gateway.sparo.cc/internal/msg/api/';

    function __construct()
    {
        parent::__construct();
		$this->CI = &get_instance();

		if (class_exists('curl') === false) {
            $this->CI->load->library('util/curl/curl', null, 'curl'); // curl
        }
		$this->CI->load->library('Common', null, 'commLib');
        $this->load->model('CommModel', 'commModel'); // 공통 모델
		$this->load->model('MsgModel', 'msgModel'); // 메세지 모델		

    }

    public function decrypt($val)
	{
		if(!$val) return '';
		$salt = 'mIn282##1m1F?@11';
		$mode=MCRYPT_MODE_ECB;
		$enc=MCRYPT_RIJNDAEL_128;

		$iv = mcrypt_create_iv(mcrypt_get_iv_size($enc, $mode), MCRYPT_RAND);
		$val = mcrypt_decrypt($enc, $salt, pack("H*", $val), $mode, $iv );
		return rtrim($val,$val[strlen($val)-1]);
	}
   
    public function checkIP()
    {
		$res = explode(",",$_SERVER["HTTP_X_FORWARDED_FOR"]);
		$my_ip = $res[0];

		$result = $this->CI->commModel->getIP('', $my_ip);
		//$this->CI->commLib->xmp($result, 1); //test
		if(empty($result)===true){
			echo "<meta charset='utf-8'>접속이 불가한 IP입니다. (".$my_ip.")";
			exit;
		}
	}

    public function test()
    {
		//echo CI_VERSION;
		//echo $_SERVER["HTTP_X_FORWARDED_FOR"];
		$this->load->view('test');
	}

	public function msg_list()
	{			
		//$this->CI->commLib->xmp($this->input->get(), 1); exit;
		$tk = $_REQUEST["tk"];
		$ykn = $this->decrypt($tk);
		$auth = explode("_",$ykn);
		if(empty($auth)){	 //실패 시			
			$this->checkIP();
		}else{ //성공 시

		}

		$this->load->view('header');
		//$this->load->view('msg_list', array("data"=>$return));
		$this->load->view('msg_list');
		$this->load->view('footer');

	}

	public function getMsgList()
	{			
		parse_str($this->input->post('data'), $aData);
		//$this->CI->commLib->xmp($aData, 1); //test
		$result = $this->CI->msgModel->getMsgList($aData);
		//$this->CI->commLib->xmp($result, 1); //test

		if($aData["dataType"]=="list"){
			$res = array();
			$msg_type_arr = array("K" => "KAKAO", "S" => "SMS", "L" => "LMS", "M" => "MMS");
			$stat_type_arr = array("S" => "성공", "E" => "실패");
			foreach($result as $k=>$row){
				$msg_err = "";
				$temp = array();
				$temp['IDX'] = $row['IDX'];
				$temp['DATE'] = $row['REQUEST_TIME'];
				$temp['TYPE'] = $msg_type_arr[$row['MSG_TYPE']];
				$temp['TEL'] = $row['DSTADDR'];
				$temp['ORDERNO'] = $row['ORDERNO'];
				$temp['COUPONNO'] = $row['COUPONNO'];
				$temp['COUPON_TYPE'] = $row['COUPON_TYPE'];
				$temp['STAT'] = $stat_type_arr[$row['STAT']];

				if (isset($aData['IDX']) && $aData['IDX'] != '') {
					$detil = json_decode($row['RESULTS'],true);

					// 에러가 발생된 경우, 아래와 같이 에러메세지를 한글로 변경함.
					if ($row['MSG_TYPE'] == "K") {
						if ($detil[0]['result'] == 'N') $msg_err = $this->CI->commLib->KAKAO_ErrMsg($detil[0]['code']);
					}else {
						if ($detil['header']['isSuccessful'] != TRUE) $msg_err = $this->CI->commLib->TOAST_ErrMsg($detil['header']['resultCode']);
					}

					$temp['MSG_SUBJECT'] = $row['MSG_SUBJECT'];
					$temp['MSG_TEXT'] = $row['MSG_TEXT'];
					$temp['RESULTS'] = $row['RESULTS'];
					$temp['ERR_MSG'] = $msg_err;
					$temp['CALLBACK'] = $row['CALLBACK'];
					$temp['PROFILE'] = $row['PROFILE'];
					$temp['EXTVAL1'] = $row['EXTVAL1'];
					$temp['EXTVAL2'] = $row['EXTVAL2'];
					$temp['EXTVAL3'] = $row['EXTVAL3'];
					$temp['EXTVAL4'] = $row['EXTVAL4'];
					$temp['FILELOC1'] = $row['FILELOC1'];
				}
				$res[] = $temp;
			}
			echo json_encode($res);

		}else{
			echo json_encode($result);
		}
	}

	public function sendMsg()
	{		
		$userhp = $this->input->post('tel');                                                       // 수신번호
		$callBack = $this->input->post('callback');                                                // 발신자번호
		$subject = $this->input->post('subject') != '' ? $this->input->post('subject') : '';                   // 템플릿 코드 또는 제목
		$msg = $this->input->post('text');                                                         // 내용
		$orderNo = $this->input->post('orderno');                                                  // 주문번호
		$type = $this->input->post('type');                                                        // 메세지 타입
		$profile = $this->input->post('profile');                                                  // 프로필키
		$ext1 = $this->input->post('ext1') ? $this->input->post('ext1') : "";
		$ext2 = $this->input->post('ext2') ? $this->input->post('ext2') : ""; 
		$ext3 = $this->input->post('ext3') ? $this->input->post('ext3') : "";
		$ext4 = $this->input->post('ext4') ? $this->input->post('ext4') : "";

		$Authorization = $ext2; //인증키

		$pinNo = $this->input->post('couponno');
		$pinType = $this->input->post('coupon_type');
		$mmsFile = $this->input->post('file');

		if(empty($Authorization)===true){
			$this->commLib->json_exit(9, "발급키가 존재하지 않습니다.(Authorization)");
		}
		if(empty($type)===true){
			$this->commLib->json_exit(9, "메세지 타입이 존재하지 않습니다.(type)");
		}

		$url = $this->API_URL.$type;
		$data = array(
			"dstAddr" => str_replace("-", "", $userhp),
			"callBack" => str_replace("-", "", $callBack),
			"msgSubject" => $subject,
			"msgText" => $msg
		);

		$data = json_encode($data, JSON_UNESCAPED_UNICODE);

		$this->CI->curl->setHeader('Content-type', 'application/json;charset=UTF-8');
		$this->CI->curl->setHeader('Authorization', $Authorization);
		$this->CI->curl->post($url, $data);
		$result = $this->CI->curl->response;  
		
		echo $result;

		$this->CI->commLib->addLog(date('Ymd').'_sendMsg_log', print_r(array(
			'date' => date('Y.m.d H:i:s'),
			'url' => $url,
			'ip' => $this->input->ip_address(),
			'Authorization' => $Authorization,
			'data' => json_decode($data, true),
			'result' => json_decode($result, true)
		), true), 'api');
        
    }

	//키발급 사용자 정보
	public function getAuth(){
		$result = $this->CI->commModel->getAuth($this->input->post('code'));
		//$this->CI->commLib->xmp($result, 1); //test
		$res = array();

		if(empty($this->input->post('code'))==false){
			$res = $result;
		}else{
			foreach($result as $k=>$row){
				$msg_err = "";
				$temp = array();
				$temp['IDX'] = $row['IDX'];
				$temp['NAME'] = $row['NAME'];
				$temp['CODE'] = $row['CODE'];
				$temp['PROFILE_ID'] = $row['PROFILE_ID'];
				$temp['REGDATE'] = $row['REGDATE'];
				$temp['SEND_CNT'] = $row['SEND_CNT'];

				$res[] = $temp;
			}
		}
		echo json_encode($res);

	}
	
	//키 생성
	public function genRandomStr($length = 15) {
		$characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		echo $randomString;
	}

	//키발급 사용자 저장
	public function setAuth(){
		$idx = $this->input->post('idx');
		$name = $this->input->post('name');
		$profile_id = $this->input->post('profile_id');
		$code = $this->input->post('code');

        if (empty($name) === true || empty($profile_id) === true || empty($code) === true) {            
			//$this->commLib->json_exit(9, $this->input->post());
			$this->commLib->json_exit(9, "저장 오류가 발생하였습니다.(데이타 부족)");
        }

		$result = $this->CI->commModel->setAuth($idx, $name, $profile_id, $code);
		if($result){		
			$this->commLib->json_exit(0, "저장완료");
		}else{
			$this->commLib->json_exit(9, "저장 오류가 발생하였습니다.");
		}

	}
	

	//IP 정보
	public function getIP(){
		$result = $this->CI->commModel->getIP($this->input->post('idx'));
		//$this->CI->commLib->xmp($result, 1); //test
		$res = array();

		if(empty($this->input->post('idx'))==false){
			$res = $result;
		}else{
			foreach($result as $k=>$row){
				$msg_err = "";
				$temp = array();
				$temp['IDX'] = $row['IDX'];
				$temp['NAME'] = $row['NAME'];
				$temp['IP'] = $row['IP'];
				$temp['REGDATE'] = $row['REGDATE'];

				$res[] = $temp;
			}
		}
		echo json_encode($res);

	}

	//IP 저장
	public function setIP(){
		$idx = $this->input->post('idx');
		$name = $this->input->post('name');
		$ip = $this->input->post('ip');

        if (empty($name) === true || empty($ip) === true) {            
			//$this->commLib->json_exit(9, $this->input->post());
			$this->commLib->json_exit(9, "저장 오류가 발생하였습니다.(데이타 부족)");
        }

		$result = $this->CI->commModel->setIP($idx, $name, $ip);
		if($result){		
			$this->commLib->json_exit(0, "저장완료");
		}else{
			$this->commLib->json_exit(9, "저장 오류가 발생하였습니다.");
		}

	}
	

}
