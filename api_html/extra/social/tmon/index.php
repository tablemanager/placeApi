<?php

error_reporting(0);

require_once('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
require_once('/home/placedev/php_script/lib/placemlib.php');
header("Content-type:application/json");
$conn_rds->query("set names utf8");

$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

// 파라미터
$itemreq = explode("/", $para);
$jsonreq = trim(file_get_contents('php://input'));
// 인터페이스 로그
$tranid = date("Ymd") . genRandomStr(10); // 트렌젝션 아이디

$logsql = "insert cmsdb.extapi_log set apinm='티몬', tran_id='$tranid', ip='".get_ip()."', logdate= now(), apimethod='$apimethod', querystr='".$para."', header='".json_encode($apiheader)."', body='".$jsonreq."'";
$conn_rds->query($logsql);


$auth = $apiheader['Authorization'];

$authqry = "SELECT * FROM spadb.extapi_config WHERE authkey = '$auth' limit 1";
$authres = $conn_cms3->query($authqry);
$authrow = $authres->fetch_object();

$aclmode = $authrow->aclmode;

// $ary['authqry'] = $auth;
// echo json_encode($ary);
// exit;

if($aclmode == "IP"){
	// ACL 확인
	if(!in_array(get_ip(),json_decode($authrow->accessip,false))){
		tmon_error_code("1002" ,"");
	}
}

// API키 확인
if(!$authrow->cp_code){

// 	header("HTTP/1.0 401 Unauthorized");
// 	$res = array("Result"=>"4100","Msg"=>"인증 오류");
// 	echo json_encode($res);
// 	exit;
	
	tmon_error_code("1004" ,$jsonreq->transactionId);

}else{

	$cpcode = $authrow->cp_code; // 채널코드
	$cpname = $authrow->cp_name; // 채널명
	$grmt_id = $authrow->cp_grmtid; // 채널 업체코드
	$ch_id = $grmt_id;

}

// $ary = json_encode(
//     array(
//         'mainBuySrl' => '12122512998',
//         'goodsCd' => '23735',
//         'ticket' => '2965336695',
//         'consumerName' => '전주호',
//         'consumerPhone' => '01082085996',
//         'consumerEmail' => 'connor@placem.co.kr',
//         'createdAt' => '1473239684000',
//         'mainDealSrl' => '531290131',
//         'dealSrl' => '5312901312',
//         'dealTitle' => '홍콩 디즈니랜드 티켓',
//         'optionTitle' => '성인 종일권',
//         'reserveDate' => '2018-12-16',
//         'transactionId' => 'ABCD1234',
//     )
// );

// $ary = json_encode(
//     array(
//         'mainBuySrl' => '12122512997',
//         'goodsCd' => '23735',
//         'ticket' => '2965336695',
//         'pin' => 'MKAnmH8yoG'
//     )
// );

$itemreq[1] = $jsonreq;

// echo json_encode($itemreq);

//테스트는 TMON2 이지만 상품을 TMON으로 만들었음
// $cpcode = 'TMON';
// $cpcode = 'TMON2';
// $ch_id = "150";

// echo json_encode($itemreq);
// exit;

// REST Method 분기
switch ($apimethod) {
    case 'GET':
        switch ($itemreq[0]) {

            case 'order':
                set_extorder_insert($cpcode, $itemreq);
                break;
        }

        // 주문 조회
        //   get_extorder_info($ch_id,$itemreq);
        break;
    case 'POST':
        // 주문 등록

        switch ($itemreq[0]) {
            case 'order':
                set_extorder_insert($cpcode, $itemreq);
                break;
           case 'cancel':
//                set_extorder_update($cpcode,$ch_id,$itemreq,$jsonreq);
               set_extorder_update($ch_id, $itemreq);
               
               break;
               
           case 'sendeticket':
           		resend_ticket($ch_id,$itemreq,$jsonreq);
           	break;
           case 'search':
               get_extorder_info($ch_id,$itemreq);
              break;
//            case 'resend':
//                // set_extorder_insert($cpcode,$ch_id,$itemreq,$jsonreq);
//                break;
        }

        break;
    case 'PATCH':
        // 주문 취소, 변경
//        set_extorder_update($ch_id,$itemreq,$jsonreq);
        break;
    default:
//         header("HTTP/1.0 400 Bad Request");
//         $res = array("Result" => "4000", "Msg" => "파라미터 오류");
//         echo json_encode($res);
        
        tmon_error_code("1006" , $itemreq[1]->transactionId);
}


// 채널 주문 테이블 주문 정보
function get_chorderno($cpcode, $orderno)
{
    global $conn_cms;

    $itemsql = "SELECT * pcmsdb.ordermts_ext where channel = '$cpcode' and orderno_num = '$orderno' limit 1";

    $itemres = $conn_cms->query($itemsql);
    $itemurow = $itemres->fetch_object();

    return $itemurow;
}

// 주문 조회

function get_extorder_info($ch_id, $jsonreq)
{
    global $conn_cms3;


//     $info = json_decode($jsonreq);
    
    $info = json_decode($jsonreq[1]);

//     $ch_orderno = $info->mainBuySrl . "_" . $info->ticket;
    $ch_orderno = $info->mainBuySrl;

    $usql = "SELECT
                *,
                AES_DECRYPT(UNHEX(hp),'Wow1daY') as dhp 
             FROM 
                spadb.ordermts 
             WHERE 
                 ch_orderno = '$ch_orderno' 
             AND ch_id = '$ch_id' 
             LIMIT 1";

    $ures = $conn_cms3->query($usql);
    $urow = $ures->fetch_object();

    if ($urow->id) {

        // 외부 쿠폰 사용 조회(에버랜드, 롯데등)
        // 쿠폰의 prefix로 구분함

        $cptype = substr($urow->barcode_no, 0, 2);

        switch ($cptype) {
            case 'S0': // 에버랜드 쿠폰 조회
            case 'CB': // 에버랜드 쿠폰 조회
            case 'EL': // 에버랜드 쿠폰 조회
                $useflag = getuseev(str_replace(";", "", $urow->barcode_no));
                $usedate = $urow->usegu_at;

                break;
            case '04': // 롯데워터 쿠폰 조회
                $res = explode(";", getusewp($urow->barcode_no));

                $useflag = $res[0];
                $usedate = $urow->usegu_at;

                break;
            default:
                $useflag = $urow->usegu;
                $usedate = $urow->usegu_at;
        }

        if ($useflag == 1) {
            $usecp = "Y";
        } else {
            $usecp = "N";
        }

        $result = array("ticket" => $info->ticket,
            "pin" => $info->ticket,
            "status" => $urow->state);

        header("HTTP/1.0 200 OK");
        echo json_encode($result);

    } else {
    	
    	tmon_error_code( "2004" ,$info->transactionId );
//         // 조회 결과가 없을시
//         $result = array("Code" => "4002",
//             "Msg" => "조회 결과가 없습니다.",
//             "Result" => null);

//         header("HTTP/1.0 404");
//         echo json_encode($result);
    }

}

// 주문 주문등록
function set_extorder_insert($cpcode, $jsonreq)
{
    global $conn_cms;
    global $conn_cms3;
    global $conn_rds;
    global $tranid;

    $chorderno = "0";
    $info = json_decode($jsonreq[1]);

    $itemcode = $info->goodsCd;
    $ch_orderno = $info->mainBuySrl . "_" . $info->ticket;

    // 파라미터 오류 처리
    if (!$itemcode) {
        tmon_error_code( "1004" ,$info->transactionId );
    }

    // 중복 주문 처리 티몬의 경우는 티켓시리얼과 주문번호 체크
    if (chk_chorderno($cpcode, $ch_orderno)) {
        
        tmon_error_code( "2000" ,$info->transactionId );

    } else {
        // 판매채널 연동 셋팅 확인
        $itemsql = "SELECT * from pcmsdb.items_ext where channel = '$cpcode' and pcmsitem_id = '$itemcode' and useyn='Y'";
        $itemres = $conn_cms->query($itemsql);
        $itemurow = $itemres->fetch_object();

        if (!$itemurow->id)
        {
        	tmon_error_code( "2004" ,$info->transactionId );
        } else {

            if (!$info->Qty) $info->Qty = 1;

            // 쿠폰번호 생성
            $sellcode = $itemurow->gu;

            if ($sellcode) {
//                $n = 1;
//                $cpno = get_coupon_LW("WMP", $sellcode, $n, $info->orderNo, $info->userName, $info->userHp);

            } else {
                $cpno = genRandomStr(10);
            }


            if (!$cpno) {
                // 조회 결과가 없을시
            	tmon_error_code( "2004" ,$info->transactionId );

            }
            
            
            //$info->consumerName
            //$info->consumerPhone
            //이메일은 사용안함
            
            //암호화 사용 시
            $consumerName = tmon_decrypt($info->consumerName);
            $consumerPhone = tmon_decrypt($info->consumerPhone);
            
            // 티몬 파라미터에 맞게 외부 주문 생성(임시)
            $extsql = "insert pcmsdb.ordermts_ext set
						order_num = '" . $ch_orderno . "',
						sell_code = '" . $itemcode . "',
						buy_opt = '" . $info->optionTitle . "',
						buy_name = '" . $info->consumerName . "',
						buy_date = now(),
						buy_hp = '" . $info->consumerPhone . "',
						buy_count = '1',
						channel = '" . $cpcode . "',
						pcms_couponno =  '" . $cpno . "',
						ip = '" . get_ip() . "',
						regdate = now()";

            $conn_cms->query($extsql);

            // 상품 정보
            $res = order_cms($info->mainBuySrl, $itemcode, $info->Qty, $itemurow->usedate, $state, $info->consumerName, $info->consumerPhone, $cpno, $info->dealSrl);

            $result = array("mainBuySrl" => $info->mainBuySrl,
                "mainDealSrl" =>  $info->mainDealSrl,
                "ticket" => $info->ticket,
                "pin" => $cpno
            );

            header("HTTP/1.0 200");

            echo $rts = json_encode($result);

            $logsql2 = "update cmsdb.extapi_log set apiresult = '" . addslashes($rts) . "' where tran_id='$tranid' limit 1";
            $conn_rds->query($logsql2);
            exit;
        }
    }
}

// cms 주문입력
function order_cms($chorderno, $itemcode, $qty, $expdate, $state, $usernm, $userhp, $couponno, $orderdesc)
{
    global $conn_cms;
    global $conn_cms3;

    global $cpname;
    global $ch_id;

    $orderno = date("Ymd") . "_" . genRandomStr(12);
    $mdate = date("Y-m-d");
    $iteminfo = get_iteminfo($itemcode, $expdate);

    $man1 = $qty;

    if (!$couponno) $couponno = $orderno;

    // 다회권 처리
    $cparr = $conn_cms3->query("select * from spadb.pcms_extcoupon where couponno='$couponno'")->fetch_object();


    if (strlen($cparr->opt_coupon) > 10) {
        $cdarr = json_decode($cparr->opt_coupon);
        $wstr = $couponno . ";";
        foreach ($cdarr as $wcp) {
            $wstr .= $wcp . ";";
        }
        $couponno = $wstr;
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

		jpnm	=  '" . $iteminfo['jpnm'] . "',
		itemnm	=  '" . $iteminfo['itemnm'] . "',
		man1	=	'$man1',
		man2	=	'0',
		chnm	=	'" . $cpname . "',
		grnm 	=	'" . $iteminfo['grnm'] . "',
		jpmt_id 	=	'" . $iteminfo['jpmt_id'] . "',
		itemmt_id 	=	'" . $iteminfo['itemid'] . "',
		ch_id 	=	'" . $ch_id . "',
		pricemt_id  = '" . $iteminfo['price_id'] . "', 
		grmt_id  = '" . $iteminfo['gtmt_id'] . "', 
        ch_orderno = '" . $chorderno . "',
		usernm 	=	'" . $usernm . "',
		hp	=	hex(aes_encrypt( '" . $userhp . "', 'Wow1daY' )),
		usernm2	=	'$usernm',
		hp2	=	hex(aes_encrypt( '" . $userhp . "', 'Wow1daY' )),
		dangu = '공통권',
		amt	=	'" . $iteminfo['price_sale'] . "',
		accamt	=	'" . $iteminfo['price_sale'] . "',
		damnm 	=	'시스템',
		usegu = '2',
		barcode_no = '" . $couponno . "',		
		state	=	'예약완료'
		";

    $conn_cms3->query($mode . $where);


    $ordrow = $conn_cms3->query("select id from spadb.ordermts where orderno = '$orderno'")->fetch_object();
    $conn_cms3->query("update spadb.pcms_extcoupon set order_id = '" . $ordrow->id . "' where couponno = '$couponno' limit 1");

    return $orderno;
}

// 랜덤 스트링
function genRandomStr($length = 10)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}


// 연동 정보 조회
function get_iteminfo($itemid, $expdate)
{
    global $conn_cms;

    // 상품 정보
    $itemsql = "SELECT * from CMSDB.CMS_ITEMS where item_id = '$itemid' limit 1";
    $itemres = $conn_cms->query($itemsql);
    $itemurow = $itemres->fetch_object();

    // 업체 정보
    $cpsql = "SELECT *  from CMSDB.CMS_COMPANY where com_id = '" . $itemurow->item_cpid . "' limit 1";
    $cpcres = $conn_cms->query($cpsql);
    $cpcrow = $cpcres->fetch_object();

    // 시설 정보
    $facsql = "SELECT *  from CMSDB.CMS_FACILITIES where fac_id = '" . $itemurow->item_facid . "' limit 1";
    $facres = $conn_cms->query($facsql);
    $facrow = $facres->fetch_object();

    // 가격 정보
    $pricesql = "SELECT * from CMSDB.CMS_PRICES where price_itemid = '$itemid' and price_date = '$expdate' limit 1";
    $priceres = $conn_cms->query($pricesql);
    $pricerow = $priceres->fetch_object();

    $result = array(
        "itemid" => $itemurow->item_id,
        "itemnm" => $itemurow->item_nm,
        "gtmt_id" => $cpcrow->com_id,
        "grnm" => $cpcrow->com_nm,
        "jpmt_id" => $itemurow->item_facid,
        "jpnm" => $facrow->fac_nm,
        "price_id" => $pricerow->price_id,
        "price_sale" => $pricerow->price_sale,
        "price_in" => $pricerow->price_in,
        "price_out" => $pricerow->price_out
    );

    return $result;
}

// 주문 주문 취소, 변경
function set_extorder_update($grmt_id, $jsonreq)
{

    global $conn_cms3;
    global $conn_cms;
//     $ch_orderno = $itemreq[1];
    
    $info = json_decode($jsonreq[1]);
    $ch_orderno = $info->mainBuySrl;
    $pin = $info->pin;
//     echo json_encode($arry , true);
    
//     exit;
    
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
    $cinfosql = "select * from pcmsdb.cms_coupon where chgu ='T' and items_id = '" . $urow->itemmt_id . "' ";
    
    $cinforow = $conn_cms->query($cinfosql)->fetch_object();

    switch ($cinforow->ctype) {
        case 'EV':
        case 'EL':
        case 'CB':
            $useflag = "1";
            break;
        case 'WP':
        case 'WP2':
            $useflag = getusewp($urow->barcode_no);
            break;
        default:
            $useflag = $urow->usegu;
    }

    if ($useflag != '1') {

    	if ($urow->state == "취소"){
    		tmon_error_code( "2002" ,$info->transactionId );
    	} else {
    		$cusql = "update spadb.ordermts set state='취소' where id = '" . $urow->id . "' limit 1";
    		$conn_cms3->query($cusql);
    		
    		$result = array("pin" => $pin, "qry" => $cusql ,
    				"success" => true);
    		
    		header("HTTP/1.0 200");
    		echo json_encode($result);
    		exit;
    	}


    } else {

    	if ($urow->state == "취소"){
    		tmon_error_code( "2002" ,$info->transactionId );
    	} else {
    		$result = array("pin" =>$pin, 
    				"success" => false);
    	}
    	
    	header("HTTP/1.0 200");
    	
        echo json_encode($result);
        exit;

    }


}

// 클라이언트 아아피
function get_ip()
{

    $res = explode(",", $_SERVER["HTTP_X_FORWARDED_FOR"]);
    return $res[0];
}

// 외부 주문 테이블 조회
function chk_chorderno($cpcode, $orderno)
{
    global $conn_cms;

    $sql = "SELECT * from pcmsdb.ordermts_ext where channel = '$cpcode' and order_num = '$orderno' limit 1";
    $res = $conn_cms->query($sql);
    $row = $res->fetch_object();

    if ($row->idx) {
        return true;
    } else {
        return false;
    }

}

// 롯데워터 사용조회
function getusewp($no)
{
    $curl = curl_init();
    $url = "http://cms.sparo.co.kr/api/?q=" . $no;
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1");
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 5);

    $data = curl_exec($curl);
    $info = curl_getinfo($curl);
    curl_close($curl);
    if ($info['http_code'] == "200") {
        $res = explode(";", $data);

        if ($res[1] == "1") {
            $useflag = "1";
        } else {
            $useflag = "2";
        }

        return $useflag;
    } else {
        return false;
    }
}

