<?php


/*
*
* 알펜시아 사용처리 리시버
*
*/

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');

error_reporting(E_ALL);

$type = $_GET['type'];
$barcode = $_GET['barcode'];
// 시즌권 고객정보 등록시 사용처리(내부)
$isSeason = ($_GET['internal']=='seasontk_regist')?true:false;

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
$logsql = "insert cmsdb.extapi_log set apinm='alpensia/recv2', chnm='ALPENSIA request[$type]', tran_id='$tranid', ip='".get_ip()."', logdate=now(), apimethod='$apimethod', header='".addslashes(json_encode($apiheader))."', querystr='$para'";
$conn_rds->query($logsql);
//echo $logsql;
//echo $conn_rds->affected_rows;
//exit; 
// 바코드가 숫자가 아니면
if(!is_numeric($barcode)){
  exit;
}

$_row = $conn_rds->query("select id, orderno, state from cmsdb.alpensia_extcoupon where barcode= '$barcode' limit 1")->fetch_object();

if(empty($_row)){
    // 시설에서 (입장) 사용처리
    if($isSeason == false){
        // 주문시 발급받은 쿠폰번호는 인증번호로 사용되고 입장용으로도 사용처리 됨
        // 시즌권은 사용후 1일이 지나면 새 쿠폰번호가 발행됨
        
    }
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
      $usql = "update cmsdb.alpensia_extcoupon set state = 'Y', useDt =  now() where state='N' and barcode= '$barcode' limit 1";

      // 네이버는 주문 번호가 없으니 사용처리를 분기하자!
      if(strlen($_row->orderno) > 11){
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

    $rtn = $conn_rds->query($usql);
    // $rtn = true;
    if($rtn){
        echo "OK;$type 처리완료";
        // 복합패키지 상품일경우 사용처리 추가 루틴
        use_ext_package($_row);
    }else{
        echo "NO;$type 처리실패";
    }



  break;
  // 회수처리
  case 'unuse':
    // print_r($_row);exit;
    // 네이버는 주문 번호가 없으니 회수처리를 분기하자!
    $unused = false;
    if(strlen($_row->orderno) > 11){
        $usql = "update cmsdb.alpensia_extcoupon set state = 'N', useDt = null where state='Y' and barcode= '$barcode' limit 1";
        $ures = $conn_rds->query($usql);
        // 쿼리가 정상적으로 실행된 경우에 회수 처리 정상인 것으로 판단
        if($ures) $unused = true;

        // 주문번호에 연결된 모든 쿠폰번호가 미사용일 경우에만 주문테이블 정보를 미사용으로 변경
        $osql = "select * from cmsdb.alpensia_extcoupon where orderno='$_row->orderno' and state='Y'";
        $ores = $conn_rds->query($osql)->fetch_object();
        // 조회된 row 수
        $ocnt = $conn_rds->affected_rows;
        //print_r($ocnt);exit;

        // 사용처리된 쿠폰번호가 0건일 경우에만 미사용으로 변경
        if ($ocnt == 0){
           $c_sql = "update spadb.ordermts set usegu = '2', usegu_at = null where orderno = '$_row->orderno' limit 1";
           $conn_cms3->query($c_sql);
        }
    }else{
        // 네이버 주문은 네이버 테이블에서 쿠폰 코드를 구해서 원복
        $_nrow = $conn_rds->query("select id, cmsOrderNo from cmsdb.nbooking_orderdatails where couponNo= '$barcode' limit 1")->fetch_object();
//print_r($_nrow);exit;
/*
stdClass Object
(
    [id] => 5422822
    [cmsOrderNo] => 20220531_N281396068_2
    [bookingId] => 281396068
    [priceId] => 3999941
    [bookingSeq] => 2
    [agencyBizId] => 88942
    [agencyBizItemId] => 4456641
    [cmsItemCode] => 54554
    [orderState] => O
    [useState] => Y
    [cusNm] => ???
    [cusPhone] => 01031613596
    [expDate] => 2022-07-15
    [couponNo] => 9800000767000007
    [optCouponNo] => 
    [npayOrderNo] => 
    [nPayProductOrderNumber] => 
    [flagOrderCms] => Y
    [flagSyncUse] => Y
    [flagSyncCancel] => 
    [date_used] => 2022-05-31 14:35:46
    [date_ordered] => 2022-05-31 11:36:18
    [date_canceled] => 
    [naverStatus] => 
// stdClass Object ( [id] => 5422822 [cmsOrderNo] => 20220531_N281396068_2 )
)*/ 
        $n_sql = "update cmsdb.nbooking_orderdatails set useState = 'N', date_used = null where id = '$_nrow->id' limit 1";
        $ures = $conn_rds->query($n_sql);

        $usql = "update cmsdb.alpensia_extcoupon set state = 'N', useDt = null where state='Y' and barcode= '$barcode' limit 1";
        $ures = $conn_rds->query($usql);
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

// 복합패키지 주문목록에 데이터 찾기
// 있으면 복합패키지 상품의 구성상품임.
// 없으면 일반 상품임
// $orderno : 구성상품 주문번호
function get_ext_package_order_info($orderno){
    global $conn_cms3;

    $sql = "select * from spadb.ext_package_orders
                where TRUE
                and part_item_orderno = '$orderno'";
    $res = $conn_cms3->query($sql);

    $_row = $res->fetch_object();

    return $_row;
}

// 복합패키지 주문목록에서 노출상품 주문번호는 동일하고, 사용처리한 주문번호와 다른 주문  데이터 찾기
// $expose_orderno : 노출상품 주문번호
// $except_part_orderno : 사용처리한 구성상품 주문번호
function get_ext_package_order_info_exclusive($expose_orderno, $except_part_orderno){
    global $conn_cms3;
    // id, orderno, state 
    $sql = "select * from spadb.ext_package_orders
                where TRUE
                and expose_item_orderno = '$expose_orderno'
                and part_item_orderno not in ('$except_part_orderno')";
    $res = $conn_cms3->query($sql);

    $_row = array();

    while ($_r = $res->fetch_array()) {
        $_row[] = $_r;
    }

    return $_row;
}

// 복합패키지 정보 조회
function get_ext_package_info($seq){
    global $conn_cms3;
    // id, orderno, state 
    $sql = "select * from spadb.ext_package_ordermts
                where TRUE
                and seq = '$seq'";
    $res = $conn_cms3->query($sql);

    $_row = $res->fetch_object();

    return $_row;
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

// 복합패키지 구성상품일경우 노출상품 주문건 사용처리 추가 루틴
// $info : alpensia_extcoupon 테이블에서 조회한 쿠폰정보
// 출력이 있으면 절대 안됨-이 함수가 호출되기 전에 이미 결과 회신을 했음
function use_ext_package($info){
    global $conn_rds, $conn_cms3;
    //print("\n");

    // 복합패키지 구성상품인지 체크
    $ext_order_info = get_ext_package_order_info($info->orderno);
    if(empty($ext_order_info)){
        // 복합패키지 상품이 아님. 처리할 것이 없음
        return;
    }
    
    // 복합패키지 정보 조회
    $ext_package_info = get_ext_package_info($ext_order_info->ext_package_ordermts_seq);
    if(empty($ext_package_info)){
        // 복합패키지 정보가 없으면 아무것도 처리할 수 없음
        return;
    }

    // 노출상품 사용처리 플레그
    if ($ext_package_info->package_use == 'Y'){
        // 네이버예약 주문 건이 아니면
        if(is_naver_order($ext_order_info->expose_item_orderno) == false){
            // 소셜, 오픈마켓등등등은 노출주문번호로 사용처리
            $c_sql = "update spadb.ordermts set usegu = '1', usegu_at = now() where orderno = '$ext_order_info->expose_item_orderno' limit 1";
            //print(__LINE__." $c_sql\n");
            $conn_cms3->query($c_sql);
        }else{ 
            // 네이버 주문은 네이버 테이블에서 쿠폰 코드를 구해서 pcms에 주문 사용처리
            $_nrow = $conn_rds->query("select id, cmsOrderNo from cmsdb.nbooking_orderdatails where cmsOrderNo= '$ext_order_info->expose_item_orderno' limit 1")->fetch_object();

            $n_sql = "update cmsdb.nbooking_orderdatails set useState = 'Y', date_used = now() where id = '$_nrow->id' limit 1";
            //print(__LINE__." $n_sql\n");
            $conn_rds->query($n_sql);

            $c_sql = "update spadb.ordermts set usegu = '1', usegu_at = now() where orderno  = '$ext_order_info->expose_item_orderno' limit 1 ";
            //print(__LINE__." $c_sql\n");
            $conn_cms3->query($c_sql);
        }
    }

    // 사용처리되지 않은 나머지 구성상품 취소 처리 여부 확인
    if($ext_package_info->package_exclusive_use == 'Y'){
        // 구성상품 나머지 구성상품 
        $cancel_orders = get_ext_package_order_info_exclusive($ext_order_info->expose_item_orderno, $ext_order_info->part_item_orderno);
//print_r($cancel_orders);

        // 취소처리
        foreach($cancel_orders as $cancel){
            // 주문번호로 쿠폰번호 찾기
            $couponno = get_couponno_by_orderno($cancel['part_item_orderno']);
//print_r($couponno);
            // 쿠폰 취소처리
            foreach($couponno as $cp) {
                // echo "쿠폰 취소:[$cp]";
                $rtn = cancelCoupon($cp);
                // echo "=> [$rtn]\n";
                $uc_sql = "update spadb.ordermts set sync_fac='C', state='취소' where orderno='".$cancel['part_item_orderno']."' limit 1";
 //               print(__LINE__." $uc_sql\n");
                $conn_cms3->query($uc_sql);
            }
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

// 오션700패스 시즌권 사용처리
function use_ocean700pass($barcode){
}

?>
