<?php

$sip = 'test_ip';
//서버 아이피 제한 시작

//서버 아이피 제한 종료
require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');

require_once("./cms.lib.php");

header('Content-type: application/xml');

#######################
$schid = "SPV";
$chid = "SPV";
$chnm ="아산스파비스";
#######################

/*
http://api.placem.co.kr:1015/SPV/req.php?ORDER_NO=20160319_16106&COUPON_NO=20160419GYQJ0EHKW613C6P&STATUS_DIV=I&RESULT_DATE=2016-04-21 14:12:34
http://api.placem.co.kr:1015/SPV/req.php?ORDER_NO=20160421_00055&COUPON_NO=2016042146BNQY8NWZ9AZXY&STATUS_DIV=I&RESULT_DATE=2016-04-22 14:12:34
http://api.placem.co.kr:1015/SPV/req_20160614.php?ORDER_NO=20160614W07334158281&COUPON_NO=W07334158281&STATUS_DIV=I&RESULT_DATE=2016-06-14 00:00:34

이아름
http://api.placem.co.kr:1015/SPV/req_20160614.php?
ORDER_NO=20160614W07334158281&
COUPON_NO=W07334158281&
STATUS_DIV=I&
RESULT_DATE=2016-06-14 00:00:34

http://api.placem.co.kr:1015/SPV/req_20160614.php?order_no=20160614W07334158281&coupon_no=W07334158281&status_div=I&result_date=2016-06-14+00%3A00%3A34
http://api.placem.co.kr:1015/SPV/req_20160614.php?order_no=20160614W07334158281&coupon_no=W07334158281&status_div=C&result_date=2016-06-14+00%3A00%3A34


https://gateway.sparo.cc/extra/asanspvs/req.php?order_no=20210521PM247665155546&coupon_no=P20210521070320KX45I3YZE51&status_div=I&result_date=2021-05-24%2016:00:10
https://gateway.sparo.cc/extra/asanspvs/req.php?order_no=20210521PM247665155546&coupon_no=P20210521070320KX45I3YZE51&status_div=C&result_date=2021-05-24%2016:00:20

*/
/*********************************************************
ORDER_NO	A사 주문번호	Varchar	37	주문번호 = 회사구분(6) + 자체번호(31)
COUPON_NO	A사 쿠폰번호[]	Varchar	37	쿠폰번호 = 회사구분(6) + 자체번호(31)
STATUS_DIV	상태값[]	Char	1	사용(I), 취소(C)
RESULT_DATE	사용일시[]	Varchar	20	예) 2016-02-15 16:40:15
============================================================
RTN_DIV	성공여부	Char	1	성공(S), 실패(F)
RTN_MSG	결과 메시지	Varchar	250
*********************************************************/

$ORDER_NO = '20210521PM247665155546';		//주문번호
$AR_COUPON_NO = explode(",",'P20210521070320KX45I3YZE51');		//쿠폰번호 (','로 구분)
//$AR_STATUS_DIV = explode(",",trim($_GET['status_div']));	//상태값 (','로 구분)
//$AR_RESULT_DATE = explode(",",trim($_GET['result_date']));	//사용일시 (','로 구분)

/*
$authres = $conn_cms3->query($authqry);
$authrow = $authres->fetch_object();
*/

$STATUS_DIV = trim('C');	//상태값 (','로 구분)
$RESULT_DATE = trim('2021-05-24 16:00:20');	//사용일시 (','로 구분)

$RTN_DIV="";	//성공(S), 실패(F)
$RTN_MSG="";	//결과 메시지

$tdate = date('Ymd');

// 로그
$qrystr = "test-tony 20210521PM247665155546 P20210521070320KX45I3YZE51 ".$STATUS_DIV;
$qrysql = "insert pcmsdb.log_asanorders set mode = '$STATUS_DIV', qrystr = '$qrystr', regdate = now(), ip ='$sip'";
@$conn_cms->query($qrysql);



// 필수 파라미터 확인 (고객명, 핸드폰도 필요함)
if (!$ORDER_NO or !$AR_COUPON_NO or !$STATUS_DIV or !$RESULT_DATE){
	echo makexml("F","Parameter Error_1");
	exit;
}

// //데이터 수량 확인 -> 하지 않기로 함.(어느순간부터 쿠폰번호만 배열로 주고 있음)
// if( count($AR_COUPON_NO) != count($STATUS_DIV)
// ||  count($AR_COUPON_NO) != count($RESULT_DATE)
// ||  count($STATUS_DIV) != count($RESULT_DATE)
// ){
// 	$AR_COUPON_NO_cnt = count($AR_COUPON_NO);
// 	$STATUS_DIV_cnt = count($STATUS_DIV);
// 	$RESULT_DATE_cnt = count($RESULT_DATE);
// 	//echo makexml("F","Parameter Error_2");
// 	//exit;
// }


