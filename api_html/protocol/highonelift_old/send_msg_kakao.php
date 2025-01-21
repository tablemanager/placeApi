<?php
/**
 * Created by PhpStorm.
 * User: Connor
 * Date: 2018-06-04
 * Time: 오후 1:48
 */

// ===================================================
// http://gateway.sparo.cc/protocol/highonelift_m/send_msg_kakao.php
// ===================================================
/*
[#{FACNM}] 모바일 이용권 정상적으로 예약신청이 완료되었습니다.

#{CUSNM} 고객님, 구매하신 모바일 이용권을 전달드립니다.

▶상품명 : #{ITEMNM}
▶쿠폰번호 : #{COUPONNO}
▶유효기간 : #{EXPDATE}

구매하신 내역은 아래 버튼을 눌러 확인해주시기 바랍니다.
*/

$userhp = "01082085996";                                    // 수신번호

//$userhp = "010-9090-1678";                                                  // 수신번호 (제이)
//$userhp = "010-7374-1491";                                                  // 수신번호 (카일)
//$userhp = "010-4550-4541";                                                  // 수신번호 (하이원 담당자)

$Subject = "high1_cj_01";                                        // 카카오 템플릿명
//$callBack = '16443913';
$msg = @"
[하이원] CJ 홈쇼핑 하이원 워터월드 입장권 구매가 완료 되었습니다.

테스트 고객님께서 구매하신 내역입니다.

▶상품명 : CJ TV쇼핑 하이원 워터월드 3매 패키지
▶매수 : 1매
▶객실 예약번호 : 3201855110005493
▶유효기간 : 2019년 4월 30일
▶유의사항 : 
- 유효기간은 2019년 4월 30일까지 입니다.
- 취소 및 환불은 구매 후 14일이내에만 가능합니다.(이후 취소 절대불가)
- 링크를 클릭하시면 바코드 티켓을 확인 할 수 있습니다.
- 워터월드 현장 매표소에서 바코드 제시 후 이용 가능하십니다. 
- 본 권은 입장시 우선 혜택은 없으며, 입장 인원 초과시 입장이 제한될 수 있습니다.
- 36개월 미만 무료 입장시 증빙서류(주민등록등본/의료보험증)를 지참하시기 바랍니다. 
- 이용 전 홈페이지를 통해 이용시간 및 휴장일을 확인바랍니다.";

$orderno = date("YmdHis")."_0614";
//$mmsFile = "https://gateway.sparo.cc/assets/qr.jpg";
//$pinType = "QR";
//$pinNo = "CB5000648000004466";

$extVal1 = array("name"=>"워터월드 입장 교환권 보기", "type"=>"WL", "url_mobile"=>"https://sparo.cc/2018072301", "url_pc"=>"https://sparo.cc/2018072301");
$extVal2 = array("name"=>"객실 예약 바로가기", "type"=>"WL", "url_mobile"=>"https://img.sparo.kr/sparo/2018/high1/cj/cj_high1_reservation.html", "url_pc"=>"https://img.sparo.kr/sparo/2018/high1/cj/cj_high1_reservation.html");
//$extVal3 = array("name"=>"운영시설 바로보기", "type"=>"WL", "url_mobile"=>"http://www.lotteworld.com/app/wtp_suspendInfo/view.asp?cmsCd=CM0296", "url_pc"=>"http://www.lotteworld.com/app/wtp_suspendInfo/view.asp?cmsCd=CM0296");
//$extVal4 = array("name"=>"가이드맵 바로보기", "type"=>"WL", "url_mobile"=>"http://img.sparo.kr/sparo/2017/lottewater/lottewater_guide.jpg", "url_pc"=>"http://img.sparo.kr/sparo/2017/lottewater/lottewater_guide.jpg");

//발신번호 프로필키
$kakao_profile = "high1";            // 하이원 key

$msgarr = array(
    "dstAddr"=>$userhp,
    "callBack"=>$callBack,
    "msgSubject"=>$Subject,
    "msgText"=>trim($msg),
    "mmsFile"=>$mmsFile,
    "orderNo"=>$orderno,
    "pinType"=>$pinType,
    "pinNo"=>$pinNo,
    "kakao_profile"=>$kakao_profile,
    "extVal1"=>json_encode(array($extVal1, $extVal2, $extVal3, $extVal4)),
    "extVal2"=>"",
    "extVal3"=>"",
    "extVal4"=>""
);

$jsonreq = json_encode($msgarr);

$data = send_url("http://gateway.sparo.cc/internal/messages/dev/index.php?val=kakao","POST", $jsonreq);
xmp($data);
exit;
// ===================================================

function send_url($url, $method, $data, &$http_status, &$header = null) {

    //Log::debug("Curl $url JsonData=" . $post_data);
    $ch=curl_init();

    //curl_setopt($ch, CURLOPT_HEADER, true);				// 헤더 출력 옵션..
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

    // 메세지의 따른..
    switch(strtoupper($method)) {
        case 'GET':
            curl_setopt($ch, CURLOPT_URL, $url);
            break;

        case 'POST':
            $info = parse_url($url);
            $url = $info['scheme'] . '://' . $info['host'] . $info['path'] . '?' . $info['query'];
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);

            $str = "";
            if (is_array($data)) {
                $req = array();
                foreach ($data as $k => $v) {
                    $req[] = $k . '=' . urlencode($v);
                }

                $str = @implode($req);
            } else {
                $str = $data;
            }

            curl_setopt($ch, CURLOPT_POSTFIELDS, $str);
            break;

        default:
            return false;
    }

    curl_setopt($ch, CURLOPT_TIMEOUT, 30);						// TimeOut 값
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);				// 결과를 받을것인가.. ( False로 하면 자동출력댐.. ㅠㅠ )
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    //curl_setopt($ch, CURLOPT_VERBOSE, true);

	$response = curl_exec($ch);
    $body = null;

	// error
    if (!$response) {
        $body = curl_error($ch);
        // HostNotFound, No route to Host, etc  Network related error
        $http_status = -1;
    } else {
       //parsing http status code
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $body = $response;
		/*
        if (!is_null($header)) {
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header = substr($response, 0, $header_size);
            $body = substr($response, $header_size);
        } else {
            $body = $response;
        }
		*/
    }

    curl_close($ch);

    return $body;
}

// 디버깅용
function xmp($text) {
    echo "<xmp>";
    print_r($text);
    echo "</xmp>";
}

?>