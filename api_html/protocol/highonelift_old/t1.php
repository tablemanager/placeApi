<?php

//echo getuseev("S06416022190");
	echo $_SERVER["HTTP_X_FORWARDED_FOR"];
	echo "-";
	echo get_ip();
	echo "-";

function get_ip(){

    $res = explode(",",$_SERVER["HTTP_X_FORWARDED_FOR"]);
	echo json_encode($res);
//	print_r($res);
    
	return $res[0];

}

function getuseev($no) {

    $curl = curl_init();
    $url = "https://api.placem.co.kr/foreigner/ev.php?no=".$no."&pc=CP";
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_USERAGENT,"Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1" );
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 5);
    
    $data = curl_exec($curl);
    $info = curl_getinfo($curl);
    curl_close($curl);

    $result = simplexml_load_string($data) or die("Error: Cannot create object");

    return $result->PIN_STATUS;

}

?>