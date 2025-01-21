<?php

/*
 *
 * CMS 채널 주문 인터페이스
 *
 * 작성자 : 이정진
 * 작성일 : 2017-08-20
 *
 * 주문 등록(POST) https://gateway.sparo.cc/extra/agency/v2/dealcode/{상품코드}
 * {"orderNo":"채널주문번호","userName":"고객명","userHp":"고객핸드폰","orderDesc":"기타옵션"}
 *
 * 주문 조회(GET) https://gateway.sparo.cc/extra/agency/v2/chorderno/{채널주문번호}
 * 주문 취소(PATCH) https://gateway.sparo.cc/extra/agency/v2/chorderno/{채널주문번호}
 *
 * 20220624 tony
 * 한화(하비스_habis) 티켓 추출시 전화번호에 국가번호 있으면 에러나는 버그 패치
 * 결과 데이터 DB에 기록 남김
 *
 * 20220822 tony
 * 롯데월드 핀 실시간 뽑아내게 루틴 변경
 * 
 * 20220824 09:55 tony
 * 한화 하비스 실시간 핀 뽑아내게 루틴 변경
 * 주문번호 2회 생성하는 것을 1회 생성하도록 패치(한화 핀 중복 생성 방지됨)
 */
error_reporting(0); 

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
require_once ('/home/placedev/php_script/lib/placemlib.php');
header("Content-type:application/json");

$conn_rds->query("set names utf8");

$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

// 파라미터
$itemreq = explode("/",$para);
$jsonreq = trim(file_get_contents('php://input'));


list($microtime,$timestamp) = explode(' ',microtime());
$tranid = $timestamp . substr($microtime, 2, 3);
$logsql = "insert cmsdb.extapi_log set apinm='대매사통합',tran_id='$tranid', ip='".get_ip()."', logdate= now(), apimethod='$apimethod', querystr='".$para."', header='".json_encode($apiheader)."', body='".$jsonreq."'";
$conn_rds->query($logsql);


// PM 통합 로그
// 인터페이스 로그
// $tranid = date("Ymd").genRandomStr(10); // 트렌젝션 아이디
// $pmlog = array(
// 	"chnm"=>"대매사통합",
// 	"method"=>$apimethod,
// 	"header"=>$apiheader,
// 	"querystr"=>$para,
// 	"body"=>$jsonreq,
// 	"ip"=>get_ip()
// );

// put_logs($tranid,$pmlog);
// 엘라스틱서치 로그 서버 사용 종료 - 22.03.14

if(get_ip() =="106.254.252.100"){
//print_r(put_logs($tranid,$pmlog));
}



//$logsql = "insert cmsdb.cms_logs set log_tag= 'pm_agency_order', log_result = 'R', log_content='".addslashes(json_encode($pmlog))."' ";
//$conn_rds->query($logsql);


// 인증 정보 조회
$auth = $apiheader['Authorization'];

if(!$auth) $auth = $apiheader['authorization'];

$authqry = "SELECT * FROM spadb.extapi_config WHERE authkey = '$auth' limit 1";
$authres = $conn_cms3->query($authqry);
$authrow = $authres->fetch_object();

$aclmode = $authrow->aclmode;

if($aclmode == "IP"){
// ACL 확인
    if(!in_array(get_ip(),json_decode($authrow->accessip,false))){
        header("HTTP/1.0 401 Unauthorized");
        $res = array("Result"=>"4100","Msg"=>"아이피 인증 오류 : ".get_ip());
        echo json_encode($res);
        _log_update_result($res);
        exit;
    }
}

// API키 확인
if(!$authrow->cp_code){

    header("HTTP/1.0 401 Unauthorized");
    $res = array("Result"=>"4100","Msg"=>"인증 오류");
    echo json_encode($res);
    _log_update_result($res);
    exit;

}else{

    $cpcode = $authrow->cp_code; // 채널코드
    $cpname = $authrow->cp_name; // 채널명
    $grmt_id = $authrow->cp_grmtid; // 채널 업체코드
    $ch_id = $grmt_id;
    $logsql = "UPDATE cmsdb.extapi_log SET chnm = '$cpname' WHERE tran_id = '$tranid'";
    $conn_rds->query($logsql);

}

if(get_ip() =="106.254.252.100"){
//echo $itemreq[0];
}

// REST Method 분기
switch($apimethod){
    case 'GET':
        // 주문 조회
//        print_r($itemreq);
            if($itemreq[0] =="GoodsList"){
                get_deallist($authrow->cp_code);
            }else{
                get_extorder_info($ch_id,$itemreq);
            }


    break;
    case 'POST':
        // 주문 등록
        set_extorder_insert($cpcode,$ch_id,$itemreq,$jsonreq);
    break;
    case 'PATCH':
        // 주문 취소, 변경
        switch($itemreq[0]){
            case 'chorderno':
                // 주문 취소
                set_extorder_update($ch_id,$itemreq,$jsonreq);

            break;
            case 'sendeticket':
               // 문자 재전송
                resend_ticket($ch_id,$itemreq,$jsonreq);
            break;
            default:
            header("HTTP/1.0 400 Bad Request");
            $res = array("Result"=>"4000","Msg"=>"파라미터 오류");
            echo json_encode($res);
        }


    break;
    default:
        header("HTTP/1.0 400 Bad Request");
        $res = array("Result"=>"4000","Msg"=>"파라미터 오류");
        echo json_encode($res);
}

function resend_ticket($ch_id,$itemreq,$jsonreq){
    global $conn_cms3;

    $ch_orderno = $itemreq[1];
     $usql = "SELECT
                *,
                AES_DECRYPT(UNHEX(hp),'Wow1daY') as dhp
             FROM
                spadb.ordermts
             WHERE
                 ch_orderno = '$ch_orderno'
             AND ch_id = '$ch_id'
             LIMIT 1";

    $ures = $conn_cms3->query($usql);
    $urow = $ures->fetch_object();

   if($urow->id){

        $mmscnt = $urow->optsu1;

        if($mmscnt < 3){
            $mcnt = $mmscnt+1;
            $sendsql = "update spadb.ordermts set smsgu='N', optsu1='$mcnt' where id = '".$urow->id."' limit 1";
            $conn_cms3->query($sendsql);

            $result = array("Code" => "1000",
                            "Msg" => "문자 재발송 성공",
                            "Result" => $order);

            header("HTTP/1.0 200 OK");
            echo json_encode($result);

        }else{

            $result = array("Code" => "5000",
                            "Msg" => "문자 재발송 횟수 초과",
                            "Result" => $order);

            header("HTTP/1.0 200 OK");
            echo json_encode($result);

        }

   }else{
        $result = array("Code" => "4000",
                        "Msg" => "발송 가능 주문이 없습니다.",
                        "Result" => $order);

        header("HTTP/1.0 404 OK");
        echo json_encode($result);
   }
}

