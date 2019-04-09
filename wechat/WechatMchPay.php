<?php
/**
 * @note: 微信企业付款
 * @author: jdzor <895947580@qq.com>
 * @date: 2019/3/29
 */

namespace app\common\extend\wechat;


/**
 * 接口文档：https://pay.weixin.qq.com/wiki/doc/api/tools/mch_pay.php?chapter=14_2
 *
 * 使用方法：
 *          $wechatMchPay = new WechatMchPay($config);
 *          $info = $wechatMchPay->payChange($param);
 *          $info = $wechatMchPay->queryChange($partnerTradeNo);
 *          $info['return_code'] == 'FAIL' && json_error($info['return_msg'], -2);
 *          $info['result_code'] == 'FAIL' && json_error($info['err_code_des'], -2);
 *
 * Class WechatMchPay
 * @package app\common\extend\wechat
 */
class WechatMchPay extends BaseWechat
{
    const TRANSFERS_URL = '/mmpaymkttransfers/promotion/transfers'; //企业付款到零钱

    const TRANSFERS_QUERY_URL = '/mmpaymkttransfers/gettransferinfo'; //查询企业付款到零钱

    private $checkName = 'NO_CHECK'; //NO_CHECK：不校验真实姓名  FORCE_CHECK：强校验真实姓名

    public function __construct($config)
    {
        parent::__construct($config);

        isset($config['sslcert_path']) && $this->sslcertPath = $config['sslcert_path'];
        isset($config['sslkey_path']) && $this->sslkeyPath = $config['sslkey_path'];
    }

    private function checkSsl()
    {
        $this->sslcertPath || json_error('缺少参数：sslcert_path'); //证书必传
        $this->sslkeyPath || json_error('缺少参数：sslkey_path');
    }

    //企业付款参数检测
    private function check($param, $method)
    {
        $this->checkSsl();

        if ($method == self::TRANSFERS_URL) {
            isset($param['partner_trade_no']) || json_error('缺少参数：partner_trade_no');
            isset($param['openid']) || json_error('缺少参数：openid');
            isset($param['amount']) || json_error('缺少参数：amount');
            isset($param['desc']) || json_error('缺少参数：desc');

            if ($this->checkName == 'FORCE_CHECK') {
                isset($param['re_user_name']) || json_error('缺少参数：re_user_name');
            }
        }
    }

    //企业付款到零钱
    public function payChange($param)
    {
        $this->check($param, self::TRANSFERS_URL);

        $data['mch_appid'] = $this->appId;
        $data['mchid'] = $this->mchId;
        $data['nonce_str'] = $this->getRandomString();
        $data['partner_trade_no'] = $param['partner_trade_no']; //商户订单号，需保持唯一性(只能是字母或者数字，不能包含有其他字符)
        $data['openid'] = $param['openid']; //商户appid下，某用户的openid
        $data['check_name'] = $this->checkName;
        $data['check_name'] == 'FORCE_CHECK' && $data['re_user_name'] = $param['re_user_name']; //收款用户真实姓名，如果check_name设置为FORCE_CHECK，则必填用户真实姓名
        $data['amount'] = $param['amount']; //企业付款金额，单位为分
        $data['desc'] = $param['desc']; //企业付款备注，必填。注意：备注中的敏感词会被转成字符*
        $data['spbill_create_ip'] = get_client_ip();
        $data['sign'] = $this->makeSign($data);

        $xml = $this->dataToXml($data);
        $response = $this->postXmlCurl($xml, self::TRANSFERS_URL, true);

        if (!$response) return false;
        $result = $this->xmlToArray($response);

        return $result;
    }

    //查询企业付款到零钱 查询企业付款API只支持查询30天内的订单，30天之前的订单请登录商户平台查询
    public function queryChange($partnerTradeNo)
    {
        $this->checkSsl();

        $data['appid'] = $this->appId;
        $data['mch_id'] = $this->mchId;
        $data['nonce_str'] = $this->getRandomString();
        $data['partner_trade_no'] = $partnerTradeNo; //商户订单号，需保持唯一性(只能是字母或者数字，不能包含有其他字符)
        $data['sign'] = $this->makeSign($data);

        $xml = $this->dataToXml($data);
        $response = $this->postXmlCurl($xml, self::TRANSFERS_QUERY_URL, true);

        if (!$response) return false;
        $result = $this->xmlToArray($response);

        return $result;
    }

}