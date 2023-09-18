<?php
/**
 * Created by PhpStorm.
 * User: Harvey
 * Date: 2020/7/11
 * Time: 10:01 PM
 */

namespace app\base\service\tencent;
require_once EXTEND_PATH . "TLSSigAPIv2.php";

use Tencent\TLSSigAPIv2;

class TxLiveService
{
    public $sdkAppid = null;
    public $sdkAppKey = null;
    public $api = null;
    const EXPIRETIME = 604800; // 7天

    public function __construct()
    {
        $this->sdkAppid = config('site.tencent_cloud_sdk_appid');
        $this->sdkAppKey = config('site.tencent_cloud_sdk_key');
        $this->api = new TLSSigAPIv2($this->sdkAppid, $this->sdkAppKey);
    }

    public function getSig($customerId)
    {
        return $this->api->genSig($customerId, self::EXPIRETIME);
    }

    public function verifySig($customerId, $sig)
    {
        $init_time = 0;
        $expire = 0;
        $err_msg = '';
        $ret = $this->api->verifySig($sig, $customerId, $init_time, $expire, $err_msg);
        $res = [
            'status' => false,
            'expire' => $expire,
            'err_msg' => $err_msg,
            'init_time' => $init_time
        ];
        if (!$ret) {
            return $res;
        } else {
            $res['status'] = true;
            return $res;
        }
    }

    public function demo()
    {
        $sdkAppid = config('site.tencent_cloud_sdk_appid');
        $sdkAppKey = config('site.tencent_cloud_sdk_key');

        $api = new \Tencent\TLSSigAPIv2($sdkAppid, $sdkAppKey);
        $sig = $api->genSig('xiaojun');
        echo $sig . "\n";
        $init_time = 0;
        $expire = 0;
        $err_msg = '';
        $ret = $api->verifySig($sig, 'xiaojun', $init_time, $expire, $err_msg);
        if (!$ret) {
            echo $err_msg . "\n";
        } else {
            echo "verify ok expire $expire init time $init_time\n";
        }
        $userbuf = '';
        $ret = $api->verifySigWithUserBuf($sig, 'xiaojun', $init_time, $expire, $userbuf, $err_msg);
        if (!$ret) {
            echo $err_msg . "\n";
        } else {
            echo "verify ok expire $expire init time $init_time userbuf $userbuf\n";
        }

        $sig = $api->genSigWithUserBuf('xiaojun', 86400 * 180, 'abc');
        echo $sig . "\n";
        $init_time = 0;
        $expire = 0;
        $err_msg = '';
        $ret = $api->verifySig($sig, 'xiaojun', $init_time, $expire, $err_msg);
        if (!$ret) {
            echo $err_msg . "\n";
        } else {
            echo "verify ok expire $expire init time $init_time\n";
        }
        $userbuf = '';
        $ret = $api->verifySigWithUserBuf($sig, 'xiaojun', $init_time, $expire, $userbuf, $err_msg);
        if (!$ret) {
            echo $err_msg . "\n";
        } else {
            echo "verify ok expire $expire init time $init_time userbuf $userbuf\n";
        }

    }
}