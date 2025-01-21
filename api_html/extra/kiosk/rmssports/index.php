<?php
/*
 *
 * RMS스포츠 인터페이스
 *
 * 작성자 : 이정진
 * 작성일 : 2020-08-01
 *
 *
 */

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
require_once ('/home/placedev/php_script/lib/placemlib.php');
$conn_rds->query("set names utf8");

$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

// 파라미터
$itemreq = explode("/",$para);
$jsonreq = trim(file_get_contents('php://input'));

$proc = $itemreq[0];
$couponno = $itemreq[1];

// ACL 확인
$accessip = array("106.254.252.100",
    "61.38.140.35",
    "118.130.130.34",
    "118.130.130.38",
    "118.130.130.55",
    "118.130.130.57",
    "211.232.5.2",
    "1.220.248.60"
);

if(!in_array(get_ip(),$accessip)){
  //header("HTTP/1.0 401 Unauthorized");
  //    $res = array("resultCode"=>"9996","resultMessage"=>"아이피 인증 오류 : ".get_ip());
  //  echo json_encode($res);
  //  exit;
}

header("Content-type:application/json");


$mdate = date("Y-m-d");

switch($proc){
    case 'coupon':

      switch($apimethod){
          case 'POST': // 쿠폰 생성

		  	// 주문번호로 쿠폰 코드 조회 후 생성

            $sellcode = get_sellcode(json_decode($jsonreq)->itemCd); // 상품 코드를 이용해서 쿠폰코드를 조회한다.

            $spno = json_decode(getsportcouponno($sellcode));

            $_resjson = json_encode(array(
                        "resultCode" => "200",
                        "couponNo" =>  $spno
                        ));
          break;

          case 'PATCH': // 쿠폰 폐기
            $sellcode = get_sellcode(json_decode($jsonreq)->itemCd); // 상품 코드를 이용해서 쿠폰코드를 조회한다.

            $cpno = json_decode($jsonreq)->ticketCode;
            $_res = cancelsportcouponno($cpno);

              $_resjson = json_encode(array(
                          "resultMsg"=>"폐기 되었습니다."
              ));

          break;
          default:

          break;
        }

    break;
    case 'orders':
        switch($apimethod){
            case 'GET': // 쿠폰 조회
                $_res = getcouponinfo($couponno);

                $_resjson = json_encode(array(
                  "resultCode"=> "200",
                  "ticketCode"=> $couponno,
                  "grade_code"=> $_res->classCd,
                  "ticketStatus"=> $_res->state,
                  "useDt"=> $_res->usedate,
                  "cancelDt"=> $_res->canceldate
                ));
            break;
            case 'POST': // 입장처리
                  $_cinfo = getcouponinfo($couponno);
                  if($_cinfo->state == 'N'){
                    $_res = usecoupon($couponno);

                    $_resjson = json_encode(array(
                      "resultCode"=> "200",
                      "resultMSg" => "입장 처리 되었습니다.",
                      "results" => array(
                      "itemCd" =>"",
                      "sellerName"=>"",
                      "ticketCode"=> $couponno,
                      "grade_code"=> $_res['grade_code']
                      )
                    ));
                  }else{
                    $_resjson = json_encode(array(
                      "resultCode"=> "403",
                      "resultMSg" => "쿠폰 상태를 변경 할수 없습니다.",
                      "results" => null
                    ));
                  }

            break;
            case 'PATCH': // 입장처리 취소
                  $_cinfo = getcouponinfo($couponno);
                  if($_cinfo->state == 'Y'){
                    $_res = unusecoupon($couponno);
                    $_resjson = json_encode(array(
                      "resultCode"=> "200",
                      "resultMSg" => "입장 취소 처리 되었습니다.",
                      "results" => array(
                                    "itemCd" =>"",
                                    "sellerName"=>"",
                                    "ticketCode"=> $couponno,
                                    "grade_code"=> $_res['grade_code']
                      )
                    ));
                }else{
                  $_resjson = json_encode(array(
                    "resultCode"=> "403",
                    "resultMSg" => "쿠폰 상태를 변경 할수 없습니다.",
                    "results" => null
                  ));
                }
            break;
            default:
            break;
          }
    break;
    case 'report':
    // 사용내역 조회(GET)
        $_resjson = json_encode(get_report($couponno));
    break;
    case 'eticket':
    // 이티켓 발송(POST)
      $_reqsend = json_decode($jsonreq);

      $_tplcode = $_reqsend->tplCode;
      $_orderno = $_reqsend->orderno;
      $_userhp = $_reqsend->distHp;
      $_cparr = $_reqsend->coupons;

      $cstr = get_pmcoupon(20);
      $curl = "http://q.qpass.cc/".$cstr;

      if(empty($_orderno))  $_orderno = "rms_kiosk";

      // 템플릿 코드 구하기 추가 필요
      $tplcd ="1";

       $isql = "insert cmsdb.callback_msg set setting_seq = '$tplcd',
                                            curlstr = '$cstr',
                                            item_id = '$_tplcode',
                                            typecode = 'Q',
                                            couponno = '".json_encode($_cparr,JSON_UNESCAPED_UNICODE)."',
                                            userhp = '$_userhp',
                                            regdate = now()";
     $_msgres = $conn_rds->query($isql);

     if($_msgres){

     $msgstr = "모바일 입장권 URL \n$curl ";

     send_bgfmsg($_userhp,$_orderno,$msgstr);
     $_resjson = json_encode(array("resultCode"=>"200",
                                    "resultMessage"=>"발송 성공",
                                    "results" => array("eticketUrl"=>$curl)
    ));
    // 발송

    }

    break;
    default:
        header("HTTP/1.0 400");
        $res = array("resultCode"=>"9998","resultMessage"=>"파라미터 오류");
        echo json_encode($res);
        exit;

}

