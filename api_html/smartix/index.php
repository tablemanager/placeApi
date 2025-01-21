<?php

/*
 *
 * 스마틱스 연동 인터페이스
 *
 * 작성자 : 신디
 * 작성일 : 2019-04-01
 *
 */

//http://extapi.sparo.cc/hanwha/info
//http://extapi.sparo.cc/hanwha/order
//http://extapi.sparo.cc/hanwha/cancel

require_once('/home/sparo.cc/Library/M_ConnSparo2.php'); /* 주문 테이블 공통 클래스 : 수정 및 등록은 미카엘에게 요청 */
require_once('/home/sparo.cc/Library/M_ConnCms.php'); /* 주문 테이블 공통 클래스 : 수정 및 등록은 미카엘에게 요청 */
require_once('/home/sparo.cc/Library/messagelib.php');   /* 메세지 공통 클래스 : 수정 및 등록은 미카엘에게 요청 */

require_once('/home/sparo.cc/smartix_script/lib/smartixmodel.php');   /* 스마틱스 DB 클래스 */
require_once('/home/sparo.cc/Library/useapi.php');   /* 네이버, 소셜 사용처리 */
require_once('/home/sparo.cc/Library/coupangAPI2018.php');   /* 쿠팡 사용처리 */

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
	"13.124.215.30",
	"218.39.39.190",
);

$smartixip = array(
    "27.122.250.55",
    "27.122.250.64",
    "27.122.250.195",
    "27.122.250.61",
    "103.129.187.41",
    "103.129.187.42",
    "45.119.147.205",
    "106.10.59.240",
    "106.10.57.87",
    "106.10.39.239"
);

$para = $_GET['val']; // URI 파라미터

if(!in_array(get_ip(),$accessip) && !in_array(get_ip(),$smartixip) ){
    header("HTTP/1.0 401 Unauthorized");
    $res = array("RESULT"=>false,"MSG"=>"아이피 인증 오류 : ".get_ip());
    echo json_encode($res);
    exit;
}else if(in_array(get_ip(),$smartixip) && $para != 'use'){
    header("HTTP/1.0 401 Unauthorized");
    $res = array("RESULT"=>false,"MSG"=>"API 오류 : ".get_ip());
    echo json_encode($res);
    exit;
}


$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders();            // http 헤더

$jsonreq = trim(file_get_contents('php://input'));
$data = json_decode($jsonreq,TRUE);

//couponno는 소문자로 통일
if($data['COUPONNO'] != null && $data['COUPONNO'] != "") $data['couponno'] = $data['COUPONNO'];

$m_connSparo2 = new M_ConnSparo2();
$m_connCms = new M_ConnCms();
$smartixmodel = new smartixmodel();

