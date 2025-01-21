<?php
/*
    제주 모바일 주문연동 인터페이스
*/

require_once('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
header("Content-type:application/json");

$para = $_GET['val']; // URI 파라미터

$apiheader = getallheaders();            // http 헤더
$jsonreq = trim(file_get_contents('php://input'));
$itemreq = explode("/",$para);
$data = json_decode($jsonreq,TRUE);

$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드

$apicmd = $itemreq[0];
$itemcode = explode("_",$itemreq[1]);

$url = "https://api.jejumobile.kr"; // 라이브
//$url = "https://devapi.jejumobile.kr"; // 개발

$req = array();



if(strlen($data['sCode']) > 2){

	$sellerCode = $data['sCode'];

}else{

	$sellerCode ="R0178";

}


$prdNo = $data['prdNo'];
$optNo = $data['optNo'];
$optType = $data['optType'];
$qty = $data['qty'];
$tid = $data['orderNo'];

$itemqry = "SELECT * FROM cmsdb.jejumobile_products WHERE prdNo='$prdNo' and optNo='$optNo'";
$itemrow = $conn_rds->query($itemqry)->fetch_object();

if(empty($itemrow)){
  header("HTTP/1.0 400 Bad Request");
  $res = array("Result"=>"4000","Msg"=>"필수 파라미터 오류 ep");
  echo json_encode($res);
  exit;
}

$op = json_decode($itemrow->optPrice);


$prdName = $itemrow->prdname;
$optName = $itemrow->optName;


$usernm = $data['userNm'];
$userhp = $data['userHp'];

$req['cntA'] = "0";                         // 성인 인원
$req['cntB'] = "0";                         // 청소년 인원
$req['cntC'] = "0";                         // 소인 인원
$req['priceRA'] = "0";                      // 성인 판매단가
$req['priceRB'] = "0";                      // 청소년 판매단가
$req['priceRC'] = "0";

switch($optType){
  case 'A':
    $optprice = $op->priceRA;
  break;
  case 'B':
    $optprice = $op->priceRB;
  break;
  case 'C':
    $optprice = $op->priceRC;
  break;
}

$totalPrice = $optprice * $qty;

setting_type($optType,$qty, $optprice,$req);

$req['sCode'] = $sellerCode;                                    // 업체코드*
$req['mobileNo'] = $userhp;                                // 발행 휴대폰번호*
$req['buyerName'] = $usernm;                             // 구매자이름*
$req['buyerEmail'] = "ctrip@hamac.co.kr";                // 구매자이메일* (고정)
$req['prdNo'] = $prdNo;                           // 제주모바일 상품번호*
$req['prdName'] = $prdName;                      // 제주모바일 상품명
$req['optNo'] = $optNo;                                         // 옵션번호*
$req['optName'] = $optName;                   // 옵션명
$req['totalPrice'] = $totalPrice;                    // 총 구매금액

$req['sendYn'] = "N";                                           // 문자 발송여부
$req['tid'] = $tid;     // 제휴사 주문번호
$req['svcId'] = "";                        // 제휴사 서비스 업체 아이디
$req['svcName'] = "";                      // 제휴사 서비스명
$req['svcTel'] = "";                       // 제휴사 업체 전화번호

$res = jejuOrder($req);

$Xres = simplexml_load_string($res, null, LIBXML_NOCDATA);

$resCd = (string)$Xres->resCd;
$resBarcode = (string)$Xres->barcode;

if($resCd == "0000"){
  header("HTTP/1.0 200");
  $res = array("Result"=>"0000","Msg"=>"성공","Barcode" =>$resBarcode );
  echo json_encode($res);
  exit;
}else{
  header("HTTP/1.0 400 Bad Request");
  $res = array("Result"=>"4000","Msg"=>"필수 파라미터 오류 ep");
  echo json_encode($res);
  exit;
}


function jejuOrder($req){
    $qrystr = http_build_query($req);
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://api.jejumobile.kr/send?".$qrystr,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => array(),
    ));
	$response = curl_exec($curl);
    return $response;

    curl_close($curl);

}

function setting_type($type, $qty,$price, &$data)
{
    global $req;

    $data['cntA'] = "0";                         // 성인 인원
    $data['cntB'] = "0";                         // 청소년 인원
    $data['cntC'] = "0";                         // 소인 인원

    $data['priceRA'] = "0";                      // 성인 판매단가
    $data['priceRB'] = "0";                      // 청소년 판매단가
    $data['priceRC'] = "0";                      // 소인 판매단가

    switch ($type) {

        // 성인
        case 'A' :
            $data['cntA'] = $qty;
            $data['priceRA'] = $price;
            break;

        // 청소년
        case 'B' :
            $data['cntB'] = $qty;
            $data['priceRB'] = $price;
            break;

        // 소인
        case 'C' :
            $data['cntC'] = $qty;
            $data['priceRC'] = $price;
            break;
    }
}

function _get_hpcoupon($post) // 한화신 하비스 연동
	{
			$ch = curl_init();
			$apireq = "http://extapi.sparo.cc/hanwha_aqua/index.php?val=order";

	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS,$post);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_URL, $apireq);


	$info = curl_getinfo($ch);
	//print_r($info);
	$data = curl_exec($ch);
	//print_r(json_decode($data));
/*
$hwworld = array("ORDERNO"=>$orderno,
				 "SELLCODE"=>$itemcd,
				 "UNIT"=>1,
				 "RCVER_NM"=>$ordrow->usernm,
				 "RCVER_TEL"=>$ordrow->dhp
		);
 $aqcoupon = json_decode(_get_hpcoupon(json_encode($hwworld)))->couponno;
*/

	return $data;
}

?>
