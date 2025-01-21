<?php

require '/home/sparo.cc/lib/class/class.Curl.php';
require '/home/sparo.cc/lib/class/MysqliDb.php';
require '/home/sparo.cc/lib/placem_helper.php';

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
    "13.209.232.254",
    "13.124.215.30"
);

//3번 LG
//
if(!in_array(__get_ip(),$accessip)){
    header("HTTP/1.0 401 Unauthorized");
    $res = array("RESULT"=>false,"MSG"=>"아이피 인증 오류 : ".__get_ip());
    echo json_encode($res);
    exit;
}

if (empty($_POST['log_tag'])){
    $res = array("RESULT"=>false,"MSG"=>"필수 파라미터");
    echo json_encode($res);
    exit;
}

$_db = new MysqliDb();

$_log_tag = $_db->escape($_POST['log_tag']);
$_log_result = $_db->escape($_POST['log_result']);
$_log_content = $_db->escape($_POST['log_content']);
$_log_tranid = $_db->escape($_POST['log_tranid']);
//$_log_content = $_POST['log_content'];

//$_db->setChangeDB('rds');

$agtlog = array( "log_tag"=>$_log_tag,
    "log_result" => $_log_result,
    "log_tranid" => $_log_tranid,
    "log_content"=> $_log_content
);

//$qry = "INSERT INTO cms_logs (log_tag, log_result,log_tranid ,  log_content) VALUES ({$_log_tag}, {$_log_result} ,{$_log_tranid},{$_log_content}) ON DUPLICATE KEY UPDATE log_tranid={$_log_tranid}";

$_result = $_db->insert('cms_logs', $agtlog);

if ($_result){
    $res = array("RESULT"=>true,"MSG"=>"성공");
    echo json_encode($res);
    exit;
} else {
    $res = array("RESULT"=>false,"MSG"=>"저장 실패");
    echo json_encode($res);
    exit;
}
//echo $_db->getLastQuery();

?>