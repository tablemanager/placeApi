<?php

/*
 *
 * 하이원 리프트 인터페이스
 * 
 * 작성자 : 이정진
 * 작성일 : 2017-10-27
 * 
 * 조회 : https://gateway.sparo.cc/extra/highonelift/v1/inquiry/{BARCODE}
 * 사용처리 : https://gateway.sparo.cc/extra/highonelift/v1/process/N051000000134{BARCODE}
 * 사용회수(취소) : https://gateway.sparo.cc/extra/highonelift/v1/recovery/{BARCODE}
 *
 */

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
header("Content-type:application/json");

$para = $_GET['val']; // URI 파라미터 
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더



$procmode = "recovery";
$barcode = $_GET['BARCODE'];

if(strlen($barcode) < 10){
    header("HTTP/1.0 400 Bad Request");
    $res = array("BARCODE"=>$barcode,"MSG"=>"NO SEARCH BARCODE","RESULT"=>"DBER02");
    echo json_encode($res);
    exit;
}

// REST Method 분기
switch($procmode){
    case 'inquiry':
        // 주문 조회
        $result =  get_couponinfo($barcode);
    break;
    case 'process':
        //쿠폰 사용처리
        $result =  set_use($barcode);
    break;
    case 'recovery':
        //쿠폰 회수처리 
        $result =  set_unuse($barcode);
    break;
    default:
        header("HTTP/1.0 400 Bad Request");
        $result = array("BARCODE"=>null,"MSG"=>"REQUEST ERROR","RESULT"=>"DBER00");
   
}
        header("HTTP/1.0 200");
     echo json_encode($result);

// 클라이언트 아아피
function get_ip(){
    $res = explode(",",$_SERVER["HTTP_X_FORWARDED_FOR"]);
    return $res[0];
}

function get_couponinfo($no){
  global $conn_cms3;
 $usql = "SELECT
                *
             FROM 
                spadb.high1_pincode 
             WHERE 
                 couponno = '$no' 
             LIMIT 1";

    $ures = $conn_cms3->query($usql);
    $urow = $ures->fetch_object();

    switch($urow->state){
        case 'Y':
            $res = array("BARCODE"=>$no,"MSG"=>"USED BARCODE","RESULT"=>"DBER03");
        break;
        case 'N':
            $res = array("BARCODE"=>$no,"MSG"=>"DBOK","RESULT"=>"DBOK");
        break;
        case 'C':
            $res = array("BARCODE"=>$no,"MSG"=>"CANCELED BAROCDE","RESULT"=>"DBER04");
        break;
        default:
            $res = array("BARCODE"=>$no,"MSG"=>"INTERFACE ERROR","RESULT"=>"DBER07");
    }

    return $res;
}

function set_use($no){
  global $conn_cms3;
  $usql = "SELECT
                *
             FROM 
                spadb.high1_pincode 
             WHERE 
                 couponno = '$no' 
             LIMIT 1";

    $ures = $conn_cms3->query($usql);
    $urow = $ures->fetch_object();

    switch($urow->state){
        case 'Y':
            $res = array("BARCODE"=>$no,"MSG"=>"USED BARCODE","RESULT"=>"DBER03");
        break;
        case 'N':
            $res = array("BARCODE"=>$no,"MSG"=>"USE OK","RESULT"=>"DBOK");
            $usql = "update spadb.high1_pincode set state='Y', usedate = now() where couponno = '$no' limit 1";
            $conn_cms3->query($usql);
        break;
        case 'C':
            $res = array("BARCODE"=>$no,"MSG"=>"CANCELED BAROCDE","RESULT"=>"DBER04");
        break;
        default:
            $res = array("BARCODE"=>$no,"MSG"=>"INTERFACE ERROR","RESULT"=>"DBER07");
    }

    return $res;
}

function set_unuse($no){
  global $conn_cms3;
 $usql = "SELECT
                *
             FROM 
                spadb.high1_pincode 
             WHERE 
                 couponno = '$no' 
             LIMIT 1";

    $ures = $conn_cms3->query($usql);
    $urow = $ures->fetch_object();

    switch($urow->state){
        case 'Y':
            $res = array("BARCODE"=>$no,"MSG"=>"USECANCEL OK","RESULT"=>"DBOK");
            $usql = "update spadb.high1_pincode set state='N', usedate = null where couponno = '$no' and state='Y' limit 1";

            $conn_cms3->query($usql);
        break;
        case 'N':
            $res = array("BARCODE"=>$no,"MSG"=>"UNUSED BARCODE","RESULT"=>"DB09");
        break;
        case 'C':
            $res = array("BARCODE"=>$no,"MSG"=>"CANCELED BAROCDE","RESULT"=>"DBER04");
        break;
        default:
            $res = array("BARCODE"=>$no,"MSG"=>"INTERFACE ERROR","RESULT"=>"DBER07");
    }

    return $res;
}
/*
header("HTTP/1.0 401 Unauthorized");
header("HTTP/1.0 400 Bad Request");
header("HTTP/1.0 200 OK");
header("HTTP/1.0 500 Internal Server Error");

4000	필수 파라미터 누락 및 Validation 실패 시 각 상황에 따른 메시지를 전달 함	400 Bad Request
4001	필수 해더 검증에 실패하였을 경우	412 Precondition Failed
4002	RestKey 인증에 실패 하였을 경우	401 Unauthorized

9005	검색된 리소스(데이터)가 없을경우	404 Not Found

5000	내부 시스템에서 오류가 발생하였습니다.	500 Internal Server Error



*/
?>