// 채널 주문 테이블 주문 정보
function get_chorderno($cpcode,$orderno){
    global $conn_cms;

    $itemsql = "SELECT * pcmsdb.ordermts_ext where channel = '$cpcode' and orderno_num = '$orderno' limit 1";

    $itemres = $conn_cms->query($itemsql);
    $itemurow = $itemres->fetch_object();

    return $itemurow;
}

// 주문 조회
function get_extorder_info($grmt_id,$itemreq){
    global $conn_cms3;

    $ch_orderno = $itemreq[1];

    $usql = "SELECT
                *,
                AES_DECRYPT(UNHEX(hp),'Wow1daY') as dhp
             FROM
                spadb.ordermts
             WHERE
                 ch_orderno = '$ch_orderno'
             AND ch_id = '$grmt_id'
             LIMIT 1";

if(get_ip() =="106.254.252.100"){
 //   echo $usql;
}
    $ures = $conn_cms3->query($usql);
    $urow = $ures->fetch_object();

    if($urow->id){

        // 외부 쿠폰 사용 조회(에버랜드, 롯데등)
        // 쿠폰의 prefix로 구분함

        $cptype = substr($urow->barcode_no,0,2);

        switch($cptype){
            case 'CB': // 에버랜드 쿠폰 조회
            case 'EL': // 에버랜드 쿠폰 조회
            case 'S0': // 에버랜드 쿠폰 조회
                $useflag = getuseev(str_replace(";", "",$urow->barcode_no));
                $usedate = $urow->usegu_at;

            break;
            case '04': // 롯데워터 쿠폰 조회

                $res = explode(";",getusewp(str_replace(";", "",$urow->barcode_no)));

				if(empty($res)){
					$useflag = $urow->usegu;
					$usedate = $urow->usegu_at;
				}else{
					if($res[1] == '1'){
						$useflag = '1';
					}else{
						$useflag = '2' ;
					}
					$usedate = $urow->usegu_at;
				}

            break;
            default:
                $useflag = $urow->usegu;
                $usedate = $urow->usegu_at;
        }

				if(strlen($urow->barcode_no) < 3) $urow->barcode_no = null;

        $order[] = array("orderno" => $urow->orderno, // 주문번호
                         "ch_orderno" => $urow->ch_orderno, // 채널 주문번호
                         "cus_nm" => $urow->usernm, // 고객명
                         "cus_hp" => $urow->dhp, // 고객핸드폰
                         "state" => $urow->state, // 주문상태
                         "itemcode" => $urow->itemmt_id, // 상품코드
                         "qty" => $urow->man1, // 주문 수량
                         "expdate" => $urow->usedate, // 유효기간(이용일)
                         "use" => $useflag, // 이용구분
                         "usedate" => $usedate, // 이용처리일
                         "canceldate" => $urow->canceldate, // 취소일
                         "couponno" => str_replace(";","",$urow->barcode_no) // 쿠폰번호
                        );

        $result = array("Code" => "1000",
                        "Msg" => "성공",
                        "Result" => $order);

        header("HTTP/1.0 200 OK");
        echo json_encode($result);

    }else{
        // 조회 결과가 없을시
        $result = array("Code" => "4002",
                        "Msg" => "조회 결과가 없습니다.",
                        "Result" => null);

        header("HTTP/1.0 404");
        echo json_encode($result);
    }

}

