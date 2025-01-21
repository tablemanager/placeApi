<?php
/**
 * 생성자: JAMES
 * 마지막 수정 : JAMES
 * 생성일: 2019-07-22
 * 수정일: 2019-07-05
 * 사용 유무: release (test, release,inactive,dev)
 * 파일 용도: 위즈돔(이버스) API 연동
 * 설명 : https://docs.google.com/document/d/17wfmtUD1OS7pe4z-b-_Uiqo7v-F94K3YkWkQZW2YdCg/edit
 */

include '/home/sparo.cc/lib/placem_helper.php';
require_once('/home/placedev/cmsapps/common/conf/dbconn.conf.php');

header("Content-type:application/json");

// ACL 확인
$accessip = array("115.68.42.2",
	"115.68.42.8",
	"115.68.42.130",
	"52.78.174.3",
	"106.254.252.100",
	"115.68.182.165",
	"13.124.139.14",
	"218.39.39.190",
	"114.108.179.112",
	"13.209.232.254",
	"13.124.215.30",
	"54.180.190.102",
	"52.78.51.243"
);
__accessip($accessip);

$para = $_GET['val']; // URI 파라미터

$apiheader = getallheaders();            // http 헤더

$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드

$_para_len = strlen($para);
//echo $_para_len;
if ($_para_len < 12){
    $json_result['code'] = "0003";
    $json_result['msg'] = "Unknown error";
} else {
//    $_use_search = "SELECT * FROM ordermts  WHERE   grmt_id = '3780' and  barcode_no LIKE '%".$para."%'";
//    $_use_search = "SELECT * FROM ordermts  WHERE   grmt_id in ('3780' ,'3752' , '3771') and  barcode_no = '$para'";
    // $_use_search = "SELECT *, AES_DECRYPT(UNHEX(hp), 'Wow1daY') AS dec_hp FROM ordermts WHERE ch_id in ('128', '150', '3652', '3810', '3811', '3813', '3294', '3809', '3815', '3817', '3821', '3331', '2904', '3500', '2094', '2984', '142') and (barcode_no = '{$para}' or barcode_no = '{$para};')";
    $_use_search = "SELECT *, AES_DECRYPT(UNHEX(hp), 'Wow1daY') AS dec_hp FROM ordermts WHERE (barcode_no = '{$para}' or barcode_no = '{$para};')";
//echo $_use_search;

    // 20230119 tony https://placem.atlassian.net/browse/P2CCA-250
    // [CMS] 스테이노트 접속 바코드 관련 확인요청건
    // 다중 구매도 가능하도록 쿼리 변경
    $_use_search = "SELECT *, AES_DECRYPT(UNHEX(hp), 'Wow1daY') AS dec_hp FROM ordermts WHERE (barcode_no = '{$para}' or barcode_no = '{$para};')";
    $itemUseResult = $conn_cms3->query($_use_search);

    // 속도가 빠른 검색 먼저 하고(1초 이내) 
    $cnt = $conn_cms3->affected_rows;
    // 검색이 안되면 2개 이상 구매해서 ;로 구분된 2개 쿠폰번호가 있는 경우인지 검색(20230119 현재 약 10초 소요)
    // 없는 번호를 넣거나 2개 이상 구매한 건의 쿠폰번호를 넣으면 속도가 겁나 느려진다.
    if ($cnt == 0){
        // 시간이 오래 걸리는 쿼리였는데 시설로 제한해서 속도 개선
        // 시설이 추가되면 여기에 넣어줘야 한다.

        // 20230617 tony https://placem.atlassian.net/browse/P2CCA-360  [한국민속촌] 티켓오류 확인요청건
        // 4315 한국민속촌(지류) 추가
        $_use_search = "SELECT *, AES_DECRYPT(UNHEX(hp), 'Wow1daY') AS dec_hp FROM ordermts WHERE barcode_no like '%{$para}%' 
                        AND jpmt_id in (
                                        '4315', -- 한국민속촌(지류)(4315)
                                        '4297', -- 알펜시아 숙박pkg
                                        '1387', -- 휘닉스 평창(1387)
                                        '4286', -- 휘닉스 제주 섭지코지(4286)
                                        '70', -- 이랜드 크루즈(70) 
                                        '4106' -- 한화리조트
                                        )";
        $itemUseResult = $conn_cms3->query($_use_search);
        $cnt = $conn_cms3->affected_rows;
    }

    $jj= 0;
    while($row = $itemUseResult->fetch_object()){

//        if($para == 'PF8HP5ES26FD')
        { 
            // 주문수량이 2개 이상인 경우
            //if ($row->man1 >= 2)
            // 20230210 tony 1개일때도 검사한다. 짧게 입력한게 like 절로 패스되는경우 회피
            {
                $bFind = false;

                $arBar = explode (';', $row->barcode_no);
                // 수량이 같아야 한다.
                if(count($arBar) == $row->man1){
                    foreach($arBar as $bar){
                        // 대소문자 구분없이 비교
                        if(strcasecmp($para, $bar) == 0){
                            $bFind = true;
                            break;
                        }
                    }
                }

                if($bFind == false){
                    continue;
                }
            }
            // 1개일 경우에는 조회되는 시험에서 인증된 것임
        }

        if ($row->state == "예약완료" || $row->state == "완료"){
            $jj++;
            $json_result['code'] = "0000";
            $json_result['usernm'] = $row->usernm;
            $json_result['jpmt_id'] = $row->jpmt_id;
            $json_result['jpnm'] = $row->jpnm;
            $json_result['itemmt_id'] = $row->itemmt_id;
            $json_result['itemnm'] = $row->itemnm;
            $json_result['barcode'] = $para;
            $json_result['ch_id'] = $row->ch_id;
            $json_result['price']['commission'] = $row->ch_rate;
            $json_result['price']['sale_price'] = $row->amt;
            $json_result['price']['net_channel'] = $row->gongdan;
            $json_result['price']['net_facilities'] = $row->saipdan;
            $json_result['msg'] = "success";

            $_hpn = str_replace('-', '', $row->dec_hp);
            if (in_array($_hpn, ['01029870801', '01076149671', '01035861514'])) {
                $json_result['hpn'] = $_hpn;
            } else {
                $json_result['hpn'] = '';
            }
        } else  if ($row->state == "취소"){
            $jj++;
            $json_result['code'] = "0001";
            $json_result['msg'] = "cancel";
        } else  {
            $jj++;
            $json_result['code'] = "0003";
            $json_result['msg'] = "Unknown error";
        }
    }

    if ($jj == 0){
        $json_result['code'] = "0002";
        $json_result['msg'] = "No reservation";
    }
}



$res = json_encode($json_result);

echo $res;
