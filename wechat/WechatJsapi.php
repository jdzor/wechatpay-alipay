<?php
/**
 * @note: 公众号相关
 * @author: jdzor <895947580@qq.com>
 * @date: 2019/4/2
 */

namespace app\common\extend\wechat;


/**
 * 接口文档：https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140842
 *
 * Class WechatJsapi
 * @package app\common\extend\wechat
 */
class WechatJsapi extends BaseWechat
{
    const AUTHORIZE_URL = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=%s&redirect_uri=%s&response_type=code&scope=%s&state=STATE#wechat_redirect";

    const ACCESS_TOKEN_URL = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=%s&secret=%s&code=%s&grant_type=authorization_code";

    const REFRESH_TOKEN_URL = "https://api.weixin.qq.com/sns/oauth2/refresh_token?appid=%s&grant_type=refresh_token&refresh_token=%s";

    const USERINFO_URL = "https://api.weixin.qq.com/sns/userinfo?access_token=%s&openid=%s&lang=zh_CN";

    /*
        1、以snsapi_base为scope发起的网页授权，是用来获取进入页面的用户的openid的，并且是静默授权并自动跳转到回调页的。用户感知的就是直接进入了回调页（往往是业务页面）
        2、以snsapi_userinfo为scope发起的网页授权，是用来获取用户的基本信息的。但这种授权需要用户手动同意，并且由于用户同意过，所以无须关注，就可在授权后获取该用户的基本信息。
        3、用户管理类接口中的“获取用户基本信息接口”，是在用户和公众号产生消息交互或关注后事件推送后，
        才能根据用户openid来获取用户基本信息。这个接口，包括其他微信接口，都是需要该用户（即openid）关注了公众号后，才能调用成功的。
    */
    const BASE_SCOPE = 'snsapi_base';

    const USERINFO_SCOPE = 'snsapi_userinfo';

    private $jsapiAppId;

    private $jsapiSecret;

    public function __construct($config)
    {
        parent::__construct($config);

        isset($config['jsapi_appid']) || json_error('缺少参数：jsapi_appid');
        isset($config['jsapi_secret']) || json_error('缺少参数：jsapi_secret');
        $this->jsapiAppId = $config['jsapi_appid'];
        $this->jsapiSecret = $config['jsapi_secret'];
    }

    //获取code
    public function getCode($redirectUrl, $scope = self::BASE_SCOPE)
    {
        $authorizeUrl = sprintf(self::AUTHORIZE_URL, $this->jsapiAppId, urlencode($redirectUrl), $scope);

        //重定向请求微信用户信息
        header("Location: " . $authorizeUrl);
    }

    //获取access_token
    public function getAccessToken($code)
    {
        $accessTokenUrl = sprintf(self::ACCESS_TOKEN_URL, $this->jsapiAppId, $this->jsapiSecret, $code);
        return $this->getCurl($accessTokenUrl);
    }

    //刷新access_token
    public function refreshAccessToken($refreshToken)
    {
        $refreshTokenUrl = sprintf(self::REFRESH_TOKEN_URL, $this->jsapiAppId, $refreshToken);
        return $this->getCurl($refreshTokenUrl);
    }

    //获取用户信息
    public function getUserInfo($accessToken, $openid)
    {
        $userInfoUrl = sprintf(self::USERINFO_URL, $accessToken, $openid);
        return $this->getCurl($userInfoUrl);
    }


    //生成微信公众号分享参数
    public function getShareData()
    {
        $data['appId'] = $this->appId;
        $data['nonceStr'] = $this->getRandomString();
        $data['timeStamp'] = time();
        $data['paySign'] = $this->makeSign($data);

        return $data;
    }


}