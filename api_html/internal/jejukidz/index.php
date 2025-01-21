<?php
/*
 *
 * 제주 키자니아 쿠폰 발급
 *
 * 작성자 : 이정진
 * 작성일 : 2018-07-02
 *
 * 사용(POST) : https://gateway.sparo.cc/internal/jejukidz
 *
 * JSON {"CHCODE":"채널코드", "ORDERNO":"20180802", "SELLCODE":"P10101_1", "UNIT":"1", "USERNM":"테스트","USERHP":"01090901678"} ;
 */

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
header("Content-type:application/json");

mysqli_set_charset($conn_cms, 'utf8');
mysqli_set_charset($conn_rds, 'utf8');
mysqli_set_charset($conn_cms3, 'utf8');

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
				  "211.219.73.56");

if(!in_array(get_ip(),$accessip)){
    header("HTTP/1.0 401 Unauthorized");
    $res = array("Result"=>"4100","Msg"=>"아이피 인증 오류 : ".get_ip());
    echo json_encode($res);
    exit;
}


$order=json_decode($jsonreq);

$orderno = $order->ORDERNO;
$sellcode = $order->SELLCODE;
$unit = $order->UNIT;
//$unit = 1; // 호출당 하나
$usernm = $order->RCVER_NM;
$userhp = str_replace("-","",$order->RCVER_TEL);

if(!$unit) $unit = 1;

// 파라미터 체크할것
if(strlen($orderno) < 5 or strlen($usernm) < 2 or strlen($userhp) < 10 ){
  echo "E";
    exit;
}

// 상품 설정 DB화 필요


switch($sellcode){
        case '36425':
            $itemnm = "1부_어린이권(제주 키자니아)";
            $pkg_type = "1";
            $item_type = "1";
            $person_flag="C";
            $item_price = "24600";
        break;
        case '36426':
            $itemnm = "1부_성인권(제주 키자니아)";
            $item_type= "1";
            $pkg_type = "1";
            $person_flag="A";
            $item_price = "6800";
        break;
        case '36427':
            $itemnm = "2부_어린이권(제주 키자니아)";
            $item_type= "2";
            $pkg_type = "1";
            $person_flag="C";
            $item_price = "24600";
        break;
        case '36428':
            $itemnm = "2부_성인권(제주 키자니아)";
            $item_type= "2";
            $pkg_type = "1";
            $person_flag="A";
            $item_price = "6800";
        break;
        case '36429':
            $itemnm = "종일_어린이권(제주 키자니아)";
            $item_type= "3";
            $pkg_type = "1";
            $person_flag="C";
            $item_price = "43500";
        break;
        case '36430':
            $itemnm = "종일_성인권(제주 키자니아)";
            $pkg_type = "1";
            $item_type= "3";
            $person_flag="A";
            $item_price = "12000";
        break;
        case '36581':
            $itemnm = "5회_2부_어린이권(제주 키자니아)";
            $pkg_type = "5";
            $item_type= "2";
            $person_flag="C";
            $item_price = "21800";
        break;
        case '36582':
            $itemnm = "5회_2부_성인권(제주 키자니아)";
            $item_type= "2";
            $pkg_type = "5";
            $person_flag="A";
            $item_price = "30000";
        break;
        case '37575':
            $itemnm = "얼리버드5회_1부_성인권(제주 키자니아)";
            $item_type= "1";
            $pkg_type = "5";
            $person_flag="A";
            $item_price = "20200";
        break;
        case '37576':
            $itemnm = " 5회_1부_성인권(제주 키자니아)";
            $item_type= "1";
            $pkg_type = "5";
            $person_flag="A";
            $item_price = "30000";
        break;
        case '37577':
            $itemnm = " 5회_1부_어린이권(제주 키자니아)";
            $item_type= "1";
            $pkg_type = "5";
            $person_flag="C";
            $item_price = "21800";
        break;
        case '37574':
            $itemnm = "얼리버드5회_1부_어린이권(제주 키자니아)";
            $item_type= "1";
            $pkg_type = "5";
            $person_flag="C";
            $item_price = "28000";
        break;
        case '36583':
            $itemnm = "10회_2부_성인권(제주 키자니아)";
            $item_type= "2";
            $pkg_type = "10";
            $person_flag="A";
            $item_price = "5600";
        break;
        case '37028':
            $itemnm = "1부_성인패키지(제주 키자니아)";
            $item_type= "1";
            $pkg_type = "1";
            $person_flag="A";
            $item_price = "6800";
        break;
        case '37029':
            $itemnm = "1부_어린이패키지(제주 키자니아)";
            $item_type= "1";
            $pkg_type = "1";
            $person_flag="C";
            $item_price = "24600";
        break;
        case '37030':
            $itemnm = "2부_성인패키지(제주 키자니아)";
            $item_type= "2";
            $pkg_type = "1";
            $person_flag="A";
            $item_price = "6800";
        break;
        case '37031':
            $itemnm = "2부_어린이패키지(제주 키자니아)";
            $item_type= "2";
            $pkg_type = "1";
            $person_flag="C";
            $item_price = "24600";
        break;
        case '37032':
            $itemnm = "종일_성인패키지(제주 키자니아)";
            $item_type= "3";
            $pkg_type = "1";
            $person_flag="A";
            $item_price = "12000";
        break;
        case '37033':
            $itemnm = "종일_어린이패키지(제주 키자니아)";
            $item_type= "3";
            $pkg_type = "1";
            $person_flag="C";
            $item_price = "43500";
        break;
        case '37578':
            $itemnm = "10회_1부_성인권(제주 키자니아)";
            $item_type= "1";
            $pkg_type = "10";
            $person_flag="A";
            $item_price = "5600";
        break;
        case '37579':
            $itemnm = "10회_1부_어린이권(제주 키자니아)";
            $item_type= "1";
            $pkg_type = "10";
            $person_flag="C";
            $item_price = "20300";
        break;
        case '36584':
            $itemnm = "10회_2부_어린이권(제주 키자니아)";
            $item_type= "2";
            $pkg_type = "10";
            $person_flag="C";
            $item_price = "20300";
        break;
        case '36585':
            $itemnm = "얼리버드5회_2부_성인권(제주 키자니아)";
//            $item_type= "3";
            $item_type= "2";
            $pkg_type = "5";
            $person_flag="A";
            $item_price = "28000";
        break;
        case '36586':
            $itemnm = " 얼리버드5회_2부_어린이권(제주 키자니아)";
//            $item_type= "3";
            $item_type= "2";
            $pkg_type = "5";
            $person_flag="C";
            $item_price = "20200";
        break;
        case '36640':
            $itemnm = "1부_도민어린이권(제주 키자니아)";
            $item_type= "1";
            $pkg_type = "1";
            $person_flag="C";
            $item_price = "23200";
        break;
        case '36641':
            $itemnm = "1부_도민성인권(제주 키자니아)";
            $item_type= "1";
            $pkg_type = "1";
            $person_flag="A";
            $item_price = "6400";
        break;
        case '36642':
            $itemnm = "2부_도민어린이권(제주 키자니아)";
            $item_type= "2";
            $pkg_type = "1";
            $person_flag="C";
            $item_price = "23200";
        break;
        case '36643':
            $itemnm = "2부_도민성인권(제주 키자니아)";
            $item_type= "2";
            $pkg_type = "1";
            $person_flag="A";
            $item_price = "6400";
        break;
    default:
        exit;

}

