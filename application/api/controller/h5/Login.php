<?php

namespace app\api\controller\h5;


use app\common\controller\Api;
use app\service\UserService;
use app\service\WxH5Service;
use think\Db;

/**
 *
 */
class Login extends Api
{

    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['gotologin', 'getlogincode'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['*'];


    protected $customer = null;
    protected $customerId = null;

    protected $appId = 'wxc8c84f52d588ec3b';
    protected $appSecret = '477103a1badfaeb5fae70fda829df9ae';

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\v1\ArticleCourseCollect();
        if ($this->auth->isLogin()) {
            $this->customer = $this->auth->getUser();
            $this->customerId = $this->customer['id'];
        }
    }

    //前端重定向到此接口，后台拼接完参数后，继续重定向到微信
    // login_back_url 登录完成后跳转回前端的地址
    public function gotoLogin()
    {
        $login_back_url = input('get.login_back_url');//登录完成后跳转到前端的地址
        if (empty($login_back_url)) $this->error('缺少login_back_url参数');
        $state = 'wxh5login' . time() . mt_rand(1000, 9999);
        cache($state, urldecode($login_back_url), 20);
        $redirect_uri = request()->domain() . '/api/h5/login/getLoginCode';
        (new WxH5Service())->step1($this->appId, $redirect_uri, $state);
    }

    //微信重定向回来
    public function getLoginCode()
    {
        $param = input('param.');
        $state = $param['state'];
        $return_h5_url = cache($state);
        $wxLoginRes = (new WxH5Service($this->appId, $this->appSecret))->step2($param['code']);
        if (array_key_exists('openid', $wxLoginRes)) {
            $userService = new UserService();
            $password = '112123';
            $email = '';
            $mobile = '';
            if (!empty($wxLoginRes['unionid'])) {
                $userInfo = Db::name('user')->where('union_id', $wxLoginRes['unionid'])->find();
            } else {
                $userInfo = Db::name('user')->where('h5_openid', $wxLoginRes['openid'])->find();
            }
            $needUpdateUserInfo = empty($userInfo['avatar']) ? true : false;
            if ($userInfo) {//登录
                if (config('custom.need_user_mobile_register')) {
                    $needGetMobile = empty($userInfo['mobile']) ? true : false;
                } else {
                    $needGetMobile = false;
                }
                $username = $userInfo['username'];
                $ret = $this->auth->login($username, $password);
                if ($ret) {
                    //更新session_key
                    $loginUpdate = [
                        'h5_access_token' => $wxLoginRes['access_token'],
                        'h5_refresh_token' => $wxLoginRes['refresh_token'],
                        'h5_openid' => $wxLoginRes['openid'],
                        'union_id' => $wxLoginRes['unionid'],
                    ];
                    Db::name('user')->where('username', $username)->update($loginUpdate);
                    $userService->user = $this->auth->getUser();
                    $rga = $userService->loginAfter($param);
                    $ext = $userService->getExtUser();
                    $userInfo = array_merge($this->auth->getUserinfo(), $ext);
                    $data = ['userinfo' => $userInfo,
                        'wxInfo' => $wxLoginRes,
                        'needGetUserMobile' => $needGetMobile,
                        'needUpdateUserInfo' => $needUpdateUserInfo];
                    if (!is_bool($rga)) {
                        $data = array_merge($data, $rga);
                    }
//                    $this->success(__('登录成功'), $data);
                    cookie('token', $this->auth->getToken(), 3600);
                    Header("Location: {$return_h5_url}");
                } else {
                    $this->error($this->auth->getError());
                }
            } else {//注册
                $username = $wxLoginRes['openid'];
                $extra['h5_openid'] = $wxLoginRes['openid'];
                $extra['h5_access_token'] = $wxLoginRes['access_token'];
                $extra['h5_refresh_token'] = $wxLoginRes['refresh_token'];
                $extra['nickname'] = '访客' . mt_rand(10000, 99999);
                if (!empty($wxLoginRes['unionid'])) {
                    $extra['union_id'] = $wxLoginRes['unionid'];
                }
                $re = $this->auth->register($username, $password, $email, $mobile, $extra);
                if ($re) {
                    $user = $this->auth->getUser();
                    // 保存token
                    $token = $this->auth->getToken();
                    Db::name('user')->where(['id' => $user['id']])->update(['token' =>$token]);
                    $userService->user = $user;
                    $rga = $userService->registerAfter($param);
                    $ext = $userService->getExtUser();
                    $userInfo = array_merge($this->auth->getUserinfo(), $ext, $rga['user']);
                    $data = [
                        'userinfo' => $userInfo,
                        'wxInfo' => $wxLoginRes,
                        'needGetUserMobile' => config('custom.need_user_mobile_register'),
                        'needUpdateUserInfo' => $needUpdateUserInfo];
                    if (!is_bool($rga)) {
                        unset($rga['user']);
                        $data = array_merge($data, $rga);
                    }
//                    $this->success('注册成功', $data);
                    cookie('token', $token, 3600);
                    Header("Location: {$return_h5_url}");
                } else {
                    $this->error('注册失败', $wxLoginRes);
                }
            }
        } else {
            $this->error('登录失败', $wxLoginRes);
        }
    }

}
