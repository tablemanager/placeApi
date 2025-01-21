<?php
/*
 *
 * 오월드 키오스크 연동 인터페이스
 *
 * 작성자 : 이정진, 김민태
 * 작성일 : 2018-10-15
 *
 * 티켓 조회(POST) https://gateway.sparo.cc/extra/oworld/ordered
 * 티켓 사용(POST) https://gateway.sparo.cc/extra/oworld/used
 * 티켓 회수(POST) https://gateway.sparo.cc/extra/oworld/unused
 * 공개키 PATH /home/sparo.cc/application/keys/oworld/placem.pub
 * 개인키 PATH /home/sparo.cc/application/keys/oworld/placem.pem
 * https://gateway.sparo.cc/extra/oworld/testqr 테스트 QR 생성
 *
 *
 */

//error_reporting(0);

require_once('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
header("Content-type:application/json");

$data = array();
$para = $_GET['tel']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

//$sql = "select * from ordermts where grmt_id = '' and  ";
//$sql = "select * from ordermts where grmt_id = '' and  ";


function usecouponno($no){
    // 쿠폰 사용처리
    $curl = curl_init();
    $url = "http://172.31.30.15:3040/use/".$no;
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