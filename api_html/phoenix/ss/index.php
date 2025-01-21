<?php
/*
 *
 * 휘닉스파크 연동 인터페이스
 * 소셜3사, 네이버 : 고객이 n장 주문하면 n번 이 API를 호출한다.
 *
 * 작성자 : 미카엘
 * 작성일 : 2018-12-13
 *
 * 조회(GET)			: https://gateway.sparo.cc/everland/sync/{BARCODE}
 * 발권(POST)		: https://gateway.sparo.cc/everland/sync/{BARCODE}
 * 발권취소(PATCH)	: https://gateway.sparo.cc/everland/sync/{BARCODE}
 */

//http://gateway.sparo.cc/phoenix/cancel/18100409982
require_once ('/home/sparo.cc/phoenix_script/lib/phoenixApi.php');
require_once ('/home/sparo.cc/phoenix_script/lib/ConnSparo2.php');
header("Content-type:application/json");

/*
function get_ph_sellClientCd_from_chID($ch_id) {
    switch ($ch_id) {
        case '129': $return = '10000192'; break;  // 0G마켓
        case '141': $return = '10000193'; break;  // 0옥션
        case '2984': $return = '10000214'; break;  // 0네이버
        //case '3384': $return = '10001703'; break;  // 0여기어때 
        case '3384': $return = '10000224'; break;  // 0여기어때(2022908 휘닉스코드 변경)
        //case '128': $return = '10000191'; break;  // 011번가
        case '128': $return = '10000438'; break;  // 011번가(2022908 휘닉스코드 변경)
        case '142': $return = '10000194'; break;  // 0위메프
        case '154': $return = '10000196'; break;  // 0티몬
        //case '150': $return = '10000195'; break;  // 0쿠팡
        case '150': $return = '10001995'; break;  // 0쿠팡(2022908 휘닉스코드 변경)
        //case '3809': $return = '10001832'; break;  // 0카카오톡스토어(카카오쇼핑)
        case '3809': $return = '10002107'; break;  // 0카카오톡스토어(카카오쇼핑)
                                                   // (2022908 휘닉스코드 변경)

        case '2548': $return = '10001994'; break;  // CJ(CJ온스타일 20220908 추가)
        case '3698': $return = '10000204'; break;  // 0SSG
        //case '168': $return = '10000413'; break;  // 0GS몰
        case '168': $return = '10001997'; break;  // 0GS몰(GS SHOP)(2022908 휘닉스코드 변경)
        case '3629': $return = '10001707'; break;  // 0KLOOK
        case '2901': $return = '10001704'; break;  // 0KKDAY
        case '2918': $return = '10001623'; break;  // 0와그
        case '3116': $return = '10001613'; break;  // 0마이리얼트립
        case '3500': $return = '10001010'; break;  // 웅진놀이의발견(웅진북클럽(놀이의발견))

        case '3818': $return = '10002189'; break;  // 네이버 스마트스토어 - 20220908 추가
        case '3885': $return = '10002190'; break;  // 마켓컬리 - 20220908 추가
        case '3810': $return = '10000430'; break;  // SKT 딜 - 20220908 추가
        case '3331': $return = '10000409'; break;  // 0야놀자
        case '3694': $return = '10001621'; break;  // 0트립닷컴

        default: $return = '10000222';  // 플레이스엠
    }

    return $return;
}
*/

// ACL 확인
$accessip = array("115.68.42.2",
    "115.68.42.8",
    "115.68.42.130",
    "52.78.174.3",
    "106.254.252.100",
    "115.68.182.165",
    "13.124.139.14",
    "13.209.232.254"
);

if(!in_array(get_ip(),$accessip)){
//    header("HTTP/1.0 401 Unauthorized");
//    $res = array("Result"=>"4100","Msg"=>"아이피 인증 오류 : ".get_ip());
//    echo json_encode($res);
//    exit;
}

$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

// 파라미터
$itemreq = explode("/",$para);
$jsonreq = trim(file_get_contents('php://input'));
$data = json_decode($jsonreq,TRUE);
$rprsSellNo = $itemreq[0];


if ($data['ch_orderno'] == '16485844' && 1 == 2) {
    $phoenixApi = new phoenixApi('y');
} else {
    $phoenixApi = new phoenixApi();
}

