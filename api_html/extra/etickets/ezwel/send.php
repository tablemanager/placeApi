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

$logtp = "send";

// 2달지난 로그 지운다.
dellog($logpath, $logtp);

// 로그 기록
$fnm = 'log_'.date('Ymd')."{$logtp}.log";
$fp = fopen("{$logpath}{$fnm}", 'a+');
fwrite($fp, "\n\n================================================================================\n");
fwrite($fp, date("Y-m-d H:i:s")." 주문접수\n");
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
$accessip = array("115.68.42.2",
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
	"52.78.174.3",
	"211.180.161.90",
	"103.60.126.37",

    "211.180.161.66", // 이지웰 운영서버
);
__accessip($accessip);

$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드

switch($apimethod) {

	case 'POST':

		$_model = new ezwel_model();
		$crypto = new Crypto();
		$_ezwel = new ezwel();

///// 로그찍기
$tmp = array();
foreach($_POST as $k => $v){
    $tmp[$k] = $crypto->decrypt($v);
}
fwrite($fp, "수신전문 복호화 : ");
fwrite($fp, print_r($tmp, true));
/////


//		$_fillter['ecpnTrid'] = $_POST['ecpnTrid'];
		//$_fillter['ecpnTrid'] = base64_decode($crypto->decrypt($_POST['ecpnTrid']));
		$_fillter['ecpnTrid'] = $crypto->decrypt($_POST['ecpnTrid']);
		$_fillter['optionSeq'] = $crypto->decrypt($_POST['optionSeq']);

		$_result = $_model->getEzwelRese($_fillter);
        // [ecpnTrid] => 165331 

		if (count($_result) > 0){
			//예약있음

//			$_ezwel->set_encrypt_base64( $_POST[$_field]));

			//$_data['result'] = $_ezwel->set_encrypt_base64('0001');
			$_data['result'] = '0001';
			$_data['message'] = $crypto->encrypt('FAIL');
			$_data['returnValue'] = $crypto->encrypt('');
			//$_data['cspCd'] = $crypto->encrypt('30000068');
			//$_data['cspCd'] = $_POST['cspCd'];
			//$_data['cspGoodsCd'] = $_POST['cspGoodsCd'];
			$_data['cspCd'] = $crypto->decrypt($_POST['cspCd']);
			$_data['cspGoodsCd'] = $crypto->decrypt($_POST['cspGoodsCd']);
			$_data['ecpnTrid'] = $_POST['ecpnTrid'];
			$_data['ecpnRn'] = '';

			echo __makeXMLa("ResponseEzwel" , $_data);
            logresult($fp, $crypto, $_data);
			exit;

		} else {

			//$_itemsExt = $_model->getPcmsItemsExt('EZWOW' , '' , base64_decode($crypto->decrypt($_POST['cspGoodsCd'])));
            // 채널번호 :  현대이지웰(API) : 3944
            // cspGoodsCd : cm 상품코드임
			//$_itemsExt = $_model->getPcmsItemsExt('3944' , base64_decode($crypto->decrypt($_POST['cspGoodsCd'])), '');
			//$_itemsExt = $_model->getPcmsItemsExt('3944', $crypto->decrypt($_POST['cspGoodsCd']), '');

            // 채널 상품코드로 상품 정보 조회
			$_itemsExt = $_model->getPcmsItemsExt('3944', '', $crypto->decrypt($_POST['optionSeq']));

			//플레이스엠 상품코드 가져와서
			//https://{apiurl}/extra/agency/v2/dealcode/{플레이스엠 상품코드} 우리 API를 통해서 예약을 넣는다

			if (count($_itemsExt) == 0){

				//$_data['result'] = $_ezwel->set_encrypt_base64('0002');
				$_data['result'] = '0002';
				$_data['message'] = $crypto->encrypt('Internal Error');
				$_data['returnValue'] = $crypto->encrypt('');
				//$_data['cspCd'] = $crypto->encrypt('30000068');
				//$_data['cspCd'] = $_POST['cspCd'];
				//$_data['cspGoodsCd'] = $_POST['cspGoodsCd'];
                $_data['cspCd'] = $crypto->decrypt($_POST['cspCd']);
			    $_data['cspGoodsCd'] = $crypto->decrypt($_POST['cspGoodsCd']); 
				$_data['ecpnTrid'] = $_POST['ecpnTrid'];
				$_data['ecpnRn'] = '';

				echo __makeXMLa("ResponseEzwel" , $_data);
                logresult($fp, $crypto, $_data);
				exit;

			}

			$is_insert = false;
			$_insert_data = array();

            // optionQty 는 전체 주문 수량임.
            // 이지웰측(박수민 전임)에서 수량은 총 수량이나, 수량만큼 주문 API를 호출하므로, 수량 1개인 것처럼 처리 해야 함 이라고 회신줌.
            // db 기록에는 수량을 총 수량을 넣으나, cm에 주문 등록할때는 수량을 무조건 1로 해서(생략할 경우 1로 처리) 등록해야 함.
			$_para_where = array("ecpnTrid" , "cspCd" , "goodsCd" ,"optionSeq","cspGoodsCd","orderNum" , "rcvPNum" ,"sendPNum","mmsTitle","addMsg","optionQty");
			foreach ($_para_where as $ss => $_field){
				if (array_key_exists($_field, $_POST)) {
					if (!empty($_POST[$_field])){
						$is_insert = true;
						//$_insert_data[$_field] = base64_decode($crypto->decrypt($_POST[$_field]));
						$_insert_data[$_field] = $crypto->decrypt($_POST[$_field]);
					}
				}
			}

			if ($is_insert === true){

				//step 1 이지웰 예약 테이블 insert
//				$_model->setReturn_mode(true);
				$_model->setEzwelRese($_insert_data);
				//step 2 ordermts insert

                // 인서트한 데이터 다시 조회
                $_inserted['ecpnTrid'] = $_insert_data['ecpnTrid'];
                $_inserted['orderNum'] = $_insert_data['orderNum'];
                $_reseult_inserted = $_model->getEzwelRese($_inserted);

                $_seq = '';
                if (count($_reseult_inserted) > 0){
                    $_seq = $_reseult_inserted[0]['seq'];
                }else{
                    unset($_seq);
                }

				//$_order['orderNo'] = base64_decode($crypto->decrypt($_POST['ecpnTrid']));
				//$_order['orderNo'] = $crypto->decrypt($_POST['ecpnTrid']);
               
                // 채널주문번호 : 
                // 2023. 11. 28. 오후 2:14 메일로 회신 받음
                // 꼭 주문번호 조합으로 사용해야 한다면 orderNum + ecpnTrid 를 고려부탁드립니다.
				$_order['orderNo'] = $crypto->decrypt($_POST['orderNum'])."_".$crypto->decrypt($_POST['ecpnTrid']);
				//$_order['userName'] = base64_decode($crypto->decrypt($_POST['rcvPNum']));
				//$_order['userName'] = $crypto->decrypt($_POST['rcvPNum']);
                // 20230613 tony 이지웰측(박수민 전임)에서 수취인명으로 문자 발송될 수 있도록 요청. 
                // 이에 대응하여, 플엠시스템에는 주문자명에 수취인명이 들어감 설명하였음. 이지웰측에서는 수취인명이 기록되니 문제 없다고 함
                //       
				//$_order['userName'] = $crypto->decrypt($_POST['orderUserNm']);
				$_order['userName'] = $crypto->decrypt($_POST['recvUserNm']);
				//$_order['userHp'] = base64_decode($crypto->decrypt($_POST['sendPNum']));
				//$_order['userHp'] = $crypto->decrypt($_POST['sendPNum']);
				$_order['userHp'] = $crypto->decrypt($_POST['rcvPNum']);
				$_order['postCode'] = '';
				$_order['addr1'] = '';
				$_order['addr2'] = '';
				$_order['orderDesc'] = '';
				$_order['expDate'] = $_itemsExt[0]['usedate'];

				$_result_place_api_code = $_ezwel->order_placem($_itemsExt[0]['pcmsitem_id'], $_order); 
/*
$order[]= array("OrderNo" => $res,
                         "CouponNo" => $cpno);

         $result = array("Code" => "1000",
                         "Msg" => "성공",
                         "Result" => $order);
*/
//                $jsonde_result = json_decode($_result_place_api_code);
              
                // 주문표준api 호출
                fwrite($fp, "주문 플레이스엠 표준api 호출응답 : ["); 
                fwrite($fp, print_r($_result_place_api_code, true)); 
//                fwrite($fp, "]\n   => json decoded : ["); 
//                fwrite($fp, print_r($jsonde_result, true)); 
                fwrite($fp, "]\n"); 
/*
주문표준api 호출응답 : [stdClass Object
(
    [Code] => 1000
    [Msg] => 성공
    [Result] => Array
        (
            [0] => stdClass Object
                (
                    [OrderNo] => 20230613_Cuyt1YMIrE67
                    [CouponNo] => 
                )

        )

)
]
   => json decoded : []
*/

				if (isset($_result_place_api_code->Code) && $_result_place_api_code->Code == "1000"){
					//step 3 ezwel output XML
//					echo "SUCCESS";
					$_data = array();
					//$_data['result'] = $_ezwel->set_encrypt_base64('0000');
					$_data['result'] = '0000';
					$_data['message'] = $crypto->encrypt('SUCCESS');
					$_data['returnValue'] = $crypto->encrypt('');
					//$_data['cspCd'] = $crypto->encrypt('30000068');
					//$_data['cspCd'] = $_POST['cspCd'];
					//$_data['cspGoodsCd'] = $_POST['cspGoodsCd'];
                    $_data['cspCd'] = $crypto->decrypt($_POST['cspCd']);
                    $_data['cspGoodsCd'] = $crypto->decrypt($_POST['cspGoodsCd']); 
					$_data['ecpnTrid'] = $_POST['ecpnTrid'];

                    if(isset($_result_place_api_code->Result[0]->CouponNo)){
                        if(strlen($_result_place_api_code->Result[0]->CouponNo > 0)){
                            // 20240607 tony [뽀아빌] 현대이지웰 연동 확인 요청건 https://placem.atlassian.net/browse/P2CCA-613
                            // 암호화 모듈 변수 오타 수정 및 시험요청
					        $_data['ecpnRn'] = $crypto->encrypt($_result_place_api_code->Result[0]->CouponNo);
                        }else{
                            // 20230920 tony 
                            // https://placem.atlassian.net/browse/PM2104COBBS-13   [이지웰]이쿠폰 주문연동 쿠폰번호영역 전문번호 발송 개발 요청
                            // 쿠폰번호를 실시간으로 발송해줄 수 없는 시설의 경우 쿠폰번호 대신 전문번호(TRID)와 동일하게 입력
					        //$_data['ecpnRn'] = "";
					        $_data['ecpnRn'] = $_POST['ecpnTrid'];
                        }
                    }else{
                        // 20230920 tony 
                        // https://placem.atlassian.net/browse/PM2104COBBS-13   [이지웰]이쿠폰 주문연동 쿠폰번호영역 전문번호 발송 개발 요청
                        // 쿠폰번호를 실시간으로 발송해줄 수 없는 시설의 경우 쿠폰번호 대신 전문번호(TRID)와 동일하게 입력 
					    //$_data['ecpnRn'] = "";
					    $_data['ecpnRn'] = $_POST['ecpnTrid'];
                    }

                    // 이지웰은 주문번호가 쿠폰번호임.
                    // 시설에서 쿠폰번호를 주는 경우는 어쩐다지??? 
                    // => CM에 조회해보니깐 주문번호가 쿠폰번호로도 셋팅되므로 주문번호를 쿠폰번호로 리턴헤주자.
					// $_data['ecpnRn'] = $ctrypto->encrypt($_result_place_api_code->Result[0]->OrderNo);

					echo $rtn = __makeXMLa("ResponseEzwel" , $_data);

                    // 이지웰에 주문 전송 성공 플레그 셋팅
                    $_model->updateChConfirm('Y', $_seq);

                    // PCMS 주문번호 갱신
                    // 20240221 11:59 패치:CouponNo가 리턴되던거를 OrderNo로 패치
                    $pcms_orderno = (isset($_result_place_api_code->Result[0]->OrderNo))?$_result_place_api_code->Result[0]->OrderNo:"";
                    $_model->updateplace_orderno($pcms_orderno, $_seq);

                    fwrite($fp, "주문등록 ezwel에 응답:");
                    logresult($fp, $crypto, $_data);
                    fwrite($fp, $rtn);
					exit;

				} else {
					//step 3 ezwel output XML
					$_data = array();
					//$_data['result'] = $_ezwel->set_encrypt_base64('0003');
					$_data['result'] = '0003';
					$_data['message'] = $crypto->encrypt('FAIL');
					$_data['returnValue'] = $crypto->encrypt('');
					//$_data['cspCd'] = $crypto->encrypt('30000068');
					//$_data['cspCd'] = $_POST['cspCd'];
					//$_data['cspGoodsCd'] = $_POST['cspGoodsCd'];
                    $_data['cspCd'] = $crypto->decrypt($_POST['cspCd']);
                    $_data['cspGoodsCd'] = $crypto->decrypt($_POST['cspGoodsCd']);
					$_data['ecpnTrid'] = $_POST['ecpnTrid'];
					$_data['ecpnRn'] = '';

                    fwrite($fp, "주문등록 ezwel에 응답:");
					echo __makeXMLa("ResponseEzwel" , $_data);
                    logresult($fp, $crypto, $_data);
					exit;

				}
			}
		}

		break;

}

/*
function dellog(){
    $sdate = date('Y-m-d');
    $ldate  = date('Ymd', strtotime('-2 month', strtotime($sdate)));

    //echo $ldate;
    $dellog = "/home/sparo.cc/api_html/extra/etickets/ezwel/txt/log_{$ldate}send.log";
    //echo "삭제대상 로그 찾기 $dellog ";

    if(file_exists($dellog)){
        unlink($dellog);
        echo "$dellog log file deleted now.\n\n";
    }else{
        // 굳이 로그남길 필요가 없을듯
        //echo "[상태검사] 삭제할 로그파일이 없는 상태입니다.\n\n";
    }
}

// 비문 결과를 로그 남김(비문/평문)
// fp : file desc
// crypto : 복호화 모듈
// arEncData : 비문 데이터
function logresult($fp, $crypto, $arEncData){
 
    // 일시 기록
    fwrite($fp, date("Y-m-d H:i:s")."\n");

    // 암호화된 데이터 
    fwrite($fp, print_r($arEncData, true));

    $arDecData = array();
    foreach($arEncData as $k => $v){
        $arDecData[$k] = base64_decode($crypto->decrypt($v));
    }

    fwrite($fp, print_r($arDecData, true));
}
*/

?>
