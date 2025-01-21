<?php
require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
$conn_cms3->query("set names utf8");
$conn_rds->query("set names utf8");

$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

// 파라미터
$itemreq = explode("/",$para);
$jsonreq = trim(file_get_contents('php://input'));

// ACL 확인
$ip = explode(",",$_SERVER["HTTP_X_FORWARDED_FOR"]);
$accessip = array("115.68.42.2",
                  "115.68.42.8",
                  "115.68.42.130",
                  "52.78.174.3",
                  "106.254.252.100",
                  "115.68.182.165",
                  "13.124.139.14",
                  "13.209.232.254",
                  "118.131.208.123" );

if(!in_array(trim($ip[0]),$accessip)){
//    header("HTTP/1.0 401 Unauthorized");
//    $res = array("Result"=>"4100","Msg"=>"아이피 인증 오류 : ".get_ip());
//    echo json_encode($res);
    exit;
}else{

    $ccode = $itemreq[0];
    $json  = $jsonreq;
    
        echo "[수정완료] \n";
        $csql = "update cmsdb.nbooking_items set itemOptions = '".addslashes($json)."' where agencyBizItemId= '".$ccode."' limit 1 ";
    
        print_r($_POST);
    
    
    
    $conn_rds->query($csql);
    

}

?>