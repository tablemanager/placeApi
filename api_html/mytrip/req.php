<?php
// 마이 리얼트립
error_reporting(0);
require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
$conn_rds->query("set names utf8");
header('Content-type: application/xml');
//https://gateway.sparo.cc/mytrip/req.php?pcd=42202&pc=RS&oid=2020052200EETTT002&bnm=테스트&bhp=01090901678&ousedate=2020-05-26
$schid = "MRT";
$chid = "MRT";
$chnm = "마이리얼트립";
$authkey ="18576734875387237523853286829375768";

$v_chid = trim($_GET['pid']); // 채널 아이디
$v_mode = trim($_GET['pc']); // 주문 모드
$v_sellcode = trim($_GET['pcd']); // 아이템 코드
$v_orderno = trim($_GET['oid']); // 주문번호
$v_selldate = trim($_GET['odate']); // 판매일자
$v_desc = trim($_GET['odesc']); // 기타

$v_pincode = urldecode($_GET['pno']); // 외부쿠폰번호
$v_usernm = urldecode($_GET['bnm']); // 고객명(암호화)
$userhp = $_GET['bhp'];
$usedate = $_GET['ousedate']; // 날자 지정 파라미터


$v_userhp = str_replace(" ", "+", $v_userhp);
$v_usernm = str_replace(" ", "+", $v_usernm);
$v_pincode = str_replace(" ", "+", $v_pincode);

$usernm = $chnm;
$userhp = $userhp;
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
		$ord = chk_chorderno($schid,$v_orderno);

		if($ord->idx){
			$rstate = "RQ";
			$ecode  = "1001"; // 기주문 코드
			$xml =  makexml('E',$v_orderno,$rstate,'N','Inserted Order',$ecode);
			break;
		}

		// 상품 정보 가지고 오기
		$itemsql = "select * from items_ext where pcmsitem_id = '$itemmt_id' and useyn ='Y' and channel= '$schid' limit 1";
    $itemrow = $conn_cms->query($itemsql)->fetch_object();


		if($itemrow) {
			$gu = $itemrow->gu;

      if($usedate){
          $ousedate = $usedate;
      }else{
          $ousedate = $itemrow->usedate;
      }

		}else{
			$rstate = "RQ";
			$ecode  = "8888"; // 상품코드 미셋팅
			echo makexml('E',$v_orderno,$rstate,'N','Inventory Error',$ecode);
			exit;
		}

    $_ordesreq = array("orderNo" => $v_orderno,
                   "userHp" => $userhp,
                   "userName" => $usernm,
                   "expDate" => $ousedate);

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
    $ord = chk_chorderno($schid,$v_orderno);

    		if(empty($ord->idx)){

        			$rstate = "RQ";
        			$ecode  = "8000"; // 없는 쿠폰번호
        			$xml = makexml('E',$v_orderno,$rstate,'N','Unknown Order',$ecode,$couponno);

    		}else{

              if(getuseno($v_orderno) == '1'){
                $useyn = "Y";
              }else{
                $useyn = "N";
              }

              if($useyn == "N"){
                    // 취소 가능
            				$rstate="CC";
            				$csqll = "update pcmsdb.ordermts_ext set useyn = 'C' where idx = '$ord->idx' limit 1";
                    $conn_cms->query($csqll);

                    $ocsql = "update spadb.ordermts set state = '취소' where ch_orderno = '$ord->order_num' and usegu != '1' limit 1";
                    $conn_cms3->query($ocsql);

                    $ecode  = "2000"; // 취소 할수 있는 주문
            				$xml = makexml('S',$v_orderno,$rstate,'N','Cancel Success',$ecode,$couponno);

              }else{
                    // 취소 불가능
                    $ecode  = "2001"; // 취소 할수 없는 주문
            				$xml = makexml('E',$v_orderno,$rstate,$ord['useyn'],'Cancel Fail',$ecode,$couponno);
              }

        }
  break;
  case 'SS':

        $ord = chk_chorderno($schid,$v_orderno);

        if(empty($ord->idx)){
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

// 외부 주문 테이블 조회
function chk_chorderno($cpcode,$orderno){
    global $conn_cms;
    global $conn_rds;

    $sql0 = "SELECT * from cmsdb.pcms_extorder where ch_code = '$cpcode' and ch_orderno = '$orderno' limit 1";
    $res0 = $conn_rds->query($sql0);
    $row0 = $res0->fetch_object();

    if($row0->idx){

        return true;

    }else{

        $sql = "SELECT * from pcmsdb.ordermts_ext where channel = '$cpcode' and order_num = '$orderno' limit 1";
        $res = $conn_cms->query($sql);
        $row = $res->fetch_object();

        if($row->idx){
            return true;
        }else{
            return false;
        }


    }

}

function getuseno($ch_orderno){
    global $conn_cms3;
    $ssql = "select * from spadb.ordermts where ch_orderno ='$ch_orderno' limit 1";
    $row = $conn_cms3->query($ssql)->fetch_object();

    return $row->usegu;

}

function orders($itemcd, $reqjson){
          global $authkey;
          $curl = curl_init();

          curl_setopt_array($curl, array(
            CURLOPT_URL => "http://extapi.sparo.cc/extra/agency/v2/dealcode/".$itemcd,
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

          return $response = curl_exec($curl);

          curl_close($curl);

}


?>
