<?php
/**
 * Created by PhpStorm.
 * User: BBUNA_SYSTEM
 * Date: 2018-12-04
 * Time: 오후 3:42
 */

class phoenixApi
{
    /*
 *
 * ---- 휘닉스 개발 서버 정보 ----
clientId : placem
clientSecret : placem1234
거래처코드 : 10000222
port : 13051
host : dev-oapi.phoenixhnr.co.kr
aedc463fd7565871604bd067a9c04d40
---- 휘닉스 운영 서버 정보 ----

clientId : placem
clientSecret : placem1234
거래처코드 : 10000222
port : 13051
host : oapi.phoenixhnr.co.kr
*/


    private $client_id     = "placem";
    private $client_secret = "placem1234";
    private $agncyClientCd = "10000222";    //대매사코드
    private $port = "13051";
    private $host = "https://oapi.phoenixhnr.co.kr";

    //private $host = "https://dev-oapi.phoenixhnr.co.kr";
    private $host_test = "https://dev-oapi.phoenixhnr.co.kr";

    private $url_token  = "/oauth/token";// 	POST	토큰 발행
    private $url_EXTPKGCOM101  = "/api/package/EXTPKGCOM101";// 	POST	패키지목록조회
    private $url_EXTPKGCOM102  = "/api/package/EXTPKGCOM102";// 	POST	패키지구매
    private $url_EXTPKGCOM111  = "/api/package/EXTPKGCOM111";// 	POST	패키지구매  EXTPKGCOM102 20200109 2시 변경예정
    private $url_EXTPKGCOM103  = "/api/package/EXTPKGCOM103";// 	POST	패키지구매취소
    private $url_EXTPKGCOM105  = "/api/package/EXTPKGCOM105";// 	POST	정산 정보 조회
    private $url_EXTPKGCOM106  = "/api/package/EXTPKGCOM106";// 	POST	상품사용유무 조회
    private $url_EXTPKGCOM208  = "/api/package/EXTPKGCOM208";// 	POST    재구매 주회
//    private $url_SKCMNIFS027  = "/api/package/SKCMNIFS027";  // 	POST    정보등록 OR 발권 여부 조회
    private $url_SKCMNIFS027  = "/api/package/EXTPKGCOM207";  // 	POST    정보등록 OR 발권 여부 조회
//    private $url_SKCMNIFSI116  = "/api/package/SMPKGPKGI116";  // 	POST    정보등록 OR 발권 여부 조회
    private $url_SKCMNIFSI116  = "/api/package/EXTPKGPKGI116";  // 	POST    정보등록 OR 발권 여부 조회

    private $url_EXTPKGCOM116  = "/api/package/EXTPKGCOM116";  // 	패키지 취소

    private $url_EXTPKGCOM219  = "/api/package/EXTPKGCOM219";// 	POST	스키락카 구매정보 및 고객정보 기준 주문생성

    public function __construct($is_test = null)
    {
        if ($is_test == 'y') {
            $this->host = 'https://dev-oapi.phoenixhnr.co.kr';
        }
    }

    function get_access_token(){

        $token_url = "{$this->host}:{$this->port}{$this->url_token}";

        $fields = array(
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type'   => 'client_credentials'
        );

        $postvars = '';
        foreach($fields as $key=>$value) {
            $postvars .= $key . "=" . $value . "&";
        }

		$_result = $this->curl_post($token_url,$postvars);

        return $_result;
    }

    function get_access_token_test(){

        $token_url = "{$this->host_test}:{$this->port}{$this->url_token}";

        $fields = array(
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type'   => 'client_credentials'
        );
        $postvars = '';
        foreach($fields as $key=>$value) {
            $postvars .= $key . "=" . $value . "&";
        }
        return $this->curl_post($token_url,$postvars);
    }

