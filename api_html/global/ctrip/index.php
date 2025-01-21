<?php
$para = $_GET['val']; // URI 파라미터 
echo $apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

// 파라미터 
$itemreq = explode("/",$para);
	$requestJson = trim(file_get_contents('php://input'));


    $aes = new AES;
    $accountId = "36e1de6d3d722bab";
    $signKey = "3626d613b767bdc3976003fe59b4192d";
    $aesKey = "b415ea63fc5c2e44";
    $aesIv = "9b3f6cb685e4f1f7";

    $obj=json_decode($requestJson);
    $bodyStr=$obj->body;
    $reqTimeStr=$obj->header->requestTime;
    $version=$obj->header->version;
    $serviceName=$obj->header->serviceName;
    $signSource=$obj->header->sign;


    $bodyJsonStr=json_decode($aes->decrypt($bodyStr,$aesKey,$aesIv));
	print_r($bodyJsonStr);

    $signTarget=strtolower(md5($accountId.$serviceName.$reqTimeStr.$bodyStr.$version.$signKey));
    //echo($signTarget);

	switch($serviceName){

		case "VerifyOrder": // 주문확인 
		break;

		case "CreateOrder": // 주문생성
		break;

		case "CancelOrder": // 취소주문생성
		break;

		case "RefundOrder": // 환불주문생성
		break;

		case "QueryOrder": // 주문조회
		break;

		case "SendVoucher": // 바우쳐 발송
		break;
	}
    
    class AES{
    /**
     *
     * @param string $string
     * @param int $blocksize Blocksize
     * @return String
     */
    private function addPkcs7Padding($string, $blocksize = 16) {
        $len = strlen($string);
        $pad = $blocksize - ($len % $blocksize);
        $string .= str_repeat(chr($pad), $pad);
        return $string;
    }

    /**
     *
     * @param String *
     * @return String
     */
    private function stripPkcs7Padding($string){
        $slast = ord(substr($string, -1));
        $slastc = chr($slast);
        $pcheck = substr($string, -$slast);

        if(preg_match("/$slastc{".$slast."}/", $string)){
            $string = substr($string, 0, strlen($string)-$slast);
            return $string;
        } else {
            return false;
        }
    }

    private function decodeBytes($hex)
    {
        $str = '';
        for($i=0;$i<strlen($hex);$i+=2){
        	$tmpValue = (((ord($hex[$i]) - ord('a')) & 0xf ) <<4) + ((ord($hex[$i+1])- ord('a')) & 0xf);
        	$str .= chr($tmpValue);
        }
        return  $str;
    }

    private function encodeBytes($string)
    {
        $str = '';
        for($i=0;$i<strlen($string);$i++)
        {
            $tmpValue = ord($string[$i]);
            $ch = ($tmpValue >> 4 & 0xf) + ord('a');
            $str .= chr($ch);
            $ch = ($tmpValue & 0xf) + ord('a');
            $str .= chr($ch);
        }
        return $str;
    }

    /**
     *
     * @param String $encryptedText
     * @param String $secretKey
     * @param String $vector
     * @return String
     */
    function decrypt($encryptedText, $secretKey, $vector) {
        $str = $this->decodeBytes($encryptedText);
        return $this->stripPkcs7Padding(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $secretKey, $str, MCRYPT_MODE_CBC, $vector));
    }

	/**
     *
     * @param String $encryptedText
     * @param String $secretKey
     * @param String $vector
     * @return String
     */
    function encrypt($decData, $secretKey, $vector) {
        $base = (mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $secretKey,$this->addPkcs7Padding($decData,16) , MCRYPT_MODE_CBC, $vector));
        return $this->encodeBytes($base);
    }
}
?>