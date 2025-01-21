<?php

/*
 *
 * 코코몽 키즈카페 사용처리 인터페이스
 * 
 * 작성자 : 이정진
 * 작성일 : 2018-04-06
 * 
 * https://gateway.sparo.cc/extra/cocomong/
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
$no = $itemreq[0];

// 인터페이스 로그

$logdata= array("no"=>$no,
                "appcode"=>"cocomong",
                "method"=>$apimethod
);

setlog($logdata);
// REST Method 분기
switch($apimethod){
//    case 'GET':
        // 주문 조회
       
  //  break;
    case 'POST':
        // 주문 등록
        $res = set_use($no);
        echo json_encode($res);       
    break;
    case 'PATCH':
        // 주문 취소, 변경
        $res = array("Result"=>"1000","Msg"=>"쿠폰회수 성공");
        echo json_encode($res);
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

function set_use($no){
  global $conn_cms3;
  
  $idx = explode("_",$no);
  $id = str_replace("PM","",$idx[0]);

      $usql = "SELECT
                *
             FROM 
                spadb.ordermts 
             WHERE 
                 id = '".$id."' 
             LIMIT 1";

    $ures = $conn_cms3->query($usql);
    $urow = $ures->fetch_object();

    switch($urow->usegu){
        case 1:
            $res = array("Result"=>"1000","Msg"=>"쿠폰사용 성공");
        break;
        case 2:
            $res = array("Result"=>"1000","Msg"=>"쿠폰사용 성공");

            $usql = "update spadb.ordermts set usegu='1', usegu_at = now() where grmt_id = '3346' and id = '".$id."' limit 1";
            $conn_cms3->query($usql);
        break;
        default:
            $res = array("Result"=>"9000","Msg"=>"쿠폰사용 실패");
    }

    return $res;
}

// 클라이언트 아아피
function setlog($logdata){

$no = $logdata['no'];
$apitype = $logdata['appcode'];
$apimode = $logdata['cmd'];
$method = $logdata['method'];
$desc = array("method"=>$method);
    

$infojson = json_encode($desc);
global $conn_cms3;
$logsql = "insert 
                spadb.logs
           set 
                logdate = now(),
                logtype = '$apitype',
                couponno = '$no',
                logip = '".get_ip()."',
                info = '$infojson'";

$conn_cms3->query($logsql);

}
?>