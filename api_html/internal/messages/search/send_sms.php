<?php
/**
 * Created by IntelliJ IDEA.
 * User: Connor
 * Date: 2018-08-03
 * Time: 오후 3:23
 */

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
require_once ('/home/sparo.cc/order_script/lib/SendData_Script.php');
//
//if ($_POST['tel'] == '') {
//    echo "err";
//    exit;
//}

$ch = curl_init();
$userhp = $_POST['tel'];                                                       // 수신번호
$callBack = $_POST['callback'];                                                // 발신자번호
$subject = $_POST['subject'] != '' ? $_POST['subject'] : '';                   // 템플릿 코드 또는 제목
$msg = $_POST['text'];                                                         // 내용
$orderNo = $_POST['orderno'];                                                  // 주문번호
$type = $_POST['type'];                                                        // 메세지 타입
$profile = $_POST['profile'];                                                  // 프로필키
$ext1 = $_POST['ext1'];

$pinNo = $_POST['couponno'];
$pinType = $_POST['coupon_type'];
$mmsFile = $_POST['file'];

//'couponno': $('#modal_couponno').val(),
//                    'coupon_type': $('#modal_coupon_type').val()

if (!empty($_POST['ext1'])) $ext1 = $_POST['ext1'];
if (!empty($_POST['ext2'])) $ext2 = $_POST['ext2'];
if (!empty($_POST['ext3'])) $ext3 = $_POST['ext3'];
if (!empty($_POST['ext4'])) $ext4 = $_POST['ext4'];

// 글자수가.. key로 되어있는 경우가 있음
if (strlen($profile) == 40) {
    $profile_array = array(
        'f11c00d8beafedf5fc03b65a473613a8294f67a1' => 'hamac',
        '326bd99cea2f7d92f2e9355684f6c9afa57ec5bd' => 'high1',
    );
    $profile = $profile_array[$profile];
}

if ($profile == '') $profile = 'hamac';

$msgarr = array(
    "dstAddr" => $userhp,
    "callBack" => $callBack,
    "msgSubject" => $subject,
    "msgText" => $msg,
    "mmsFile" => "",
    "kakao_profile" => $profile,
    "orderNo" => $orderNo,
    "pinType" => $pinType,
    "pinNo" => $pinNo,
    "extVal1" => $ext1,
    "extVal2" => $ext2,
    "extVal3" => $ext3,
    "extVal4" => $ext4
);

$data = send_url($ch, "http://gateway.sparo.cc/internal/messages/".$type, "POST", json_encode($msgarr));
$ch = curl_close();
$res = json_decode($data,true);

if ($res['result'] == true) {
    echo json_encode(array('TRUE', 'SMS 발송성공'));
    exit;
}else {
    echo json_encode(array('FALSE', 'SMS 발송에 실패하였습니다.'));
    exit;
}
?>