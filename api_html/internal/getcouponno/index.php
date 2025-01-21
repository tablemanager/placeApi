<?php

/*
 *
 * 쿠폰번호 발권 인터페이스
 *
 * 작성자 : 이정진
 * 작성일 : 2018-07-02
 *
 * 사용(POST)			: https://gateway.sparo.cc/internal/getcouponno
 *
 * JSON {"CHCODE":"채널코드", "ORDERNO":"20180802", SELLCODE:"P10101_1", "UNIT":"1", "USERNM":"테스트","USERHP":"01090901678"} ;
 */

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
header("Content-type:application/json");

$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

// 파라미터
$itemreq = explode("/",$para);
$jsonreq = trim(file_get_contents('php://input'));

//$couponno = $itemreq[0];


$accessip = array("115.68.42.2",
                  "115.68.42.8",
                  "115.68.42.130",
                  "52.78.174.3",
                  "106.254.252.100",
                  "115.68.182.165",
                  "13.124.139.14",
                  "18.163.36.64"
                );

if(!in_array(get_ip(),$accessip)){
    header("HTTP/1.0 401 Unauthorized");
    $res = array("Result"=>"4100","Msg"=>"아이피 인증 오류 : ".get_ip());
    echo json_encode($res);
    exit;
}

$order=json_decode($jsonreq);


$facode = $order->FACCODE;
$chcode = $order->CHCODE;
$orderno = $order->ORDERNO;
$sellcode = $order->SELLCODE;
$unit = $order->UNIT;
$usernm = $order->USERNM;
$userhp = $order->USERHP;

if(!$unit) $unit = 1;
if(strlen($orderno) < 5){
    //header("HTTP/1.0 401");
    exit;
}

// 쿠폰 생성이 없고 핀자동생성 상품의 경우
$ccnt = $conn_cms->query("select * from pcmsdb.cms_coupon where items_id = '$sellcode'")->num_rows;
$ctype = $conn_cms->query("select type_coupon from CMSDB.CMS_ITEMS where item_id = '$sellcode'")->fetch_object();

if($ctype->type_coupon == "NUM" or $ctype->type_coupon == "CHAR" ) $facode = $ctype->type_coupon;

switch($facode){
	case 'NUM':
		$_results = json_encode(array($sellcode.genRandomNum(11)));
	break;
	case 'CHAR':
		$_results = json_encode(array(genRandomStr(16)));
	break;
	case 'DM':
		$_results = getdmcoupon($order);
	break;
    // 20221129 tony https://placem.atlassian.net/browse/P2CCA-212
    // 씨트립 주문건. 알펜시아 실시간 쿠폰번호 가져와서 리턴한다.
    case 'AL';
        $alreq =  array("orderno"=>$orderno,
                        "qty" => $unit,
                        "usernm"=>$usernm,
                        "userhp"=>$userhp);
        $_results = get_alpensiano($sellcode, $alreq);
    break;
	default:
		$_results = getpcmscoupon($order);

}

echo $_results;
//echo $facode;
// 랜덤 스트링
function genRandomStr($length = 10) {
	$characters = '123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}

// 랜덤 10진수
function genRandomNum($length = 10) {
	$characters = '0123456789';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}


// 클라이언트 아아피
function get_ip(){

	if(empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
		 $ip= $_SERVER["REMOTE_ADDR"].",";
	}else{
		$ip= $_SERVER["HTTP_X_FORWARDED_FOR"];
	}

	$res = explode(",",$ip);

    return trim($res[0]);
}

function getdmcoupon($order){
    global $conn_cms2;


	$facode = $order->FACCODE;
	$chcode = $order->CHCODE;
	$orderno = $order->ORDERNO;
	$sellcode = $order->SELLCODE;
	$unit = $order->UNIT;
	$usernm = $order->USERNM;
	$userhp = $order->USERHP;

	$ii = array("42869"=>"41310583",
					  "42870"=>"41310584",
					  "42907"=>"41310586",
					  "42908"=>"41310587",
					  "42909"=>"41310588",
					  "42910"=>"41310589",
					  "43024"=>"41310590",
					  "43025"=>"41310591",
					  "43026"=>"41310592",
					  "43027"=>"41310593",
					  "43462"=>"41310823",
					  "43463"=>"41310824",
            "43674"=>"41310886",
            "43675"=>"41310887",
            "43671"=>"41310892",
            "43672"=>"41310893",
            "43673"=>"41310894"

	);

	$dmsellcode = $ii[$sellcode];

	if($dmsellcode){
						$lcnt = 0;
						$lqry = "SELECT * FROM apidb.dm_pincode where orderno='$orderno'";
						$lres = $conn_cms2->query($lqry);
						$lcnt = $lres->num_rows;

						if($lcnt > 0 ){
              $dmrow = $lres->fetch_object();
              $cpno = $dmrow->couponno;
						}else{

							$dmsql = "update apidb.dm_pincode set orderno='$orderno',hp='$userhp', usernm='$usernm', syncresult= 'R', regdate = now() where syncresult= 'N' and sellcode = '$dmsellcode' limit 1";
							$conn_cms2->query($dmsql);

					        $iqry = "SELECT couponno FROM apidb.dm_pincode where orderno = '$orderno' limit 1";
							$dmrow = $conn_cms2->query($iqry)->fetch_object();
							$cpno = $dmrow->couponno;

						}
	}

	return json_encode(array($cpno));
}

function get_alpensiano($itemcode,$_req){
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://gateway.sparo.cc/extra/kiosk/alpensia/order/'.$itemcode,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS =>json_encode($_req, JSON_UNESCAPED_UNICODE),
      CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json'
      ),
    ));

    $result = json_decode(curl_exec($curl),true);

    return $result['0'];
}

function getpcmscoupon($order){
    global $conn_cms3;

    $facode = $order->FACCODE;
    $chcode = $order->CHCODE;
    $orderno = $order->ORDERNO;
    $sellcode = $order->SELLCODE;
    $unit = $order->UNIT;
    $usernm = $order->USERNM;
    $userhp = $order->USERHP;

    if(!$unit) $unit = 1;

    if(strlen($orderno) < 5){
        exit;
    }

    $cpsql = "UPDATE
                spadb.pcms_extcoupon
              SET
                order_no = '$orderno',
                cus_nm='$usernm',
                cus_hp='$userhp',
                syncfac_result='R',
                date_order = now()
              WHERE
                    syncfac_result = 'N'
                AND order_no is null
                AND state_use  = 'N'
                AND sellcode = '$sellcode'
              LIMIT $unit";

    $res = $conn_cms3->query($cpsql);

    $ordsql = "select * from spadb.pcms_extcoupon where order_no = '$orderno' and sellcode='$sellcode' limit $unit";
    $result = $conn_cms3->query($ordsql);

    //if($res->num_rows > 0)
    $cpns = array();

    while($row = $result->fetch_object()){
        $cpns[] = $row->couponno;
    }

    return json_encode($cpns);

}

?>
