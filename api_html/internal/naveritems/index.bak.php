<?php
exit;
require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');

// https://gateway.sparo.cc/internal/cpconfig/AQ/TP23810_31

$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

// 파라미터
$itemreq = explode("/",$para);
$jsonreq = trim(file_get_contents('php://input'));

?>

<script
  src="https://code.jquery.com/jquery-2.2.4.min.js"
  integrity="sha256-BbhdlvQf/xTY9gja0Dq3HiwQF8LaCRTXxZKRutelT44="
  crossorigin="anonymous"></script>

<?php
// ACL 확인
$ip = explode(",",$_SERVER["HTTP_X_FORWARDED_FOR"]);
$accessip = array("115.68.42.2",
                  "115.68.42.8",
                  "115.68.42.130",
                  "52.78.174.3",
                  "106.254.252.100",
                  "115.68.182.165",
                  "13.124.139.14",
                  "13.209.232.254",
                  "118.131.208.123"
                  );

if(!in_array(trim($ip[0]),$accessip)){
    header("HTTP/1.0 401 Unauthorized");
    $res = array("Result"=>"4100","Msg"=>"아이피 인증 오류 : ".get_ip());
    echo json_encode($res);
    exit;
}

$tcode = $itemreq[0];
$ccode = $itemreq[1];

if(strlen($tcode) < 1 ){
    exit;
}
if(strlen($ccode) < 4 ) exit;

$tsql = "select * from cmsdb.nbooking_items where agencyBizItemId = '$ccode'";
$trow = $conn_rds->query($tsql)->fetch_object();
$_json = json_decode($trow->itemOptions);

foreach($_json as $ni){
	print_r($ni);
	/*
 priceId => 996112
 optNm => 스토리크루즈 대인권
 facType =>
 couponCode =>
 cmsItemCode =>
 price => 13600
 couponUnit => 1
 */
}

?>
