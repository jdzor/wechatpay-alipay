<?php
/**
 * @note: 支付宝退款
 * @author: jdzor <895947580@qq.com>
 * @date: 2019/3/29
 */

namespace app\common\extend\alipay;


/**
 * 统一收单交易退款
 * 接口文档：https://docs.open.alipay.com/api_1/alipay.trade.refund/
 * 公共错误码：https://docs.open.alipay.com/common/105806
 *
 *  使用方法：
 *          $alipay = new AlipayRefund($config);
 *          $info = $alipay->doRefund($payData);
 *          $info = $alipay->queryRefund($payData);
 *          $info['code'] != 10000 && json_error((($info['sub_msg'] ?? '') . ' ' . $info['sub_code'] ?? ''), -2);
 *
 * Class AlipayRefund
 * @package app\common\extend\alipay
 */
class AlipayRefund extends BaseAlipay
{
    const REFUND_URL = 'alipay.trade.refund'; //申请退款

    const REFUND_QUERY_URL = 'alipay.trade.fastpay.refund.query'; //退款查询

    public function __construct($config)
    {
        parent::__construct($config);
    }

    //申请退款
    public function doRefund($param)
    {
        isset($param['out_trade_no']) || json_error('缺少参数：out_trade_no');
        isset($param['refund_amount']) || json_error('缺少参数：refund_amount');
        isset($param['refund_reason']) || json_error('缺少参数：refund_reason');
        isset($param['out_request_no']) || json_error('缺少参数：out_request_no');

        //请求参数
        $requestConfig = [
            'out_trade_no' => $param['out_trade_no'], //商户订单号
            'refund_amount' => $param['refund_amount'], //需要退款的金额，该金额不能大于订单金额,单位为元，支持两位小数
            'refund_reason' => $param['refund_reason'], //退款的原因说明，256字以内
            'out_request_no' => $param['out_request_no'], //标识一次退款请求，同一笔交易多次退款需要保证唯一，如需部分退款，则此参数必传
        ];

        $result = json_decode($this->commonRequest($requestConfig, self::REFUND_URL), true);
        return $result['alipay_trade_refund_response'];
    }

    //退款查询
    public function queryRefund($param)
    {
        isset($param['out_trade_no']) || json_error('缺少参数：out_trade_no');
        isset($param['out_request_no']) || json_error('缺少参数：out_request_no');

        //请求参数
        $requestConfig = [
            'out_trade_no' => $param['out_trade_no'], //订单支付时传入的商户订单号,和支付宝交易号不能同时为空
            'out_request_no' => $param['out_request_no'], //请求退款接口时，传入的退款请求号，如果在退款请求时未传入，则该值为创建交易时的外部交易号
        ];

        $result = json_decode($this->commonRequest($requestConfig, self::REFUND_QUERY_URL), true);
        return $result['alipay_trade_fastpay_refund_query_response'];
    }
}