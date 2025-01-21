<?php

/*
 *
 * 휘닉스파크 연동 인터페이스
 *
 * 작성자 : 미카엘
 * 작성일 : 2018-12-13
 *
 * 조회(GET)			: https://gateway.sparo.cc/everland/sync/{BARCODE}
 * 발권(POST)		: https://gateway.sparo.cc/everland/sync/{BARCODE}
 * 발권취소(PATCH)	: https://gateway.sparo.cc/everland/sync/{BARCODE}
 */

//http://extapi.sparo.cc/hanwha/info
//http://extapi.sparo.cc/hanwha/order
//http://extapi.sparo.cc/hanwha/cancel

require_once('/home/sparo.cc/Library/M_ConnSparo2.php'); /* 주문 테이블 공통 클래스 : 수정 및 등록은 미카엘에게 요청 */
require_once('/home/sparo.cc/Library/messagelib.php');   /* 메세지 공통 클래스 : 수정 및 등록은 미카엘에게 요청 */

require_once('/home/sparo.cc/smartix_script/lib/smartixmodel.php');   /* 스마틱스 DB 클래스 */

header("Content-type:application/json");

// ACL 확인
$accessip = array("115.68.42.2",
    "115.68.42.8",
    "115.68.42.130",
    "52.78.174.3",
    "106.254.252.100",
    "115.68.182.165",
    "13.124.139.14",
    "13.209.232.254",
	"13.124.215.30"
);

if(!in_array(get_ip(),$accessip)){
    header("HTTP/1.0 401 Unauthorized");
    $res = array("RESULT"=>false,"MSG"=>"아이피 인증 오류 : ".get_ip());
    echo json_encode($res);
    exit;
}

$para = $_GET['val']; // URI 파라미터

$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders();            // http 헤더

