<?php
/**
 * 생성자: : Larry
 * 마지막 수정 : Larry
 * 생성일: 2020-11-27
 * 사용 유무: release (test, release,inactive,dev)
 * 파일 용도: 휘닉스 강습권 바코드 체크
 */

include '/home/sparo.cc/lib/placem_helper.php';
require_once('/home/placedev/cmsapps/common/conf/dbconn.conf.php');

header("Content-type:application/json");

// ACL 확인
$accessip = array("115.68.42.2",
	"115.68.42.8",
	"115.68.42.130",
	"52.78.174.3",
	"106.254.252.100",
	"115.68.182.165",
	"13.124.139.14",
	"218.39.39.190",
	"114.108.179.112",
	"13.209.232.254",
	"13.124.215.30",
	"54.180.190.102",
	"52.78.51.243"
);
__accessip($accessip);

$para = $_GET['val']; // URI 파라미터

$apiheader = getallheaders();            // http 헤더

$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$json_result = array();

$_para_len = strlen($para);
if ($_para_len < 8){
    $json_result['code'] = "0003";
    $json_result['msg'] = "Unknown error";
} else {
	//휘닉스파크 블루캐니언(테스트용)
	//$_use_search = "SELECT * FROM ordermts  WHERE jpmt_id in ( '398')  and  barcode_no LIKE '%".$para."%'"; 
	
	//플레이스엠 강습권(라이브)
	$_use_search = "SELECT * FROM ordermts a WHERE jpmt_id='2273' and a.itemmt_id='45941' and  barcode_no LIKE '%".$para."%'"; 
	
	//echo $_use_search;

    $itemUseResult = $conn_cms3->query($_use_search);
    $jj= 0;
    while($row = $itemUseResult->fetch_object()){
        if ($row->state == "예약완료" || $row->state == "완료"){
            $jj++;
			$barcode = explode(";", $row->barcode_no); //수량이 2개 이상이면 바코드 안에 ; 구분자가 들어가 있다.
			//print_r($barcode);
			$isValid = false;			
			foreach($barcode as $v){
				if($v == $para){
					$isValid = true;
				}
			}
			if($isValid == true){
				$json_result['code'] = "0000";
				$json_result['usernm'] = $row->usernm;
				$json_result['jpmt_id'] = $row->jpmt_id;
				$json_result['jpnm'] = $row->jpnm;
				$json_result['itemmt_id'] = $row->itemmt_id;
				$json_result['itemnm'] = $row->itemnm;
				$json_result['barcode'] = $para;
				$json_result['price']['commission'] = $row->ch_rate;
				$json_result['price']['sale_price'] = $row->amt;
				$json_result['price']['net_channel'] = $row->gongdan;
				$json_result['price']['net_facilities'] = $row->saipdan;
				$json_result['msg'] = "success";
				break;
			}

        } else  if ($row->state == "취소"){
            $jj++;
            $json_result['code'] = "0001";
            $json_result['msg'] = "cancel";
        } else  {
            $jj++;
            $json_result['code'] = "0003";
            $json_result['msg'] = "Unknown error";
        }
    }

    if ($jj == 0){
        $json_result['code'] = "0002";
        $json_result['msg'] = "No reservation";
    }
}

if(empty($json_result)){
	$json_result['code'] = "0004";
	$json_result['msg'] = "Unknown error";
}

$res = json_encode($json_result);

echo $res;
