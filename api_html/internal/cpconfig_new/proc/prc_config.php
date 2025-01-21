<?php
require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');

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
                  "118.131.208.123",                  
                  "118.131.208.126"
                  );

if(!in_array(trim($ip[0]),$accessip)){
    header("HTTP/1.0 401 Unauthorized");
    $res = array("Result"=>"4100","Msg"=>"아이피 인증 오류 : ".get_ip());
    echo json_encode($res);
    exit;
}

$json= json_encode($_POST);
$tcode = $_POST['pm_tcode'];
$ccode = $_POST['pm_ccode'];

$tsql = "select * from pcmsdb.cms_admin_assets where tcode = '$tcode' and ccode = '$ccode'";;
$trow = $conn_cms->query($tsql)->fetch_object();

if(empty($trow)){
    echo "[입력완료] \n";
    $csql = "insert pcmsdb.cms_admin_assets set regdate=now(), tcode= '".$tcode."',ccode= '".$ccode."', cconfig= '".addslashes($json)."' ";
    print_r($_POST);
}else{
    echo "[수정완료] \n";
    $csql = "update pcmsdb.cms_admin_assets set moddate=now(), cconfig= '".addslashes($json)."' where tcode= '".$tcode."' and ccode= '".$ccode."' limit 1 ";
    print_r($_POST);

}

$conn_cms->query($csql);

?>
