<?php
/*
 *
 * 키자니아
 *
 * 작성자 : 이정진
 * 작성일 : 2017-11-21
 *
 *
 *
 */

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');

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
    header("HTTP/1.0 401 Unauthorized");
    $res = array("resultCode"=>"9996","resultMessage"=>"아이피 인증 오류 : ".get_ip());
    echo json_encode($res);
    exit;
}

if(strlen($couponno)  != 16){
    header("HTTP/1.0 400");
    $res = array("resultCode"=>"9997","resultMessage"=>"파라미터 오류");
    echo json_encode($res);
    exit;
}

header("Content-type:application/json");


$mdate = date("Y-m-d");

switch($proc){
    case 'COUPONNO':
        $_resjson = getOrderInfo($couponno);
        break;
    case 'USE':
        $_resjson = setOrderUse($couponno);
        break;
    case 'CANCEL':
        $_resjson = setOrderCancel($couponno);
        break;
    case 'ENTER':
        $_resjson = setOrderEnter($couponno);
        break;
    default:
        header("HTTP/1.0 400");
        $res = array("resultCode"=>"9998","resultMessage"=>"파라미터 오류");
        echo json_encode($res);
        exit;

}

echo $_resjson;

$_result_ary = json_decode($_resjson);

@setApiLog($couponno ,$proc ,$_SERVER['QUERY_STRING'] ,$_resjson , get_ip() , $_result_ary->resultCode );

function setOrderUse($couponno){
    global $conn_rds;

    // 쿠폰상태 조회
    $_res = json_decode(getOrderInfo($couponno));

    // 상태가 배포일때만 사용처리를 할수 있음
    if($_res->orderStatus == "1"){

        $usql = "update cmsdb.kidzania_reservation set orderStatus='2', useDt = now() where couponNo = '$couponno' limit 1";
        $conn_rds->query($usql);
        usecouponno($couponno);
        $_result['resultCode'] = "0000";
        $_result['resultMessage'] = "사용처리 성공.";

    }else{

        $_result['resultCode'] = "9999";
        $_result['resultMessage'] = "상태를 변경할수 없습니다.";

    }


    $result = $_result;

    return json_encode($result);
}

function setApiLog($couponno ,$proc , $req_url , $res , $_ip , $result_code )
{
    global $conn_rds;

    $logsql = "insert cmsdb.kidzania_log set couponno='$couponno' ,proc='$proc',request='$req_url',response='$res' , req_ip='$_ip' , result_code = '$result_code' ";

    $conn_rds->query($logsql);

}

function setOrderEnter($couponno){
    global $conn_rds;

    // 쿠폰상태 조회
    $_res = json_decode(getOrderInfo($couponno));

    // 상태가 배포일때만 사용처리를 할수 있음
    if($_res->orderStatus == "1"){

        $usql = "update cmsdb.kidzania_reservation set enterStatus ='Y', enterDt = now() where couponNo = '$couponno' limit 1";
        $conn_rds->query($usql);
        usecouponno($couponno);
        $_result['resultCode'] = "0000";
        $_result['resultMessage'] = "입장처리 성공.";

    }else{

        $_result['resultCode'] = "9999";
        $_result['resultMessage'] = "상태를 변경할수 없습니다.";

    }

    $result = $_result;

    return json_encode($result);
}

function setOrderCancel($couponno){
    global $conn_rds;
    global $conn_cms3;
    // 쿠폰상태 조회
    $_res = json_decode(getOrderInfo($couponno));

    // 상태가 배포일때만 사용처리를 할수 있음
    if($_res->orderStatus == "2"){

        $usql = "update cmsdb.kidzania_reservation set orderStatus='1', useDt = null where couponNo = '$couponno' limit 1";
        $conn_rds->query($usql);

        // CMS에 사용해제

        $ucsql = "UPDATE spadb.ordermts A INNER JOIN spadb.ordermts_coupons B ON A.id = B.order_id SET A.usegu ='2' WHERE A.usegu = '1' and B.couponno ='$couponno'";
        $conn_cms3->query($ucsql);

        $_result['resultCode'] = "0000";
        $_result['resultMessage'] = "사용취소처리 성공.";

    }else{

        $_result['resultCode'] = "9995";
        $_result['resultMessage'] = "상태를 변경할수 없습니다.";

    }

    $result = $_result;

    return json_encode($result);
}