    //상품리스트 불러오기$url_SKCMNIFS027
    function IF_SM_027_get_publication($token , $_sopmalCd ,$_sopmalOrdrNo ){
//        $fields = array(
//            'sopmalCd' => $_sopmalCd,
//            'sopmalOrdrNo' => $_sopmalOrdrNo
//        );
        $fields = array(
            'agncyClientCd' => $this->agncyClientCd,
            'sopmalCd' => $_sopmalCd,
            'sopmalOrdrNo' => $_sopmalOrdrNo
        );



//        echo $this->host.":".$this->port.$this->url_SKCMNIFS027."<br>";

        $fields = json_encode($fields);

//        echo $fields;
//        return $this->curl_post_json($this->host_test.":".$this->port.$this->url_SKCMNIFS027,$fields,$token);
        return $this->curl_post_json($this->host.":".$this->port.$this->url_SKCMNIFS027,$fields,$token);
    }



    //상품리스트 불러오기
    function IF_SM_201_get_paglist($token, $sellFromDate = null, $sellToDate = null, $pkgCd = null){
        $fields = array(
            'agncyClientCd' => $this->agncyClientCd,
            'sellFromDate' => date("Ymd")
        );

        if ($sellFromDate && $sellToDate) {
            $fields['sellFromDate'] = $sellFromDate;
            $fields['sellToDate'] = $sellToDate;
        }
        if ($pkgCd) {
            $fields['pkgCd'] = $pkgCd;
        }

        $fields = json_encode($fields);
//        echo $fields;
        return $this->curl_post_json($this->host.":".$this->port.$this->url_EXTPKGCOM101,$fields,$token);
    }
    //상품리스트 불러오기 테스트
    function IF_SM_201_get_paglist_test($token){
        $fields = array(
            'agncyClientCd' => $this->agncyClientCd,
            'sellFromDate' => date("Ymd")
        );
        $fields = json_encode($fields);
//        return $this->curl_post_json($this->host.":".$this->port.$this->url_EXTPKGCOM101,$fields,$token);
        return $this->curl_post_json("https://dev-oapi.phoenixhnr.co.kr".":".$this->port.$this->url_EXTPKGCOM101,$fields,$token);
    }

    //패키지 주문 등록
    function IF_SM_202_set_order($token,$fields){
        return $this->curl_post_json($this->host.":".$this->port.$this->url_EXTPKGCOM111,$fields,$token);
    }
    //패키지 주문 등록
    function IF_SM_202_set_order_season($token,$fields){
        return $this->curl_post_json($this->host.":".$this->port.$this->url_EXTPKGCOM102,$fields,$token);
    }
    //패키지 주문 등록
    function IF_SM_202_set_order_test($token,$fields){
        return $this->curl_post_json($this->host_test.":".$this->port.$this->url_EXTPKGCOM111,$fields,$token);
    }

    //주문 정보 조회
    function IF_SM_203_info_order($token,$fields){
        return $this->curl_post_json($this->host.":".$this->port.$this->url_EXTPKGCOM106,$fields,$token);
    }

    //주문 취소
    function IF_SM_204_cancel_order($token,$fields){
        return $this->curl_post_json($this->host.":".$this->port.$this->url_EXTPKGCOM103,$fields,$token);
    }

    //패키지 환불 주문 취소
    function EXTPKGCOM116($token,$fields){
        return $this->curl_post_json($this->host.":".$this->port.$this->url_EXTPKGCOM116,$fields,$token);
    }

    //재구매 조회
    function EXTPKGCOM208($token,$fields){
        return $this->curl_post_json($this->host.":".$this->port.$this->url_EXTPKGCOM208,$fields,$token);
    }

    //JSON POST (HEADER에 TOKEN 사용)
    function curl_post_json($url,$json,$token){
        //echo "{$url}\n{$json}\n{$token}\n";
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER,
            array('Content-Type: application/json',
                'Content-Length: ' . strlen($json),
                'Authorization: Bearer ' . $token));
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json);

        $json_response = curl_exec($curl);
        /*echo "\njson_response=";
        var_dump($json_response);*/
        $status = $info = curl_getinfo($curl);
        $err = curl_error($curl);
