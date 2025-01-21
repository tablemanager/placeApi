<?php
require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
header("Content-type:application/json");

$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

// 파라미터
$itemreq = explode("/",$para);
$jsonreq = trim(file_get_contents('php://input'));

$apis = $itemreq[0];
$jpmt_id = $itemreq[1];

// ACL 확인
$accessip = array(
                  "106.254.252.100",
                  "18.163.36.64"
                  );

if(!in_array(get_ip(),$accessip)){
    header("HTTP/1.0 401 Unauthorized");
    $res = array("Result"=>"4100","Msg"=>"아이피 인증 오류 : ".get_ip());
    echo json_encode($res);
    exit;
}


switch($apimethod){
    case 'POST': 
        break; 
	case 'PATCH': 
        break; 
	default:
        switch($apis){
            case 'deallists': 
                echo $_res = get_deallists();
            break; 
            // 20240122 tony https://placem.atlassian.net/browse/PM2201COBBF1-18 [Q-PASS][국내_한국민속촌] 채널 판매 확인요청건
            // jpmt_id(시설코드)로 상품코드 리스트 조회
            case 'deallistsjpmtid': 
                echo $_res = get_deallists_jpmt_id($jpmt_id);
            break; 
        }
        break;
}


function get_deallists(){
    global $conn_cms;
    $cpcode = "CTP";
    $itemsql = "SELECT * from pcmsdb.items_ext where channel = '$cpcode' and usedate >= '".date("Y-m-d")."' and useyn ='Y'";

    $itemres = $conn_cms->query($itemsql);
    $lts = array();

    while($row = $itemres->fetch_object()){
      $lts[] = $row->pcmsitem_id;
    }
    
    return json_encode($lts); 
}

// 20240122 tony https://placem.atlassian.net/browse/PM2201COBBF1-18 [Q-PASS][국내_한국민속촌] 채널 판매 확인요청건
// jpmt_id(시설코드)로 상품코드 리스트 조회
// 상품번호 일일이 넣지 않고 이걸로 조회해서 프로그래밍 하길 바란다.
function get_deallists_jpmt_id($jpmt_id){
    global $conn_cms;
    $cpcode = "CTP";
    
    $itemsql = "SELECT a.*, b.* FROM CMSDB.CMS_ITEMS a, (
                    SELECT * FROM pcmsdb.items_ext WHERE channel = '$cpcode' AND usedate >= '".date("Y-m-d")."' AND useyn ='Y'
                ) AS b
                WHERE true
                AND b.pcmsitem_id = a.item_id 
                AND a.item_edate >= '".date("Y-m-d H:i:s")."'
                AND a.item_state = 'Y'
                AND a.item_facid = '$jpmt_id';
                ";

    $itemres = $conn_cms->query($itemsql);
    $lts = array();

    while($row = $itemres->fetch_object()){
      $lts[] = $row->pcmsitem_id;
    }
    
    return json_encode($lts); 
}

function get_ip(){

	if(empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
		 $ip= $_SERVER["REMOTE_ADDR"].",";
	}else{
		$ip= $_SERVER["HTTP_X_FORWARDED_FOR"];
	}

	$res = explode(",",$ip);

    return trim($res[0]);
}
?>