function getOrderInfo($couponno){
    global $conn_rds;

    $orderqry2 = "SELECT 
							couponNo,
							orderStatus,
							validStartDate,
							validEndDate,
							kidzaniaItemCode
					FROM 
							cmsdb.kidzania_reservation 
					WHERE 
							couponNo='$couponno'
	";

    $_res = $conn_rds->query($orderqry2)->fetch_object();

    $_result = array();

    if(count($_res) > 0){
        $_result['resultCode'] = "0000";
        $_result['resultMessage'] = "조회성공.";

        $_result['couponNo'] = $_res->couponNo;
        $_result['orderStatus'] = $_res->orderStatus;
        $_result['validStartDate'] = $_res->validStartDate;
        $_result['validEndDate'] = $_res->validEndDate;
        $_result['kidzaniaItemCode'] = $_res->kidzaniaItemCode;

        $result = $_result;

    }else{
        $_result['resultCode'] = "9999";
        $_result['resultMessage'] = "조회실패 쿠폰이 없거나 조회할수 없습니다.";
        $result = $_result;
    }

    return json_encode($result);
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

function set_kidzania_db($_flag , $coupon)
{
    $serverName = "118.130.130.38";
    $connectionOptions = array(
        "database" => "SAL_DB", // 데이터베이스명
        "uid" => "user_placem",   // 유저 아이디
        "pwd" => "user_placem2020@!&"    // 유저 비번
    );

// DB커넥션 연결
    $dbconn = sqlsrv_connect($serverName, $connectionOptions);

// 쿼리
    $query = "SELECT * FROM DBO.PM_VOUCHER_LIST WHERE COUPONNO = '{$coupon}'";

    $stmt = sqlsrv_query($dbconn, $query);

    $is_update = false;
    $coupon_status = '';

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))
    {
        if ($row['COUPONNO'] == $coupon){
            $is_update = true;
            $coupon_status = $row['STATUS'];
        }
    }
    sqlsrv_free_stmt($stmt);

    $update_query = "";

    /*
     * "바우처상태
    1:배포, 2:구매, 3:예약, 4:예약취소, 5:결제취소, 6:폐기, 7:입장(사용), 8:입장후 취소"
     */

    if ($is_update === true){
        switch($_flag){
            case 'COUPONNO':
//                $query = "UPDATE DBO.PM_VOUCHER_LIST SET STATUS = '내용' WHERE COUPONNO = '{$coupon}'";
                break;
            case 'USE':
                $update_query = "UPDATE DBO.PM_VOUCHER_LIST SET STATUS = '3' WHERE COUPONNO = '{$coupon}'";
                break;
            case 'CANCEL':
                if ($coupon_status == "7"){
                    //7 번 입장 후 CANCEL 이 오면 입장 후 취소로 본다....이건 문의 한번 해봐야할듯
                    $update_query = "UPDATE DBO.PM_VOUCHER_LIST SET STATUS = '8' WHERE COUPONNO = '{$coupon}'";
                } else {
                    $update_query = "UPDATE DBO.PM_VOUCHER_LIST SET STATUS = '4' WHERE COUPONNO = '{$coupon}'";
                }
                break;
            case 'ENTER':
                $update_query = "UPDATE DBO.PM_VOUCHER_LIST SET STATUS = '7' WHERE COUPONNO = '{$coupon}'";
                break;
        }

        if (!empty($query)){
            $stmt_update = sqlsrv_query($dbconn, $update_query);
        }

    }

// 데이터베이스 접속을 해제한다

    sqlsrv_close($dbconn);
}
?>