// 주문 주문등록
function set_extorder_insert($cpcode,$grmt_id,$itemreq,$jsonreq){
    global $conn_cms;
    global $conn_cms2;
    global $conn_cms3;
    global $conn_rds;
    global $cpname;
    global $tranid;

    usleep(rand(2000,700000));

    $itemcode = $itemreq[1];
    $orderno = date("Ymd")."_".genRandomStr(12);


    $chorderno = "0";
    $info = json_decode($jsonreq);



    // 유효기간
    $oexpdate = $info->expDate;

    if(!$itemcode){
        header("HTTP/1.0 400 Bad Request");
        $res = array("Result"=>"4000","Msg"=>"필수 파라미터 오류 ep");
        echo json_encode($res);
        _log_update_result($res);
        exit;
    }

    if(!$info->userName){
        header("HTTP/1.0 400 Bad Request");
        $res = array("Result"=>"4000","Msg"=>"필수 파라미터 오류 nm");
        echo json_encode($res);
        _log_update_result($res);
        exit;
    }
    if($info->useHp) $info->userHp = $info->useHp;

    if(!$info->userHp){
        header("HTTP/1.0 400 Bad Request");
        $res = array("Result"=>"4000","Msg"=>"필수 파라미터 오류 hp");
        echo json_encode($res);
        _log_update_result($res);
        exit;
    }
		// 전화번호 예외처리


    if(chk_chorderno($cpcode,$info->orderNo)){

        if($cpcode == "TMON2"){
            // 일부 채널의 경우 기발권 주문 내역을 표시한다.

            $vorow = $conn_rds->query("select order_no, couponno from cmsdb.pcms_extorder where ch_code = '$cpcode' and ch_orderno = '$info->orderNo' limit 1")->fetch_object();

            $order[]= array("OrderNo" => $vorow->order_no,
                            "CouponNo" => $vorow->couponno);

            $result = array("Code" => "1000",
                            "Msg" => "성공",
                            "Result" => $order);

            header("HTTP/1.0 200");

        }else{

            $result = array("Code" => "4001",
                            "Msg" => "이미 등록된 주문이거나 중복된 주문번호입니다. 404",
                            "Result" => null);

            header("HTTP/1.0 400 Bad Request");
        }

        echo json_encode($result);
        _log_update_result($result);
        exit;
    }else{

      // 신세계 TV
        if($cpcode == "3830"){

           $itemsql = "SELECT * from pcmsdb.items_ext where channel = '3830' and chitem_id = '$itemcode' and useyn='Y' order by id desc limit 1";
            $ri = $conn_cms->query($itemsql)->fetch_object();
            $itemcode = $ri->pcmsitem_id;
        }


        // 임시 주문 테이블에 주문 입력
        $itemsql = "SELECT * from pcmsdb.items_ext where channel = '$cpcode' and pcmsitem_id = '$itemcode' and useyn='Y' order by id desc limit 1";

        $itemres = $conn_cms->query($itemsql);
        $itemurow = $itemres->fetch_object();

        // 외부 코드 사용 채널의 경우 한번더 체크

        if(!$itemurow->id){

           // 조회 결과가 없을시
            $result = array("Code" => "4003",
                            "Msg" => "상품 정보가 없거나 판매중이 아닙니다.",
                            "Result" => null);
            header("HTTP/1.0 400 Bad Request");

            echo json_encode($result);


			//if($cpcode=="3827") exit;
			//if($cpcode=="3838") exit;



            // 담당자 문자 발송
            $msg = "판매채널 연동 누락({$cpname}/{$cpcode}) - $itemcode";

			if(strlen($itemcode) > 5){
		        //send_notice("판매채널 연동 설정 오류", "판매채널에서 잘못된 코드 설정 - {$cpname} / {$cpcode} - $itemcode");
            }else{
		        //send_notice("판매채널 연동 설정 오류", "플레이스엠에서 판매채널 연동누락 - {$cpname} / {$cpcode} - $itemcode");
			}
			    // send_report("01090901678",$msg);
            _log_update_result($result);
            exit;

        }else{
            $sellcode = $itemurow->gu;
            $typefac = $itemurow->typefac;

			if(!$info->Qty) $info->Qty = 1;

            if($sellcode){
                $n = 1;


				if($info->userName == "테스트"){
					$cpno = get_extcoupon2("EXT",$sellcode,$n,$orderno,$info->userName,$info->userHp);
				}else{
	                $cpno = get_extcoupon2("EXT",$sellcode,$n,$orderno,$info->userName,$info->userHp);
				}

				// 쿠폰코드가 있으면
				if(strlen($info->couponNo) > 2){
					$cpno = $info->couponNo;
				}
                // 쿠폰발급 실패시
                if(strlen($cpno) < 2){

					// 쿠폰 발급 실패시 핀복사 확인
					copyapicoupon($sellcode);

                    $result = array("Code" => "4006",
                    "Msg" => "쿠폰발급 오류",
                    "Result" => null);
                    header("HTTP/1.0 400 Bad Request");

                    echo json_encode($result);
                    _log_update_result($result);
                    exit;

                }

            }else{
				// 플레이스엠 쿠폰 생성
				$_pmcode = array('24658','30635','30634','30633','30632','30617','30616','30615','30614','28947','30635','30634','31746','31745','31744','31743','31756','31755','31754','31753','30617','30616','30615','30614','28948','32464','32322','32323','32324','34845','34846','34847');
				if(in_array($itemcode,$_pmcode)){
					 $cpno = $itemcode.genRandomNum(11);
				}else{
				// 쿠폰코드가 있으면
					if(strlen($info->couponNo) > 2){
						$cpno = $info->couponNo;
					}
				}

                // 한화
                if($typefac == "HW"){
                    $hwworld = array();
                    $hwworld[] = array(
                        //"ORDERNO"=>$info->orderNo,
                        "ORDERNO"=>$orderno,
                        "SELLCODE"=>$itemcode,
                        "UNIT"=>$info->Qty,
                        "RCVER_NM"=>$info->userName,
                        "RCVER_TEL"=>_phone($info->userHp)
                    );

                    //_log_update_result($hwworld);
                    $habis_rtn = json_decode(_get_hpcoupon(json_encode($hwworld)));
                    $cpno = $habis_rtn->couponno;

                    if(empty($cpno)){
                        $result = array("Code" => "4006",
                        "Msg" => "쿠폰발급 오류-HW",
                        "Result" => null);
                        header("HTTP/1.0 400 Bad Request");

                        echo json_encode($result);
                        _log_update_result($result);
                        _log_update_result($habis_rtn);
                        exit;
                    }
                }

                // 제주 키자니아
                if(get_ip() =="106.254.252.100"){
                  //echo "$typefac";
                }

                // 한유망, 해외경우 자동 핀생성
				$_fcode = array("HYW","KKDY","KLOOK","3721","TMON2","SPC");

				if(in_array($cpcode,$_fcode)){
					if(strlen($cpno) < 3) $cpno = $itemcode.genRandomNum(11);
				}

				// 대명
				if($typefac == "DM") {
                    // 20230810 tony https://placem.atlassian.net/browse/XH2201-3 [오션월드] 위메프 사용전환 확인 요청
                    // 대명 소노 오션월드 시설을 소셜3사에서 판매하기 시작하는데, 채널 핀번호 보관해야 함.
                    // 위메프 기준으로 프로그래밍중이므로 티몬과 쿠팡은 별도의 검증이 필요하다.
                    if(in_array($cpcode, array("WMP", "TMON2", "CPN"))){
                    }else{
					    $cpno = "0";
                    }
				}

                // 롯데 티켓인 경우
                // 쿠폰번호가 꼽혀서 주문등록이되면 그대로 유지, 없으면 "0"
                if($typefac == "LWB2B" || $typefac == "LW"){
                    if(strlen($info->couponNo) > 2){
						$cpno = $info->couponNo;
					}else{ 
                        $cpno = "0";
                    }
                } 
 
				// 키자니아 제주
				if($typefac == "JK") {

                    $jkid = array();
                    $jkid = array("ORDERNO"=>$info->orderNo,
                        "SELLCODE"=>$itemcode,
                        "UNIT"=>$info->Qty,
                        "RCVER_NM"=>$info->userName,
                        "RCVER_TEL"=>$info->userHp
                    );

                    $cpno = json_decode(_get_kidzcoupon(json_encode($jkid)));
		        }

				// 휘닉스스키,블루캐니언
				if($typefac == "PB") {

				  $pborders = array(
						"oid"=>$info->orderNo,
						"qty"=>1,
						"itemid"=>$itemcode,
						"usernm"=>trim($info->userName),
						"userhp"=>str_replace("-","",$info->userHp),
						"ch_orderno"=>$info->orderNo,
						"ch_id"=>$grmt_id
				  );
					//print_r($pborders);
				  $_pres = json_decode(get_phoenixno($pborders));
				  $cpno = $_pres->rprsBarCd;

				}

				// Sticket일경우
				if($typefac == "EL" or $typefac == "CB") {
					$cpno = _real_sticketno($itemcode,$cpcode,$info->orderNo);

				}

            }


       		$extsql = "insert pcmsdb.ordermts_ext set
						order_num = '".$info->orderNo."',
						sell_code = '".$itemcode."',
						buy_opt = '".$info->orderDesc."',
						buy_name = '".$info->userName."',
						buy_hp = '".$info->userHp."',
						buy_count = '".$info->Qty."',
						channel = '".$cpcode."',
						postcode =  '".$info->postCode."',
						address =  '".$info->addr1."',
						address2 =  '".$info->addr2."',
						ip = '".get_ip()."',
						regdate = now()";

            $conn_cms->query($extsql);


            // 롯데 티켓은 핀번호가 채널에서 부터 꼽혀서 오지 안으면 여기서는 저장되지 않는다.
            // 롯데 티켓은 주문 꼽은 후에(order_cms()) 호출 후에 즉시 설정된다.
            $extsql2 = "insert cmsdb.pcms_extorder set
                            date_order = now(),
                            ch_orderno = '".$info->orderNo."',
                        	  ch_code = '".$cpcode."',
                        	  order_itemcode = '".$itemcode."',
                            order_state = 'N',
                            couponno = '$cpno',
                            fac_code = '$typefac'
                            ";
            $conn_rds->query($extsql2);

         // 유효기간권 처리
         if($oexpdate){
            $usabledate = $oexpdate;
         }else{
            $usabledate = $itemurow->usedate;
         }

         if(is_array($cpno)){

            $res = order_cms($orderno,$info->orderNo, $itemcode,$info->Qty,$usabledate,$state,$info->userName,$info->userHp,implode(";",$cpno),$info->orderDesc);

         }else{

           $res = order_cms($orderno,$info->orderNo, $itemcode,$info->Qty,$usabledate,$state,$info->userName,$info->userHp,$cpno,$info->orderDesc);

         }

        // 롯데 티켓인 경우 ordermts에 쿠폰번호 바로 설정
        if($typefac == "LWB2B" || $typefac == "LW"){
            $logtm[] = __LINE__." ".date("H:i:s");
            $lotte_order_sql = "SELECT
                    *,
                    AES_DECRYPT(UNHEX(hp),'Wow1daY') as dhp
                 FROM
                    spadb.ordermts
                 WHERE
                     ch_orderno = '".$info->orderNo."'
                 AND orderno = '$res'
                 LIMIT 1";

            $ures = $conn_cms3->query($lotte_order_sql);
            $urow = $ures->fetch_object();
            $logtm[] = __LINE__." ".date("H:i:s");

            // 조회가 성공 && 바코드길이가 3보다 작으면
            if($urow->id && strlen($urow->barcode_no) < 3){
                $logtm[] = __LINE__." ".date("H:i:s");
                // 롯데월드 핀 뽑아오기
                $cpno = set_lotte_pincode($urow);
                $logtm[] = __LINE__." ".date("H:i:s");
            }
            $logtm[] = __LINE__." ".date("H:i:s");
            // 조회가 실패하더라도 롯데 핀 넣어주는 배치가 돌고 있으므로 정상처리한다.

            _log_update_result($logtm);
        } 

         // 상품 정보


         $order[]= array("OrderNo" => $res,
                         "CouponNo" => $cpno);

         $result = array("Code" => "1000",
                         "Msg" => "성공",
                         "Result" => $order);

         header("HTTP/1.0 200");
         echo $rts = json_encode($result);

				 // 로그 업데이트
				 //$logsql2 = "update cmsdb.extapi_log set apiresult = '".addslashes($rts)."' where tran_id='$tranid' limit 1";
         //$conn_rds->query($logsql2);

         _log_update_result($result);

         exit;

        }


    }
}

