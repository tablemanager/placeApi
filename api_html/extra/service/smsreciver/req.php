<?php
require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
header("Content-type:application/json");

$edate = date("Y-m-d H:i:s");
$sdate = date('Y-m-d H:i:s', strtotime('-5 mins'));

// 6시간 이전 로그는 지운다.(개인정보 이슈)
$dsql = "delete from spadb.crowling_sms where regdate < '$sdate'";
$conn_cms3->query($dsql);


$sender = $_REQUEST['s'];
$msg = $_REQUEST['m'];
$regdate = $_REQUEST['r'];
$mode =  $_REQUEST['mode'];
$shp =  $_REQUEST['shp'];
$num =  preg_replace("/[^0-9]*/s", "", $msg);

if($mode == "msg"){

   $obcode = $conn_cms3->query("select * from spadb.crowling_sms order by regdate desc");
   while($row= $obcode->fetch_object()){
     $rcode[] = $row;
   }

}else{
    if($sender != '' and $msg != ''){

        $rcode = "1000";

        $sql = "insert crowling_sms set sender = '".md5($sender)."', msg = '$num', regdate = now()";
        $conn_cms3->query($sql);

    }else{

        $rcode = "9000";

    }
}

$res = array("result"=>$rcode);
echo json_encode($res, JSON_UNESCAPED_UNICODE);
?>
