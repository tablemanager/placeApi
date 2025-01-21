<?php


// 한화 힙스 연동 인터페이스
//error_reporting(0);

date_default_timezone_set('Asia/Seoul');
require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
$conn_rds->query("set names utf8");
$conn_cms3->query("set names utf8");

header("Content-type:application/json");

$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

// 파라미터
$itemreq = explode("/",$para);
$jsonReq = json_decode(trim(file_get_contents('php://input')), true);

if(get_ip() == "106.254.252.100"){
	//echo json_encode($_res);
}else{
	//echo json_encode($_res);
}

$logStr = "";
switch($apimethod){
	case 'POST':
		// 주문등록
		$logStr .="주문등록";

		$res = setOrder($itemreq[0], $jsonReq);
    header("HTTP/1.0 200");
    echo json_encode($res);
    exit;
		break;
	case 'PATCH':
					// 주문취소
			$logStr .="주문취소";
			$res = cancelOrder($itemreq[0]);
      header("HTTP/1.0 200");
      echo json_encode($res);
      exit;
	break;
  case 'GET':
					// 주문취소
			$logStr .="주문조회";
			$res = getOrder($itemreq[0]);
      header("HTTP/1.0 200");
      echo json_encode($res,JSON_UNESCAPED_UNICODE);
      exit;
	break;
	}


	function getItemInfo($itemcode){
	  global $conn_cms;

	  $cpqry = "SELECT item_cd FROM CMSDB.CMS_ITEMS WHERE item_id = '$itemcode' limit 1";
	  $cprow = $conn_cms->query($cpqry)->fetch_object();

	  return $cprow->item_cd;
	}

// 주문등록
function setOrder($itemcode, $jsonReq){

			global $conn_cms;
			global $conn_rds;

			$cpcfg = getItemInfo($itemcode);

			$CORP_CD = "1000";
			$CONT_NO = "20001446";

			$sdate = str_replace("-","",$jsonReq['sDate']);
			$edate = str_replace("-","",$jsonReq['eDate']);
			$usernm=  $jsonReq['cusNm'];
			$userhp = $jsonReq['cusPhone'];
			$menucode = $cpcfg;
			$chorderno = $jsonReq['bookingId'];
			$qty =  $jsonReq['cpQty'];

			if(empty($qty)) $qty =1;
			
			// 기존에 발권된 쿠폰이 있을경우
			$cpqry = "SELECT REPR_CPON_INDICT_NO from cmsdb.hips_orders where ORDERNO = '$chorderno' limit 1";
	  	$cpres = $conn_rds->query($cpqry);
			$cprow = $cpres->fetch_object();


			if($cpres->num_rows != 0){
					return array("couponNo"=>$cprow->REPR_CPON_INDICT_NO);
			}

			$_REQ['SystemHeader'] = getSystemHeader("order");
			$_REQ['TransactionHeader'] = getTransactionHeader($CORP_CD);
			$_REQ['MessageHeader'] = null;
			$_REQ['Data'] = setDs_input($CORP_CD ,$CONT_NO ,$menucode ,$usernm , $userhp, $qty);

			$_syncres = getResults(json_encode($_REQ));


			$CORP_CD = $_syncres->Data->ds_output[0]->CORP_CD;
			$CPON_MAST_NO = $_syncres->Data->ds_output[0]->CPON_MAST_NO;
			$CPON_MAST_SEQ = $_syncres->Data->ds_output[0]->CPON_MAST_SEQ;
			$REPR_CPON_SEQ = $_syncres->Data->ds_output[0]->REPR_CPON_SEQ;
			$REPR_CPON_NO = $_syncres->Data->ds_output[0]->REPR_CPON_NO;
			$REPR_CPON_INDICT_NO = $_syncres->Data->ds_output[0]->REPR_CPON_INDICT_NO;
			$GOODS_NO = $_syncres->Data->ds_output[0]->GOODS_NO;
			$GOODS_NM = $_syncres->Data->ds_output[0]->GOODS_NM;
			$CPON_APPLC_QTY = $_syncres->Data->ds_output[0]->CPON_APPLC_QTY;
			$PRVS_NO = $_syncres->Data->ds_output[0]->PRVS_NO;
			$RSRV_NO = $_syncres->Data->ds_output[0]->RSRV_NO;
			$CALC_DIV_CD = $_syncres->Data->ds_output[0]->CALC_DIV_CD;
			$CALC_DIV_AMT = $_syncres->Data->ds_output[0]->CALC_DIV_AMT;
			$CALC_DIV_RATE = $_syncres->Data->ds_output[0]->CALC_DIV_RATE;
			$VALI_PRID_STRT_DATE = $_syncres->Data->ds_output[0]->VALI_PRID_STRT_DATE;
			$VALI_PRID_END_DATE = $_syncres->Data->ds_output[0]->VALI_PRID_END_DATE;

			$_isql = "insert cmsdb.hips_orders set
			CMS_ITEMCD = '$itemcode',
			QTY = '$qty',
			ORDERNO = '$chorderno',
			CORP_CD = '$CORP_CD',
			CPON_MAST_NO = '$CPON_MAST_NO',
			CPON_MAST_SEQ= '$CPON_MAST_SEQ',
			REPR_CPON_SEQ= '$REPR_CPON_SEQ',
			REPR_CPON_NO= '$REPR_CPON_NO',
			REPR_CPON_INDICT_NO= '$REPR_CPON_INDICT_NO',
			GOODS_NO= '$GOODS_NO',
			GOODS_NM= '$GOODS_NM',
			CPON_APPLC_QTY= '$CPON_APPLC_QTY',
			PRVS_NO= '$PRVS_NO',
			RSRV_NO= '$RSRV_NO',
			CALC_DIV_CD= '$CALC_DIV_CD',
			CALC_DIV_AMT= '$CALC_DIV_AMT',
			CALC_DIV_RATE= '$CALC_DIV_RATE',
			VALI_PRID_STRT_DATE= '$VALI_PRID_STRT_DATE',
			VALI_PRID_END_DATE = '$VALI_PRID_END_DATE'
			";
			$conn_rds->query($_isql);

			return array("couponNo"=>$REPR_CPON_INDICT_NO);

}

