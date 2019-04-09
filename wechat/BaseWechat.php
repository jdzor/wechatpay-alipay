<?php
/**
 * @note: 微信支付
 * @author: jdzor <895947580@qq.com>
 * @date: 2019/3/15
 */

namespace app\common\extend\wechat;


class BaseWechat
{
    const API_URL_PREFIX = 'https://api.mch.weixin.qq.com'; //接口API URL前缀

    protected $signType = 'MD5'; //签名方式

    protected $appId; //公众账号ID

    protected $mchId; //商户号

    protected $key; //支付密钥

    protected $sslcertPath; //证书路径

    protected $sslkeyPath; //证书路径

    protected $notifyUrl; //支付结果回调通知地址

    protected function __construct($config)
    {
        isset($config['appid']) || json_error('缺少参数：appid');
        isset($config['mch_id']) || json_error('缺少参数：mch_id');
        isset($config['key']) || json_error('缺少参数：key');

        $this->appId = $config['appid']; //支付、退款、企业付款使用
        $this->mchId = $config['mch_id'];
        $this->key = $config['key'];
    }

    //生成签名
    protected function makeSign($param)
    {
        //签名步骤一：按字典序排序数组参数
        ksort($param);
        $param = array_filter($param);
        $string = $this->toUrlParams($param);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . $this->key;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);

        return $result;
    }

    //参数拼接 url: key=value&key=value
    protected function toUrlParams($param)
    {
        $string = '';
        if ($param) {
            $array = [];
            foreach ($param as $key => $val) {
                $array[] = $key . '=' . $val;
            }
            $string = implode("&", $array);
        }

        return $string;
    }

    //输出xml字符
    protected function dataToXml($param)
    {
        if (!is_array($param) || count($param) <= 0) return false;

        $xml = "<xml>";
        foreach ($param as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";

        return $xml;
    }

    //将xml转为array
    protected function xmlToArray($xml)
    {
        if (!$xml) return false;

        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $xmlString = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $array = json_decode(json_encode($xmlString), true);

        return $array;
    }

    //产生一个指定长度的随机字符串 不长于32位
    protected function getRandomString($length = 32)
    {
        $str = '';
        $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";

        for ($i = 0; $i < $length; $i++) {
            $str .= $strPol[mt_rand(0, strlen($strPol) - 1)];
        }

        return $str;
    }

    //post方式提交xml到对应的接口url
    protected function postXmlCurl($xml, $url, $useCert = false, $second = 30)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_URL, self::API_URL_PREFIX . $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); //生产环境设置为2

        curl_setopt($ch, CURLOPT_HEADER, false);  //设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //要求结果为字符串且输出到屏幕上

        if ($useCert) {
            //使用证书：cert 与 key 分别属于两个.pem文件
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLCERT, $this->sslcertPath);
            curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLKEY, $this->sslkeyPath);
        }

        //post提交方式
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);

        if (!($data = curl_exec($ch))) $data = ['errno' => curl_errno($ch), 'error' => curl_error($ch)] + curl_getinfo($ch);
        curl_close($ch);

        return $data;
    }

    //get方式提交
    protected function getCurl($url)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); //设置cURL允许执行的最长秒数
        curl_setopt($ch, CURLOPT_HEADER, false);  //是否抓取头文件的信息，默认值为false，表示不显示响应头

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        if (!($data = curl_exec($ch))) $data = ['errno' => curl_errno($ch), 'error' => curl_error($ch)] + curl_getinfo($ch);

        //转码
        $fromEncoding = 'auto'; //未知原编码，通过auto自动检测后，转换编码为utf-8
        if (is_array($data)) {
            $data = json_decode(mb_convert_encoding(json_encode($data), 'UTF-8', $fromEncoding), true);
        } else {
            $data = json_decode(mb_convert_encoding($data, 'UTF-8', $fromEncoding), true);
        }
        curl_close($ch);

        return $data;
    }
}