<?php
/**
 * @note: 支付宝支付
 * @author: jdzor <895947580@qq.com>
 * @date: 2019/3/14
 */

namespace app\common\extend\alipay;


class BaseAlipay
{
    const GATEWAY_URL = 'https://openapi.alipay.com/gateway.do';

    protected $charset = 'UTF-8';

    protected $signType = 'RSA2';

    protected $format = 'JSON';

    protected $version = '1.0';

    //该笔订单允许的最晚付款时间，逾期将关闭交易。取值范围：1m～15d。
    //m-分钟，h-小时，d-天，1c-当天（1c-当天的情况下，无论交易何时创建，都在0点关闭）。 该参数数值不接受小数点， 如 1.5h，可转换为 90m。
    protected $timeoutExpress = '30m';

    protected $appId;

    protected $rsaPrivateKey;

    protected $alipayPublicKey; //支付宝公钥，非应用公钥

    protected $notifyUrl;

    protected $returnUrl;

    protected function __construct($config)
    {
        isset($config['app_id']) || json_error('缺少参数：app_id');
        isset($config['rsa_private_key']) || json_error('缺少参数：rsa_private_key');
        isset($config['alipay_public_key']) || json_error('缺少参数：alipay_public_key');

        $this->appId = $config['app_id'];
        $this->rsaPrivateKey = $config['rsa_private_key'];
        $this->alipayPublicKey = $config['alipay_public_key'];
    }

    //公共请求
    protected function commonRequest($requestConfig, $method)
    {
        //公共参数
        $commonConfig = [
            'app_id' => $this->appId,
            'method' => $method,
            'charset' => $this->charset,
            'format' => $this->format,
            'version' => $this->version,
            'sign_type' => $this->signType,
            'biz_content' => json_encode($requestConfig),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        $this->notifyUrl && $commonConfig['notify_url'] = $this->notifyUrl;
        $this->returnUrl && $commonConfig['return_url'] = $this->returnUrl;
        $commonConfig['sign'] = $this->generateSign($commonConfig, $commonConfig['sign_type']);

        if ($method == 'alipay.trade.app.pay') {
            //APP支付 返回参数给移动端
            return http_build_query($commonConfig);
        } elseif ($method == 'alipay.trade.wap.pay') {
            //手机网站支付 返回参数
            return $commonConfig;
        } elseif ($method == 'alipay.trade.page.pay') {
            //电脑网站支付 返回参数
            return $commonConfig;
        }

        $result = $this->curlPost(self::GATEWAY_URL, $commonConfig);
        return gbk2utf8($result);
    }

    protected function generateSign($param, $signType = "RSA")
    {
        return $this->sign($this->getSignContent($param), $signType);
    }

    protected function sign($data, $signType = "RSA")
    {
        $privateKey = $this->rsaPrivateKey;
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($privateKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
        ($res) or die('您使用的私钥格式错误，请检查RSA私钥配置');

        //OPENSSL_ALGO_SHA256 php5.4.8 以上版本才支持
        if ("RSA2" == $signType) {
            openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256);
        } else {
            openssl_sign($data, $sign, $res);
        }

        $sign = base64_encode($sign);
        return $sign;
    }

    protected function checkEmpty($value)
    {
        if (!isset($value)) return true;
        if ($value === null) return true;
        if (trim($value) === "") return true;

        return false;
    }

    protected function getSignContent($params)
    {
        ksort($params);
        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {
                //转换成目标字符集
                $v = $this->character($v, $this->charset);
                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }

        return $stringToBeSigned;
    }

    protected function character($data, $targetCharset)
    {
        if (!empty($data)) {
            $fileType = $this->charset;
            if (strcasecmp($fileType, $targetCharset) != 0) {
                $data = mb_convert_encoding($data, $targetCharset, $fileType);
                //$data = iconv($fileType, $targetCharset.'//IGNORE', $data);
            }
        }

        return $data;
    }

    protected function buildOrderStr($data)
    {
        return http_build_query($data);
    }

    protected function curlPost($url, $postData = '')
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($postData) ? http_build_query($postData) : $postData);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); //设置cURL允许执行的最长秒数
        curl_setopt($ch, CURLOPT_HEADER, FALSE);  //设置header

        //https请求 不验证证书和host
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        if (!($data = curl_exec($ch))) $data = ['errno' => curl_errno($ch), 'error' => curl_error($ch)] + curl_getinfo($ch);
        curl_close($ch);

        return $data;
    }

    protected function rsaCheck($param)
    {
        $sign = $param['sign'];
        $signType = $param['sign_type'];
        unset($param['sign_type']);
        unset($param['sign']);

        return $this->verify($this->getSignContent($param), $sign, $signType);
    }

    protected function verify($data, $sign, $signType = 'RSA')
    {
        $pubKey = $this->alipayPublicKey;
        $res = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($pubKey, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";
        ($res) or die('支付宝RSA公钥错误。请检查公钥文件格式是否正确');

        //调用openssl内置方法验签，返回bool值
        if ("RSA2" == $signType) {
            $result = (bool)openssl_verify($data, base64_decode($sign), $res, OPENSSL_ALGO_SHA256);
        } else {
            $result = (bool)openssl_verify($data, base64_decode($sign), $res);
        }

//        if (!$this->checkEmpty($this->alipayPublicKey)) {
//            openssl_free_key($result); //释放资源
//        }

        return $result;
    }

}