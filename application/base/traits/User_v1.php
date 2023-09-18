<?php

namespace app\base\traits;

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
        if (array_key_exists('openid', $wxLoginRes)) {
            $userService = new UserService();
            $password = '112123';
            $email = '';
            $mobile = '';
            $userInfo = Db::name('user')->where('openid', $wxLoginRes['openid'])->find();
            $needUpdateUserInfo = empty($userInfo['avatar']) ? true : false;
            if ($userInfo) {
                $needGetMobile = config('custom.need_user_mobile_register') && empty($userInfo['mobile']) ? true : false;
                $username = $wxLoginRes['openid'];
                $ret = $this->auth->login($username, $password);
                if ($ret) {
                    //更新session_key
                    Db::name('user')->where('username', $username)->update(['session_key' => $wxLoginRes['session_key']]);
                    $userService->user = $this->auth->getUser();
                    $loginAfterData = $userService->loginAfter($param);
                    $extUser = $userService->getExtUser();
                    $data = [
                        'userinfo' => array_merge($this->auth->getUserinfo(), $extUser),
                        'wxInfo' => $wxLoginRes,
                        'needGetUserMobile' => $needGetMobile,
                        'needUpdateUserInfo' => $needUpdateUserInfo
                    ];
                    $this->success(__('Logged in successful'), array_merge($data, $loginAfterData));
                } else {
                    $this->error($this->auth->getError());
                }
            } else {
                $username = $wxLoginRes['openid'];
                $extra['openid'] = $wxLoginRes['openid'];
                $extra['session_key'] = $wxLoginRes['session_key'];
                $extra['nickname'] = '访客' . mt_rand(10000, 99999);
                $re = $this->auth->register($username, $password, $email, $mobile, $extra);
                if ($re) {
                    $user = $this->auth->getUser();
                    // 保存token
                    Db::name('user')->where(['id' => $user['id']])->update(['token' => $this->auth->getToken()]);
                    $userService->user = $user;
                    $registerAfterData = $userService->registerAfter($param);
                    $extUser = $userService->getExtUser();
                    $data = [
                        'userinfo' => array_merge($this->auth->getUserinfo(), $extUser),
                        'wxInfo' => $wxLoginRes,
                        'needGetUserMobile' => config('custom.need_user_mobile_register'),
                        'needUpdateUserInfo' => true];
                    $this->success('注册成功', array_merge($data, $registerAfterData));
                } else {
                    $this->error('注册失败', $wxLoginRes);
                }
            }
        } else {
            $this->error('登录失败', $wxLoginRes);
        }
    }


    /**
     * 小程序绑定手机号
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function miniDecryptPhone()
    {
        $appid = config('site.appid');;
        $appsecret = config('site.app_secret');
        $customer = $this->auth->getUser();
        $session_key = $customer['session_key'];
        $param = $this->checkParams('post', ['encryptedData', 'iv'], true);
        $pc = new \app\base\service\tencent\WxBizDataCrypt($appid, $session_key); //注意使用\进行转义
        $errCode = $pc->decryptData($param['encryptedData'], $param['iv'], $data);
        $data = json_decode($data);
        if (!empty($data->phoneNumber)) {
            Db::name('user')->where(['id' => $customer['id']])->update(['mobile' => $data->phoneNumber]);
            $this->success('ok', $data);
        } else {
            $this->error('解密失败');
        }
    }

    private function getRegisterNum()
    {
        $username = Db::name('user')->order('id desc')->value('username');
        if (empty($username)) return create_serial_number(100000);
        return create_serial_number(intval($username),6);
    }
}