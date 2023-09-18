<?php

namespace app\common\behavior;

use app\base\service\sms\LbSmsService;
use think\Config;
use think\Lang;
use think\Loader;

class LingbangSms
{

    public function run(&$params)
    {

        $code = $params['code'];
        if (empty($code)) return ['errcode' => 7, 'msg' => '验证码不能为空'];
        $serv = new LbSmsService();
        $msg = '验证码：' . $code . '，请勿将验证码泄露于他人！';
        $result = $serv->send_sms($params['mobile'], $msg);
        if ($result['errcode'] == 0) {
            return $result;
        } else {
            ajax_return(0, $result['msg']);
        }
    }

}