echo $_resjson;



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

function get_sellcode($itemid){
  global $conn_cms;

  // 아이템 코드를 조회해서 쿠폰 코드를 구함
  $tsql = "select * from pcmsdb.cms_coupon where items_id = '$itemid' order by id desc limit 1";
  $trow = $conn_cms->query($tsql)->fetch_object();

  return $trow->ccode;
}

function get_report($dt){
    global $conn_rds;
    $_report = array();
    if(strlen($dt) < 4) $dt = date("Y-m-d");

    $sdate = $dt." 00:00:00";
    $edate =  $dt." 23:59:59";
    $mvsql = "select * from cmsdb.rmssports_extcoupon where state = 'Y' and usedate between '$sdate' and '$edate'";
    $mvres = $conn_rds->query($mvsql);
    while($mvrow = $mvres->fetch_object()) {
      $_report[] = array(
        "itemCd"=> $mvrow->sellcode,
        "sellerName" => $mvrow->ch_nm,
        "ticketCode"=> $mvrow->couponno,
        "grade_code" => $mvrow->classCd,
        "useDt" => $mvrow->usedate
      );
    }

    $_res = array(
      "resultCode" => "성공",
      "results" => $_report
    );

    return $_res;

}

function usecoupon($no){
    global $conn_rds;
    $_row = getcouponinfo($no);

    $usql = "update cmsdb.rmssports_extcoupon set state = 'Y' , usedate = now() where state = 'N' and couponno = '$no' limit 1";
    $conn_rds->query($usql);

    $_res = array(
                  "itemCd"=>$_row->sellcode,
				          "grade_code"=>$_row->classCd,
                  "sellerName"=>$_row->ch_nm);

    return $_res;
}

function unusecoupon($no){
    global $conn_rds;
    $_row = getcouponinfo($no);

    $usql = "update cmsdb.rmssports_extcoupon set state = 'N' , usedate = null where state = 'Y' and couponno = '$no' limit 1";
    $conn_rds->query($usql);

    $_res = array(
                  "itemCd"=>$_row->sellcode,
				          "grade_code"=>$_row->classCd,
                  "sellerName"=>$_row->ch_nm);
    return $_res;
}

function usecouponno($no){
    // 쿠폰 사용처리
    $curl = curl_init();
    $url = "http://172.31.30.15:3040/use/".$no;
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

// 쿠폰 코드 발급
function getsportcouponno($sellcode) {

  global $conn_rds;
  global $conn_cms;



  $csql="SELECT c.*, a.cconfig FROM pcmsdb.cms_coupon c, pcmsdb.cms_admin_assets a  WHERE c.ccode = a.ccode and c.ccode = '$sellcode'";
  $arow = $conn_cms->query($csql)->fetch_object();

  $iinfo = json_decode($arow->cconfig);
  $classCd = str_replace(array(" ","/n","/r"), "", $iinfo->classCD);

  $placemcode = get_pmcoupon(7);
  $couponno = str_replace(array(" ","\t","\n","\r"), "",$classCd.$placemcode);

  $expdate = substr($arow->use_edate,0,10);
  $sdate =  date("Y-m-d")." 00:00:00";
  $edate = $expdate." 23:59:59";

  $isql = "insert cmsdb.rmssports_extcoupon set  sellcode = '$sellcode',
                                               couponno = '$couponno',
                     classCd = '$classCd',

                                               tks_sdate = '$sdate',
                                               tks_edate = '$edate' ";

  $conn_rds->query($isql);


  $_row = $conn_rds->query($isql);
  $_rescp = array(
    "grade_code"=> $classCd,
    "ticketCode"=> $couponno
  );

  return json_encode($_rescp);

}

// 쿠폰 취소
function getcouponinfo($no) {
  global $conn_rds;
  $_sql = "SELECT
                  *
            FROM
                cmsdb.rmssports_extcoupon
            WHERE
                couponno = '$no'
            LIMIT 1
           ";

  return $_row = $conn_rds->query($_sql)->fetch_object();


}

// 쿠폰 취소
function cancelsportcouponno($no) {

  global $conn_rds;

  if(strlen($no) < 5) return;

    $isql = "UPDATE
              cmsdb.rmssports_extcoupon
           SET
              state = 'C',
              canceldate = now()
           WHERE
              couponno = '$no' AND
              state = 'N'
           LIMIT 1
           ";

  $_row = $conn_rds->query($isql);

  return $_row;

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

function get_pmcoupon($length=16){



	$characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;

}
?>