$fp = @fopen('./test.log', 'a');

// 최근 2시간 이내 받은 키가 있으면 재사용한다.
//$get_access = $phoenixApi->get_access_token();

$connSparo2 = new ConnSparo2();

$pkgcoupon_cnt =  $connSparo2->count_phoenix_pkgcoupon_ch_orderno($data['ch_orderno']);

if($pkgcoupon_cnt > 0){
    $res = json_encode(array(
            "RESULT"=>false,
            "MSG"=>"중복 데이터")
    );
  //  echo $res;
//    exit;
}

// if ($data['ch_orderno'] == '16485844' && 1 == 2) {
//     $get_access = $phoenixApi->get_access_token();
//     $get_token = json_decode($get_access);
// } else {
    $_tokn = $connSparo2->get_authtoken();

    if(empty($_tokn)){
        $get_access = $phoenixApi->get_access_token();
        // 토큰정보 저장
        $connSparo2->logAuthToken($get_access);
        $get_token = json_decode($get_access);
    }else{
        // 저장된 토큰 사용
        $get_token = json_decode($_tokn['tokens']);
    }
// }


if($get_token->access_token){

    foreach ($connSparo2->get_phoenix_pkglist($data['itemid']) as $row){

        $sellDate = date('Ymd');
        $clientList = json_decode($row['clientList']);      //거래처리스트
        $stdList = json_decode($row['stdList']);            //기준정보
        $stdGoodtList_ARR = json_decode($row['stdGoodtList']);  //기준상품리스트
        $chngGoodtList = json_decode($row['chngGoodtList']);

        //$get_client_cd = get_ph_sellClientCd_from_chID($data['ch_id']);
        // 20221031 toney https://placem.atlassian.net/browse/PM1912COBBF-50
        $get_client_cd = $phoenixApi->getSellClientCdbyChId($data['ch_id']);
        $_sellClientCd = '';
        $_agncyClientCd = '';
        $_arSttlClientCd = '';
        foreach ($clientList as $item) {
            if ($item->sellClientCd == $get_client_cd) {
                $_sellClientCd = $item->sellClientCd;
                $_agncyClientCd = $item->agncyClientCd;
                $_arSttlClientCd = $item->arSttlClientCd;
                break;
            }
        }
        if (!$_sellClientCd || !$_agncyClientCd || !$_arSttlClientCd) {
            $_sellClientCd = $clientList[0]->sellClientCd;
            $_agncyClientCd = $clientList[0]->agncyClientCd;
            $_arSttlClientCd = $clientList[0]->arSttlClientCd;
        }

        if($row['stdGoodselect'] == null){

            $res = json_encode(array(
                    "RESULT"=>false,
                    "MSG"=>"패키지없음")
            );

            echo $res;
            exit;

        }else{
            $stdGoodselect_ARR = json_decode($row['stdGoodselect']);

            $sellGoodtList = array();   //패키지 상품 리스트

            foreach ($stdGoodselect_ARR as $stdGoodselect){

                $sellGoodtList[] = array(
                    'goodDivCd'=>'O', //	상품구분코드 10	String		Y O:기준상품, C:변경상품
                    'sellMenuOutletCd'=>$stdGoodtList_ARR[$stdGoodselect]->outletCd, //	판매메뉴영업장코드 10	String		Y
                    'sellMenuTypeCd'=>$stdGoodtList_ARR[$stdGoodselect]->menuTypeCd, //	판매메뉴유형코드 10	String		Y
                    'sellMenuCd'=>$stdGoodtList_ARR[$stdGoodselect]->menuCd, //	판매메뉴코드 10	String		Y
                    'useQty'=>$stdGoodtList_ARR[$stdGoodselect]->useQty, //	사용수량 18	Number		Y
                    'goodsSellAmt'=>$stdList[0]->sellAmt, //	메뉴판매금액 18	Number		Y
                    'indvPrtYn'=>$stdGoodtList_ARR[$stdGoodselect]->indvPrtYn, //	분활출력여부 1	String		Y
                    'addAmt'=>0, //	추가금액 18	Number		Y
                    'orgMenuOutletCd'=>$stdGoodtList_ARR[$stdGoodselect]->outletCd, //	원메뉴영업장코드 10	String		Y
                    'orgMenuCd'=>$stdGoodtList_ARR[$stdGoodselect]->menuCd, //	원메뉴코드 10	String		Y
                    'orgMenuTypeCd'=>$stdGoodtList_ARR[$stdGoodselect]->menuTypeCd //	원메뉴유형코드 10	String		Y
                );
            }


            /*
   {
      "sellClientCd":"10000192", // G마켓
      "sellClientCd":"10000193", // 옥션
      "sellClientCd":"10000194", // 위메프
      "sellClientCd":"10000195", // 쿠팡
      "sellClientCd":"10000196" // 티몬
      "sellClientCd":"10000204", // 신세계몰(SSG)
      "sellClientCd":"10000224", // 여기어때
      "sellClientCd":"10000404", // AK몰
      "sellClientCd":"10000409", // 야놀자
      "sellClientCd":"10000411", // CJ오쇼핑
      "sellClientCd":"10000414", // GSShop
      "sellClientCd":"10000438", // 11번가(종합몰)
             */

//            $_sellClientCd = $clientList[0]->sellClientCd;
            //'sellClientCd'=>$clientList[0]->sellClientCd, <-- 여기에 $_sellClientCd 이 값으로

            // switch ($data['ch_id'])
            // {
            //     case "150":
            //         $_sellClientCd = "10000195";
            //     break;

            //     default :

            //         $_sellClientCd = $clientList[0]->sellClientCd;

            // }


             $chClientCd = array(
                    //  "sellClientCd"=>"10000411", // CJ오쇼핑
                     "168"=>"10000414", // GSShop
                     "2613"=>"10000204", // 신세계몰(SSG)
                     "2613"=>"10000404", // AK몰
                     "129"=>"10000192", // G마켓
                     "141"=>"10000193", // 옥션
                     "142"=>"10000194", // 위메프
                     "150"=>"10000195", // 쿠팡
                     "154"=>"10000196", // 티몬
                     "3384"=>"10000224", // 여기어때
                     "3331"=>"10000409", // 야놀자
                     "128"=>"10000438", // 11번가(종합몰)
                     "2984"=>"10000214" //네이버
             );
             // 휘팍 채널코드
             $chnClientCd = $chClientCd[$data['ch_id']];
            if(empty($chnClientCd)) $chnClientCd ="10000192";
             if(empty($chnClientCd)) $chnClientCd =$clientList[0]->sellClientCd;

            $fields = array(
                //'sellpSellNo'=>$data['ch_orderno'],      //판매처판매번호	sellpSellNo	15	String		Y
                'sellpSellNo'=>$data['oid'],      //판매처판매번호	sellpSellNo	15	String		Y
                'sellDate'=>$sellDate,         //판매일자	sellDate	8	String		Y
                'pkgCd'=>$row['pkgCd'],            //상품코드	pkgCd	10	String		Y
                'bsuCd'=>$row['bsuCd'],            //사업장코드	bsuCd	10	String		Y
                'sellClientCd'=>$_sellClientCd,     //판매거래처코드	sellClientCd	10	String		Y
				// 'sellClientCd'=>$chnClientCd,     //판매거래처코드	sellClientCd	10	String		Y
                'agncyClientCd'=>$_agncyClientCd,    //대행사거래처코드	agncyClientCd	10	String		Y
                'arSttlClientCd'=>$_arSttlClientCd,   //후불정산거래처코드	arSttlClientCd	10	String		Y
                'asgnDate'=>'',         //지정일자(사용일자)	asgnDate	8	String		Y
                'midwkWkndDivCd'=>$stdList[$row['stdselect']]->midwkWkndDivCd,  //주중주말코드	midwkWkndDivCd	10	String		Y
                'stdGcnt'=>$stdList[$row['stdselect']]->stdGcnt,           //기준인원수	stdGcnt	18	Number		Y
                'sellAmt'=>$stdList[$row['stdselect']]->sellAmt,          //판매금액	sellAmt	18	Number		Y
                'addpAmt'=>0,          //선수금액	addpAmt	18	Number		Y
                'prchrNm'=>$data['usernm'],		    //구매자명	prchrNm	100	String
                'prchrMpNo'=>$data['userhp'],		//구매자휴대전화번호	prchrMpNo	20	String
                'actlUserNm'=>$data['usernm'],		//실사용자명	actlUserNm	100	String
                'actlUserMpNo'=>$data['userhp'],		//실사용자휴대전화번호	actlUserMpNo	20	String
                'custNo'=>'',           //고객번호	custNo	11	String		Y
                'custTypeCd'=>'',       //고객유형	custTypeCd		String		Y
                'totSellQty'=>1,       //총판매수량	totSellQty		Number		Y                   //$row['man1'] 인원은 무조건 1개
                'sellClientSellNo'=>$data['oid'],      //판매거래처판매번호  sellClientSellNo    40  String      Y   대매사주문번호
                // 20220913 tony 시설에서(헤니 통해) -숫자 제거 요청, 주문번호 넣던 것을 oid(주문번호 id)로 변경
                //                  시설과 협의해서 오픈마켓에서도 2개 이상 주문 안되게 제한하는 걸로 협의되었다함.
                'sellGoodtList'=>$sellGoodtList //판매 상품 리스트	sellGoodtList		GRID
            );

            $insertPkgData = $fields;
            $insertPkgData['orderid'] = "";
            $insertPkgData['pkglist_id'] = $row['id'];
            $insertPkgData['orderno'] = "";
            $insertPkgData['ch_id'] = $data['ch_id'];
            $insertPkgData['ch_orderno'] = $data['ch_orderno'];

            $insertPkgData['seq'] = "1";

//			$connSparo2->insert_phoenix_pkgcoupon($insertPkgData);
            $connSparo2->insert_phoenix_pkgcoupon_ch_order($insertPkgData);

            $fields_tmp = json_encode($fields, JSON_UNESCAPED_UNICODE);
            $fields = json_encode($fields);

            $str = "\n-------------------------------------------------\n";
            $str .= $data['ch_orderno'] . "\n";
            $str .= "request time: " . date("YmdHis") . "\n";
            $str .= "-------------------------------------------------\n";
            $str .= $fields_tmp;
            @fwrite($fp, $str);

            $order_responce = $phoenixApi->IF_SM_202_set_order($get_token->access_token, $fields);
            //$order_responce = $phoenixApi->IF_SM_202_set_order_season($get_token->access_token, $fields);

            $order_responce_json = json_encode($order_responce, JSON_UNESCAPED_UNICODE);
            $str = "\n-------------------------------------------------\n";
            $str .= $data['ch_orderno'] . "\n";
            $str .= "response time: " . date("YmdHis") . "\n";
            $str .= "-------------------------------------------------\n";
            $str .= $order_responce_json;
            @fwrite($fp, $str);

            $order_responce['ch_orderno'] = $data['ch_orderno'];

            $UpdateBarcode .= "{$order_responce['rprsBarCd']};";

//				if($connSparo2->update_phoenix_pkgcoupon_202_Result($pkgcoupon_id, $order_responce)){
            if($connSparo2->update_phoenix_pkgcoupon_202_Result_ch_orderno($data['ch_orderno'], $order_responce)){

                $res = json_encode(array(
                        "RESULT"=>true,
                        "rprsSellNo"=>$order_responce['rprsSellNo'],
                        "rprsBarCd"=>$order_responce['rprsBarCd'],
                        "MSG"=>"성공")
                );

                echo $res;
                exit;

            } else {

                $res = json_encode(array(
                        "RESULT"=>false,
                        "rprsSellNo"=>$order_responce['rprsSellNo'],
                        "rprsBarCd"=>$order_responce['rprsBarCd'],
                        "MSG"=>"update fail")
                );

                echo $res;
                exit;
            }

        }
    }

    fclose($fp);

}else{

    $res = json_encode(array(
            "RESULT"=>false,
            "MSG"=>"access fail")
    );
    echo $res;
    exit;

}

// 클라이언트 아아피
function get_ip(){
    $res = explode(",",$_SERVER["HTTP_X_FORWARDED_FOR"]);
    return trim($res[0]);
}
?>
