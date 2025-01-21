<?php
include '/home/sparo.cc/tourpass_script/class/class.jeonbuk.php';
include '/home/sparo.cc/tourpass_script/model/jeonbuk_model.php';


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


$_jeon = new \Jeonbuk\Jeonbuk();
$_jeon_model = new \Jeonbuk\Jeonbuk_model();

// 우리측 예약 가져오기
$_ordermts_fillter['jpmt_id'] = '4080';
$_ordermts_fillter['state'] = '취소';
$_ordermts_fillter['cancelgu'] = 'N';
$_ordermts_fillter['limit'] = '1000';
$_ordermts_fillter['barcode'] = 'Y';
$_ordermts_fillter['usedate'] = $mdate = date("Y-m-d");

$_ordermts_fillter['orderno'] = $_orderno;

$_order_result = $_jeon_model->getOrderMtsList($_ordermts_fillter);


if (count($_order_result) <= 0) {
    output_msg(false , "주문없음");
}

foreach ($_order_result as $_order) {
    $_orderno = $_order['orderno'];
    $_barcode_no = $_order['barcode_no'];

    // 취소 전 가능여부 체크
    $set_seqno = $_jeon_model->api_log_edit('L003');
    $auth_check = $_jeon->setReseCancelAuthCheck($set_seqno, $_barcode_no);
    $_jeon_model->api_log_edit('L003', $set_seqno, $auth_check['request'], $auth_check['response']);
    if ($auth_check['response']['result_info']['result'] != '31') {
        // 실패 시 건너뜀.
        if (count($_order_result) <= 0) {
            output_msg(false , "취소불가 : ".$auth_check['response']['result_info']['result']);
        }
    }

    // 주문 취소 요청
    $set_seqno = $_jeon_model->api_log_edit('L002');
    $result = $_jeon->setReseCancel($set_seqno, $_barcode_no);
    $_jeon_model->api_log_edit('L002', $set_seqno, $result['request'], $result['response']);

    // 취소 전 가능여부를 다시 로출하여 최종적으로 취소가 완료되었는지 체크
    $set_seqno = $_jeon_model->api_log_edit('L003');
    $auth_recheck = $_jeon->setReseCancelAuthCheck($set_seqno, $_barcode_no);
    if ($auth_recheck['response']['result_info']['result'] != '28') {

        output_msg(false , "취소 미반영 : ".$auth_recheck['response']['result_info']['result']);
    }

    // 취소 결과가 성공으로 오면 최종 우리 주문의 취소 플래그를 변경한다.
    $_jeon_model->setOrderMtsCancelGu("Y" , $_orderno);

    // 전북투어 주문 테이블에도 반영
    $_jeon_model->edit_jeonbuk_order_status($_orderno, 'C');

    output_msg(true , $_barcode_no);

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

    echo json_encode($_json_data);
    exit;

}