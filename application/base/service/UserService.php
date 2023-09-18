<?php

namespace app\base\service;

use think\Controller;
use think\Db;
use think\Request;
use app\common\model\User;

class UserService
{
    public $user = null;

    public function __construct($user = null)
    {
        if (!empty($user)) $this->user = $user;
    }

    public function getExtUser()
    {
        $needUpdateWxUserInfo = empty($this->user['avatar']) || !preg_match('/(http:\/\/)|(https:\/\/)/i', $this->user['avatar']) ? true : false;
        $data['needUpdateWxUserInfo'] = $needUpdateWxUserInfo;
        return $data;
    }


    public function registerAfter($params)
    {
        if (lb_c('login_need_check')) {
            $data['user']['is_check'] = 0;
            Db::name('user')->where(['id' => $this->user['id']])->update(['is_check' => 0]);
        } else {
            $data['user']['is_check'] = 1;
            Db::name('user')->where(['id' => $this->user['id']])->update(['is_check' => 1]);
        }
        return $data;
    }

    public function loginAfter($params)
    {
        return [];
    }


}
