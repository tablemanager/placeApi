<?php
/**
 * 생성자: JAMES
 * 마지막 수정 : JAMES
 * 생성일: 2019-07-22
 * 수정일: 2019-07-05
 * 사용 유무: release (test, release,inactive,dev)
 * 파일 용도: 위즈돔(이버스) API 연동
 * 설명 : https://docs.google.com/document/d/17wfmtUD1OS7pe4z-b-_Uiqo7v-F94K3YkWkQZW2YdCg/edit
 */

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


$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

// 파라미터
//$itemreq = explode("/",$para);

$itemreq[0] = $_GET['barcode'];
$itemreq[1] = $_GET['method'];

$_log_header = "S";

switch ($itemreq[1])
{
    case 'use':
        $_date = date("Y-m-d H:is");
        $_date2 = date("Y-m-d");

        $_use_search = "SELECT * FROM ordermts  WHERE barcode_no = '".$itemreq[0]."' ";

        $itemUseResult = $conn_cms3->query($_use_search);
        $jj= 0;
        while($row = $itemUseResult->fetch_object()){
            $jj++;
            if ($row->usegu == "1"){
                $json_result['branch_cd'] = '1000';
                $json_result['status'] = '2000';
                $json_result['method'] = $itemreq[1];
                $json_result['barcode'] = $itemreq[0];
                $_log_header = "use";
            }
            if ($row->state != "예약완료"){
                $json_result['branch_cd'] = '1000';
                $json_result['status'] = '2000';
                $json_result['method'] = $itemreq[1];
                $json_result['barcode'] = $itemreq[0];
                $_log_header = "cancel";
            }


        }

        if ($jj == 0){
            $_use_search_qry = "SELECT * FROM ordermts  WHERE grmt_id in('3649','3651','3625')  AND  barcode_no LIKE '%".$itemreq[0]."%'";
            $itemUseResultOpen = $conn_cms3->query($_use_search_qry);
            while($row = $itemUseResultOpen->fetch_object()){
                if ($row->state != "예약완료"){
                    $json_result['branch_cd'] = '1000';
                    $json_result['status'] = '2000';
                    $json_result['method'] = $itemreq[1];
                    $json_result['barcode'] = $itemreq[0];
                    $_log_header = "cancel";
                }
            }
        }

        if ($json_result['status'] == "2000"){

            $res = json_encode($json_result);

            $tranid = date("Ymd") . genRandomStrtemp(10); // 트렌젝션 아이디
            $logsql = "insert cmsdb.extapi_log_para set apinm='paradise',tran_id='$tranid', ip='" . get_ip_temp() . "', logdate= now(), header ='$_log_header', apiresult='$res' , apimethod='$itemreq[1]', querystr='" . $itemreq[0] . "'";
//$logsql = "insert cmsdb.extapi_log set apinm='paradise',tran_id='$tranid', ip='" . get_ip_temp() . "', logdate= now(), apimethod='$apimethod', querystr='" . $para . "', header='" . json_encode($apiheader) . "', body='" . $itemreq[0].$itemreq[1] . "'";
//echo $logsql;
            $conn_rds->query($logsql);

            echo $res;
            exit;
        }


        $_barcode_search = "select * from paradian_reservation where BARCODE = '".$itemreq[0]."' ";

        $itemListResult = $conn_rds->query($_barcode_search);

        $json_result['status'] = '2000';

        while($row = $itemListResult->fetch_object()){

            if ($row->SYNC_MSG == "/IF_EXT_PSS_002?PROC_CONN_STATUS=S"){
                if ($row->USEGU == "1") {
                    $json_result['status'] = '2000';

                    $_log_header = "use2";

                } else {
                    if ($row->STATUS == "RESERVE"){
//				if ($row->AVAIL_TDATE >  $_date){
                        if ($row->AVAIL_TDATE >  $_date && $row->AVAIL_FDATE <  $_date){
                            $json_result['status'] = '1000';
                            $update_sql = "UPDATE cmsdb.paradian_reservation  SET USEGU = '1' WHERE BARCODE = '".$itemreq[0]."'";
                            $conn_rds->query($update_sql);
                            __usecouponno($itemreq[0]);
                        } else {
                            if (!empty($row->AVAIL_USEDATE) && $row->AVAIL_USEDATE != null){
                                if ($row->AVAIL_USEDATE >=  $_date2 && $row->AVAIL_USEDATE <=  $_date2){
                                    $json_result['status'] = '1000';
                                    $update_sql = "UPDATE cmsdb.paradian_reservation  SET USEGU = '1' WHERE BARCODE = '".$itemreq[0]."'";
                                    $conn_rds->query($update_sql);
                                    __usecouponno($itemreq[0]);
                                } else {
                                    $json_result['status'] = '2000';
                                    $_log_header = "exp_date1";
                                }
                            } else {
                                $json_result['status'] = '2000';
                                $_log_header = "exp_date2";
                            }
                        }

                    } else {
                        $json_result['status'] = '2000';
                        $_log_header = "cancel3";
                    }
                }
            } else {
                $json_result['status'] = '2000';
                $_log_header = "resex";
            }


        }

        if (empty($json_result['status'])){
            $json_result['status'] = "2000";
            $_log_header = "unknown";
        }

        $json_result['branch_cd'] = '1000';
        $json_result['method'] = $itemreq[1];
        $json_result['barcode'] = $itemreq[0];

        $res = json_encode($json_result);

        break;
    case 'unuse':
        $json_result['branch_cd'] = '1000';
        $json_result['method'] = $itemreq[1];
        $json_result['status'] = '1000';
        $json_result['barcode'] = $itemreq[0];

        $_curl = new paradise();
        $_model = new paradise_model();

        $_fillter['BARCODE'] = $json_result['barcode'];

        $_data = $_model->getParadiseCoupon($_fillter);

        if (count($_data) == 0){
            $json_result['status'] = '2000';
            $_log_header = "no_rese";
        } else {
            if ($_data[0]['STATUS'] == "RESERVE"){

                if ($_data[0]['AVAIL_TDATE'] < date('Y-m-d')){
                    $res = json_encode(array(
                        "RESULT"=>false,
                        "MSG"=>"유효기간 지남"
                    ));

                    $_log_header = "exp_date4";
                } else {
                    $_use_search = "SELECT * FROM ordermts  WHERE barcode_no = '".$itemreq[0]."' ";

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

                            $useg_sparo_usql = "update ordermts set usegu = '2' where  orderno = '".$_data[0]['ORDER_NO']."' and  barcode_no = '".$itemreq[0]."' limit 1 ";
                            $conn_cms3->query($useg_sparo_usql);

                            $usegusql = "insert ordermts_recovery set ch_id='$row->ch_id', sync_flag= 'N' ,couponno = '$_coupon_data' ,order_id='$row->id', log_ip='" . get_ip_temp() . "', logdate= now()";
//							echo $usegusql;
                            $conn_cms3->query($usegusql);

                            $update_sql = "UPDATE cmsdb.paradian_reservation  SET USEGU = '2' WHERE BARCODE = '".$itemreq[0]."'";
                            $conn_rds->query($update_sql);

                        } else if ($row->usegu == "2"){
                            $json_result['status'] = '1000';
                            $_log_header = "usegu2";
                        }
                    }
                }

            } else if ($_data[0]['STATUS'] == "CANCEL"){
                $json_result['status'] = '2000';
                $_log_header = "cancel5";
            }
        }

        $res = json_encode($json_result);

        break;

    default:
}

/*
 * 2020-05-14 출근해서 오픈 처리
 * $_status = $json_result['status'];
$logsql = "insert cmsdb.extapi_log_para set apinm='paradise' ,apiresult='$_status' ,tran_id='$tranid', ip='" . get_ip_temp() . "', logdate= now(), apimethod='$itemreq[1]', querystr='" . $itemreq[0] . "'";
$conn_rds->query($logsql);
 */

$tranid = date("Ymd") . genRandomStrtemp(10); // 트렌젝션 아이디
$logsql = "insert cmsdb.extapi_log_para set apinm='paradise',tran_id='$tranid', ip='" . get_ip_temp() . "', logdate= now(), header='$_log_header', apiresult='$res' , apimethod='$itemreq[1]', querystr='" . $itemreq[0] . "'";
//$logsql = "insert cmsdb.extapi_log set apinm='paradise',tran_id='$tranid', ip='" . get_ip_temp() . "', logdate= now(), apimethod='$apimethod', querystr='" . $para . "', header='" . json_encode($apiheader) . "', body='" . $itemreq[0].$itemreq[1] . "'";
//echo $logsql;
$conn_rds->query($logsql);

echo $res;
exit;

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
