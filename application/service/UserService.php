<?php

namespace app\service;

use think\Controller;
use think\Db;
use think\Request;
use app\common\model\User;

class UserService extends \app\base\service\UserService
{
    public $user = null;
    public function __construct($user = null)
    {
        parent::__construct();
    }


}
