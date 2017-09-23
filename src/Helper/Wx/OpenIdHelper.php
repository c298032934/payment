<?php

namespace Payment\Helper\Wx;

use Payment\Utils\Curl;
use Payment\Common\PayException;
use Payment\Common\Weixin\WxBaseStrategy;

/**
 * 微信OpenId获取
 */
class OpenIdHelper extends WxBaseStrategy
{

    /**
     * 执行类
     * @param array $data
     * @throws PayException
     * @return array|string
     */
    public function handle(array $data)
    {
        if (!isset($_GET['code'])) {
            //触发微信返回code码
            $baseUrl = urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '?' . trim($_SERVER['QUERY_STRING'],
                    '?'));
            $url = $this->__CreateOauthUrlForCode($baseUrl);
            Header("Location: $url");
            exit();
        } else {
            //获取code码，以获取openid
            $code = $_GET['code'];
            $openid = $this->getOpenidFromMp($code);
            return $openid;
        }
    }

    /**
     * 通过code从工作平台获取openid机器access_token
     * @param string $code 微信跳转回来带上的code
     * @return openid
     * @throws PayException
     */
    public function GetOpenidFromMp($code)
    {
        $url = $this->__CreateOauthUrlForOpenid($code);

        $res = $this->curlGet($url);
        if ($res['error']) {
            throw new PayException('网络发生错误，请稍后再试curl返回码：' . $res['message']);
        }
        //取出openid
        $data = json_decode($res['body'], true);
        $openid = $data['openid'];
        return $openid;
    }

    /**
     * 微信退款接口，需要用到相关加密文件及证书，需要重新进行curl的设置
     * @param string $url
     * @return array
     */
    protected function curlGet($url)
    {
        $curl = new Curl();
        $responseTxt = $curl->get($url);
        return $responseTxt;
    }

    /**
     * 构造获取code的url连接
     * @param string $redirectUrl 微信服务器回跳的url，需要url编码
     *
     * @return string
     */
    private function __CreateOauthUrlForCode($redirectUrl)
    {
        $urlObj["appid"] = $this->config->appId;
        $urlObj["redirect_uri"] = "$redirectUrl";
        $urlObj["response_type"] = "code";
        $urlObj["scope"] = "snsapi_base";
        $urlObj["state"] = "STATE" . "#wechat_redirect";
        $bizString = $this->ToUrlParams($urlObj);
        return "https://open.weixin.qq.com/connect/oauth2/authorize?" . $bizString;
    }

    /**
     *
     * 拼接签名字符串
     * @param array $urlObj
     *
     * @return string
     */
    private function ToUrlParams($urlObj)
    {
        $buff = "";
        foreach ($urlObj as $k => $v) {
            if ($k != "sign") {
                $buff .= $k . "=" . $v . "&";
            }
        }
        $buff = trim($buff, "&");
        return $buff;
    }

    /**
     * 构造获取open和access_toke的url地址
     * @param string $code 微信跳转带回的code
     * @return string
     */
    private function __CreateOauthUrlForOpenid($code)
    {
        $urlObj["appid"] = $this->config->appId;
        $urlObj["secret"] = $this->config->appSecret;
        $urlObj["code"] = $code;
        $urlObj["grant_type"] = "authorization_code";
        $bizString = $this->ToUrlParams($urlObj);
        return "https://api.weixin.qq.com/sns/oauth2/access_token?" . $bizString;
    }

    /**
     * 获取支付对应的数据完成类
     * @return string
     */
    public function getBuildDataClass()
    {
        return '';
    }
}