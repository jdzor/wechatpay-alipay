<?php
/**
 * @note: 微信支付 App支付、小程序支付、扫码支付、H5支付
 * @author: jdzor <895947580@qq.com>
 * @date: 2019/1/18
 */

namespace app\common\extend\wechat;


/**
 * 文档地址:  https://pay.weixin.qq.com/wiki/doc/api/index.html
 * 预下单方法：
 *              $wechatPay = new WechatPay($config);
 *              $info = $wechatPay->unifiedOrder($payData);
 *              $info['return_code'] == 'FAIL' && json_error($info['return_msg'], -2);
 *              $info['result_code'] == 'FAIL' && json_error($info['err_code_des'], -2);
 *
 * 1.扫码支付：    $info['code_url']  此url用于生成支付二维码，然后提供给用户进行扫码支付。注意：code_url的值并非固定，使用时按照URL格式转成二维码即可
 * 2.H5支付：      $info['mweb_url']  mweb_url为拉起微信支付收银台的中间页面，可通过访问该url来拉起微信客户端，完成支付,mweb_url的有效期为5分钟
 *                                 可拼接跳转链接：  $info['mweb_url'].'&redirect_url='.urlencode($returnUrl);
 * 3.App支付：     $wechatPay->getAppParam($info['prepay_id']);  将结果返回给App端即可
 * 4.Jsapi支付：  $wechatPay->getJsapiParam($info['prepay_id']);  将结果返回给微信内H5支付或小程序端即可
 *
 * Class WechatPay
 * @package app\common\extend\wechat
 */
class WechatPay extends BaseWechat
{
    const UNIFIED_ORDER_URL = "/pay/unifiedorder"; //下单地址

    const ORDER_QUERY_URL = "/pay/orderquery"; //查询订单

    const CLOSE_ORDER_URL = "/pay/closeorder"; //关闭订单

    public static $tradeMap = [
        'jsapi' => 'JSAPI', //小程序支付、公众号支付（微信内H5环境）
        'app' => 'APP', //APP支付
        'native' => 'NATIVE', //扫码支付
        'mweb' => 'MWEB' //H5支付，非微信内H5环境
    ];

    private $openid; //公众号支付 用户openid

    private $wapUrl; //H5支付 WAP网站URL地址

    private $wapName; //H5支付 WAP网站名

    private $qrcodeTimeoutExpress; //扫码支付 二维码失效时间，单位：秒

    public function __construct($config)
    {
        parent::__construct($config);
    }

    //下单参数检测
    private function check($param)
    {
        isset($param['body']) || json_error('缺少参数：body');
        isset($param['out_trade_no']) || json_error('缺少参数：out_trade_no');
        isset($param['total_fee']) || json_error('缺少参数：total_fee');
        isset($param['trade_type']) || json_error('缺少参数：trade_type');
        isset($param['notify_url']) || json_error('缺少参数：notify_url');

        //不同支付类型时 参数检测
        if ($param['trade_type'] == self::$tradeMap['jsapi']) { //公众号支付
            isset($param['openid']) || json_error('缺少参数：openid');
            $this->openid = $param['openid'];
        } elseif ($param['trade_type'] == self::$tradeMap['native']) { //扫码支付
            isset($param['expire']) || json_error('缺少参数：expire');
            $this->qrcodeTimeoutExpress = $param['expire'];
        } elseif ($param['trade_type'] == self::$tradeMap['mweb']) { //H5支付
            isset($param['wap_url']) || json_error('缺少参数：wap_url');
            isset($param['wap_name']) || json_error('缺少参数：wap_name');
            $this->wapUrl = $param['wap_url'];
            $this->wapName = $param['wap_name'];
        }
    }