function copyapicoupon($sellcode){
    global $conn_cms;
	$sql = "update pcmsdb.cms_coupon set api='S' where ccode = '$sellcode' limit 1";
    $conn_cms->query($sql);
}

// cms 주문입력
function order_cms($orderno,$chorderno,$itemcode,$qty,$expdate,$state,$usernm,$userhp,$couponno,$orderdesc) {

    global $conn_cms;
    global $conn_cms3;
    global $conn_rds;
    global $cpname;
    global $ch_id;
    if(empty($orderno)) $orderno = date("Ymd")."_".genRandomStr(12);
    $mdate = date("Y-m-d");
    $iteminfo = get_iteminfo($itemcode,$expdate);

	   $lasthp = substr($userhp,-4);
	    if($lasthp == "0000") $userhp = "02-1544-3913";

    // 휴대번호 변환

    if(substr($userhp,0,3) == "82-"){

      $userhp = str_replace("82-","0",$userhp);
      if(substr($userhp,0,2) == "00") $userhp = substr($userhp,1,20);

    }else{

        $userhp  = get_inthp($userhp);

    }



    $man1 = $qty;

    if(strlen($couponno) < 2) $couponno = "0";
    if(strlen($usernm) < 1) $usernm = $cpname;

    $mode = "INSERT spadb.ordermts SET created = now(),";

	$where = "
		gu = 'J',
		updated = now(),
		mechstate 	=	'정산대기',
		meipstate 	=	'정산대기',
		site	=	'SSO',
		orderno = '$orderno',
		mdate	= '$mdate',
		usedate	= '$expdate',

		jpnm	=  '".$iteminfo['jpnm']."',
		itemnm	=  '".$iteminfo['itemnm']."',
		man1	=	'$man1',
		man2	=	'0',
		chnm	=	'".$cpname."',
		grnm 	=	'".$iteminfo['grnm']."',
		jpmt_id 	=	'".$iteminfo['jpmt_id']."',
		itemmt_id 	=	'".$iteminfo['itemid']."',
		ch_id 	=	'".$ch_id."',
		pricemt_id  = '".$iteminfo['price_id']."',
		grmt_id  = '".$iteminfo['gtmt_id']."',
        ch_orderno = '".$chorderno."',
		usernm 	=	'".$usernm."',
		hp	=	hex(aes_encrypt( '".$userhp."', 'Wow1daY' )),
		usernm2	=	'$usernm',
		hp2	=	hex(aes_encrypt( '".$userhp."', 'Wow1daY' )),
		dangu = '공통권',
		amt	=	'".$iteminfo['price_sale']."',
		accamt	=	'".$iteminfo['price_sale']."',
		damnm 	=	'시스템',
		usegu = '2',
		barcode_no = '".$couponno."',
		state	=	'예약완료'
		";

         $conn_cms3->query($mode.$where);
    // 임시 테이블도 업데이트 시킴
    $otsql = "update cmsdb.pcms_extorder set order_no = '$orderno',expdate= '$expdate' where ch_orderno = '$chorderno' limit 1";
    $conn_rds->query($otsql);
         return $orderno;
}

// 랜덤 스트링
function genRandomStr($length = 10) {
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}

	return $randomString;
}

function genRandomChar($length = 10) {
	$characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}

	return $randomString;
}

