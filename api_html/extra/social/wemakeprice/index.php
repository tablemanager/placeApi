<?php

/*
 *
 * CMS 채널 주문 인터페이스
 *
 * 작성자 : 이정진
 * 작성일 : 2017-08-20
 *
 * 주문 등록(POST) https://gateway.sparo.cc/extra/agency/v2/dealcode/{상품코드}
 * {"orderNo":"채널주문번호","userName":"고객명","userHp":"고객핸드폰","orderDesc":"기타옵션"}
 *
 * 주문 조회(GET) https://gateway.sparo.cc/extra/agency/v2/chorderno/{채널주문번호}
 * 주문 취소(PATCH) https://gateway.sparo.cc/extra/agency/v2/chorderno/{채널주문번호}
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

// 인터페이스 로그
$tranid = date("Ymd").genRandomStr(10); // 트렌젝션 아이디

$logsql = "insert cmsdb.extapi_log set apinm='위메프', tran_id='$tranid', ip='".get_ip()."', logdate= now(), apimethod='$apimethod', querystr='".$para."', header='".json_encode($apiheader)."', body='".$jsonreq."'";
 $conn_rds->query($logsql);

// 인증 정보 조회
$auth = $apiheader['Authorization'];
$authqry = "SELECT * FROM spadb.extapi_config WHERE authkey = '$auth' limit 1";
$authres = $conn_cms3->query($authqry);
$authrow = $authres->fetch_object();

// ACL 확인
if(!in_array(get_ip(),json_decode($authrow->accessip,false))){
    header("HTTP/1.0 401 Unauthorized");
    $res = array("Result"=>"4100","Msg"=>"아이피 인증 오류 : ".get_ip());
   // echo json_encode($res);
   // exit;
}

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
    case 'GET':
        // 주문 조회
        get_extorder_info($ch_id,$itemreq);
    break;
    case 'POST':
        // 주문 등록
        set_extorder_insert($cpcode,$ch_id,$itemreq,$jsonreq);
    break;
    case 'PATCH':
        // 주문 취소, 변경
        set_extorder_update($ch_id,$itemreq,$jsonreq);
    break;
    default:
        header("HTTP/1.0 400 Bad Request");
        $res = array("Result"=>"4000","Msg"=>"파라미터 오류");
        echo json_encode($res);
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
function get_extorder_info($grmt_id,$itemreq){
    global $conn_cms3;

    $ch_orderno = $itemreq[1];

    $usql = "SELECT
                *,
                AES_DECRYPT(UNHEX(hp),'Wow1daY') as dhp
             FROM
                spadb.ordermts
             WHERE
                 ch_orderno = '$ch_orderno'
             AND ch_id = '$grmt_id'
             LIMIT 1";

    $ures = $conn_cms3->query($usql);
    $urow = $ures->fetch_object();

    if($urow->id){

        // 외부 쿠폰 사용 조회(에버랜드, 롯데등)
        // 쿠폰의 prefix로 구분함

        $cptype = substr($urow->barcode_no,0,2);

        switch($cptype){
            case 'S0': // 에버랜드 쿠폰 조회
            case 'CB': // 에버랜드 쿠폰 조회
            case 'EL': // 에버랜드 쿠폰 조회
                $useflag = getuseev(str_replace(";", "",$urow->barcode_no));
                $usedate = $urow->usegu_at;

            break;
            case '04': // 롯데워터 쿠폰 조회
                $res = explode(";",getusewp($urow->barcode_no));

                $useflag = $res[0];
                $usedate = $urow->usegu_at;

            break;
            default:
                $useflag = $urow->usegu;
                $usedate = $urow->usegu_at;
        }


		// 이버스 임시 조회

		if($urow->itemmt_id == "31580" or  $urow->itemmt_id == "31581" or $urow->itemmt_id == "31582"){
			//get_ebusinfo
			$cprow 	= $conn_cms3->query("select opt_coupon from spadb.pcms_extcoupon where couponno = '$urow->barcode_no'")->fetch_object();
			if(!empty($cprow->opt_coupon)){
				$ebuscp = json_decode($cprow->opt_coupon)[0];
				$useflag = get_ebusinfo($ebuscp);
			}
		}

        if($useflag == 1){
            $usecp = "Y";
        }else{
            $usecp = "N";
        }

        $order[] = array("orderno" => $urow->orderno, // 주문번호
                         "ch_orderno" => $urow->ch_orderno, // 채널 주문번호
                         "cus_nm" => $urow->usernm, // 고객명
                         "cus_hp" => $urow->dhp, // 고객핸드폰
                         "state" => $urow->state, // 주문상태
                         "itemcode" => $urow->itemmt_id, // 상품코드
                         "qty" => $urow->man1, // 주문 수량
                         "expdate" => $urow->usedate, // 유효기간(이용일)
                         "use" => $usecp, // 이용구분
                         "usedate" => $usedate, // 이용처리일
                         "canceldate" => $urow->canceldate, // 취소일
                         "couponno" => $urow->barcode_no // 쿠폰번호
                        );

        $result = array("Code" => "1000",
                        "Msg" => "성공",
                        "Result" => $order);

        header("HTTP/1.0 200 OK");
        echo json_encode($result);

    }else{
        // 조회 결과가 없을시
        $result = array("Code" => "4002",
                        "Msg" => "조회 결과가 없습니다.",
                        "Result" => null);

        header("HTTP/1.0 404");
        echo json_encode($result);
    }

}

// 주문 주문등록
function set_extorder_insert($cpcode,$grmt_id,$itemreq,$jsonreq){
    global $conn_cms;
    global $conn_cms3;
    global $conn_cms2;
    global $conn_rds;
    global $tranid;

    $itemcode = $itemreq[1];

    $chorderno = "0";
    $info = json_decode($jsonreq);

    if(!$itemcode){
        header("HTTP/1.0 400 Bad Request");
        $res = array("Result"=>"4000","Msg"=>"필수 파라미터 오류");
        echo json_encode($res);
        exit;
    }

    if(chk_chorderno($cpcode,$info->orderNo)){
        $result = array("Code" => "4000",
                        "Msg" => "이미 등록된 주문이거나 중복된 주문번호입니다.",
                        "Result" => null);

        header("HTTP/1.0 400 Bad Request");

        echo json_encode($result);
        exit;
    }else{
        // 임시 주문 테이블에 주문 입력
        $itemsql = "SELECT * from pcmsdb.items_ext where channel = '$cpcode' and pcmsitem_id = '$itemcode' and useyn='Y'";

//		if(get_ip() =="106.254.252.100") echo $itemsql;

		$itemres = $conn_cms->query($itemsql);
        $itemurow = $itemres->fetch_object();

        if(!$itemurow->id){

           // 조회 결과가 없을시
            $result = array("Code" => "4003",
                            "Msg" => "상품 정보가 없거나 판매중이 아닙니다.",
                            "Result" => null);
            header("HTTP/1.0 400 Bad Request");

            echo json_encode($result);

            // 담당자 문자 발송
            $msg = "위메프(N) 연동 누락 - $itemcode";
            send_report("01090901678",$msg);
            send_report("01067934084",$msg); // tony
			send_notice("위메프 연동","위메프 연동누락 - $itemcode");
            exit;

        }else{

			if(!$info->Qty) $info->Qty = 1;

            // 쿠폰번호 생성
            $sellcode = trim($itemurow->gu);

            if(strlen($sellcode) > 3) {

                $n = 1; // 기본 발권 수량

				$cpno = get_coupon_EXT("WMP",$sellcode,$n,$info->orderNo,$info->userName,$info->userHp);

            }else{
				// 지류권 쿠폰발급 (사용처리 반드시 체크할것)
                $cpno = get_pmcoupon(16,$info->orderNo ,$itemurow->usedate,$info->userName,$info->userHp);
            }

// 시설 발권

		if($itemurow->typefac == "AP"){

				$tempnm = genRandomStr(10);
				$temphp = "010".get_numcoupon(8);


				$stayorder = array("ORDERNO"=>$info->orderNo,
								"CH_ORDERNO"=>$info->orderNo,
								"SELLCODE"=>$itemcode,
								"UNIT"=>1,
								"RCVER_NM"=>$info->userName,
								"RCVER_TEL"=>$info->userHp);
			$cpno = "XXXX".get_pmcoupon(16,null ,null,null,null)."XXXX";
			$smsflag="W";
		}



		if($itemurow->typefac == "ST"){
			// 스마틱스(이랜드크루즈)
			$stayorder = array("ORDERNO"=>$info->orderNo,
						"CH_ORDERNO"=>$info->orderNo,
						"SELLCODE"=>$itemcode,
						"UNIT"=>1,
						"RCVER_NM"=>$info->userName,
						"RCVER_TEL"=>$info->userHp);
		//	$cps = json_decode(get_smatixcoupon(json_encode($stayorder)));
		//	$cpno = $cps->couponno;
		}

		// 휘팍
		if($itemurow->typefac == "PB"){

			$pborders = array(
				"itemid"=>$itemcode,
				"usernm"=>$info->userName,
				"userhp"=>$info->userHp,
				"ch_orderno"=>$info->orderNo,
				"ch_id"=>142
			);
			//if(get_ip() =="106.254.252.100") print_r($pborders);
			$_pbjson = json_encode($pborders);

			$_phres = json_decode(get_phcoupon($_pbjson));

//			$cpno = $_phres->rprsSellNo;
			$cpno = $_phres->rprsBarCd;

		}

    if(get_ip() =="106.254.252.100"){

    }

    if($itemurow->typefac == "DM"){
          $dmsql = "update apidb.dm_pincode set orderno='$info->orderNo',hp='$info->userHp', usernm='$info->userName', syncresult= 'R', regdate = now() where couponno = '$cpno' limit 1";
          $conn_cms2->query($dmsql);
    }

            if(strlen($cpno) < 4){
              // 조회 결과가 없을시
                $result = array("Code" => "4003",
                                "Msg" => "판매중지 상품.",
                                "Result" => null);
                header("HTTP/1.0 400 Bad Request");

                echo json_encode($result);

                $msg = "위메프 쿠폰생성 오류 - $itemcode $sellcode $itemurow->typefac - $info->orderNo";
	            send_report("01090901678",$msg);
	            send_report("01067934084",$msg); // tony
				//send_notice("위메프 쿠폰","위메프 쿠폰 생성 실패 - $itemcode $sellcode");
                exit;
            }

       		    $extsql = "insert pcmsdb.ordermts_ext set
						order_num = '".$info->orderNo."',
						sell_code = '".$itemcode."',
						buy_opt = '".$info->orderDesc."',
						buy_name = '".$info->userName."',
						buy_date = now(),
						buy_hp = '".$info->userHp."',
						buy_count = '".$info->Qty."',
						channel = '".$cpcode."',
						postcode =  '".$info->postCode."',
						address =  '".$info->addr1."',
						address2 =  '".$info->addr2."',
						pcms_couponno =  '".$cpno."',
						ip = '".get_ip()."',
						regdate = now()";

            $conn_cms->query($extsql);

         // 상품 정보
         $res = order_cms($info->orderNo, $itemcode,$info->Qty,$itemurow->usedate,$state,$info->userName,$info->userHp,$cpno,$info->orderDesc);

         $order[]= array("OrderNo" => $res,
                         "CouponNo" => $cpno);

         $result = array("Code" => "1000",
                         "Msg" => "성공",
                         "Result" => $order);

         header("HTTP/1.0 200");

	     // 회팍용 업데이트

         echo $rts = json_encode($result);
         $logsql2 = "update cmsdb.extapi_log set apiresult = '".addslashes($rts)."' where tran_id='$tranid' limit 1";
         $conn_rds->query($logsql2);



         exit;

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


// 주문 주문 취소, 변경
function set_extorder_update($grmt_id,$itemreq,$jsonreq){

    global $conn_cms3;
    global $conn_cms
        ;
    $ch_orderno = $itemreq[1];

    $usql = "SELECT
                *,
                AES_DECRYPT(UNHEX(hp),'Wow1daY') as dhp
             FROM
                spadb.ordermts
             WHERE
                 ch_orderno = '$ch_orderno'
             AND ch_id = '$grmt_id'
             LIMIT 1";

    $ures = $conn_cms3->query($usql);
    $urow = $ures->fetch_object();

    // 사용확인

    $cinfosql = "select * from pcmsdb.cms_coupon where chgu ='W' and items_id = '".$urow->itemmt_id."' ";
    $cinforow = $conn_cms->query($cinfosql)->fetch_object();

    switch($cinforow->ctype){
        case 'WP':
        case 'WP2':
            $useflag = getusewp($urow->barcode_no);
        break;
        case 'EV':
        case 'EL':
        case 'CB':
            $useflag = getusestks($urow->barcode_no);
		break;
        default:
            $useflag = $urow->usegu;
    }

    if($useflag != '1'){
        $order[]= array("OrderNo" => $urow->ch_orderno,
                        "CouponNo" => $urow->barcode_no);

             $result = array("Code" => "1000",
                             "Msg" => "취소 성공",
                             "Result" => $order);

            $cusql = "update spadb.ordermts set state='취소' where id = '".$urow->id."' limit 1";
            $conn_cms3->query($cusql);

             header("HTTP/1.0 200");
             echo json_encode($result);
             exit;


    }else{

             $order[]= array("OrderNo" => null,
                             "CouponNo" => null);

             $result = array("Code" => "4004",
                             "Msg" => "취소할수 없거나 변경이 불가능한 주문",
                             "Result" => $order);

             header("HTTP/1.0 400");
             echo json_encode($result);
             exit;

    }


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
function chk_chorderno($cpcode,$orderno){
    global $conn_cms;

    $sql = "SELECT * from pcmsdb.ordermts_ext where channel = '$cpcode' and order_num = '$orderno' limit 1";
    $res = $conn_cms->query($sql);
    $row = $res->fetch_object();

    if($row->idx){
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
        //$apireq = "https://gateway.sparo.cc/phoenix/ss/";
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