    //下单方法
    public function unifiedOrder($param)
    {
        $this->check($param);

        $data['appid'] = $this->appId;
        $data['mch_id'] = $this->mchId;
        $data['notify_url'] = $this->notifyUrl;
        $data['body'] = $param['body'];
        $data['out_trade_no'] = $param['out_trade_no'];
        $data['total_fee'] = $param['total_fee'];
        $data['trade_type'] = $param['trade_type'];
        $data['notify_url'] = $param['notify_url'];
        $data['nonce_str'] = $this->getRandomString();
        $data['spbill_create_ip'] = get_client_ip();
        $data['sign_type'] = $this->signType;

        //不同支付类型时 参数传入
        if ($param['trade_type'] == self::$tradeMap['jsapi']) {
            $data['openid'] = $this->openid;
        } elseif ($param['trade_type'] == self::$tradeMap['native']) {
            $data['time_start'] = date("YmdHis");
            $data['time_expire'] = date("YmdHis", time() + $this->qrcodeTimeoutExpress);
        } elseif ($param['trade_type'] == self::$tradeMap['mweb']) {
            $senseInfo = ['h5_info' => ['type' => 'Wap', 'wap_url' => $this->wapUrl, 'wap_name' => $this->wapName]];
            $data['scene_info'] = json_encode($senseInfo);
        }

        //获取签名数据
        $data['sign'] = $this->makeSign($data);
        $xml = $this->dataToXml($data);
        $response = $this->postXmlCurl($xml, self::UNIFIED_ORDER_URL);

        if (!$response) return false;
        $result = $this->xmlToArray($response);

        return $result;
    }

    //生成 APP端支付参数
    public function getAppParam($prepayId)
    {
        $data['appid'] = $this->appId;
        $data['partnerid'] = $this->mchId;
        $data['prepayid'] = $prepayId;
        $data['package'] = 'Sign=WXPay';
        $data['noncestr'] = $this->getRandomString();
        $data['timestamp'] = time();
        $data['sign_type'] = $this->signType;
        $data['sign'] = $this->makeSign($data);

        return $data;
    }

    //生成微信内H5支付、小程序端支付参数 注意参数大小写
    public function getJsapiParam($prepayId)
    {
        $data['appId'] = $this->appId;
        $data['nonceStr'] = $this->getRandomString();
        $data['package'] = 'prepay_id=' . $prepayId;
        $data['timeStamp'] = time();
        $data['signType'] = $this->signType;
        $data['paySign'] = $this->makeSign($data);

        return $data;
    }

    //查询订单
    public function queryOrder($outTradeNo)
    {
        $data['appid'] = $this->appId;
        $data['mch_id'] = $this->mchId;
        $data['nonce_str'] = $this->getRandomString();
        $data['out_trade_no'] = $outTradeNo;
        $data['sign_type'] = $this->signType;
        $data['sign'] = $this->makeSign($data);

        $xml = $this->dataToXml($data);
        $response = $this->postXmlCurl($xml, self::ORDER_QUERY_URL);

        if (!$response) return false;
        $result = $this->xmlToArray($response);

        return $result;
    }

    //关闭订单
    public function closeOrder($outTradeNo)
    {
        $data['appid'] = $this->appId;
        $data['mch_id'] = $this->mchId;
        $data['nonce_str'] = $this->getRandomString();
        $data['out_trade_no'] = $outTradeNo;
        $data['sign_type'] = $this->signType;
        $data['sign'] = $this->makeSign($data);

        $xml = $this->dataToXml($data);
        $response = $this->postXmlCurl($xml, self::CLOSE_ORDER_URL);

        if (!$response) return false;
        $result = $this->xmlToArray($response);

        return $result;
    }

    //获取支付结果通知数据
    public function getNotifyData()
    {
        //获取通知的数据
        $xml = file_get_contents('php://input');
        if (!$xml) return false;

        $data = $this->xmlToArray($xml);
        if (isset($data['return_code']) && $data['return_code'] == 'FAIL') return false;

        return $data;
    }

    //对后台通知交互时，如果微信收到商户的应答不是成功或超时，微信认为通知失败，微信会通过一定的策略定期重新发起通知，尽可能提高通知的成功率，但微信不保证通知最终能成功。
    //通知频率为15/15/30/180/1800/1800/1800/1800/3600，单位：秒
    public function replyNotify()
    {
        $data['return_code'] = 'SUCCESS';
        $data['return_msg'] = 'OK';
        $xml = $this->dataToXml($data);
        echo $xml;
        die();
    }
}