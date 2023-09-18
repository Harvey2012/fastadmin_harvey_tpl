<?php
/**
 * Created by PhpStorm.
 * User: Harvey
 * E-mail:lenziye@qq.com
 * Date: 2021/9/22
 * Time: 8:35 PM
 */

namespace app\base\service\tencent;


class WxappService
{

    const CACHE_NAME = 'wx_service_access_token';
    const API_1 = 'https://api.weixin.qq.com/wxa/getwxacode?access_token=';
    const API_2 = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=';

    protected static $_instance = null;
    private $access_token = null;


    //方法静态化
    public static function instance()
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    //克隆方法私有化，防止复制实例
    private function __clone()
    {

    }


    public function get_access_token()
    {
        $token = cache(self::CACHE_NAME);
        if (empty($token)) {
            print_log('get_access_token【1】');
            $appid = config('site.appid');
            $appsecret = config('site.app_secret');
            if (empty($appid) || empty($appsecret)) ajax_return(0, 'appid或appsecret不存在');
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $appid . '&secret=' . $appsecret;
            $res = http_get($url);
            print_log('get_access_token【1-1】', $res);
            $res = json_decode($res, true);
            if (!empty($res['access_token'])) {
                $this->access_token = $res['access_token'];
                cache(self::CACHE_NAME, $this->access_token, bcdiv(intval($res['expires_in']), 2, 0));
            } else {
                ajax_return(0, $res['errmsg']);
            }
        } else {
            $this->access_token = $token;
            print_log('get_access_token【2】');
        }
        return $this->access_token;
    }

    /*
     *  获取有限的小程序码
          width	number	430	否	二维码的宽度，单位 px。最小 280px，最大 1280px
          auto_color	boolean	false	否	自动配置线条颜色，如果颜色依然是黑色，则说明不建议配置主色调
          line_color	Object	{"r":0,"g":0,"b":0}	否	auto_color 为 false 时生效，使用 rgb 设置颜色 例如 {"r":"xxx","g":"xxx","b":"xxx"} 十进制表示
          is_hyaline	boolean	false	否	是否需要透明底色，为 true 时，生成透明底色的小程序码
    */
    public function getWxQrcodeLimited($path, $returnType = 'buffer', $ext = [], $fileName = '')
    {
        $token = $this->get_access_token();
        $url = self::API_1 . $token;
        $params['path'] = $path;
        $params = array_merge($params, $ext);
        $res = http_post($url, json_encode($params));
        print_log('小程序二维码返回', $res);
        if (stripos($res, 'errcode') !== false) {
            $res = json_decode($res, true);
            ajax_return(0, $res['errmsg']);
        }
        if ($returnType == 'buffer') {
            return $res;
        } else {
            $domain = request()->domain();
            $path = 'temp/wx_qrcode_temp/';
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
            $fileName = empty($fileName) ? time() . mt_rand(1000, 9999) . '.png' : $fileName;
            $paths = $path . $fileName;
            if (!file_exists($paths)) {
                $res = file_put_contents($paths, $res);
                if (!$res) {
                    ajax_return(0, '小程序码获取失败');
                    return '';
                }
            }
            return $domain . '/' . $paths;
        }
    }


    /*
 *  获取无限的小程序码
      width	number	430	否	二维码的宽度，单位 px。最小 280px，最大 1280px
      auto_color	boolean	false	否	自动配置线条颜色，如果颜色依然是黑色，则说明不建议配置主色调
      line_color	Object	{"r":0,"g":0,"b":0}	否	auto_color 为 false 时生效，使用 rgb 设置颜色 例如 {"r":"xxx","g":"xxx","b":"xxx"} 十进制表示
      is_hyaline	boolean	false	否	是否需要透明底色，为 true 时，生成透明底色的小程序码
*/
    public function getWxQrcodeNotLimited($page, $scene, $ext = [], $returnType = 'buffer', $fileName = '')
    {
        $token = $this->get_access_token();
        $url = self::API_2 . $token;
        $params['page'] = $page;
        $params['scene'] = $scene;
        $params['check_path'] = false;

        //todo
//        $params['env_version'] = 'develop'; //release trial  develop

        $params = array_merge($params, $ext);
        $res = http_post($url, json_encode($params));
        print_log('小程序二维码返回', $res);
        if (stripos($res, 'errcode') !== false) {
            $res = json_decode($res, true);
            ajax_return(0, $res['errmsg']);
        }
        if ($returnType == 'buffer') {
            return $res;
        } elseif ($returnType == 'file') {
            $domain = request()->domain();
            if (!is_dir('temp/')) {
                mkdir('temp', 0755, true);
            }
            $path = 'temp/wx_qrcode_temp/';
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
            $fileName = empty($fileName) ? time() . mt_rand(1000, 9999) . '.png' : $fileName;
            $paths = $path . $fileName;
            if (!file_exists($paths)) {
                $res = file_put_contents($paths, $res);
                if (!$res) {
                    ajax_return(0, '小程序码获取失败');
                    return '';
                }
            }
            return $domain . '/' . $paths;
        }
    }

    /**
     * 发送订阅消息
     */
    public function sendSubscribeMessage($touserOpenid, $templateId, $dataTemp)
    {
        $tokenInfo = $this->get_access_token();
        $url = 'https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token=' . $tokenInfo;
        $parm = [];
        $parm['touser'] = $touserOpenid;
        $parm['template_id'] = $templateId;
        foreach ($dataTemp as $key => $item) {
            $data[$key] = [
                'value' => $item
            ];
        }
        $parm['data'] = $data;
        return http_post($url, json_encode($parm));
    }

    /**
     * 获取scheme链接
     * @param $jump_wxa
     * @param $expire_type
     * @param array $ext
     * @return mixed
     */
    public function getScheme($jump_wxa, $expire_type, $ext = [])
    {
        $token = $this->get_access_token();
        $url = 'https://api.weixin.qq.com/wxa/generatescheme?access_token=' . $token;
        $params['jump_wxa'] = $jump_wxa;
        $params['expire_type'] = $expire_type;
        $params = array_merge($params, $ext);
        $res = http_post($url, json_encode($params));
        $result = json_decode($res, true);
        // "errcode": 41001,
        //        "errmsg": "access_token missing rid: 62b7d049-17a86299-4afab94d"
        if ($result['errcode'] == '41001') {
            cache(self::CACHE_NAME, null);
            return $this->getScheme($jump_wxa, $expire_type, $ext);
        }
        return $result;
    }

//    public function getScheme()
//    {
//        $cacheLink = cache('index_page_scheme_link');
//        if ($cacheLink) {
//            $this->success('', $cacheLink);
//        }
//        $jump_wxa = ['path' => '/pages/index/index'];
//        $res = WxappService::instance()->getScheme($jump_wxa, 1, ['expire_interval' => 30]);
//        if ($res['errcode'] == 0) {
//            cache('index_page_scheme_link', $res['openlink'], 30 * 24 * 3600);
//            $this->success($res['errmsg'], $res['openlink']);
//        } else {
//            $this->error($res['errmsg'], $res['errcode']);
//        }
//    }
}
