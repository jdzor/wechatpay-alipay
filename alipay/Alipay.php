<?php
/**
 * @note: 支付宝 APP支付、手机网站支付、当面付
 * @author: jdzor <895947580@qq.com>
 * @date: 2019/3/14
 */

namespace app\common\extend\alipay;

/**
 * 统一支付
 * 接口文档：https://docs.open.alipay.com/
 * 公共错误码：https://docs.open.alipay.com/common/105806
 * 说明：
 * 1.当面付不用设置应用网关和授权回调地址，只需要设置应用公钥
 *
 * 使用方法：
 * 1.当面付
 *          $alipay = new Alipay($config);
 *          $info = $alipay->placeQrcode($payData);
 *          $info['code'] != 10000 && json_error((($info['sub_msg'] ?? '') . ' ' . $info['sub_code'] ?? ''), -2);
 *          $info['code_url']  通过qrcode生成二维码后，用户扫码即可
 * 2.app支付
 *          $alipay = new Alipay($config);
 *          $info = $alipay->placeApp($payData);
 *          将结果$info传给移动端即可，$info是将参数http_build_query()处理后生成的字符串
 * 3.手机网站支付
 *          $alipay = new Alipay($config);
 *          $info = $alipay->placeWap($payData);
 *          $info['html_form']  将结果通过ajax方式写入html body中即可
 * 4.电脑网站支付
 *          $alipay = new Alipay($config);
 *          $info = $alipay->placePage($payData);
 *          $info['html_form']  将结果通过ajax方式写入html body中即可
 *
 * Class AlipayQrcode
 * @package app\common\extend\alipay
 */
class Alipay extends BaseAlipay
{
    const PRECREATE_URL = 'alipay.trade.precreate'; //当面付 扫码

    const APP_PAY_URL = 'alipay.trade.app.pay'; //App支付

    const WAP_PAY_URL = 'alipay.trade.wap.pay'; //手机网站支付

    const PAGE_PAY_URL = 'alipay.trade.page.pay'; //电脑网站支付

    const CLOSE_URL = 'alipay.trade.close'; //关闭订单

    const QUERY_RUL = 'alipay.trade.query'; //查询订单

    public function __construct($config)
    {
        parent::__construct($config);
    }

    //下单参数检测
    private function check($param)
    {
        isset($param['order_no']) || json_error('缺少参数：order_no');
        isset($param['order_price']) || json_error('缺少参数：order_price');
        isset($param['subject']) || json_error('缺少参数：subject');
        isset($param['notify_url']) || json_error('缺少参数：notify_url');

        $this->notifyUrl = $param['notify_url'];
        isset($param['return_url']) && $this->returnUrl = $param['return_url'];;
    }

    //下订单 当面付
    public function placeQrcode($param)
    {
        $this->check($param);
        isset($param['expire']) || json_error('缺少参数：expire');

        //请求参数
        $requestConfig = [
            'out_trade_no' => $param['order_no'], //商户订单号
            'total_amount' => $param['order_price'], //订单总金额，整形，此处单位为元，精确到小数点后2位，不能超过1亿元
            'subject' => $param['subject'], //订单标题，粗略描述用户的支付目的
            'qr_code_timeout_express' => floor($param['expire'] / 60) . 'm', //当面付 二维码过期时间
            'timeout_express' => $this->timeoutExpress //交易创建后才生效
        ];

        $result = json_decode($this->commonRequest($requestConfig, self::PRECREATE_URL), true);
        return $result['alipay_trade_precreate_response'];
    }

    //下订单 APP支付
    public function placeApp($param)
    {
        $this->check($param);

        //请求参数
        $requestConfig = [
            'out_trade_no' => $param['order_no'], //商户订单号
            'total_amount' => $param['order_price'], //订单总金额，整形，此处单位为元，精确到小数点后2位，不能超过1亿元
            'subject' => $param['subject'], //订单标题，粗略描述用户的支付目的
            'timeout_express' => $this->timeoutExpress //交易创建后才生效
        ];

        $result = $this->commonRequest($requestConfig, self::APP_PAY_URL);
        return ['alipay' => $result];
    }

    //下订单 手机网站支付
    public function placeWap($param)
    {
        $this->check($param);

        //请求参数
        $requestConfig = [
            'out_trade_no' => $param['order_no'], //商户订单号
            'total_amount' => $param['order_price'], //订单总金额，整形，此处单位为元，精确到小数点后2位，不能超过1亿元
            'subject' => $param['subject'], //订单标题，粗略描述用户的支付目的
            'timeout_express' => $this->timeoutExpress, //交易创建后才生效
            'product_code' => 'QUICK_WAP_WAY', //销售产品码，商家和支付宝签约的产品码，如：QUICK_WAP_WAY
        ];

        $result = $this->commonRequest($requestConfig, self::WAP_PAY_URL);
        return $this->buildRequestForm($result);
    }

