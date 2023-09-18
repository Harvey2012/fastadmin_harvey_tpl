<?php

namespace app\base\traits;

use app\base\service\tencent\PcAppService;
use app\service\UserService;
use think\Db;

trait User
{
    /**
     * 小程序登录
     */
    public function miniLogin()
    {
        $param = input('param.');
        $code = $param['code'];
        $appid = config('site.appid');
        $appsecret = config('site.app_secret');
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid=" . $appid . "&secret=" . $appsecret . "&js_code=" . $code . "&grant_type=authorization_code";
        $responseJson = httpGet($url);
        $wxLoginRes = json_decode($responseJson, true);
        $this->loginRegister($wxLoginRes, $appid, 'json');
    }

    /**
     * 小程序绑定手机号
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function mini_decrypt_phone()
    {
        $appid = config('site.appid');
        $session_key = Db::name('user_account')->where(['appid' => $appid, 'uid' => $this->customerId])->value('session_key');
        $param = $this->checkParams('post', ['encryptedData', 'iv'], true);
        $pc = new \app\base\service\tencent\WxBizDataCrypt($appid, $session_key); //注意使用\进行转义
        $errCode = $pc->decryptData($param['encryptedData'], $param['iv'], $data);
        $data = json_decode($data);
        if (!empty($data->phoneNumber)) {
            Db::name('user')->where(['id' => $this->customerId])->update(['mobile' => $data->phoneNumber]);
            $this->success('ok', $data);
        } else {
            $this->error('解密失败', compact('appid', 'session_key', 'data'));
        }
    }

    /**
     * 后端跳转到 微信PC扫码登录页面
     */
    public function goPcWxLoginPage()
    {
        [$appid, $app_secret] = $this->getAppConfig('PC');
        $pcAppservice = new PcAppService($appid, $app_secret, config('site.pc_callbackurl'));
        $pcAppservice->goWxPcLoginPage('redirect');
    }

    /**
     *  后端跳转到 微信PC扫码登录回调
     */
    public function backPcWxloginPage()
    {
        [$appid, $app_secret] = $this->getAppConfig('PC');
        $pcAppservice = new PcAppService($appid, $app_secret, config('site.open_callbackurl'));
        // 用唯一随机串防CSRF攻击

        if (empty($_GET['code'])) {
            echo 'sorry,网络请求失败...';
            exit("5002");
        }
        $state = input('get.state');
        if (!empty($state) && $state != 'undefined') {
            $cacheState = cache('wx_state_' . $state);
            if ($state != $cacheState) {
                echo 'sorry,网络请求失败...' . $state . '--' . $cacheState;
                exit("5001");
            }
        }
        $wxLoginRes = $pcAppservice->wxPcLogin($_GET['code']);
        $res = $this->loginRegister($wxLoginRes, $appid, 'html');
        cookie('login_data', json_encode($res['data']), 20);
        return redirect(config('site.pc_index_page_url'));
    }

