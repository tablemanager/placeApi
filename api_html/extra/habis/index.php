<?php

/*
 *
 * 휘닉스파크 연동 인터페이스
 *
 * 작성자 : 미카엘
 * 작성일 : 2018-12-13
 *
 * 조회(GET)			: https://gateway.sparo.cc/everland/sync/{BARCODE}
 * 발권(POST)		: https://gateway.sparo.cc/everland/sync/{BARCODE}
 * 발권취소(PATCH)	: https://gateway.sparo.cc/everland/sync/{BARCODE}
 */

//http://extapi.sparo.cc/hanwha/info
//http://extapi.sparo.cc/hanwha/order
//http://extapi.sparo.cc/hanwha/cancel

include '/home/sparo.cc/hanwha_script/hanwha/class/class.hanwha.php';
include '/home/sparo.cc/hanwha_script/lib/class/class.lib.common.php';
require '/home/sparo.cc/hanwha_script/hanwha/class/hanwhamodel.php';

//header("Content-type:application/json");

// ACL 확인
$accessip = array(
    "115.68.42.2",
    "115.68.42.8",
    "115.68.42.130",
    "115.89.22.27",
    "52.78.174.3",
    "106.254.252.100",
    "115.68.182.165",
    "13.124.139.14",
    "218.39.39.190",
    "114.108.179.112",
    "13.209.232.254",
    "13.124.215.30",
    "118.131.208.123",
    "221.141.192.124",
    "103.60.126.37"
);

if (!in_array(get_ip(), $accessip)) {
    header("HTTP/1.0 401 Unauthorized");
    $res = array("RESULT" => false, "MSG" => "아이피 인증 오류 : " . get_ip());
    echo json_encode($res);
    exit;
}

$para = $_GET['val']; // URI 파라미터

$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders();            // http 헤더

$jsonreq = trim(file_get_contents('php://input'));
$data = json_decode($jsonreq, true);

switch ($apimethod) {
    case 'GET':
        $_fa = $_REQUEST['var'];

        $hanwha = new \Hanwha\Hanwha();
        $_is_request = false;

        //일반 한화
        if ($_fa == "100") {
            echo "일반한화\n";
            $hanwha->setCORP_CD("1000");
            $hanwha->setCONT_NO("11203810");
            $_is_request = true;//63
        } else if ($_fa == "200") {
            echo "63\n";
            $hanwha->setCORP_CD("1000");
            $hanwha->setCONT_NO("11900018");
            $_is_request = true;
        } else if ($_fa == "400"){
            echo "jeju\n";

            $hanwha->setCORP_CD("6000");
            $hanwha->setCONT_NO("11900057");
            $_is_request = true;
        } else {
            if ($_fa == "300") {  //일산
                echo "일산\n";
                $hanwha->setCORP_CD("4000");
                $hanwha->setCONT_NO("11900080");
                $_is_request = true;
            }
        }

        if ($_is_request === true) {
            $_data_result = $hanwha->getDsSearch();

			array_print($_data_result['Data']['ds_resultContPakgList']);
//			array_print($_data_result['Data']['ds_resultContPakgList']);
        }
        break;
}

function get_ip()
{

    if (empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
        $ip = $_SERVER["REMOTE_ADDR"] . ",";
    } else {
        $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
    }

    $res = explode(",", $ip);

    return trim($res[0]);
}

function array_print($p_ary)
{
    echo '<font >';
    if (is_array($p_ary)) {
        echo '<xmp>', print_r($p_ary) . '</xmp>';
    } else {
        echo $p_ary;
    }
    echo '</font>';
}


?>