// 롯데워터 쿠폰 발권

function get_coupon_LW($chcode, $sellcode, $n, $orderno, $usernm, $usehp)
{

    $reqinfo = array(
        "CHCODE" => $chcode,
        "ORDERNO" => $orderno,
        "SELLCODE" => $sellcode,
        "UNIT" => $n,
        "USERNM" => $usernm,
        "USERHP" => $usehp
    );

    $post = json_encode($reqinfo);

    $apiurl = "https://gateway.sparo.cc/internal/getcouponno/" . $orderno;
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $apiurl);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1");
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 5);

    $data = json_decode(curl_exec($curl));
    $info = curl_getinfo($curl);
    curl_close($curl);
    if ($info['http_code'] == "200") {
        return $data[0];
    } else {
        return false;
    }

}

// 에버랜드 사용조회
function getuseev($no)
{

    $curl = curl_init();
    $url = "https://api.placem.co.kr/foreigner/ev.php?no=" . $no . "&pc=CP";
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1");
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 5);

    $data = curl_exec($curl);
    $info = curl_getinfo($curl);
    curl_close($curl);

    $result = simplexml_load_string($data);

    switch ($result->PIN_STATUS) {
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

function resend_ticket($ch_id,$jsonreq){
	global $conn_cms3;
	
	$info = json_decode($jsonreq[1]);
	$ch_orderno = $info->mainBuySrl;
	

	$usql = "SELECT  *,
	AES_DECRYPT(UNHEX(hp),'Wow1daY') as dhp
	FROM
	spadb.ordermts
	WHERE
	ch_orderno = '$ch_orderno'
	AND ch_id = '$ch_id'
	LIMIT 1";

	$ures = $conn_cms3->query($usql);
	$urow = $ures->fetch_object();
	
	if ($urow->id) {
		//optsu1옵션 수량이라고 써 있는데 문자 발송 수?
		$mmscnt = $urow->optsu1;
		
		if ($mmscnt < 3) {
			$mcnt = $mmscnt + 1;
			$sendsql = "update spadb.ordermts set smsgu='N', optsu1='$mcnt' where id = '" . $urow->id . "' limit 1";
			$conn_cms3->query ( $sendsql );
			
			$result = array (
					"success" => true
			);
			
			header ( "HTTP/1.0 200 OK" );
			echo json_encode ( $result );
		} else {
			
// 			$result = array (
// 					"success" =>false ,
// 					"exceptionMessage" => "문자 재발송 횟수 초과",
// 			);
			
			mon_error_code("2011" ,$info->transactionId);
			
			header ( "HTTP/1.0 200 OK" );
			echo json_encode ( $result );
		}
	} else {
// 		$result = array (
// 				"Code" => "4000",
// 				"Msg" => "발송 가능 주문이 없습니다.",
// 				"Result" => $order 
// 		);

		tmon_error_code("2004" ,$info->transactionId);
		
		header ( "HTTP/1.0 404 OK" );
		echo json_encode ( $result );
	}
}



// 티몬 암호 복호화
function tmon_decrypt_old($estr)
{

    $secure = "f0918174031c4303a2b75dcc001ad6b5"; // 실제운영시 키 수정

    $h = bin2hex(base64_decode($estr));
    $iv = hex2bin(substr($h, 0, 32));
    $enstr = base64_encode(hex2bin(str_replace($iv, "", $h)));
    $destr = openssl_decrypt($enstr, "AES-256-CBC", $secure, false, $iv);

    return trim(substr($destr, 16, 255));
}


function tmon_decrypt($ciphertext)
{
	
	$password = "f0918174031c4303a2b75dcc001ad6b5";
	// 초기화 벡터와 HMAC 코드를 암호문에서 분리하고 각각의 길이를 체크한다.

	$ciphertext = @base64_decode($ciphertext, true);
	if ($ciphertext === false) return false;
	$len = strlen($ciphertext);
	if ($len < 64) return false;
	$iv = substr($ciphertext, $len - 64, 32);
	$hmac = substr($ciphertext, $len - 32, 32);
	$ciphertext = substr($ciphertext, 0, $len - 64);

	// 암호화 함수와 같이 비밀번호를 해싱한다.

	$password = hash('sha256', $password, true);

	// HMAC 코드를 사용하여 위변조 여부를 체크한다.

	$hmac_check = hash_hmac('sha256', $ciphertext, $password, true);
	if ($hmac !== $hmac_check) return false;

	// 복호화한다.

	$plaintext = @mcrypt_decrypt('rijndael-256', $password, $ciphertext, 'cbc', $iv);
	if ($plaintext === false) return false;

	// 압축을 해제하여 평문을 얻는다.

	$plaintext = @gzuncompress($plaintext);
	if ($plaintext === false) return false;

	// 이상이 없는 경우 평문을 반환한다.

	return $plaintext;
}


function tmon_error_code ($code , $transactionId)
{
	$error_txt = "";
	
	switch ($code) {
		case '0002':
			$error_txt = "업체와의 HTTP 통신시 오류가 발생했습니다.";
		break;
		case '0003':
			$error_txt = "내부 서버 오류";
		break;
		case '0006':
			$error_txt = "HTTP 연결 오류";
		break;
		case '1001':
			$error_txt = "잘못된 연동업체 코드 오류";
		break;
		case '1002':
			$error_txt = "허용되지 않은 아이피 오류";
		break;
		case '1003':
			$error_txt = "잘못된 API 호출 오류";
		break;
		case '1004':
			$error_txt = "필수 파라미터 누락 오류";
		break;
		case '1006':
			$error_txt = "잘못된 파라미터 에러";
		break;
		case '2000':
			$error_txt = "이미 주문 성공한 주문번호";
		break;
		case '2001':
			$error_txt = "이미 사용한 티켓(핀)";
		break;
		case '2002':
			$error_txt = "이미 취소된 티켓(핀)";
		break;
		case '2003':
			$error_txt = "취소(환불) 진행 중인 티켓(핀)";
		break;
		case '2004':
			$error_txt = "존재하지 않는 티켓(핀)";
		break;
		case '2005':
			$error_txt = "미사용 티켓(핀)";
		break;
		case '2007':
			$error_txt = "유효기간 만료된 티켓(핀)";
		break;
		case '2008':
			$error_txt = "사용취소 가능일이 지난 티켓(핀)";
		break;
		case '2009':
			$error_txt = "중복된 주문번호";
		break;
		case '2010':
			$error_txt = "중복된 티켓번호";
		break;
		case '2011': //임시 티몬에는 없는 코드
			$error_txt = "문자 재발송 횟수 초과";
		break;
		default:
			$error_txt = "기타 에러";
	}
	
	if ($transactionId == null){
		$transactionId = "";
	}
	
	header("HTTP/1.0 400 Bad Request");
	$res = array( "isSuccessful"=> false,"exceptionCode" => $code, "exceptionMessage" => $error_txt ,  "transactionId" =>$transactionId );
	echo json_encode($res);
	exit;
	
}

?>
