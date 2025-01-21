<?php 
if( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 암호화 라이브러리
 * @author mhlee
 * @since 
 *
 */
class Custom_Encryption
{
    private $CI = null;
    private static $_key = "sn!@#$%^&*()";

    public function __construct()
    {
        $this->CI = &get_instance();
    }

	public function encrypt($data) 
	{
		$data = (string) $data;

		for ($i=0;$i<strlen($data);$i++) {
            @$encrypt .= $data[$i] ^ self::$_key[$i % strlen(self::$_key)];
		}

        return self::_encodeHexadecimal(@$encrypt);
    }

    public function decrypt($crypt) 
	{
		$crypt = self::_decodeHexadecimal($crypt);

        for ($i=0;$i<strlen($crypt);$i++) {
            @$data .= $crypt[$i] ^ self::$_key[$i % strlen(self::$_key)];
		}
        return @$data;
    }

    private function _encodeHexadecimal($data) 
	{
        $data = (string) $data;

        for ($i=0;$i<strlen($data);$i++) {
            @$hexcrypt .= str_pad(dechex(ord($data[$i])), 2, 0, STR_PAD_LEFT);
		}
        return @$hexcrypt;
    }

    private function _decodeHexadecimal($hexcrypt) 
	{
        $hexcrypt = (string) $hexcrypt;

        for ($i=0;$i<strlen($hexcrypt);$i+=2) {
            @$data .= chr(hexdec(substr($hexcrypt, $i, 2)));
		}
        return @$data;
    }
}