function cancelOrder($couponno){
	global $conn_cms;
	global $conn_rds;
	global $conn_cms3;

  $CORP_CD = "1000";
  $CONT_NO = "20001446";

  $REPR_CPON_INDICT_NO = $couponno;

  $_REQ['SystemHeader'] = getSystemHeader("cancel");
  $_REQ['TransactionHeader'] = getTransactionHeader($CORP_CD);
  $_REQ['MessageHeader'] = null;

  $_REQ['Data'] =setDs_cancel($CORP_CD ,$CONT_NO,$REPR_CPON_INDICT_NO);


  $_syncres = getResults(json_encode($_REQ));;

  return array("couponNo"=>$REPR_CPON_INDICT_NO,
  "useCode"=>$_syncres->Data->ds_result[0]->RESULT_CODE,
  "codeMsg"=>$_syncres->Data->ds_result[0]->RESULT_MSG);
}

function getOrder($couponno){
	global $conn_cms;
	global $conn_rds;
	global $conn_cms3;

    $CORP_CD = "1000";
    $CONT_NO = "20001446";

    $cpqry = "SELECT * FROM cmsdb.hips_orders WHERE REPR_CPON_INDICT_NO = '$couponno' limit 1";
	  $row = $conn_rds->query($cpqry)->fetch_object();

    $ISSUE_DATE = str_replace("-","",substr($row->REG_DATE,0,10));
    $REPR_CPON_SEQ = $row->REPR_CPON_SEQ;
    $REPR_CPON_INDICT_NO = $couponno;

    $_REQ['SystemHeader'] = getSystemHeader("search");
    $_REQ['TransactionHeader'] = getTransactionHeader($CORP_CD);
    $_REQ['MessageHeader'] = null;
    $_REQ['Data'] = getDs_use($CORP_CD,$CONT_NO,$ISSUE_DATE ,$REPR_CPON_SEQ ,$REPR_CPON_INDICT_NO);


    $_syncres = getResults(json_encode($_REQ));;

    return array("couponNo"=>$REPR_CPON_INDICT_NO,
    "useCode"=>$_syncres->Data->ds_result[0]->REPR_CPON_STAT_CD,
    "codeMsg"=>$_syncres->Data->ds_result[0]->REPR_CPON_STAT_NM,
    "useDate"=>$_syncres->Data->ds_result[0]->CLLT_DS);

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

function genRandomStr($length = 10) {
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}

function genRandomNum($length = 10) {
	$characters = '0123456789';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}

function phone_number($number) {
    $number = str_replace(" ","",$number);
    $number = str_replace("+82","",$number);
    $number = str_replace("(","",$number);
    $number = str_replace(")","",$number);

    return preg_replace("/(^02.{0}|^01.{1}|[0-9]{3})([0-9]+)([0-9]{4})/", "$1-$2-$3", $number);
}


function setDs_cancel($CORP_CD ,$CONT_NO,$REPR_CPON_INDICT_NO){
  $_req = array('CORP_CD'=>$CORP_CD
      , 'CONT_NO' => $CONT_NO
      ,'REPR_CPON_INDICT_NO' => $REPR_CPON_INDICT_NO
  );
  $ds_input['ds_input'][] = $_req;
  return $ds_input;
}

function getDs_use($CORP_CD ,$CONT_NO ,$ISSUE_DATE,$REPR_CPON_SEQ,$REPR_CPON_INDICT_NO){

  $_req = array('CORP_CD'=>$CORP_CD
      , 'CONT_NO' => $CONT_NO
      , 'ISSUE_DATE' => $ISSUE_DATE
      ,'REPR_CPON_SEQ'=> $REPR_CPON_SEQ
      ,'REPR_CPON_INDICT_NO' => $REPR_CPON_INDICT_NO
  );
  $ds_input['ds_input'][] = $_req;
  return $ds_input;
}


function setDs_input($CORP_CD ,$CONT_NO ,$_good_no ,$usernm , $userhp, $qty){
  $hp = explode("-",$userhp);

	if(!$qty) $qty=1;

	// 통합 발권 여부 판단
	if($qty > 1) {
		$unity = "Y";
	}else{
		$unity = "N";
	}

  $_req = array('CORP_CD'=>$CORP_CD
      , 'CONT_NO' =>$CONT_NO
      ,'SEQ' =>"1"
      ,'ISSUE_DATE' => date("Ymd")
      ,'GOODS_NO' => $_good_no
      ,'ISSUE_QTY' => $qty //고객 발행 수량 일단 1 고정
      ,'UNITY_ISSUE_YN' => $unity  //'Y' : 통합발권, 'N' : 개별 발권
      ,'RCVER_NM' => $usernm     //고객명
      ,'RCVER_TEL_NATION_NO' => '82'    //국가번호
      ,'RCVER_TEL_AREA_NO' => $hp[0]  //수신자 전화     010
      ,'RCVER_TEL_EXCHGE_NO' => $hp[1] //수신자 전화 9979
      ,'RCVER_TEL_NO' => $hp[2]      //수신자 전화            6534
  );
  $ds_input['ds_input'][] = $_req;
  return $ds_input;
}


function getSystemHeader($_mode){
  $SysTemsHeader['MCI_SSN_ID']  = "";                     //none
  //시스템 구분코드(3) + 일련번호(5)- 시스템 구분코드 : 메타에 등록된 시스템명
  //- 일련번호 : Random(5)
  $rand_num = sprintf("%05d",rand(00000,99999));
  $SysTemsHeader['TMSG_CRE_SYS_NM']  = "SIF".$rand_num;

  $SysTemsHeader['TRSC_SYNC_DV_CD']  = "S";            //fix
  $SysTemsHeader['TMSG_RSPS_DTM']  = "";          //none

  //해당 전문의 요청 시스템의 환경L(로컬) / D(개발) / R(운영)
  $SysTemsHeader['ENVR_INFO_DV_CD']  = "R";

  $SysTemsHeader['TMSG_VER_DV_CD']  = "01";   //fix
  $SysTemsHeader['REMT_IP']  = "";                          //none

switch($_mode){
  case 'order':
    $RECV_SVC_CD = "HBSSAMCPN0306";
    $INTF_ID = "SIF00HBSSAMCPN0306";
  break;
  case 'search':
    $RECV_SVC_CD = "HBSSAMCPN1100";
    $INTF_ID = "SIF00HBSSAMCPN1100";
  break;
  case 'cancel':
    $RECV_SVC_CD = "HBSSAMCPN1003";
    $INTF_ID = "SIF00HBSSAMCPN1003";
  break;
  case 'items':
    $RECV_SVC_CD = "HBSSAMCNT0114";
    $INTF_ID = "SIF00HBSSAMCNT0114";
  break;

}

/*
  HBSSAMCNT0114 : 계약사 상품조회
  HBSSAMCPN0306 : 쿠폰발행처리
  HBSSAMCPN1003 : 쿠폰발행취소
  HBSSAMCPN1100 : 대매점 회수이력 조회
*/

  $SysTemsHeader['RECV_SVC_CD']  = $RECV_SVC_CD;

  $SysTemsHeader['MCI_NODE_NO']  = "";                  //none
  $SysTemsHeader['ERR_OCC_SYS_CD']  = "";             //none

/*
  SIF00HBSSAMCNT0114 : 계약사 상품조회
  SIF00HBSSAMCPN0306 : 쿠폰발행처리
  SIF00HBSSAMCPN1003 : 쿠폰발행취소
  SIF00HBSSAMCPN1100 : 대매점 회수이력 조회
  */
  $SysTemsHeader['INTF_ID']  =  $INTF_ID;

  $rand_num = rand(1,9);
  $timestamp = date("Ymdhis");
  $SysTemsHeader['STD_TMSG_SEQ_NO']  = $timestamp;

  //YYYYMMDDHHMMSSTTT (년-월-일-시-분-초-1/1000초)
  $timestampDt = date("Ymdhis");
// 		$SysTemsHeader['FRS_RQST_DTM']  = "20181008114924791";
  $SysTemsHeader['FRS_RQST_DTM']  = $timestampDt."0000";


  //(IP v4)
  $SysTemsHeader['STN_TMSG_IP']  = "52.78.174.3";
// 		$SysTemsHeader['STN_TMSG_IP']  = $_SERVER["REMOTE_ADDR"];

  $SysTemsHeader['TRMS_ND_NO']  = "";                  //none

  //YYYYMMDDHHMMSSTTT (년-월-일-시-분-초-1/1000초) Format
  $__microtime =  date("Ymdhis");
// 		$SysTemsHeader['TMSG_RQST_DTM']  = $today.$__microtime;
  $SysTemsHeader['TMSG_RQST_DTM']  = date("YmdHist").rand(1,9);

  $SysTemsHeader['TRMS_SYS_CD']  = "SIF";                   //fix
  $SysTemsHeader['STD_TMSG_PRGR_NO']  = 00;            //fix
  $SysTemsHeader['STN_MSG_ENCP_CD']  = "0";  //fix
  $SysTemsHeader['STD_TMSG_LEN']  = "";
  $SysTemsHeader['STN_TMSG_ERR_CD']  = "";          //none
  $SysTemsHeader['LANG_CD']  = "KO";          //fix
  $SysTemsHeader['RQST_RSPS_DV_CD']  = "S";     //fix
  $SysTemsHeader['PRCS_RSLT_CD']  = "";              //none
  $SysTemsHeader['FILLER']  = "";                              //none
  $SysTemsHeader['FRS_RQST_SYS_CD']  = "SIF";            //fix
  $SysTemsHeader['STN_MSG_COMP_CD']  = "0";    //fix

  // 맥어드레스
// 		$SysTemsHeader['STN_TMSG_MAC']  = "8C-16-45-D4-21-1B";
  $SysTemsHeader['STN_TMSG_MAC']  = "f0:2f:4b:07:7c:98";

  //전문작성일 (YYYYMMDD)
  $SysTemsHeader['TMSG_WRTG_DT']  =  date("Ymd");

  return $SysTemsHeader;

}

function getTransactionHeader($CORP_CD){
  $setTransactionHeader['OSDE_TR_PRG_NO']  = "";
  $setTransactionHeader['OSDE_TR_ORG_CD']  = "";
  $setTransactionHeader['OSDE_TR_RUTN_ID']  = "";
  $setTransactionHeader['SYSTEM_TYPE']  = "HABIS";
  $setTransactionHeader['OSDE_TR_JOB_CD']  = "";
  $setTransactionHeader['WRKR_NO']  = "l1711019";
  $setTransactionHeader['OSDE_TR_MSG_CD']  = "";
  $setTransactionHeader['OSDE_TR_CD']  = "";
  $setTransactionHeader['LOC_CD']  = "";
  $setTransactionHeader['BRANCH_NO']  = "";
  $setTransactionHeader['SCREEN_ID']  = "";
  $setTransactionHeader['CMP_NO']  = "";
  $setTransactionHeader['FILLER']  = "";
  $setTransactionHeader['SCREEN_SHORTEN_NO']  = "";
  $setTransactionHeader['CORP_CD']  = $CORP_CD;
  $setTransactionHeader['STN_MSG_TR_TP_CD']  = "O";

  return $setTransactionHeader;
}

function getResults($_json){

  $curl = curl_init();

  curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://exgate.hanwha-insight.com/iGate/SIF/json.jdo',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS =>$_json,
    CURLOPT_HTTPHEADER => array(
      'Content-Type: application/json',
    ),
  ));

  $response = curl_exec($curl);

  curl_close($curl);
  return json_decode($response);


}
?>
