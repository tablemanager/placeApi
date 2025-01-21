<?php
/*
 *
 * 키자니아
 *
 * 작성자 : 이정진
 * 작성일 : 2017-11-21
 *
 *
 *
 */

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
$conn_rds->query("set names utf8");
$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

// 파라미터
$itemreq = explode("/",$para);
$jsonreq = trim(file_get_contents('php://input'));

$proc = $itemreq[1];


// ACL 확인
$accessip = array("106.254.252.100",
                  "61.38.140.35",
				  "118.130.130.34",
				  "118.130.130.38",
				  "118.130.130.55",
				  "118.130.130.57",
				  "211.232.5.2",
				  "1.220.248.60"
                  );



if(!in_array(get_ip(),$accessip)){
    //header("HTTP/1.0 401 Unauthorized");
    //$res = array("resultCode"=>"9996","resultMessage"=>"아이피 인증 오류 : ".get_ip());
    //echo json_encode($res);
    //exit;
}



header("Content-type:application/json");


$mdate = date("Y-m-d");

$_resjson = json_encode(array("resultCode"=>"9998","resultMessage"=>"파라미터 오류"));

/*
  1. 파라미터 체크가 필요함(코드)
*/

switch($proc){
	case 'couponno':
    //쿠폰번호 조회
    $couponno = $itemreq[2];
		if($apimethod == "GET") $_resjson = getOrderInfo("couponno",$couponno);
	break;
	case 'mobile':
    //핸드폰번호로 조회
     $cushp = $itemreq[2];
		if($apimethod == "GET") $_resjson = getOrderInfo("cushp",$cushp);
	break;
	case 'used':
    // 사용내역 조회(일자)
    $sdate = $itemreq[3];
    $edate = $itemreq[5];
		if($apimethod == "GET") $_resjson = getUsedOrders($sdate,$edate);
	break;
  case 'itemcode':

    $itemcd = $itemreq[2];
    $vdate = $itemreq[4];

    // 예약가능 수량 조회 GET
		if($apimethod == "GET") $_resjson = getItemInventory($vdate,$itemcd);
    // 예약가능 수량 수정 PATCH
    if($apimethod == "PATCH") $_resjson = setItemInventory($vdate,$itemcd,$jsonreq);

	break;
  case 'use':
    // 사용처리
    $couponno = $itemreq[2];
    if($apimethod == "POST") $_resjson = setUseCoupon($couponno);

    // 예약가능 수량 수정 PATCH
  break;
  case 'recover':
    // 사용취소 처리 PATCH
    $couponno = $itemreq[2];
    if($apimethod == "PATCH") $_resjson = setRecoveryCoupon($couponno);
  break;

	default:
		header("HTTP/1.0 400");
}

echo $_resjson;

