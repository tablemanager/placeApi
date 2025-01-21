<?php
/**
 * Created by PhpStorm.
 * User: Connor
 * Date: 2019-01-11
 * Time: 오전 10:03
 */

defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! function_exists('array_search_multidim'))
{
    /**
     * @param (array) Main Array
     * @param (string) search name
     * @param (string) search value
     * @return (array)index
     */
    function array_search_multidim($array, $column, $key)
    {
        return (array_search($key, array_column($array, $column)));
    }
}