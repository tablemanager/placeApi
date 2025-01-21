<?php
require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
header("Content-type:application/json");


$reqmsg = json_decode(trim(file_get_contents('php://input')));

$orderno = $reqmsg->OrderNo;

$typecode = $reqmsg->Ctype;
$sellcode = $reqmsg->SellCode;
$msg = $reqmsg->OrderNo;
$cnoarr = json_encode($reqmsg->CouponNo);
$userhp = $reqmsg->UserHp;
$msg = $reqmsg->Msg;
$callback = $reqmsg->CallBack;

if(strlen($callback) > 5){
    $curlstr = $callback;
}else{
    $curlstr = genRandomStr(18);
}

$qry = "select * from spadb.eticket_msg where orderno = '{$orderno}' limit 1";
$row = $conn_cms3->query($qry)->fetch_object();

if($row){
    // 동일 주문정보가 있으면 전화번호 수정해서 재발송
    $qsql = "UPDATE spadb.eticket_msg SET
                userhp   = '{$userhp}',
                couponno = '{$cnoarr}',
                msg = '{$msg}',
                syncresult = 'S',
                regdate = now() 
             WHERE id = '".$row->id."' limit 1";
             // 기존 콜백
             $curlstr = $row->curlstr;
}else{
    $qsql = "INSERT spadb.eticket_msg SET
                orderno = '{$orderno}',
                curlstr = '{$curlstr}',
                typecode = '{$typecode}',
                sellcode = '{$sellcode}',
                couponno = '{$cnoarr}',
                userhp   = '{$userhp}',
                msg = '{$msg}',
                syncresult = 'S'";
}

$res = $conn_cms3->query($qsql);


if($res){
    $result = array("result"=>"S", "curl"=>"https://sparo.cc/".$curlstr);

}else{
    $result = array("result"=>"E", "curl"=>NULL);
};

echo json_encode($result);


function genRandomStr($length = 12) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

?>
