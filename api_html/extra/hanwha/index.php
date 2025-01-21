<?php
/**
 * Created by PhpStorm.
 * User: Michael
 * Date: 2019-03-04
 * Time: ���� 1:21
 */

//require_once('/home/sparo.cc/Library/lockProcess.php');
/**
 * ���� ���°� ��ȸ
 * ���̹� ä���� �ֹ���ȣ�� ���� �ֹ���ȣ�� �ٸ���.
 * ���� ���̺�� �ѹ�
 * �ֹ� ���̺�� �� �ѹ� ó��
 * */

require_once('/home/sparo.cc/Library/M_ConnSparo2.php');
require_once('/home/sparo.cc/Library/messagelib.php');
include '/home/sparo.cc/hanwha_script/hanwha/class/class.hanwha.php';
include '/home/sparo.cc/hanwha_script/lib/class/class.lib.common.php';
require '/home/sparo.cc/hanwha_script/hanwha/class/hanwhamodel.php';

$_coupon = $_GET['coupon']; // URI �Ķ����

if (empty($_coupon)){
    $json_result['status'] = "00";
} else {

    $m_connSparo2 = new M_ConnSparo2();
    $hanwhamodel = new hanwhamodel();
    $hanwha = new \Hanwha\Hanwha();

    $_data = $hanwhamodel->selectHanwhaCouponUseInfoListOneCpn($_coupon);

    if (empty($_data['REPR_CPON_SEQ'])){
        $json_result['status'] = "01";
    } else {
        $hanwha->setCORP_CD($_data['CORP_CD']);
        $hanwha->setCONT_NO($_data['CONT_NO']);

        $_data_result =  $hanwha->searchDs($_data['REPR_CPON_SEQ'], $_coupon , $_data['ISSUE_DATE']);

        if (empty($_data_result['Data']['ds_result'][0]['REPR_CPON_STAT_CD'])){
            $json_result['status'] = "00";
        } else {
            $json_result['status'] = $_data_result['Data']['ds_result'][0]['REPR_CPON_STAT_CD'];
        }
    }

    $res = json_encode($json_result);
}

echo $res;
exit;






