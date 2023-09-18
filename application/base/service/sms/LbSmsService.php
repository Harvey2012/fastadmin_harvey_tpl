<?php

namespace app\base\service\sms;

/**
 * 采悠短信
 */
class LbSmsService
{


    public function send_sms($mobile, $msg)
    {

        $post_data = array();
        $post_data['account'] = config('site.lb_sms_account');
        $post_data['pswd'] = config('site.lb_sms_password');
        $smsTitle = config('site.lb_sms_title');
        if (empty($post_data['account'])) return ['errcode' => 1, 'msg' => '短信账户未配置'];
        if (empty($post_data['pswd'])) return ['errcode' => 2, 'msg' => '短信密码未配置'];
        if (empty($smsTitle)) return ['errcode' => 3, 'msg' => '短信模板标题未配置'];
        if (empty($mobile)) return ['errcode' => 6, 'msg' => '手机不能为空'];
        $post_data['mobile'] = $mobile;
        $post_data['msg'] = "【" . $smsTitle . "】" . $msg;
//        $post_data['msg'] =  $msg;
        $post_data['needstatus'] = 'false';
        $url = 'http://120.26.199.31/msg/HttpBatchSendSM';
        $o = "";
        foreach ($post_data as $k => $v) {
            $o .= "$k=" . urlencode($v) . "&";
        }
        $post_data = substr($o, 0, -1);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $result = curl_exec($ch);
        curl_close($ch);
        ob_clean();
        if ($result) {
            return ['errcode' => 0, 'msg' => "短信发送成功,短信ID：" . $result['result']['sid']];
        } else {
            $error_code = '';
            return ['errcode' => 4, 'msg' => "短信发送失败(" . $error_code . ")：" . $msg];
            //返回内容异常，以下可根据业务逻辑自行修改
//            return ['errcode' => 5, 'msg' => "请求发送短信失败"];
        }
    }

}