<?php
/*
 *
 * 천안상록 키오스크 인터페이스
 *
 * 작성자 : 이정진
 * 작성일 : 2017-11-21
 *
 *
 *
 */
//242,3327,3463,3584,3088,218

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');

header('Content-type: text/xml');
$mdate = date("Y-m-d");

$v_ch = trim($_REQUEST["ch"]);
$v_val = trim($_REQUEST["pval"]);
$v_mode = trim($_REQUEST["pc"]);
$v_termno = $_REQUEST["fnco"];
//실제로 들어오는 요청은 fnco가 아니라 대부분 fcno로 들어오는 것으로 파악되어 아래 코드 추가 - Jason 21.12.13
if($v_termno == ''){
	$v_termno = $_GET["fcno"];
}
$v_sdate = $_REQUEST["sdate"];
$v_edate = $_REQUEST["edate"];

//log기록부분 - Jason 21.12.13
$conn_rds->query("set names utf8");
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드

// mktime을 이용해 초단위로만 tranid를 설정할 시 1초에 여러번 리퀘스트가 발생할 경우 로그가 기록되지 않는 문제를 인지하여 0.001초까지의 유닉스타임값으로 수정 - Jason 22.02.23
list($microtime,$timestamp) = explode(' ',microtime()); 
$tranid = $timestamp . substr($microtime, 2, 3);
$apiheader = getallheaders(); // http 헤더
$para = $_SERVER['QUERY_STRING'];
$logsql = "insert cmsdb.extapi_log_v1 set apinm='kiosk/v2',tran_id='$tranid', ip='".get_ip()."', logdate= now(), apimethod='$apimethod', mode='$v_mode', ch='$v_ch', val='$v_val', fnco='$v_termno', header='".json_encode($apiheader)."', querystr='$para'";
$conn_rds->query($logsql);


if(!$v_ch) $v_ch = 488; // 상록

// 판매 가능 상품
// 20221216 tony https://placem.atlassian.net/browse/P2CCA-227
// 상품추가
$items = array("46942","46943","48245","48246","49685","49686","50888","50889",
"59649", "59650", "59919", "59921",
// 20230320 tony https://placem.atlassian.net/browse/P2CCA-306
// 상품추가
"62100", "62101",
);
$itemstr = implode(",",$items);
// 가능 판매채널 코드
$charr = array(488);

if(!in_array($v_ch,$charr)){
	exit;
}

