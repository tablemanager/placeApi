<?php
// 20220928 tony
// 롯데아이몰 채널 연동 재개발
// 기존 서비스는 2번서버에 있었으나 4년만에 서비스 재개하려고 했으나 프로그램 관리가 안되어 정상 작동하지 않음
// 신 서버 서비스로 이관
// 롯데아이몰에서는 주문(RS) 전문만 사용함.
// 취소/조회 전문은 사용하지 않음.

error_reporting(0);

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
require_once ('./mcrypt.php');
$conn_rds->query("set names utf8");

header('Content-type: application/xml');

// 원본 참고 : https://gateway.sparo.cc/mytrip/req.php?pcd=42202&pc=RS&oid=2020052200EETTT002&bnm=테스트&bhp=01090901678&ousedate=2020-05-26
// http://api.placem.co.kr:1015/reserve/req.php => https://gateway.sparo.cc/extra/lotteimall/req.php 로 변경
// 원래 호출하던 URL => http://api.placem.co.kr:1015/reserve/req.php?pid=LT&pc=RS&pcd=1234567&oid=20220929J68205_589122340&odate=20220929134200&pno=null&bnm=qVjCMv1RQayc4qNqzIL+Tw==&bhp=ppy6eSczAT3hhpomL7lzBw==&odesc=null
// 신규 호출 URL => http://gateway.sparo.cc/extra/lotteimall/req.php?pid=2668&pc=RS&pcd=1234567&oid=20220929J68205_589122340&odate=20220929134200&pno=null&bnm=qVjCMv1RQayc4qNqzIL+Tw==&bhp=ppy6eSczAT3hhpomL7lzBw==&odesc=null
//$v_chid = "2668";
// 롯데아이몰에서 통합주문 인터페이스를 호출하는 것처럼 한다.
$authkey ="u5ce54XYlpov49FqW5cgG8LYUdA7Zf1tleIqiFoD4N99WPASUM9BUUzP7aX7";

// 채널 아이디  LT로 수신되고 있지만 2668로 변경 요청 필요
$v_chid = trim($_GET['pid']);
//$v_chid = ($v_chid == 'LT')?"2688":$v_chid;

// 주문 모드 주문(RS) 만 사용 
$v_mode = trim($_GET['pc']);
// 아이템 코드(플레이스엠 상품코드) 
$v_sellcode = trim($_GET['pcd']);
// 주문번호(판매채널 주문번호)
$v_orderno = trim($_GET['oid']);
// 판매일자(옵션)
$v_selldate = trim($_GET['odate']); 
// 기타(옵션)
$v_desc = trim($_GET['odesc']); 

// 외부쿠폰번호(옵션):빈값
$v_pincode = urldecode($_GET['pno']); 

// 고객명(암호화)
//$v_usernm = urldecode($_GET['bnm']); 
$v_usernm = evdecrypt($_GET['bnm']); 
// 고객전화번호(암호화)
$userhp = evdecrypt($_GET['bhp']);
// 날자 지정 파라미터(옵션)
// 날짜지정 티켓인 경우 해당 날짜의 가격을 적용
//$usedate = $_GET['ousedate']; 

// 공백문자 제거
$v_userhp = str_replace(" ", "+", $v_userhp);
$v_usernm = str_replace(" ", "+", $v_usernm);
//$v_pincode = str_replace(" ", "+", $v_pincode);


$usernm = $v_usernm;
$userhp = $userhp;

$itemmt_id = $v_sellcode;

$tdate = date('Ymd');
//$sip = $_SERVER["REMOTE_ADDR"];


// 필수 파라미터 확인 (고객명, 핸드폰도 필요함)
if (!$v_mode || !$v_orderno || !$v_sellcode || !$userhp){
	$rstate = "RQ";
	$ecode = "8888";  // 필수 파라미터 누락

	echo makexml('E', $v_orderno, $rstate, '', 'Parameter Error', $ecode, '');
	exit;
}

//echo "채널:[$v_chid], 주문모드:[$v_mode], 플엠아이템코드:[$v_sellcode], 판매일자:[$v_selldate], 고객명:[$v_usernm], 고객핸드폰:[$userhp]\n";
//exit;

