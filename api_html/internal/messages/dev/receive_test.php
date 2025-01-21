<?php
require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
require_once ('../send_msg_api.php');
require_once ('../kakao_template.php');
require_once ('../lib/messages_db.php');

$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders();            // http 헤더
$auth = $apiheader['Authorization'];
echo "auth=".$auth;
?>