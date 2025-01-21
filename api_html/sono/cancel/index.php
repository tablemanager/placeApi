<?php

include '/home/sparo.cc/sono_script/class/class.sono.php';
include '/home/sparo.cc/sono_script/model/sono_model.php';

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

$_get_ip = explode(",",$_SERVER["HTTP_X_FORWARDED_FOR"]);

if(!in_array($_get_ip[0],$accessip)){
    header("HTTP/1.0 401 Unauthorized");
    $res = array("RESULT"=>false,"MSG"=>"아이피 인증 오류 : ".$_get_ip[0]);
    echo json_encode($res);
    exit;
}

$jsonreq = trim(file_get_contents('php://input'));
$data = json_decode($jsonreq,TRUE);

$_orderno = $data['orderno'];

if (empty($_orderno)){
    output_msg(false , "파라미터 누락");
}

$_sono = new Sono\Sono();
$_sono_model = new \Sono\sono_model();

$_ordermts_fillter['jpmt_id_ary'][0] = '430';
$_ordermts_fillter['jpmt_id_ary'][1] = '4292';
//$_ordermts_fillter['jpmt_id_ary'][1] = '3383';
//$_ordermts_fillter['jpmt_id_ary'][2] = '2317';
//$_ordermts_fillter['jpmt_id_ary'][3] = '308';
//$_ordermts_fillter['jpmt_id_ary'][4] = '3037';
//$_ordermts_fillter['jpmt_id_ary'][5] = '309';
//$_ordermts_fillter['jpmt_id_ary'][6] = '306';
//$_ordermts_fillter['jpmt_id_ary'][7] = '307';
//$_ordermts_fillter['jpmt_id_ary'][8] = '412';

//$_ordermts_fillter['state'] = '취소';
//$_ordermts_fillter['cancelgu'] = 'N';
$_ordermts_fillter['limit'] = '1';
//$_ordermts_fillter['barcode'] = 'Y';
//$_ordermts_fillter['usedate'] = date("Y-m-d");

$_ordermts_fillter['id'] = $_orderno;
// $_ordermts_fillter['orderno'] = $_orderno;

$_order_result = $_sono_model->getOrderMtsListNew($_ordermts_fillter);

if (count($_order_result) <= 0) {
    output_msg(false , "주문없음");
}

$_sono->set_sono_cancel($_order_result[0]["orderno"]);
$_result = $_sono->getResultData();

if (!isset($_result['ticketNoInfo'])) {
    output_msg(false , print_r($_result, true));
}

$msg = '';
$pass_cnt = 0;
foreach ($_result['ticketNoInfo'] as $_coupon) {
    if ($_coupon['resultCode'] == '0000') {
        $pass_cnt++;
    }

    $msg .= ($msg) ? "\n\n" : "";
    $msg .= '번호: ' . $_coupon['resultTicketNo'] . "\n결과: " . $_coupon['resultMsg'] . ' (' . $_coupon['resultCode'] . ')';
}

// 모든 바코드 취소 성공하면 DB 에 반영
if (count($_result['ticketNoInfo']) == $pass_cnt) {
    $_sono_model->setOrderMtsCancelGu("Y", $_order_result[0]["orderno"]);
    $_sono_model->set_order_cancel_sono("C", $_order_result[0]["orderno"]);
}

output_msg(false, $msg);

// $barcode_tmp = explode(';', $_order_result[0]['barcode_no']);
// foreach ($barcode_tmp as $_barcode) {}

// foreach ($_order_result as $_order) {
//     // 발권이 된 예약인지 체크
// //    $chk_get_order = $_sono_model->getSonoOrders([ "order_no" => $_order["orderno"] ]);
// //    if (count($chk_get_order) > 0) {
// //        output_msg(false , "발권된 상품");
// //    }

//     $_sono->set_sono_cancel($_order["orderno"]);
    // $_result = $_sono->getResultData();

//     for ($i = 0; $i < count($_result['ticketNoInfo']); $i ++){

//         if ($_result['ticketNoInfo'][$i]['resultCode'] == "0000"){

// //            $_sono_model->set_order_cancel_sono("Y" , $_orderno);
//             $_sono_model->set_order_cancel_sono("C" , $_orderno);

//             output_msg(true , $_orderno);

//         } else  if ($_result['ticketNoInfo'][$i]['resultCode'] == "SOCIAL-104"){
//             output_msg(false , "사용된 쿠폰");
//         } else {
//             output_msg(false , "error");
//         }

//     }

// }

function output_msg($_status , $_msg)
{
    $_json_data = array();

    if ($_status === true){
        $_json_data = array(
            "RESULT"=>true,
            "BARCODE"=>$_msg,
            "MSG"=>"success"
        );
    } else {
        $_json_data = array(
            "RESULT"=>false,
            "BARCODE"=>"",
            "MSG"=>$_msg
        );
    }

    echo json_encode($_json_data);
    exit;

}
