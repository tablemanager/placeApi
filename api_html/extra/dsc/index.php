<?php
/*
 *
 * 신세계 대전과학관
 *
 * 작성자 : 이정진
 * 작성일 : 2021-06-02
 *
 *
 *
 */

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
$conn_rds->query("set names utf8");
$conn_cms3->query("set names utf8");

$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

// 파라미터
$itemreq = explode("/",$para);
$jsonreq = trim(file_get_contents('php://input'));
$placecdc = $itemreq[0];
$proc = $itemreq[1];


// ACL 확인
$accessip = array("106.254.252.100");

if(!in_array(get_ip(),$accessip)){
//    header("HTTP/1.0 401 Unauthorized");
//    $res = array("resultCode"=>"9996","resultMessage"=>"아이피 인증 오류 : ".get_ip());
//    echo json_encode($res);
//    exit;
}
if(in_array(get_ip(),$accessip)){
	// echo "what";
	// echo $itemreq[0];
	// echo $itemreq[1];
	// echo $itemreq[2];
	// echo $itemreq[3];
}

if(strlen($couponno)  != 16){
//  header("HTTP/1.0 400");
//    $res = array("resultCode"=>"9997","resultMessage"=>"파라미터 오류");
//    echo json_encode($res);
//    exit;
}

header("Content-type:application/json");


$mdate = date("Y-m-d");
if(in_array(get_ip(),$accessip)){
	// var_dump($proc);
}
switch($apimethod){
	case 'GET':
		switch($proc){
			case 'couponno': //쿠폰 조회
				$_no = $itemreq[2];
				$_nm = $itemreq[3];


				$_res = getOrder("CP",$_no,$_nm,$placecdc);

			break;
			case 'mobile': //핸드폰 조회
				$_hp = $itemreq[2];
				$_nm = $itemreq[3];

				$_res = getOrder("HP",$_hp,$_nm,$placecdc);
			break;
			case 'report': // 마감 리포트
				$_mdate = $itemreq[2];
				$_res = getReports($_mdate,$placecdc);
			break;
			default:
			break;
		}

	break;
	case 'POST':
		switch($proc){
			case 'couponno': // 사용처리
				$_no = $itemreq[2];
				$_res = setUseCoupon($_no,$_usedate,$placecdc);

			break;
			default:
			break;
		}
	break;

	case 'PATCH':
		// 사용처리 취소
		switch($proc){
			case 'couponno': // 사용처리 취소
				$_no = $itemreq[2];
				$_res = setUnuseCoupon($_no,$placecdc);
			break;
			default:
		}
	break;
	default:
	exit;

}

echo json_encode($_res);


function getOrder($_stype,$_val1,$_val2,$placecdc){
	global $conn_rds;


	if($_stype == "CP"){

		  $w = "RESERVE_NO = '$_val1' ";
			$ss = "'Y','N','C'";

	}
	if($_stype == "HP"){
		  $w = "RESERVE_TEL_NO = '$_val1' ";
			$ss = "'N'";

	}

	switch($placecdc){
		case 'sc':
			$pcd ="'SC'";
		break;
		case 'ob':
			$pcd ="'OB'";
		break;
		case 'ds':
			$pcd ="'DS'";
		break;
		default:
		$pcd ="null";
	}

	if(strlen($_val2) > 2){
		$n = " and RESERVE_NAME= '$_val2'";
	}else{
		$n = "";
	}

	$qry = "SELECT
				placecode as PLACECODE,
				RESERVE_NO,
				TICKET_FG_TYPE_CD,
				TICKET_FG_CD,
				TICKET_STATUS,
				DATE_FORMAT(USE_DATE,'%Y%m%d%H%i%s') as USE_DATE,
				DATE_FORMAT(CANCEL_DATE,'%Y%m%d%H%i%s') as CANCEL_DATE,
				VALID_DATE,
				BUY_CHANNEL_NAME,
				RESERVE_NAME,
				RESERVE_TEL_NO,
				SALE_PRICE
			FROM
				cmsdb.dsc_extcoupon
			WHERE
				TICKET_STATUS in ($ss) and
				placecode in ($pcd) and ".$w.$n;

	// if(get_ip() == "106.254.252.100"){
	// 	echo $qry;
	// }
    $res = $conn_rds->query($qry);
    $ocnt = $res->num_rows;

		if($ocnt == 0){
			header("HTTP/1.0 404");
			$_result = array(
					"RESERVE_STATUS" => "404",
					"RESERVE_STATUS_NM" => "요청하신 쿠폰이 존재 하지않습니다.",
					"RESERVE_NO" => $_val1
			);
			echo json_encode($_result);
			exit;
		}

    while($row = $res->fetch_object()){
        $ord[] = $row;
    }

	$_result = array(
			  "RESERVE_STATUS" => "200",
			  "RESERVE_STATUS_NM" => "조회성공",
			  "RESULTS" => $ord

		);
	return $_result;
}