########################
$check_coupon = true;	//기본 참
$return_msg = "";		//메세지
########################

#주문 및 쿠폰 상태 조회

try {
//주문 조회
$Oqry="SELECT * FROM pcmsdb.asan_orders where ORDER_NO = '$ORDER_NO' order by id desc limit 1";
$Ores=$conn_cms->query($Oqry);
$O_rows = $Ores->num_rows;

if($O_rows < 1){
	$check_coupon = false;
	$return_msg .= "$ORDER_NO : ORDER_NO Error,";
}else{

	$Orow = $Ores->fetch_object();

	//상품정보
	$getItemgu = "SELECT * FROM `asan_items` where pcms_id = '{$Orow->PCMS_itemmt_id}' and ITEMCODE = '{$Orow->GOODS_CODE}' and state = 'Y' order by id desc limit 1";

	$getItemrow = $conn_cms->query($getItemgu)->fetch_object();
	$synctype = $getItemrow->synctype;

	switch($synctype){
		case 'PCMS':
		case 'COUPANG':
			//PCMS 주문조회
			$PCMSqry="SELECT * FROM spadb.ordermts where id = '".$Orow->PCMS_orderid."' limit 1";

			$PCMSrow = $conn_cms3->query($PCMSqry)->fetch_object();
			$PCMSstate = $PCMSrow->state;

			if($PCMSstate == '취소'){
				$check_coupon = false;
				$return_msg .= "$ORDER_NO : Canceled Order,";
			}
		break;
		case 'NSPARO_SC':
		case 'NSPARO':
			//뉴스파로 주문조회
			$NSPRqry="SELECT * FROM spadb.ordermts where id = '".$Orow->PCMS_orderid."' limit 1";

			$NSPRrow = $conn_cms3->query($NSPRqry)->fetch_object();
			$NSPRstate = $NSPRrow->state;

			if($NSPRstate == '취소'){
				$check_coupon = false;
				$return_msg .= "$ORDER_NO : Canceled Order,";
			}
		break;
	}

	for ($j = 0; $j < count($AR_COUPON_NO); $j++) {
		$COUPON_NO = $AR_COUPON_NO[$j];		//쿠폰번호
		//$STATUS_DIV = $AR_STATUS_DIV[$j];	//상태값

		//쿠폰 조회
		$SSqry="SELECT * FROM pcmsdb.asan_coupons where ORDER_NO = '$ORDER_NO' and COUPON_NO = '$COUPON_NO' limit 1";
		$SSres=$conn_cms->query($SSqry);
		$ss_rows = $SSres->num_rows;

		if($ss_rows < 1 ){
			$check_coupon = false;
			$return_msg .= "$COUPON_NO : COUPON_NO Error,";
		}else{
			$SSrow = $SSres->fetch_object();
			$state = $SSrow->STATE;

			switch($STATUS_DIV){
				case 'I':	//사용
					switch($state)
					{
						case 'Y':
							$check_coupon = false;
							$return_msg .= "$COUPON_NO : Used Coupon,";
						break;
						case 'C':
							$check_coupon = false;
							$return_msg .= "$COUPON_NO : Canceled Coupon,";
						break;
					}
				break;

				case 'C': //사용 취소
					switch($state)
					{
						case 'N':
							$check_coupon = false;
							$return_msg .= "$COUPON_NO : Unused Coupon,";
						break;
						case 'C':
							$check_coupon = false;
							$return_msg .= "$COUPON_NO : Canceled Coupon,";
						break;
					}
				break;
				default:
					$check_coupon = false;
					$return_msg .= "STATUS_DIV Error,";
				break;

			}//switch($STATUS_DIV){
		}//쿠폰 조회 end

	}//for 쿠폰 array

	if(!$check_coupon){
		$return_msg = substr($return_msg, 0, -1);	//마지막 쉼표제거
		echo makexml("F",$return_msg);
		exit;
	}else{

		$DIV= true;	//성공(S), 실패(F)
		$MSG="";	//결과 메시지

		for ($i = 0; $i < count($AR_COUPON_NO); $i++) {
			$COUPON_NO = $AR_COUPON_NO[$i];		//쿠폰번호
			//$STATUS_DIV = $AR_STATUS_DIV[$i];	//상태값
			//$RESULT_DATE = $AR_RESULT_DATE[$i];	//사용일시

			switch($STATUS_DIV){
				case 'I':	//사용

					//쿠폰 사용처리
					$useqry="update pcmsdb.asan_coupons set
							STATE = 'Y', Used_at = '$RESULT_DATE', Updated_at= NOW() , syncuse = ''
							where  ORDER_NO = '$ORDER_NO' and COUPON_NO = '$COUPON_NO' and STATE != 'Y' limit 1";
					$useres = $conn_cms->query($useqry);

					//주문 사용처리
					$useqry2="update pcmsdb.asan_orders set
							 USE_CNT = USE_CNT + 1, Used_at = '$RESULT_DATE', Updated_at= NOW()
							where id = '".$SSrow->ORDER_ID."' limit 1";
					$useres2 =  $conn_cms->query($useqry2);


					switch($synctype){
						case 'PCMS':
						case 'COUPANG':
							//PCMS 주문 사용처리

							//뉴스파로 주문 사용처리
							$NSPRuseqry="update spadb.ordermts set
									usegu = '1', usegu_at ='$RESULT_DATE', updated= NOW()
									where (id = '{$Orow->PCMS_orderid}' or pcms_oid = '{$Orow->PCMS_orderid}') and usegu != '1' limit 1";
							$NSPRuseres = $conn_cms3->query($NSPRuseqry);

							if($PCMSuseres){
								$MSG.= "$COUPON_NO : Use Success,";
							}else{
								$DIV = false;
								$MSG.= "$COUPON_NO : Use Fail,";
							}
						break;
						case 'NSPARO_SC':
						case 'NSPARO':
							//뉴스파로 주문 사용처리
							$NSPRuseqry="update spadb.ordermts set
									usegu = '1', usegu_at ='$RESULT_DATE', updated= NOW()
									where (id = '{$Orow->PCMS_orderid}' or pcms_oid = '{$Orow->PCMS_orderid}') and usegu != '1' limit 1";
							$NSPRuseres = $conn_cms3->query($NSPRuseqry);

							if($NSPRuseres){
								$MSG.= "$COUPON_NO : Use Success,";
							}else{
								$DIV = false;
								$MSG.= "$COUPON_NO : Use Fail,";
							}
						break;
					}

					//echo $xml;
				break;

				case 'C': //사용 취소

					//쿠폰 처리

					$useqry="update pcmsdb.asan_coupons set
							STATE = 'N', Used_at = null, Updated_at= NOW() , syncUnuse = 'UC'
							where  ORDER_NO = '$ORDER_NO' and COUPON_NO = '$COUPON_NO' and STATE != 'N' limit 1";
					$useres = $conn_cms->query($useqry);
					//주문 처리
					$useqry2="update pcmsdb.asan_orders set
							 USE_CNT = USE_CNT - 1, Updated_at= NOW()
							where id = '".$SSrow->ORDER_ID."' limit 1";
					$useres2 = $conn_cms->query($useqry2);

					//PCMS 주문 처리
					//사용쿠폰 수량 조회
					$ucqry="SELECT * FROM pcmsdb.asan_coupons where ORDER_NO = '$ORDER_NO' and STATE = 'Y'";
					$ucres=@$conn_cms->query($ucqry);
					$uc_rows = $ucres->num_rows;

					//사용 수량 없으면 주문 미사용 처리
					if($uc_rows == 0){


						switch($synctype){
							case 'PCMS':
							case 'COUPANG':
								//PCMS 주문 사용처리
								$PCMSuseqry="update terp_placem.ordermts set
								usegu = '2', usegu_at = null, Updated_at= NOW()
								where id = '".$Orow->PCMS_orderid."' limit 1";
								$PCMSuseres = $conn_cms->query($PCMSuseqry);

								//뉴스파로 주문 사용처리
								$NSPRuseqry="update spadb.ordermts set
										usegu = '2', usegu_at = null, updated= NOW()
										where (id = '{$Orow->PCMS_orderid}' or pcms_oid = '{$Orow->PCMS_orderid}') and usegu != '2' limit 1";
								$NSPRuseres = $conn_cms3->query($NSPRuseqry);

							break;
							case 'NSPARO_SC':
							case 'NSPARO':
								//PCMS 주문 사용처리
								$NSPRuseqry="update spadb.ordermts set
								usegu = '2', usegu_at = null, updated= NOW()
								where (id = '{$Orow->PCMS_orderid}' or pcms_oid = '{$Orow->PCMS_orderid}') limit 1";
								$NSPRuseres = $conn_cms3->query($NSPRuseqry);
							break;
						}
					}

					if($useres){
						$MSG.= "$COUPON_NO : Unuse Success,";
					}else{
						$DIV = false;
						$MSG.= "$COUPON_NO : Unuse Fail,";
					}
				break;
				default:
					$DIV = false;
					$MSG.= "$COUPON_NO : STATUS_DIV Error,";
				break;
			}//switch
		}//for
		$MSG = substr($MSG, 0, -1);	//마지막 쉼표제거
		if($DIV){
			/*
			//성공했을때 리턴 주지 말아 달라. from 김용삼 차장 20170214
			*/
			echo makexml("S",$MSG);
		}else{
			echo makexml("F",$MSG);
		}
	}//if($check_coupon){

}//주문 조회

}

//catch exception
catch(Exception $e) {
  echo 'Message: ' .$e->getMessage();
}
?>