// 랜덤 10진수
function genRandomNum($length = 10) {
	$characters = '0123456789';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}

	return $randomString;
}

// 연동 정보 조회
function get_iteminfo($itemid,$expdate){
    global $conn_cms;

    // 상품 정보
    $itemsql = "SELECT * from CMSDB.CMS_ITEMS where item_id = '$itemid' limit 1";
    $itemres = $conn_cms->query($itemsql);
    $itemurow = $itemres->fetch_object();

    // 업체 정보
    $cpsql = "SELECT *  from CMSDB.CMS_COMPANY where com_id = '".$itemurow->item_cpid."' limit 1";
    $cpcres = $conn_cms->query($cpsql);
    $cpcrow = $cpcres->fetch_object();

    // 시설 정보
    $facsql = "SELECT *  from CMSDB.CMS_FACILITIES where fac_id = '".$itemurow->item_facid."' limit 1";
    $facres = $conn_cms->query($facsql);
    $facrow = $facres->fetch_object();

    // 가격 정보
    $pricesql = "SELECT * from CMSDB.CMS_PRICES where price_itemid = '$itemid' and price_date = '$expdate' limit 1";
    $priceres = $conn_cms->query($pricesql);
    $pricerow = $priceres->fetch_object();

    $result = array(
                    "itemid"=> $itemurow->item_id,
                    "itemnm"=> $itemurow->item_nm,
                    "gtmt_id"=> $cpcrow->com_id,
                    "grnm"=> $cpcrow->com_nm,
                    "jpmt_id"=> $itemurow->item_facid,
                    "jpnm"=> $facrow->fac_nm,
                    "price_id"=> $pricerow->price_id,
                    "price_sale"=> $pricerow->price_sale,
                    "price_in"=> $pricerow->price_in,
                    "price_out"=> $pricerow->price_out
                    );

    return $result;
} 

// 주문 주문 취소, 변경
function set_extorder_update($grmt_id,$itemreq,$jsonreq){

    global $conn_cms3;
    global $conn_rds;

    $ch_orderno = $itemreq[1];

    $usql = "SELECT
                *,
                AES_DECRYPT(UNHEX(hp),'Wow1daY') as dhp
             FROM
                spadb.ordermts
             WHERE
                 ch_orderno = '$ch_orderno'
             AND ch_id = '$grmt_id'
             AND usegu = '2'
             LIMIT 1";

    $ures = $conn_cms3->query($usql);
    $urow = $ures->fetch_object();

    if(get_ip() =="106.254.252.100"){
        //echo "개발시에만 출력됨 : $usql\n";
    //print_r($urow);
    }

    // 20231212 tony https://placem.atlassian.net/browse/P2CBSC-7   [SSG.COM] 플레이스엠 : 취소 이후 사용 처리 쿠폰 확인 요청 건
    // 한화(하비스) 시설(휘닉스파크;431) 상품일 경우 시설에 핀 상태를 확인한다. 사용처리 체크 텀이 너무 길어서(몇시간, 하루 4+2회) 실시간 반영이 안됨
    if ($urow->grmt_id == "431"){
        //echo "휘닉스파크\n";
        $pbarcode_no = array_filter(explode(";",$urow->barcode_no));
        // 쿠폰번호는 1개여야 함
        if(count($pbarcode_no) == '1'){
            $psql= "SELECT * FROM spadb.phoenix_pkgcoupon where ch_id='{$urow->ch_id}' and rprsBarCd='{$pbarcode_no[0]}'";
            //echo "$psql\n";

            $pres = $conn_cms3->query($psql);
            $prow = $pres->fetch_object(); 
            //print_r($prow);
            // 시설에 사용상태 확인
            $rprsSellNo = $prow->rprsSellNo;
            if(isset($rprsSellNo) == false || empty($rprsSellNo)){
                    // 핀폐기 불가 대상
                    $order[]= array("OrderNo" => null,
                                     "CouponNo" => null);
                    
                    $result = array("Code" => "4000",
                                     "Msg" => "취소할수 없거나 변경이 불가능한 주문...fac fin",
                                     "Result" => $order);
                    
                    header("HTTP/1.0 400");
                    echo json_encode($result);
                    
                    // 결과 기록
                    _log_update_result($result);

                    exit;
            }else{
                $pdata = getPhoenixTicketStatus($rprsSellNo);
                $pdata = json_decode($pdata);
                // 쿠폰 조회 결과 기록
                _log_update_result($pdata);
                // statusCode
                // 100 미사용 - 전체 취소 가능 합니다.
                // 101 일부사용 - 일부 사용으로 취소 불가능 합니다.
                // 102 전체사용 - 사용 완료
                // 104 구매취소
                if($pdata->statusCode != "100"){
                    // 핀폐기 불가 대상
                    $order[]= array("OrderNo" => null,
                                     "CouponNo" => null);

                    $result = array("Code" => "4000",
                                     "Msg" => "취소할수 없거나 변경이 불가능한 주문...pin stat",
                                     "Result" => $order);

                    header("HTTP/1.0 400");
                    echo json_encode($result);

                    // 결과 기록
                    _log_update_result($result);
                    exit;
                }else{
                    // 취소 가능-이후 처리 동일
                }
            }
        }else{
            // 쿠폰번호 이상
            $order[]= array("OrderNo" => null,
                             "CouponNo" => null);

            $result = array("Code" => "4000",
                             "Msg" => "취소할수 없거나 변경이 불가능한 주문...cnt",
                             "Result" => $order);

            header("HTTP/1.0 400");
            echo json_encode($result);

            // 결과 기록
            _log_update_result($result);

            exit;
        }
    }

    // 이미 사용된 티켓
    // 쿼리에서 안나올 조건이지만 혹시나 시험이나 디버깅시에 usegu='2' 조건을 주석처리하면 문제생기지 않도록 예방
    if($urow->usegu == '1'){
        $order[]= array("OrderNo" => null,
                         "CouponNo" => null);

        $result = array("Code" => "4000",
                         "Msg" => "취소할수 없거나 변경이 불가능한 주문...used",
                         "Result" => $order);

        header("HTTP/1.0 400");
        echo json_encode($result);

        // 결과 기록
        _log_update_result($result);

        exit;
    }
  
    if($urow->id){

        // 쿠폰번호 사용확인
        $cpusql = "select * from spadb.ordermts_coupons where order_id = '".$urow->id."' ";
        $cpures = $conn_cms3->query($cpusql);
        $cpflag = "N";

        while($cpurow = $cpures->fetch_object()){
            $cpno = $cpurow->couponno;

            $cptype = substr($cpno,0,2); 

            switch($cptype){
                case 'CB': // 에버랜드 쿠폰 조회
                case 'EL': // 에버랜드 쿠폰 조회
                case 'S0': // 에버랜드 쿠폰 조회
                    $useflag = getuseev($cpno);
                    $usedate = $urow->usegu_at;

                break;
                case '04': // 롯데워터 쿠폰 조회

                    $res = explode(";",getusewp($cpno));

					if($res[1] == '1'){
						$useflag = '1';
					}else{
						$useflag = '2' ;
					}

                    $usedate = $urow->usegu_at;

                break;
                default:
                    $useflag = $urow->usegu;
                    $usedate = $urow->usegu_at;
            }

			// 제주 아쿠아 한화
			if($urow->grmt_id == "3504"){
				$res = json_decode(_get_habiscoupon($cpno));
				if($res->status == "30"){
					$useflag = "1";
				}else{
					$useflag = "2";
				} 
			}
        }

        if( $useflag != "1"){
            if(strlen($urow->barcode_no) < 2) $urow->barcode_no = null;

            $order[]= array("OrderNo" => $urow->ch_orderno,
                            "CouponNo" => $urow->barcode_no);

            $result = array("Code" => "1000",
                             "Msg" => "취소 성공",
                             "Result" => $order);

            $cusql = "update spadb.ordermts set state='취소',canceldate = now() where id = '".$urow->id."' AND state != '취소' limit 1";
            $conn_cms3->query($cusql);

            // 취소처리값이 coupons테이블까지 전파되도록 쿼리 추가 - Jason 22.02.25
            $cucsql = "update spadb.ordermts_coupons set state='C',dt_cancel = now() where order_id = '".$urow->id."' AND state = 'N' limit 1";
            $conn_cms3->query($cucsql);

            header("HTTP/1.0 200");
            echo json_encode($result);

            // 결과 기록
            _log_update_result($result);

            exit;
        }else{

            $order[]= array("OrderNo" => null,
                             "CouponNo" => null);

            $result = array("Code" => "4003",
                             "Msg" => "취소할수 없거나 변경이 불가능한 주문.",
                             "Result" => $order);

            header("HTTP/1.0 400");
            echo json_encode($result);

            // 결과 기록
            _log_update_result($result);

            exit; 
        }

    }else{ 
        $order[]= array("OrderNo" => null,
                         "CouponNo" => null);

        $result = array("Code" => "4000",
                         "Msg" => "취소할수 없거나 변경이 불가능한 주문..",
                         "Result" => $order);

        header("HTTP/1.0 400");
        echo json_encode($result);
 
        // 결과 기록
        _log_update_result($result); 

        exit; 
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

// 휘닉스 사용조회
function getPhoenixTicketStatus($no) {
    $curl = curl_init();
    $url = "http://gateway.sparo.cc/phoenix/info/{$no}";

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_USERAGENT,"Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1" );
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 5);

    $data = curl_exec($curl);
    $info = curl_getinfo($curl);
    curl_close($curl);
    if($info['http_code'] == "200"){
        return $data;
    }else{
        return false;
    }
}

