<?php
/*
 *
 * 플레이스엠 CMS 주문입력
 *
 *
 *
 */
require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');

header("Content-type:application/json");

$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

// 파라미터
$itemreq = explode("/",$para);
$jsonreq = trim(file_get_contents('php://input'));

// ACL 확인
$accessip = array("115.68.42.2",
                  "115.68.42.8",
                  "115.68.42.130",
                  "52.78.174.3",
                  "106.254.252.100");

if(!in_array(get_ip(),$accessip)){
    header("HTTP/1.0 401 Unauthorized");
    $res = array("Result"=>"4100","Msg"=>"아이피 인증 오류 : ".get_ip());
    echo json_encode($res);
    exit;
}

switch($apimethod){
  case 'GET':
    header("HTTP/1.0 401 Unauthorized");
    $res = array("Result"=>"4100","Msg"=>"아이피 인증 오류 : ".get_ip());
    echo json_encode($res);
  break;
  case 'POST':

    $_result = array("orderno"=>setorders(json_decode($jsonreq)));
    echo json_encode($_result);
  break;
  default:


}

function setorders($req){

    global $conn_cms;
    global $conn_cms3;

    $req->chid; // 판매 채널 코드
    $req->chorderno; // 판매처 주문코드
    $req->usernm; // 구매자명
    $req->userhp; // 구매자 핸드폰
    $req->itemcode; // 상품코드
    $req->qty; // 수량
    $req->usedate; // 이용일(없으면 상품기본값)
    $req->couponno; // 쿠폰번호(선택)


    $iteminfo = get_iteminfo($req->chid, $req->itemcode,$req->usedate);
  //  print_r($iteminfo);
    $orderno = date("Ymd")."_".genRandomStr(10);
    $mdate = date("Y-m-d");

    $lasthp = substr($req->userhp,-4);
  	if($lasthp == "0000") $req->userhp = "02-1544-3913";
    if(!$req->couponno) $req->couponno = "0";
    if(!$req->qty)$req->qty = 1;


    if($req->chid == "3700"){
      $syncflag = "2";
    }else{
      $syncflag = "0";
    }

    $mode = "INSERT spadb.ordermts SET created = now(),";

  	$where = "
  		gu = 'J',
  		updated = now(),
  		mechstate 	=	'정산대기',
  		meipstate 	=	'정산대기',
  		site	=	'CMS',
  		orderno = '$orderno',
  		mdate	= '$mdate',
  		usedate	= '".$iteminfo['expdate']."',
  		jpnm	=  '".$iteminfo['jpnm']."',
  		itemnm	=  '".$iteminfo['itemnm']."',
  		man1	=	'$req->qty',
  		man2	=	'0',
  		chnm	=	'".$iteminfo['chnm']."',
  		grnm 	=	'".$iteminfo['grnm']."',
  		jpmt_id 	=	'".$iteminfo['jpmt_id']."',
  		itemmt_id 	=	'".$iteminfo['itemid']."',
  		ch_id 	=	'".$req->chid."',
  		pricemt_id  = '".$iteminfo['price_id']."',
  		grmt_id  = '".$iteminfo['gtmt_id']."',
      ch_orderno = '".$req->chorderno."',
  		usernm 	=	'".$req->usernm."',
  		hp	=	hex(aes_encrypt( '".$req->userhp."', 'Wow1daY' )),
  		usernm2	=	'".$req->usernm."',
  		hp2	=	hex(aes_encrypt( '".$req->userhp."', 'Wow1daY' )),
  		dangu = '공통권',
  		amt	=	'".$iteminfo['price_sale']."',
  		accamt	=	'".$iteminfo['price_sale']."',
  		damnm 	=	'시스템',
      sync_fac = '$syncflag',
  		usegu = '2',
  		barcode_no = '".$req->couponno."',
  		state	=	'예약완료'";
      // sync_fac 꼭수정할것!

      //echo $mode.$where;
      $conn_cms3->query($mode.$where);

      return $orderno;

}


// 연동 정보 조회
function get_iteminfo($chid, $itemid,$expdate){
    global $conn_cms;

    // 상품 정보
    $itemsql = "SELECT * from CMSDB.CMS_ITEMS where item_id = '$itemid' limit 1";
    $itemres = $conn_cms->query($itemsql);
    $itemurow = $itemres->fetch_object();

    // 업체 정보
    $cpsql = "SELECT *  from CMSDB.CMS_COMPANY where com_id = '".$itemurow->item_cpid."' limit 1";
    $cpcres = $conn_cms->query($cpsql);
    $cpcrow = $cpcres->fetch_object();

    // 채널정보
    $chsql = "SELECT *  from CMSDB.CMS_COMPANY where com_id = '".$chid."' limit 1";
    $chcres = $conn_cms->query($chsql);
    $chcrow = $chcres->fetch_object();

    // 시설 정보
    $facsql = "SELECT *  from CMSDB.CMS_FACILITIES where fac_id = '".$itemurow->item_facid."' limit 1";
    $facres = $conn_cms->query($facsql);
    $facrow = $facres->fetch_object();

    if(strlen($expdate) < 3){
      $expdate = substr($itemurow->item_edate,0,10);
    }

    // 가격 정보
    $pricesql = "SELECT * from CMSDB.CMS_PRICES where price_itemid = '$itemid' and price_date = '$expdate' limit 1";
    $priceres = $conn_cms->query($pricesql);
    $pricerow = $priceres->fetch_object();

    $result = array(
                    "itemid"=> $itemurow->item_id,
                    "expdate"=> $expdate,
                    "itemnm"=> $itemurow->item_nm,
                    "chnm"=> $chcrow->com_nm,
                    "gtmt_id"=> $cpcrow->com_id,
                    "grnm"=> $cpcrow->com_nm,
                    "jpmt_id"=> $itemurow->item_facid,
                    "jpnm"=> $facrow->fac_nm,
                    "price_id"=> empty($pricerow->price_id)?0:$pricerow->price_id,
                    "price_sale"=> empty($pricerow->price_sale)?0:$pricerow->price_sale,
                    "price_in"=> empty($pricerow->price_in)?0:$pricerow->price_in,
                    "price_out"=> empty($pricerow->price_out)?0:$pricerow->price_out
                    );

    return $result;
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

// 랜덤 스트링
function genRandomStr($length = 10) {
	$characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}
?>
