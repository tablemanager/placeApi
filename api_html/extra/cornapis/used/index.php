<?php
// 리라이트가 귀찮아서 디렉토리 처리
date_default_timezone_set('Asia/Seoul');
require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
$conn_rds->query("set names utf8");
$conn_cms3->query("set names utf8");

header("Content-type:application/json");

$_type = $_GET['type'];
$_goods_no = $_GET['goods_no'];
$_value = $_GET['values'];

// 인터페이스 로그
list($microtime,$timestamp) = explode(' ',microtime());
$tranid = $timestamp . substr($microtime, 2, 3);

$para = json_encode($_GET); // URI 파라미터
$para = addslashes($para);
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더
$jsonreq = $para;

$logsql = "insert cmsdb.extapi_log set apinm='사용처리', chnm='콘소프트', tran_id='$tranid', ip='".get_ip()."', logdate= now(), apimethod='$apimethod', querystr='".$para."', header='".json_encode($apiheader)."', body='".$jsonreq."'";
$conn_rds->query($logsql);

$_sql = "select * from corn_extcoupon where couponno = '$_value' limit 1";

$_row = $conn_rds->query($_sql)->fetch_object();

switch($_row->state){
  case 'Y':
    $rcode = "0004";
    $rmsg = "coupon that was used";


  break;
  case 'N':
    $rcode = "0000";
    $rmsg = "success";
    //미사용처리

    $_usql = "update cmsdb.corn_extcoupon set state='Y', date_use = now() where couponno = '$_value' limit 1";
    $conn_rds->query($_usql);

    __usecouponno($_value);

  break;
  case 'C':
    $rcode = "0002";
    $rmsg = "cancel coupon";
  break;
  default:
    $rcode = "0006";
    $rmsg = "no coupon_no";
}

header("HTTP/1.0 200");
echo $result = json_encode(array(
  $rcode => $rmsg,
  "list" => null), JSON_UNESCAPED_UNICODE);

// 결과 로그 기록
$logsql2 = "update cmsdb.extapi_log set apiresult = '".addslashes($result)."' where tran_id='$tranid' limit 1";
$conn_rds->query($logsql2);

function __usecouponno($no){
    // 쿠폰 사용처리
    $curl = curl_init();
    $url = "http://172.31.30.15:3040/use/".$no;
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_USERAGENT,"Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1" );
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 5);
    $data = explode(";",curl_exec($curl));
    $info = curl_getinfo($curl);
    curl_close($curl);
    if($data[1] == "0"){
        return "N";
    }else{
        return "Y";
    }
}

// 클라이언트 아아피
function get_ip(){

    $res = explode(",",$_SERVER["HTTP_X_FORWARDED_FOR"]);
    return $res[0];
}

?>