    //下订单 电脑网站支付
    public function placePage($param)
    {
        $this->check($param);

        //请求参数
        $requestConfig = [
            'out_trade_no' => $param['order_no'], //商户订单号
            'total_amount' => $param['order_price'], //订单总金额，整形，此处单位为元，精确到小数点后2位，不能超过1亿元
            'subject' => $param['subject'], //订单标题，粗略描述用户的支付目的
            'timeout_express' => $this->timeoutExpress, //交易创建后才生效
            'product_code' => 'FAST_INSTANT_TRADE_PAY', //销售产品码，商家和支付宝签约的产品码，注：目前仅支持 FAST_INSTANT_TRADE_PAY
        ];

        $result = $this->commonRequest($requestConfig, self::PAGE_PAY_URL);
        return $this->buildRequestForm($result);
    }

    /**
     * 建立请求，以表单HTML形式构造（默认）
     * @param array $param 请求参数数组
     * @return array 提交表单HTML文本
     */
    private function buildRequestForm($param)
    {
        $html = "<form id='alipaysubmit' name='alipaysubmit' action='" . self::GATEWAY_URL
            . "?charset=" . $this->charset . "' method='POST'>";
        foreach ($param as $key => $val) {
            if (false === $this->checkEmpty($val)) {
                $val = str_replace("'", "&apos;", $val);
                $html .= "<input type='hidden' name='" . $key . "' value='" . $val . "'/>";
            }
        }

        //submit按钮控件请不要含有name属性
        $html = $html . "<input type='submit' value='ok' style='display:none;'></form>";
        $html = $html . "<script>document.forms['alipaysubmit'].submit();</script>";

        return ['html_form' => $html];
    }

    /**
     * 查询订单
     * 文档：https://openclub.alipay.com/read.php?tid=5407&fid=72
     * 当面付：生成二维码使用支付宝钱包扫码唤起收银台后订单创建，支付宝未扫码前查询显示交易不存在 ACQ.TRADE_NOT_EXIST
     * 支付宝钱包支付（手机网站）：用户点击支付，唤起支付宝收银台后，输入正确完整的支付密码后订单创建
     * 支付宝钱包支付（APP支付）：用户点击支付，唤起支付宝收银台后，输入正确完整的支付密码后订单创建
     * @param $outTradeNo
     * @return mixed
     */
    public function queryOrder($outTradeNo)
    {
        //请求参数
        $requestConfig = array(
            'out_trade_no' => $outTradeNo //订单支付时传入的商户订单号，和支付宝交易号不能同时为空。 trade_no,out_trade_no 如果同时存在优先取trade_no
        );

        $result = json_decode($this->commonRequest($requestConfig, self::QUERY_RUL), true);
        return $result['alipay_trade_query_response'];
    }

    /**
     * 关闭订单
     * 文档：https://openclub.alipay.com/read.php?tid=5407&fid=72
     * 当面付：生成二维码使用支付宝钱包扫码唤起收银台后订单创建，支付宝未扫码前查询显示交易不存在 ACQ.TRADE_NOT_EXIST
     * 支付宝钱包支付（手机网站）：用户点击支付，唤起支付宝收银台后，输入正确完整的支付密码后订单创建
     * 支付宝钱包支付（APP支付）：用户点击支付，唤起支付宝收银台后，输入正确完整的支付密码后订单创建
     * @param $outTradeNo
     * @return mixed
     */
    public function closeOrder($outTradeNo)
    {
        //请求参数
        $requestConfig = array(
            'out_trade_no' => $outTradeNo //订单支付时传入的商户订单号，和支付宝交易号不能同时为空。 trade_no,out_trade_no 如果同时存在优先取trade_no
        );

        $result = json_decode($this->commonRequest($requestConfig, self::CLOSE_URL), true);
        return $result['alipay_trade_close_response'];
    }

    //异步通知 验签
    public function rsaCheck($param)
    {
        return parent::rsaCheck($param);
    }

    /**
     *程序执行完后必须打印输出“success”（不包含引号）。如果商户反馈给支付宝的字符不是success这7个字符，支付宝服务器会不断重发通知，直到超过24小时22分钟。
     * 一般情况下，25小时以内完成8次通知（通知的间隔频率一般是：4m,10m,10m,1h,2h,6h,15h）
     */
    public function replyNotify()
    {
        echo 'success';
    }

}