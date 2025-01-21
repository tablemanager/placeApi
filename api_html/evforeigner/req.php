<?php
// RMS 주문 인터페이스
error_reporting(0);
require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
$conn_rds->query("set names utf8");

$schid = "FNR";
$chid = "FNR";
$chnm = "홈페이지RMS";

$authkey ="352hghhhh444442326778893RRRRee";


$v_chid = trim($_GET['pid']); // 채널 아이디
$v_mode = trim($_GET['pc']); // 주문 모드
$v_sellcode = trim($_GET['pcd']); // 아이템 코드
$v_orderno = addslashes(trim($_GET['oid'])); // 주문번호
$v_selldate = trim($_GET['odate']); // 판매일자
$v_desc = trim($_GET['odesc']); // 기타

$v_pincode = urldecode($_GET['pno']); // 외부쿠폰번호
$v_usernm = urldecode($_GET['bnm']); // 고객명(암호화)
$v_userhp = urldecode($_GET['bhp']); // 핸드폰(암호화)
//$userhp = $_GET['bhp'];
$useremail = $_GET['bemail'];

$v_expdate = trim($_GET['expdate']); // 만료일

$v_userhp = str_replace(" ", "+", $v_userhp);
$v_usernm = str_replace(" ", "+", $v_usernm);
$v_pincode = str_replace(" ", "+", $v_pincode);

$usernm = addslashes($v_usernm);
$userhp = addslashes($v_userhp);
$itemmt_id = $v_sellcode;
$tdate = date('Ymd');
$sip = $_SERVER["REMOTE_ADDR"];

// 필수 파라미터 확인 (고객명, 핸드폰도 필요함)
if (!$v_mode and !$v_orderno and !$v_sellcode and !$userhp){
	$rstate = "RQ";
	$ecode = "8888";  // 필수 파라미터 누락
	echo makexml('E',$v_orderno,$rstate,'Parameter Error',$ecode);
	exit;
}


switch($v_mode){
	case 'RS':
		$ord = get_extorder($v_orderno);

		if($ord->idx){
			$rstate = "RQ";
			$ecode  = "1001"; // 기주문 코드
			$xml =  makexml('E',$v_orderno,$rstate,'N','Inserted Order',$ecode);
			break;
		}

		// 상품 정보 가지고 오기
		$itemsql = "select * from pcmsdb.items_ext where pcmsitem_id = '$itemmt_id' and useyn ='Y' and channel= '$schid' order by id desc limit 1";

		$itemrow = $conn_cms->query($itemsql)->fetch_object();


		if($itemrow) {
			$gu = $itemrow->gu;

			$ousedate = $itemrow->usedate;

			if($gu){
                switch($gu){
                    default:
    				    		$couponno = get_sticket($gu, $v_orderno,$usernm,$userhp);
                }

			}else{
                $couponno = $v_pincode;
            }

		}else{
			$rstate = "RQ";
			$ecode  = "8888"; // 상품코드 미셋팅
			echo makexml('E',$v_orderno,$rstate,'N','Inventory Error',$ecode);
			exit;
		}

	// 주문 파라미터
		$state = "예약완료";
		$userhp = $userhp;
		$desc_1 = $v_orderno; // 시설 주문 번호
		$desc_2 = $couponno; // 쿠폰 번호
		// PCMS 주문입력

        if($v_expdate){

            $ousedate = $v_expdate;

        }else{

        }

		$_ordesreq = array("orderNo" => $v_orderno,
									 "userHp" => $userhp,
									 "userName" => $usernm,
									 "expDate" => $ousedate,
								 	"couponNo" => $couponno);

		$_ordesres = json_decode(orders($v_sellcode, json_encode($_ordesreq,JSON_UNESCAPED_UNICODE)));// 주문 파라미터

		$oresno = $_ordesres->Result[0]->OrderNo;
		$couponno = $_ordesres->Result[0]->CouponNo;

		if($oresno){
			$qryord = "update pcmsdb.ordermts_ext set pcms_orderno = '$oresno' where order_num = '$v_orderno' limit 1";

			$conn_cms->query($qryord);

			$rstate = "RC";
			$ecode = "1000";
			$xml = makexml('S',$v_orderno,$rstate,'N','Order Success',$ecode,$couponno);

		}else{
			$rstate = "RC";
			$ecode = "9999"; // DB에러
			$xml = makexml('E',$v_orderno,$rstate,'Order Fail',$ecode);
		}
	break;

	case 'CS':
        // ordermts_ext에서 조회
		$ord = get_extorder($v_orderno);

		//if(getuseno($v_orderno) == 'Y') $ord->useyn = "Y";
        // 20221014 tony https://placem.atlassian.net/browse/P2CCA-165
        // ordermts의 사용처리 상태를 기준으로 한다. => cmadmin에서 제어한 값을 사용
        // ordermts의 사용플레그 조회
        $ord->useyn = getuseno($v_orderno);

		if(!$ord->idx){
			$rstate = "RQ";
			$ecode  = "8000"; // 없는 쿠폰번호

			$xml = makexml('E',$v_orderno,$rstate,'N','Unknown Order',$ecode,$couponno);

		}else{
			if($ord->useyn == 'N'){
				$rstate="CC";



				$csqll="update pcmsdb.ordermts_ext set useyn = 'C' where idx = '$ord->idx' limit 1";
				$conn_cms->query($csqll);


				if(1){
					$osql = "select * from spadb.ordermts where ch_orderno = '$ord->order_num' limit 1";
					$orow = $conn_cms3->query($osql)->fetch_object();


					// 연동상품 관련 취소처리
					$pincode = $orow->barcode_no;
					$pincode = str_replace(";", "",$pincode);

					if ($pincode){

            $csqll7="update spadb.pcms_extcoupon set syncfac_result = 'C' where couponno = '$pincode' limit 1";
	    			$conn_cms3->query($csqll7);

					}


					$conn_cms3->query("update spadb.ordermts set state = '취소' where ch_orderno = '$ord->order_num' and usegu != '1' limit 1");
				}
				$ecode  = "2000"; // 취소 할수 있는 주문
				$xml = makexml('S',$v_orderno,$rstate,'N','Cancel Success',$ecode,$couponno);
			}else{
				$rstate="CC";
				$ecode  = "2001"; // 취소 할수 없는 주문
				$xml = makexml('E',$v_orderno,$rstate,$ord->useyn,'Cancel Fail',$ecode,$couponno);

			}
		}

	break;
	case 'SS':
		$ord = get_extorder($v_orderno);
		
		// 주문테이블에 쿠폰번호가 있으면 쿠폰번호를 리턴한다.
		$orw = getOrderInfo($ord->pcms_orderno);
		$couponno = $orw->barcode_no;

		if(getuseno($v_orderno) == 'Y') $ord->useyn = "Y";
		
		if(!$ord->idx){
			$rstate = "RQ";
			$ecode  = "8000"; // 없는 쿠폰번호
			$xml = makexml('E',$v_orderno,$rstate,'N','Unknown Order',$ecode,$couponno);
		}else{
			switch($ord->useyn){
				case 'C':
					$rstate='RQ';
					$ecode = "2000";
					$xml = makexml('S',$v_orderno,$rstate,$ord->useyn,'Canceled Order',$ecode,$couponno);
				break;
				case 'N':
					$rstate='RQ';
					$ecode = "1000";
					$xml = makexml('S',$v_orderno,$rstate,$ord->useyn,'Confirmed Order:N',$ecode,$couponno);
				break;
				case 'Y':
					$rstate='RQ';
					$ecode = "1000";
					$xml = makexml('S',$v_orderno,$rstate,$ord->useyn,'Confirmed Order:Y',$ecode,$couponno);
				break;
			}
		}
	break;

}

