<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use think\Cache;
use think\Env;

class Index extends Frontend
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $layout = '';

    public function index()
    {
//        return $this->view->fetch();
    }

    public function test()
    {
        ajax_return(0, 'hello');
    }

    //微信PC登录测试页面
    public function pcLogin()
    {
        return $this->view->fetch('pc_login');
    }

    public function testCache()
    {

        cache('abc', 1);
        dump(cache('abc'));

        Cache::store('file')->set('aa', 11, 60);
        dump(Cache::store('file')->get('aa'));

        Cache::store('redis')->set('cc', 22, 60);
        dump(Cache::store('redis')->get('cc'));
    }

    //微信H5登录测试页面
    public function wxH5LoginTest(){
        return $this->view->fetch();
    }

    //微信H5登录成功返回页面
    public function testWxLoginSuccess(){
        return $this->view->fetch();
    }
}