$jsonreq = trim(file_get_contents('php://input'));
$data = json_decode($jsonreq,TRUE);
/*request -----------------------------

$data = array(
    "NAME" => "미카엘",
    "TEL" => "",
    "USEDATE" => "20190331",
    "ORDERNO" => "20190228HM12345",
    "SELLCODE" => 12345 (플레이스엠상품코드)
);
$json = json_encode($data);

responce ----------------------------
{
    "couponno": "1234567899988776"
}
*/
switch ($para){
    case 'order':

    break;

    case 'info':

        $hanwhamodel = new hanwhamodel();
        $couponrow = $hanwhamodel->select_hanwha_coupon_couponno($data['couponno']);

        if($couponrow == null){
            $res = json_encode(array(
                "RESULT"=>false,
                "MSG"=>"존재하지 않는 쿠폰입니다."
            ));
        }else{
            $hanwha = new \Hanwha\Hanwha();
            //쿠폰 상태 조회
            $_data_result = $hanwha->searchDs($couponrow['REPR_CPON_SEQ'], $couponrow['REPR_CPON_INDICT_NO'],$couponrow['ISSUE_DATE']);
            if(!$_data_result) {
                $res = json_encode(array(
                    "RESULT"=>false,
                    "MSG"=>"시스템오류. 통신에 실패했습니다."
                ));
            }else{
                /*
                 *  20 : 사용대기 >>미사용
                    30 : 회수 >> 사용
                    40 : 폐기 >>취소
                 */

                if($_data_result['Data']['ds_result'][0]['REPR_CPON_STAT_CD'] == "40"){
                    $hanwhamodel->set_COUPON_STATE($couponrow['id'], "C");
                }else if($_data_result['Data']['ds_result'][0]['REPR_CPON_STAT_CD'] == "30"){
                    $hanwhamodel->set_COUPON_STATE($couponrow['id'], "Y");
                    $_data_result['Data']['ds_result'][0]['REPR_CPON_STAT_NM'] = "사용";
                }

                $res = json_encode(array(
                        "RESULT"=>true,
                        "MSG"=>"조회 성공",
                        "STAT_CD"=>$_data_result['Data']['ds_result'][0]['REPR_CPON_STAT_CD'] ,
                        "STAT_NM"=>$_data_result['Data']['ds_result'][0]['REPR_CPON_STAT_NM'])
                );

            }
        }
        break;
    case 'cancel':

        $hanwhamodel = new hanwhamodel();
        $couponrow = $hanwhamodel->select_hanwha_coupon_couponno($data['couponno']);

        if($couponrow == null){
            $res = json_encode(array(
                "RESULT"=>false,
                "MSG"=>"존재하지 않는 쿠폰입니다."
            ));
        }else{

            $hanwha = new \Hanwha\Hanwha();
            //쿠폰 상태 조회
            $_data_result = $hanwha->searchDs($couponrow['REPR_CPON_SEQ'], $couponrow['REPR_CPON_INDICT_NO'],$couponrow['ISSUE_DATE']);
            if(!$_data_result) {
                $res = json_encode(array(
                    "RESULT"=>false,
                    "MSG"=>"시스템오류. 통신에 실패했습니다."
                ));
            }else{

                if($_data_result['Data']['ds_result'][0]['REPR_CPON_STAT_CD'] == "40"){
                    $hanwhamodel->set_COUPON_STATE($couponrow['id'], "C");
                }else if($_data_result['Data']['ds_result'][0]['REPR_CPON_STAT_CD'] == "30"){
                    $hanwhamodel->set_COUPON_STATE($couponrow['id'], "Y");
                    $_data_result['Data']['ds_result'][0]['REPR_CPON_STAT_NM'] = "사용";
                }

                if($_data_result['Data']['ds_result'][0]['REPR_CPON_STAT_CD'] != "20"){
                    $res = json_encode(array(
                        "RESULT"=>false,
                        "MSG"=>"취소 가능한 상태가 아닙니다.",
                        "STAT_CD"=>$_data_result['Data']['ds_result'][0]['REPR_CPON_STAT_CD'] ,
                        "STAT_NM"=>$_data_result['Data']['ds_result'][0]['REPR_CPON_STAT_NM'])
                    );
                    if($_data_result['Data']['ds_result'][0]['REPR_CPON_STAT_CD'] == "40"){
                        $hanwhamodel->set_COUPON_STATE($couponrow['id'], "C");
                    }
                }else{
                    $_cancel_try = $hanwha->setDsInputCancel($couponrow['REPR_CPON_INDICT_NO']);
                    $_cancel_result = $hanwha->searchDs($couponrow['REPR_CPON_SEQ'], $couponrow['REPR_CPON_INDICT_NO'],$couponrow['ISSUE_DATE']);
                    if($_cancel_result['Data']['ds_result'][0]['REPR_CPON_STAT_CD'] != "40"){
                        $res = json_encode(array(
                                "RESULT"=>false,
                                "MSG"=>"발권취소 실패/{$_cancel_try['MessageHeader']['MSG_DATA_SUB'][0]['MSG_CTNS']}",
                                "STAT_CD"=>$_cancel_result['Data']['ds_result'][0]['REPR_CPON_STAT_CD'] ,
                                "STAT_NM"=>$_cancel_result['Data']['ds_result'][0]['REPR_CPON_STAT_NM'])
                        );
                    }else{
                        $res = json_encode(array(
                                "RESULT"=>true,
                                "MSG"=>"발권취소 성공/{$_cancel_try['MessageHeader']['MSG_DATA_SUB'][0]['MSG_CTNS']}",
                                "STAT_CD"=>$_cancel_result['Data']['ds_result'][0]['REPR_CPON_STAT_CD'] ,
                                "STAT_NM"=>$_cancel_result['Data']['ds_result'][0]['REPR_CPON_STAT_NM'])
                        );

                        $hanwhamodel->set_COUPON_STATE($couponrow['id'], "C");
                    }
                }
            }
        }
       break;

    default:
        $res = json_encode(array(false, "API 타입이 존재하지 않습니다."));
        break;
}

echo $res;


function get_ip(){
    
	if(empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
		 $ip= $_SERVER["REMOTE_ADDR"].",";		
	}else{
		$ip= $_SERVER["HTTP_X_FORWARDED_FOR"];	
	}

	$res = explode(",",$ip);

    return trim($res[0]);
}
?>