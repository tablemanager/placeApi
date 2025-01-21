<?php

require_once('/home/sparo.cc/everland_script/lib/DBSpadbSparo.php');
require_once('/home/sparo.cc/Library/messagelib.php');
require_once('/home/placedev/cmsapps/common/conf/dbconn.conf.php');

$a_items = [];
$a_items[] = '49001';
// $a_items[] = '49040';
// $a_items[] = '49041';
// $a_items[] = '49341';
// $a_items[] = '49342';
// $a_items[] = '49430';
// $a_items[] = '49431';
// $a_items[] = '49432';
// $a_items[] = '49433';
// $a_items[] = '49525';
// $a_items[] = '49526';
// $a_items[] = '49530';

foreach ($a_items as $item) {
    $mmsrow[$item] = $connSparo2->selectSticketInfo($item);
}

print_r($mmsrow);

// $msgarr = array(
//     "dstAddr" => '01035861514',
//     "callBack" => null,
//     "msgSubject" => "모바일이용권",
//     "msgText" => '테스트',
//     "mmsFile" => null,
//     "orderNo" => '',
//     "pinType" => "QR",
//     "pinNo" => 'EL500097162PZJS830',
//     "extVal1" => "",
//     "extVal2" => "",
//     "extVal3" => "",
//     "extVal4" => ""
// ); /* 주의 $pinType이 "QR"일 경우, $mmsFile 파일은 전송하지 않는다. */
// $jsonreq = json_encode($msgarr);
// $method = "MMS";
// $data = @send_url("http://gateway.sparo.cc/internal/messages/" . $method, "POST", $jsonreq);