echo $xml;


function getuseno($orderno){
	global $conn_cms;
	global $conn_cms3;

	$gu=substr($no,0,1);
	$useflag = "N";

	if(true){
		// 쿠폰 DB 사용 확인
			$usql3 = "select * from spadb.ordermts where ch_id ='2822' and  ch_orderno = '$orderno' limit 1";
			$urow3 = $conn_cms3->query($usql3)->fetch_object();


			if($urow3->usegu == '1'){
				$useflag =  "Y";
			}else{
				$useflag =  "N";
			}
	}else{
				$useflag =  "Y";
	}

	return $useflag;
}

function get_sticket($gu,$orderno,$usernm,$hp){

	global $conn_cms;
	global $conn_cms3;

	if(!$gu) return 0;
	if(!$orderno) return 0;

	$barsql = "update spadb.pcms_extcoupon set cus_nm = '$usernm', order_no='$orderno', cus_hp ='$hp', syncfac_result = 'R' , date_order = now() where sellcode ='$gu' and  syncfac_result ='N' and order_no is null limit 1";
	$conn_cms3->query($barsql);


	$bsql = "select * from spadb.pcms_extcoupon where sellcode ='$gu' and order_no= '$orderno' limit 1";
	$brow = $conn_cms3->query($bsql)->fetch_object();

	$csql ="update pcmsdb.ordermts_ext set pcms_couponno ='$brow->couponno' where order_num ='$orderno' limit 1 ";
	$conn_cms3->query($csql);

	$conn_cms3->query($csql);

	return $brow->couponno;
}

function get_extorder($orderno)
{
	global $conn_cms;

 	$psql="SELECT * FROM pcmsdb.ordermts_ext WHERE channel= 'FNR'  and order_num = '$orderno' limit 1";

	$brow = $conn_cms->query($psql)->fetch_object();
	return $brow;
}

function getOrderInfo($orderno){
	
	global $conn_cms3;
	if(!$orderno) return 0;

	$osql = "select * from spadb.ordermts where orderno = '$orderno' limit 1";
	$orow = $conn_cms3->query($osql)->fetch_object();

	return $orow;
}

function orders($itemcd, $reqjson){
          global $authkey;
          $curl = curl_init();

          curl_setopt_array($curl, array(
            CURLOPT_URL => "http://gateway.sparo.cc/extra/agency/v2/dealcode/".$itemcd,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS =>$reqjson,
            CURLOPT_HTTPHEADER => array(
              "Authorization: ".$authkey,
              "Content-Type: application/json"
            ),
          ));

					$response = curl_exec($curl);

					return $response;

          curl_close($curl);

}


function makexml($result,$rorderno,$rstate,$useyn,$msg,$ecode,$couponno){
	$xml = '<?xml version="1.0" encoding="UTF-8" ?>
	<RESULT>
	<RCODE>'.$result.'</RCODE>
	<ORDERNO>'.$rorderno.'</ORDERNO>
	<RMSG>'.$msg.'</RMSG>
	<COUPONNO>'.$couponno.'</COUPONNO>
	<USTATE>'.$useyn.'</USTATE>
	<ECODE>'.$ecode.'</ECODE>
	</RESULT>';
	return trim($xml);
}

