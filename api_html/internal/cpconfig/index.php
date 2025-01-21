<?php
require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');

// https://gateway.sparo.cc/internal/cpconfig/AQ/TP23810_31

$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

// 파라미터
$itemreq = explode("/",$para);
$jsonreq = trim(file_get_contents('php://input'));

?>

<script
  src="https://code.jquery.com/jquery-2.2.4.min.js"
  integrity="sha256-BbhdlvQf/xTY9gja0Dq3HiwQF8LaCRTXxZKRutelT44="
  crossorigin="anonymous"></script>

<?php
// ACL 확인
//$ip = explode(",",$_SERVER["HTTP_X_FORWARDED_FOR"]);
//$accessip = array("115.68.42.2",
//                  "115.68.42.8",
//                  "115.68.42.130",
//                  "52.78.174.3",
//                  "106.254.252.100",
//                  "115.68.182.165",
//                  "13.124.139.14",
//                  "13.209.232.254",
//                  "118.131.208.123","218.39.39.190","118.131.208.126" 
//                  );

//if(!in_array(trim($ip[0]),$accessip)){
//    header("HTTP/1.0 401 Unauthorized");
//    $res = array("Result"=>"4100","Msg"=>"아이피 인증 오류 : ".get_ip());
//    echo json_encode($res);
//    exit;
//}

$tcode = $itemreq[0];
$ccode = $itemreq[1];

if(strlen($tcode) < 1 ){
    exit;
}
if(strlen($ccode) < 4 ) exit;

$tsql = "select * from pcmsdb.cms_admin_template where tcode = '$tcode'";
$trow = $conn_cms->query($tsql)->fetch_object();

$object = simplexml_load_string($trow->template);

if(empty($object)){
    echo "<h1>설정 정보가 없습니다.</h1>";
    exit;
}else{
    $tsql1 = "select * from pcmsdb.cms_admin_assets where tcode = '$tcode' and ccode= '$ccode'";
    $trow1 = $conn_cms->query($tsql1)->fetch_object();
    if(!empty($trow1)){
        $cconfig1 = json_decode($trow1->cconfig);
    }

}

$title = $object->CONFIG->TITLE;
$items = $object->ITEMS;
echo "<H1>$title ($ccode)</H1>";
echo "<form name='cmsform' id='cmsform' style='margin:0px;' enctype='multipart/form-data'>";
echo "<input name='pm_tcode' id='pm_tcode' type='hidden' value='$tcode'>";
echo "<input name='pm_ccode' id='pm_ccode' type='hidden' value='$ccode'>";
foreach($items as $i){

    $item_label = $i->TITLE;
    $item_id = $i->ID;
    $item_type = $i->TYPE;
    $item_desc = nl2br($i->DESC);
    if(empty($cconfig1)){
        $val= "";

    }else{
        $val= $cconfig1->$item_id;
    }
    // 폼생성
    switch($item_type){

        case 'TEXT':

            echo "<BR><div><b>$item_label</b> - $item_desc</div><div> <input type='$item_type' id = '$item_id' name='$item_id' size=45 value='$val'> </div>  ";
        break;
        case 'TEXTAREA':
            echo "<BR><div><b>$item_label</b> - $item_desc </div><div> <textarea input type='$item_type' id = '$item_id' name='$item_id' style='width: 350px; height: 150px;'>$val</textarea></div>  ";
        break;
        case 'OPTION':
            echo "<BR><div><b>$item_label</b> - $item_desc </div><div> <textarea input type='$item_type' id = '$item_id' name='$item_id' style='width: 350px; height: 150px;'>$val</textarea></div>  ";
        break;
    }


}
echo"</form>";
echo"<BR><BR><input type='button' value=' 저  장 ' onclick='formSubmit()' />";
?>
<script>
function formSubmit() {

    $.ajax({
            url:'/internal/cpconfig/proc/prc_config.php',
            type:'post',
            data:$('#cmsform').serialize(),
            success:function(data){
                alert(data);
            }
        })


}
</script>
<br><br>
<?php
    $tsql = "select * from pcmsdb.cms_admin_assets where tcode = '$tcode' and ccode= '$ccode'";
    $trow = $conn_cms->query($tsql)->fetch_object();
    if(!empty($trow)){
        $cconfig = json_decode($trow->cconfig);
    }


?>
