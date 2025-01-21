<?php

/*
 *
 * 에버랜드 회수처리 인터페이스
 * 
 * 작성자 : 이정진
 * 작성일 : 2017-12-20
 * 
 *
 */

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
header('Content-type: application/xml'); 


$mdate = date("Y-m-d");
$para = $_GET['PIN_NO']; // URI 파라미터 
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

$xml = new SimpleXMLElement('<?xml version="1.0" encoding="EUC-KR"?><result/>');

if(strlen($para) < 12){

    $track = $xml->addChild('code',0);
    $track = $xml->addChild('message',"파라미터 오류");
    
}else{


    $res = get_pininfo($para);



    switch($res['state']){
        case 'Y':
            $uqry = "update spadb.pcms_extcoupon set state_use= 'N', date_use = null where state_use= 'Y' and couponno = '$para' limit 1";
            $conn_cms3->query($uqry);
            unuseev($para);

            $track = $xml->addChild('code',1);
            $track = $xml->addChild('message',"사용취소성공");
        break;
        case 'N':
            $track = $xml->addChild('code',0);
            $track = $xml->addChild('message',"사용취소실패 / 미사용코드");
        break;
        case 'C':
            $track = $xml->addChild('code',0);
            $track = $xml->addChild('message',"사용취소실패 / 취소코드");
        break;
        default:
            $track = $xml->addChild('code',0);
            $track = $xml->addChild('message',"조회불가코드");
    }

}

print($xml->asXML());

function get_pininfo($no){

    global $conn_cms;
    global $conn_cms3;
    
    // 신규 쿠폰 테이블을 먼저 검색한다. 
    $cqry = "select * from spadb.pcms_extcoupon where couponno = '$no' limit 1";
    $row = $conn_cms3->query($cqry)->fetch_object();

    if($row->couponno){

        $result = array("couponno"=>$row->couponno,"state"=>$row->state_use);

    }else{

        // 결과가 없으면 기존 쿠폰 테이블을 검색
        $ocqry = "select * from pcmsdb.cms_extcoupon where no_coupon = '$no' limit 1";
        $ocrow = $conn_cms->query($ocqry)->fetch_object();

        if($ocrow->no_coupon){
        
            $result = array("couponno"=>$ocrow->no_coupon,"state"=>$ocrow->state_use);    
        
        }else{

            $result = null;

        }

    }

    return $result;        

//    print_r($result);
}

function unuseev($no){

	$curl = curl_init();
    $url = "http://115.68.42.8/everland/cancel?PIN_NO=".$no;
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_USERAGENT,"Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1" );
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 3);
    
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