<?php
header("Content-type:application/json");

$_res = array("serviceStatus"=>"Active","transSeq"=>genRandomStr(24),"logDt"=> date('YmdHis'),"ipAddr"=>get_ip());
echo json_encode($_res);


//send_notice("시스템 공지 테스트","시스템 설정을 잘못했음 상품코드 : 111111TT");
function send_notice($title,$msg){

	$curl = curl_init();

	$msgarr = array("title"=>$title,
					"description"=>$msg);
	print_r($msgarr);
	curl_setopt_array($curl, array(
	  CURLOPT_URL => "http://extapi.sparo.cc/internal/sysnotice/ACT",
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => "",
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 30,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "POST",
	  CURLOPT_POSTFIELDS => json_encode($msgarr),
	  CURLOPT_HTTPHEADER => array(
		"Accept: application/vnd.tosslab.jandi-v2+json",
		"Cache-Control: no-cache",
		"Connection: keep-alive",
		"Content-Type: application/json",
		"Host: extapi.sparo.cc",
		"accept-encoding: gzip, deflate",
		"cache-control: no-cache"
	  ),
	));

	$response = curl_exec($curl);
	$err = curl_error($curl);

	curl_close($curl);

}

function get_ip(){

	if(empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
		 $ip= $_SERVER["REMOTE_ADDR"].",";
	}else{
		$ip= $_SERVER["HTTP_X_FORWARDED_FOR"];
	}

	$res = explode(",",$ip);
//print_r($res);
    return trim($res[0]);
}


// 랜덤 10진수
function genRandomStr($length = 10) {
	$characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	//abcdefghijklmnopqrstuvwxyz
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}
?>
