<?php
require_once ('/home/ubuntu/placedev/cmsapps/common/conf/dbconn.conf.php');

// https://gateway.sparo.cc/internal/cpconfig/AQ/TP23810_31


$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

// 파라미터
$itemreq = explode("/",$para);
$jsonreq = trim(file_get_contents('php://input'));


?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>상품 상세정보</title>

    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

    <!-- UI Object -->
    <link rel="stylesheet" type="text/css" href="/internal/formbuild/css/form.css">

    <style>
        textarea {
            resize: none; /* 사용자 임의 변경 불가 */
        }
    </style>
</head>


<body scroll=auto style="overflow-x:hidden">
<div>
<?php

$tcode = $itemreq[0];
$ccode = $itemreq[1];

if(strlen($tcode) < 1 ){
    exit;
}
if(strlen($ccode) < 0 ) exit;




$tsql = "select * from pcmsdb.cms_admin_template where tcode = '$tcode'";
$trow = $conn_cms->query($tsql)->fetch_object();
$template = $trow->template;
if (empty($template)) {
	echo "<pre>";
	print_r($trow); 
	echo "</pre>";
	exit;
}

libxml_use_internal_errors(true);
$object = simplexml_load_string($trow->template);

if ($object === false) {
    echo "SimpleXML parsing failed!";
    foreach (libxml_get_errors() as $error) {
        echo $error->message . PHP_EOL;
    }
    libxml_clear_errors();
    exit;
} /*else {
    print_r($object);
}*/ // 2025-01-17 주석 처리

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
$formdesc = $object->CONFIG->DESC;
$items = $object->ITEMS;

echo "<form name='cmsform' id='cmsform' style='margin:0px;' enctype='multipart/form-data'>";
echo "<input name='pm_tcode' id='pm_tcode' type='hidden' value='$tcode'>";
echo "<input name='pm_ccode' id='pm_ccode' type='hidden' value='$ccode'>";
?>
        <fieldset>
            <legend><?=$title?></legend>
            <div class="form_table">
                <table border="1" cellspacing="0" summary="<?=$title?><">
                    <tbody>
                      <tr>
                          <th scope="row">내용</th>
                          <td>
                              <div class="item">
                                  <p class="i_dsc"><?=$formdesc?></p>
                              </div>
                          </td>
                      </tr>
<!--- 폼 빌딩 시작 -->
<?php
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
              echo "<tr>
                  <th scope='row'>$item_label</th>
                  <td>
                      <div class='item' id=''>
                          <input type='$item_type' name='$item_id' id = '$item_id' title='$item_label' class='i_text'
                                 value='$val' style='width:300px'>
                          <p class='i_dsc'>$item_desc</p>
                      </div>
                  </td>
              </tr>";
        break;
        case 'RADIO':
        break;
        case 'CHECKBOX':
        if($val == "Y") $cc = "checked";
            else $cc = "";
        echo "<tr>
            <th scope='row'>$item_label</th>
            <td>
                <div class='item' id=''>
                    <input name='$item_id' type='checkbox' value='Y' id='$item_id' class='i_radio' $cc ><label for='$item_id'>$item_label</label>
                    <p class='i_dsc' >$item_desc</p>
                </div>
            </td>
        </tr>";

        break;
        case 'DATE':
        echo "<tr>
            <th scope='row'>$item_label</th>
            <td>
                <div class='item' id=''>
                    <input type='date' name='$item_id' id = '$item_id' title='$item_label' class='i_text'
                           value='$val' style='width:300px'>
                    <p class='i_dsc'>$item_desc</p>
                </div>
            </td>
        </tr>";
        break;
        case "SELECTBOX":
              echo "<tr>
                      <th scope='row'>$item_label</th>
                      <td>
                          <div class='item' id=''>
                              <SELECT name='$item_id' id = '$item_id' title='$item_label' class='i_text' style='width:300px'>";

              foreach($i->OPTION->OPT as $opt){
                if($opt->VALUE == $val) $ss = "selected";
                    else $ss = "";
                echo "<option value= '".$opt->VALUE."' $ss>".$opt->TITLE." ($opt->VALUE) </option>";
              }
              echo "</SELECT><p class='i_dsc'>$item_desc</p></div></td></tr>";

        break;
        case "TEXTAREA":
        echo "
        <tr>
            <th scope='row'>$item_label</th>
            <td>
                <div class='item'>
                    <textarea name='$item_id' id = '$item_id' cols='85' rows='10' title='$item_label'
                              class='i_text'>$val</textarea>
                </div>
            </td>
        </tr>
        ";
        break;
      }
    }

/*

                        <tr>
                            <th scope="row">시설명</th>
                            <td>
                                <div class="item" id="">
                                    <input type="text" name="facNm" title="시설명" class="i_text" value="스파플러스">&nbsp; 어쩌고 설명
                                </div>
                            </td>
                        </tr>
                        <!-- 텍스트 시작 --->
                        <tr>
                            <th scope="row">상품명</th>
                            <td>
                                <div class="item" id="">
                                    <input type="text" name="prodNm" title="상품명" class="i_text"
                                           value="[테스트상품] 이천 미란다호텔 스파플러스 이용권." style="width:300px">
                                </div>
                            </td>
                        </tr>
                        <!-- 날짜 텍스트 시작 --->
                        <tr>
                            <th scope="row">판매기간</th>
                            <td>
                                <div class="item">
                                    <input type="date" name="saleStartTime" title="레이블 텍스트" class="i_text"
                                           value="2019-04-11">
                                </div>
                            </td>
                        </tr>
                        <!-- 날짜 텍스트 끝 --->
                        <!-- 상세 텍스트 시작 --->
                        <tr>
                            <th scope="row">상세설명</th>
                            <td>
                                <div class="item">
                                    <textarea name="prodDesc" cols="85" rows="10" title="레이블 텍스트"
                                              class="i_text">42141421412412</textarea>
                                </div>
                            </td>
                        </tr>
                        <!-- 상세 텍스트 끝 --->
                        <!-- 라디오 시작 --->
                        <tr>
                            <th scope="row">사용여부</th>
                            <td>
                                <div class="item">
                                    <input name="useCh1" type="checkbox" value="Y" id="c3" class="i_radio"><label
                                            for="c3">사용</label><br>
                                    <input name="useCh2" type="checkbox" value="N" id="c4" class="i_radio"><label
                                            for="c4">미사용</label>

                                </div>
                            </td>
                        </tr>
                        <!-- 라디오 끝 --->
                        */
?>
<!--- 폼 빌딩 끝 -->




                    </tbody>
                </table>
            </div>
            <div style="float: right">
                <input id="save-btn" type="button" value="저장" onclick='formSubmit()' style="width:100px"/>
                <input id="close-btn" type="button" value="닫기" onclick='window.close()' style="width:100px"/>
            </div>
        </fieldset>
    </form>

    <!-- //UI Object -->
</div>
<script src="https://code.jquery.com/jquery-3.3.1.js"></script>

<script>

function formSubmit() {
    $.ajax({
            url:'/internal/formbuild/proc/prc_config.php',
            type:'post',
            data:$('#cmsform').serialize(),
            success:function(data){
                alert(data);
            }
        })


}
</script>
</script>
</body>
</html>
