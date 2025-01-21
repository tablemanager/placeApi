<?php

 /*
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

$logsql = "insert cmsdb.extapi_log set  apinm='원더투어',tran_id='$tranid', ip='".get_ip()."', logdate= now(), apimethod='$apimethod', querystr='".$para."', header='".json_encode($apiheader)."', body='".$jsonreq."'";
 $conn_rds->query($logsql);

// 인증 정보 조회
$auth = $apiheader['Authorization'];
$authqry = "SELECT * FROM spadb.extapi_config WHERE authkey = '$auth' limit 1";
$authres = $conn_cms3->query($authqry);
$authrow = $authres->fetch_object();

$aclmode = $authrow->aclmode;

if($aclmode == "IP"){
// ACL 확인
    if(!in_array(get_ip(),json_decode($authrow->accessip,false))){
        header("HTTP/1.0 401 Unauthorized");
        $res = array("Result"=>"4100","Msg"=>"아이피 인증 오류 : ".get_ip());
        echo json_encode($res);
        exit;
    }
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

if(get_ip() =="106.254.252.100"){
//echo $itemreq[0];
}

// REST Method 분기
switch($apimethod){
    case 'GET':
        // 주문 조회
            switch($itemreq[0]){
                case 'GoodsList':
                case 'goodslist':
                     get_deallist($authrow->cp_code);
                break;
                case 'PriceInfo':
                case 'priceinfo':
                     //get_price($ch_id,$itemreq);
                    get_ivinfo($ch_id,$itemreq);
                break;
                default:
                     get_extorder_info($ch_id,$itemreq);    
            }

    break;
    case 'POST':
        // 주문 등록
        set_extorder_insert($cpcode,$ch_id,$itemreq,$jsonreq);
    break;
    case 'PATCH':
        // 주문 취소, 변경
        switch($itemreq[0]){
            case 'chorderno':
            case 'Chorderno':
                // 주문 취소
                set_extorder_update($ch_id,$itemreq,$jsonreq);

            break;
            case 'sendeticket':
            case 'Sendeticket':
               // 문자 재전송
                resend_ticket($ch_id,$itemreq,$jsonreq);
            break;
            default:
            header("HTTP/1.0 400 Bad Request");
            $res = array("Result"=>"4000","Msg"=>"파라미터 오류");
            echo json_encode($res);
        }


    break;
    default:
        header("HTTP/1.0 400 Bad Request");
        $res = array("Result"=>"4000","Msg"=>"파라미터 오류");
        echo json_encode($res);
}


function get_ivinfo($ch_id,$itemreq){
            global $conn_cms;
            global $conn_rds;
            $itemcode = $itemreq[1];
            
            $itemsrow = $conn_rds->query("select * from cmsdb.WDT_PRODUCTS where prodSeq = '$itemcode'")->fetch_object();
            
            $itemarr = json_decode($itemsrow->ticketsCodes);
        if(count($itemarr) > 0){
            foreach($itemarr as $itemid){
                $ivarr = array();
                $priceres = $conn_cms->query("select * from CMSDB.CMS_PRICES where price_itemid = '$itemid' and price_state= 'Y' order by price_date desc limit 1");
                $pcnt = $priceres->num_rows;

                 if($pcnt > 0){
                    while($prow=$priceres->fetch_object()){

                        $ivarr[] = array(
                                        "DATE" => $prow->price_date,
                                        "STATE" => "Y",
                                        "QTY" => "99999",
                                        "PRICE" => $prow->price_sale);
                    }


                    $pricearr[] = array(
                                       "ITEMCODE" => $itemid,
                                        "IV" => $ivarr);            
		        }
            
            }
                    $result = array("Code" => 1000,
                                    "Msg" => "조회성공",
                                    "DT_REG" => "20181001000000",
                                    "DT_MOD" => "20181201192000",
                                    "EXPDATE" => $prow->price_date,
                                    "PRICEINFO" => $pricearr);

            }else{
                $pricearr[] = null;
                $result = array("Code" => 4000,
                                "Msg" => "조회가능한 데이터가 없습니다.",                  
                                "DT_REG" => null,
                                "DT_MOD" => null,
                                "EXPDATE" => null,
                                "PRICEINFO" => null);                      
            }





            header("HTTP/1.0 200 OK");
            echo json_encode($result);      
}

function resend_ticket($ch_id,$itemreq,$jsonreq){
    global $conn_cms3;
    
    $ch_orderno = $itemreq[1];
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

   if($urow->id){

        $mmscnt = $urow->optsu1;

        if($mmscnt < 3){
            $mcnt = $mmscnt+1;
            $sendsql = "update spadb.ordermts set smsgu='N', optsu1='$mcnt' where id = '".$urow->id."' limit 1";
            $conn_cms3->query($sendsql);

            $result = array("Code" => "1000",
                            "Msg" => "문자 재발송 성공",
                            "Result" => $order);

            header("HTTP/1.0 200 OK");
            echo json_encode($result);  

        }else{

            $result = array("Code" => "5000",
                            "Msg" => "문자 재발송 횟수 초과",
                            "Result" => $order);

            header("HTTP/1.0 200 OK");
            echo json_encode($result);          

        }

   }else{
        $result = array("Code" => "4000",
                        "Msg" => "발송 가능 주문이 없습니다.",
                        "Result" => $order);

        header("HTTP/1.0 404 OK");
        echo json_encode($result);     
   }
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
            case 'CB': // 에버랜드 쿠폰 조회                
            case 'EL': // 에버랜드 쿠폰 조회                
            case 'S0': // 에버랜드 쿠폰 조회                
                $useflag = getuseev(str_replace(";", "",$urow->barcode_no));
                $usedate = $urow->usegu_at;

            break;
            case '04': // 롯데워터 쿠폰 조회

                $res = explode(";",getusewp($urow->barcode_no));

                $useflag = $res[1];
                $usedate = $urow->usegu_at;

            break;
            default:
                $useflag = $urow->usegu;
                $usedate = $urow->usegu_at;
        }


        $order[] = array("orderno" => $urow->orderno, // 주문번호 
                         "ch_orderno" => $urow->ch_orderno, // 채널 주문번호
                         "cus_nm" => $urow->usernm, // 고객명
                         "cus_hp" => $urow->dhp, // 고객핸드폰
                         "state" => $urow->state, // 주문상태
                         "itemcode" => $urow->itemmt_id, // 상품코드
                         "qty" => $urow->man1, // 주문 수량
                         "expdate" => $urow->usedate, // 유효기간(이용일)
                         "use" => $useflag, // 이용구분 
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
    global $conn_rds;
    global $cpname;
    global $tranid;
    $itemcode = $itemreq[1];
    $orderno = date("Ymd")."_".genRandomStr(12);


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
        $itemsql = "SELECT * from pcmsdb.items_ext where channel = '$cpcode' and pcmsitem_id = '$itemcode' and useyn='Y' order by usedate desc limit 1";

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
            $msg = "판매채널 연동 누락({$cpname}/{$cpcode}) - $itemcode";
            send_report("01090901678",$msg);
            send_report("01067934084",$msg); // tony

            exit;

        }else{
            $sellcode = $itemurow->gu;

			if(!$info->Qty) $info->Qty = 1;

            if(strlen($info->userHp) < 2){
                $info->userHp = $info->userHP;
            }

            if($sellcode){
                $n = 1;
                $cpno = get_extcoupon("EXT",$sellcode,$n,$orderno,$info->userName,$info->userHp);            
            }else{
                $cpno = null;
            }

       		$extsql = "insert pcmsdb.ordermts_ext set
						order_num = '".$info->orderNo."',
						sell_code = '".$itemcode."',
						buy_opt = '".$info->orderDesc."',
						buy_name = '".$info->userName."',
						buy_hp = '".$info->userHp."',
						buy_count = '".$info->Qty."',
						channel = '".$cpcode."',
						postcode =  '".$info->postCode."',
						address =  '".$info->addr1."',
						address2 =  '".$info->addr2."',
						ip = '".get_ip()."',
						regdate = now()";

            $conn_cms->query($extsql);

           
            $extsql2 = "insert cmsdb.pcms_extorder set 
                            date_order = now(),
                            ch_orderno = '".$info->orderNo."',
                        	ch_code = '".$cpcode."',
                        	order_itemcode = '".$itemcode."',
                            order_state = 'N'";
            $conn_rds->query($extsql2);

         // 상품 정보     
         $res = order_cms($orderno,$info->orderNo, $itemcode,$info->Qty,$itemurow->usedate,$state,$info->userName,$info->userHp,$cpno,$info->orderDesc);

         $order[]= array("OrderNo" => $res,
                         "CouponNo" => $cpno);
                                
         $result = array("Code" => "1000",
                         "Msg" => "성공",
                         "Result" => $order);

         header("HTTP/1.0 200");
         echo $rts = json_encode($result);    
         $logsql2 = "update cmsdb.extapi_log set apiresult = '".addslashes($rts)."' where tran_id='$tranid' limit 1";
         $conn_rds->query($logsql2);
         exit;
         
        }


    }
}

// cms 주문입력
function order_cms($orderno,$chorderno,$itemcode,$qty,$expdate,$state,$usernm,$userhp,$couponno,$orderdesc) {
    global $conn_cms;
    global $conn_cms3;

    global $cpname;
    global $ch_id;
    if($orderno) $orderno = date("Ymd")."_".genRandomStr(12);
    $mdate = date("Y-m-d");
    $iteminfo = get_iteminfo($itemcode,$expdate);    
   
    $man1 = $qty;
    
    if(!$couponno) $couponno = "0";

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
		barcode_no = '".$couponno."',		
		state	=	'예약완료'
		";

         $conn_cms3->query($mode.$where);
         
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

    $ch_orderno = $itemreq[1];

    $usql = "SELECT
                *,
                AES_DECRYPT(UNHEX(hp),'Wow1daY') as dhp 
             FROM 
                spadb.ordermts 
             WHERE 
                 ch_orderno = '$ch_orderno' 
             AND ch_id = '$grmt_id' 
             AND usegu = '2' 
             LIMIT 1";

    $ures = $conn_cms3->query($usql);
    $urow = $ures->fetch_object();
    
    if($urow->id){

        // 쿠폰번호 사용확인
        $cpusql = "select * from spadb.ordermts_coupons where order_id = '".$urow->id."' ";
        $cpures = $conn_cms3->query($cpusql);
        $cpflag = "N";

        while($cpurow = $cpures->fetch_object()){
            $cpno = $cpurow->couponno;

            $cptype = substr($cpno,0,2);


            
            switch($cptype){
                case 'CB': // 에버랜드 쿠폰 조회                
                case 'EL': // 에버랜드 쿠폰 조회                
                case 'S0': // 에버랜드 쿠폰 조회                
                    $useflag = getuseev($cpno);
                    $usedate = $urow->usegu_at;

                break;
                case '04': // 롯데워터 쿠폰 조회

                    $res = explode(";",getusewp($cpno));

                    $useflag = $res[1];
                    $usedate = $urow->usegu_at;

                break;
                default:
                    $useflag = $urow->usegu;
                    $usedate = $urow->usegu_at;
            }


        }

        if( $useflag != "1"){

            $order[]= array("OrderNo" => $urow->ch_orderno,
                        "CouponNo" => $urow->barcode_no);
    
             $result = array("Code" => "1000",
                             "Msg" => "취소 성공",
                             "Result" => $order);

            $cusql = "update spadb.ordermts set state='취소',canceldate = now() where id = '".$urow->id."' limit 1";
            $conn_cms3->query($cusql);

             header("HTTP/1.0 200");
             echo json_encode($result);    
             exit;
        }else{

             $order[]= array("OrderNo" => null,
                             "CouponNo" => null);
                                    
             $result = array("Code" => "4000",
                             "Msg" => "취소할수 없거나 변경이 불가능한 주문",
                             "Result" => $order);

             header("HTTP/1.0 400");
             echo json_encode($result);    
             exit;
        
        }
    
    }else{

             $order[]= array("OrderNo" => null,
                             "CouponNo" => null);
                                    
             $result = array("Code" => "4000",
                             "Msg" => "취소할수 없거나 변경이 불가능한 주문",
                             "Result" => $order);

             header("HTTP/1.0 400");
             echo json_encode($result);    
             exit;
    
    }
    

}

// 클라이언트 아아피
function get_ip(){

    $res = explode(",",$_SERVER["HTTP_X_FORWARDED_FOR"]);
    return trim($res[0]);
}

// 외부 주문 테이블 조회
function chk_chorderno($cpcode,$orderno){
    global $conn_cms;
    global $conn_rds;

    $sql0 = "SELECT * from cmsdb.pcms_extorder where ch_code = '$cpcode' and ch_orderno = '$orderno' limit 1";
    $res0 = $conn_rds->query($sql0);
    $row0 = $res0->fetch_object();

    if($row0->idx){

        return true;

    }else{

        $sql = "SELECT * from pcmsdb.ordermts_ext where channel = '$cpcode' and order_num = '$orderno' limit 1";
        $res = $conn_cms->query($sql);
        $row = $res->fetch_object();
        
        if($row->idx){
            return true;
        }else{
            return false;
        }


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
        return $data;
    }else{
        return false;
    }
}

// 딜리스트
function get_deallist($cpcode){
    global $conn_cms;
    $mdate = date("Y-m-d");

    $glist = array();
    $gsql = "select pcmsitem_id,nm, usedate from pcmsdb.items_ext where channel = '$cpcode' and useyn= 'Y' and usedate >= '$mdate' order by usedate desc";
    $gres = $conn_cms->query($gsql);
    //$grow = $gres->fetch_all();
    while($grow = $gres->fetch_object()){

        $res = get_iteminfo($grow->pcmsitem_id,$grow->usedate);

        $glistp[] = array(
            "FACNM" => $res['grnm'],
            "ITEMCODE" => $grow->pcmsitem_id,
            "ITEMNM" => $grow->nm,
            "EXPDATE"  => $grow->usedate,
            "DESC"  => null
        );
    }
    echo json_encode($glistp);
}

// 에버랜드 사용조회
function getuseev($no) {

    $curl = curl_init();
    $url = "https://gateway.sparo.cc/everland/sync/".$no;
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
            $useflag = "1"; // 확인할수 없으면 사용으로 본다.                 
    }

    return $useflag;

}


function get_pmcoupon($orderno,$expdate,$usernm,$usehp){


}

function get_extcoupon($chcode, $sellcode,$n,$orderno,$usernm,$usehp) {

        $reqinfo = array(
            "CHCODE" => $chcode,
            "ORDERNO" => $orderno,
            "SELLCODE" => $sellcode,
            "UNIT" => $n,
            "USERNM" => $usernm,
            "USERHP" => $usehp
        );

        $post = json_encode($reqinfo);

if(get_ip() =="106.254.252.100"){
//    echo $post;
}
        $apiurl="https://gateway.sparo.cc/internal/apicouponno/".$orderno;
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

/*
header("HTTP/1.0 401 Unauthorized");
header("HTTP/1.0 400 Bad Request");
header("HTTP/1.0 200 OK");
header("HTTP/1.0 500 Internal Server Error");

4000	필수 파라미터 누락 및 Validation 실패 시 각 상황에 따른 메시지를 전달 함	400 Bad Request
4001	필수 해더 검증에 실패하였을 경우	412 Precondition Failed
4002	RestKey 인증에 실패 하였을 경우	401 Unauthorized

9005	검색된 리소스(데이터)가 없을경우	404 Not Found

5000	내부 시스템에서 오류가 발생하였습니다.	500 Internal Server Error



S|UC|사용완료
S|UR|사용완료요청

S|PC|구매취소

S|PS|구매완료
S|CR|회수완료
*/
?>
