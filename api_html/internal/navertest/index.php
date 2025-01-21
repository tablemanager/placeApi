
<?php
require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
$conn_cms3->query("set names utf8");
$conn_rds->query("set names utf8");

// https://gateway.sparo.cc/internal/cpconfig/AQ/TP23810_31

// ACL 확인
$ip = explode(",",$_SERVER["HTTP_X_FORWARDED_FOR"]);
$accessip = array(
                  "106.254.252.100",
                  "13.209.232.254",
                  "118.131.208.123",
				          "218.39.39.190",
                  "118.131.208.126" 
                  );


if(!in_array(trim($ip[0]),$accessip)){
    echo "IP : ".$ip[0];
    exit;
}


$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

// 파라미터
$itemreq = explode("/",$para);
$jsonreq = trim(file_get_contents('php://input'));

$bizitemid = $itemreq[1];

$row = $conn_rds->query("select * from nbooking_items where agencyBizItemId = '$bizitemid'")->fetch_object();

print_r($row->itemOptions);

$_test_ary = json_decode($row->itemOptions , true);
echo "<br>";
echo "<br>";
echo "<br>";
print_r($_test_ary);

foreach ($_test_ary as $k ){
    foreach ($k as $kk => $vv){
        echo $kk."--->".$vv."<br>";
    }
}

//echo print_r(json_decode($row->naver_price));
echo "<form name='cmsform' id='cmsform' style='margin:0px;' enctype='multipart/form-data'>";
echo "<input name='pm_ccode' id='pm_ccode' type='hidden' value='$bizitemid'>";
?>
<h1>[<?=$bizitemid?>] <?=$row->itemNm?></H1>
<textarea id="naveritems" name = 'naveritems'  cols=90% rows=25></textarea>

<BR><BR><input type='button' value=' 저  장 ' onclick='formSubmit()' />
<script
  src="https://code.jquery.com/jquery-2.2.4.min.js"
  integrity="sha256-BbhdlvQf/xTY9gja0Dq3HiwQF8LaCRTXxZKRutelT44="
  crossorigin="anonymous"></script>

<script>
// arbitrary js object:
var myJsObj2 = <?=$row->itemOptions?>;

// using JSON.stringify pretty print capability:
var str2 = JSON.stringify(myJsObj2, undefined, 4);

// display pretty printed object in text area:
document.getElementById('naveritems').innerHTML = str2;

function formSubmit() {

    // $.ajax({
    //         url:'/internal/navertest/proc/prc_config.php',
    //         type:'post',
    //         data:$('#cmsform').serialize(),
    //         success:function(data){
    //             alert(data);
    //         }
    //     })


}
</script>
