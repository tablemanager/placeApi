<?php

include '/home/sparo.cc/sono_script/class/class.sono.php';
include '/home/sparo.cc/sono_script/model/sono_model.php';
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

// 우리측 예약 가져오기
//$_ordermts_fillter['jpmt_id'] = '4073';
//$_ordermts_fillter['jpmt_id_ary'][0] = '13';
//$_ordermts_fillter['jpmt_id_ary'][1] = '3383';
//$_ordermts_fillter['jpmt_id_ary'][2] = '2317';
//$_ordermts_fillter['jpmt_id_ary'][3] = '308';
//$_ordermts_fillter['jpmt_id_ary'][4] = '3037';
//$_ordermts_fillter['jpmt_id_ary'][5] = '309';
//$_ordermts_fillter['jpmt_id_ary'][6] = '306';
//$_ordermts_fillter['jpmt_id_ary'][7] = '307';
//$_ordermts_fillter['jpmt_id_ary'][8] = '412';

$_ordermts_fillter['state'] = '예약완료';
//$_ordermts_fillter['sync_fac'] = '2';
$_ordermts_fillter['limit'] = '1000';
$_ordermts_fillter['cancelgu'] = '';
$_ordermts_fillter['barcode'] = '';
$_ordermts_fillter['orderno'] = $_orderno;

$_order_result = $_sono_model->getOrderMtsListNew($_ordermts_fillter);
//print_r($_order_result);
if (count($_order_result) <= 0) {
    output_msg(false , "주문없음");
}

foreach ($_order_result as $_order) {
    $_product_code = $_sono_model->getSonoProductCode($_order['itemmt_id']);
//    print_r($_product_code);
    if (empty($_product_code[0]['ticket_no'])) {
        output_msg(false , "상품없음");
    }

    // 이미 생성된 예약건인지 체크
    $chk_get_order = $_sono_model->getSonoOrders([ "order_no" => $_order["orderno"] ]);

    $_is_fail = false;

    if (isset($chk_get_order[0]['coupon_state']) && ($chk_get_order[0]['coupon_state'] =="N" || $chk_get_order[0]['coupon_state'] =="F") ){
        $_is_fail = true;
    } else {
        if (count($chk_get_order) > 0) {
            output_msg(false , "발권된 상품");
        }

        if (isset($chk_get_order[0]['order_num']) && $chk_get_order[0]['order_num'] == $_order["orderno"]) {
            if (count($chk_get_order) > 0) {
                output_msg(false , "발권된 상품");
            }
        }
    }

    if ($_is_fail === false){
        //통신 전에 일단 예약을 대기로 생성
        //중복 발권 막기 위해
        //$_is_fail == true 시 발권 대기 혹은 실패이기 때문에 insert 하지 않음 재발권 시도

        $_total_count = $_order['man1'];

        for ($j = 0; $j < $_total_count; $j++){
            $sono_first_insert = [
                "sellcode" =>$_product_code[0]['ticket_no'],
                "order_no" => $_order["orderno"],
                "user_name" => $_order['usernm'],
                "user_tel" => $_order['user_tel'],
                "rese_total_count" => $_total_count,
                "rese_count" => $j++
            ];
            $_sono_model->insert_first_sono_order_rese_count($sono_first_insert);
        }

//        $sono_first_insert = [
//            "sellcode" =>$_product_code[0]['ticket_no'],
//            "order_no" => $_order["orderno"],
//            "user_name" => $_order['usernm'],
//            "user_tel" => $_order['user_tel']
//        ];
//        $_sono_model->insert_first_sono_order_rese($sono_first_insert);
    }

    $_user_tel = str_replace('-','',$_order['user_tel']);

    $_rese_data = [];
    $_rese_data['ticketNo'] =$_product_code[0]['ticket_no'];
    $_rese_data['purchaseQty'] = $_order['man1'];
    $_rese_data['userName'] = $_order['usernm'];
//    $_rese_data['userTel'] =$_order['user_tel'];
    $_rese_data['userTel'] = $_user_tel;
    $_rese_data['businessOrder'] =$_order['orderno'];
//    $_rese_data['businessId'] = $this->_businessId;
//    $_rese_data['language'] = $this->_language;
    $_sono->set_sono_rese($_rese_data);


    $_result = $_sono->getResultData();
    $_barcode_ary = [];

    $is_request = false;

    for ($i = 0; $i < count($_result['ticketNoInfo']); $i ++){
//        echo $_result['ticketNoInfo'][$i]['resultTicketNo']."\n";
//        echo $_result['ticketNoInfo'][$i]['randomNumber']."\n";

        if (empty($_result['ticketNoInfo'][$i]['randomNumber'])){
            $_barcode = $_result['ticketNoInfo'][$i]['resultTicketNo'];
        } else {
            $_barcode = $_result['ticketNoInfo'][$i]['randomNumber'];
        }

        $sono_update = [
            "result_ticket_no" => $_result['ticketNoInfo'][$i]['resultTicketNo'],
            "random_number" => $_result['ticketNoInfo'][$i]['randomNumber'],
            "coupon_state" => "R"
        ];
//        $_sono_model->insert_sono_order_rese($sono_insert);
        $_sono_model->update_sono_order_rese($sono_update , $_order["orderno"]);
        $is_request = true;
        array_push($_barcode_ary , $_barcode);
    }

    if ($is_request === false){
        //발권 실패
        $_sono_model->update_sono_order_coupon_state($_order["orderno"] , "F");

        output_msg(false , "발권 실패");
    } else {
        $columns_barcode = implode(", ",array_keys($_barcode_ary));

        if (count($chk_get_order) > 0  || is_array($_barcode_ary)) {
            output_msg(true , $_barcode_ary);
        }
    }
}

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
   echo json_encode($_json_data , true);
   exit;

}