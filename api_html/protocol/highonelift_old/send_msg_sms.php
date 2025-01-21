<?php
/**
 * Created by PhpStorm.
 * User: Connor
 * Date: 2018-06-04
 * Time: 오후 5:57
 */

// ===================================================
// http://gateway.sparo.cc/protocol/highonelift_m/send_msg_sms.php
// ===================================================
/*

[한 필규] [오전 10:07] 0503-5319-8602
[한 필규] [오전 10:07] 0503-5319-8606
*/
//$userhp = "010-9090-1678";                                                  // 수신번호 (제이)
//$userhp = "010-7374-1491";                                                  // 수신번호 (카일)
//$userhp = "010-4550-4541";                                                  // 수신번호 (하이원 담당자)

$type = "MMS";
$userhp = "010-8208-5996";                                                  // 수신번호
$callBack = "";

$Subject = "모바일이용권";                                                        // 제목
$msg = @"구매 해 주셔서 감사합니다.
CJ TV쇼핑 하이원 워터월드 3매 패키지 구매 고객 안내

▣이름 : 테스트 (1678)
▣워터월드 입장 교환권 :  http://sparo.cc/2018072301
▣유의사항
- 유효기간은 2019년 4월 30일까지 입니다.
- 취소 및 환불은 구매 후 14일이내에만 가능합니다.(이후 취소 절대불가)
- 링크를 클릭하시면 바코드 티켓을 확인 할 수 있습니다.
- 워터월드 현장 매표소에서 바코드 제시 후 이용 가능하십니다. 
- 본 권은 입장시 우선 혜택은 없으며, 입장 인원 초과시 입장이 제한될 수 있습니다.
- 36개월 미만 무료 입장시 증빙서류(주민등록등본/의료보험증)를 지참하시기 바랍니다. 
- 이용 전 홈페이지를 통해 이용시간 및 휴장일을 확인바랍니다.

★혜택 1. 하이원 리조트 객실 무료 예약권
객실예약쿠폰번호 : 3201855110005493

아래의 링크를 클릭하시면 객실예약 및 유의사항 확인이 가능합니다.

http://img.sparo.kr/sparo/2018/high1/cj/cj_high1_reservation.html";         // 내용

$orderno = date("YmdHis")."_0614";                                  // 주문번호
/* 주의 $pinType이 "QR"일 경우, $mmsFile 파일은 전송하지 않는다. */
$mmsFile = NULL;
$pinType = "barcode";
$pinNo = "EL5000650184759020";

// /EL5000650110616920 - 일반건 / EL5000650184759020 - 추가이미지

$msgarr = array(
    "dstAddr"=>$userhp,
    "callBack"=>$callBack,
    "msgSubject"=>$Subject,
    "msgText"=>$msg,
    "mmsFile"=>$mmsFile,
    "orderNo"=>$orderno,
    "pinType"=>$pinType,
    "pinNo"=>$pinNo,
    "extVal1"=>"",
    "extVal2"=>"",
    "extVal3"=>"",
    "extVal4"=>""
);

$jsonreq = json_encode($msgarr);
$data = send_url("http://gateway.sparo.cc/internal/messages/dev/index.php?val=".$type,"POST", $jsonreq);
xmp($data);

$res = json_decode($data);
xmp($res);
exit;
// ===================================================

function send_url($url, $method, $data, &$http_status, &$header = null) {

    //Log::debug("Curl $url JsonData=" . $post_data);
    $ch=curl_init();

    //curl_setopt($ch, CURLOPT_HEADER, true);				// 헤더 출력 옵션..
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

    // 메세지의 따른..
    switch(strtoupper($method))
    {
        case 'GET':
            curl_setopt($ch, CURLOPT_URL, $url);
            break;

        case 'POST':
            $info = parse_url($url);

            $url = $info['scheme'] . '://' . $info['host'] . $info['path'];
            if (!empty($info['query'])) $url = $url . "?" . $info['query'];

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);

            $str = "";
            if (is_array($data)) {
                $req = array();
                foreach ($data as $k => $v) {
                    $req[] = $k . '=' . urlencode($v);
                }

                $str = @implode($req);
            }else {
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