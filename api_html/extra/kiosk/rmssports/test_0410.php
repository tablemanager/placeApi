<?php
exit;
/*
 *
 * Ű�ڴϾ�
 *
 * �ۼ��� : ������
 * �ۼ��� : 2017-11-21
 *
 *
 *
 */

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');

$para = $_GET['val']; // URI �Ķ�����
$apimethod = $_SERVER['REQUEST_METHOD']; // http �޼���
$apiheader = getallheaders(); // http ����

// �Ķ�����
$itemreq = explode("/",$para);
$jsonreq = trim(file_get_contents('php://input'));

$proc = $itemreq[0];
$couponno = $itemreq[1];

// ACL Ȯ��
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
    $res = array("resultCode"=>"9996","resultMessage"=>"������ ���� ���� : ".get_ip());
    echo json_encode($res);
    exit;
}

if(strlen($couponno)  != 16){
    header("HTTP/1.0 400");
    $res = array("resultCode"=>"9997","resultMessage"=>"�Ķ����� ����");
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
        $res = array("resultCode"=>"9998","resultMessage"=>"�Ķ����� ����");
        echo json_encode($res);
        exit;

}

echo $_resjson;

$_result_ary = json_decode($_resjson);

@setApiLog($couponno ,$proc ,$_SERVER['QUERY_STRING'] ,$_resjson , get_ip() , $_result_ary->resultCode );

function setOrderUse($couponno){
    global $conn_rds;

    // �������� ��ȸ
    $_res = json_decode(getOrderInfo($couponno));

    // ���°� �����϶��� ����ó���� �Ҽ� ����
    if($_res->orderStatus == "1"){

        $usql = "update cmsdb.kidzania_reservation set orderStatus='2', useDt = now() where couponNo = '$couponno' limit 1";
        $conn_rds->query($usql);
        usecouponno($couponno);
        $_result['resultCode'] = "0000";
        $_result['resultMessage'] = "����ó�� ����.";

    }else{

        $_result['resultCode'] = "9999";
        $_result['resultMessage'] = "���¸� �����Ҽ� �����ϴ�.";

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

    // �������� ��ȸ
    $_res = json_decode(getOrderInfo($couponno));

    // ���°� �����϶��� ����ó���� �Ҽ� ����
    if($_res->orderStatus == "1"){

        $usql = "update cmsdb.kidzania_reservation set enterStatus ='Y', enterDt = now() where couponNo = '$couponno' limit 1";
        $conn_rds->query($usql);
        usecouponno($couponno);
        $_result['resultCode'] = "0000";
        $_result['resultMessage'] = "����ó�� ����.";

    }else{

        $_result['resultCode'] = "9999";
        $_result['resultMessage'] = "���¸� �����Ҽ� �����ϴ�.";

    }

    $result = $_result;

    return json_encode($result);
}

function setOrderCancel($couponno){
    global $conn_rds;
    global $conn_cms3;
    // �������� ��ȸ
    $_res = json_decode(getOrderInfo($couponno));

    // ���°� �����϶��� ����ó���� �Ҽ� ����
    if($_res->orderStatus == "2"){

        $usql = "update cmsdb.kidzania_reservation set orderStatus='1', useDt = null where couponNo = '$couponno' limit 1";
        $conn_rds->query($usql);

        // CMS�� ��������

        $ucsql = "UPDATE spadb.ordermts A INNER JOIN spadb.ordermts_coupons B ON A.id = B.order_id SET A.usegu ='2' WHERE A.usegu = '1' and B.couponno ='$couponno'";
        $conn_cms3->query($ucsql);

        $_result['resultCode'] = "0000";
        $_result['resultMessage'] = "��������ó�� ����.";

    }else{

        $_result['resultCode'] = "9995";
        $_result['resultMessage'] = "���¸� �����Ҽ� �����ϴ�.";

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
        $_result['resultMessage'] = "��ȸ����.";

        $_result['couponNo'] = $_res->couponNo;
        $_result['orderStatus'] = $_res->orderStatus;
        $_result['validStartDate'] = $_res->validStartDate;
        $_result['validEndDate'] = $_res->validEndDate;
        $_result['kidzaniaItemCode'] = $_res->kidzaniaItemCode;

        $result = $_result;

    }else{
        $_result['resultCode'] = "9999";
        $_result['resultMessage'] = "��ȸ���� ������ ���ų� ��ȸ�Ҽ� �����ϴ�.";
        $result = $_result;
    }

    return json_encode($result);
}

// Ŭ���̾�Ʈ �ƾ���
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
    // ���� ����ó��
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
        "database" => "SAL_DB", // �����ͺ��̽���
        "uid" => "user_placem",   // ���� ���̵�
        "pwd" => "user_placem2020@!&"    // ���� ����
    );

// DBĿ�ؼ� ����
    $dbconn = sqlsrv_connect($serverName, $connectionOptions);

// ����
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
     * "�ٿ�ó����
    1:����, 2:����, 3:����, 4:��������, 5:��������, 6:����, 7:����(����), 8:������ ����"
     */

    if ($is_update === true){
        switch($_flag){
            case 'COUPONNO':
//                $query = "UPDATE DBO.PM_VOUCHER_LIST SET STATUS = '����' WHERE COUPONNO = '{$coupon}'";
                break;
            case 'USE':
                $update_query = "UPDATE DBO.PM_VOUCHER_LIST SET STATUS = '3' WHERE COUPONNO = '{$coupon}'";
                break;
            case 'CANCEL':
                if ($coupon_status == "7"){
                    //7 �� ���� �� CANCEL �� ���� ���� �� ���ҷ� ����....�̰� ���� �ѹ� �غ����ҵ�
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

// �����ͺ��̽� ������ �����Ѵ�

    sqlsrv_close($dbconn);
}
?>
