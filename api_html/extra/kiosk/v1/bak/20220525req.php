<?php
/*
 *
 * 다이노스타 키오스크 인터페이스
 *
 * 작성자 : 이정진
 * 작성일 : 2017-11-21
 *
 *
 * 2022.05.04 tony 키오스크 API이상으로 로그 기록
 * 2022.05.17 tony 곤지암루지 다회권 사용처리 추가 하기 전 백업
 *
 */
//242,3327,3463,3584,3088,218

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');

// header('Content-type: text/xml');
header("Content-Type: application/xml; charset=utf-8");
$mdate = date("Y-m-d");
$s_starttm = getms();

$v_ch = trim($_GET["ch"]);
$v_val = trim($_GET["pval"]);
$v_mode = trim($_GET["pc"]);
$v_mode = strtoupper($v_mode); //소문자로 들어와도 대문자로 전환함으로써 대소문자 구분없이 파라미터 검증 - Jason 21.10.22
$v_termno = $_GET["fnco"];
//실제로 들어오는 요청은 fnco가 아니라 대부분 fcno로 들어오는 것으로 파악되어 아래 코드 추가 - Jason 21.10.06
if($v_termno == ''){
	$v_termno = $_GET["fcno"];
}
$v_sdate = $_GET["sdate"];
$v_edate = $_GET["edate"];

//log기록부분 - Jason 21.10.05
$conn_rds->query("set names utf8");
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드


// $tranid = mktime();
// mktime을 이용해 초단위로만 tranid를 설정할 시 1초에 여러번 리퀘스트가 발생할 경우 로그가 기록되지 않는 문제를 인지하여 0.001초까지의 유닉스타임값으로 수정 - Jason 22.01.12
list($microtime,$timestamp) = explode(' ',microtime()); 
$tranid = $timestamp . substr($microtime, 2, 3);


// 로그 남기기
/*
$fnm = date('Ymd') . 'kiosk.log';
$fp = fopen("/home/sparo.cc/api_html/extra/kiosk/v1/txt/$fnm", 'a+');
$adata = Array(
  'get' => $_GET,
  'apimethod' => $apimethod,
  'tranid' => $tranid
);
fwrite($fp, "[$tranid]==================================================\n");
fwrite($fp, "[$tranid][Start] ".date("Y-m-d H:i:s")." ".print_r($adata, true));
// fclose($fp);
*/


$apiheader = getallheaders(); // http 헤더
$para = $_SERVER['QUERY_STRING'];
$logsql = "insert cmsdb.extapi_log_v1 set apinm='kiosk/v1',tran_id='$tranid', ip='".get_ip()."', logdate= now(), apimethod='$apimethod', mode='$v_mode', ch='$v_ch', val='$v_val', fnco='$v_termno', header='".json_encode($apiheader)."', querystr='$para'";
$conn_rds->query($logsql);

// Param Validation 파라미터 검증부 추가 - Jason 21.10.22
// mode 값 검증
// 2022.05.04 tony
// SS : 쿠폰조회
// US : 쿠폰사용
$modearr = array("CL","US","SS","RC");
$valid_mode = in_array($v_mode,$modearr);
// ch 값 검증
$valid_ch_2 = true;
$valid_ch = is_numeric($v_ch);
if($valid_ch){
    if(!($v_ch < 10000)){
        $valid_ch_2 = false;
    }
}
if(!($valid_mode && $valid_ch && $valid_ch_2)){
    $v_mode = "ER";
}

// sdate, edate 날짜값 검증
if($v_mode === "CL"){
    $vaild_date = false;
    if(isValidDate($v_sdate) && isValidDate($v_edate)){
        $vaild_date = true;
    }
    if(!$vaild_date){
        $v_mode = "ER";
    }
}



if(!$v_ch) $v_ch = 242;

// 가능 판매채널 코드 (채널이 아니라 시설의 업체코드, 허용 grmt_id 값)
// 3871 코어웍스
// 3823 곤지암루지
$charr = array(3871,3856,3823,3788,3782,4182,242,3327,3463,3584,3088,218,3467,3590,211,
3605,3610,3615,3619,238,3631,4012,4014,3632,3633,3634,3638,3324,3135,3481,3653,3690,3493,187,3294);

if(!in_array($v_ch,$charr)){
/*
    $totms = (getms()-$s_starttm);

    fwrite($fp, "\n[$tranid][ERR] 비허용 시설코드 : [$v_ch]");
    fwrite($fp, "\n[$tranid]소요시간 : $totms ms");
    if($totms > 1000) fwrite($fp, "\n[$tranid][WARN] [$totms]>1000 ms");
    fwrite($fp, "\n[$tranid][End] exit!! ".date("Y-m-d H:i:s"));
    fwrite($fp, "\n[$tranid]==================================================\n");
    fclose($fp);
*/   
    exit;
}

