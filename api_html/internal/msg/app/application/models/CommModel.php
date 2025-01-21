<?php
/**
 * 공통 모델
 * @author larry
 * @since 2020.08.18
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class CommModel extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->rds = $this->load->database('rds', TRUE);        // rds DB
    }
    
    /**
     * API 승인 키
	 * @author larry
	 * @since 2020.08.18
	 * @table CMSSMS.AUTH_API
     * @param $code 키 값
     * @return array
     */
    public function getAuth($code='')
    {
		$arrData = array(1);
		$sql = "
			SELECT 
				* 
			FROM
				CMSSMS.AUTH_API a
			WHERE
				1 = ?
		";
		if($code){
			$sql.=" AND CODE = ?";
			array_push($arrData, $code);
			$result = $this->rds->query($sql, $arrData)->row_array();
		}else{
			$result = $this->rds->query($sql, $arrData)->result_array();
		}

		//$this->CI->commLib->sql($sql, '', $arrData); //test                
        if (empty($result) === true) {
            return array();
        }        
        return $result;
	}

	 /**
     * API 사용자 저장
	 * @author larry
	 * @since 2020.08.20
	 * @table CMSSMS.AUTH_API
     * @param $idx, $name, $profile_id, $code
     * @return boolean
     */
    public function setAuth($idx, $name, $profile_id, $code)
    {
        if (empty($name) === true || empty($profile_id) === true || empty($code) === true) {            
			return false;
        }

		if(empty($idx)){
			$sql = "
				INSERT INTO CMSSMS.AUTH_API (
					NAME,
					PROFILE_ID,
					CODE
				)
				VALUES (
					?, ?, ?
				)
			";
			$aData = array($name, $profile_id, $code);
		}else{
			$sql = "
				UPDATE 
					CMSSMS.AUTH_API 
				SET
					NAME = ?,
					PROFILE_ID = ?,
					CODE = ?
				WHERE
					IDX = ?
			";
			$aData = array($name, $profile_id, $code, $idx);
		}
		//$this->CI->commLib->sql($sql, '', $aData); //test
        $this->rds->query($sql, $aData);
        return true;
	}

	/**
     * 승인 IP
	 * @author larry
	 * @since 2020.08.18
	 * @table CMSSMS.AUTH_API
     * @param $IDX 값
     * @return array
     */
    public function getIP($idx='', $ip='')
    {
		$arrData = array(1);
		$sql = "
			SELECT 
				* 
			FROM
				CMSSMS.AUTH_IP a
			WHERE
				1 = ?
		";
		if($idx || $ip){
			if($idx){
				$sql.=" AND IDX = ?";
				array_push($arrData, $idx);
			}else if($ip){
				$sql.=" AND ip = ?";
				array_push($arrData, $ip);
			}
			$result = $this->rds->query($sql, $arrData)->row_array();
		}else{
			$result = $this->rds->query($sql, $arrData)->result_array();
		}

		//$this->CI->commLib->sql($sql, '', $arrData); //test                
        if (empty($result) === true) {
            return array();
        }        
        return $result;
	}

	 /**
     * 승인 IP 사용자 저장
	 * @author larry
	 * @since 2020.08.26
	 * @table CMSSMS.AUTH_IP
     * @param $idx, $name, $ip
     * @return boolean
     */
    public function setIP($idx, $name, $ip)
    {
        if (empty($name) === true || empty($ip) === true) {            
			return false;
        }

		if(empty($idx)){
			$sql = "
				INSERT INTO CMSSMS.AUTH_IP (
					NAME,
					IP
				)
				VALUES (
					?, ?
				)
			";
			$aData = array($name, $ip);
		}else{
			$sql = "
				UPDATE 
					CMSSMS.AUTH_IP 
				SET
					NAME = ?,
					IP = ?
				WHERE
					IDX = ?
			";
			$aData = array($name, $ip, $idx);
		}
		//$this->CI->commLib->sql($sql, '', $aData); //test
        $this->rds->query($sql, $aData);
        return true;
	}


	

}