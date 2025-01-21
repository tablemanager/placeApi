<?php

/*
 *
 * 휘닉스파크 연동 인터페이스
 *
 * 작성자 : 미카엘
 * 작성일 : 2018-12-13
 *
 * 조회(GET)			: https://gateway.sparo.cc/everland/sync/{BARCODE}
 * 발권(POST)		: https://gateway.sparo.cc/everland/sync/{BARCODE}
 * 발권취소(PATCH)	: https://gateway.sparo.cc/everland/sync/{BARCODE}
 */

//http://gateway.sparo.cc/phoenix/info/18100409982
require_once ('/home/sparo.cc/phoenix_script/lib/phoenixApi.php');
require_once ('/home/sparo.cc/phoenix_script/lib/ConnSparo2.php');
header("Content-type:application/json");

// ACL 확인
//$accessip = array("115.68.42.2",
//    "115.68.42.8",
//    "115.68.42.130",
//    "52.78.174.3",
//    "106.254.252.100",
//    "115.68.182.165",
//    "13.124.139.14",
//    "13.209.232.254"
//);
//
//if(!in_array(get_ip(),$accessip)){
//    header("HTTP/1.0 401 Unauthorized");
//    $res = array("Result"=>"4100","Msg"=>"아이피 인증 오류 : ".get_ip());
//    echo json_encode($res);
//    exit;
//}

$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더
$para = $_GET['val']; // URI 파라미터
// 파라미터
$itemreq = explode("/",$para);
$phoenixApi = new phoenixApi();
$get_access = $phoenixApi->get_access_token();

if($get_access){
	$get_token = json_decode($get_access);


	$fields = array(
		'bsuCd'=>'1',
		'tcktDivCd'=>'10',
		'sesnDivCd'=>'2019',
		'cyberMbId'=>$para

	);
	$fields = json_encode($fields);

	$member_responce = $phoenixApi->EXTPKGCOM208($get_token->access_token, $fields);
//	echo $fields;
//	print_r($member_responce);

	echo json_encode($member_responce);

}else{
	echo "Token Fail\n";
	print_r($get_access);
}

// 클라이언트 아아피
function get_ip(){
    $res = explode(",",$_SERVER["HTTP_X_FORWARDED_FOR"]);
    return trim($res[0]);
}
?>