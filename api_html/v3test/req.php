<?php

/*
 *
 * CMS 채널 주문 인터페이스 V3
 *
 * 작성자 : 이정진
 * 작성일 : 2021-10-22
 * 수정 : Jason,  ila
 * 수정작업기간 2022-02-11 ~ 03-22
 *
 */
//error_reporting(0);

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
require_once ('/home/sparo.cc/Library/Ordermts.php');

$ordermts = new Ordermts();
header("Content-type:application/json");

$conn_rds->query("set names utf8");
$conn_cms->query("set names utf8");

$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

// 파라미터
$itemreq = explode("/",$para);
$jsonreq = trim(file_get_contents('php://input'));
$info = json_decode($jsonreq);

// 인터페이스 로그
list($microtime,$timestamp) = explode(' ',microtime());
$tranid = $timestamp . substr($microtime, 2, 3);
//$logsql = "insert cmsdb.extapi_log set apinm='V3_챗봇톡',tran_id='$tranid', ip='".get_ip()."', logdate= now(), apimethod='$apimethod', querystr='".$para."', header='".json_encode($apiheader)."', body='".$jsonreq."'";
//$conn_rds->query($logsql);

$today = date("Ymd");
$now = date("Y-m-d H:i:s");


if(get_ip() !="106.254.252.100"){
    // exit;
}
if(get_ip() =="106.254.252.100"){
	// echo $itemreq[0];
}

// 인증 정보 조회
$auth = $apiheader['Authorization'];

if(!$auth) $auth = $apiheader['authorization'];

$authqry = "SELECT * FROM spadb.extapi_config WHERE authkey = '$auth' and state = 'Y' limit 1";
$authres = $conn_cms3->query($authqry);
$authrow = $authres->fetch_object();

$aclmode = $authrow->aclmode;

