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

include '/home/sparo.cc/hanwha_script/hanwha/class/class.hanwha.php';
include '/home/sparo.cc/hanwha_script/lib/class/class.lib.common.php';
require '/home/sparo.cc/hanwha_script/hanwha/class/hanwhamodel.php';

header("Content-type:application/json");

// ACL 확인
$accessip = array("115.68.42.2",
    "115.68.42.8",
    "115.68.42.130",
    "52.78.174.3",
    "106.254.252.100",
    "115.68.182.165",
    "13.124.139.14",
    "218.39.39.190",
    "13.209.232.254",
	"13.124.215.30",
  "18.163.36.64"
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

//print_r($data);
//
//exit;

switch ($para){
    case 'order':
        $hanwhamodel = new hanwhamodel();
        //연동확인

        $pkgsync = $hanwhamodel->select_hanwha_hanwha_pkgsync($data['SELLCODE']);

        if(!$pkgsync){
            $res = json_encode(array(
                "RESULT"=>false,
                "MSG"=>"연동코드를 확인해주세요."
            ));
        }else{
            //쿠폰 Row 생성
//            $thisCouponId = $hanwhamodel->insert_hanwha_coupon_orderInfo($data);
            $data['CORP_CD'] = $pkgsync['CORP_CD'];
            $data['CONT_NO'] = $pkgsync['CONT_NO'];


            $thisCouponId = $hanwhamodel->insert_hanwha_aqua_coupon_orderInfo($data);
            if(!$thisCouponId){
                $res = json_encode(array(
                    "RESULT"=>false,
                    "MSG"=>"시스템오류. 주문을 생성하지 못했습니다."
                ));
            }else{
                $hanwha = new \Hanwha\Hanwha();
                //쿠폰발행처리

                //일산
                //$hanwha->setCORP_CD("4000");
                //$hanwha->setCONT_NO("11900011");
                //63
//                $hanwha->setCORP_CD("1000");
//                $hanwha->setCONT_NO("11806416");
                $hanwha->setCORP_CD($pkgsync['CORP_CD']);
                $hanwha->setCONT_NO($pkgsync['CONT_NO']);
//                print_r($pkgsync);
                $_data_result = $hanwha->getDsInput($pkgsync['GOODS_NO'] , $data['RCVER_NM'] , $data['RCVER_TEL'] );
//                print_r($_data_result);

                if(!$_data_result) {
                    $res = json_encode(array(
                        "RESULT"=>false,
                        "MSG"=>"시스템오류. 통신에 실패했습니다.")
                    );
                }else if($_data_result['MessageHeader']['MSG_PRCS_RSLT_CD'] != 0){
                    /*
                     * 에러코드  콜백 오류코드
                     * 정상 0, 오류 -1"
                     */
                    $res = json_encode(array(
                            "RESULT"=>false,
                            "MSG"=>"발권실패",
                            $_data_result['MessageHeader'])
                    );
                }else{
                    $_data_ary = $_data_result['Data'];
//                    if($hanwhamodel->set_DsInputAqua($thisCouponId,$_data_ary , $pkgsync['CORP_CD'] , $pkgsync['CONT_NO'])){
                    if($hanwhamodel->set_DsInput($thisCouponId,$_data_ary)){
                        $res = json_encode(array(
                            "RESULT"=>true,
                            "MSG"=>"발권성공",
                            "couponno"=>$_data_ary['ds_output'][0]['REPR_CPON_INDICT_NO'])
                        );
                    }else{
                        $res = json_encode(array(
                            "RESULT"=>false,
                            "MSG"=>"시스템오류. 쿠폰정보 등록에 실패했습니다."
                        ));
                    }
                }
            }
        }
    break;

    case 'info':

        $hanwhamodel = new hanwhamodel();
        $couponrow = $hanwhamodel->select_hanwha_coupon_couponno($data['couponno']);
		$_is_info = true;
        if($couponrow == null || empty($couponrow) ){
			//쿠팡에선 바코드가 아닌 쿠판 채널 번호가 넘어와서 한번 더 select 아쿠파 핀 번호 찾기
			$_cou = $hanwhamodel->getOrderMtsChOrderNo($data['couponno']);

			if (!empty($_cou['coupon_no']) && $_cou['coupon_no'] != null){
				$couponrow = $hanwhamodel->select_hanwha_coupon_couponno($_cou['coupon_no']);
			} else {
				$_is_info = false;
			}
			if($couponrow == null || empty($couponrow) ){
				$_is_info = false;
				$res = json_encode(array(
					"RESULT"=>false,
					"MSG"=>"존재하지 않는 쿠폰입니다."
				));
			} else {
				$_is_info = true;
			}
        }else{
			$_is_info = true;
		}

        if ($_is_info === true){
            $hanwha = new \Hanwha\Hanwha();

            //CORP_CD,CONT_NO

            $hanwha->setCONT_NO($couponrow['CONT_NO']);
            $hanwha->setCORP_CD($couponrow['CORP_CD']);

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
                        "CLLT_DS"=>$_data_result['Data']['ds_result'][0]['CLLT_DS'] ,
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

            $hanwha->setCONT_NO($couponrow['CONT_NO']);
            $hanwha->setCORP_CD($couponrow['CORP_CD']);

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

    // 20230321 tony https://placem.atlassian.net/browse/PM1903COBB-40
    // 네고왕_한화호텔&리조트_CM 연동취소 및 바우처방식 연동전문 확인 요청
    case 'negowangsearch':
        {
            $hanwha = new \Hanwha\Hanwha();

            //CORP_CD,CONT_NO

            $hanwha->setCONT_NO("20001933");    // 네고왕전용
            $hanwha->setCORP_CD("1000");        // 네고왕전용
// 테스트용으로 쿠폰번호 넣기
// $data['couponno'] = '2324007300009701';
// $data['couponno'] = '2324006400009701';
            if (empty($data['couponno'])){
                // 쿠폰번호가 없으면 전체를 조회하므로 예외처리함
                $res = json_encode(array(
                    "RESULT"=>false,
                    "MSG"=>"쿠폰번호가 없습니다."
                ));

                echo $res; 
                exit;
            }

            //쿠폰 상태 조회
            $_data_result = $hanwha->searchNegowang($data['couponno']);
/*
print_r($hanwha->getSysTemsHeader());
print_r($hanwha->getMessageHeader());
print_r($hanwha->getTransactionHeader());
print_r(json_encode($data));
print_r(json_encode($_data_result));
exit;
*/
            if(!$_data_result) {
                $res = json_encode(array(
                    "RESULT"=>false,
                    "MSG"=>"시스템오류. 통신에 실패했습니다."
                ));
            } else if($_data_result['MessageHeader']['MSG_PRCS_RSLT_CD'] == '-1'){
                $res = json_encode(array(
                    "RESULT"=>false,
                    "MSG"=>"시스템오류. 조회실패."
                )); 
            }else{
                 // 20 : 사용대기 >> 미사용
                 // 30 : 회수 >> 사용
                 // 40 : 폐기 >> 취소

                if($_data_result['Data']['ds_result'][0]['REPR_CPON_STAT_CD'] == "40"){
                    // $hanwhamodel->set_COUPON_STATE($couponrow['id'], "C");
                }else if($_data_result['Data']['ds_result'][0]['REPR_CPON_STAT_CD'] == "30"){
                    // $hanwhamodel->set_COUPON_STATE($couponrow['id'], "Y");
                    $_data_result['Data']['ds_result'][0]['REPR_CPON_STAT_NM'] = "사용";
                }

                $res = json_encode(array(
                        "RESULT"=>true,
                        "MSG"=>"조회 성공",
                        "STAT_CD"=>$_data_result['Data']['ds_result'][0]['REPR_CPON_STAT_CD'] ,
                        "CLLT_DS"=>$_data_result['Data']['ds_result'][0]['CLLT_DS'] ,
                        "STAT_NM"=>$_data_result['Data']['ds_result'][0]['REPR_CPON_STAT_NM'])
                );

            }
        }
        break;
    // 20230321 tony https://placem.atlassian.net/browse/PM1903COBB-40
    // 네고왕_한화호텔&리조트_CM 연동취소 및 바우처방식 연동전문 확인 요청
    case 'negowangcancel':
        {
            $hanwha = new \Hanwha\Hanwha();

            $hanwha->setCONT_NO("20001933");    // 네고왕전용
            $hanwha->setCORP_CD("1000");        // 네고왕전용

            if (empty($data['couponno'])){
                // 쿠폰번호가 없으면 전체를 조회하므로 예외처리함
                $res = json_encode(array(
                    "RESULT"=>false,
                    "MSG"=>"쿠폰번호가 없습니다."
                ));

                echo $res;
                exit;
            }

            //쿠폰 상태 조회
            $_data_result = $hanwha->searchNegowang($data['couponno']);
            if(!$_data_result) {
                $res = json_encode(array(
                    "RESULT"=>false,
                    "MSG"=>"시스템오류. 통신에 실패했습니다.",
                    "STAT_NM"=>"통신실패"
                ));
            } else if($_data_result['MessageHeader']['MSG_PRCS_RSLT_CD'] == '-1'){
                $res = json_encode(array(
                    "RESULT"=>false,
                    "MSG"=>"시스템오류. 조회실패.",
                    "STAT_NM"=>"핀폐기전 조회실패"
                )); 
            }else{
//echo $res; exit;
                // 20 : 사용대기 >> 미사용
                // 30 : 회수 >> 사용
                // 40 : 폐기 >> 취소

                if($_data_result['Data']['ds_result'][0]['REPR_CPON_STAT_CD'] == "40"){
                    // $hanwhamodel->set_COUPON_STATE($couponrow['id'], "C");
                }else if($_data_result['Data']['ds_result'][0]['REPR_CPON_STAT_CD'] == "30"){
                    // $hanwhamodel->set_COUPON_STATE($couponrow['id'], "Y");
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
                        // $hanwhamodel->set_COUPON_STATE($couponrow['id'], "C");
                    }
                }else{
                    $_cancel_try = $hanwha->cancelNegowang($data['couponno']);
                    $_cancel_result = $hanwha->searchNegowang($data['couponno']);
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

                        // $hanwhamodel->set_COUPON_STATE($couponrow['id'], "C");
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
