<?php

/*
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

    case  'orders' :

        $hanwhamodel = new hanwhamodel();
        //연동확인
        $is_insert = true;
        $thisCouponId = "";


        for ($ii = 0; $ii < count($data); $ii++){
            $pkgsync = $hanwhamodel->select_hanwha_hanwha_pkgsync($data[$ii]['SELLCODE']);

            if(!$pkgsync){
                $res = json_encode(array(
                    "RESULT"=>false,
                    "MSG"=>"연동코드를 확인해주세요."
                ));
            }else{
                $data[$ii]['CORP_CD'] = $pkgsync['CORP_CD'];
                $data[$ii]['CONT_NO'] = $pkgsync['CONT_NO'];
//                $thisCouponId = $hanwhamodel->insert_hanwha_aqua_coupon_orderInfo($data);
                if ($ii == 0){
                    $thisCouponId = $hanwhamodel->insert_hanwha_aqua_coupons_orderInfos($data[$ii]['RCVER_TEL'],$data[$ii]['ORDERNO'] , $data[$ii]['CORP_CD'] ,$data[$ii]['CONT_NO'] ,$data[$ii]['SELLCODE'] , $data[$ii]['RCVER_NM'] , $data[$ii]['UNIT']);
                }

                if(!$thisCouponId){
                    $is_insert = false;
                }
            }

        }

//        echo "thisCouponId : ".$thisCouponId."<br>";
        if ($is_insert === true){

            $hanwha = new \Hanwha\Hanwha();

            $is_update = false;
            for ($j = 0; $j < count($data); $j++){

                $pkgsync = $hanwhamodel->select_hanwha_hanwha_pkgsync($data[$j]['SELLCODE']);
//                print_r($pkgsync);

                $hanwha->setCORP_CD($pkgsync['CORP_CD']);
                $hanwha->setCONT_NO($pkgsync['CONT_NO']);

                $_han_data[$j]['GOODS_NO'] = $pkgsync['GOODS_NO'];
                $_han_data[$j]['RCVER_NM'] = $data[$j]['RCVER_NM'];
                $_han_data[$j]['RCVER_TEL'] = $data[$j]['RCVER_TEL'];
                $_han_data[$j]['UNIT'] = $data[$j]['UNIT'];
            }

//            print_r($_han_data);

            $_data_result = $hanwha->getDsInputUnity($_han_data);

//            echo "----------------------------------------<br>";
//            print_r($_data_result);

            if(!$_data_result) {
                $res = json_encode(array(
                        "RESULT"=>false,
                        "MSG"=>"시스템오류. 통신에 실패했습니다.")
                );
            } else if($_data_result['MessageHeader']['MSG_PRCS_RSLT_CD'] != 0){
                /*
                 * 에러코드  콜백 오류코드
                 * 정상 0, 오류 -1"
                 */
                $res = json_encode(array(
                        "RESULT"=>false,
                        "MSG"=>"발권실패",
                        $_data_result['MessageHeader'])
                );
            } else {
                $_data_ary = $_data_result['Data'];
                if($hanwhamodel->set_DsInput($thisCouponId,$_data_ary)){
                    if ($is_update === false){
                        $is_update == true;
                        $res = json_encode(array(
                                "RESULT"=>true,
                                "MSG"=>"발권성공",
                                "couponno"=>$_data_ary['ds_output'][0]['REPR_CPON_INDICT_NO'])
                        );
                    }
                }else{
                    $res = json_encode(array(
                        "RESULT"=>false,
                        "MSG"=>"시스템오류. 쿠폰정보 등록에 실패했습니다."
                    ));
                }
            }

        } else {
            $res = json_encode(array(
                "RESULT"=>false,
                "MSG"=>"시스템오류. 주문을 생성하지 못했습니다."
            ));
        }

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