<?php
$conn_sparo2 = new mysqli("ip-172-31-21-120.ap-northeast-2.compute.internal", "cmsdb", "wsoqb?^NRl#PJluzxa3", "spadb");
$conn_cms2 = new mysqli("cmsdb130.ihw0kgi52o.sparo.cc", "cmsdb", "wsoqb?^NRl#PJluzxa3", "spadb");
$conn_cms = new mysqli("cmsdb2.ilw0k2i52o.sparo.cc", "cmsdb", "wsoqb?^NRl#PJluzxa3", "spadb");

header("Content-type:application/json");


$reqmsg = json_decode(trim(file_get_contents('php://input')));

$cmode = $reqmsg->Mode;
$typecode = $reqmsg->Ctype;
$sellcode = $reqmsg->SellCode;
$msg = $reqmsg->OrderNo;
$cnoarr = json_encode($reqmsg->CouponNo);
$userhp = $reqmsg->UserHp;
$msg = $reqmsg->Msg;

$qry = "select * from spadb.eticket_msg where orderno = '{$orderno}' limit 1";
$row = $conn_cms3->query($qry)->fetch_object();

if($row){
    // 동일 주문정보가 있으면 전화번호 수정해서 재발송
    $qsql = "UPDATE spadb.eticket_msg SET
                userhp   = '{$userhp}',
                couponno = '{$cnoarr}',
                syncresult = 'S'
             WHERE id = '".$row->id."' limit 1";
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
