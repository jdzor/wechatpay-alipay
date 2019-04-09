<?php
/**
 * @note: 微信退款
 * @author: jdzor <895947580@qq.com>
 * @date: 2019/3/28
 */

namespace app\common\extend\wechat;


/**
 * 关于微信退款的说明
 * 1.微信退款要求必传证书，需要到https://pay.weixin.qq.com 账户中心->账户设置->API安全->下载证书，证书路径在第119行和122行修改
 * 2.参考 ：https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=9_4
 *
 * 使用方法：
 *          $wechatRefund = new WechatRefund($config);
 *          $info = $wechatRefund->doRefund($param);
 *          $info = $wechatRefund->queryRefund($outTradeNo);
 *          $info['return_code'] == 'FAIL' && json_error($info['return_msg'], -2);
 *          $info['result_code'] == 'FAIL' && json_error($info['err_code_des'], -2);
 *
 * Class WechatRefund
 * @package app\common\extend\wechat
 */
class WechatRefund extends BaseWechat
{
    const REFUND_URL = '/secapi/pay/refund'; //申请退款

    const REFUND_QUERY_URL = '/pay/refundquery'; //退款查询

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

    //退款参数检测
    private function check($param)
    {
        $this->checkSsl();

        isset($param['out_trade_no']) || json_error('缺少参数：out_trade_no');
        isset($param['out_refund_no']) || json_error('缺少参数：out_refund_no');
        isset($param['total_fee']) || json_error('缺少参数：total_fee');
        isset($param['refund_fee']) || json_error('缺少参数：refund_fee');
        isset($param['refund_desc']) || json_error('缺少参数：refund_desc');
        isset($param['notify_url']) || json_error('缺少参数：notify_url');

        $this->notifyUrl = $param['notify_url'];
    }

    //申请退款
    public function doRefund($param)
    {
        $this->check($param);

        $data['appid'] = $this->appId;
        $data['mch_id'] = $this->mchId;
        $data['notify_url'] = $this->notifyUrl;
        $data['out_trade_no'] = $param['out_trade_no']; //商户系统内部订单号
        $data['out_refund_no'] = $param['out_refund_no']; //商户系统内部的退款单号
        $data['total_fee'] = $param['total_fee']; //订单总金额，单位为分，只能为整数
        $data['refund_fee'] = $param['refund_fee']; //退款总金额，订单总金额，单位为分，只能为整数
        $data['refund_desc'] = $param['refund_desc']; //若商户传入，会在下发给用户的退款消息中体现退款原因，注意：若订单退款金额≤1元且属于部分退款，则不会在退款消息中体现退款原因
        $data['nonce_str'] = $this->getRandomString();
        $data['sign_type'] = $this->signType;
        $data['sign'] = $this->makeSign($data);

        $xml = $this->dataToXml($data);
        $response = $this->postXmlCurl($xml, self::REFUND_URL, true);

        if (!$response) return false;
        $result = $this->xmlToArray($response);

        return $result;
    }

    //退款查询
    public function queryRefund($outTradeNo, $offset = 0)
    {
        $data['appid'] = $this->appId;
        $data['mch_id'] = $this->mchId;
        $data['nonce_str'] = $this->getRandomString();
        $data['out_trade_no'] = $outTradeNo;
        $data['sign_type'] = $this->signType;
        $offset > 10 && $data['offset'] = $offset; //偏移量，当部分退款次数超过10次时可使用，表示返回的查询结果从这个偏移量开始取记录
        $data['sign'] = $this->makeSign($data);

        $xml = $this->dataToXml($data);
        $response = $this->postXmlCurl($xml, self::REFUND_QUERY_URL);

        if (!$response) return false;
        $result = $this->xmlToArray($response);

        return $result;
    }
}