    /**
     * 前端登录
     */
    public function pcLoginByCode()
    {
        [$appid, $app_secret] = $this->getAppConfig('PC');
        $pcAppservice = new PcAppService($appid, $app_secret, config('site.open_callbackurl'));
        $wxLoginRes = $pcAppservice->wxPcLogin($_GET['code']);
        $this->loginRegister($wxLoginRes, $appid, 'json');
    }

// 【$wxLoginRes数据格式】
//        [
//            'access_token' => $arr['access_token'],
//            'refresh_token' => $arr['refresh_token'],
//            'expires_in' => $arr['expires_in'],
//            'openid' => $arr['openid'],
//            'unionid' => $arr['unionid'],
//            'nickname' => $user_info['nickname'],
//            'avatar' => $user_info['headimgurl'],
//        ]
    /**
     * @param $wxLoginRes
     * @param $appid
     * @param string $returnType json/html
     * @return array|void
     */
    private function loginRegister($wxLoginRes, $appid, $returnType = "json")
    {
        $password = '112123';
        $email = '';
        $mobile = '';
        $userService = new UserService();

        if (array_key_exists('openid', $wxLoginRes)) {
            $userInfo = null;
            $userAccount = Db::name('user_account')->where(['openid' => $wxLoginRes['openid']])->find();
            if (empty($userAccount)) {
                if (!empty($wxLoginRes['unionid'])) {
                    $userAccount = Db::name('user_account')->where(['unionid' => $wxLoginRes['unionid']])->find();
                    if (!empty($userAccount)) {
                        $userInfo = Db::name('user')->where('id', $userAccount['uid'])->find();
                        $data2 = [
                            'uid' => $userInfo['id'],
                            'openid' => $wxLoginRes['openid'],
                            'unionid' => $wxLoginRes['unionid'] ?? '',
                            'access_token' => $wxLoginRes['access_token'] ?? '',
                            'refresh_token' => $wxLoginRes['refresh_token'] ?? '',
                            'session_key' => $wxLoginRes['session_key'] ?? '',
                            'appid' => $appid,
                            'add_time' => time()
                        ];
                        if (!empty($wxLoginRes['expires_in'])) {
                            $data2['access_token_expire_time'] = time() + $wxLoginRes['expires_in'];
                            $data2['refresh_token_expire_time'] = time() + 29 * 3600 * 24;
                        }
                        Db::name('user_account')->insert($data2);
                    }
                }
            } else {
                $userInfo = Db::name('user')->where('id', $userAccount['uid'])->find();
            }
            $needUpdateUserInfo = empty($userInfo['avatar']) ? true : false;
            // 登录
            if ($userInfo) {
                $needGetMobile = config('custom.need_user_mobile_register') && empty($userInfo['mobile']) ? true : false;
                $ret = $this->auth->login($userInfo['username'], $password);
                if ($ret) {
                    $userService->user = $this->auth->getUser();
                    $loginAfterData = $userService->loginAfter();
                    $extUser = $userService->getExtUser();
                    $account_update = [];
                    if (!empty($wxLoginRes['access_token'])) $account_update['access_token'] = $wxLoginRes['access_token'];
                    if (!empty($wxLoginRes['refresh_token'])) $account_update['refresh_token'] = $wxLoginRes['refresh_token'];
                    if (!empty($wxLoginRes['session_key'])) $account_update['session_key'] = $wxLoginRes['session_key'];
                    if (!empty($wxLoginRes['expires_in'])) {
                        $account_update['access_token_expire_time'] = time() + $wxLoginRes['expires_in'];
                        $account_update['refresh_token_expire_time'] = time() + 29 * 3600 * 24;
                    }
                    Db::name('user_account')->where(['openid' => $wxLoginRes['openid'], 'uid' => $userInfo['id']])->update($account_update);
                    $data = [
                        'userinfo' => array_merge($this->auth->getUserinfo(), $extUser),
                        'wxInfo' => $wxLoginRes,
                        'needGetUserMobile' => $needGetMobile,
                        'needUpdateUserInfo' => $needUpdateUserInfo
                    ];
                    if ($returnType == 'json') {
                        $this->success(__('Logged in successful'), array_merge($data, $loginAfterData));
                    } else {
                        return ['status' => 1, 'data' => array_merge($data, $loginAfterData)];
                    }

                } else {
                    $this->error($this->auth->getError());
                }
            } //注册
            else {
                $username = $this->getRegisterNum();
                $extra['nickname'] = empty($wxLoginRes['nickname']) ? '访客' . mt_rand(10000, 99999) : $wxLoginRes['nickname'];
                if (!empty($wxLoginRes['avatar'])) {
                    $extra['avatar'] = $wxLoginRes['avatar'];
                }
                $re = $this->auth->register($username, $password, $email, $mobile, $extra);
                if ($re) {
                    $user = $this->auth->getUser();
                    // 保存token
                    Db::name('user')->where(['id' => $user['id']])->update(['token' => $this->auth->getToken()]);
                    $userService->user = $user;
                    $registerAfterData = $userService->registerAfter([]);
                    $extUser = $userService->getExtUser();
                    $data3 = [
                        'uid' => $user['id'],
                        'openid' => $wxLoginRes['openid'],
                        'unionid' => $wxLoginRes['unionid'] ?? '',
                        'access_token' => $wxLoginRes['access_token'] ?? '',
                        'refresh_token' => $wxLoginRes['refresh_token'] ?? '',
                        'session_key' => $wxLoginRes['session_key'] ?? '',
                        'appid' => $appid,
                        'add_time' => time()
                    ];
                    if (!empty($wxLoginRes['expires_in'])) {
                        $data3['access_token_expire_time'] = time() + $wxLoginRes['expires_in'];
                        $data3['refresh_token_expire_time'] = time() + 29 * 3600 * 24;
                    }
                    Db::name('user_account')->insert($data3);
                    $data = [
                        'userinfo' => array_merge($this->auth->getUserinfo(), $extUser),
                        'wxInfo' => $wxLoginRes,
                        'needGetUserMobile' => config('custom.need_user_mobile_register'),
                        'needUpdateUserInfo' => empty($wxLoginRes['nickname']) ? true : false,
                    ];
                    if ($returnType == 'json') {
                        $this->success('注册成功', array_merge($data, $registerAfterData));
                    } else {
                        return ['status' => 1, 'data' => array_merge($data, $registerAfterData)];
                    }
                } else {
                    $this->error('注册失败', $wxLoginRes);
                }
            }
        } else {
            $this->error('登录失败', $wxLoginRes);
        }
    }


    private function getRegisterNum()
    {
        $username = Db::name('user')->order('id desc')->value('username');
        if (empty($username)) return create_serial_number(100000);
        return create_serial_number(intval($username));
    }

}