switch($v_mode){
	case 'RS':
		$ord = chk_chorderno($v_chid, $v_orderno);

		if($ord->idx){
			$rstate = "RQ";
			$ecode  = "1001"; // 기주문 코드
			$xml =  makexml('E', $v_orderno, $rstate, 'N', 'Inserted Order', $ecode, '');
			break;
		}

		// 상품 정보 가지고 오기
		$itemsql = "select * from items_ext where pcmsitem_id = '$itemmt_id' and useyn ='Y' and channel= '$v_chid' limit 1";
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

			echo makexml('E', $v_orderno, $rstate, 'N', 'Inventory Error', $ecode, '');

			exit;
		}

        $_ordesreq = array("orderNo" => $v_orderno,
                        "userHp" => $userhp,
                        "userName" => $usernm,
                        "expDate" => $ousedate);

        // 주문 파라미터
        $_ordesres = json_decode(orders($v_sellcode, json_encode($_ordesreq, JSON_UNESCAPED_UNICODE)));

        // 플레이스엠 주문번호
        $oresno = $_ordesres->Result[0]->OrderNo;
        // 플레이스엠에서 리턴받은 쿠폰번호(핀번호)
        $couponno = $_ordesres->Result[0]->CouponNo;

        if($oresno){
             $qryord = "update pcmsdb.ordermts_ext set pcms_orderno = '$oresno' where order_num = '$v_orderno' limit 1";
             $conn_cms->query($qryord);

             $rstate = "RC";
             $ecode = "1000";
             $xml = makexml('S', $v_orderno, $rstate, 'N', 'Order Success', $ecode, $couponno);

        }else{
             $rstate = "RC";
             $ecode = "9999"; // DB에러
             $xml = makexml('E', $v_orderno, $rstate, '', 'Order Fail', $ecode, '');
        }
        break;
    // 취소, 조회는 호출하지 않음 => 참고 소스 원본을 주석처리함
    // 요청이 있으면 아래 주석 처리 부분을 풀어서 검증해야 함
    default:
        break;
/*
    // 주문 취소
    case 'CS':
        $ord = chk_chorderno($v_chid, $v_orderno);

        if(empty($ord->idx)){

            $rstate = "RQ";
            $ecode  = "8000"; // 없는 쿠폰번호
            $xml = makexml('E', $v_orderno, $rstate, 'N', 'Unknown Order', $ecode, $couponno);

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
                $xml = makexml('S', $v_orderno, $rstate, 'N', 'Cancel Success', $ecode, $couponno);

            }else{
                // 취소 불가능
                $ecode  = "2001"; // 취소 할수 없는 주문
                $xml = makexml('E', $v_orderno, $rstate, $ord->useyn, 'Cancel Fail', $ecode, $couponno);
            }
        }
        break;
    // 주문조회
    case 'SS':

        $ord = chk_chorderno($v_chid, $v_orderno);

        if(empty($ord->idx)){
            $rstate = "RQ";
            $ecode  = "8000"; // 없는 쿠폰번호
            $xml = makexml('E', $v_orderno, $rstate, 'N', 'Unknown Order', $ecode, $couponno);
        }else{
	        switch($ord->useyn){
                case 'C':
                     $rstate='RQ';
                     $ecode = "2000";
                     $xml = makexml('S', $v_orderno, $rstate, $ord->useyn, 'Canceled Order', $ecode, $couponno);
                     break;
                case 'N':
                     $rstate='RQ';
                     $ecode = "1000";
                     $xml = makexml('S', $v_orderno, $rstate, $ord->useyn, 'Confirmed Order:N', $ecode, $couponno);
                     break;
                case 'Y':
                     $rstate='RQ';
                     $ecode = "1000";
                     $xml = makexml('S', $v_orderno, $rstate, $ord->useyn, 'Confirmed Order:Y', $ecode, $couponno);
                    break;
             }
        }
        break;
*/
}

echo $xml;


function makexml($result, $rorderno, $rstate, $useyn, $msg, $ecode, $couponno){
	$xml = '<?xml version="1.0" encoding="UTF-8" ?>
<RESULT>
<RCODE>'.$result.'</RCODE>
<ORDERNO>'.$rorderno.'</ORDERNO>
<RMSG>'.$msg.'</RMSG>
<COUPONNO>'.$couponno.'</COUPONNO>
<USTATE>'.$useyn.'</USTATE>
<ECODE>'.$ecode.'</ECODE>
</RESULT>';

//	return $xml;
	return trim($xml);
}

// 외부 주문 테이블 조회
function chk_chorderno($cpcode, $orderno){
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

    $response = curl_exec($curl);

    curl_close($curl);

    return $response;
}


?>