// 롯데워터 사용조회
function getusewp($no) {
	$curl = curl_init();
    $url = "http://cms.sparo.co.kr/api/?q=".$no;
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_USERAGENT,"Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1" );
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 5);

    $data = curl_exec($curl);
    $info = curl_getinfo($curl);
    curl_close($curl);
    if($info['http_code'] == "200"){
        return $data;
    }else{
        return false;
    }
}

// 딜리스트
function get_deallist($cpcode){
    global $conn_cms;
    $mdate = date("Y-m-d");

    $glist = array();
    $gsql = "select pcmsitem_id,nm, usedate from pcmsdb.items_ext where channel = '$cpcode' and useyn= 'Y' and usedate >= '$mdate' order by usedate desc";
    $gres = $conn_cms->query($gsql);
    //$grow = $gres->fetch_all();
    while($grow = $gres->fetch_object()){

        $res = get_iteminfo($grow->pcmsitem_id,$grow->usedate);

        $glistp[] = array(
            "FACNM" => $res['grnm'],
            "ITEMCODE" => $grow->pcmsitem_id,
            "ITEMNM" => $grow->nm,
            "EXPDATE"  => $grow->usedate,
            "DESC"  => null
        );
    }
    echo json_encode($glistp);
}

// 에버랜드 사용조회
function getuseev($no) {

    $curl = curl_init();
    $url = "http://extapi.sparo.cc/everland/sync/".$no;
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_USERAGENT,"Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1" );
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 5);

    $data = curl_exec($curl);
    $info = curl_getinfo($curl);
    curl_close($curl);

    $result = json_decode($data);

    switch($result->PIN_STATUS){
        case 'UC':
        case 'UR':
            $useflag = "1";
        break;
        case 'PC':
            $useflag = "2"; // 취소핀
        break;
        case 'PS':
        case 'CR':
            $useflag = "2";
        break;
        default:
            $useflag = "1"; // 확인할수 없으면 미사용으로 본다.
    }

    return $useflag;

}


function get_extcoupon($chcode, $sellcode,$n,$orderno,$usernm,$usehp) {

        $reqinfo = array(
            "CHCODE" => $chcode,
            "ORDERNO" => $orderno,
            "SELLCODE" => $sellcode,
            "UNIT" => $n,
            "USERNM" => $usernm,
            "USERHP" => $usehp
        );

        $post = json_encode($reqinfo);

if(get_ip() =="106.254.252.100"){
//    echo $post;
}
        $apiurl="http://extapi.sparo.cc/internal/apicouponno/".$orderno;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $apiurl);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_USERAGENT,"Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1" );
        curl_setopt($curl, CURLOPT_POSTFIELDS,$post);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);

        $data = json_decode(curl_exec($curl));
        $info = curl_getinfo($curl);
        curl_close($curl);
        if($info['http_code'] == "200"){
            return $data[0];
        }else{
            return false;
        }


}


function get_phoenixno($_req){

  $curl = curl_init();
  $json = json_encode($_req);
  curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://gateway.sparo.cc/phoenix/rese/',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS =>$json,
    CURLOPT_HTTPHEADER => array(
      'Content-Type: application/json',
      'Cookie: AWSELB=FD5D8FB31437D8ECEFAC51645EAA3E39BD3738B5834A2913B2D0D0E7ED026EFE53189529F7915FB68D858E99A4C7DEB3C5ECE7CCCFE307EF483F2343532BC6340A1D958BD5; AWSELBCORS=FD5D8FB31437D8ECEFAC51645EAA3E39BD3738B5834A2913B2D0D0E7ED026EFE53189529F7915FB68D858E99A4C7DEB3C5ECE7CCCFE307EF483F2343532BC6340A1D958BD5'
    ),
  ));

  $response = curl_exec($curl);

  curl_close($curl);
  return $response;

}

