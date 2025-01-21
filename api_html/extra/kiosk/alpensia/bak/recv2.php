<?php


/*
*
* 알펜시아 사용처리 리시버
*
*/

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');

$type = $_GET['type'];
$barcode = $_GET['barcode'];



// 로그 남기기
// GET 방식이므로 아파치 접속 로그 참조



// 바코드가 숫자가 아니면
if(!is_numeric($barcode)){
  exit;
}

$_row = $conn_rds->query("select id, orderno, state from cmsdb.alpensia_extcoupon where barcode= '$barcode' limit 1")->fetch_object();

if(empty($_row)){
  echo "NO;$type 처리실패 : 조회 할수없습니다..";
  exit;
}

switch($type){
  case 'use':
      // 20220608 tony
      // 이미 사용처리 된 경우에도 업데이트 쿼리를 날린다.
      // 간혹 ordermts 테이블의 업데이트(2~3일에 몇건 발생)가 안되는 건이 있는데 재 시도하면 된다.
      $usql = "update cmsdb.alpensia_extcoupon set state = 'Y', useDt =  now() where state='N' and barcode= '$barcode' limit 1";

      // 네이버는 주문 번호가 없으니 사용처리를 분기하자!
      if(strlen($_row->orderno) > 11){
            // 소셜, 오픈마켓등등등은 알펜시아 테이블에서 주문번호를 구해서 pcms에 주문 사용 처리
            // 20220608 tony 미사용 건을 사용으로 변경
            $c_sql = "update spadb.ordermts set usegu = '1', usegu_at = now() where usegu = '2' and orderno = '$_row->orderno' limit 1";
            $rtn = $conn_cms3->query($c_sql);
echo "<p>결과1 : $rtn, aff : $conn_cms3->affected_rows<p>";
            if (!$rtn){
                 echo "NO;$type 처리실패 : 재시도 요망";
                 exit;
            }
      }else{
            // 20220608 tony 미사용 건을 사용으로 변경
            // 네이버 주문은 네이버 테이블에서 쿠폰 코드를 구해서 pcms에 주문 사용처리
            $_nrow = $conn_rds->query("select id, cmsOrderNo from cmsdb.nbooking_orderdatails where couponNo= '$barcode' limit 1")->fetch_object();

            $n_sql = "update cmsdb.nbooking_orderdatails set useState = 'Y', date_used = now() where useState = 'N' and id = '$_nrow->id' limit 1";
           $rtn= $conn_rds->query($n_sql);
echo "<p>결과1 : $rtn, aff : $conn_cms2->affected_rows<p>";
            if (!$rtn){
                 echo "NO;$type 처리실패 : 재시도 요망(NB1)";
                 exit;
            }
 
            //$c_sql = "update spadb.ordermts set usegu = '1', usegu_at = now() where orderno = '$_nrow->orderno' limit 1 ";
            $c_sql = "update spadb.ordermts set usegu = '1', usegu_at = now() where usegu = '2' and barcode_no  = '$barcode' limit 1 ";
            $rtn = $conn_cms3->query($c_sql);
echo "<p>결과1 : $rtn, aff : $conn_cms3->affected_rows<p>";
            if (!$rtn){
                 echo "NO;$type 처리실패 : 재시도 요망(NB2)";
                 exit;
            }
      }

      if($_row->state == 'Y'){
        echo "NO;$type 처리실패 : 이미 사용된 쿠폰.";

        exit;
      } 

    if($conn_rds->query($usql)){
      echo "OK;$type 처리완료";
    }else{
      echo "NO;$type 처리실패";
    }



  break;
  // 회수처리
  case 'unuse':
//print_r($_row);exit;
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