/*request -----------------------------

$data = array(
    "NAME" => "미카엘",
    "TEL" => "",
    "USEDATE" => "20190331",
    "ORDERNO" => "20190228HM12345",
    "SELLCODE" => 12345 (플레이스엠상품코드),
    "QTY" => "3"
);
$json = json_encode($data);

$res = json_encode(array(
    "RESULT"=>true,
    "MSG"=>"쿠폰발권성공",
    "couponno" => $getCoupon // array('111111','211111','311111')
));

response ----------------------------
{
    ["couponno": "1234567899988776"]
}

조회를 할때는 쿠폰번호만 받자.
$data = array(
    "RESULT"=>true,
    "couponno" =>array(
         "1111111111","1111111112"
    )
);
*/
switch ($para){
    case 'order':

        //스마틱스 오더를 만들자
        //연동확인
        $pkgsync = $smartixmodel->selectSmartixSynclistItemid($data['SELLCODE']);   #스마틱스 연동 코드 확인으로 변경 SELLCODE 는 플레이스엠 상품코드

        if(!$pkgsync){
            $res = json_encode(array(
                "RESULT"=>false,
                "MSG"=>"시스템오류. 연동을 확인해주세요."
            ));
        }else {

            $coupon_cnt = $smartixmodel->countSmartixCoupon($data['ORDERNO']);

            //기발권 수량 체크
            if ($coupon_cnt > 0) {

                $res = json_encode(array(
                    "RESULT"=>false,
                    "MSG"=>"시스템오류. 중복된 ORDERNO 입니다."
                ));
            } else {
                $rsTelno = implode(explode('-', $data['TEL']));
                $sdDt = implode(explode('-', $data['USEDATE']));
                //$usedate = date("Y-m-d",strtotime($data['USEDATE']));
                $usedate = $data['USEDATE'];
                $rsQuant = 1;


                //가격 가져와야함. 2번 DB에 CMS_PRICE where item_id 랑 날짜 넣어서 가격을 가져오기
                $chkPrice = $m_connCms->select_CMS_PRICES($data['SELLCODE'],$usedate);
                $price = $chkPrice['price_sale'];
                $totalPrice = $price * $data['QTY'];    //총가격
                $rsTime = date("YmdHis");       //현재시간


                //가격이 없으면 에러메세지.
                if(!$chkPrice){
                    $res = json_encode(array(
                        "RESULT"=>false,
                        "MSG"=>"시스템오류. 가격을 확인해주세요."
                    ));
                }else{
                    //$pkgsync 에서 datetype 확인~
                    if($pkgsync['datetype']=='P'){
                        $optionNm = $pkgsync['item_nm'];
                    }else if($pkgsync['datetype']=='D'){
                        //$optionNm = $pkgsync['usedate'] . "|" . $pkgsync['item_nm'];
                        $optionNm = $usedate . "|" . $pkgsync['item_nm'];
                    }else{
                        $res = json_encode(array(
                            "RESULT"=>false,
                            "MSG"=>"datetype 오류."
                        ));
                    }

                    //스마틱스 쿠폰을 발권, 입력하고 쿠폰번호 리턴 ~

                    //스마틱스 주문 생성
                    $orderdata = array(
                        "channelOrderNo" => $data['ORDERNO'],
                        "ch_id" => $data['ch_id'],
                        "optionId" => $data['SELLCODE'],
                        "prodSeq" => $pkgsync['prodSeq'],
                        "item_nm" => $pkgsync['item_nm'],
                        "channelRegProdId" => $data['SELLCODE'],
                        "channelRegProdNm" => $pkgsync['item_nm'],
                        "rsNm" => $data['NAME'],
                        "rsTelno" => $rsTelno,
                        "sdDt" => $sdDt,
                        "total_rsQuant" => $data['QTY'],
                        "total_rsAmt" => $totalPrice,
                        "rsTime" => $rsTime,
                        "usedate" => $data['USEDATE'],
                        "rsQuant" => $rsQuant,
                        "optionNm" => $optionNm
                    );

                    //echo "{$orderdata['total_rsQuant']}건 신규 주문 생성\n";

                    //스마틱스 주문 생성
                    $insertOrder = $smartixmodel->insertSmartixOrder($orderdata);

                    //실패했으면 실패 메세지 $res 생성
                    if (!$insertOrder) {
                        $res = json_encode(array(
                            "RESULT"=>false,
                            "MSG"=>"스마틱스 주문 생성 오류."
                        ));
                    } else {
                        //티켓 예약등록 데이터
                        $senddata = array(
                            "channelOrderNo" => $data['ORDERNO'],
                            "optionId" => $data['SELLCODE'],
                            "prodSeq" => $pkgsync['prodSeq'],
                            "channelRegProdId" => $data['SELLCODE'],
                            "channelRegProdNm" => $pkgsync['item_nm'],
                            "rsNm" => $data['NAME'],
                            "rsTelno" => $rsTelno,
                            "sdDt" => $sdDt,
                            "total_rsQuant" => $data['QTY'],
                            "rsAmt" => $price,
                            "rsTime" => $rsTime,
                            "optionNm" => $optionNm
                        );

                        $UpdateBarcode = "";
                        $getCoupon = array();

                        //입력받은 인원 수 만큼 쿠폰 생성 시작
                        for ($i = 1; $i <= $data['QTY']; $i++) {
                            //쿠폰 생성 시작

                            $senddata['channelTktNo'] = $data['ORDERNO'] . $smartixmodel->RandomString(3);
                            $UpdateBarcode .= $senddata['channelTktNo'];
                            //echo $senddata['channelTktNo'] . "\n";

                            //티켓 예약 등록
                            $reservations = $smartixmodel->reservations($senddata);

                            if (!$reservations) {
                                $res = json_encode(array(
                                    "RESULT"=>false,
                                    "MSG"=>"{$senddata['channelOrderNo']} 시스템오류:티켓 예약 등록 실패"
                                ));
                                break;
                            } else {
                                $rsvdata = array(
                                    'order_code' => $reservations->code,
                                    'order_message' => $reservations->message,
                                    'channelOrderNo' => $reservations->channelOrderNo,
                                    'channelTktNo' => $reservations->channelTktNo,
                                    'rsQuant' => $reservations->rsQuant,
                                    'rsAmt' => $reservations->rsAmt,
                                    'rsSeqInspect' => $reservations->rsSeqInspect,
                                    'couponNo' => $reservations->couponNo,
                                    'shortUrl' => $reservations->shortUrl,
                                    'paymentPlanNm' => $reservations->paymentPlanNm,
                                    'rsTycd' => $reservations->rsTycd
                                );
                                $UpdateBarcode .= "/" . $reservations->rsSeqInspect . ";";
                                $getCoupon[] = $reservations->rsSeqInspect;

                                //쿠폰 Row 생성
                                $thisCouponId = $smartixmodel->insertSmartixCoupon($rsvdata);

                                if (!$thisCouponId) {
                                    $res = json_encode(array(
                                        "RESULT"=>false,
                                        "MSG"=>"{$senddata['channelOrderNo']} 시스템오류:쿠폰 생성 실패"
                                    ));
                                    break;
                                }
                            }
                        }//인원 for loop

                        //발권 수량 비교
                        $coupon_cnt = $smartixmodel->countSmartixCoupon($data['ORDERNO']);
                        //echo "{$coupon_cnt}(발권수량)/{$data['QTY']}(구매수량)\n";

                        if ($data['QTY'] == $coupon_cnt) {
                            //echo $endstr = "스마틱스쿠폰발권정보:{$coupon_cnt}(발권수량)/{$data['QTY']}(구매수량)";
                            $res = json_encode(array(
                                "RESULT"=>true,
                                "MSG"=>"쿠폰발권성공",
                                "couponno" => $getCoupon
                            ));
                        } else {
                            $res = json_encode(array(
                                "RESULT"=>false,
                                "MSG"=>"발권수량오류:{$coupon_cnt}(발권수량)/{$data['QTY']}(구매수량)"
                            ));
                            continue;
                        }
                    }
                }//가격 유무 체크
            }//기발권 수량 체크
        }//$pkgsync 확인

    break;

    case 'info':

//        echo $data['couponno'];
        //쿠폰 존재 유무 확인

        $couponrow = $smartixmodel->selectSmartixCouponno($data['couponno']);

        if($couponrow == null){
            $res = json_encode(array(
                "RESULT"=>false,
                "MSG"=>"존재하지 않는 쿠폰입니다."
            ));
        }else{
            //쿠폰 상태 조회
            $_data_result = $smartixmodel->status($couponrow['couponNo']); //쿠폰상태 조회
            if(!$_data_result) {
                $res = json_encode(array(
                    "RESULT"=>false,
                    "MSG"=>"시스템오류. 통신에 실패했습니다."
                ));
            }else{

                if($_data_result->rsStatus == "예약취소"){ //취소
                    $smartixmodel->changeSmartixCouponState($_data_result->rsSeqInspect, 'C');
                }else if($_data_result->rsStatus == "사용완료"){//사용
                    $smartixmodel->changeSmartixCouponState($_data_result->rsSeqInspect, 'Y');
                }

                $res = json_encode(array(
                        "RESULT"=>true,
                        "MSG"=>"조회 성공",
                        "couponno"=>$_data_result->rsSeqInspect,
                        "STATE"=>$_data_result->rsStatus
                ));

            }
        }
        break;
    case 'cancel':

        $couponrow = $smartixmodel->selectSmartixCouponno($data['couponno']);

        if($couponrow == null){
            $res = json_encode(array(
                "RESULT"=>false,
                "MSG"=>"존재하지 않는 쿠폰입니다."
            ));
        }else{

            $chk = true;
            $chkmsg = array();
            foreach ($smartixmodel->selectSmartixChannelOrderNo($couponrow['channelOrderNo']) as $crow):
                if($crow['state'] != "N"){
                    $chk = false;
                    $chkmsg[] = "{$crow['rsSeqInspect']}:{$crow['state']}";
                }
                $_data_result = $smartixmodel->status($crow['rsSeqInspect']); //쿠폰상태 조회

                if(!$_data_result) {
                    $chk = false;
                    $chkmsg[] = "{$crow['rsSeqInspect']}:조회실패(스마틱스)";
                }else{
                    if($_data_result->rsStatus != "예약완료"){
                        $chk = false;
                        $chkmsg[] = "{$crow['rsSeqInspect']}:{$_data_result->rsStatus}";

                        //조회결과 취소 반영
                        if($_data_result->rsStatus == "예약취소"){
                            $smartixmodel->changeSmartixCouponState($crow['rsSeqInspect'], 'C');
                        }
                    }
                }
            endforeach;

            //취소가 가능하다면

            if($chk){
                $chk2 = true;
                $chkmsg2 = array();

                foreach ($smartixmodel->selectSmartixChannelOrderNo($couponrow['channelOrderNo']) as $crow2):
                    $cancel_result = $smartixmodel->cancelticket($crow2['channelTktNo']);

                    if(!$cancel_result){
                        $chk2 = false;
                        $chkmsg2[$crow2['channelTktNo']] = "시스템오류. 통신에 실패했습니다.";
                    }else{
                        $chkmsg2[$crow2['channelTktNo']] = $cancel_result->message;
                        if($cancel_result->code != "100") $chk2 = false;
                    }
                endforeach;

                //성공하면 결과
                //쿠폰 상태 C로 업데이트 할대는 where 조건에 couponno
                if($chk2){
                    $smartixmodel->changeSmartixCouponState2($couponrow['channelOrderNo'],'C');
                    $m_connSparo2->updateOrderStateOrderno($couponrow['channelOrderNo'],"취소","API CANCEL:".date("Y-m-d H:i:s"));
                    $smartixmodel->changeSmartixOrderState($couponrow['channelOrderNo'],'C');
                }
                $res = json_encode(array(
                        "RESULT"=>$chk2,
                        "MSG"=>$chkmsg2,
                        "ORDERNO"=>$couponrow['channelOrderNo']
                    )
                );

            }else{
                $res = json_encode(array(
                        "RESULT"=>$chk,
                        "MSG"=>$chkmsg,
                        "ORDERNO"=>$couponrow['channelOrderNo']
                    )
                );
            }
        }
       break;
        
    case 'use':

        /*
         * smartix_coupon 에 쿠폰 조회
         * 없으면 쿠폰번호를 찾을 수 없습니다.
         *
         * 사용처리할때 이미 사용이면
         * 사용된 쿠폰 번호입니다. 라고 에러
         *
         * 사용취소처리할때 이미 미사용이면
         * 미사용 쿠폰 번호입니다.
         *
         * */

        $chkCoupon = $smartixmodel->selectSmartixCouponno($data['couponno']);

        if(!$chkCoupon){
            $res = json_encode(array(
                "RESULT"=>false,
                "MSG"=>"쿠폰 번호를 찾을 수 없습니다."
            ));
        }else if($chkCoupon['state'] == "C"){
            $res = json_encode(array(
                "RESULT"=>false,
                "MSG"=>"취소된 쿠폰입니다."
            ));
        }else{
            //사용처리
            if($data['STATE'] == "Y"){
                if($chkCoupon['state'] == "Y"){
                    $res = json_encode(array(
                        "RESULT"=>false,
                        "MSG"=>"이미 사용된 쿠폰 번호입니다."
                    ));
                }else{
                    $chkuse1 = $smartixmodel->changeSmartixCouponState($chkCoupon['rsSeqInspect'],'Y');
                    $chkuse2 = $smartixmodel->changeOrdermtsUsegu2($chkCoupon['channelOrderNo'],'1');
                    $chkuse3 = $smartixmodel->changeSmartixOrderState($chkCoupon['channelOrderNo'],'Y');


                    $orderrow = $smartixmodel->selectSmartixOrderOrderNo($chkCoupon['channelOrderNo']);
                    if($orderrow['ch_id'] == "142"){   //위메프 사용처리 (채널아이디 '142')
                        @sync_useWeMakePrice($orderrow['channelRegProdId'],$chkCoupon['rsSeqInspect']); //위메프 실제 쿠폰번호로 사용처리
                        @sync_useWeMakePrice($orderrow['channelRegProdId'],$chkCoupon['channelTktNo']); //위메프 채널 쿠폰 번호로 사용처리
                    }else if($orderrow['ch_id'] == "154"){//티켓몬스터 154
                        @sync_useTmon($chkCoupon['channelTktNo']); //티켓몬스터 채널 쿠폰 번호로 사용처리
                    }else if($orderrow['ch_id'] == "150") {//쿠팡
                        @coupang_use_pin($chkCoupon['channelTktNo']); //쿠팡 채널 쿠폰 번호로 사용처리
                    }else{
                        @usecouponno($chkCoupon['rsSeqInspect']); //네이버 및 기타 신연동들~ (feat.Jay)
                    }


                    if(!$chkuse1 || !$chkuse2 || !$chkuse3){
                        $err = "";
                        if(!$chkuse1)$err.="스마틱스쿠폰 사용처리에 실패했습니다.\n";
                        if(!$chkuse2)$err.="주문 사용처리에 실패했습니다.\n";
                        if(!$chkuse3)$err.="스마틱스주문 사용처리에 실패했습니다.\n";

                        $res = json_encode(array(
                            "RESULT"=>false,
                            "MSG"=>"시스템오류. {$err}"
                        ));
                    }else{
                        $res = json_encode(array(
                            "RESULT"=>true,
                            "MSG"=>"사용처리 성공",
                            "couponno"=>$chkCoupon['rsSeqInspect']
                        ));
                    }
                }
            }
            //미사용처리
            elseif($data['STATE'] == "N"){
                if($chkCoupon['state'] == "N"){
                    $res = json_encode(array(
                        "RESULT"=>false,
                        "MSG"=>"미사용 쿠폰 번호입니다."
                    ));
                }else{

                    $chkunuse1 = $smartixmodel->changeSmartixCouponState($chkCoupon['rsSeqInspect'],'N');
                    $chkunuse2 = $smartixmodel->changeOrdermtsUsegu2($chkCoupon['channelOrderNo'],'2');
                    $chkunuse3 = $smartixmodel->changeSmartixOrderState($chkCoupon['channelOrderNo'],'N');

                    $orderrow = $smartixmodel->selectSmartixOrderOrderNo($chkCoupon['channelOrderNo']);
                    if($orderrow['ch_id'] == "150") {//쿠팡
                        @coupang_unuse_pin($chkCoupon['channelTktNo']); //쿠팡 채널 쿠폰 번호로 사용처리
                    }else if($orderrow['ch_id'] == "154"){//티켓몬스터 154
                        @sync_unuseTmon($chkCoupon['channelTktNo']); //티켓몬스터 채널 쿠폰 번호로 사용처리
                    }

                    if(!$chkunuse1 || !$chkunuse2 || !$chkunuse3){
                        $err = "";
                        if(!$chkunuse1)$err.="스마틱스쿠폰 미사용처리에 실패했습니다.\n";
                        if(!$chkunuse2)$err.="주문 미사용처리에 실패했습니다.\n";
                        if(!$chkunuse3)$err.="스마틱스주문 미사용처리에 실패했습니다.\n";

                        $res = json_encode(array(
                            "RESULT"=>false,
                            "MSG"=>"시스템오류. {$err}"
                        ));
                    }else{
                        $res = json_encode(array(
                            "RESULT"=>true,
                            "MSG"=>"미사용처리 성공",
                            "couponno"=>$chkCoupon['rsSeqInspect']
                        ));
                    }
                }
            }
        }//쿠폰번호 조회
        //로그 만들기.
        $smartixmodel->insertSmartixLog($data['couponno'],"use",$jsonreq,$res);

    /*
     * STATE 가 Y면 사용 N 이면 미사용 처리
     * 이것을 smartix_coupon 에 적용
     * smartix_order에는 모두 미사용이면 미사용, 한개라도 사용이면 사용
     * ordermts에도 모두 미사용이면 미사용, 한개라도 사용이면 사용
     * 
     * 사용처리 할때는 쿠폰에는 그냥 사용처리
     * 주문(ordermts, smartix_order)에는 사용이 아니면 사용처리 -> 이렇게 하는 이유는 사용처리 시간을 최초 사용처리 한 시간만 기록할것이기 때문..
     * 
     * 사용취소처리 할때는 쿠폰에는 그냥 미사용 처리
     * 주문(ordermts, smartix_order)에는 사용된 쿠폰이 0개일때(갯수는 orderno 로 smartix_copupon 에 조회) 미사용처리 -> 이렇게 하는 이유는 쿠폰이 여러개인데 부분 사용처리를 못하니까... 한개라도 사용이면 사용취소처리를 하면 안됨.
     *
     * */

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