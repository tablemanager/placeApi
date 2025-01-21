<?php
//namespace ticket;
defined('BASEPATH') OR exit('No direct script access allowed');

interface iTicket
{
    /**
     * 리스트 가져오기
     */
    public function getAPiList();

    /**
     * 상세 가져오기
     */
    public function getAPiDetail();

    /**
     * 예약하기
     */
    public function postAPiReservation();

    /**
     * 취소하기
     */
    public function setAPiCancel();

    /**
     * 검색하기
     */
    public function getAPiSearch();

    /**
     * 연동 완료 콜백
     */
    public  function _complete();
}