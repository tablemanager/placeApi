<?php

/*
 *
 * CMS 상품 정보
 *
 * 작성자 : 이정진
 *
 * https://gateway.sparo.cc/extra/agency/provision
 *
 */
error_reporting(0);

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
require_once ('/home/placedev/php_script/lib/placemlib.php');
header("Content-type:application/json");

$conn_rds->query("set names utf8");

$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

// 파라미터
$itemreq = explode("/",$para);
$jsonreq = trim(file_get_contents('php://input'));


// 인증 정보 조회
$auth = $apiheader['Authorization'];

if(!$auth) $auth = $apiheader['authorization'];

$authqry = "SELECT * FROM spadb.extapi_config WHERE authkey = '$auth' limit 1";
$authres = $conn_cms3->query($authqry);
$authrow = $authres->fetch_object();
//echo $authqry;
$aclmode = $authrow->aclmode;

if($aclmode == "IP"){
// ACL 확인
    if(!in_array(get_ip(),json_decode($authrow->accessip,false))){
        //header("HTTP/1.0 401 Unauthorized");
        //$res = array("Result"=>"4100","Msg"=>"아이피 인증 오류 : ".get_ip());
        //echo json_encode($res);
        //exit;
    }
}

// API키 확인
if(!$authrow->cp_code){

//    header("HTTP/1.0 401 Unauthorized");
  //  $res = array("Result"=>"4100","Msg"=>"인증 오류");
    //echo json_encode($res);
//    exit;

}else{

    $cpcode = $authrow->cp_code; // 채널코드
    $cpname = $authrow->cp_name; // 채널명
    $grmt_id = $authrow->cp_grmtid; // 채널 업체코드
    $ch_id = $grmt_id;

}

if(get_ip() =="106.254.252.100"){
//echo $itemreq[0];
}

// REST Method 분기
switch($apimethod){
    case 'GET':

		echo $_res = get_deallist($cpcode);

    break;
    case 'POST':
    $prod_id = $itemreq[0];
		echo $_res = set_confirm($prod_id,$jsonreq);

    break;
    default:
        header("HTTP/1.0 400 Bad Request");
        $res = array("Result"=>"4000","Msg"=>"파라미터 오류");
        echo json_encode($res);
}

function set_confirm($id,$req){
  global $conn_rds;

  $deal_no = (string) json_decode($req)->deal_no;
  $usql = "update cmsdb.channel_deals set sync_flag='Y' , deal_no = '$deal_no' where sync_flag='N' and id = '$id' limit 1";
  $urow = $conn_rds->query($usql);

  $res = array("Result"=>"0000","Msg"=>"성공");
  return json_encode($res);

}

// 딜리스트
function get_deallist($cpcode){
    global $conn_cms;
    global $conn_rds;
    $mdate = date("Y-m-d");

    $glist = array();
    $gsql = "select * from cmsdb.channel_deals where ch_code = '".$cpcode."' and sync_flag='N'";
	//echo $gsql;
    //$gsql = "select * from cmsdb.channel_deals where 1 limit 2";
    $gres = $conn_rds->query($gsql);

	while($grow = $gres->fetch_object()){

		$items = array();

    $georesult = json_decode(get_geocode($grow->gdAddr1." ".$grow->gdAddr2));

    if (!empty($georesult)) {
        $lng = $georesult->addresses[0]->x;
        $lat = $georesult->addresses[0]->y;
    }
    // 주소2
    $Product['gdLat'] = $lat;                                                  // 위도
    $Product['gdLon'] = $lng;
	$items = json_decode($grow->deal_info);
	$lastupdate = date('YmdHis', strtotime($grow->Update_date));

    // 시설정보
    $facinfo = get_facinfo($grow->fac_id);

    $glistp[] = array(
			"timeHash" => $lastupdate, // 해시
      "facCode" => $grow->fac_id,
      "facName" => $facinfo->fac_nm,
			"prodSeq" => $grow->id, // 딜번호
			"prodNm" => $grow->deal_title, // 프로덕트 이름
			"prodDesc" => $grow->prodDesc,
			"prodSimpleDesc" => $grow->prodSimpleDesc,
			"refundRuleDesc" => $grow->refundRuleTxt,
			"saleStartTime"=> $grow->use_sdate,
			"saleEndTime"=> $grow->sale_edate,
			"useStartTime"=> $grow->use_sdate,
			"useEndTime"=> $grow->use_edate,
			"prodDetailUrl"=> $items->maincontents,
			"prodDetail"=> $items->maincontents, //get_detailcontens($items->maincontents),
			"prodImgUrl"=> $items->mainimg,
//			"cancelFeeYn"=> $grow->cancelFeeYn,
			"cancelFeeYn"=> "N",
			"cancelRate"=> array(),
			"refundPossibleYn"=> $grow->refundPossibleYn,
			"gdAddr1"=> $grow->gdAddr1,
			"gdAddr2"=> $grow->gdAddr2,
			"geoCode"=>$Product,
			"items"=> get_items($items),
			"deal_cate" => $grow->deal_cate

        );
		
    }

//    echo json_encode($glistp);

	echo json_encode( $glistp, JSON_UNESCAPED_UNICODE );
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


function get_detailcontens($url)
{
    $is_post = false;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, $is_post);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $headers = array();
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    return $response;

	curl_close($ch);

}

