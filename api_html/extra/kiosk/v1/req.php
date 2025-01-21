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
 * 2022.05.17 tony 곤지암루지 다회권 사용처리 추가
 *          조회   http://extapi.sparo.cc/extra/kiosk/v1/req.php?pc=SS&pval=barcode no&ch=3823&fcno=gate
 *          사용   http://extapi.sparo.cc/extra/kiosk/v1/req.php?pc=US&pval=barcode no&ch=3823&fcno=gate
 *
 */
//242,3327,3463,3584,3088,218

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');

/*
// 20221022 tony 로그 파일 기록 남길것인지 설정(날짜까지만 남김)
$_FILE_LOG = "2023-12-31";
$_CUR_DATE = date("Y-m-d");
$_LOG = ($_CUR_DATE <= $_FILE_LOG)?true:false;
*/
$_LOG = true; 

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
if ($_LOG){
    // 10주(약2달) 이전 파일 삭제
    // 2달전 파일 삭제
    $dfnm = date("Ymd", strtotime("-2 month")) . 'kiosk.log';

    $delfile = "/home/sparo.cc/api_html/extra/kiosk/v1/txt/$dfnm";
    //echo $delfile."\n";
    if (file_exists($delfile)){
        $delrtn = unlink($delfile);
        //echo "[$delrtn]";
    }

    // 로그 파일 열기
    $fnm = date('Ymd') . 'kiosk.log';
    $fp = fopen("/home/sparo.cc/api_html/extra/kiosk/v1/txt/$fnm", 'a+');
    $adata = Array(
      'get' => $_GET,
      'apimethod' => $apimethod,
      'tranid' => $tranid
    );
    fwrite($fp, "\n[$tranid]==================================================\n");
    fwrite($fp, "[$tranid][Start] ".date("Y-m-d H:i:s")." ".print_r($adata, true));
    // fclose($fp);
}


$apiheader = getallheaders(); // http 헤더
$para = $_SERVER['QUERY_STRING'];
$logsql = "insert cmsdb.extapi_log_v1 set apinm='kiosk/v1',tran_id='$tranid', ip='".get_ip()."', logdate= now(), apimethod='$apimethod', mode='$v_mode', ch='$v_ch', val='$v_val', fnco='$v_termno', header='".json_encode($apiheader)."', querystr='$para'";
$conn_rds->query($logsql);

// Param Validation 파라미터 검증부 추가 - Jason 21.10.22
// mode 값 검증
// 2022.05.04 tony
// SS : 쿠폰조회, 4자리 입력시 핸드폰 번호 검색, 8자리 이상 입력시 쿠폰검색
// US : 쿠폰사용
// CL : 쿠폰사용 갯수(주문당 여러개 쿠폰 구매건이 있을 수 있다)
// RC : 회수
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
// 3897 놀자숲(2022.08.01)
// 3945 횡성루지 20230213 tony https://placem.atlassian.net/browse/FX2201-1
$charr = array(3871,3856,3823,3788,3782,4182,242,3327,3463,3584,3088,218,3467,3590,211,
3605,3610,3615,3619,238,3631,4012,4014,3632,3633,3634,3638,3324,3135,3481,3653,3690,3493,187,3294,
3897,
3945,
// 4006 키벤저스 20240311 tony https://placem.atlassian.net/browse/P2CCA-553 [CMS] 신규시설 키벤저스 POS 온라인 연동 확인 요청건
4006,
// 20240314 tony [CMS] 어뮤즈스파 POS 온라인 연동 확인 요청건 https://placem.atlassian.net/browse/P2CCA-557
// 어뮤즈스파 남악:업체 3947
3947,
// 어뮤즈스파 진주:업체 3948
3948,
// 중흥오투스파:업체 3949
3949,
// 20240712 tony [중흥 디스코이모션] 키오스크 CM 연동 요청의 건.(나주관광개발㈜) https://placem.atlassian.net/browse/P2D-4
92,
);

