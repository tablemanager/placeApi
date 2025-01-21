<?php

include '/home/sparo.cc/dogo_script/class/class.dogo.php';
include '/home/sparo.cc/dogo_script/model/dogo_model.php';
header("Content-type:application/json");

$coupon_code = $_GET['coupon_code'];
$sqcode = $_GET['sqcode'];

//__usecouponno($coupon_code);

if (empty($coupon_code) || empty($sqcode)){
    $json_result['result'] = '2';
    $json_result['message'] = '파라미터 누락';
    output_msg($json_result);
}

$_dogo = new \Dogo\Dogo();
$_dogo_model = new \Dogo\dogo_model();

// 이미 생성된 예약건인지 체크
$chk_get_order = $_dogo_model->getDogoOrders([ "barcode" => $sqcode ]);

if (!isset($chk_get_order[0]['order_num'])){
    $json_result['result'] = '2';
    $json_result['message'] = '없는 예약';
    __usecouponno($sqcode);  //혹시 몰라서 사용처리
    output_msg($json_result);
} else {
    if ($chk_get_order[0]['status'] == "R" || $chk_get_order[0]['status'] == "U"){

        if ($chk_get_order[0]['order_num'] == $coupon_code){
            $json_result['result'] = '1';
            $json_result['message'] = '성공';

            __usecouponno($sqcode);  //혹시 몰라서 사용처리
            $_dogo_model->edit_dogo_order_status($chk_get_order[0]['order_num'], 'U');
        } else {
            $json_result['result'] = '2';
            $json_result['message'] = '예약 불 일치';
            output_msg($json_result);
        }

//        //ordermts 사용처리
//        $_dogo_model->setOrderMtsCouponUse($chk_get_order[0]['order_num'] ,"2" ,"","");

        output_msg($json_result);
    } else if ($chk_get_order[0]['status'] == "C"){
        $json_result['result'] = '2';
        $json_result['message'] = '취소된 예약';
        output_msg($json_result);
    } else {
        $json_result['result'] = '2';
        $json_result['message'] = '업데이트 불가';
        output_msg($json_result);
    }
}

function __usecouponno($no){
    // 쿠폰 사용처리
    $curl = curl_init();
    $url = "http://172.31.30.15:3040/use/".$no;
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_USERAGENT,"Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1" );
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 5);
    $data = explode(";",curl_exec($curl));
    $info = curl_getinfo($curl);
    curl_close($curl);
    return "Y";
}

function output_msg($_result)
{
    $res = json_encode($_result);
    echo $res;
    exit;

}