function get_facinfo($faccode)
{
    global $conn_cms;
    $syncsql = "select f.fac_id, f.fac_nm from CMSDB.CMS_FACILITIES f  where f.fac_id = '$faccode'";
    $syncinfo = $conn_cms->query($syncsql)->fetch_object();

    return $syncinfo;
}

function get_items($items)
{
    global $conn_cms;
    global $conn_rds;

    $optcodes = explode("_",$items->opt_cmcode);

    $optarr = array();

    $i = 0;
    foreach ($optcodes as $cmsitescd){
        $i++;
        $cmsitescd = trim($cmsitescd);

		    $syncsql = "select * from CMSDB.CMS_ITEMS where item_id = '$cmsitescd'";
        $syncinfo = $conn_cms->query($syncsql)->fetch_object();

        $priceinfo = $conn_cms->query("select * from CMSDB.CMS_PRICES where  price_itemid = '$cmsitescd' and price_state= 'Y' order by price_sale asc limit 1")->fetch_object();

        if ($i == 1) {
            $nt_prod = "Y";
        } else {
            $nt_prod = "N";
        }
		$schedules = array();

		$itemnm = explode("@", $syncinfo->item_nm);

		$schedule["sdseq"] = $priceinfo->price_id;
		$schedule["sdName"] = $itemnm[0];
		$schedule["sdStDate"]  = substr($syncinfo->item_sdate,0,10);
		$schedule["sdStHh"] = "00";
		$schedule["sdStMm"] = "00";
		$schedule["sdEdDate"] = substr($syncinfo->item_sdate,0,10);
		$schedule["sdEdHh"] = "23";
		$schedule["sdEdMm"] = "59";
		$schedule["sdSaleMax"] = 1;
		$schedule["sdRemainder"] = 0;
		$schedule["sdSaleCnt"] = null;
		$schedule["sdStatus"] = null;
		$schedule["useYn"] = $syncinfo->item_state;

		$schedules[] = $schedule;

        $Ticket = array();
        $Ticket['prodPlSeq'] = $cmsitescd;
        $Ticket['plName'] = $itemnm[0];
        $Ticket['plPrice'] = $priceinfo->price_sale;
        $Ticket['plBasicPrice'] = $priceinfo->price_normal;
        $Ticket['plPriority'] = $i;
        $Ticket['useYn'] = "Y";
        $Ticket['minQuantity'] = "1";
        $Ticket['maxQuantity'] = "10";
        $Ticket['plRemainUseYn'] = "N";
        $Ticket['plRemainCnt'] = "1000";
        $Ticket['plNotiYn'] = "$nt_prod";
        $Ticket['schedules'] = $schedules;
        $optarr[] = $Ticket;

    }
	return $optarr;

}

function get_geocode($addr)
{
    $client_id = "751vfo0uze";
    $client_secret = "aofRZPq29QcfnzHGbNWJRWTlRVMdSIpNdlXlys31";

    $encText = urlencode($addr);

	$url = "https://naveropenapi.apigw.ntruss.com/map-geocode/v2/geocode?query=".$encText;
    $is_post = false;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, $is_post);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $headers = array();
    $headers[] = "X-NCP-APIGW-API-KEY-ID: " . $client_id;
    $headers[] = "X-NCP-APIGW-API-KEY: " . $client_secret;
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    return $response;

	curl_close($ch);

}
?>