function get_extcoupon2($chcode, $sellcode,$n,$orderno,$usernm,$usehp) {

		global $conn_rds;
		global $conn_cms;
		global $conn_cms3;

		$ordsql = "select * from cmsdb.pcms_extcoupon_api where order_no = '$orderno' and sellcode='$sellcode' limit $n";
		$result = $conn_rds->query($ordsql);

		if($result->num_rows > 0){

		}else{
			$cpsql = "UPDATE
						cmsdb.pcms_extcoupon_api
					  SET
						order_no = '$orderno',
						cus_nm='$usernm',
						cus_hp='$userhp',
						syncfac_result='R',
						date_order = now()
					  WHERE
							syncfac_result = 'N' AND sellcode = '$sellcode'
					  LIMIT $n";

			$res = $conn_rds->query($cpsql);

			$ordsql = "select * from cmsdb.pcms_extcoupon_api where order_no = '$orderno' and sellcode='$sellcode' limit $n";
			$result = $conn_rds->query($ordsql);
		}

		$cpns = array();

		while($row = $result->fetch_object()){
			$cpns[] = $row->couponno;
			$cpflag= substr($row->couponno,0,2);

			// 발권 플래그
			switch($cpflag){
				case 'EL':
				case 'CB':
					$syncflag = "R";
				break;
				default:
					$syncflag = "O";
			}

		  // 7번
			$pksql = "update spadb.pcms_extcoupon set
					order_no = '$orderno',
					cus_nm='$usernm',
					cus_hp='$userhp',
					syncfac_result='$syncflag',
					date_order = now()
				  WHERE couponno = '".$row->couponno."' limit 1";
			$conn_cms3->query($pksql);

			// 2번
			$pksql2 = "update pcmsdb.cms_extcoupon set
					order_no = '$orderno',
					cus_nm='$usernm',
					cus_hp='$userhp',
					syncfac_result='$syncflag',
					date_order = now()
				  WHERE no_coupon = '".$row->couponno."' limit 1";
			$conn_cms->query($pksql2);
		}

		return $cpns[0];


}

  // 한화 하비스 쿠폰조회
  function _get_habisinfo($couponno){

    $ch = curl_init();
    $apireq = "https://gateway.sparo.cc/hanwha/info";

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode(array("couponno"=>$couponno)));
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_URL, $apireq);

    $info = curl_getinfo($ch);
    $data = curl_exec($ch);

    return $data;
  }

	function _get_kidzcoupon($post){
    $ch = curl_init();
    $apireq = "http://extapi.sparo.cc/internal/jejukidz/";

  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS,$post);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_URL, $apireq);

  $info = curl_getinfo($ch);
       $data = curl_exec($ch);

  return $data;
    }


	function _get_hpcoupon($post)
    {
        $ch = curl_init();
        $apireq = "http://extapi.sparo.cc/hanwha/orders";

		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$post);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_URL, $apireq);

		$info = curl_getinfo($ch);
        $data = curl_exec($ch);

		return $data;
	}


	function _get_habiscoupon($no)
    {
        $ch = curl_init();
        $apireq = "http://gateway.sparo.cc/extra/hanwha/index.php?coupon=".$no;

		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_URL, $apireq);

		$info = curl_getinfo($ch);
        $data = curl_exec($ch);

		return $data;
	}


  function _get_sticketcoupon($ctype, $ccode){

  while(1){
          $headflag = $ctype."500";
          $itemcode = $ccode;
          $number = rand(1904,999900);
          $chhid = rand(7,9);
          $placemcode=str_pad($number, 7, "0", STR_PAD_LEFT);

          $cno = $headflag.$itemcode.$placemcode.$chhid;
          break;
      }

      return $cno;
  }


function send_notice($title,$msg){

	$curl = curl_init();
	$msgarr = array("title"=>$title,
					"description"=>$msg);
	curl_setopt_array($curl, array(
	  CURLOPT_URL => "http://extapi.sparo.cc/internal/sysnotice/ACT",
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => "",
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 30,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "POST",
	  CURLOPT_POSTFIELDS => json_encode($msgarr),
	  CURLOPT_HTTPHEADER => array(
		"Accept: application/vnd.tosslab.jandi-v2+json",
		"Cache-Control: no-cache",
		"Connection: keep-alive",
		"Content-Type: application/json",
		"Host: extapi.sparo.cc",
		"accept-encoding: gzip, deflate",
		"cache-control: no-cache",
	  ),
	));

	$response = curl_exec($curl);
	$err = curl_error($curl);

	curl_close($curl);

}