// 발권수량
$unit = $unit * $pkg_type;

$itemsql = "SELECT item_cd,item_nm  FROM CMSDB.CMS_ITEMS WHERE `item_id`='$sellcode'";
$itemrow = $conn_cms->query($itemsql)->fetch_object();

if(!empty($itemrow)){
      $itemnm = $itemrow->item_nm;
      $itemcd = $itemrow->item_cd;
}else{

}

$ordsql = "select * from cmsdb.kidzania_jeju_rese where jeju_order = '$orderno' and item_code='$sellcode' limit $unit";
$result = $conn_rds->query($ordsql);

if($result->num_rows < $unit){

    for($i=0;$i<$unit-$result->num_rows;$i++){

       $it = 1;
       $cp = $sellcode.genRandomNum(9);
       $cpsql = "
              INSERT
                cmsdb.kidzania_jeju_rese
              SET
                item_type = '$item_type',
                item_kioskcode ='$itemcd',
                item_name = '$itemnm',
                person_flag='$person_flag',
                coupon = '$cp',
                item_code = '$sellcode',
                item_price = '$item_price',
                jeju_order = '$orderno',
                user_nm='$usernm',
                user_tel='$userhp',
                sync_flag = 'O',
                rese_date = now()";
         $conn_rds->query($cpsql);

    }

}

$ordsql = "select * from cmsdb.kidzania_jeju_rese where jeju_order = '$orderno' and item_code='$sellcode' order by seqno asc limit $unit";
$result = $conn_rds->query($ordsql);

$cpns = array();

while($row = $result->fetch_object()){
    if($unit > 1){
      $cpns[] = $row->coupon;
    }else{
      $cpns = $row->coupon;
    }

}

echo json_encode($cpns);


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

?>
