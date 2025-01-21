<?php
/*
*
* 휘닉스api 테스트
* author Jason
* date 2022.02.04
*/

require_once ('/home/sparo.cc/phoenix_script/lib/phoenixApi.php');

// for($i=0; $i < 1000; $i++){
    $curl = curl_init();

    curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://oapi.phoenixhnr.co.kr:13051/oauth/token',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS =>'client_id=placem&client_secret=placem1234&grant_type=client_credentials',
    CURLOPT_HTTPHEADER => array(
        'Content-Type: text/plain'
    ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {

        // $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        // echo "ERR=".$httpCode;
        var_dump($err);
    } else {
        var_dump($response);
    }
// }

// for($i=0; $i < 100; $i++){
//     $curl = curl_init();

//     curl_setopt_array($curl, array(
//       CURLOPT_URL => 'http://gateway.sparo.cc/phoenix/info/22102119666',
//       CURLOPT_RETURNTRANSFER => true,
//       CURLOPT_ENCODING => '',
//       CURLOPT_MAXREDIRS => 10,
//       CURLOPT_TIMEOUT => 0,
//       CURLOPT_FOLLOWLOCATION => true,
//       CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//       CURLOPT_CUSTOMREQUEST => 'GET',
//       CURLOPT_HTTPHEADER => array(
//         'Content-Type: application/json',
//       ),
//     ));
    
//     $response = curl_exec($curl);
    
//     curl_close($curl);
//     var_dump(json_decode($response));
    
// }


// for($i=0; $i < 100; $i++){
//     $phoenixApi = new phoenixApi();
//     $get_access = $phoenixApi->get_access_token();

//     $get_token = json_decode($get_access);
//     var_dump($get_token);
//     // continue;
//     $fields = array(
//         'rprsSellNo'=>'22102119666'     //휘닉스 대표 판매 번호
//     );
//     $fields = json_encode($fields);

//     $order_responce = $phoenixApi->IF_SM_203_info_order($get_token->access_token, $fields);
//     var_dump($order_responce);
// }

