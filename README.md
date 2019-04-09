# wechatpay-alipay
支付宝支付 、支付宝退款、微信支付、微信企业付款、微信退款、微信公众号开发
### 目录
- [一、支付宝支付](#1)
- [二、支付宝退款](#2)
- [三、微信支付](#3)
- [四、微信退款](#4)
- [五、微信企业付款](#5)
- [六、支付回调通知](#6)
- [七、微信公众号开发](#7)
 
## <a id="#1">一、支付宝支付</a>
* 接口文档：https://docs.open.alipay.com/
* 公共错误码：https://docs.open.alipay.com/common/105806
 ```
 $config = [
     'app_id' => '',
     'rsa_private_key' => '',
     'alipay_public_key' => ''
 ];
 ```
#### 1.当面付
**注意：当面付不用设置应用网关和授权回调地址，只需要设置应用公钥**
``` 
$payData = [
    'order_no' => '', //商户订单号
    'order_price' => '', //订单总金额，单位：元 
    'subject' => '', //订单标题，粗略描述用户的支付目的
    'notify_url' => '', //支付成功后回调地址
    'expire' => '', ////当面付 二维码过期时间，单位：秒
];

$alipay = new Alipay($config);
$info = $alipay->placeQrcode($payData);
$info['code'] != 10000 && json_error((($info['sub_msg'] ?? '') . ' ' . $info['sub_code'] ?? ''), -2);
$info['code_url']  通过qrcode生成二维码后，用户扫码即可
```
#### 2.app支付
```
$payData = [
    'order_no' => '', //商户订单号
    'order_price' => '', //订单总金额，单位：元 
    'subject' => '', //订单标题，粗略描述用户的支付目的
    'notify_url' => '', //支付成功后回调地址
];

$alipay = new Alipay($config);
$info = $alipay->placeApp($payData);
将结果$info传给移动端即可，$info是将参数http_build_query()处理后生成的字符串
```
#### 3.手机网站支付
```
$payData = [
    'order_no' => '', //商户订单号
    'order_price' => '', //订单总金额，单位：元 
    'subject' => '', //订单标题，粗略描述用户的支付目的
    'notify_url' => '', //支付成功后回调地址
    'product_code' => '', //销售产品码，商家和支付宝签约的产品码
];

$alipay = new Alipay($config);
$info = $alipay->placeWap($payData);
$info['html_form']  将结果通过ajax方式写入html body中即可
```
#### 4.电脑网站支付
* 服务端代码：
```
$payData = [
    'order_no' => '', //商户订单号
    'order_price' => '', //订单总金额，单位：元 
    'subject' => '', //订单标题，粗略描述用户的支付目的
    'notify_url' => '', //支付成功后回调地址
];

$alipay = new Alipay($config);
$info = $alipay->placePage($payData);
$info['html_form']  将结果通过ajax方式写入html body中即可
```

* html代码如下：
```
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>支付宝电脑网站支付</title>
</head>
<body id="body">

<script src="http://code.jquery.com/jquery-2.2.4.min.js" 
integrity="sha256-BbhdlvQf/xTY9gja0Dq3HiwQF8LaCRTXxZKRutelT44=" crossorigin="anonymous"></script>
<script>
    $.ajax({
        url: "/monitor/test", //接口地址请自行修改
        type: 'POST',
        success: function (data) {
            $("#body").html(data.data.html_form);
        }
    });
</script>
</body>
</html>
```

## <a id="#2">二、支付宝退款</a>
 ```
 $config = [
     'app_id' => '',
     'rsa_private_key' => '',
     'alipay_public_key' => ''
 ];
 ```
#### 1.申请退款
 ```
$param = [
    'out_trade_no' => '', //商户订单号
    'refund_amount' => '', //需要退款的金额，该金额不能大于订单金额,单位为元，支持两位小数
    'refund_reason' => '', //退款的原因说明，256字以内
    'out_request_no' => '', //标识一次退款请求，同一笔交易多次退款需要保证唯一，如需部分退款，则此参数必传
];
 
$alipay = new AlipayRefund($config);
$info = $alipay->doRefund($param);
$info['code'] != 10000 && json_error((($info['sub_msg'] ?? '') . ' ' . $info['sub_code'] ?? ''), -2);
``` 
#### 2.退款查询
```
$payData = [
    'out_trade_no' => '', //商户订单号
    'out_request_no' => '', //标识一次退款请求，同一笔交易多次退款需要保证唯一，如需部分退款，则此参数必传
];
 
$alipay = new AlipayRefund($config);
$info = $alipay->queryRefund($payData);
$info['code'] != 10000 && json_error((($info['sub_msg'] ?? '') . ' ' . $info['sub_code'] ?? ''), -2);
```

## <a name="3">三、微信支付</a>
* 微信支付文档：https://pay.weixin.qq.com/wiki/doc/api/index.html
* 企业付款文档：https://pay.weixin.qq.com/wiki/doc/api/tools/mch_pay.php?chapter=14_2
```
$config = [
    'appid' => '',
    'mch_id' => '',
    'key' => '',
    'sslcert_path' => '', //支付时不用上传证书
    'sslkey_path' => ''
];

$payData = [
    'body' => '', //订单标题，粗略描述用户的支付目的
    'out_trade_no' => '', //商户订单号
    'total_fee' => '', //订单总金额，单位：分
    'trade_type' => '', //支付类型 JSAPI、APP、NATIVE、MWEB
    'notify_url' => '', //支付成功后回调地址
];

//不同支付方式，需传入参数
if ($payData['trade_type'] == 'JSAPI') {
    $payData['openid'] = ''; //公众号支付 用户openid
}elseif($payData['trade_type'] == 'NATIVE'){
    $payData['expire'] = ''; //当面付 二维码过期时间
}elseif($payData['trade_type'] == 'MWEB'){
    $payData['wap_url'] = ''; //H5支付 WAP网站URL地址
    $payData['wap_name'] = ''; //H5支付 WAP网站名
}

//预下单方法：
$wechatPay = new WechatPay($config);
$info = $wechatPay->unifiedOrder($payData);
$info['return_code'] == 'FAIL' && json_error($info['return_msg'], -2);
$info['result_code'] == 'FAIL' && json_error($info['err_code_des'], -2);
```
#### 1.扫码支付
```
$info['code_url']  此url用于生成支付二维码，然后提供给用户进行扫码支付。
                   注意：code_url的值并非固定，使用时按照URL格式转成二维码即可
```
#### 2.H5支付
```
$info['mweb_url']  mweb_url为拉起微信支付收银台的中间页面，可通过访问该url来拉起微信客户端，完成支付,
                   mweb_url有效期为5分钟可拼接跳转链接：$info['mweb_url'].'&redirect_url='.urlencode($returnUrl);
```
#### 3.App支付
```
$wechatPay->getAppParam($info['prepay_id']);  将结果返回给App端即可
```
#### 4.Jsapi支付
```
$wechatPay->getJsapiParam($info['prepay_id']);  将结果返回给微信内H5支付或小程序端即可
```

## <a name="4">四、微信退款</a>
 ```
$config = [
    'appid' => '',
    'mch_id' => '',
    'key' => '',
    'sslcert_path' => '', //退款时必须上传证书
    'sslkey_path' => ''
];
 ```
#### 1.申请退款
 ```
$param = [
    'out_trade_no' => '', //商户系统内部订单号
    'out_refund_no' => '', //商户系统内部的退款单号
    'total_fee' => '', //订单总金额，单位为分，只能为整数
    'refund_fee' => '', //退款总金额，订单总金额，单位为分，只能为整数
    'refund_desc' => '', //退款原因，注意：若订单退款金额≤1元且属于部分退款，则不会在退款消息中体现退款原因
    'notify_url' => '', //支付成功后回调地址
];
 
$wechatRefund = new WechatRefund($config);
$info = $wechatRefund->doRefund($param);
$info['return_code'] == 'FAIL' && json_error($info['return_msg'], -2);
$info['result_code'] == 'FAIL' && json_error($info['err_code_des'], -2);
``` 
#### 2.退款查询
```
$outTradeNo = ''; //商户订单号

$wechatRefund = new WechatRefund($config);
$info = $wechatRefund->queryRefund($outTradeNo); 
$info['return_code'] == 'FAIL' && json_error($info['return_msg'], -2);
$info['result_code'] == 'FAIL' && json_error($info['err_code_des'], -2);
```

## <a name="5">五、微信企业付款</a>
 ```
$config = [
    'appid' => '',
    'mch_id' => '',
    'key' => '',
    'sslcert_path' => '', //企业付款时必须上传证书
    'sslkey_path' => ''
];
 ```
 #### 1.企业付款到零钱
```
$param = [
    'partner_trade_no' => '', //商户订单号，需保持唯一性(只能是字母或者数字，不能包含有其他字符)
    'openid' => '', //商户appid下，某用户的openid
    'amount' => '', //企业付款金额，单位为分
    'desc' => '', //企业付款备注，必填。注意：备注中的敏感词会被转成字符*
    're_user_name' => '', //收款用户真实姓名，NO_CHECK 时可不传，如果check_name设置为FORCE_CHECK，则必填用户真实姓名
];
  
$wechatMchPay = new WechatMchPay($config);
$info = $wechatMchPay->payChange($param);
$info['return_code'] == 'FAIL' && json_error($info['return_msg'], -2);
$info['result_code'] == 'FAIL' && json_error($info['err_code_des'], -2);
``` 
 #### 2.查询企业付款到零钱
 ```
$partnerTradeNo = ''; //商户订单号
 
$wechatRefund = new WechatRefund($config);
$info = $wechatRefund->queryRefund($partnerTradeNo); 
$info['return_code'] == 'FAIL' && json_error($info['return_msg'], -2);
$info['result_code'] == 'FAIL' && json_error($info['err_code_des'], -2);
 ```
 
## 六、<a name="6">支付回调通知</a>
``` 
//微信支付
public function orderWechat()
{
    $this->wechat('order', LogFile::$OrderPaySuccess, LogFile::$OrderPayError);
}

//支付宝支付
public function orderAlipay()
{
    $this->alipay('order', LogFile::$OrderPaySuccess, LogFile::$OrderPayError);
}

//微信支付
private function wechat($callback, $successLog, $errorLog)
{
    $wechatPay = new WechatPay($this->wechatConfig);
    $verify = $wechatPay->getNotifyData();

    list($userId, $orderNo) = explode(Order::$mark, $verify['out_trade_no']);
    $msg = 'user_id:' . $userId . '  order_no:' . $orderNo . '  ' . mb_convert_encoding(json_encode(gbk2utf8($verify)), 'UTF-8', 'auto');

    if ($verify['return_code'] == 'SUCCESS' && $verify['result_code'] == 'SUCCESS') {
        $bool = (new NotifyService())->$callback($verify, Order::$wechatPay);
        if ($bool === true) {
            //支付成功后关闭支付宝订单（必须使用支付宝钱包扫码唤起收银台后才能创建订单，因此直接关闭订单时，显示错误 ACQ.TRADE_NOT_EXIST）
            (new Alipay($this->alipayConfig))->closeOrder($verify['out_trade_no']);

            Log::write($msg, $successLog, Log::API_LOG);
        } else {
            Log::write($msg, $errorLog, Log::ERROR_LOG);
        }

        $wechatPay->replyNotify(); //操做成功，通知微信停止异步通知
    } else {
        Log::write($msg, $errorLog, Log::ERROR_LOG);
    }
}

//支付宝支付
private function alipay($callback, $successLog, $errorLog)
{
    $alipay = new Alipay($this->alipayConfig);
    $check = $alipay->rsaCheck($verify = $_POST);

    list($userId, $orderNo) = explode(Order::$mark, $verify['out_trade_no']);
    $msg = 'user_id:' . $userId . '  order_no:' . $orderNo . '  ' . mb_convert_encoding(json_encode(gbk2utf8($verify)), 'UTF-8', 'auto');

    if ($check === true) {
        //TRADE_SUCCESS：支付成功的通知  TRADE_FINISHED：退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知
        if ($verify['trade_status'] == 'TRADE_SUCCESS') {
            $bool = (new NotifyService())->$callback($verify, Order::$alipay);
            if ($bool === true) {
                //支付成功后关闭微信订单（可直接关闭）
                (new WechatPay($this->wechatConfig))->closeOrder($verify['out_trade_no']);

                Log::write($msg, $successLog, Log::API_LOG);
            } else {
                Log::write($msg, $errorLog, Log::ERROR_LOG);
            }
        } else {
            Log::write($msg, $errorLog, Log::ERROR_LOG);
        }

        $alipay->replyNotify(); //操做成功，通知支付宝停止异步通知
    } else {
        Log::write($msg, $errorLog, Log::ERROR_LOG);
    }
}
 ``` 
 
 ## <a name="7">七、微信公众号开发</a>
  ``` 
  $config = [
      'appid' => 'xxxxx',
      'mch_id' => 'xxxxx',
      'key' => 'xxxxx',

      //公众号支付 
      'jsapi_appid' => 'xxxxx', //jsapi_appid与appid 实际一致，分开写好做区分
      'jsapi_secret' => 'xxxxx',
  ];
  
 //分享链接（移动端）
 public function wapShare()
 {
     if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
         //电脑扫一扫分享 type=scane
         $type = isset($_GET['type']) ? ('?type=' . $_GET['type']) : '';
         $redirectUrl = 'http://xxx/api/wap/openid' . $type;

         $jsapi = new WechatJsapi(config('pay.wechat'));
         $jsapi->getCode($redirectUrl, WechatJsapi::BASE_SCOPE);
     } else {
         return $this->fetch('wap/book_share', ['openid' => '', 'appId' => '', 'nonceStr' => '', 'timeStamp' => '', 'paySign' => '',]);
     }
 }

 //获取openid
 public function openid()
 {
     if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') === false) {
         $this->redirect('/share');
     }

     $openid = '';
     $jsapi = new WechatJsapi(config('pay.wechat'));

     $type = isset($_GET['type']) ? ('?type=' . $_GET['type']) : '';
     $redirectUrl = 'http://xxx/api/wap/openid' . $type;
     if (isset($_REQUEST['code'])) {
         $tokenData = $jsapi->getAccessToken($_REQUEST['code']);
         if (isset($tokenData['errcode'])) {
             if (isset($tokenData['errcode']) == 40163) { //返回上一页重定向
                 $jsapi->getCode($redirectUrl, WechatJsapi::BASE_SCOPE);
             } else {
                 json_error('errcode:' . $tokenData['errcode'] . ' ' . $tokenData['errmsg']);
             }
         }
         $openid = $tokenData['openid'];
     } else {
         $jsapi->getCode($redirectUrl, WechatJsapi::BASE_SCOPE);
     }

     //微信分享
     $shareData = $jsapi->getShareData();
     $shareData['openid'] = $openid;

     return $this->fetch('wap/book_share', $shareData);
 }
 ``` 