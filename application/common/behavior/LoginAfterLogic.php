<?php

namespace app\common\behavior;

use think\Config;
use think\Lang;
use think\Loader;

class LoginAfterLogic
{

    // 登录检查后的逻辑
    public function smsCheck(&$param){
        trace('登录检查后的逻辑');
        trace($param);
        return true;
    }

}
