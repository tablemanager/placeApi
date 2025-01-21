<?php

/*
 *
 * 주문 테이블 사용처리 인터페이스
 * 
 * 작성자 : 이정진
 * 작성일 : 2018-06-13
 * 
 * 사용(GET)			: https://gateway.sparo.cc/internal/usecoupon/{BARCODE}
 */

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
header("Content-type:application/json");

$para = $_GET['val']; // URI 파라미터 
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

// 파라미터 
$itemreq = explode("/",$para);
$jsonreq = trim(file_get_contents('php://input'));

$couponno = $itemreq[0];

if(ctype_alnum($couponno) and strlen($no) > 5){
    $osql = "select * from spadb.ordermts_coupons where couponno = '$couponno' limit 1";
    $res = $conn_cms3->query($osql)->fetch_object();
    $idx = $res->order_id;
    if($idx){
        $usql = "update spadb.ordermts set usegu = '1', usegu_at= now() where usegu = '2' and id = '$idx' limit 1";
        $useres = $conn_cms3->query($usql);
        if($useres) echo "$idx";
    }
}else{
    echo "E";
}

// 클라이언트 아아피
function get_ip(){
    $res = explode(",",$_SERVER["HTTP_X_FORWARDED_FOR"]);
    return $res[0];
}


?>