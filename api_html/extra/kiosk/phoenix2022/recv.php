<?php


/*
*
* 휘닉스 락커 사용처리 리시버
*
*/

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');

error_reporting(E_ALL);

$type = $_GET['type'];
$barcode = $_GET['barcode'];

// 개발망 IP 
$devip = "106.254.252.100";
$cliip = get_ip();

/*
if($cliip == $devip){
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
}
*/

// 로그 남기기
// GET 방식이므로 아파치 접속 로그 참조
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
list($microtime,$timestamp) = explode(' ',microtime()); 
$tranid = $timestamp . substr($microtime, 2, 3);
$apiheader = getallheaders(); // http 헤더
$para = $_SERVER['QUERY_STRING'];
$logsql = "insert cmsdb.extapi_log set apinm='phoenix2022 locker/recv', chnm='PHOENIX2022 locker request[$type]', tran_id='$tranid', ip='".get_ip()."', logdate=now(), apimethod='$apimethod', header='".addslashes(json_encode($apiheader))."', querystr='$para'";
$conn_rds->query($logsql);

//echo $logsql;
//echo $conn_rds->affected_rows;
//exit; 
// 바코드가 숫자가 아니면
//if(!is_numeric($barcode)){
//  exit;
//}

//$_row = $conn_rds->query("select id, orderno, state from cmsdb.alpensia_extcoupon where barcode= '$barcode' limit 1")->fetch_object();
//$_row = $conn_cms3->query("select * from spadb.ordermts_coupons where couponno = '$barcode' limit 1")->fetch_object();
$_row = $conn_cms3->query(
    "select c.*, o.id, o.ch_id, o.chnm, 
        o.man1, o.man2, o.man3, 
        o.couponno as o_couponno, o.barcode_no, o.barno,
        o.orderno, o.ch_orderno, o.state as o_state, o.usegu 
    from spadb.ordermts_coupons as c, spadb.ordermts as o 
    where c.couponno = '$barcode' and c.order_id = o.id limit 1"
)->fetch_object();
/*
  {
    "order_id": 21423976,
    "couponno": "PURGBH9NSQMH",
    "state": "N",
    "dt_use": null,
    "dt_cancel": null,
    "sync_use": "N",
    "coupon_info": null,

    "id": 21423976,
    "ch_id": 3294,
    "chnm": "뿌리깊은나무",
    "man1": 2,
    "man2": 0,
    "man3": 0,
    "o_couponno": "[\"PG9NJ33QENYS\",\"PURGBH9NSQMH\"]",
    "barcode_no": "PG9NJ33QENYS;PURGBH9NSQMH",
    "barno": null,
    "orderno": "20221021_PM257928180418",
    "ch_orderno": "",
    "o_state": "예약완료",
    "usegu": "2"
  }
*/
if(empty($_row)){
  echo "NO;$type 처리실패 : 조회 할수없습니다..";
  exit;
}