/*
(33261,33262,36051,36052,36053,36054,36055,36056)
*/
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
								 and itemmt_id in ($itemstr)
								 AND grmt_id = '$v_ch'
								 AND created between '$sdate' and '$edate'";
		$cntrow = $conn_cms3->query($cntsql)->fetch_object();

		// 주문 리스트(메인)
 			$orderqry = "SELECT o. * ,AES_DECRYPT(UNHEX(o.hp),'Wow1daY') dhp
								 FROM spadb.ordermts o
								 WHERE 1
								 and itemmt_id in ($itemstr)
								 AND grmt_id = '$v_ch'
								 AND usegu_at between '$sdate' and '$edate'
								 order by created asc ";

		if($v_termno == "pos" ) $orderqry = $orderqry." and paygu='pos'";
		if($v_termno == "kiosk" ) $orderqry = $orderqry." and paygu='kiosk'";

	    $ores = $conn_cms3->query($orderqry);

        $track = $xml->addChild('RCODE',"S");
        $track = $xml->addChild('RMSG',"성공");
        $track = $xml->addChild('ORDERS');

		$totalsum = 0;
		while($orow = $ores->fetch_object()){

                $mcsql ="select * from CMSDB.CMS_ITEMS where item_id = '".$orow->itemmt_id."' limit 1";
                $mcinfo = $conn_cms->query($mcsql)->fetch_object();
                $mcode = $mcinfo->item_cd;

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
					$ord->addChild('ORDERNO',$orow->orderno);
					$ord->addChild('BUYDATE',$orow->created);
					$ord->addChild('CHANNEL',$orow->chnm);
					$ord->addChild('MENUCODE',$mcode);
					$ord->addChild('MENUNAME',$orow->itemnm);

					$ord->addChild('QTY',$orow->man1);
					$cp = $ord->addChild('COUPONS')->addChild('COUPON');
					$cp->addChild('COUPONNO',$orow->barcode_no);
					$cp->addChild('USESTATE',$orow->usegu=="1"?"Y":"N");
					$cp->addChild('USEDATE',$odetailrow->dt_use);
					$totalsum += $orow->man1;
					$totalamt += $orow->accamt;

				}else{
					while($odetailrow = $odetailres->fetch_object()){
						$ord = $track->addChild('ORDER');
						$ord->addChild('ORDERNO',$orow->orderno);
						$ord->addChild('BUYDATE',$orow->created);
						$ord->addChild('CHANNEL',$orow->chnm);
						$ord->addChild('MENUCODE',$mcode);
						$ord->addChild('MENUNAME',$orow->itemnm);
						$ord->addChild('QTY',1);
						$cp = $ord->addChild('COUPONS')->addChild('COUPON');
						$cp->addChild('COUPONNO',$orow->barcode_no);
						$cp->addChild('USESTATE',$orow->usegu=="1"?"Y":"N");
						$cp->addChild('USEDATE',$odetailrow->dt_use);
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
        if(is_numeric($v_val) and substr($v_val,0,3) == "010"){
					// 전화번호 검색
					$hp = str_replace(array("-"," "),"",$v_val);
					$hp1 = str2hash(preg_replace("/(^02.{0}|^01.{1}|[0-9]{3})([0-9]+)([0-9]{4})/","$1",$hp));
					$hp2 = str2hash(preg_replace("/(^02.{0}|^01.{1}|[0-9]{3})([0-9]+)([0-9]{4})/","$2",$hp));
					$hp3 = str2hash(preg_replace("/(^02.{0}|^01.{1}|[0-9]{3})([0-9]+)([0-9]{4})/","$3",$hp));

					$orderqry2 = "SELECT
										a.*,
										b.couponno as coupon,
										b.state as cstate,
										AES_DECRYPT(UNHEX(a.hp),'Wow1daY') as dhp

								  FROM ordermts a
								  LEFT OUTER JOIN ordermts_coupons b
								  ON a.id = b.order_id
								  WHERE a.usedate >= '$mdate'
								  AND	a.itemmt_id in ($itemstr)
								  AND	a.state= '예약완료'
								  AND	b.state = 'N'
								  AND	a.id in (select order_id from spadb.ordermts_hp where hp_1='$hp1' and hp_2='$hp2' and hp_3='$hp3') ORDER BY a.id DESC";


			 //$orderqry2 = "SELECT a.*,b.couponno as coupon, b.state as cstate , AES_DECRYPT(UNHEX(a.hp),'Wow1daY') as dhp
			//	FROM ordermts a LEFT OUTER JOIN ordermts_coupons  b ON a.id = b.order_id where and a.itemmt_id in (33261,33262,36051,36052,36053,36054,36055,36056) and  a.grmt_id = '$v_ch' AND a.usedate >= '$mdate' AND a.state= '예약완료' AND a.lasthp = '".$v_val."'";

        }else{

			$orderqry = "SELECT * FROM spadb.ordermts WHERE  itemmt_id in ($itemstr) and grmt_id = '$v_ch'  and id in (select order_id from spadb.ordermts_coupons where couponno = '$v_val')";

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
								a.itemmt_id in ($itemstr) AND
								a.state= '예약완료' AND
								a.hp = '".$ores->hp."'";
        }

		$res = $conn_cms3->query($orderqry2);



        if($res->num_rows > 0){
            $track = $xml->addChild('RCODE',"S");
            $track = $xml->addChild('RMSG',"성공");
            $track = $xml->addChild('RCNT',$res->num_rows);
            $track = $xml->addChild('ORDERS');


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
                $ord->addChild('CUSHP',$row->dhp);
                $ord->addChild('CUSOPT'," ");
//                $ord->addChild('CUSOPT',$row->bigo);
            }
        }else{
            $track = $xml->addChild('RCODE',"E");
            $track = $xml->addChild('RMSG',"조회결과가 없습니다.");
            $track = $xml->addChild('RCNT',$res->num_rows);
            $track = $xml->addChild('ORDERS');

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
        }else{
            $track = $xml->addChild('RCODE',"E");
            $track = $xml->addChild('RMSG',"사용처리 실패");
            $track = $xml->addChild('RCNT',1);
            $track = $xml->addChild('ORDERS');
        }


    break;
    case 'RC':
		$sql = "SELECT * FROM spadb.ordermts WHERE grmt_id = '$v_ch'  and id in (select order_id from spadb.ordermts_coupons where couponno = '$v_val')";
        $row = $conn_cms3->query($sql)->fetch_object();

        if($row->id){

            $ussql2 = "update spadb.ordermts_coupons set state='N', dt_use= null where couponno = '$v_val'";
            $conn_cms3->query($ussql2);

			$ussql3 = "select order_id from spadb.ordermts_coupons where order_id = '$row->id' and state ='Y'";
			$uscnt3 = $conn_cms3->query($ussql3)->num_rows;

			if($uscnt3 == 0){
	          $ussql = "update spadb.ordermts set usegu = 2, usegu_at = null where id = '".$row->id."' limit 1";
		      $conn_cms3->query($ussql);
			}

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
    default:

            $track = $xml->addChild('RCODE',"E");
            $track = $xml->addChild('RMSG',"조회결과가 없습니다.");
            $track = $xml->addChild('RCNT',$res->num_rows);
            $track = $xml->addChild('ORDERS');

}
        print($xml->asXML());

function usecouponno($no){

	$curl = curl_init();
    $url = "http://115.68.42.2:3040/use/".$no;
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

function str2hash($no){

	return strtoupper(hash("sha256", $no));

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
?>
