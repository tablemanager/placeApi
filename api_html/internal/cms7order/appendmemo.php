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

header("Content-type:application/json");
$reqmsg = json_decode(trim(file_get_contents('php://input')));

$barcode_no = $reqmsg->barcode_no;
$dammemo = "<br/>방문시간:".$reqmsg->dammemo;

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

echo $res;
?>