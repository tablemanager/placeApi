<?php

/*
 *
 * 위메프 신규 티켓 연동
 *
 * 작성자 : 이정진
 * 작성일 : 20200507
 *
 * 발행요청 https://gateway.sparo.cc/extra/social/wemaketickets/requestTicketIssue
 * 상태조회 https://gateway.sparo.cc/extra/social/wemaketickets/requestInquiry
 * 재전송요청 https://gateway.sparo.cc/extra/social/wemaketickets/requestResend
 * 취소요청 https://gateway.sparo.cc/extra/social/wemaketickets/requestCancel
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
// 인터페이스 로그
$tranid = date("Ymd").genRandomStr(10); // 트렌젝션 아이디
$logsql = "insert cmsdb.extapi_log set apinm='위메프티켓', tran_id='$tranid', ip='".get_ip()."', logdate= now(), apimethod='$apimethod', querystr='".$para."', header='".json_encode($apiheader)."', body='".$jsonreq."'";
$conn_rds->query($logsql);

// 인증 정보 조회
$auth = $apiheader['Authorization'];
$authqry = "SELECT * FROM spadb.extapi_config WHERE authkey = '$auth' limit 1";
$authres = $conn_cms3->query($authqry);
$authrow = $authres->fetch_object();

$authrow->cp_code = "2222222";
// API키 확인
if(!$authrow->cp_code){

    header("HTTP/1.0 401 Unauthorized");
    $res = array("Result"=>"4100","Msg"=>"인증 오류");
    echo json_encode($res);
    exit;

}else{

    $cpcode = $authrow->cp_code; // 채널코드
    $cpname = $authrow->cp_name; // 채널명
    $grmt_id = $authrow->cp_grmtid; // 채널 업체코드
    $ch_id = $grmt_id;

}


// REST Method 분기
switch($apimethod){
    case 'POST':
      switch($itemreq[0]){

        case 'requestTicketIssue': // 발권요청
			$_result = set_extorder_insert($jsonreq);
			echo json_encode($_result);
        break;

        case 'requestInquiry':// 조회
			$_result = get_extorder_info($jsonreq);
			echo json_encode($_result);

        break;

		case 'requestResend': // 재전송

			$_result = set_resend_update($jsonreq);
			echo json_encode($_result);

        break;

	

        break;
        case 'requestCancel': // 취소
			$_result = set_extorder_update($jsonreq);
			echo json_encode($_result);
        break;
	    default:
        header("HTTP/1.0 400 Bad Request");
        $res = array("status"=>"400","code"=>"E000","title"=>"파라미터 오류","detail"=>null);
		echo json_encode(set_error($res));
      }

    break;
    default:
        header("HTTP/1.0 400 Bad Request");
        $res = array("status"=>"400","code"=>"E000","title"=>"파라미터 오류","detail"=>null);
		echo json_encode(set_error($res));

}



// 채널 주문 테이블 주문 정보
function get_chorderno($cpcode,$orderno){
    global $conn_cms;

    $itemsql = "SELECT * pcmsdb.ordermts_ext where channel = '$cpcode' and orderno_num = '$orderno' limit 1";

    $itemres = $conn_cms->query($itemsql);
    $itemurow = $itemres->fetch_object();

    return $itemurow;
}

// 주문 조회
function get_extorder_info($jsonreq){
    global $conn_cms;
    global $conn_cms3;
    global $conn_cms2;
    global $conn_rds;
    global $tranid;

    $info = json_decode($jsonreq);

     $usql = "SELECT
                *
             FROM
                cmsdb.cms_wmp_orders
             WHERE
                 oid = '$info->orderNo'
             LIMIT 1";

    $ures = $conn_rds->query($usql);
    $urow = $ures->fetch_object();


    if($urow->id){

		 $order= [
					"data" => 
								[
									ticketCode => $urow->ticketCode,
 									orderNo => $info->orderNo,
			 						ticketUsedDt => null,
									ticketValidStartDt => "2020-05-01 00:00:00",
									ticketValidEndDt => "2020-05-30 23:59:59",									
									ticketStatus => "T1"
								]
						 	];

		return $order;
    }else{

		 $order = [
					"data" => 
								[
									ticketCode => null,
 									orderNo => $info->orderNo,
			 						ticketUsedDt => null,
									ticketValidStartDt => null,
									ticketValidEndDt => null,
									ticketStatus => "T0"
								]
						 	];


		return $order;
    }

}

// 주문 주문등록
function set_extorder_insert($jsonreq){
    global $conn_cms;
    global $conn_cms3;
    global $conn_cms2;
    global $conn_rds;
    global $tranid;

    $info = json_decode($jsonreq);

    if(chk_chorderno($info->orderNo)){
        header("HTTP/1.0 400 Bad Request");
        $res = array("status"=>"400","code"=>"E010","title"=>"티켓 발급에 실패하였습니다.","detail"=>"이미 발급된 주문입니다.");
		echo json_encode(set_error($res));
        exit;

    }else{
        // 임시 주문 테이블에 주문 입력
        $itemsql = "SELECT * from pcmsdb.items_ext where channel = '$cpcode' and pcmsitem_id = '$itemcode' and useyn='Y'";


		$itemres = $conn_cms->query($itemsql);
        $itemurow = $itemres->fetch_object();
		$itemurow->id = true;
        if(!$itemurow->id){

           // 조회 결과가 없을시
			header("HTTP/1.0 400 Bad Request");
			$res = array("status"=>"400","code"=>"E010","title"=>"티켓 발급에 실패하였습니다.","detail"=>"판매 가능상태가 아닙니다.");
			echo json_encode(set_error($res));


            // 담당자 문자 발송
            $msg = "위메프(N) 연동 누락 - $itemcode";
            send_report("01090901678",$msg);
            send_report("01067934084",$msg); // tony
			send_notice("위메프 연동","위메프 연동누락 - $itemcode");
            exit;

        }else{


                $cpno = get_pmcoupon(16,$info->orderNo ,$itemurow->usedate,$info->username,$info->buyerPhone);

				if(get_ip() =="106.254.252.100"){
				}


	            if(strlen($cpno) < 4){
					// 쿠폰 발급 실패
	            }else{
	       		   $extsql = "insert cmsdb.cms_wmp_orders set
										partnerId ='API',
										oid = '".$info->orderNo."',
										reservationId = '".$info->orderNo."',
										partnerGoodsCode = '".$info->prodNo."',		
										userNm = '".$info->username."',		
										userHp = '".$info->buyerPhone."',		
										createdAt = '".$info->orderDt."',
										sellerOptCd = '".$info->sellerOptCd."',
										ticketCode = '$cpno',
										orderStatus = '결제완료'";
		            $conn_rds->query($extsql);
					//$res = order_cms($info->orderNo, $itemcode,$info->Qty,$itemurow->usedate,$state,$info->userName,$info->userHp,$cpno,$info->orderDesc);

					 $order= [
								"data" => 
								[
									ticketCode => $cpno,
									ticketValidStartDt => "2020-05-01 00:00:00",
									ticketValidEndDt => "2020-05-30 23:59:59",
									transactionId => $cpno,
									orderNo => $info->orderNo
								]
						 	];


				}

				return $order;
        }
    }
}

// cms 주문입력
function order_cms($chorderno,$itemcode,$qty,$expdate,$state,$usernm,$userhp,$couponno,$orderdesc) {
    global $conn_cms;
    global $conn_cms2;
    global $conn_cms3;

    global $cpname;
    global $ch_id;

    $orderno = date("Ymd")."_".genRandomStr(12);
    $mdate = date("Y-m-d");
    $iteminfo = get_iteminfo($itemcode,$expdate);

    $man1 = $qty;

    if(!$couponno) $couponno = $orderno;

    // 다회권 처리
    $cparr = $conn_cms3->query("select * from spadb.pcms_extcoupon where couponno='$couponno'")->fetch_object();

    if(strlen($cparr->opt_coupon) > 10){

        $cdarr = json_decode($cparr->opt_coupon);
        $wstr = $couponno.";";
        foreach($cdarr as $wcp){
			if(strlen($wcp) < 10) continue;
            // 주문테이블 다회권 스트링 생성
            $wstr.= $wcp.";";
            // 대명 코드 분기
            if(substr($wcp,0,7) == "40303535" or substr($wcp,0,7) == "40303537"){
                $dmsql = "update apidb.dm_pincode set orderno='$orderno',hp='$userhp', usernm='$usernm', syncresult= 'R', regdate = now() where couponno = '$wcp' limit 1";

                $conn_cms2->query($dmsql);
            }

        }
        $couponno = $wstr;
    }

	// 휘닉스 분기
	if($iteminfo['gtmt_id'] =="431"){
		$smsflag = "Q";
	}else{
		$smsflag = "N";
	}


    $mode = "INSERT spadb.ordermts SET created = now(),";

	$where = "
		gu = 'J',
		updated = now(),
		mechstate 	=	'정산대기',
		meipstate 	=	'정산대기',
		site	=	'SSO',
		orderno = '$orderno',
		mdate	= '$mdate',
		usedate	= '$expdate',

		jpnm	=  '".$iteminfo['jpnm']."',
		itemnm	=  '".$iteminfo['itemnm']."',
		man1	=	'$man1',
		man2	=	'0',
		chnm	=	'".$cpname."',
		grnm 	=	'".$iteminfo['grnm']."',
		jpmt_id 	=	'".$iteminfo['jpmt_id']."',
		itemmt_id 	=	'".$iteminfo['itemid']."',
		ch_id 	=	'".$ch_id."',
		pricemt_id  = '".$iteminfo['price_id']."',
		grmt_id  = '".$iteminfo['gtmt_id']."',
        ch_orderno = '".$chorderno."',
		usernm 	=	'".$usernm."',
		hp	=	hex(aes_encrypt( '".$userhp."', 'Wow1daY' )),
		usernm2	=	'$usernm',
		hp2	=	hex(aes_encrypt( '".$userhp."', 'Wow1daY' )),
		dangu = '공통권',
		amt	=	'".$iteminfo['price_sale']."',
		accamt	=	'".$iteminfo['price_sale']."',
		damnm 	=	'시스템',
		usegu = '2',
		smsgu='".$smsflag."',
		barcode_no = '".$couponno."',
		state	=	'예약완료'
		";

        $conn_cms3->query($mode.$where);


        $ordrow = $conn_cms3->query("select id from spadb.ordermts where orderno = '$orderno'")->fetch_object();
        $conn_cms3->query("update spadb.pcms_extcoupon set order_id = '".$ordrow->id."' where couponno = '$couponno' limit 1");

        // 아쿠아필드 분기
        $aqsql = "update pcmsdb.pos_orders set
                      ORDERNO = '$orderno',
                      SEQ = '1',
                      BUYDATE = now(),
                      CUSNM= '$usernm',
                      CUSMOBILE = '".substr($userhp,-4)."'
                  where COUPONNO = '".$couponno."' limit 1";
        $conn_cms->query($aqsql);

		if($iteminfo['gtmt_id'] =="431"){
			 $pbosql = "update spadb.phoenix_pkgcoupon set orderid= '$ordrow->id', orderno = '$orderno' where rprsBarCd = '$couponno'";
			$conn_cms3->query($pbosql);
		}

        return $orderno;
}

// 랜덤 스트링
function genRandomStr($length = 10) {
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}


// 연동 정보 조회
function get_iteminfo($itemid,$expdate){
    global $conn_cms;

    // 상품 정보
    $itemsql = "SELECT * from CMSDB.CMS_ITEMS where item_id = '$itemid' limit 1";
    $itemres = $conn_cms->query($itemsql);
    $itemurow = $itemres->fetch_object();

    // 업체 정보
    $cpsql = "SELECT *  from CMSDB.CMS_COMPANY where com_id = '".$itemurow->item_cpid."' limit 1";
    $cpcres = $conn_cms->query($cpsql);
    $cpcrow = $cpcres->fetch_object();

    // 시설 정보
    $facsql = "SELECT *  from CMSDB.CMS_FACILITIES where fac_id = '".$itemurow->item_facid."' limit 1";
    $facres = $conn_cms->query($facsql);
    $facrow = $facres->fetch_object();

    // 가격 정보
    $pricesql = "SELECT * from CMSDB.CMS_PRICES where price_itemid = '$itemid' and price_date = '$expdate' limit 1";
    $priceres = $conn_cms->query($pricesql);
    $pricerow = $priceres->fetch_object();

    $result = array(
                    "itemid"=> $itemurow->item_id,
                    "itemnm"=> $itemurow->item_nm,
                    "gtmt_id"=> $cpcrow->com_id,
                    "grnm"=> $cpcrow->com_nm,
                    "jpmt_id"=> $itemurow->item_facid,
                    "jpnm"=> $facrow->fac_nm,
                    "price_id"=> $pricerow->price_id,
                    "price_sale"=> $pricerow->price_sale,
                    "price_in"=> $pricerow->price_in,
                    "price_out"=> $pricerow->price_out
                    );

    return $result;
}

// 문자 재전송
function set_resend_update($jsonreq){
    global $conn_cms3;
    global $conn_cms;
    global $conn_rds;
    $info = json_decode($jsonreq);

    $usql = "SELECT
                *
             FROM
                cmsdb.cms_wmp_orders
             WHERE
                 oid = '$info->orderNo'
             LIMIT 1";

    $ures = $conn_rds->query($usql);
    $urow = $ures->fetch_object();


    if($urow->id){
					 $order= [
								"data" => 
								[
 									orderNo => $info->orderNo,
									ticketCode => $urow->ticketCode,
									buyerPhone => $info->buyerPhone,
									resultCode => "success"

								]
						 	];	
			return $order;
	}else{
			header("HTTP/1.0 400 Bad Request");
			$res = array("status"=>"400","code"=>"E003","title"=>"해당되는 티켓 정보가 존재하지 않습니다..","detail"=>null);
			echo json_encode(set_error($res));
			exit;	
	}
}

// 주문 주문 취소, 변경
function set_extorder_update($jsonreq){

    global $conn_cms3;
    global $conn_cms;
    global $conn_rds;


    $info = json_decode($jsonreq);

    $usql = "SELECT
                *
             FROM
                cmsdb.cms_wmp_orders
             WHERE
                 oid = '$info->orderNo'
             LIMIT 1";

    $ures = $conn_rds->query($usql);
    $urow = $ures->fetch_object();


    if($urow->id){

		 $order= [
					"data" => 
								[
									ticketCode => $urow->ticketCode,
 									orderNo => $info->orderNo,
									transactionId => $urow->ticketCode,
									resultCode  => "success"
								]
						 	];

		return $order;

    }else{	
			header("HTTP/1.0 400 Bad Request");
			$res = array("status"=>"400","code"=>"E003","title"=>"해당되는 티켓 정보가 존재하지 않습니다..","detail"=>null);
			echo json_encode(set_error($res));
			exit;

	}

}

// 에러코드
function  set_error($_req){

		 $order = [
					"error" => 
								[
									status => $_req['status'],
									code => $_req['code'],
									title => $_req['title'],
									detail => $_req['detail']
								]
						 	];

		 return $order;
}

// 이버스 실시간 주문조회
function get_ebusinfo($_no){

	include '/home/sparo.cc/lib/placem_helper.php';
	include '/home/sparo.cc/wizdome_script/class/class.wizdome.php';
	//include '/home/sparo.cc/wizdome_script/class/wizdome_model.php';

	$_curl = new wizdome();
	$_result = $_curl->search_coupon($_no);
	for ($i = 0; $i < count($_result['result']);$i++){
		if ($_result['result'][$i]['status'] == "0"){
		   $useflag = "2";
		} else {
		   $useflag = "1";
		}
	}

	return $useflag;
}

// 클라이언트 아아피
function get_ip(){

    $res = explode(",",$_SERVER["HTTP_X_FORWARDED_FOR"]);
    return $res[0];
}

// 외부 주문 테이블 조회
function chk_chorderno($orderno){
    global $conn_rds;

    $sql = "SELECT * from cmsdb.cms_wmp_orders where oid = '$orderno'";
    $res = $conn_rds->query($sql);
    $row = $res->fetch_object();

    if($row->id){
        return true;
    }else{
        return false;
    }

}

// 롯데워터 사용조회
function getusewp($no) {
	$curl = curl_init();
    $url = "http://cms.sparo.co.kr/api/?q=".$no;
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_USERAGENT,"Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1" );
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 5);

    $data = curl_exec($curl);
    $info = curl_getinfo($curl);
    curl_close($curl);
    if($info['http_code'] == "200"){
        $res = explode(";",$data);

        if($res[1] == "1"){
            $useflag = "1";
        }else{
            $useflag = "2";
        }

        return $useflag;
    }else{
        return false;
    }
}


function get_coupon_EXT($chcode, $sellcode,$n,$orderno,$usernm,$usehp) {

	global $conn_cms3;

	$chcode = $chcode;
	$orderno = $orderno;
	$sellcode = $sellcode;
	$unit = $n;
	$usernm = $usernm;
	$userhp = $usehp;

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

	$ordsql = "select * from spadb.pcms_extcoupon where order_no = '$orderno' and sellcode='$sellcode' limit 1";
	$result = $conn_cms3->query($ordsql)->fetch_object();

	return $result->couponno;

}

// 롯데워터 쿠폰 발권

function get_coupon_LW($chcode, $sellcode,$n,$orderno,$usernm,$usehp) {

        $reqinfo = array(
            "CHCODE" => $chcode,
            "ORDERNO" => $orderno,
            "SELLCODE" => $sellcode,
            "UNIT" => $n,
            "USERNM" => $usernm,
            "USERHP" => $usehp
        );

        $post = json_encode($reqinfo);

        $apiurl="http://extapi.sparo.cc/internal/getcouponno/".$orderno;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $apiurl);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_USERAGENT,"Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1" );
        curl_setopt($curl, CURLOPT_POSTFIELDS,$post);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);

        $data = json_decode(curl_exec($curl));
        $info = curl_getinfo($curl);
        curl_close($curl);
        if($info['http_code'] == "200"){
            return $data[0];
        }else{
            return false;
        }


}


	function get_hpcoupon($post)
    {
        $ch = curl_init();
        $apireq = "http://extapi.sparo.cc/staynote/order";

		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "post");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$post);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_URL, $apireq);


		$info = curl_getinfo($ch);
        $data = curl_exec($ch);


		return $data;
	}

	function get_phcoupon($post)
    {
        $ch = curl_init();

        $apireq = "https://gateway.sparo.cc/phoenix/rese/";
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "post");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$post);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_URL, $apireq);

		$info = curl_getinfo($ch);
        $data = curl_exec($ch);


		return $data;
	}

	function get_smatixcoupon($post)
    {
        $ch = curl_init();
        $apireq = "http://extapi.sparo.cc/staynote/order";

		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "post");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$post);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_URL, $apireq);


		$info = curl_getinfo($ch);
        $data = curl_exec($ch);


		return $data;
	}

function get_numcoupon($length=16){



	$characters = '0123456789';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;

}

function get_pmcoupon($length=16,$orderno ,$expdate,$usernm,$usehp){



	$characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;

}

// 에버랜드 사용조회
function getuseev($no) {

    $curl = curl_init();
    $url = "https://api.placem.co.kr/foreigner/ev.php?no=".$no."&pc=CP";
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_USERAGENT,"Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1" );
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 5);

    $data = curl_exec($curl);
    $info = curl_getinfo($curl);
    curl_close($curl);

    $result = simplexml_load_string($data);

    switch($result->PIN_STATUS){
        case 'UC':
        case 'UR':
            $useflag = "1";
        break;
        case 'PC':
            $useflag = "2"; // 취소핀
        break;
        case 'PS':
        case 'CR':
            $useflag = "2";
        break;
        default:
            $useflag = "1"; // 확인할수 없으면 사용으로 본다.
    }

    return $useflag;

}

// 에버랜드 사용조회
function getusestks($no) {

    $curl = curl_init();
    $url = "http://extapi.sparo.cc/everland/sync/".$no;
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_USERAGENT,"Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1" );
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 5);

    $data = curl_exec($curl);
    $info = curl_getinfo($curl);
    curl_close($curl);

    $result = json_decode($data);

    switch($result->PIN_STATUS){
        case 'UC':
        case 'UR':
            $useflag = "1";
        break;
        case 'PC':
            $useflag = "2"; // 취소핀
        break;
        case 'PS':
        case 'CR':
            $useflag = "2";
        break;
        default:
            $useflag = "2"; // 확인할수 없으면 미사용으로 본다.
    }

    return $useflag;

}



function send_notice($title,$msg){

	$curl = curl_init();
	$msgarr = array("title"=>$title,
					"description"=>$msg);
	curl_setopt_array($curl, array(
	  CURLOPT_URL => "http://extapi.sparo.cc/internal/sysnotice/ACT",
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => "",
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 30,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "POST",
	  CURLOPT_POSTFIELDS => json_encode($msgarr),
	  CURLOPT_HTTPHEADER => array(
		"Accept: application/vnd.tosslab.jandi-v2+json",
		"Cache-Control: no-cache",
		"Connection: keep-alive",
		"Content-Type: application/json",
		"Host: extapi.sparo.cc",
		"accept-encoding: gzip, deflate",
		"cache-control: no-cache",
	  ),
	));

	$response = curl_exec($curl);
	$err = curl_error($curl);

	curl_close($curl);

}
?>
