<?php
exit;
include_once '/home/sparo.cc/staynote_script/class/aquaplanet.php';
include_once '/home/sparo.cc/staynote_script/class/aquaplanet_dao.php';
include_once '/home/sparo.cc/staynote_script/class/aqua_model.php';

//header("Content-type:application/json");

// ACL 확인
$accessip = array("115.68.42.2",
    "115.68.42.8",
    "115.68.42.130",
    "52.78.174.3",
    "106.254.252.100",
    "115.68.182.165",
    "13.124.139.14",
    "54.180.190.102",
    "52.78.51.243",
    "13.209.232.254"
);

//if(!in_array(get_ip(),$accessip)){
//    header("HTTP/1.0 401 Unauthorized");
//    $res = array("RESULT"=>false,"MSG"=>"아이피 인증 오류 : ".get_ip());
//    echo json_encode($res);
//    exit;
//}

$para = $_GET['val']; // URI 파라미터

$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders();            // http 헤더

$jsonreq = trim(file_get_contents('php://input'));
$data = json_decode($jsonreq,TRUE);

//$uri=$_SERVER['REQUEST_URI'];
//$totaldata = date("Y-m-d H:i:s")."[".$_SERVER['REMOTE_ADDR']."][".$uri."]".PHP_EOL.print_r($data, true);
//addLog(array(
//    'date' => date('Y-m-d H:i:s'),
//    'ip' => $_SERVER['REMOTE_ADDR'],
//    'get' => $_GET,
//    'post' => $_POST,
//    'json' => $data,
//    'request' => $_REQUEST,
//    'url' => $_SERVER['REQUEST_URI'],
//    'query_string' => $_SERVER['QUERY_STRING']
//));
print_r($data);
switch ($para){
    case 'order':

        $note_dao = new aquaplanet_dao();
        $note = new aquaplanet();

        $staynotemodel = new staynotemodel();

        $_pcms_res = array();
        $_pcms_template = array();
        $_pcms_rese = array();
        $_pcms_rese_sub = array();
        $tot_price = 0;
        $acc_price = 0;

        $_result_data_set = array();
        $is_set = true;

        for ($i = 0; $i < count($data['RESE']);$i++){
            $_pcms_data = $note_dao->getStayNoteReseNo($data['RESE'][$i]['ORDERNO']);

            if (!empty($_pcms_data[0]['coupon']) || $_pcms_data[0]['coupon'] != null){
                $_pcms_res[$i]['couponno']  =  $_pcms_data[0]['coupon'];
            }
            $_code_ary = $staynotemodel->getTemplateCode($data['RESE'][$i]['SELLCODE']);

            if (empty($_code_ary['ccode'])){
                $_pcms_template[$i]['template'] = "NG";
            }

            $template_ary = $note_dao->getTemplate($_code_ary['ccode']);
            print_r($template_ary);
            $template_data = json_decode($template_ary[0]['cconfig'],TRUE);

            $tot_price = $tot_price + $template_data['saipamt'];
            $acc_price = $tot_price + $template_data['saipamt'];

            if ($is_set){
                $_result_data_set['cd'] = $template_data['cd'];
                $_result_data_set['jm_order_num'] = $data['RESENO'];
                $_result_data_set['spot_id'] = $template_data['spot_id'];
                $_result_data_set['acc_type'] = "1";
                $_result_data_set['state'] = "1";
                $_result_data_set['order_name'] = $data['RCVER_NM'];
                $_result_data_set['order_hp'] = $data['RCVER_TEL'];
                $_result_data_set['exp_date'] = $data['usedate'];
                $_result_data_set['memo'] = '';
                $is_set = false;
            }

            $_data['cd'] = $template_data['cd'];
            $_data['spot_id'] = $template_data['spot_id'];
            $_data['ticket_id'] = $template_data['ticket_id'];
            $_data['aprice'] = $template_data['saipamt'];
            $_data['bprice'] = $template_data['jungamt'];
            $_data['sprice'] = $template_data['amt'];
            $_data['SELLCODE'] = $data['SELLCODE'];
            $_data['RCVER_NM'] = $data['RCVER_NM'];
            $_data['ORDERNO'] = $data[$i]['ORDERNO'];
            $_data['user_hp'] = $data['RCVER_TEL'];

//            $thisCouponId = $staynotemodel->insert_staynote_orderInfo($_data);

            if(!$thisCouponId){
                $_pcms_rese[$i]['rese'] = 'NG';
            } else {
                $_pcms_rese[$i]['rese'] = 'OK';
            }

            $_pcms_rese_sub[$i] = array('jm_rev_num' => $data[$i]['ORDERNO'],                   // * (String) 예약번호
                'ticket_id' => $template_data['ticket_id'],                    // * (int) 상품[권종]고유번호 제주모바일 고유번호 / 상품등록 후 키 전달 > 스테이노트 셋팅
                'qty' => '1',                          //(int) 예약번호 수량
                'due_date' => $data['usedate'],                     //(String) 이용예정일 YYYYMMDD
                'aprice' => $template_data['saipamt'],                       //(int) 입금가aprice
                'bprice' => $template_data['jungamt'],                       //(int) 정상가
                'sprice' => $template_data['amt'],                       // * (int) 판매가
                'dc_price' => "0",                     //(int) 할인금액
                'acc_price' => $template_data['amt'],                    //(int) 결제가
                'state' => 'Y'                         //(String) 이용구분)
            );

        }

        $_result_data_set['rev_list'] = $_pcms_rese_sub;

        print_r($_result_data_set);

        break;

    case 'info':

        $note_dao = new aquaplanet_dao();
        $note = new aquaplanet();

        $_result_data = $note_dao->getStayNoteReseNo($data['ORDERNO']);
        $_data_ary = $note->ticket_search($_result_data[0]['sn_order_num']);

        if ($_data_ary['error'] == "0"){
            //성공
            if ($_data_ary['rev_list'][0]['bar_use'] == null){
                $_data_ary['rev_list'][0]['bar_use'] = "N";
            }

            $res = json_encode(array(
                    "RESULT"=>true,
                    "MSG"=>"조회 성공",
                    "state"=>$_data_ary['state'],  //1예약 ,2취소 사용은?
                    "use"=>$_data_ary['rev_list'][0]['state'],
                    "bar_use"=>$_data_ary['rev_list'][0]['bar_use'], // N 미발행 Y 사용 F 취소  S:발권
                    "use_datets"=>$_data_ary['rev_list'][0]['usedatets'], // bar_use 의 최근 업데이트 시간
                    "BARCODE"=>$_data_ary['rev_list'][0]['bar_no'] )
            );

        } else {
            //실패
            $res = json_encode(array(
                "RESULT"=>false,
                "MSG"=>$_data_ary['msg']
            ));
        }

        break;
    case 'cancel':

        $note_dao = new aquaplanet_dao();
        $note = new aquaplanet();

        $staynotemodel = new staynotemodel();
//        $_result_data = $staynotemodel->getStayNoteReseNo($data['ORDERNO']);
        $_result_data = $note_dao->getStayNoteReseNo($data['ORDERNO']);
        $_data_ary = $note->ticket_cancel($_result_data[0]['sn_rev_num'] , $_result_data[0]['sn_order_num']);

        if ($_data_ary['error'] == "0"){
            if ($_data_ary['state'] == "2"){
                $res = json_encode(array(
                    "RESULT"=>true,
                    "MSG"=>"발권취소 성공"
                ));

                $_update_ary['sn_rev_num'] = $_result_data[0]['sn_rev_num'];
                $_update_ary['sn_order_num'] = $_result_data[0]['sn_order_num'];
                $_update_ary['ORDERNO'] = $data['ORDERNO'];
                $_update_ary['state'] = "C";

                $staynotemodel->update_staynote_cancel($_update_ary);

            } else {
                $res = json_encode(array(
                    "RESULT"=>false,
                    "MSG"=>$_data_ary['msg']
                ));
            }
        } else if ($_data_ary['error'] == "1"){
            $res = json_encode(array(
                "RESULT"=>false,
                "MSG"=>$_data_ary['msg']
            ));
        } else {
            $res = json_encode(array(
                "RESULT"=>false,
                "MSG"=>"시스템오류. 통신에 실패했습니다."
            ));
        }

        break;

    case 'use':
        //사용처리 리시버 스테이노트 미구현
        $_order_no = $_POST['jm_order_num'];

        $note_dao = new aquaplanet_dao();
        $note = new aquaplanet();

        $staynotemodel = new staynotemodel();

        $_result_data = $staynotemodel->getPCMSReseNo($_order_no);

        $_code_ary['order_no'];

        break;

    case 'rev_cancel':

        //주문 취소 리시버 스테이노트 미구현
        $_cd = $_POST['cd'];
        $_order_no = $_POST['jm_order_num'];

        $note_dao = new aquaplanet_dao();
        $note = new aquaplanet();

        $staynotemodel = new staynotemodel();

        $_result_data = $note_dao->getStayNoteReseNo($_order_no);

        $_update_ary['sn_rev_num'] = $_result_data[0]['sn_rev_num'];
        $_update_ary['sn_order_num'] = $_result_data[0]['sn_order_num'];
        $_update_ary['ORDERNO'] =$_order_no;
        $_update_ary['state'] = "C";

        $staynotemodel->update_staynote_cancel($_update_ary);

        //스테이노트에서 상태값을 던져주면 그에따라 업데이트 로직을 추가한다.
        //
        $use_date = date("Y-m-d h:i:s");
        if (!empty($_result_data[0]['sn_order_num']) && !empty($_result_data[0]['coupon'])){

            $res = json_encode(array(
                "error"=>0,
                "usedatets"=>$use_date,
                "sn_order_num"=>$_pcms_data[0]['sn_order_num']
            ));
        } else {
            $res = json_encode(array(
                "error"=>1,
                "msg"=>"주문 정보를 찾을 수 없습니다",
                "usedatets"=>$use_date,
                "sn_order_num"=>""
            ));
        }

        break;

    default:
        $res = json_encode(array(false, "API 타입이 존재하지 않습니다."));
        break;
}

echo $res;

function echoSystem($_res)
{
    echo $_res;
    exit;
}

// 임시 로그 생성
function addLog($aResponse = array(), $fileName = 'aquaplanet')
{
    if (empty($aResponse) === true || is_array($aResponse) === false) {
        return false;
    }


    $fp = fopen('/home/sparo.cc/staynote_script/log/' . $fileName . '-' . date('Ymd') . '.log', 'a+');
    fwrite($fp, print_r($aResponse, true) . PHP_EOL);
    fclose($fp);

    return true;
}

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