if(!in_array($v_ch,$charr)){
    if ($_LOG){
        $totms = (getms()-$s_starttm);

        fwrite($fp, "\n[$tranid][ERR] 비허용 시설코드 : [$v_ch]");
        fwrite($fp, "\n[$tranid]소요시간 : $totms ms");
        if($totms > 1000) fwrite($fp, "\n[$tranid][WARN] [$totms]>1000 ms");
        fwrite($fp, "\n[$tranid][End] exit!! ".date("Y-m-d H:i:s"));
        fwrite($fp, "\n[$tranid]==================================================\n");
        fclose($fp);
    }
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
            // 공백으로 인한 오류가 발생하여 추가 - Jason 21.12.24
	    $mcode = trim($mcode);

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
	                  FROM ordermts a LEFT OUTER JOIN ordermts_coupons  b ON a.id = b.order_id 
                          WHERE a.grmt_id = '$v_ch' 
                          AND a.usedate >= '$mdate' 
                          AND a.state= '예약완료' 
                          AND a.lasthp = '".$v_val."'

                          -- 2022.08.05 tony 조회대상 쿠폰 번호가 ordermts의 쿠폰번호에 기록되어 있는지 체크 추가
                          -- 녹테마레(3856)에서 간혹 쿠폰 중복발행 현상이 있음
                          AND (a.couponno like concat('%', b.couponno, '%') 
                            or a.barcode_no like concat('%', b.couponno, '%')
                          )
                     ";

        }else{

            $orderqry = "SELECT * 
                         FROM spadb.ordermts 
                         WHERE grmt_id = '$v_ch' 
                         -- AND usedate >= '$mdate' 
                         AND id in (select order_id from spadb.ordermts_coupons where couponno = '$v_val')";

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
                  a.usedate >= '$mdate' AND
			      a.state= '예약완료' AND
			      a.hp = '".$ores->hp."'

                          -- 2022.08.05 tony 조회대상 쿠폰 번호가 ordermts의 쿠폰번호에 기록되어 있는지 체크 추가
                          -- 녹테마레(3856)에서 간혹 쿠폰 중복발행 현상이 있음
                          AND (a.couponno like concat('%', b.couponno, '%') 
                            or a.barcode_no like concat('%', b.couponno, '%')
                          )
              ";

        }