if($aclmode == "IP"){
// ACL 확인
  //  if(!in_array(get_ip(),json_decode($authrow->accessip,false))){
        //header("HTTP/1.0 401 Unauthorized");
        //$res = array("Result"=>"4100","Msg"=>"아이피 인증 오류 : ".get_ip());
//        echo json_encode($res);
  //      exit;
//    }
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
// 시설번호 가져오기
$deal_qry = "select fac_id from cmsdb.channel_deals where use_edate >= '$today'";
$deal_res = $conn_rds->query($deal_qry);
$rcnt = $deal_res->num_rows;
//print_r("\n".$rcnt." 개 search \n");

$fac_id = array();
while($deal_row = $deal_res->fetch_object()){
		$orderid = $deal_row->fac_id;
    array_push($fac_id,$orderid);
}



// REST Method 분기
switch($apimethod){
    case 'GET':
	      //get_predict($itemreq[1]);
        switch($itemreq[0]){
            case 'deallists':
                // 시설별 상품목록 조회
                $res = get_deallists($itemreq[1]);
                break;
            case 'orderno':
                // 판매처 주문조회(ch_orderno)
                $res = get_order($itemreq[1]);
                break;
            case 'user':
                // 판매처 구매내역 조회
                $res = get_orders($itemreq[1]);
                break;
            case 'faccd':
                // 쿠폰 정보 조회 + 쿠폰 내역(목록) 조회
                $res = get_couponinfo($itemreq[1],$itemreq[2],$itemreq[3]);
                break;
      			case 'facinfo':
      				// 시설정보 조회
      				$res = get_facinfo($itemreq[1]);
      				break;
                  default:
                      header("HTTP/1.0 400 Bad Request");
                      $res = array("Result"=>"4000","Msg"=>"파라미터 오류".$itemreq[0]);
                      echo json_encode($res);
        }
    break;
    case 'POST':
        switch($itemreq[0]){
            case 'itemcode':
                // 주문
                // 주문등록시 body로 들어오는 값에 대한 validation check - Jason 22.02.24
                if($info->orderSeq == "" || $info->orderNo == "" || $info->userName == "" || $info->userHp == ""){
                    header("HTTP/1.0 400 Bad Request");
                    $res = array("Result"=>"4010","Msg"=>"파라미터 오류/필수파라미터 누락");
                    echo json_encode($res);
                    break;
                }
                if(!is_numeric($info->orderSeq)){
                    header("HTTP/1.0 400 Bad Request");
                    $res = array("Result"=>"4011","Msg"=>"파라미터 오류/orderSeq는 숫자만 가능합니다.");
                    echo json_encode($res);
                    break;
                }
                if(strpos($info->orderNo, "-")){
                    header("HTTP/1.0 400 Bad Request");
                    $res = array("Result"=>"4012","Msg"=>"파라미터 오류/orderNo에 대시('-')를 쓸 수 없습니다.");
                    echo json_encode($res);
                    break;
                }
                if(strlen($info->userName) > 40){
                    header("HTTP/1.0 400 Bad Request");
                    $res = array("Result"=>"4013","Msg"=>"파라미터 오류/이름을 더 짧게 입력해주세요.");
                    echo json_encode($res);
                    break;
                }
                if(!check_phone($info->userHp)){
                    header("HTTP/1.0 400 Bad Request");
                    $res = array("Result"=>"4014","Msg"=>"파라미터 오류/유효하지 않은 핸드폰번호입니다.");
                    echo json_encode($res);
                    break;
                }
                if(!isValidDate($info->expDate)){
                    header("HTTP/1.0 400 Bad Request");
                    $res = array("Result"=>"4015","Msg"=>"파라미터 오류/유효하지 않은 날짜값입니다.");
                    echo json_encode($res);
                    break;
                }
                $orderinfo = array(
                    "orderNo"=>$info->orderNo."-".$info->orderSeq,
                    "orderSeq"=>$info->orderSeq,
                    "userName"=>$info->userName,
                    "userHp"=>$info->userHp,
                    "expDate"=>$info->expDate,
                    "couponNo" => genRandomNum(16)
                );
                set_order($itemreq[1],$orderinfo);
                break;
            default:
                header("HTTP/1.0 400 Bad Request");
                $res = array("Result"=>"4000","Msg"=>"파라미터 오류");
                echo json_encode($res);
        }
    break;
    case 'PATCH':
		switch($itemreq[0]){
            case 'orderno':
                // 주문 취소
                if($itemreq[2] == "seq"){
                    set_cancel($itemreq[1],$itemreq[3]);
                } else {
                    header("HTTP/1.0 400 Bad Request");
                    $res = array("Result"=>"4000","Msg"=>"파라미터 오류");
                    echo json_encode($res);
                }
				    break;
            case 'chorderno': // 타채널 주문건에 대한 취소접수
                // 챗봇톡으로 들어온 주문이 아닐 경우(타채널 주문건) state를 취소가 아닌 '취소접수'로 변경
                $msg = "/".$now." 챗봇톡 V3 api 취소접수 /";
                $cancelRequestResult = $ordermts->updateStateCancelRequestByChOrderno($itemreq[1],$msg);
                if($cancelRequestResult){
                    $resultArr = array("orderNo"=>$itemreq[1]);
                    $result = array($resultArr);
                    $res = [
                        "Code"=>1000,
                        "Msg"=>"취소접수 성공",
                        "Result"=> $result
                    ];
                    header("HTTP/1.0 200");
                } else {
                    header("HTTP/1.0 400 Bad Request");
                    $res = array("Result"=>"4001","Msg"=>"잘못된 요청(주문상태) - 없는 채널주문번호 또는 기사용건 또는 기취소건");
                }
                echo json_encode($res);
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



// 판매처 주문 조회
function get_order($orderno){
	  global $conn_rds;
    global $conn_cms;
	  global $conn_cms3;

	$select = "SELECT id, orderno, ch_orderno, usernm, state, usedate, itemmt_id, itemnm, usegu, usegu_at,
				created, mdate, canceldate, barcode_no, couponno, AES_DECRYPT(UNHEX(hp),'Wow1daY') as dhp
			FROM spadb.ordermts ";
    // 카카오챗봇(3851), 테이블매니저(3864) 채널 판매건에 한해서 검색
	$where = " WHERE ch_id IN('3851','3864')
                AND ch_orderno LIKE '$orderno%'
			LIMIT 100";

    $qry = $select.$where;

        // limit 몇으로 할지는 논의 필요(날짜도 얼마까지 할 건지 논의 필요)
        // result에 결과 하나뿐 아니라 여러개 담아야함.

    $res = $conn_cms3->query($qry);
    // var_dump($row);
    $orders = array();
    while($row = $res->fetch_object()){

        $splited_orderno = explode("-",$row->ch_orderno);
        $chatbot_orderno = $splited_orderno[0];
        $order_seq = $splited_orderno[1];
        if(empty($splited_orderno[1])){
          $order_seq = $row->id; //채널주문번호에 seq 번호가 없으면 주문의 id값 보냄
        }else{
          $order_seq = $splited_orderno[1];
        }
        $useState = $row->usegu == '1' ? 'Y' : 'N'; // usegu가 1이면 Y, 1이 아니면(2면) N
        $order = [
      			"orderNo"=>$chatbot_orderno,
      			"orderSeq"=>$order_seq,
      			"userName"=>$row->usernm,
      			"userHp"=>$hp_withdash,
      			"orderState"=>$row->state,
      			"itemCd"=>$row->itemmt_id,
      			"expDate"=>$row->usedate,
      			"useState"=>$useState,
      			"usedate"=>$row->usegu_at,
      			"canceldate"=>$row->canceldate,
      			"couponno"=>$row->barcode_no
        ];
        array_push($orders, $order);
    }

    $result = [
        "Code"=>1000,
        "Msg"=>"성공",
        "Result"=> $orders
    ];

	header("HTTP/1.0 200");
	echo json_encode($result);
}


// 판매처 구매목록 조회
function get_orders($userhp){
	global $conn_rds;
	global $conn_cms;
	global $conn_cms3;


	$hp_onlynum = $userhp;
	$hp_withdash = hpWithDash($userhp);
	$lasthp = substr($userhp,-4);
	$timestamp = strtotime("-3 months");
	$last3months = date("Y-m-d", $timestamp);

	$where = " WHERE mdate > '$last3months'
                AND ch_id IN('3851','3864')
				AND lasthp = '$lasthp'
				AND (hp = HEX(AES_ENCRYPT('$hp_onlynum','Wow1daY')) OR hp = HEX(AES_ENCRYPT('$hp_withdash','Wow1daY')))
			ORDER BY mdate DESC
			LIMIT 50";


	$select = "SELECT id, orderno, ch_orderno, usernm, state, usedate, itemmt_id, itemnm, usegu, usegu_at,
            created, mdate, canceldate, barcode_no, couponno, AES_DECRYPT(UNHEX(hp),'Wow1daY') as dhp
        	FROM spadb.ordermts ";
    $qry = $select.$where;
        // limit 몇으로 할지는 논의 필요(날짜도 얼마까지 할 건지 논의 필요)
        // result에 결과 하나뿐 아니라 여러개 담아야함.

    $res = $conn_cms3->query($qry);
    // var_dump($row);
    $orders = array();
    while($row = $res->fetch_object()){
        // $itemqry = "SELECT * FROM CMSDB.CMS_ITEMS WHERE item_id = '$row->itemmt_id' LIMIT 1";
        // $itemres = $conn_cms->query($itemqry);
        // $itemrow = $itemres->fetch_object();
        // var_dump($itemrow);
        $splited_orderno = explode("-",$row->ch_orderno);
        $chatbot_orderno = $splited_orderno[0];
        $order_seq = $splited_orderno[1];
        if(empty($splited_orderno[1])){
          //$order_seq = $couponrow->couponno;
          //$order_seq = substr($order_seq, -5);
          $order_seq = $row->id;
          //$order_seq = '1';

        }else{
          $order_seq = $splited_orderno[1];
        }
        $useState = $row->usegu == '1' ? 'Y' : 'N'; // usegu가 1이면 Y, 1이 아니면(2면) N
        $order = [
      			"orderNo"=>$chatbot_orderno,
      			"orderSeq"=>$order_seq,
      			"userName"=>$row->usernm,
      			"userHp"=>$hp_withdash,
      			"orderState"=>$row->state,
      			"itemCd"=>$row->itemmt_id,
      			"expDate"=>$row->usedate,
      			"useState"=>$useState,
      			"usedate"=>$row->usegu_at,
      			"canceldate"=>$row->canceldate,
      			"couponno"=>$row->barcode_no
        ];
        array_push($orders, $order);
    }
    // var_dump($coupons);

    $result = [
        "Code"=>1000,
        "Msg"=>"성공",
        "Result"=> $orders
    ];


	header("HTTP/1.0 200");
	echo json_encode($result);
}




// 쿠폰 정보 조회, 쿠폰 내역 조회
function get_couponinfo($_faccd,$_mode,$_val){
    global $conn_rds;
    global $conn_cms;
	  global $conn_cms3;
    global $fac_id;
    $strIndex = in_array($_faccd, $fac_id);
    // if($_faccd != '4249' && $_faccd != '2781' && $_faccd != '9' && $_faccd != '76'){
    if($strIndex == ""){
        $result = [
            "Code"=>9999,
            "Msg"=>"현재 제공되는 시설이 아닙니다."
        ];
        header("HTTP/1.0 200");
        echo json_encode($result);
    }else{
      if($_mode == 'userhp'){
          $hp_onlynum = $_val;
          $hp_withdash = hpWithDash($_val);
          $lasthp = substr($_val,-4);
          $timestamp = strtotime("-3 month"); //유효기간 이후 최대 3개월 조회 가능 - 22.4.19 ila
          $last3months = date("Y-m-d", $timestamp);

          $where = " WHERE jpmt_id = '$_faccd'
                      AND usedate > '$last3months'
                      AND lasthp = '$lasthp'

                      AND (hp = HEX(AES_ENCRYPT('$hp_onlynum','Wow1daY')) OR hp = HEX(AES_ENCRYPT('$hp_withdash','Wow1daY')))
                  ORDER BY created DESC
                  LIMIT 100";
      } elseif($_mode == 'coupons'){
          $where = " WHERE jpmt_id = '$_faccd'
                      AND barcode_no = '$_val'
                  LIMIT 1";
      }
    }



    $select = "SELECT id, orderno, ch_orderno, usernm, amt, state, usedate, itemmt_id, itemnm, usegu, usegu_at,
            created, mdate, canceldate, barcode_no, couponno, AES_DECRYPT(UNHEX(hp),'Wow1daY') as dhp
        FROM spadb.ordermts ";
    $qry = $select.$where;
        // limit 몇으로 할지는 논의 필요(날짜도 얼마까지 할 건지 논의 필요)
        // result에 결과 하나뿐 아니라 여러개 담아야함.

    $res = $conn_cms3->query($qry);

    // var_dump($row);
    $coupons = array();
    while($row = $res->fetch_object()){
		    $couponqry = "SELECT * FROM spadb.ordermts_coupons WHERE order_id = '$row->id'";
		     $couponres = $conn_cms3->query($couponqry);
		while($couponrow = $couponres->fetch_object()){
			$itemqry = "SELECT * FROM CMSDB.CMS_ITEMS WHERE item_id = '$row->itemmt_id' LIMIT 1";
			$itemres = $conn_cms->query($itemqry);
			$itemrow = $itemres->fetch_object();
			// var_dump($itemrow);

            $splited_orderno = explode("-",$row->ch_orderno);
            $chatbot_orderno = $splited_orderno[0];
            $order_seq = $splited_orderno[1];
            // order_seq값이 테이블매니저에서 필수값으로 넣어달라하고, 유니크값으로 보내달라고 해서 id값을 붙여서 보내줌
            if(empty($splited_orderno[1])){
              //$order_seq = $couponrow->couponno;
              //$order_seq = substr($order_seq, -5);
              $order_seq = $row->id;
              //$order_seq = '1';

            }else{
              $order_seq = $splited_orderno[1];
            }
            //$useState = $row->usegu == '1' ? 'Y' : 'N'; // usegu가 1이면 Y, 1이 아니면(2면) N
            $usedate = $couponrow->dt_use ?? $row->usegu_at; // 쿠폰테이블 dt_use값 있으면 그거 넣고 없으면 usegu_at값 넣기
            $candate = $couponrow->dt_cancel ?? $row->canceldate;
            $state=$row->state;
            //print_r($state=$row->state);
            //쿠폰status가 제대로 출력되지 않는 현상으로 couponState값 변경 - 22.07.01 ila
            if($state === '취소'){
                $useState = 'C';
            }elseif($state === '예약완료'){
                $useState = $row->usegu == '1' ? 'Y' : 'N';
            }elseif($state === '취소접수'){
                $useState = 'C';
            }


			$coupon = [
                "orderNo"=>$chatbot_orderno,
                "orderSeq"=>$order_seq,
        				"couponNo"=>$couponrow->couponno,
        				"couponNm"=>$row->itemnm,
        				"buyDate"=>$row->created,
        				"expDate"=>$row->usedate,
                "useDate"=>$usedate,
				        "cancelDate"=>$candate,
                //"couponState"=>$couponrow->state,
                "couponState"=>$useState,
				        "itemType"=>$itemrow->item_type,
                "price"=>$row->amt
			];
			array_push($coupons, $coupon);
		}
    }
    // var_dump($coupons);

    $result = [
        "Code"=>1000,
        "Msg"=>"성공",
        "Result"=> $coupons
    ];

	header("HTTP/1.0 200");
	echo json_encode($result);
}




function get_deallists($_faccd){
		global $conn_rds;
    global $conn_cms;
    global $now;
		global $today;
    global $fac_id;
    //print_r($fac_id);
    $strIndex = in_array($_faccd, $fac_id);
    //print_r($strIndex);
    // if($_faccd != '4249' && $_faccd != '2781' && $_faccd != '9' && $_faccd != '76'){
    if($strIndex == ""){
        $result = [
            "Code"=>9999,
            "Msg"=>"현재 제공되는 시설이 아닙니다."
        ];
        header("HTTP/1.0 200");
        echo json_encode($result);
    } else {
        $dealqry = "select * from cmsdb.channel_deals where fac_id = ? and use_edate > '$today'";
        $dealstmt = $conn_rds->prepare($dealqry);
        $dealstmt->bind_param("s", $_faccd); // 들어갈 파라미터가 문자열이란 뜻에서 s
        $dealstmt->execute();
        $dealres = $dealstmt->get_result();
        $dealrow = $dealres->fetch_object();
        $infojson = json_decode($dealrow->deal_info);

        if($infojson->dealnm == null){
            $result = [
                "Code"=>9999,
                "Msg"=>"현재 판매 가능한 상품이 없습니다."
            ];
            header("HTTP/1.0 200");
            echo json_encode($result);
        } else {
          //상품명
          $optnms = explode("_",$infojson->opt_nm);
          //상품가격
          $optprices = explode("_",$infojson->opt_price);
          //상품코드
          $optitemids = explode("_",$infojson->opt_cmcode);
          //판매가능티켓수
          $optqty = explode("_",$infojson->opt_qty);
          //정상가
          $optNormalPrice = [];
          $optSalePrice = [];

          foreach($optitemids as $optitem){
              $expDatefix = [];
              $itemqry = "select * from CMSDB.CMS_PRICES where price_itemid = ? ORDER BY price_date DESC LIMIT 1";
              $itemstmt = $conn_cms->prepare($itemqry);
              // var_dump($optitem);
              $itemstmt->bind_param("s", $optitem); // 들어갈 파라미터가 문자열이란 뜻에서 s
              $itemstmt->execute();
              $itemres = $itemstmt->get_result();
              $itemrow = $itemres->fetch_object();
              array_push($optNormalPrice, $itemrow->price_normal);
              array_push($optSalePrice, $itemrow->price_sale);

              // 날짜지정권일 경우 예약 가능한 날짜 가져오기 추가  - 22.08.10 ila
              $itemDateqry = "select price_date from CMSDB.CMS_PRICES where price_itemid = '$optitem'";
              $itemDateres = $conn_cms->query($itemDateqry);


              while($itemDaterow = $itemDateres->fetch_object()){
                $price_date = $itemDaterow->price_date;
                array_push($expDatefix, $price_date);
              }
          }


          $optitems = array();
          for($i=0;$i<count($optnms);$i++){
              // 비즈와 상의 정상가보다 판매가가 더 높게 설정되었을 경우
              // if($optNormalPrice[$i] < $infojson->price+$optprices[$i]){
              //     $price = $optNormalPrice[$i];
              // } else {
              //     $price = $infojson->price+$optprices[$i];
              // }
              $iteminfo = getItemInfo($optitemids[$i]);


              // 날짜지정권일 경우 예약 가능한 날짜 가져오기 추가  - 22.08.10 ila
              if($iteminfo->item_type == "S"){
                $expDate = $expDatefix;
              }else{
                $expDate = array(substr($iteminfo->item_edate,0,10));
              }



              if($iteminfo->item_nm == null) {

              } else {
                $expDate = substr($iteminfo->item_edate,0,10);
                //"itemNm" => $optnms[$i],
                $optitems[]= array(
                    "itemNm" => $iteminfo->item_nm,
                    "itemCd" => $optitemids[$i],
                    "itemType" => $iteminfo->item_type,
                    "itemDesc" => null,
                    "maxBookingCount" => $optqty[$i],
                    "salePrice" => $optSalePrice[$i],
                    "normalPrice" => $optNormalPrice[$i],
                    "itemSchedule" => $expDate// 상품관리의 판매 종료일(유효기간권) 또는 예약가능한 날짜 (날짜지정권)
                );
              }
          }
          $result = [
              "Code"=>1000,
              "Msg"=>"성공",
              "Result"=> [
                  [
                      "dealCd"=>$dealrow->ch_code."-".$dealrow->fac_id."-".$dealrow->id,
                      "dealNm"=>$infojson->dealnm,
                      "expDate"=>$infojson->use_edate, // 딜 관리의 사용 종료일
                      "mainImageUrl" => $infojson->mainimg,
                      "contentUrl" => $infojson->maincontents,
                      "desc" => $infojson->fac_detail,
                      "itemLists" => $optitems,
                      "dealInfoJson" => [],
                      "dealExtraJson" => []
                  ]
              ]
          ];

          header("HTTP/1.0 200");
          echo json_encode($result);
        }

    }

}



function get_facinfo($_faccd){
		global $conn_rds;
    global $now;
		global $today;
    global $fac_id;
    $strIndex = in_array($_faccd, $fac_id);
    // if($_faccd != '4249' && $_faccd != '2781' && $_faccd != '9' && $_faccd != '76'){
    if($strIndex == ""){
        $result = [
            "Code"=>9999,
            "Msg"=>"현재 제공되는 시설이 아닙니다."
        ];
        header("HTTP/1.0 200");
        echo json_encode($result);
    } else {
        $dealqry = "select * from cmsdb.channel_deals where fac_id = ?";
        $dealstmt = $conn_rds->prepare($dealqry);
        $dealstmt->bind_param("s", $_faccd); // 들어갈 파라미터가 문자열이란 뜻에서 s
        $dealstmt->execute();
        $dealres = $dealstmt->get_result();
        $dealrow = $dealres->fetch_object();
        $infojson = $dealrow->deal_info;
        $facinfo = json_decode($infojson);

        // 가져온 데이터에 위경도값이 없을 경우 네이버맵api를 호출하여 위경도값을 받아와서 저장
        if(!$facinfo->fac_lat){
            $geoinfo = json_decode(get_geocode($facinfo->fac_addr));
            $geoinfo = $geoinfo->addresses[0];
            $facinfo->fac_lat = $geoinfo->y;
            $facinfo->fac_lon = $geoinfo->x;
            $newInfojson = json_encode($facinfo,JSON_UNESCAPED_UNICODE);
            $geoUpQry = "UPDATE cmsdb.channel_deals SET deal_info = ? WHERE id = ?";
            $stmt = $conn_rds->prepare($geoUpQry);
            $stmt->bind_param("ss", $newInfojson, $dealrow->id);// ?에 들어갈 파라미터가 문자열, 문자열이란 뜻에서 ss
            $stmt->execute();
        }

        $result = [
            "Code"=>1000,
            "Msg"=>"성공",
            "Result"=> [
                [
                    "facCd"=>$dealrow->fac_id,
                    "facTel"=>$facinfo->fac_tel,
                    "facAddress"=>$facinfo->fac_addr,
                    "facLat"=>$facinfo->fac_lat,
                    "facLon"=>$facinfo->fac_lon,
                    "refundRule" =>$facinfo->refund_rule,
                    "facDetail"=>$facinfo->fac_detail
                ]
            ]
        ];

        header("HTTP/1.0 200");
        echo json_encode($result);
    }
}



// PCMS 주문입력 // 테스트상품 오산버드파크 52144
function set_order($_itemcode,$_req){

	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => "https://gateway.sparo.cc/extra/agency/v2/dealcode/".$_itemcode,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "POST",
		CURLOPT_POSTFIELDS =>json_encode($_req),
		CURLOPT_HTTPHEADER => array(
			"Authorization: bQXGhiUKskKiO.pWnWm4YBsrQngus3nkJL.q2ehVOkO7d2TznbNs9M3.JbbM6ezKVJ",
			"Content-Type: application/json"
		),
	));

    $response = json_decode(curl_exec($curl));
    // var_dump($response);
		$result = [
		  "Code"=> $response->Code,
		  "Msg"=> $response->Msg,
		  "Result"=>[
            "orderNo"=> $_req['orderNo'],
		    "placemOrderNo"=> $response->Result[0]->OrderNo,
		    "orderSeq"=> $_req['orderSeq'],
		    "couponNo"=> $response->Result[0]->CouponNo
		  ]
		];
		header("HTTP/1.0 200");
		echo json_encode($result);

}



function set_cancel($orderno,$seq){

    $curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => "https://gateway.sparo.cc/extra/agency/v2/chorderno/".$orderno."-".$seq,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "PATCH",
		CURLOPT_POSTFIELDS =>json_encode($_req),
		CURLOPT_HTTPHEADER => array(
			"Authorization: bQXGhiUKskKiO.pWnWm4YBsrQngus3nkJL.q2ehVOkO7d2TznbNs9M3",
			"Content-Type: application/json"
		),
	));

    $response = json_decode(curl_exec($curl));

    // $result = [
    //     "Code"=> 1000,
    //     "Msg"=> "성공",
    //     "Result"=>[]
    // ];
    header("HTTP/1.0 200");
    echo json_encode($response);
}


