<?php
/**
 * Created by PhpStorm.
 * User: PLACEM
 * Date: 2019-07-08
 * Time: 오후 5:41
 */

// 로그기록 함수
include './logutil.php';

// 로그기록 패스. 맨뒤에 / 포함
$logpath = "/home/sparo.cc/api_html/extra/etickets/ezwel/txt/";

// 2달지난 로그 지운다.
$logtp = "info";
dellog($logpath, $logtp);

// 로그 기록
$fnm = 'log_'.date('Ymd')."{$logtp}.log";
$fp = fopen("{$logpath}{$fnm}", 'a+');
fwrite($fp, "\n\n================================================================================\n");
fwrite($fp, date("Y-m-d H:i:s")." 주문조회 접수\n");
// 입력값 기록
fwrite($fp, "_SERVER\n");
fwrite($fp, print_r($_SERVER, true));
fwrite($fp, "_POST\n");
fwrite($fp, print_r($_POST, true));
fwrite($fp, "_GET\n");
fwrite($fp, print_r($_GET, true));

//===================================================================
// 업무프로세스 시작
header('Content-type: text/xml');

include '/home/sparo.cc/lib/placem_helper.php';
include '/home/sparo.cc/ezwel_script/class/class.ezwel.php';
include '/home/sparo.cc/ezwel_script/class/ezwel_model.php';

// ACL 확인
$accessip = array(
	"115.68.42.2",
	"115.68.42.8",
	"115.68.42.130",
	"52.78.174.3",
	"106.254.252.100",
	"115.68.182.165",
	"13.124.139.14",
	"218.39.39.190",
	"114.108.179.112",
	"13.209.232.254",
	"13.124.215.30",
	"221.141.192.124",
	"106.254.252.100",
	"211.180.161.90",
	"52.78.174.3",
	"103.60.126.37",

    "211.180.161.66", // 이지웰 운영서버
);
__accessip($accessip);

$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드

//echo "메서드:[$apimethod]\n";

