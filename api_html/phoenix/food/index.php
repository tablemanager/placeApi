<?php
/*
 * 휘닉스파크 연동 인터페이스:2022 식사권 주문등록 전용 인터페이스
 * cm-admin 에서 엑셀로 식사권 주문 등록하면 이 인터페이스로 휘닉스 쿠폰번호를 가져온다.
 *
 * 작성자 : tony
 * 작성일 : 2022-12-01
 *
 * 발권(POST)		: https://gateway.sparo.cc/phoenix/food/{cm_item_code}
 */

//http://gateway.sparo.cc/phoenix/cancel/18100409982
require_once ('/home/sparo.cc/phoenix_script/lib/phoenixApi.php');
require_once ('/home/sparo.cc/phoenix_script/lib/ConnSparo2.php');
header("Content-type:application/json");

// ACL 확인
/*
$accessip = array(
    "115.68.42.2",
    "115.68.42.8",
    "115.68.42.130",
    "52.78.174.3",
    "106.254.252.100",
    "115.68.182.165",
    "13.124.139.14",
    "13.209.232.254"
);
*/

// ACL 확인 : 3번서버에서만 호출된다.
$accessip = array(
    "13.209.232.254",   // cm-admin 배치
    "52.78.174.3",
    "106.254.252.100",  // 개발망
);

if(!in_array(get_ip(),$accessip)){
    header("HTTP/1.0 401 Unauthorized");
    $res = array("RESULT"=>"4100","MSG"=>"아이피 인증 오류 : ".get_ip());
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    exit;
}

$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

// 파라미터
$itemreq = explode("/",$para);
$jsonreq = trim(file_get_contents('php://input'));
$data = json_decode($jsonreq,TRUE);
//$rprsSellNo = $itemreq[0];


if ($data['ch_orderno'] == '16485844' && 1 == 2) {
    // 개발서버
    $phoenixApi = new phoenixApi('y');
} else {
    $phoenixApi = new phoenixApi();
}

$fp = @fopen('./test.log', 'a');

// 최근 2시간 이내 받은 키가 있으면 재사용한다.
//$get_access = $phoenixApi->get_access_token();

$connSparo2 = new ConnSparo2();

$pkgcoupon_cnt =  $connSparo2->count_phoenix_pkgcoupon_ch_orderno_food($data['ch_orderno'], $data['ch_id']);

// 원주문과 동일한 채널주문번호로 식사권 주문을 넣는 것이므로 1개는 원주문이다.
if($pkgcoupon_cnt > 1){
    $res = json_encode(array(
            "RESULT"=>"4200",
            "MSG"=>"중복 데이터:$pkgcoupon_cnt 개"), JSON_UNESCAPED_UNICODE
    );
  //  echo $res;
//    exit;
}
else{
    $res = json_encode(array(
            "RESULT"=>"0000",
            "rprsSellNo"=>"RTNrprsSellNo",
            "rprsBarCd"=>"RTNrprsBarCd",
            "MSG"=>"데이터:$pkgcoupon_cnt 개"), JSON_UNESCAPED_UNICODE
    );
} 
echo $res;
exit;

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
                    "RESULT"=>"4300",
                    "MSG"=>"패키지없음"), JSON_UNESCAPED_UNICODE
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

            $fields = array(
                //'sellpSellNo'=>$data['ch_orderno'],      //판매처판매번호	sellpSellNo	15	String		Y
                'sellpSellNo'=>$data['oid'],      //판매처판매번호	sellpSellNo	15	String		Y
                'sellDate'=>$sellDate,         //판매일자	sellDate	8	String		Y
                'pkgCd'=>$row['pkgCd'],            //상품코드	pkgCd	10	String		Y
                'bsuCd'=>$row['bsuCd'],            //사업장코드	bsuCd	10	String		Y
                'sellClientCd'=>$_sellClientCd,     //판매거래처코드	sellClientCd	10	String		Y
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
//yjlee            $connSparo2->insert_phoenix_pkgcoupon_ch_order($insertPkgData);
//yjlee            $pkgcoupon_info = $connSparo2->get_phoenix_pkgcoupon_ch_orderno_last($data['ch_orderno']);
//yjlee            $pkgcoupon_id = $pkgcoupon_info['id'];
//yjlee            @fwrite($fp, print_r($pkgcoupon_id, true));

            $fields_tmp = json_encode($fields, JSON_UNESCAPED_UNICODE);
            $fields = json_encode($fields, JSON_UNESCAPED_UNICODE);

            $str = "\n-------------------------------------------------\n";
            $str .= $data['ch_orderno'] . "\n";
            $str .= "request time: " . date("YmdHis") . "\n";
            $str .= "-------------------------------------------------\n";
            $str .= $fields_tmp;
            @fwrite($fp, $str);

//yjlee            $order_responce = $phoenixApi->IF_SM_202_set_order($get_token->access_token, $fields);
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

/*
//yjlee
            if($connSparo2->update_phoenix_pkgcoupon_202_Result($pkgcoupon_id, $order_responce))
            {

                $res = json_encode(array(
                        "RESULT"=>"0000",
                        "rprsSellNo"=>$order_responce['rprsSellNo'],
                        "rprsBarCd"=>$order_responce['rprsBarCd'],
                        "MSG"=>"성공"), JSON_UNESCAPED_UNICODE
                );

                echo $res;
                exit;

            } else {

                $res = json_encode(array(
                        "RESULT"=>"W0000",
                        "rprsSellNo"=>$order_responce['rprsSellNo'],
                        "rprsBarCd"=>$order_responce['rprsBarCd'],
                        "MSG"=>"update fail"), JSON_UNESCAPED_UNICODE
                );

                echo $res;
                exit;
            }
*/

        }
    }

    fclose($fp);

}else{

    $res = json_encode(array(
            "RESULT"=>"4500",
            "MSG"=>"access fail"), JSON_UNESCAPED_UNICODE
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
