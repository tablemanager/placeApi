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
$_ordermts_fillter['state'] = '예약완료';
$_ordermts_fillter['sync_fac'] = 'C';
$_ordermts_fillter['limit'] = '1000';
$_ordermts_fillter['cancelgu'] = '';
$_ordermts_fillter['barcode'] = '';
$_ordermts_fillter['orderno'] = $_orderno;

$_order_result = $_jeon_model->getOrderMtsList($_ordermts_fillter);


if (count($_order_result) <= 0) {
    output_msg(false , "주문없음");
}

//$_jeon->setJandiHook("연결 오류", "확인 필요");

foreach ($_order_result as $_order) {
    // 전북투어의 카드 종류 가져오기
    $_product_code = $_jeon_model->getJeonBukProductCode($_order['itemmt_id']);

    if (empty($_product_code[0]['product_code'])) {

        output_msg(false , "상품없음");
    }

    // 전문 SEQ 생성
    $set_seqno = $_jeon_model->api_log_edit('L001');

    // 실패하면 일단 패스, 다음 배치 때 다시 시도
    if (!$set_seqno) {

        output_msg(false , "seq 미생성");

    }

    // 전북투어 주문 테이블 insert
    $_jeon->setJeonBarcode($set_seqno);
    $get_barcode = $_jeon->getJeonBarcode();
    if (!$get_barcode) {
        output_msg(false , "바코드 미생성");
    }

    if (empty($_order["man1"]) || $_order["man1"] == null || $_order["man1"] == 0){
        $_order["man1"] = 1;
    }

    $jeon_insert = [
        "channel_gubun" => 'kiosk',
        "order_num" => $_order["orderno"],
        "barcode" => $get_barcode,
        "cnt" => $_order["man1"],
        "status" => "N"
    ];

    $_jeon_model->insert_jeonbuk_order_rese($jeon_insert);

    // 제대로 들어갔는지 체크
    $_fillter['barcode'] = $get_barcode;
    $get_order = $_jeon_model->getJeonBukOrders($_fillter);
    if ($get_order[0]['barcode'] != $get_barcode) {
        output_msg(false , "바코드 불일치");
    }

    $_order['couponCd'] = $get_barcode;
    $_order['tpass_type'] = $_product_code[0]['product_code'];

    // 주문 생성 요청
    $result = $_jeon->setOrderRese($set_seqno, $_order);

//    $_jeon->setJandiHook("연결 오류", "확인 필요");

    if (!isset($result['request'])) {
        $_jeon->setJandiHook("연결 오류", "확인 필요");
        continue;
    }

    // 생성된 전문 SEQ 의 기록 업데이트
    $_jeon_model->api_log_edit('L001', $set_seqno, $result['request'], $result['response']);

    if (isset($result['response']['result']) && $result['response']['result'] != '00') {
        $_jeon->setJandiHook("연결 오류", "확인 필요");
        $res_errMsg = $_order["orderno"] . " - 응답 에러 : " . $result['response']['result_msg'];
        output_msg(false , $res_errMsg);

    } else if (!isset($result['response'])) {
        output_msg(false , "통신 오류");
    }

    // 주문생성이 성공하면 우리 주문테이블의 바코드 정보 업데이트
    $_jeon_model->setOrderMtsReseBarcode($_jeon->getJeonBarcode(), $_order['orderno']);

    // 전북투어 주문 테이블에도 반영
    $_jeon_model->edit_jeonbuk_order_status($_order['orderno'], 'R');

    output_msg(true , $_jeon->getJeonBarcode());


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

exit;