function getItemInfo($itemcd){
    global $conn_cms;
    global $today;

    $sql = "SELECT * FROM CMSDB.CMS_ITEMS WHERE item_id = '$itemcd' and item_edate > '$today' LIMIT 1";
    $res = $conn_cms->query($sql);
    $row = $res->fetch_object();
    return $row;
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

function genRandomChar($length = 10) {
	$characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}

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

// 핸드폰번호 체크
function check_phone($PHONE){
    $ph = preg_replace("/[^0-9]*/s", "", $PHONE);
    $ph_len=strlen($ph);
    if( $ph_len >= '8' && $ph_len <= '11' ) {
        switch( $ph_len ) {
            case 8:
                $ph="010".$ph;
                $ph=substr($ph,0,3)."-".substr($ph,3,4)."-".substr($ph,7);
                break;

            case 9:
                $ph="0".$ph;
                $ph=substr($ph,0,3)."-".substr($ph,3,3)."-".substr($ph,6);
                break;

            case 10:
                if( substr($ph,0,1) == '0' ) {
                    $ph=substr($ph,0,3)."-".substr($ph,3,3)."-".substr($ph,6);
                } else if( substr($ph,0,1) == '1' ) {
                $ph="0".$ph;
                $ph=substr($ph,0,3)."-".substr($ph,3,4)."-".substr($ph,7);
                }
                break;

            case 11:
                $ph=substr($ph,0,3)."-".substr($ph,3,4)."-".substr($ph,7);
                break;
        }

        $pattern="/^01[016789]-[0-9]{3,4}-[0-9]{4}$/";
        $rs=( preg_match($pattern, $ph) ) ? true : false ;
        return $rs;
    }
}

// 핸드폰번호 - 넣은 형식으로 반환
function hpWithDash($hp_onlynum){
    if(strlen($hp_onlynum) == 11){
        $hp_withdash = substr($hp_onlynum,0,3)."-".substr($hp_onlynum,3,4)."-".substr($hp_onlynum,7);
    } elseif(strlen($hp_onlynum) == 10){
        $hp_withdash = substr($hp_onlynum,0,3)."-".substr($hp_onlynum,3,3)."-".substr($hp_onlynum,6);
    }

    return $hp_withdash;
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

// 날짜포맷 검증 함수
function isValidDate($date, $format= 'Y-m-d'){
    return $date == date($format, strtotime($date));
}


//주소를 통해 위도, 경도값 얻기
function get_geocode($addr){
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

// 엘라스틱서치에 로그 기록 -> 제이가 엘라스틱서치 서버 내려서 주석처리 - Jason 22.03.14
// function put_logs($_pid,$_logs){
//   $curl = curl_init();
//   $_json = json_encode($_logs);

//   curl_setopt_array($curl, array(
//     CURLOPT_URL => 'http://15.165.201.74:9200/cms/pmlogs/'.$_pid,
//     CURLOPT_RETURNTRANSFER => true,
//     CURLOPT_ENCODING => '',
//     CURLOPT_MAXREDIRS => 10,
//     CURLOPT_TIMEOUT => 0,
//     CURLOPT_FOLLOWLOCATION => true,
//     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//     CURLOPT_CUSTOMREQUEST => 'PUT',
//     CURLOPT_POSTFIELDS =>$_json,
//     CURLOPT_HTTPHEADER => array(
//       'Content-Type: application/json'
//     ),
//   ));

//   $response = curl_exec($curl);

//   curl_close($curl);
//   return json_decode($response);
// }

function orders($itemcd, $reqjson){



    curl_close($curl);

    return $response;

}

?>
