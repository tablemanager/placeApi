<?php
/**
 * 생성자: JAMES
 * 마지막 수정 : JAMES
 * 생성일: 2019-07-22
 * 수정일: 2019-07-05
 * 사용 유무: release (test, release,inactive,dev)
 * 파일 용도: 위즈돔(이버스) API 연동
 * 설명 : https://docs.google.com/document/d/17wfmtUD1OS7pe4z-b-_Uiqo7v-F94K3YkWkQZW2YdCg/edit
 */
header("Content-type:application/json");

$_random[1] = "일미집";
$_random[2] = "부대찌개";
$_random[3] = "제주상회";
$_random[4] = "정가집";
$_random[5] = "정가집";
$_random[6] = "놀부밥상";
$_random[7] = "논골집";
$_random[8] = "순대국";
$_random[9] = "놀부밥상";
$_random[10] = "기타";

$_temp = rand(1 , 10);

//echo $msg = "22";
//echo "\n";
//echo $_random[$_temp];
//$_output['body'] = "점심메뉴";
//$_output['connectColor'] =  "#FAC11B";
//$_output['connectInfo']['title'] = "오늘의 점심은";
//$_output['connectInfo']['description'] = $_random[$_temp];

$msgarr = array(
    "body"=>"점심메뉴",
    "connectColor"=>"#FAC11B",
    "connectInfo"=>array('title'=>'오늘의 점심은?','description'=>$_random[$_temp])
);

//echo json_encode($msgarr);

//echo json_encode($_output);
send_notice($msgarr);

function send_notice($msgarr){

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL =>"https://wh.jandi.com/connect-api/webhook/19526168/4a8876adf6c5af4657b61ebc921b8e69",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($msgarr),
        CURLOPT_HTTPHEADER => array(
            "Accept: application/vnd.tosslab.jandi-v2+json",
            "Cache-Control: no-cache",
            "Connection: keep-alive",
            "Content-Type: application/json",
            "Host: wh.jandi.com",
            "accept-encoding: gzip, deflate",
            "cache-control: no-cache"
        ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

}

//setBab("오늘의 점심은 " , $_random[$_temp] );
//
//public function setBab($title ,$msg )
//{
//    $msgarr = array("title"=>$title, "description"=>$msg);
//
//    $json_data = json_encode($msgarr);
//
////		echo $json_data;
//    $this->setHeader('Accept', 'application/vnd.tosslab.jandi-v2+json');
//
//    $this->post("http://extapi.sparo.cc/internal/sysnotice/REFUND" , $json_data);
//    return $this->api_complete();
//
//}


