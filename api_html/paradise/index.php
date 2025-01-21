<?php

include '/home/sparo.cc/hanwha_script/lib/class/class.lib.common.php';
include '/home/sparo.cc/paradise_script/class/class.paradise.php';
include '/home/sparo.cc/paradise_script/class/paradise_model.php';

require_once('/home/placedev/cmsapps/common/conf/dbconn.conf.php');

header("Content-type:application/json");

// ACL 확인
$accessip = array("115.68.42.2",
    "115.68.42.8",
    "115.68.42.130",
    "52.78.174.3",
    "106.254.252.100",
    "115.68.182.165",
    "13.124.139.14",
    "218.39.39.190",
    "13.209.232.254",
    "13.124.215.30",
    "13.209.184.223"
);

if(!in_array(get_ip(),$accessip)){
    header("HTTP/1.0 401 Unauthorized");
    $res = array("RESULT"=>false,"MSG"=>"아이피 인증 오류 : ".get_ip());
    echo json_encode($res);
    exit;
}

$para = $_GET['val']; // URI 파라미터

$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders();            // http 헤더

$jsonreq = trim(file_get_contents('php://input'));
$data = json_decode($jsonreq,TRUE);

$_curl = new paradise();
$_model = new paradise_model();
//echo $para."===";
switch ($para){

    case 'order':

		$_fillter['BARCODE'] = $data['BARCODE'];

		$_data = $_model->getParadiseCoupon($_fillter);

		$_req_ary = array_reduce($_data, 'array_merge', array());

        if ($_fillter['BARCODE']== "" ||$_fillter['BARCODE'] == null ){
            $res = json_encode(array(
                "RESULT"=>false,
                "MSG"=>"없는 바코드"
            ));
        } else {
            if ($_model->setParadiseUpdateRese($_fillter['BARCODE'] , $data)){

                $_req_data = $_model->getParadiseCoupon($_fillter);
                $_req_ary = array_reduce($_req_data, 'array_merge', array());

                $_req_ary['SALESTYPE_DESC'] = $data['SALESTYPE_DESC'];
                $_res_data = $_curl->setParadiseReseBook($_req_ary);

                $_model->setParadiseUpdateResponse($_fillter['BARCODE'] ,$_res_data,"RESERVE");

                $conn_rds->query("set names utf8");
                $_barcode_update = "UPDATE paradian_reservation SET GSTNAME = '".$data['GSTNAME']."' WHERE BARCODE = '". $data['BARCODE']."' limit 1" ;
                $conn_rds->query($_barcode_update);

                $res = json_encode(array(
                    "RESULT"=>true,
                    "MSG"=>"예약 성공",
                    "response"=>$_res_data
                ));

            } else {
                $res = json_encode(array(
                    "RESULT"=>false,
                    "MSG"=>"업데이트 실패"
                ));
            }
        }
//			echo mb_detect_encoding($data['GSTNAME']);

        break;

    case 'info':

        $_fillter['BARCODE'] = $data['BARCODE'];


        $_use_search = "SELECT * FROM ordermts  WHERE barcode_no = '".$_fillter['BARCODE']."' ";
        $itemUseResult = $conn_cms3->query($_use_search);
        $is_use = false;
        $is_state = false;
        $_state = "";
        while($row = $itemUseResult->fetch_object()){
            $_state = $row->state;
            if ($row->usegu == "1"){
                $is_use = true;
            }
            if ($row->state == "예약완료"){
                $is_state = true;
            } else if ($row->state == "취소"){
                $is_state = false;
            }
        }


        $_data = $_model->getParadiseCoupon($_fillter);
        $rese_use = "";
        $rese_state = false;
        if ($_data[0]['STATUS'] == "" || $_data[0]['STATUS'] == null){
            $is_state = false;
        } else {
            if ($_data[0]['STATUS'] == "CANCEL"){
                $rese_use  = "cancel";
            } else if ($_data[0]['STATUS'] == "RESERVE") {
                $rese_use  = "reserve";
            }
        }
        $rese_use = '';

        $res = json_encode(array(
            "use"=>$is_use,
            "state"=>$_state,
            "para_state"=>$rese_use
        ));

        break;
    case 'cancel':

		$_fillter['BARCODE'] = $data['BARCODE'];

		$conn_rds->query("set names utf8");

		$_barcode_search = "select * from paradian_reservation where BARCODE = '".$_fillter['BARCODE']."' ";

		$itemListResult = $conn_rds->query($_barcode_search);


//		$cols = Array ("BRANCH_CD", "OTA_CD", "OTA_RESNO", "BARCODE", "STATUS", "GSTNAME", "MOBILE" ,"SALES_TIME"  ,"SALES_CNT"
//		,"SALESTYPE","SALESTYPE_DESC","AVAIL_FDATE","AVAIL_TDATE","DATA_FLAG","REG_DTIME" ,"ORDER_NO","SYNC_FLAG" ,"SELL_CODE" ,"ITEM_CODE");

		while($row = $itemListResult->fetch_object()){
			$_data[0]['STATUS'] = $row->STATUS;
			$_data[0]['OTA_CD'] = $row->OTA_CD;
			$_data[0]['OTA_RESNO'] = $row->OTA_RESNO;
			$_data[0]['GSTNAME'] = $row->GSTNAME;
			$_data[0]['MOBILE'] = $row->MOBILE;
			$_data[0]['SALES_TIME'] = $row->SALES_TIME;
			$_data[0]['SALES_CNT'] = $row->SALES_CNT;
			$_data[0]['SALESTYPE'] = $row->SALESTYPE;
			$_data[0]['SALESTYPE_DESC'] = $row->SALESTYPE_DESC;
			$_data[0]['AVAIL_FDATE'] = $row->AVAIL_FDATE;
			$_data[0]['AVAIL_TDATE'] = $row->AVAIL_TDATE;
			$_data[0]['DATA_FLAG'] = $row->DATA_FLAG;
			$_data[0]['REG_DTIME'] = $row->REG_DTIME;
			$_data[0]['ORDER_NO'] = $row->ORDER_NO;
			$_data[0]['SYNC_FLAG'] = $row->SYNC_FLAG;
			$_data[0]['BRANCH_CD'] = $row->BRANCH_CD;
		}
		$_data[0]['BARCODE'] = $_fillter['BARCODE'];

//		$_data = $_model->getParadiseCoupon($_fillter);

		if ($_data[0]['STATUS'] == "" || $_data[0]['STATUS'] == null){
			$res = json_encode(array(
				"RESULT"=>false,
				"MSG"=>"예약건없음"
			));
		} else {

			if ($_data[0]['AVAIL_TDATE'] < date('Y-m-d', strtotime('+1 days'))){
				$res = json_encode(array(
					"RESULT"=>false,
					"MSG"=>"유효기간 지남"
				));
			} else {

				$_req_ary = array_reduce($_data, 'array_merge', array());
				$_res_data = $_curl->setParadiseReseCancel($_req_ary);
				$_model->setParadiseUpdateResponse($_fillter['BARCODE'] ,$_res_data,"CANCEL");

				$res = json_encode(array(
					"RESULT"=>true,
					"MSG"=>"성공",
					"response"=>$_res_data
				));

			}
		}

        break;

    default:
        $res = json_encode(array(false, "API 타입이 존재하지 않습니다."));
        break;
}

echo $res;

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
