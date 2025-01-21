<?php

/*
 *
 * 제주 모바일 사용처리 리시버
 *
 * 작성자 : 이정진
 * 작성일 : 2020-06-26
 *
  */

require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
header("Content-type:application/json");

$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

// 파라미터
$itemreq = explode("/",$para);
$jsonreq = trim(file_get_contents('php://input'));

 $cmdtype  = $itemreq[0];



// ACL 확인
$accessip = array("115.68.42.2",
                  "115.68.42.8",
                  "115.68.42.130",
                  "52.78.174.3",
                  "106.254.252.100",
                  "115.68.182.165",
                  "13.124.139.14",
                  "13.209.232.254",
				          "218.39.39.190",
				          "13.124.215.30",
                  "18.163.36.64",
                  "13.209.184.223"
                  );

if(!in_array(get_ip(),$accessip)){
  header("HTTP/1.0 400 Bad Request");
  $res = array("result"=>"F","msg"=>"파라미터 오류");
  echo json_encode($res);
    exit;
}


switch($apimethod){
    case 'POST':

        switch($cmdtype){
          case 'use':
              $res = setuse($jsonreq);
          break;
          case 'unuse':
              $res = setunuse($jsonreq);
          break;
          default:
          header("HTTP/1.0 400 Bad Request");
          $res = array("result"=>"F","msg"=>"파라미터 오류");
          echo json_encode($res);

          exit;
        }
    break;
	  default:
        header("HTTP/1.0 400 Bad Request");
        $res = array("result"=>"F","msg"=>"파라미터 오류");
        echo json_encode($res);
        exit;
}



function setuse($jsonreq){

      header("HTTP/1.0 200");
      $res = array("result"=>"S","msg"=>"사용처리 성공");
      echo json_encode($res);
      exit;
};

function setunuse($jsonreq){

      header("HTTP/1.0 200");
      $res = array("result"=>"S","msg"=>"사용취소 처리 성공");
      echo json_encode($res);
      exit;
};

function get_ip(){

	if(empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
		 $ip= $_SERVER["REMOTE_ADDR"].",";
	}else{
		$ip= $_SERVER["HTTP_X_FORWARDED_FOR"];
	}

	$res = explode(",",$ip);

    return trim($res[0]);
}

?>
