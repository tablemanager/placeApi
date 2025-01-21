<?php
defined('BASEPATH') OR exit('No direct script access allowed');
//namespace Restserver\Libraries\util;

abstract class Dto_util
{

    private $data_list = array();
//    abstract protected function &settingInstance();
    abstract public function &settingInstance();

    /**
     * @return array
     */
    public function getDataList()
    {
        return $this->data_list;
    }

    /**
     * @param array $data_list
     */
    public function setDataList($data_list)
    {
        $this->data_list = $data_list;
    }

}