switch($apimethod) {

	case 'POST':

		$_model = new ezwel_model();
		$crypto = new Crypto();
		$_ezwel = new ezwel();

		$_ezwel_data = array();

// 복호화 데이터 로그
//if(__get_ip()=="106.254.252.100"){
//fwrite($fp, "_POST 복호화\n");
//$_dePOST = $_POST;
//foreach($_dePOST as $k => $v){
//    $_dePOST[$k] = $crypto->decrypt($v);
//}
//fwrite($fp, print_r($_dePOST, true));
//print_r($_dePOST);
//}

		//$_para_where = array("cspCd" , "cspGoodsCd" , "ecpnTrid", "orderNum" );
		//$_para_where = array("ecpnTrid", "orderNum");
		$_para_where = array("cspCd", "cspGoodsCd", "ecpnTrid");

		foreach ($_para_where as $ss => $_field){
			if (array_key_exists($_field, $_POST)) {
				if (!empty($_POST[$_field])){
					$is_insert = true;
					//$_ezwel_data[$_field] = base64_decode($crypto->decrypt($_POST[$_field]));
					$_ezwel_data[$_field] = $crypto->decrypt($_POST[$_field]);
				}
			}
		}

        // 주문정보 조회
        $ticketinfo = $_model->getEzwelRese($_ezwel_data);
        //echo "주문정보 조회:";
        //print_r($ticketinfo);
        if(count($ticketinfo) != 1){
            $_return_data = array();

            $_return_data['result'] = '0002';
            $_return_data['message'] = $crypto->encrypt('FAIL-data row count('.count($ticketinfo).')');
            $_return_data['returnValue'] = $crypto->encrypt('');
            $_return_data['cspCd'] = $crypto->decrypt($_POST['cspCd']);
            $_return_data['cspGoodsCd'] = $crypto->decrypt($_POST['cspGoodsCd']);
            $_return_data['ecpnTrid'] = $crypto->decrypt($_POST['ecpnTrid']);
            // 정보 조회 실패 했으므로 안보낸다.
            //$_return_data['useYn'] = "N";
            //$_return_data['accountYn'] = "N";
            //$_return_data['sendYn'] = "N";
            //$_return_data['useStartDt'] = '';
            //$_return_data['useEndDt'] = '';
            //$_return_data['useDate'] = '';

            echo __makeXMLa("ResponseEzwel", $_return_data);
        }else{
    
    		//$_fillter['ch_orderno'] = $_ezwel_data['ecpnTrid'];
            // 채널주문번호 조합하여 찾아냄
    		$_fillter['ch_orderno'] = $ticketinfo[0]['orderNum']."_".$ticketinfo[0]['ecpnTrid'];
            // 주문번호 설정이 안되고 있었는데 나중에 20240131 오후부터 설정됨
            // 플레이스엠 주문번호 찾아냄
    		$_fillter['orderno'] = $ticketinfo[0]['place_orderno'];
            // 채널번호 :  현대이지웰(API) : 3944
            $_fillter['ch_id'] = '3944';
    		//print_r($_fillter);exit;
    
            // 주문정보 조회
    		$_result = $_model->getOrderMtsList($_fillter);
    		//print_r($_result);
    		if (count($_result) > 0){
    			// 예약있음 
    
                // 사용상태
    			$useYn = $_result[0]['usegu'] == "2"?"N":"Y";
                // 결제상태
    			$accountYn = $_result[0]['usegu'] == "2" ?"Y":"N";
    
    			if ($_result[0]['state'] == "취소"){
    				$useYn = "C";
    				$accountYn = "C";
    			}
    
    			$useEndDt = trim(str_replace("-","",$_result[0]['usedate']));
    			$useDate = trim(str_replace("-","",$_result[0]['usegu_at']));
    
    //			useStartDt  유효기간 시작일은 일단 패스
    			$_return_data = array();
    
    			$_return_data['result'] = '0000';
    			$_return_data['message'] = $crypto->encrypt('SUCCESS');
    			$_return_data['returnValue'] = $crypto->encrypt('');
                $_return_data['cspCd'] = $crypto->decrypt($_POST['cspCd']);
                $_return_data['cspGoodsCd'] = $crypto->decrypt($_POST['cspGoodsCd']);
                $_return_data['ecpnTrid'] = $crypto->decrypt($_POST['ecpnTrid']);
    			$_return_data['useYn'] = $useYn;
    			$_return_data['accountYn'] = $accountYn;
    			$_return_data['sendYn'] = $_result[0]['smsgu'];
    			$_return_data['useStartDt'] = '';
    			$_return_data['useEndDt'] = $useEndDt;
    			$_return_data['useDate'] = $useDate;
    
    			echo __makeXMLa("ResponseEzwel" , $_return_data); 
    		} else {
    			//예약없음
    
    			$_return_data = array();
    
    		    $_return_data['result'] = '0001';
    			$_return_data['message'] = $crypto->encrypt('FAIL');
    			$_return_data['returnValue'] = $crypto->encrypt('');
                $_return_data['cspCd'] = $crypto->decrypt($_POST['cspCd']);
                $_return_data['cspGoodsCd'] = $crypto->decrypt($_POST['cspGoodsCd']);
                $_return_data['ecpnTrid'] = $crypto->decrypt($_POST['ecpnTrid']);
    			//$_return_data['useYn'] = "N";
    			//$_return_data['accountYn'] = "N";
    			//$_return_data['sendYn'] = "N";
    			//$_return_data['useStartDt'] = '';
    			//$_return_data['useEndDt'] = '';
    			//$_return_data['useDate'] = '';
    
    			echo __makeXMLa("ResponseEzwel", $_return_data); 
    		} 
        }
        break;
    }

fwrite($fp, "응답 데이터 : [");
logresult($fp, $crypto, $_return_data);
fwrite($fp, "]");

//$_data['result'] = '0000';
//$_data['message'] = 'SUCCESS';
//$_data['returnValue'] = '';
//$_data['cspCd'] = '0';
//$_data['cspGoodsCd'] = '0';
//$_data['useYn'] = 'N';
//$_data['accountYn'] = 'Y';
//$_data['sendYn'] = 'Y';
//$_data['useStartDt'] = 'yyyymmdd';
//$_data['useEndDt'] = 'yyyymmdd';
//$_data['useDate'] = 'yyyymmdd';
//
//echo __makeXMLa("ResponseEzwel" , $_data);
