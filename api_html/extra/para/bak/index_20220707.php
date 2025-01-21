<?php

// 이메일로 요청했을 때, 파라다이스 펀시티측에서 플레이스엠의 URL 은 http://extapi.sparo.cc/extra/para 이라고 답변 와서 이 파일의 api로 요청이 들어오고 있음을 알 수 있었음. - 21.09.24 Jason


// echo "TEST";
// exit;

include '/home/sparo.cc/lib/placem_helper.php';
require_once('/home/placedev/cmsapps/common/conf/dbconn.conf.php');

include '/home/sparo.cc/paradise_script/class/class.paradise.php';
include '/home/sparo.cc/paradise_script/class/paradise_model.php';

header("Content-type:application/json");


// ACL 확인
//$accessip = array("115.68.42.2",
//	"115.68.42.8",
//	"115.68.42.130",
//	"52.78.174.3",
//	"106.254.252.100",
//	"115.68.182.165",
//	"13.124.139.14",
//	"218.39.39.190",
//	"114.108.179.112",
//	"13.209.232.254",
//	"13.124.215.30",
//	"221.141.192.124",
//	"103.60.126.37"
//);
//__accessip($accessip);
/**
 1000	사용(사용취소)요청 처리 완료

2000	알려지지 않는 오류	사용 요청, 사용취소 요청 공통
2010	이미 사용 된 예약 사용 요청
2020	이미 환불 된 예약 사용 요청
2030	사용기간이 아닌 예약의 사용 요청
2031	사용기간이 아닌 예약의 사용 요청(2)
2040	존재하지 않는 예약에 대한 사용 요청
2110	이미 사용취소 된 예약에 대해 사용취소 요청	이 경우는 POS에서 취소처리를 정상적으로 진행
2120	존재하지 않는 예약에 대한 사용취소 요청

 */

$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더
$tranid = date("Ymd") . genRandomStrtemp(10); // 트렌젝션 아이디
// 파라미터
//$itemreq = explode("/",$para);

$itemreq[0] = $_GET['barcode'];
$itemreq[1] = $_GET['method'];


$_log_header = "S";


// 로그 남기기
$fnm = date('Ymd') . 'para.log';
$fp = fopen("/home/sparo.cc/api_html/extra/para/txt/$fnm", 'a+');
$adata = Array(
  'get' => $_GET,
  'apimethod' => $apimethod,
  'tranid' => $tranid
);
fwrite($fp, "==================================================\n");
fwrite($fp, date("Y-m-d H:i:s")." ".print_r($adata, true));
// fclose($fp);