$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><RESULT/>');

switch($v_mode){
    case 'CL':

		if(empty($v_sdate)) $v_sdate = $mdate;
		if(empty($v_edate)) $v_edate = $mdate;

		$sdate = $v_sdate." 00:00:00";
		$edate = $v_edate." 23:59:59";

		// 주문 수량 카운트
	 	$cntsql = "SELECT sum(man1) as usecnt
								 FROM spadb.ordermts o
								 WHERE 1
								 AND grmt_id = '$v_ch'
								 AND usegu_at between '$sdate' and '$edate'
								 AND usegu = '1'
								 AND state= '예약완료' ";
		$cntrow = $conn_cms3->query($cntsql)->fetch_object();

		// 주문 리스트(메인)
		$orderqry = "SELECT o. * ,AES_DECRYPT(UNHEX(o.hp),'Wow1daY') dhp
						FROM spadb.ordermts o
						WHERE 1
							AND grmt_id = '$v_ch'
							AND usegu_at between '$sdate' and '$edate'
							AND usegu = '1'
							AND state= '예약완료'
						";

		$orderqry = $orderqry." ORDER BY updated asc";

	    $ores = $conn_cms3->query($orderqry);

        $track = $xml->addChild('RCODE',"S");
        $track = $xml->addChild('RMSG',"성공");
        $track = $xml->addChild('ORDERS');

		$totalsum = 0;
		while($orow = $ores->fetch_object()){

                $mcsql ="select * from CMSDB.CMS_ITEMS where item_id = '".$orow->itemmt_id."' limit 1";
                $mcinfo = $conn_cms->query($mcsql)->fetch_object();
                $mcode = $mcinfo->item_cd;
				$mcode = trim($mcode); // 공백으로 인한 오류가 발생하여 추가 - Jason 21.12.24

				// 주문 리스트(서브)
				$odetailsql = "SELECT *
										 FROM spadb.ordermts_coupons
										 WHERE 1
										 AND order_id = '$orow->id' and dt_use between '$sdate' and '$edate' and state='Y'";
                $odetailres = $conn_cms3->query($odetailsql);
				$odetailcnt = $odetailres->num_rows;

				if($orow->man1 > 1){

					// 쿠폰 수가 1보다 크면
					$ord = $track->addChild('ORDER');
					$ord->addChild('MENUCODE',$mcode);
					$ord->addChild('QTY',$orow->man1);
					$ord->addChild('COUPONNO',$orow->barcode_no);
					$ord->addChild('USEDATE',$orow->usegu_at);
					$totalsum += $orow->man1;
					$totalamt += $orow->accamt;

				}else{
					while($odetailrow = $odetailres->fetch_object()){
						$ord = $track->addChild('ORDER');
						$ord->addChild('MENUCODE',$mcode);
						$ord->addChild('QTY',1);
						$ord->addChild('COUPONNO',$odetailrow->couponno);
						$ord->addChild('USEDATE',$odetailrow->dt_use);
						$totalsum += 1;
						$totalamt += $orow->accamt;
					}

				}
				$lastudate = $orow->updated;
		}

        $track = $xml->addChild('RCNT',$totalsum);
        $track = $xml->addChild('RAMT',$totalamt);
        $track = $xml->addChild('RDATE',$lastudate);



	break;
	case 'SS':
        if(is_numeric($v_val) and strlen($v_val) == 4){

			$orderqry2 = "SELECT a.*,b.couponno as coupon, b.state as cstate , AES_DECRYPT(UNHEX(a.hp),'Wow1daY') as dhp
				FROM ordermts a LEFT OUTER JOIN ordermts_coupons  b ON a.id = b.order_id where a.grmt_id = '$v_ch' AND a.usedate >= '$mdate' AND a.state= '예약완료' AND a.lasthp = '".$v_val."'";


        }else{

			$orderqry = "SELECT * FROM spadb.ordermts WHERE grmt_id = '$v_ch'  and id in (select order_id from spadb.ordermts_coupons where couponno = '$v_val')";

			$ores = $conn_cms3->query($orderqry)->fetch_object();

			  $orderqry2 = "SELECT
								a.*,
								b.couponno as coupon,
								b.state as cstate ,
								AES_DECRYPT(UNHEX(a.hp),'Wow1daY') as dhp
							FROM
								ordermts a
							LEFT OUTER JOIN
								ordermts_coupons  b
							ON a.id = b.order_id
							WHERE
								a.grmt_id = '$v_ch' AND

								a.state= '예약완료' AND
								a.hp = '".$ores->hp."'";

        }
//								a.usedate >= '$mdate' AND
		$res = $conn_cms3->query($orderqry2);



        if($res->num_rows > 0){
            $track = $xml->addChild('RCODE',"S");
            $track = $xml->addChild('RMSG',"성공");
            $track = $xml->addChild('RCNT',$res->num_rows);
            $track = $xml->addChild('ORDERS');

//            fwrite($fp, "\n[$tranid]티켓갯수 : $res->num_rows");

            while($row = $res->fetch_object()){
				if(empty($row->coupon)) continue;

				if($row->cstate == 'Y') $useyn = '1';
					else $useyn = '2';

				$n = 1;
				if($row->man1 != '1'){
					// 개별 쿠폰과 수량 쿠폰 구별
					if(count(array_filter(explode(";",$row->barcode_no.";")))== "1"){
						$n = $row->man1;
					}else{
						$n = 1;
					}
				}

                $mcsql ="select * from CMSDB.CMS_ITEMS where item_id = '".$row->itemmt_id."' limit 1";
                $mcinfo = $conn_cms->query($mcsql)->fetch_object();
                $mcode = $mcinfo->item_cd;
				$mcode = trim($mcode); // 공백으로 인한 오류가 발생하여 추가 - Jason 21.12.24

                $ord = $track->addChild('ORDER');
                $ord->addChild('ORDERNO',$row->orderno);
                $ord->addChild('COUPONNO',$row->coupon);
                $ord->addChild('MENUCODE',$mcode);
//                $ord->addChild('MENUNAME',"".$row->itemnm);
				$ord->addChild('MENUNAME',str_replace("&", ",",$row->itemnm));

                $ord->addChild('QTY',$n);
                $ord->addChild('EXPDATE',str_replace("-", "",$row->usedate));
                $ord->addChild('STATE',$row->state);
                $ord->addChild('USTATE',$useyn);
                $ord->addChild('CUSNM',$row->usernm);

								// 전화번호가 없으면
								if(strlen($row->dhp) < 5) $row->dhp = "010-0000-0000";

                $ord->addChild('CUSHP',$row->dhp);
                $ord->addChild('CUSOPT'," ");
//                $ord->addChild('CUSOPT',$row->bigo);
//                fwrite($fp, "\n[$tranid]쿠폰번호 : [$row->coupon]");
            }
        }else{
            $track = $xml->addChild('RCODE',"E");
            $track = $xml->addChild('RMSG',"조회결과가 없습니다.");
            $track = $xml->addChild('RCNT',$res->num_rows);
            $track = $xml->addChild('ORDERS');

//            fwrite($fp, "\n[$tranid]조회결과가 없음 : [$res->num_rows]");
        }



    break;
    case 'US':
		$sql = "SELECT
					*
				FROM
					spadb.ordermts
				WHERE
					grmt_id = '$v_ch' AND
					id in (select order_id from spadb.ordermts_coupons where state = 'N' and couponno = '$v_val')";

        $row = $conn_cms3->query($sql)->fetch_object();

        if($row->id and $row->state == '예약완료'){



            $ussql = "update spadb.ordermts set paygu = '$v_termno', usegu = 1, usegu_at = now() where id = '".$row->id."' and usegu = '2' limit 1";
            $conn_cms3->query($ussql);

            $ussql2 = "update spadb.ordermts_coupons set state='Y', dt_use=now() where state='N' and couponno = '$v_val'";
            $conn_cms3->query($ussql2);

			usecouponno($v_val);

            $track = $xml->addChild('RCODE',"S");
            $track = $xml->addChild('RMSG',"성공");
            $track = $xml->addChild('RCNT',1);
            $track = $xml->addChild('ORDERS');

//            fwrite($fp, "\n[$tranid]사용처리 성공 : [$v_val]");
        }else{
            $track = $xml->addChild('RCODE',"E");
            $track = $xml->addChild('RMSG',"사용처리 실패");
            $track = $xml->addChild('RCNT',1);
            $track = $xml->addChild('ORDERS');

//            fwrite($fp, "\n[$tranid]사용처리 실패 : [$v_val]");
        }


    break;
    case 'RC':
		$sql = "SELECT * FROM spadb.ordermts WHERE grmt_id = '$v_ch'  and id in (select order_id from spadb.ordermts_coupons where couponno = '$v_val')";
        $row = $conn_cms3->query($sql)->fetch_object();

        if($row->id){

            $ussql2 = "update spadb.ordermts_coupons set state='N', dt_use= null where state = 'Y' AND couponno = '$v_val'";
            $conn_cms3->query($ussql2);

			// usleep(10000);
			$ussql3 = "select order_id from spadb.ordermts_coupons where order_id = '$row->id' and state ='Y'";
			$usrow3 = $conn_cms3->query($ussql3)->fetch_object();

			if(empty($usrow3)){
				$ussql = "update spadb.ordermts set usegu = 2, usegu_at = null where id = '".$row->id."' limit 1";
				$conn_cms3->query($ussql);
			}

			/*
			* @brief 광명동굴 등, 사용처리 취소시에 ordermts 테이블의 업데이트 쿼리가 가끔씩 작동하지 않는 문제가 있어 한번 더 동일한 역할을 하는 쿼리가 돌도록 작성
			* @author Jason - 21.08.19 - 21.08.27
			* 경주버드파크에서 추가로 문제 발생 확인됨 21.08.26
			* usegu가 2로 정상적으로 되돌아갔다가 10초 정도만에 다시 1로 변경되는 현상 확인 21.08.27
			*/
			// usleep(10000);
			$doublechk_sql1 = "select order_id from spadb.ordermts_coupons where order_id = '$row->id' and state ='Y'";
			$doublechk_uscnt = $conn_cms3->query($ussql3)->num_rows;

			if($doublechk_uscnt == 0){
				$doublechk_usql1 = "update spadb.ordermts set usegu = 2, usegu_at = null where id = '".$row->id."' and usegu = 1 limit 1";
				$conn_cms3->query($doublechk_usql1);
			}

			// usleep(10000);
			// $doublechk_sql2 = "select order_id from spadb.ordermts_coupons where order_id = '$row->id' and state ='Y'";
			// $doublechk_row = $conn_cms3->query($doublechk_sql2)->fetch_object();

			// if(empty($doublechk_row)){
			// 	$doublechk_usql2 = "update spadb.ordermts set usegu = 2, usegu_at = null where id = '".$row->id."' and usegu = 1 limit 1";
			// 	$conn_cms3->query($doublechk_usql2);
			// }
			// 반품(사용처리 취소)시 ordermts 상태값 변경의 더블체크를 위한 동일한 로직 반복 끝

			// $doublechksql = "SELECT
			// 					order_id
			// 				FROM
			// 					spadb.ordermts_coupons
			// 				WHERE
			// 					couponno = '$v_val'



            $track = $xml->addChild('RCODE',"S");
            $track = $xml->addChild('RMSG',"회수 성공");
            $track = $xml->addChild('RCNT',1);
            $track = $xml->addChild('ORDERS');

        }else{
            $track = $xml->addChild('RCODE',"E");
            $track = $xml->addChild('RMSG',"회수 실패");
            $track = $xml->addChild('RCNT',1);
            $track = $xml->addChild('ORDERS');
        }
    break;
	case 'ER': //파라미터 검증 에러시 리턴값 설정 - Jason 21.10.22
        $track = $xml->addChild('RCODE',"E");
        $track = $xml->addChild('RMSG',"PARAMETER ERROR");
        $track = $xml->addChild('RCNT',1);
	break;
    default:

            $track = $xml->addChild('RCODE',"E");
            $track = $xml->addChild('RMSG',"조회결과가 없습니다.");
            $track = $xml->addChild('RCNT',$res->num_rows);
            $track = $xml->addChild('ORDERS');

}
        print($xml->asXML());

/*
$totms = (getms()-$s_starttm);

fwrite($fp, "\n[$tranid]소요시간 : $totms ms");
if($totms > 1000) fwrite($fp, "\n[$tranid][WARN] [$totms]>1000 ms");
fwrite($fp, "\n[$tranid][End] ".date("Y-m-d H:i:s"));
//fwrite($fp, "\n[$tranid][End] ".date("Y-m-d H:i:s")." ".print_r($xml, true));
fwrite($fp, "\n[$tranid]==================================================\n");
fclose($fp);
*/ 

function usecouponno($no){

	$curl = curl_init();
    $url = "http://115.68.42.2:3040/use/".$no;
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_USERAGENT,"Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1" );
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);

    $data = explode(";",curl_exec($curl));
    $info = curl_getinfo($curl);
    curl_close($curl);
    if($data[1] == "0"){
        return "N";
    }else{
        return "Y";
    }
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

// 날짜포맷 검증 함수
function isValidDate($date, $format= 'Y-m-d'){
    return $date == date($format, strtotime($date));
}

function getms()
{
  list($microtime,$timestamp) = explode(' ',microtime());
  $time = $timestamp.substr($microtime, 2, 3);
 
  return $time;
}
?>
