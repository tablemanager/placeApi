<?php

/*
 *
 * 하이원 리프트 인터페이스
 * 
 * 작성자 : 이정진
 * 작성일 : 2017-10-27
 * 

## 쿠폰정보 조회 ## 
http://extapi.sparo.cc/protocol/highonelift_m/lift_search?BARCODE=R40310624386


## 사용 처리 ## 
http://extapi.sparo.cc/protocol/highonelift_m/lift_use?BARCODE=R40310624386

## 사용 처리/취소 ## 
http://extapi.sparo.cc/protocol/highonelift_m/lift_usecancel?BARCODE={쿠폰코드}

 *
 */

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
header("Content-type:application/json");

$para = $_GET['func']; // URI 파라미터 
$q = $_GET['no']; // URI 파라미터 
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더



// 파라미터 
$jsonreq = trim(file_get_contents('php://input'));

$procmode = $para;
$barcode = $q;

if(strlen($barcode) < 10){
    header("HTTP/1.0 400 Bad Request");
    $res = array("BARCODE"=>$barcode,"MSG"=>"NO SEARCH BARCODE","RESULT"=>"DBER02");
    echo json_encode($res);
    exit;
}

// REST Method 분기
switch($procmode){
    case 'lift_search':
        // 주문 조회
        $result =  get_couponinfo($barcode);
    break;
    case 'lift_use':
        //쿠폰 사용처리
        $result =  set_use($barcode);
    break;
    case 'lift_usecancel':
        //쿠폰 회수처리 
        $result =  set_unuse($barcode);
    break;
    case 'placem_cancel':
        //쿠폰 회수처리 
        $result = set_couponcancel($barcode);
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

function set_couponcancel($no){
  global $conn_rds;

  $cancelsql = "update cmsdb.high1_extcoupon set state= 'C',canceldate = now() WHERE state= 'N' and couponno = '$no' LIMIT 1";
  $conn_rds->query($cancelsql);

}

function get_couponinfo($no){
  global $conn_rds;
 $usql = "SELECT
                *
             FROM 
                cmsdb.high1_extcoupon  
             WHERE 
                 couponno = '$no' 
             LIMIT 1";

    $ures = $conn_rds->query($usql);
    $urow = $ures->fetch_object();

    switch($urow->state){
        case 'Y':
            $res = array("BARCODE"=>$no,"MSG"=>"USED BARCODE","RESULT"=>"DBER03");
        break;
        case 'N':
            $res = array("BARCODE"=>$no,"MSG"=>"USE ABLE","RESULT"=>"DBOK");
        break;
        case 'C':
            $res = array("BARCODE"=>$no,"MSG"=>"CANCELED BAROCDE","RESULT"=>"DBER04");
        break;
        default:
            $res = array("BARCODE"=>$no,"MSG"=>"NO SEARCH BARCODE","RESULT"=>"DBER02");
    }

    return $res;
}

function set_use($no){
  global $conn_rds;
  $usql = "SELECT
                *
             FROM 
                cmsdb.high1_extcoupon  
             WHERE 
                 couponno = '$no' 
             LIMIT 1";

    $ures = $conn_rds->query($usql);
    $urow = $ures->fetch_object();

    switch($urow->state){
        case 'Y':
            $res = array("BARCODE"=>$no,"MSG"=>"USED BARCODE","RESULT"=>"DBER03");
        break;
        case 'N':
            $res = array("BARCODE"=>$no,"MSG"=>"USE OK","RESULT"=>"DBOK");
            $usql = "update cmsdb.high1_extcoupon set state='Y', usedate = now() where couponno = '$no' limit 1";
            $conn_rds->query($usql);
            usecouponno($no);
        break;
        case 'C':
            $res = array("BARCODE"=>$no,"MSG"=>"CANCELED BAROCDE","RESULT"=>"DBER04");
        break;
        default:
            $res = array("BARCODE"=>$no,"MSG"=>"NO SEARCH BARCODE","RESULT"=>"DBER02");
    }

    return $res;
}

function set_unuse($no){
  global $conn_rds;
 $usql = "SELECT
                *
             FROM 
                cmsdb.high1_extcoupon  
             WHERE 
                 couponno = '$no' 
             LIMIT 1";

    $ures = $conn_rds->query($usql);
    $urow = $ures->fetch_object();

    switch($urow->state){
        case 'Y':
            $res = array("BARCODE"=>$no,"MSG"=>"USECANCEL OK","RESULT"=>"DBOK");
            $usql = "update cmsdb.high1_extcoupon set state='N' where couponno = '$no' limit 1";
            $conn_rds->query($usql);
        break;
        case 'N':
            $res = array("BARCODE"=>$no,"MSG"=>"UNUSED BARCODE","RESULT"=>"DB09");
        break;
        case 'C':
            $res = array("BARCODE"=>$no,"MSG"=>"CANCELED BAROCDE","RESULT"=>"DBER04");
        break;
        default:
            $res = array("BARCODE"=>$no,"MSG"=>"NO SEARCH BARCODE","RESULT"=>"DBER02");
    }

    return $res;
}

function usecouponno($no){

	$curl = curl_init();
    $url = "http://115.68.42.2:3040/use/".$no;
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_USERAGENT,"Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1" );
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 5);
    
    $data = explode(";",curl_exec($curl));
    $info = curl_getinfo($curl);
    curl_close($curl);
    if($data[1] == "0"){
        return "N";
    }else{
        return "Y";
    }
}


?>