switch ($itemreq[1])
{
    case 'use':
        $_date = date("Y-m-d H:i:s");
        $_date2 = date("Y-m-d");

        $_use_search = "SELECT * FROM ordermts  WHERE barcode_no = '".$itemreq[0]."' ";

        $itemUseResult = $conn_cms3->query($_use_search);
        $ii = 0;
        $json_result['status'] = "";
        while($row = $itemUseResult->fetch_object()){
            $ii++;

            if ($row->usegu == "1"){
                $json_result['branch_cd'] = '1000';
                $json_result['status'] = '2010';
                $json_result['method'] = $itemreq[1];
                $json_result['barcode'] = $itemreq[0];
                $json_result['tran_id'] = $tranid;
                $_log_header = "use";

            }

            if ($row->state != "예약완료"){
                $json_result['branch_cd'] = '1000';
                $json_result['status'] = '2020';
                $json_result['method'] = $itemreq[1];
                $json_result['barcode'] = $itemreq[0];
                $_log_header = "cancel";
            }

        }

        if ($ii == 0){
            $_use_search_qry = "SELECT * FROM ordermts  WHERE grmt_id in('3649','3651','3625')  AND  barcode_no LIKE '%".$itemreq[0]."%'";
            $itemUseResultOpen = $conn_cms3->query($_use_search_qry);
            while($row = $itemUseResultOpen->fetch_object()){
                if ($row->state != "예약완료"){
                    $json_result['branch_cd'] = '1000';
                    $json_result['status'] = '2020';
                    $json_result['method'] = $itemreq[1];
                    $json_result['barcode'] = $itemreq[0];
                    $_log_header = "cancel2";
                }
            }
        }

        if (!empty($json_result['status'])){

            $res = json_encode($json_result);
            $logsql = "insert cmsdb.extapi_log_para set apinm='para',tran_id='$tranid', ip='" . get_ip_temp() . "', logdate= now(), header='$_log_header', apiresult='$res' , apimethod='$itemreq[1]', querystr='" . $itemreq[0] . "'";
            $conn_rds->query($logsql);

            echo $res;
            fwrite($fp, date("Y-m-d H:i:s")." ".print_r($res, true));
            fwrite($fp, "\n==================================================\n");
            fclose($fp);
            exit;
        }

        $_barcode_search = "select * from paradian_reservation where BARCODE = '".$itemreq[0]."' ";

        $itemListResult = $conn_rds->query($_barcode_search);
//        $json_result['status'] = '2000';
        while($row = $itemListResult->fetch_object())
        {
            if ($row->USEGU == "1") {
                $json_result['status'] = '2010';
            } else {
                if ($row->STATUS == "RESERVE"){
//                if ($row->AVAIL_TDATE >  $_date){
                    // 20220609 tony 주석추가
                    // AVAIL_FDATE : 상품유효기간 시작일
                    // AVAIL_TDATE : 상품유효기간 종료일
                    // if ($row->AVAIL_TDATE >  $_date && $row->AVAIL_FDATE <  $_date){
                    if ($row->AVAIL_FDATE < $_date && $_date < $row->AVAIL_TDATE){
                        $json_result['status'] = '1000';

                        $update_sql = "UPDATE cmsdb.paradian_reservation  SET USEGU = '1' WHERE BARCODE = '".$itemreq[0]."' AND USEGU = '2'";
                        $conn_rds->query($update_sql);
                        $_log_header = "use2";
                        __usecouponno($itemreq[0]);
                        // ordermts와 ordermts_coupons테이블 업데이트가 누락되는 경우가 보고되어 업데이트쿼리 추가 - Jason 21.09.17
                        $update_coupons_sql = "UPDATE spadb.ordermts_coupons SET state = 'Y', dt_use = NOW() WHERE couponno = '".$itemreq[0]."' AND state = 'N' LIMIT 1";
                        $conn_cms3->query($update_coupons_sql);
                        $update_mts_sql = "UPDATE spadb.ordermts SET usegu = '1', usegu_at = NOW() WHERE barcode_no = '".$itemreq[0]."' AND state = '예약완료' AND usegu = '2' LIMIT 1";
                        $conn_cms3->query($update_mts_sql);
                    } else {

                        if (!empty($row->AVAIL_USEDATE) && $row->AVAIL_USEDATE != null){
                            // 20220609 tony
                            // 상품유효기간이 만료된 경우에는 AVAIL_USEDATE(상품유효기간 종료일) 이내인지 체크
                            // 로직이 이상하여(만료일 당일만 가능)  주석처리하고 새 조건(만료일 이내)으로 변경
                            //if ($row->AVAIL_USEDATE >=  $_date2 && $row->AVAIL_USEDATE <=  $_date2){
                            if ($ros->AVAIL_FDATE <= $_date2 && $_date2 <= $row->AVAIL_USEDATE){
                                $json_result['status'] = '1000';
                                $update_sql = "UPDATE cmsdb.paradian_reservation  SET USEGU = '1' WHERE BARCODE = '".$itemreq[0]."'";
                                $conn_rds->query($update_sql);
                                $_log_header = "use3";
                                __usecouponno($itemreq[0]);
                            }else{
                                // 20220609 tony
                                // 상퓸유효기간 종료일이 넘은경우(코드 2031 추가)
                                $json_result['status'] = '2031';
                            }
                        } else {
//                            $json_result['status'] = '2000';
                            $json_result['status'] = '2030';
                        }

//                    $json_result['status'] = '2030';
                    }

                } else  if ($row->STATUS == "CANCEL"){
                    $json_result['status'] = '2020';
                } else {
                    $json_result['status'] = '2000';
                }
            }

        }

        $json_result['branch_cd'] = '1000';

        $json_result['method'] = $itemreq[1];
        $json_result['barcode'] = $itemreq[0];
        $json_result['tran_id'] = $tranid;

        $res = json_encode($json_result);

        break;
    case 'unuse':
        $json_result['branch_cd'] = '1000';
        $json_result['method'] = $itemreq[1];
        $json_result['status'] = '1000';
        $json_result['barcode'] = $itemreq[0];
        $json_result['tran_id'] = $tranid;

        $_curl = new paradise();
        $_model = new paradise_model();

        $_fillter['BARCODE'] = $json_result['barcode'];

        $_data = $_model->getParadiseCoupon($_fillter);

        if (count($_data) == 0){
            $json_result['status'] = '2120';
//            $json_result['status'] = '2000';
        } else {
            if ($_data[0]['STATUS'] == "RESERVE"){

                if ($_data[0]['AVAIL_TDATE'] < date('Y-m-d')){
//                    $res = json_encode(array(
//                        "RESULT"=>false,
//                        "MSG"=>"유효기간 지남"
//                    ));
                    $json_result['status'] = '2030';
                } else {
//                    $_use_search = "SELECT * FROM ordermts  WHERE barcode_no = '".$itemreq[0]."' ";

                    $_use_search = "SELECT * FROM ordermts  WHERE grmt_id in('3649','3651','3625')  AND  barcode_no LIKE '%".$itemreq[0]."%'";

                    $itemUseResult = $conn_cms3->query($_use_search);

                    while($row = $itemUseResult->fetch_object()){
                        $row->ch_id;
                        $row->id;
                        $_insert_ary = array($itemreq[0]);
                        $_coupon_data = json_encode($_insert_ary);
                        $sync_flag = "N";
                        $logdate = date("Y-m-d H:is");


                        if ($row->usegu == "1" && $row->state == "예약완료"){
                            $json_result['status'] = '1000';

//                            $useg_sparo_usql = "update ordermts set usegu = '2' where  orderno = '".$_data[0]['ORDER_NO']."' and  barcode_no = '".$itemreq[0]."' limit 1 ";
                            $useg_sparo_usql = "update ordermts set usegu = '2' where  orderno = '".$_data[0]['ORDER_NO']."' limit 1 ";
                            $conn_cms3->query($useg_sparo_usql);

                            // CM_ADMIN 상에서 쿠폰번호 눌렀을 때 사용취소처리가 되지 않고 계속 Y와 최초 사용처리일시가 뜨는 문제를 해결하기 위해 coupons테이블 업데이트문 추가 - Jason 21.09.17
                            $useg_sparo_usql = "update ordermts_coupons set state = 'N', dt_use = '' where state = 'Y' AND couponno = '$itemreq[0]' limit 1 ";
                            $conn_cms3->query($useg_sparo_usql);

//                            $usegusql = "insert ordermts_recovery set ch_id='$row->ch_id', sync_flag= 'N' ,couponno = '$_coupon_data' ,order_id='$row->id', log_ip='" . get_ip_temp() . "', logdate= now()";
                            $usegusql = "insert ordermts_recovery set ch_id='$row->ch_id', sync_flag= 'N' ,couponno = '$itemreq[0]' ,order_id='$row->id', log_ip='" . get_ip_temp() . "', logdate= now()";
//							echo $usegusql;
                            $conn_cms3->query($usegusql);

                            $update_sql = "UPDATE cmsdb.paradian_reservation  SET USEGU = '2' WHERE USEGU = '1' AND BARCODE = '".$itemreq[0]."'";
                            $conn_rds->query($update_sql);


                        } else if ($row->usegu == "2"  && $row->state == "예약완료"){
                            $useg_sparo_usql = "update ordermts set usegu = '2' where  orderno = '".$_data[0]['ORDER_NO']."' and  barcode_no = '".$itemreq[0]."' limit 1 ";
                            $conn_cms3->query($useg_sparo_usql);

                            $update_sql = "UPDATE cmsdb.paradian_reservation  SET USEGU = '2' WHERE BARCODE = '".$itemreq[0]."'";
                            $conn_rds->query($update_sql);

                            $json_result['status'] = '2110';
                        } else if ($row->state == "취소"){
                            $json_result['status'] = '2020';
                        }
                    }
                }

            } else if ($_data[0]['STATUS'] == "CANCEL"){
                $json_result['status'] = '2020';
            } else {
                $json_result['status'] = '2000';
            }
        }

        $res = json_encode($json_result);

        break;

    default:
}

