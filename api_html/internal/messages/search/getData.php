<?php
/**
 * Created by IntelliJ IDEA.
 * User: Connor
 * Date: 2018-07-12
 * Time: 오후 2:27
 * Edit: Larry
 */

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
require_once ('../lib/sms_lib.php');
require_once ('../lib/messages_db.php');

checkIP();

parse_str($_POST['data'], $data);

$res = getMsgList($data);

echo json_encode($res);
exit;
?>
