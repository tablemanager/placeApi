<?php

// 로그기록 패스. 맨뒤에 / 포함 
$logpath = dirname(__FILE__)."/txt/";
$logtp = "dogouse";

// 2달지난 로그 지운다.
dellog($logpath, $logtp);

// 로그 기록
$fnm = date('Ymd')."{$logtp}.log";
$fp = fopen("{$logpath}{$fnm}", 'a+');
fwrite($fp, "\n\n====================\n");
fwrite($fp, date("Y-m-d H:i:s")." 사용처리\n");
// 입력값 기록
fwrite($fp, "_SERVER\n");
fwrite($fp, print_r($_SERVER, true));
fwrite($fp, "_POST\n");
fwrite($fp, print_r($_POST, true));
fwrite($fp, "_GET\n");
fwrite($fp, print_r($_GET, true));

include '/home/sparo.cc/dogo_script/class/class.dogo.php';
include '/home/sparo.cc/dogo_script/model/dogo_model.php';
header("Content-type:application/json");

$coupon_code = $_GET['coupon_code'];
$sqcode = $_GET['sqcode'];

//__usecouponno($coupon_code);

if (empty($coupon_code) || empty($sqcode)){
    $json_result['result'] = '2';
    $json_result['message'] = '파라미터 누락';
    output_msg($json_result);
}

$_dogo = new \Dogo\Dogo();
$_dogo_model = new \Dogo\dogo_model();

// 이미 생성된 예약건인지 체크
$chk_get_order = $_dogo_model->getDogoOrders([ "barcode" => $sqcode ]);

if (!isset($chk_get_order[0]['order_num'])){
    $json_result['result'] = '2';
    $json_result['message'] = '없는 예약';
    __usecouponno($sqcode);  //혹시 몰라서 사용처리
    output_msg($json_result);
} else {
    if ($chk_get_order[0]['status'] == "R" || $chk_get_order[0]['status'] == "U"){

        if ($chk_get_order[0]['order_num'] == $coupon_code){
            $json_result['result'] = '1';
            $json_result['message'] = '성공';

            __usecouponno($sqcode);  //혹시 몰라서 사용처리
            $_dogo_model->edit_dogo_order_status($chk_get_order[0]['order_num'], 'U');
        } else {
            $json_result['result'] = '2';
            $json_result['message'] = '예약 불 일치';
            output_msg($json_result);
        }

//        //ordermts 사용처리
//        $_dogo_model->setOrderMtsCouponUse($chk_get_order[0]['order_num'] ,"2" ,"","");

        output_msg($json_result);
    } else if ($chk_get_order[0]['status'] == "C"){
        $json_result['result'] = '2';
        $json_result['message'] = '취소된 예약';
        output_msg($json_result);
    } else {
        $json_result['result'] = '2';
        $json_result['message'] = '업데이트 불가';
        output_msg($json_result);
    }
}

function __usecouponno($no){
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
    return "Y";
}

function output_msg($_result)
{
    global $fp;

    $res = json_encode($_result);
    echo $res;

    // 한글 정상 출력
    $res2 = json_encode($_result, JSON_UNESCAPED_UNICODE);

    fwrite($fp, "Response\n");
    fwrite($fp, print_r($res2, true));

    exit;

}

function dellog($logpath, $logtp){
    $sdate = date('Y-m-d');
    $ldate  = date('Ymd', strtotime('-2 month', strtotime($sdate)));

    //echo $ldate;
    $dellog = "{$logpath}{$ldate}{$logtp}.log";
    //echo "삭제대상 로그 찾기 $dellog ";

    if(file_exists($dellog)){
        unlink($dellog);
        //echo "$dellog log file deleted now.\n\n";
    }else{
        // 굳이 로그남길 필요가 없을듯
        //echo "[상태검사] 삭제할 로그파일이 없는 상태입니다.\n\n";
    }
}

?> 