//$logsql = "insert cmsdb.extapi_log_para set apinm='para',tran_id='$tranid', ip='" . get_ip_temp() . "', logdate= now(), apimethod='$itemreq[1]', querystr='" . $itemreq[0] . "'";
$logsql = "insert cmsdb.extapi_log_para set apinm='para',tran_id='$tranid', ip='" . get_ip_temp() . "', logdate= now(), header='$_log_header', apiresult='$res' , apimethod='$itemreq[1]', querystr='" . $itemreq[0] . "'";
$conn_rds->query($logsql);
echo $res;

fwrite($fp, date("Y-m-d H:i:s")." ".print_r($res, true));
fwrite($fp, "==================================================\n");
fclose($fp);


function get_ip_temp(){

    if(empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
        $ip= $_SERVER["REMOTE_ADDR"].",";
    }else{
        $ip= $_SERVER["HTTP_X_FORWARDED_FOR"];
    }

    $res = explode(",",$ip);

    return trim($res[0]);
}
// 랜덤 스트링
function genRandomStrtemp($length = 10)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function __usecouponno($no){
    // 쿠폰 사용처리
    $curl = curl_init();
    $url = "http://172.31.30.15:3040/use/".$no;
    // $url = "http://115.68.42.2:3040/use/".$no; // 바꿨더니 오히려 작동이 되지 않아서 원래 것 사용 - 21.09.15. Jason
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
