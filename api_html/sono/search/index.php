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

// 이미 생성된 예약건인지 체크
$chk_get_order = $_sono_model->getSonoOrders([ "order_no" => $_orderno ]);

if (count($chk_get_order) == 0) {
    output_msg(false , "미발권 상품");
}

$result_ticket_no = $chk_get_order[0]['result_ticket_no'];

$_sono->get_coupon_search($result_ticket_no);
//$_sono->get_coupon_search($_orderno);
$_result = $_sono->getResultData();
output_msg(true , $_result);

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