// 인벤토리 조회
function getItemInventory($vdate,$itemcd){
  global $conn_rds;

  $_items = $conn_rds->query("select item_type from cmsdb.kidzania_jeju_rese where item_kioskcode = '$itemcd' limit 1")->fetch_object();

  if($_items->item_type == "1" or $_items->item_type == "2"){
      // 오전오후 일경우 그대로 조회
      $icflag = "100".$_items->item_type;
      $_sql = "select
                   item_type,
                   item_code,
                   r_block,
                   r_price,
                   chk_date,
                   disable
             from
                 cmsdb.kidzania_jeju_block
             where
                 chk_date='$vdate' and
                 item_code='$icflag'";

     $_row = $conn_rds->query($_sql)->fetch_object();

     $result = array(
                     "itemtype"=>$_row->item_type,
                     "itemcode"=>$itemcd,
                     "block"=>$_row->r_block,
                     "chkdate"=>$_row->chk_date,
                     "status"=>'Y'
     );

  }else{
     $_row1 = $conn_rds->query("select
                 item_type,
                 item_code,
                 r_block,
                 r_price,
                 chk_date,
                 disable
           from
               cmsdb.kidzania_jeju_block
           where
               chk_date='$vdate' and
              item_code = '1001'")->fetch_object();

     $_row2 = $conn_rds->query("select
                          item_type,
                          item_code,
                          r_block,
                          r_price,
                          chk_date,
                          disable
                    from
                        cmsdb.kidzania_jeju_block
                    where
                        chk_date='$vdate' and
                       item_code = '1002'")->fetch_object();

      if($_row1->r_block > $_row2->r_block){
        // 오전이 오후보다 수량이 많으면 오후
              $result = array(
                              "itemtype"=>$_row2->item_type,
                              "itemcode"=>$itemcd,
                              "block"=>$_row2->r_block,
                              "chkdate"=>$_row2->chk_date,
                              "status"=>'Y'
              );

      }else{
              $result = array(
                              "itemtype"=>$_row1->item_type,
                              "itemcode"=>$itemcd,
                              "block"=>$_row1->r_block,
                              "chkdate"=>$_row1->chk_date,
                              "status"=>'Y'
              );
      }

  }

	return json_encode($result);
}

// 인벤토리 수정
function setItemInventory($vdate,$itemcd,$jsonreq){
  global $conn_rds;
  $_items = $conn_rds->query("select item_type from cmsdb.kidzania_jeju_rese where item_kioskcode = '$itemcd' limit 1")->fetch_object();
  $_req= json_decode($jsonreq);
  if($_req->flag == 'C') $bstr = "r_block = r_block - $_req->block ";
  if($_req->flag == 'R') $bstr = "r_block = r_block + $_req->block ";

  if($_items->item_type == "1" or $_items->item_type == "2"){
        $icflag = "100".$_items->item_type;


        $_sql = "
                update
                        cmsdb.kidzania_jeju_block
                set
                        $bstr
                where
                        chk_date='$vdate' and
                        item_code='$icflag'";

        $_row = $conn_rds->query($_sql);
  }else{
       // 종일권의 경우 오전오후 동시 차감
       $_sql = "
               update
                       cmsdb.kidzania_jeju_block
               set
                       $bstr
               where
                       chk_date='$vdate' and
                       item_code='1001'";

       $_row = $conn_rds->query($_sql);
       $_sql = "
               update
                       cmsdb.kidzania_jeju_block
               set
                       $bstr
               where
                       chk_date='$vdate' and
                       item_code='1002";

       $_row = $conn_rds->query($_sql);

  }

  $_result = array("RCODE"=>"S",
                   "RMSG"=>"성공");
	return json_encode($_result);
}

// 사용취소
function setRecoveryCoupon($couponno){
	global $conn_rds;
  $_sql = "select
               *
          from
             cmsdb.kidzania_jeju_rese
          where coupon = '$couponno'";

  $_row = $conn_rds->query($_sql)->fetch_object();

  if($_row->coupon_use == "Y"){

  $_usql = "update cmsdb.kidzania_jeju_rese set coupon_use='N', use_date = null where coupon = '$couponno' and coupon_use='Y' limit 1";
  $conn_rds->query($_usql);

  $_result = array("RCODE"=>"S",
                   "RMSG"=>"성공");
  }else{
    $_result = array("RCODE"=>"F",
                     "RMSG"=>"상태를 변경할수 없습니다.");
  }

	return json_encode($_result);
}

// 사용처리
function setUseCoupon($couponno){
	global $conn_rds;

  $_sql = "select
               *
          from
             cmsdb.	kidzania_jeju_rese
          where coupon = '$couponno'";

 $_row = $conn_rds->query($_sql)->fetch_object();
 if($_row->coupon_use == "N"){
      $_usql = "update cmsdb.kidzania_jeju_rese set coupon_use='Y', use_date = now() where coupon = '$couponno' and coupon_use='N' limit 1";
      $conn_rds->query($_usql);

      $_result = array("RCODE"=>"S",
                       "RMSG"=>"성공");
  }else{
    $_result = array("RCODE"=>"F",
                     "RMSG"=>"상태를 변경할수 없습니다.");
  }
	return json_encode($_result);
}

// 사용처리 내역 조회
function getUsedOrders($sdate,$edate){
  global $conn_rds;
  $sdt = $sdate." 00:00:00";
  $edt = $edate." 23:59.59";
  $orderqry2 = "SELECT
            *
         FROM
             cmsdb.kidzania_jeju_rese
         WHERE
             coupon_use = 'Y' and
             use_date between '$sdt' and '$edt'
        
 ";

   $_res = $conn_rds->query($orderqry2);

   $_result = array();

   if($_res->num_rows > 0){
     $tot = 0;
     while($_row = $_res->fetch_object()){
        $_result[] = array("MENUCODE"=>$_row->item_kioskcode,
                          "USEDATE"=>$_row->use_date,
                          "COUPONNO"=>$_row->coupon,
                          "PRICE"=>$_row->item_price
                        );
          $tot = $tot + $_row->item_price;
     }

   }else{

   }
   $result = array(
                   "RCODE"=>"S",
                   "RMSG"=>"조회 성공",
                   "RCNT"=> $_res->num_rows,
                   "RAMT"=>$tot,
                   "ORDERS"=> $_result

   );

	return json_encode($result);
}

// 쿠폰번호, 핸드폰으로 주문 조회
function getOrderInfo($cd,$val){
        	global $conn_rds;

          if($cd == "cushp"){
              $where = " user_tel = '$val' ";
          }else{
              $where = " coupon = '$val' ";
          }

        	 $orderqry2 = "SELECT
                              pcms_order,
                              coupon,
                              item_code,
                              item_name,
                              item_kioskcode,
                              item_price,
                              check_in,
                              rese_status,
                              jeju_order,
                              coupon_use,
                              user_nm,
                              user_tel
        					FROM
        							cmsdb.kidzania_jeju_rese
        					WHERE
        							$where
        	";

        	$_res = $conn_rds->query($orderqry2);

        	$_result = array();

        	if($_res->num_rows > 0){

            while($_row = $_res->fetch_object()){

                $state = array("S1"=>"접수대기",
                                "F1"=>"예약확정",
                                "F2"=>"예약진행",
                                "F3"=>"예약실패",
                                "C1"=>"예약취소",
                                "C2"=>"취소실패");
                $usestate = array("1",);

                $_result[] = array(
                                    "COUPONNO" => $_row->coupon,
                                    "ORDERNO" => $_row->jeju_order,
                                    "MENUCODE" => $_row->item_kioskcode,
                                    "MENUNAME" => $_row->item_name,
                                    "PRICE" => $_row->item_price,
                                    "QTY" => 1,
                                    "EXPDATE" => $_row->check_in,
                                    "STATE" => $state["$_row->rese_status"],
                                    "USTATE" => $_row->coupon_use,
                                    "CUSNM" => $_row->user_nm,
                                    "CUSHP" => $_row->user_tel,
                                    "CUSOPT"=> null
                                  );
            }

            $result = array(
                            "RCODE"=>"S",
                            "RMSG"=>"조회 성공",
                            "RCNT"=> $_res->num_rows,
                            "ORDERS"=> $_result
            );

        	}else{
            $result = array(
                            "RCODE"=>"S",
                            "RMSG"=>"조회된 내용이 없습니다.",
                            "RCNT"=> $_res->num_rows,
                            "ORDERS"=> null
            );
        	}

        	return json_encode($result);
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
?>