//			a.usedate >= '$mdate' AND
        $res = $conn_cms3->query($orderqry2);


        if($res->num_rows > 0){
            $track = $xml->addChild('RCODE',"S");
            $track = $xml->addChild('RMSG',"성공");
            $track = $xml->addChild('RCNT',$res->num_rows);
            $track = $xml->addChild('ORDERS');

            if ($_LOG){
                fwrite($fp, "\n[$tranid]티켓갯수 : $res->num_rows");
            }

            while($row = $res->fetch_object()){
	        if(empty($row->coupon)) continue;

	        if($row->cstate == 'Y'){
                    $useyn = '1';

                    // 2022-05-30 tony
                    // 곤지암루지의 다회권 사용횟수가 남았는지 확인
                    if($v_ch == '3823'){
                        // 사용횟수가 남아있고, 사용일자가 오늘인 경우 미사용으로 설정 변경
                        if (isEnteredCntRemainKonjiamLuge($v_val, $row->itemmt_id) == true && (substr($row->usegu_at, 0, 10) === date('Y-m-d'))) 
                             $useyn = '2';
                    }

		} else {
                    $useyn = '2';
                }
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
                //$ord->addChild('YJLEE_USEGU_AT',$row->usegu_at);
                //$ord->addChild('YJLEE_orguseyn',$row->cstate);
                //$ord->addChild('YJLEE_ITEMMT_ID',$row->itemmt_id);

                $ord->addChild('QTY',$n);
                $ord->addChild('EXPDATE',str_replace("-", "",$row->usedate));
                $ord->addChild('STATE',$row->state);
                $ord->addChild('USTATE',$useyn);
                $ord->addChild('CUSNM',$row->usernm);

		// 전화번호가 없으면
		if(strlen($row->dhp) < 5) $row->dhp = "010-0000-0000";

                $ord->addChild('CUSHP',$row->dhp);
                $ord->addChild('CUSOPT'," ");
//                    $ord->addChild('CUSOPT',$row->bigo);

                if ($_LOG){
                    fwrite($fp, "\n[$tranid]쿠폰번호 : [$row->coupon]");
                }
            }
        }else{
            $track = $xml->addChild('RCODE',"E");
            $track = $xml->addChild('RMSG',"조회결과가 없습니다.");
            $track = $xml->addChild('RCNT',$res->num_rows);
            $track = $xml->addChild('ORDERS');

            if ($_LOG){
                fwrite($fp, "\n[$tranid]조회결과가 없음 : [$res->num_rows]");
            }
        }

        break;
    case 'US':
        // 곤지암루지(3823) 사용처리 루틴
        if($v_ch == '3823'){
            // 주문정보 조회
            $sql = "SELECT *
	            FROM spadb.ordermts
	            WHERE
		        grmt_id = '$v_ch' AND
		        id in (select order_id from spadb.ordermts_coupons where state = 'N' and couponno = '$v_val')
                    ";

            $row = $conn_cms3->query($sql)->fetch_object();

            if($row->id and $row->state == '예약완료'){

                $ussql = "update spadb.ordermts set paygu = '$v_termno', usegu = 1, usegu_at = now() where id = '".$row->id."' and usegu = '2' limit 1";
                $conn_cms3->query($ussql);

                $ussql2 = "update spadb.ordermts_coupons set state='Y', dt_use=now() where state='N' and couponno = '$v_val'";
                $conn_cms3->query($ussql2);

                usecouponno($v_val);
                
                // 사용처리 시에도 사용카운트 1회 증가
                // 응답값을 체크하지 않는다.(주문정보에 사용처리를 했기때문에 성공처리 한다)
                $rtnLuge = addEnteredCntKonjiamLuge($v_val, $row->itemmt_id);

                $track = $xml->addChild('RCODE',"S");
                $track = $xml->addChild('RMSG',"성공");
                $track = $xml->addChild('RCNT',1);
                $track = $xml->addChild('ORDERS');

                if ($_LOG){
                    fwrite($fp, "\n[$tranid]사용처리 성공 : [$v_val]");
                }
            }else{
                // 1회라도 사용처리된 쿠폰정보 조회
                // 다회권 처리가 되어야 하므로 사용처리 된 쿠폰도 조회한다.
                // 주문정보 조회
                // 주문처리된 쿠폰정보 조회
                // 다회권 처리가 되어야 하므로 오늘 사용처리 된 쿠폰을 조회한다.
                $sql = "SELECT *
                        FROM spadb.ordermts
	                WHERE true
	                    AND grmt_id = '$v_ch' 
		            AND id in (select order_id from spadb.ordermts_coupons where state = 'Y' and couponno = '$v_val')
                              -- 쿠폰 테이블에 사용처리 플레그가 Y 이면 사용처리된 쿠폰
                        ";

                $row = $conn_cms3->query($sql)->fetch_object();


                if(isset($row)){

// yjlee
                    $rtnLuge = addEnteredCntKonjiamLuge($v_val, $row->itemmt_id);

                    $track = $xml->addChild('RCODE', $rtnLuge[RCODE]);
                    $track = $xml->addChild('RMSG', $rtnLuge[RMSG]);
                    $track = $xml->addChild('RCNT',1);
                    $track = $xml->addChild('ORDERS');

// $track = $xml->addChild('RCODE',print_r($row,true));
// $track = $xml->addChild('cnt',$conn_cms3->affected_rows);

                    // 정상처리일 경우 반드시 break 해야 아래 에러 응답을 안탄다.
                    break;
                }

                $track = $xml->addChild('RCODE',"E");
                $track = $xml->addChild('RMSG',"사용처리 실패");
                $track = $xml->addChild('RCNT',1);
                $track = $xml->addChild('ORDERS');

                if ($_LOG){
                    fwrite($fp, "\n[$tranid]사용처리 실패 : [$v_val]");
                }
            }
        // 곤지암루지 이외의 다른 시설
        } else {
            $sql = "SELECT *
	            FROM spadb.ordermts
	            WHERE
		        grmt_id = '$v_ch' AND
		        id in (select order_id from spadb.ordermts_coupons where state = 'N' and couponno = '$v_val')";

            $row = $conn_cms3->query($sql)->fetch_object();

            if($row->id and $row->state == '예약완료'){

                if ($_LOG){
                    fwrite($fp, "\n[$tranid]사용처리 시작 : [$v_val]");
                }

                $ussql = "update spadb.ordermts set paygu = '$v_termno', usegu = 1, usegu_at = now() where id = '".$row->id."' and usegu = '2' limit 1";
                $rtnu1 = $conn_cms3->query($ussql);

                if ($_LOG){
                    fwrite($fp, "\n[$tranid] $ussql");
                    fwrite($fp, "\n[$tranid] update spadb.ordermts : [$rtnu1]");
                }

                $ussql2 = "update spadb.ordermts_coupons set state='Y', dt_use=now() where state='N' and couponno = '$v_val'";
                $rtnu2 = $conn_cms3->query($ussql2);

                if ($_LOG){
                    fwrite($fp, "\n[$tranid] $ussql2");
                    fwrite($fp, "\n[$tranid] update spadb.ordermts_coupons : [$rtnu2]");
                }


                usecouponno($v_val);

                $track = $xml->addChild('RCODE',"S");
                $track = $xml->addChild('RMSG',"성공");
                $track = $xml->addChild('RCNT',1);
                $track = $xml->addChild('ORDERS');

                if ($_LOG){
                    fwrite($fp, "\n[$tranid]사용처리 성공 : [$v_val]");
                }
            }else{
                $track = $xml->addChild('RCODE',"E");
                $track = $xml->addChild('RMSG',"사용처리 실패");
                $track = $xml->addChild('RCNT',1);
                $track = $xml->addChild('ORDERS');

                if ($_LOG){
                    fwrite($fp, "\n[$tranid]사용처리 실패 : [$v_val] id[".$$row->id."], state[".$row->state."]");
                }
            }
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

    // 응답결과 로깅    
/*
    $msg = $conn_rds->real_escape_string($xml->asXML());
    $logsql = "update cmsdb.extapi_log_v1 set header=concat(ifnull(header, ''), '\n\nRESULT:".date("Y-m-d H:i:s")."\n{$msg}') where tran_id='$tranid'";
    $conn_rds->query($logsql);
*/

if ($_LOG){
    $totms = (getms()-$s_starttm);

    fwrite($fp, "\n[$tranid]".$xml->asXML());
    fwrite($fp, "\n[$tranid]소요시간 : $totms ms");
    if($totms > 1000) fwrite($fp, "\n[$tranid][WARN] [$totms]>1000 ms");
    fwrite($fp, "\n[$tranid][End] ".date("Y-m-d H:i:s"));
    //fwrite($fp, "\n[$tranid][End] ".date("Y-m-d H:i:s")." ".print_r($xml, true));
    fwrite($fp, "\n[$tranid]==================================================\n");
    fclose($fp);
}

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

// yjlee
// 2022-05-18 tony
// 곤지암 루지 입장처리(사용횟수 증가)
function addEnteredCntKonjiamLuge($v_val, $itemmt_id){
    global $conn_rds;
    global $tranid, $apimethod, $v_mode, $v_ch, $v_termno, $apiheader, $para;

    // 사용횟수 카운트 증가 로그 기록
    $logsql = "insert cmsdb.extapi_log_v1 set apinm='kiosk/v1',tran_id='$tranid', ip='".get_ip()."', logdate= now(), apimethod='$apimethod', mode='$v_mode', ch='$v_ch', val='$v_val', fnco='$v_termno', header='".json_encode($apiheader)."', querystr='$para', add_entercnt"; 
    $conn_rds->query($logsql);


    // 사용횟수 카운트 증가 쿠폰 조회
    $sql = "SELECT * 
            FROM cmsdb.konjiamluge_extcoupon
            WHERE TRUE
                AND barcode = '$v_val'
                AND itemmt_id = '$itemmt_id'
           ";
    
    $res = $conn_rds->query($sql);
    $row = $res->fetch_object();

    if (isset($row) == false || empty($row))
    {
        $arRtn = array('RCODE' => "E", 'RMSG' => "입장 휫수 증가 쿠폰 조회 실패");
        return $arRtn;
    }
    // mul_enter_cnt : 전체 입장 가능 횟수
    $mul_enter_cnt = $row->mul_enter_cnt;
    // entered_cnt : 입장한 횟수
    $entered_cnt = $row->entered_cnt;
    // 입장일
    $entered_dt = $row->entered_dt;
    // last_entered_tm : 마지막 입장한 시각
    $last_entered_tm = $row->last_entered_tm;

    if (empty($entered_dt) == false && $entered_dt < date("Y-m-d")){
        $arRtn = array('RCODE' => "E", 'RMSG' => "입장일(".$entered_dt.") 당일만 사용 가능");
        return $arRtn;
    }


    // 사용횟수가 남은 경우
    if ($mul_enter_cnt > $entered_cnt){

        // 처음 사용
        if($entered_cnt == 0){
        // 입장날짜 입력, 사용횟수 증가
        $sqlAdd = "UPDATE cmsdb.konjiamluge_extcoupon
                SET
                    entered_cnt = '".($entered_cnt+1)."',
                    entered_dt = '".(date('Y-m-d'))."',
                    last_enterd_tm = '".(date('H:i:s'))."'
                WHERE TRUE
                    AND barcode = '$v_val'
                    AND itemmt_id = '$itemmt_id'
                    AND entered_cnt = '$entered_cnt'
                "; 
        }else{
        // 사용횟수 증가
        $sqlAdd = "UPDATE cmsdb.konjiamluge_extcoupon
                SET 
                    entered_cnt = '".($entered_cnt+1)."',
                    last_enterd_tm = '".(date('H:i:s'))."'
                WHERE TRUE
                    AND barcode = '$v_val'
                    AND itemmt_id = '$itemmt_id'
                    AND entered_cnt = '$entered_cnt'
                ";
        }

//$arRtn = array('RCODE' => "X", 'RMSG' => $sqlAdd);
//return $arRtn;

        $resAdd = $conn_rds->query($sqlAdd);
        $aff_rows = $conn_rds->affected_rows;
        if ($aff_rows == 1){
            // 입장 횟수 추가 성공 
            $arRtn = array('RCODE' => "S", 'RMSG' => "성공 - 입장 ".($entered_cnt+1)."/$mul_enter_cnt");
        } elseif ($aff_rows > 1){
            // 1개 이상 업데이트가 되면 문제 있는 것이므로 로그 남김
            $logsql = "insert cmsdb.extapi_log_v1 set apinm='kiosk/v1',tran_id='$tranid', ip='".get_ip()."', logdate= now(), apimethod='$apimethod', mode='$v_mode', ch='$v_ch', val='$v_val', fnco='$v_termno', header='".json_encode($apiheader)."', querystr='$para', add_entercnt='$aff_rows'"; 
            $conn_rds->query($logsql);

            // 입장 횟수 추가 성공 
            $arRtn = array('RCODE' => "S", 'RMSG' => "성공 - 입장");
 
        } else {
            // 입장 횟수 추가 실패
            $arRtn = array('RCODE' => "E", 'RMSG' => "입장 휫수 증가 실패 [$aff_rows]");
        }

    } else {
        // 무두 사용함
        $arRtn = array('RCODE' => "E", 'RMSG' => "모두 사용한 티켓 $entered_cnt/$mul_enter_cnt");
    }

    return $arRtn;

}


// yjlee
// 2022-05-30 tony
// 곤지암 루지 사용횟수 남았는지 체크
function isEnteredCntRemainKonjiamLuge($v_val, $itemmt_id){
    global $conn_rds;
    global $tranid, $apimethod, $v_mode, $v_ch, $v_termno, $apiheader, $para;

    // 사용횟수 카운트 증가 쿠폰 조회
    $sql = "SELECT * 
            FROM cmsdb.konjiamluge_extcoupon
            WHERE TRUE
                AND barcode = '$v_val'
                AND itemmt_id = '$itemmt_id'
           ";

    $res = $conn_rds->query($sql);
    $row = $res->fetch_object();

    if (isset($row) == false || empty($row))
    {
        return false;
    }
    // mul_enter_cnt : 전체 입장 가능 횟수
    $mul_enter_cnt = $row->mul_enter_cnt;
    // entered_cnt : 입장한 횟수
    $entered_cnt = $row->entered_cnt;

    // 사용횟수가 남은 경우
    if ($mul_enter_cnt > $entered_cnt){
        return true;
    } else {
        // 무두 사용함
        return false;
    }
}

?>