function setUseCoupon($_no,$_usedate,$placecdc){
	global $conn_rds;

	if(empty($_usedate)){
		$_udt = date("YmdHis");
	}else{
		$_udt = $_usedate;
	}

	switch($placecdc){
		case 'sc':
			$pcd ="'SC'";
		break;
		case 'ob':
			$pcd ="'OB'";
		break;
		case 'ds':
			$pcd ="'DS'";
		break;
		default:
		$pcd ="null";
	}
	$qry = "select * from cmsdb.dsc_extcoupon where placecode = $pcd and RESERVE_NO= '$_no' limit 1 ";

	$_row = $conn_rds->query($qry)->fetch_object();

	// 유효기간 지남
	if(date("Ymd") > $_row->VALID_DATE){
			header("HTTP/1.0 403");
			$_result = array(
					"RESERVE_STATUS" => "403",
					"RESERVE_STATUS_NM" => "쿠폰상태 변경불가(사용기간 지남)",
					"RESERVE_NO" => $_no
			);
			echo json_encode($_result);
			exit;
	}

	if($_row->TICKET_STATUS == "N"){
		$_result = array(
			  "RESERVE_STATUS" => "200",
			  "RESERVE_STATUS_NM" => "사용처리 성공",
			  "RESERVE_NO" => $_no,
			  "SEND_YN" => "Y",
			  "SEND_DATE" => $_udt
		);

		$conn_rds->query("update cmsdb.dsc_extcoupon set TICKET_STATUS='Y',USE_DATE = '".$_udt."' where TICKET_STATUS='N' and RESERVE_NO= '$_no' limit 1");

		usecouponno($_no);

	}else{
			  header("HTTP/1.0 403");
				$_result = array(
						"RESERVE_STATUS" => "403",
						"RESERVE_STATUS_NM" => "쿠폰상태 변경불가",
						"RESERVE_NO" => $_no
				);
		    echo json_encode($_result);
		    exit;
	}

	return $_result;
}

function setUnuseCoupon($_no,$placecdc){
	global $conn_rds;
	global $conn_cms3;

	switch($placecdc){
		case 'sc':
			$pcd ="'SC'";
		break;
		case 'ob':
			$pcd ="'OB'";
		break;
		case 'ds':
			$pcd ="'DS'";
		break;
		default:
		$pcd ="null";
	}
	$qry = "select * from cmsdb.dsc_extcoupon where placecode = $pcd and RESERVE_NO= '$_no' limit 1 ";
	$_row = $conn_rds->query($qry)->fetch_object();

	// ordermts_coupons, ordermts 테이블에도 사용처리 취소를 업데이트해주기 위한 select 쿼리문 추가 - 제이슨, 2021.09.10
	$qry_cms3 = "select * from spadb.ordermts_coupons where couponno = '$_no' limit 1 ";
	$_row_cms3 = $conn_cms3->query($qry_cms3)->fetch_object();

	if($_row->TICKET_STATUS =="Y"){
		$_result = array(
			"RESERVE_STATUS" => "200",
			"RESERVE_STATUS_NM" => "사용처리 취소성공",
			"RESERVE_NO" => $_no,
			"SEND_YN" => "N",
			"SEND_DATE" => date("Y-m-d H:i:s")
		);
		$conn_rds->query("update cmsdb.dsc_extcoupon set TICKET_STATUS='N',USE_DATE = null where  TICKET_STATUS='Y' and RESERVE_NO= '$_no' limit 1");
		// ordermts_coupons, ordermts 테이블에도 사용처리 취소를 업데이트해주기 위한 update 쿼리문 추가 - 제이슨, 2021.09.10
		$conn_cms3->query("update spadb.ordermts_coupons set state ='N', dt_use = null where state='Y' and couponno = '$_no' limit 1");
		$conn_cms3->query("update spadb.ordermts set usegu ='2', usegu_at = null where state='예약완료' and usegu ='1' and id = '$_row_cms3->order_id' limit 1");
	}else{
		header("HTTP/1.0 403");
		$_result = array(
				"RESERVE_STATUS" => "403",
				"RESERVE_STATUS_NM" => "쿠폰상태 변경불가",
				"RESERVE_NO" => $_no
		);
		echo json_encode($_result);
		exit;
	}	
		return $_result;
	}

function getReports($_mdate,$placecdc){
	global $conn_rds;
	if(empty($_mdate)){
		$_udt = date("Y-m-d");
	}else{
		$_udt = date("Y-m-d", strtotime($_mdate));
	}

	switch($placecdc){
		case 'sc':
			$pcd ="'SC'";
		break;
		case 'ob':
			$pcd ="'OB'";
		break;
		case 'ds':
			$pcd ="'DS'";
		break;
		default:
		$pcd ="null";
	}

	$qry = "SELECT
				placecode as PLACECODE,
				RESERVE_NO,
				TICKET_FG_TYPE_CD,
				TICKET_FG_CD,
				DATE_FORMAT(USE_DATE,'%Y%m%d%H%i%s') as USE_DATE,
				SALE_PRICE
			FROM
				cmsdb.dsc_extcoupon
			WHERE
				USE_DATE like '$_udt%'
				AND TICKET_STATUS = 'Y'
				AND placecode in ($pcd)
			";

	$res = $conn_rds->query($qry);
	$ocnt = $res->num_rows;
	$ord = array();
	while($row = $res->fetch_object()){
			$ord[] = $row;
	}

	$_result = array(
		  "RESERVE_STATUS" => "200",
		  "RESERVE_STATUS_NM" => "조회성공",
		  "REPORT_DATE" => $_udt,
			"REPORT_CNT" => $ocnt,
		  "REPORTS" => $ord
	);

	return $_result;
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

function usecouponno($no){
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
