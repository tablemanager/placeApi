<?php
/*
 *
 * 플레이스엠 CMS 주문테이블 비고 메세지 추가
 *
 * 작성자 : 현민우
 * 작성일 : 2018-06-29
 *
 *
 */

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');

// ACL 확인
$accessip = array("115.68.42.2",
                  "115.68.42.8",
                  "115.68.42.130",
                  "52.78.174.3",
                  "106.254.252.100",
                  "115.68.182.165",
                  "13.124.139.14",
                  "13.209.232.254",
        				  "218.39.39.190",
        				  "13.124.215.30",
                  "18.163.36.64");


if(!in_array(get_ip(),$accessip)){
  header("HTTP/1.0 401 Unauthorized");
  $res = array("Result"=>"4100","Msg"=>"아이피 인증 오류 : ".get_ip());
  echo json_encode($res);
  exit;
}


header("Content-type:application/json");
$reqmsg = json_decode(file_get_contents('php://input'));


$barcode_no = $reqmsg->barcode_no;
$dammemo = "<br/>방문시간:".$reqmsg->dammemo;
if(strlen($barcode_no) < 5) exit;

 $qsql = "select * from spadb.ordermts where barcode_no = '{$barcode_no}'";
 $result = $conn_cms3->query($qsql);

if ($result->num_rows > 0) {
    // output data of each row
    while($row = $result->fetch_assoc()) {
        $sql = "update spadb.ordermts set dammemo=concat(ifnull(dammemo,''),'{$dammemo}') where id='{$row["id"]}' limit 1";
        $conn_cms3->query($sql);
        $res = "S";
    }
} else {
    $res = "E";
}
$res = "S";
echo $res;

function get_ip(){

	if(empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
		 $ip= $_SERVER["REMOTE_ADDR"].",";
	}else{
		$ip= $_SERVER["HTTP_X_FORWARDED_FOR"];
	}

	$res = explode(",",$ip);

    return trim($res[0]);
}
?>
