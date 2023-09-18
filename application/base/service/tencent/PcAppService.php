<?php
/**
 * Created by PhpStorm.
 * User: Harvey
 * E-mail:lenziye@qq.com
 * Date: 2021/9/22
 * Time: 8:35 PM
 */

namespace app\base\service\tencent;


use app\service\UserService;
use fast\Http;
use think\Db;

class PcAppService
{

    const CACHE_NAME = 'wx_open_service_access_token';
    protected static $_instance = null;
    private $access_token = null;
    private $callBackUrl = null;
    private $appid = null;
    private $app_secret = null;

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

    /**
     * 微信扫码登录
     * $type url redirect
     */
    public function goWxPcLoginPage($type = 'url')
    {
        //--微信登录-----生成唯一随机串防CSRF攻击
        $state = md5(uniqid(rand(), TRUE));
        cache('wx_state_' . $state, $state, 60);
        $callback = $this->callBackUrl;
        $wxurl = "https://open.weixin.qq.com/connect/qrconnect?";
        $query_params = [
            'appid' => $this->appid,
            'redirect_uri' => $callback,
            'response_type' => 'code',
            'scope' => 'snsapi_login',
            'state' => $state,
        ];
        $wxurl .= http_build_query($query_params) . '#wechat_redirect';
        if ($type == 'url') {
            return $type;
        } elseif ($type == 'redirect') {
            header("Location: $wxurl");
        }
    }

    /**
     * 微信PC扫码登录
     */
    public function wxPcLogin($code)
    {

        $params = [
            'appid' => $this->appid,
            'secret' => $this->app_secret,
            'code' => $code,
            'grant_type' => 'authorization_code'
        ];
        //获取用户openID、access_token
        $ret = Http::sendRequest('https://api.weixin.qq.com/sns/oauth2/access_token', $params, 'GET');
//        【$ret数据格式】
//        {
//            ["ret"] => bool(true)
//        ["msg"] => string(75) "{"errcode":41002,"errmsg":"appid missing, rid: 62513277-29d535cc-416fb890"}"
//}
//        {
//            ["ret"] => bool(true)
//        ["msg"] => string(380) "{"access_token":"55__g27Dnvri4LFIWv8NwZWye0pWsr6I2M4k2MK7cLFcjuO2nEhGCFq5VBCzyetuZ2-5f4Y_5ZS9_3vGhLKAt_j3qdZJUOrLepDEa8cKSr2vPw",
//      "expires_in":7200,"refresh_token":"55_ibTLWG2PY3KYbZUfZAOGerR3tlQfU1W8vJ6nIqs2xRHl32fA35Nb_Os-4J5A9LUlTTgE5wIp2O1zLM_QIOKrWbxXxrgHmW-HKG622jSWrik",
//       "openid":"omnSJ5gyOiiUvg2KJg8MJRFh_eTI","scope":"snsapi_login","unionid":"oj1pK5pkMJ_r1z6BGSqUivyEwG6o"}"
//}
        if ($ret['ret']) {
            $arr = json_decode($ret['msg'], true);
            if (!empty($arr['errmsg'])) {
                ajax_return(0, '登录失败！', $arr);
            }
            //获取用户头像、昵称
            $url = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $arr['access_token'] . '&openid=' . $arr['openid'] . '&lang=zh_CN';
            $ret2 = Http::get($url);
            $user_info = json_decode($ret2, true);
            // 【$user_info 数据格式】
            //{
            //  ["openid"] => string(28) "omnSJ5gyOiiUvg2KJg8MJRFh_eTI"
            //  ["nickname"] => string(12) "颍州月光"
            //  ["sex"] => int(0)
            //  ["language"] => string(0) ""
            //  ["city"] => string(0) ""
            //  ["province"] => string(0) ""
            //  ["country"] => string(0) ""
            //  ["headimgurl"] => string(133) "https://thirdwx.qlogo.cn/mmopen/vi_32/Q0j4TwGTfTKl6dq8WK10JBqVEpUcTtObicK4IX3nsa9catohicxDWzTl7ia6WYTibXeR7SFPSFW98kYGoeRpjicB3qg/132"
            //  ["privilege"] => array(0) {
            //  }
            //  ["unionid"] => string(28) "oj1pK5pkMJ_r1z6BGSqUivyEwG6o"
            //}
            return [
                'access_token' => $arr['access_token'],
                'refresh_token' => $arr['refresh_token'],
                'openid' => $arr['openid'],
                'unionid' => $arr['unionid'] ?? '',
                'expires_in' => $arr['expires_in'],
                'nickname' => $user_info['nickname'],
                'avatar' => $user_info['headimgurl'],
            ];
        } else {
            ajax_return(0, '获取access_token失败！');
        }
    }

    // PC微信用户获取access_token
    public function get_access_token_pc($userid, $openid)
    {
        $userAccount = Db::name('user_account')->where(['uid' => $userid, 'openid' => $openid])->find();
        if (empty($userAccount)) return sys_return(false, '账户不存在');
        if (empty($userAccount['access_token_expire_time']) || empty($userAccount['refresh_token_expire_time'])) return sys_return(false, '过期时间空');
        if ($userAccount['access_token_expire_time'] > time()) {
            return sys_return(true, '', $userAccount['access_token']);
        } elseif ($userAccount['refresh_token_expire_time'] > time()) {
            $url = 'https://api.weixin.qq.com/sns/oauth2/refresh_token?appid=' . $userAccount['appid'] . '&grant_type=refresh_token&refresh_token=' . $userAccount['refresh_token'];
            $ret = Http::get($url);
            $data = json_decode($ret, true);
            $data1['access_token'] = $data['access_token'];
            $data1['access_token_expire_time'] = $data['expires_in'] + time();
            Db::name('user_account')->where(['uid' => $userid, 'openid' => $openid])->update($data1);
            return sys_return(true, '', $data);
        } else {
            return sys_return(0, '请重新登录');
        }
    }


}