//        print_r( $status);
        if ($err) {
//            echo "\nERR=";
//            var_dump($err);
        }


        if ( $status != 201 ) {
            //die("Error: call to URL $url failed with status $status, response $json_response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
            //var_dump($status);
        }
        curl_close($curl);
        $response = json_decode($json_response, true);
        return $response;
    }



    function curl_post($url,$postvars){
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $postvars
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {

            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            echo "ERR=".$httpCode;
            var_dump($err);
            return $err;
        } else {
            return $response;
        }
    }

    // 20221025 tony 락커 고객정보 등록
    function IF_SM_220_locker_reg($token, $fields){
        return $this->curl_post_json($this->host.":".$this->port.$this->url_EXTPKGCOM219, $fields, $token);
    }

    // 20221027 tony
    // 채널아이디로 휘닉스 채널코드 찾기
    // 휘닉스 패키지 리스트에 있는 채널코드가 변경되면 여기도 변경해 줘야 한다.
    // 채널코드만 좀 주지... 패키지에다가 같이 주니깐 어색하네..
    function getSellClientCdbyChId($ch_id) {
        switch ($ch_id) {
            case '129': $return = '10000192'; break;  // 0G마켓
            case '141': $return = '10000193'; break;  // 0옥션
            case '2984': $return = '10000214'; break;  // 0네이버
            //case '3384': $return = '10001703'; break;  // 0여기어때 
            case '3384': $return = '10000224'; break;  // 0여기어때(2022908 휘닉스코드 변경)
            //case '128': $return = '10000191'; break;  // 011번가
            case '128': $return = '10000438'; break;  // 011번가(2022908 휘닉스코드 변경)
            case '142': $return = '10000194'; break;  // 0위메프
            case '154': $return = '10000196'; break;  // 0티몬
            //case '150': $return = '10000195'; break;  // 0쿠팡
            case '150': $return = '10001995'; break;  // 0쿠팡(2022908 휘닉스코드 변경)
            //case '3809': $return = '10001832'; break;  // 0카카오톡스토어(카카오쇼핑)
            case '3809': $return = '10002107'; break;  // 0카카오톡스토어(카카오쇼핑)
                                                       // (2022908 휘닉스코드 변경)

            case '2548': $return = '10001994'; break;  // CJ(CJ온스타일 20220908 추가)
            case '3698': $return = '10000204'; break;  // 0SSG
            //case '168': $return = '10000413'; break;  // 0GS몰
            case '168': $return = '10001997'; break;  // 0GS몰(GS SHOP)(2022908 휘닉스코드 변경)
            case '3629': $return = '10001707'; break;  // 0KLOOK
            case '2901': $return = '10001704'; break;  // 0KKDAY
            case '2918': $return = '10001623'; break;  // 0와그
            case '3116': $return = '10001613'; break;  // 0마이리얼트립
            case '3500': $return = '10001010'; break;  // 웅진놀이의발견(웅진북클럽(놀이의발견))

            case '3818': $return = '10002189'; break;  // 네이버 스마트스토어 - 20220908 추가
            case '3885': $return = '10002190'; break;  // 마켓컬리 - 20220908 추가
            case '3810': $return = '10000430'; break;  // SKT 딜 - 20220908 추가
            case '3331': $return = '10000409'; break;  // 0야놀자
            case '3694': $return = '10001621'; break;  // 0트립닷컴

    /*
            case '2744': $return = '10000404'; break;  // AK몰
            case '2668': $return = '10000408'; break;  // 롯데I몰
            case '3299': $return = '10000700'; break;  // 이지웰페어
            case '3299': $return = '10000895'; break;  // 베네피아
            case '2771': $return = '10001146'; break;  // H몰
            case '2613': $return = '10001614'; break;  // 티켓수다
            case '3600': $return = '10001615'; break;  // 스마트콘
            case '3704': $return = '10001617'; break;  // 데이트팝
            case '3702': $return = '10001618'; break;  // 아이와트립
            case '3706': $return = '10001620'; break;  // 비프리투어
            case '3709': $return = '10001622'; break;  // 이네이블
            case '3807': $return = '10001833'; break;  // 카카오톡선물하기
            case '2094': $return = '10001834'; break;  // CJ몰
            case '3801': $return = '10001835'; break;  // 니모투어
    */
            default: $return = '10000222';  // 플레이스엠
        }

        return $return;
    }


}
