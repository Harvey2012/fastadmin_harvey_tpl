<?php
/**
 * Created by PhpStorm.
 * User: Harvey
 * E-mail:lenziye@qq.com
 * Date: 2023/5/18
 * Time: 8:35 PM
 */

namespace app\service;


use app\service\UserService;
use fast\Http;
use think\Db;
use think\Session;

class WxH5Service
{

    protected const CACHE_NAME = 'wx_open_service_access_token';
    protected static $_instance = null;
    private $access_token = null;
    protected $callBackUrl = null;
    protected $appid = null;
    protected $app_secret = null;

    public function __construct($appid = null, $app_secret = null, $callBackUrl = null)
    {
        $this->callBackUrl = $callBackUrl;
        $this->app_secret = $app_secret;
        $this->appid = $appid;
    }

    //方法静态化
    public function instance()
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    //克隆方法私有化，防止复制实例
    private function __clone()
    {

    }


    //1 第一步：用户同意授权，获取code
    //2 第二步：通过code换取网页授权access_token
    //3 第三步：刷新access_token（如果需要）
    //4 第四步：拉取用户信息(需scope为 snsapi_userinfo)

    //拼接跳转地址，跳转到微信，获取Code
    public function step1($appId, $redirect_uri, $state)
    {
        $scope = 'snsapi_userinfo';
        $redirect_uri2 = urlencode($redirect_uri);
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?" .
            "appid={$appId}&redirect_uri={$redirect_uri2}&response_type=code&scope={$scope}&state={$state}#wechat_redirect";
        Header("Location: $url");
    }

    public function step2($code)
    {
        $data = $this->getLoginAccessToken($code);
        $openid = isset($data['openid']) ? $data['openid'] : '';
        $unionid = isset($data['unionid']) ? $data['unionid'] : '';
        if ($openid) {
            Session::set("h5_openid", $openid);
            Session::set("unionid", $unionid);
        }
        return $data;
    }


    //网络授权access_token
    protected function getLoginAccessToken($code = '')
    {
        $params = [
            'appid' => $this->appid,
            'secret' => $this->app_secret,
            'code' => $code,
            'grant_type' => 'authorization_code'
        ];
        $ret = Http::sendRequest('https://api.weixin.qq.com/sns/oauth2/access_token', $params, 'GET');
        if ($ret['ret']) {
            $ar = json_decode($ret['msg'], true);
            return $ar;
        }
        return [];
//返回示例
//        {
//          "access_token":"ACCESS_TOKEN",
//          "expires_in":7200,
//          "refresh_token":"REFRESH_TOKEN",
//          "openid":"OPENID",
//          "scope":"SCOPE",
//          "is_snapshotuser": 1,
//          "unionid": "UNIONID"
//        }
    }

    //获取用户头像昵称（需scope为 snsapi_userinfo）
    public function getWxUserInfo($login_access_token,$openid)
    {
        $params = [
            'access_token' => $login_access_token,
            'openid' => $openid,
            'lang' => 'zh_CN'
        ];
        $ret = Http::sendRequest('https://api.weixin.qq.com/sns/userinfo', $params, 'GET');
        if ($ret['ret']) {
            $ar = json_decode($ret['msg'], true);
            return $ar;
        }
        return [];
//        {
//  "openid": "OPENID",
//  "nickname": NICKNAME,
//  "sex": 1,
//  "province":"PROVINCE",
//  "city":"CITY",
//  "country":"COUNTRY",
//  "headimgurl":"https://thirdwx.qlogo.cn/mmopen/g3MonUZtNHkdmzicIlibx6iaFqAc56vxLSUfpb6n5WKSYVY0ChQKkiaJSgQ1dZuTOgvLLrhJbERQQ4eMsv84eavHiaiceqxibJxCfHe/46",
//  "privilege":[ "PRIVILEGE1" "PRIVILEGE2"     ],
//  "unionid": "o6_bmasdasdsad6_2sgVt7hMZOPfL"
//}
    }

    //全局接口access_token
    public function getGlobalAccessToken()
    {
        $token = cache(self::CACHE_NAME);
        if (empty($token)) {
            $appid = $this->appid;
            $appsecret = $this->app_secret;
            if (empty($appid) || empty($appsecret)) ajax_return(0, 'appid或appsecret不存在');
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $appid . '&secret=' . $appsecret;
            $res = http_get($url);
            $res = json_decode($res, true);
            if (!empty($res['access_token'])) {
                $this->access_token = $res['access_token'];
                $expiretime = intval($res['expires_in']) - 60;
                cache(self::CACHE_NAME, $this->access_token, $expiretime);
            } else {
                ajax_return(0, $res['errmsg']);
            }
        } else {
            $this->access_token = $token;
        }
        return $this->access_token;
    }


    public function getJsSdkConfig($url)
    {
        $jsapiTicket = $this->getJsticket();
        $noncestr = $this->createNonceStr();
        $sign = $this->getSignPackage($jsapiTicket, $noncestr, $url);
        return $sign;
    }

    protected function getSignPackage($jsapiTicket, $nonceStr, $url)
    {
        $timestamp = time();
        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
        $signature = sha1($string);
        $signPackage = array(
            "appId" => $this->appid,
            "nonceStr" => $nonceStr,
            "timestamp" => $timestamp,
            "url" => $url,
            "signature" => $signature,
            "rawString" => $string
        );
        return $signPackage;
    }

    protected function createNonceStr($length = 16)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    protected function getJsticket()
    {
        $jsticket = cache('jsticket');
        if (!$jsticket) {
            $access_token = $this->getGlobalAccessToken();
            $params = [
                'access_token' => $access_token,
                'type' => 'jsapi',
            ];
            $ret = Http::sendRequest('https://api.weixin.qq.com/cgi-bin/ticket/getticket', $params, 'GET');
            if ($ret['ret']) {
                $ar = json_decode($ret['msg'], true);
                if ($ar['errcode'] == 0) {
                    cache('jsticket', $ar['ticket'], $ar['expires_in'] - 60);
                    return $ar['ticket'];
                } else {
                    ajax_return(0, $ar['errmsg']);
                }
            }
        }
        return $jsticket;
    }

}
