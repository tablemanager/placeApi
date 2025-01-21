<?php

/*
 *
 * 아쿠아필드 주문 조회 인터페이스
 * 
 * 작성자 : 이정진
 * 작성일 : 2018-03-27
 * 
 * http://gateway.sparo.cc/extra/aquafield/
 */
error_reporting(0);

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
header("Content-type:application/json");

$para = $_GET['val']; // URI 파라미터 
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

// 파라미터 
$itemreq = explode("/",$para);
$jsonreq = trim(file_get_contents('php://input'));

// 인터페이스 로그






// REST Method 분기
switch($apimethod){
    case 'GET':
        // 주문 조회
       
    break;
    case 'POST':
        // 주문 등록
       
    break;
    case 'PATCH':
        // 주문 취소, 변경
     
    break;
    default:
        header("HTTP/1.0 400 Bad Request");
        $res = array("Result"=>"4000","Msg"=>"파라미터 오류");
        echo json_encode($res);
}



// 클라이언트 아아피
function get_ip(){

    $res = explode(",",$_SERVER["HTTP_X_FORWARDED_FOR"]);
    return $res[0];
}

?>