function _real_sticketno($itemcode,$chcode,$chorderno){

    $curl = curl_init();
    $_json = json_encode(array("itemcode" => $itemcode,
                               "chcode" => $chcode,
                               "chorderno" => $chorderno));
    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://gateway.sparo.cc/everland/stkcoupon/",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => $_json ,
      CURLOPT_HTTPHEADER => array(
        "Content-Type: application/json"
      ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    return (string) json_decode($response)[0];
}

function get_inthp($hpn){
    $replace = preg_replace("/^(\+?82\s?)(0?)([0-9\-]+)/", "$2$3", $hpn);
    $replace = substr($replace, 0, 1) !== '0' ? '0' . $replace : $replace;

    return $replace;
}



function put_logs($_pid,$_logs){
	return;
  $curl = curl_init();
  $_json = json_encode($_logs);

  curl_setopt_array($curl, array(
    CURLOPT_URL => 'http://15.165.201.74:9200/cms/pmlogs/'.$_pid,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'PUT',
    CURLOPT_POSTFIELDS =>$_json,
    CURLOPT_HTTPHEADER => array(
      'Content-Type: application/json'
    ),
  ));

  $response = curl_exec($curl);

  curl_close($curl);
  return json_decode($response);
}

// 82로 시작하는 국가번호 삭제
function _phone($_tel)
{
    $tel = trim(str_replace("-" ,"" , $_tel));
    $tel = trim(str_replace("+" ,"" , $tel));
    if(strncmp($tel, "82", 2) === 0){
       $tel = substr($tel, 2);
    }else{
       $tel = $_tel; // 입력값 원복
    }
    return $tel;
}

// 결과 컬럼에 데이터 추가하기
function _log_update_result($msg, $force=true){
    global $conn_rds;
    global $tranid;
    // echo $tranid." ";

    // 20240110 tony 현자 일자 추가
    $msg['cur_dt'] = date("Y-m-d H:i:s");

    if ($force){
        $msg = json_encode($msg, JSON_UNESCAPED_UNICODE);
        //$logsql = "UPDATE cmsdb.extapi_log SET apiresult = concat(ifnull(apiresult, ''), '\n', '".addslashes($msg)."') WHERE tran_id = '$tranid'";
        $logsql = "UPDATE cmsdb.extapi_log SET apiresult = concat(ifnull(apiresult, ''), '".addslashes($msg)."', '\n') WHERE tran_id = '$tranid'";
        $conn_rds->query($logsql);
    }
    // echo $conn_rds->affected_rows;
}

// 롯데핀코드에서 판매대기시킬 핀번호를 가져온다.
// 주문이 완료되면 쿠폰번호의 sync_update_lotte_coupon() 함수를 호출하여 상태를 업데이트 해야 한다.
function set_lotte_pincode($ordrow){
    global $conn_cms;
    global $conn_cms2;
    global $conn_cms3;

    // ordermts.id
    $id = $ordrow->id;
    $orderno = $ordrow->orderno;
    $grmt_id = $ordrow->grmt_id;
    $itemcd = $ordrow->itemmt_id;
    $qty = $ordrow->man1 ;

    // 기존핀 말고 새로 들어온 핀이 나가야해서 나중에 생성된 Sellcode로 나가도록 변경 - Jason 2022.02.08
    //$cqry = "SELECT * FROM pcmsdb.cms_coupon where items_id = '$itemcd' order by id DESC limit 10";
    // Sellcode가 최근것만 조회하니 그이후의 핀을 사용하지 못하게 되어 다시 원복 - ila 2022.04.26
    //$cqry = "SELECT * FROM pcmsdb.cms_coupon where items_id = '$itemcd' order by rand() limit 1";
    // 20220819 tony 재고파악
    $cqry = "SELECT * FROM pcmsdb.cms_coupon where items_id = '$itemcd' order by id DESC limit 10";

    // https://placem.atlassian.net/browse/P2CCA-311 롯데 기간만료핀 설정 추가함
    $cqry = "SELECT * FROM pcmsdb.cms_coupon where items_id = '$itemcd' and use_edate >= '".date("Y-m-d H:i:s")."' order by id DESC limit 10";

//echo "\n[".__LINE__."]:";print_r($cqry);
    $cres = $conn_cms->query($cqry);

    // 재고 여부
    $bJego = false;
    while($crow = $cres->fetch_object()){
        // 재고 수량 파악
        $pinqry = "SELECT * FROM apidb.lotte_pincode where Sellcode='".$crow->ccode."' and SyncResult='0'";
        $pinres = $conn_cms2->query($pinqry);
        $pincnt = $pinres->num_rows; 

        if ($pincnt >= ($qty * $crow->cunit)){
            $bJego = true;
            break;
        }
    }

    // 재고부족
    if ($bJego == false){
        return "0";
    }

    $eventcd = $crow->sellno;
    $sellcode = $crow->ccode;
    $cunit = $crow->cunit;

    if($sellcode){
        // 이미 발권한건지 체크
        $lcnt = 0;
        $lqry = "SELECT * FROM apidb.lotte_pincode where OrderNo='$orderno' and OrderIdx='$id'";
        $lres = $conn_cms2->query($lqry);
        $lcnt = $lres->num_rows; 

        $ccnt = $qty * $cunit; // 발권단위

        if($lcnt > 0){
//            echo "\n I - $orderno ";
        }else{
 //           echo "\n U - $orderno ";

            // 판매완료(발권준비)
            $uqry = "update apidb.lotte_pincode set OrderNo='$orderno', OrderIdx='$id',SyncResult= 'R' where EventCd = '$eventcd' and SellCode= '$sellcode' and SyncResult='0' limit $ccnt  ";
            $uqry = $conn_cms2->query($uqry);
            $cntuqry = $conn_cms2->affected_rows;
        }

        $iqry = "SELECT * FROM apidb.lotte_pincode where OrderNo='$orderno' and OrderIdx='$id' limit $ccnt";
        $ires = $conn_cms2->query($iqry);

        $bars ="";
        $rtnbar ="";
        $cparr = array();

        while($irow = $ires->fetch_object()){
            $bars.= $irow->CouponNo.";";
            $rtnbar = $irow->CouponNo;  // 하나만 리턴할 겨우
            $cparr[] = $irow->CouponNo;

            // 추가쿠폰(패키지)
            // 20220819 tony 검색되는 것이 없는데... 왜 있나 모르겠다. cms_extcoupon에 있는듯한데...
            //              일단 원본에 있는 로직이니깐 내비둔다. 
            //              원본 소스 : 130@/home/openapi.placem.co.kr/lotte_script/prc_setCouponLW_20170401.php 
            $ecsql = "SELECT * FROM spadb.pcms_extcoupon where couponno = '".$irow->CouponNo."' limit 1";
            $ecres = $conn_cms3->query($ecsql);
            $ecrow = $ecres->fetch_object();

            $ecp = json_decode($ecrow->opt_coupon);
            if(count($ecp) > 0){
                $bars.= $ecp[0].";";
                $cparr[] = $ecp[0];
            }

        };

        $cpjson = json_encode($cparr);


        $ord1 ="update spadb.ordermts set barcode_no = '$bars',couponno = '".$cpjson."' where id = '$id' limit 1";
        $resord1 = $conn_cms3->query($ord1);
        $cntord1 = $conn_cms3->affected_rows; 

        if ($ccnt == 1){
            return $rtnbar;
        }else{ 
            return $cparr; 
        } 
    }else{
        return "0";
    }
}

/*
header("HTTP/1.0 401 Unauthorized");
header("HTTP/1.0 400 Bad Request");
header("HTTP/1.0 200 OK");
header("HTTP/1.0 500 Internal Server Error");

4000	필수 파라미터 누락 및 Validation 실패 시 각 상황에 따른 메시지를 전달 함	400 Bad Request
4001	필수 해더 검증에 실패하였을 경우	412 Precondition Failed
4002	RestKey 인증에 실패 하였을 경우	401 Unauthorized

9005	검색된 리소스(데이터)가 없을경우	404 Not Found

5000	내부 시스템에서 오류가 발생하였습니다.	500 Internal Server Error



S|UC|사용완료
S|UR|사용완료요청

S|PC|구매취소

S|PS|구매완료
S|CR|회수완료
*/
?>