switch($type){
  case 'use':

      if($_row->state == 'Y'){
        echo "NO;$type 처리실패 : 이미 사용된 쿠폰.";
        exit;
      } 
/*
      if($_row->state == 'C'){
        echo "NO;$type 처리실패 : 이미 폐기/중지된 쿠폰.";
        exit;
      } 
*/
      //$usql = "update cmsdb.alpensia_extcoupon set state = 'Y', useDt =  now() where state='N' and barcode= '$barcode' limit 1";
      $usql = "update spadb.ordermts_coupons set state = 'Y', dt_use = now() where state='N' and couponno = '$barcode' limit 1";

      // 네이버는 주문 번호가 없으니 사용처리를 분기하자!
      //if(strlen($_row->orderno) > 11)
      if ($_row->ch_id != "2984")
      {
          // 소셜, 오픈마켓등등등은 네이버 알펜시아 테이블에서 주문번호를 구해서 pcms에 주문 사용 처리
          $c_sql = "update spadb.ordermts set usegu = '1', usegu_at = now() where orderno = '$_row->orderno' limit 1";
          // print(__LINE__." c_sql:$c_sql\n");
          $conn_cms3->query($c_sql);
      }else{

          // 네이버 주문은 네이버 테이블에서 쿠폰 코드를 구해서 pcms에 주문 사용처리
          $_nrow = $conn_rds->query("select id, cmsOrderNo from cmsdb.nbooking_orderdatails where couponNo= '$barcode' limit 1")->fetch_object();
          // print("naver-주문\n");
          // print_r($_nrow);
          $n_sql = "update cmsdb.nbooking_orderdatails set useState = 'Y', date_used = now() where id = '$_nrow->id' limit 1";
          // print(__LINE__." n_sql:$n_sql\n");
          $conn_rds->query($n_sql);

          //$c_sql = "update spadb.ordermts set usegu = '1', usegu_at = now() where orderno = '$_nrow->orderno' limit 1 ";
          $c_sql = "update spadb.ordermts set usegu = '1', usegu_at = now() where barcode_no  = '$barcode' limit 1 ";
          // print(__LINE__." c_sql:$c_sql\n");
          $conn_cms3->query($c_sql);
    }

    $rtn = $conn_cms3->query($usql);

    // $rtn = true;
    if($rtn){
        echo "OK;$type 처리완료";
        // 복합패키지 상품일경우 사용처리 추가 루틴
        //use_ext_package($_row);
    }else{
        echo "NO;$type 처리실패";
    }



  break;
  // 회수처리
  case 'unuse':
    // print_r($_row);exit;
    // 네이버는 주문 번호가 없으니 회수처리를 분기하자!
    $unused = false;
    //if(strlen($_row->orderno) > 11)
    if ($_row->ch_id != "2984")
    {
        $usql = "update spadb.ordermts_coupons set state = 'N', dt_use = null where state='Y' and couponno = '$barcode' limit 1";
        $ures = $conn_cms3->query($usql);
        // 쿼리가 정상적으로 실행된 경우에 회수 처리 정상인 것으로 판단
        if($ures) $unused = true;

        // 주문번호에 연결된 모든 쿠폰번호가 미사용일 경우에만 주문테이블 정보를 미사용으로 변경
        $osql = "select * from spadb.ordermts_coupons where order_id = '".$_row->order_id."' and (state = 'Y' or state = 'C')";
        $ores = $conn_cms3->query($osql)->fetch_object();
        $ocnt = $conn_cms3->affected_rows;

/*
        $osql = "select * from cmsdb.alpensia_extcoupon where orderno='$_row->orderno' and state='Y'";
        $ores = $conn_rds->query($osql)->fetch_object();
        // 조회된 row 수
        $ocnt = $conn_rds->affected_rows;
        //print_r($ocnt);exit;
*/

        // 사용처리된 쿠폰번호가 0건일 경우에만 미사용으로 변경
        if ($ocnt == 0){
           $c_sql = "update spadb.ordermts set usegu = '2', usegu_at = null where orderno = '$_row->orderno' limit 1";
           $conn_cms3->query($c_sql);
        }
    }else{
        // 네이버 주문은 네이버 테이블에서 쿠폰 코드를 구해서 원복
        $_nrow = $conn_rds->query("select id, cmsOrderNo from cmsdb.nbooking_orderdatails where couponNo= '$barcode' limit 1")->fetch_object();
//print_r($_nrow);exit;

        $n_sql = "update cmsdb.nbooking_orderdatails set useState = 'N', date_used = null where id = '$_nrow->id' limit 1";
        $ures = $conn_rds->query($n_sql);

        $usql = "update spadb.ordermts_coupons set state = 'N', dt_use = null where state='Y' and couponno = '$barcode' limit 1";
        $ures = $conn_cms3->query($usql);
        // 쿼리가 정상적으로 실행된 경우에 회수 처리 정상인 것으로 판단
        if($ures) $unused = true;

        //$cntY = "select * from cmsdb.nbooking_orderdatails where cmsOrderNo = '$_nrow->cmsOrderNo'";
        // 네이버는 주문 한건에 쿠폰번호도 한건
        // 주문번호에 연결된 모든 쿠폰번호가 미사용일 경우에만 주문테이블 정보를 미사용으로 변경
        //$c_sql = "update spadb.ordermts set usegu = '2', usegu_at = null where orderno = '$_nrow->orderno' limit 1 ";
        $c_sql = "update spadb.ordermts set usegu = '2', usegu_at = null where barcode_no  = '$barcode' limit 1 ";
        $conn_cms3->query($c_sql);
    }

    if($unused){
      echo "OK;$type 처리완료";
    }else{
      echo "NO;$type 처리실패";
    }


  break;
  default:
      echo "NO;$type 처리실패";
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
// 주문번호로 쿠폰번호 조회
function get_couponno_by_orderno($orderno){
    global $conn_cms3;

    $sql = "select * from spadb.ordermts
                where TRUE
                and orderno = '$orderno'";

    $res = $conn_cms3->query($sql);

    $_row = $res->fetch_object();

    if(empty($_row)){
        return array();
    }else{
        $couponno = json_decode($_row->couponno);
        return $couponno;
    }
}

// 네이버에서 주문한 건인지 체크
function is_naver_order($orderno){
    global $conn_cms3;

    $sql = "select * from spadb.ordermts
                where TRUE
                and orderno = '$orderno'";

    $res = $conn_cms3->query($sql);

    $_row = $res->fetch_object();

    if(empty($_row)){
        return false;
    }else{
        // 네이버예약
        if($_row->ch_id == '2984'){
            return true;
        }else{
            return false;
        }
    }
}

/*

    $reqjson = array(
      "orderno"=> $orderno,
      "qty" => $qty,
      "usernm" => $usernm,
      "userhp" => $userhp
    );

    print_r($reqjson);
    $_cp = json_decode(getCouponNo($itemcd ,json_encode($reqjson,JSON_UNESCAPED_UNICODE)));
*/
// 시설에 쿠폰취소
function cancelCoupon($couponno){

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://gateway.sparo.cc/extra/kiosk/alpensia/cancel/'.$couponno,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'GET',
  // CURLOPT_POSTFIELDS =>$reqjson,
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/json'
  ),
));

$response = curl_exec($curl);
    
$response = json_decode($response, JSON_UNESCAPED_UNICODE);

curl_close($curl);
return $response;

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
