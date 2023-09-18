<?php
/**
 * Created by PhpStorm.
 * User: Harvey
 * E-mail:lenziye@qq.com
 * Date: 2021/6/8
 * Time: 6:24 PM
 */


/* 打印日志
* @param log
* @return boolean
* @date 2018/11/15
* @author Harvey
*/
if (!function_exists('print_log')) {
    function print_log($title, $msg = '')
    {
        if (empty($msg)) {
            trace($title, "info");
        } else {
            trace($title . ' ==>');
            if (is_array($msg)) {
                trace(json_encode($msg));
            } else {
                trace($msg);
            }

        }
    }
}

//判断数组中的某值是否存在,存在则返回,如果不存在返回默认值
if (!function_exists('array_get_val')) {
    function array_get_val($var, $key, $defaul_val = '')
    {
        return (isset($var) && isset($var[$key])) ? $var[$key] : $defaul_val;
    }
}


/* 输出网络图片
 * @param  $para
 * @return JSON
 * @Author Harvey
 * @Email  Lenziye@qq.com
 * @Date   2019/6/16
 */
if (!function_exists('out_net_img')) {

    function out_net_img($img)
    {
        if (preg_match('/(http:\/\/)|(https:\/\/)/i', $img)) {
            return $img;
        }
        return empty($img) ? '' : request()->domain() . $img;
    }

//    function out_net_img($img, $rule = '')
//    {
//        if (empty($img)) return $img;
//        if (stripos($img, 'http') === 0 || $img === '' || stripos($img, 'data:image') === 0) {
//            return $img;
//        } else {
//            $upload = \think\Config::get('upload');
//            if (!empty($upload['cdnurl'])) {
//                $url = $upload['cdnurl'] . $img;
//            } else {
//                $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
//                $url = $http_type . $_SERVER['HTTP_HOST'] . $img;
//            }
//            if (strrpos($url, '.gif') !== false) {
//                return $url;
//            } else {
//                return empty($url) ? $url : $url . $rule;
//            }
//        }
//    }
}




/** 数组图片 转为 图片数组
 * @param $arr_img
 * @return array
 */
if (!function_exists('array_to_imgs')) {
    function array_to_imgs($arr_img)
    {
        if (!empty($arr_img)) {
            $data = [];
            if (!is_array($arr_img)) {
                $imgs = explode(',', $arr_img);
            } else {
                $imgs = $arr_img;
            }
            foreach ($imgs as $vi) {
                if (preg_match('/(http:\/\/)|(https:\/\/)/i', $vi)) {
                    $data[] = $vi;
                } else {
                    $data[] = request()->domain() . $vi;
                }
            }
            return $data;
        } else {
            return [];
        }
    }
}


/**
 * 对象转数组
 *
 * @param string $path 指定的path
 * @return string
 */
if (!function_exists('obj_to_arr')) {
    function obj_to_arr($obj)
    {
        return \GuzzleHttp\json_decode(json_encode($obj), true);
    }
}

// 生成序列号
if (!function_exists('create_serial_number')) {
    function create_serial_number($total, $length = 8, $prefix = '')
    {
        return $prefix . sprintf("%0" . $length . "d", $total);
    }
}

/*
* 下划线转驼峰
*/
if (!function_exists('convert_underline')) {

    function convert_underline($str)
    {
        $str = preg_replace_callback('/([-_]+([a-z]{1}))/i', function ($matches) {
            return strtoupper($matches[2]);
        }, $str);
        return $str;
    }
}
/*
 * 驼峰转下划线
 */
if (!function_exists('hump_to_line')) {

    function hump_to_line($str)
    {
        $str = preg_replace_callback('/([A-Z]{1})/', function ($matches) {
            return '_' . strtolower($matches[0]);
        }, $str);
        return $str;
    }
}


/**
 * 获得默认用户头像
 */
if (!function_exists('get_default_avatar')) {
    function get_default_avatar()
    {
        return out_net_img(config('site.default_avatar'));
    }
}

/**
 * 获得默认图片
 */
if (function_exists('get_default_image')) {
    function get_default_image()
    {
        return out_net_img(config('site.default_image'));
    }
}


/**
 * [httpGet curl get方法]
 * @param  [type] $url [访问链接]
 * @return [type]      [response]
 */
function httpGet($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 500);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $rs = curl_exec($ch);
    curl_close($ch);
    return $rs;
}


if (!function_exists('http_get')) {
    function http_get($url)
    {
        return httpGet($url);
    }
}


/**
 * [httpGet curl post方法]
 * @param  [type] $url [访问链接]
 * @param  [type] $data [post参数--json格式]
 * @return [type]      [response]
 */
function httpPost($url, $data)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $rs = curl_exec($ch);
    curl_close($ch);
    return $rs;
}


if (!function_exists('http_post')) {
    function http_post($url, $data)
    {
        return httpPost($url, $data);
    }
}


if (!function_exists('formatter_time_long')) {
    function formatter_time_long($intTime)
    {
        $day = floor($intTime / 86400);
        $hour = floor(($intTime % 86400) / 3600);
        $minute = floor(($intTime % 86400 % 3600) / 60);
        $second = floor($intTime % 86400 % 3600 % 60);
        return ['str' => $day . '天' . $hour . '时' . $minute . '分' . $second . '秒',
            'day' => $day,
            'hour' => $hour,
            'minute' => $minute,
            'second' => $second,
        ];
    }
}


/**
 * [luhn_create luhn校验码生成]
 * @param  [type] $str [原始字符串]
 * @return [type]      [number]
 */
function luhn_create($str)
{
    $newArr = array();
    //前15或18位倒序存进数组
    for ($i = (strlen($str) - 1); $i >= 0; $i--) {
        $newArr[] = substr($str, $i, 1);
    }

    $arrJiShu = array();    //奇数位*2的积 <9
    $arrJiShu2 = array();   //奇数位*2的积 >9
    $arrOuShu = array();    //偶数位数组
    for ($j = 0; $j < count($newArr); $j++) {
        if (($j + 1) % 2 == 1) {  //奇数位
            if ($newArr[$j] * 2 < 9) {
                $arrJiShu[] = $newArr[$j] * 2;
            } else {
                $arrJiShu2[] = $newArr[$j] * 2;
            }
        } else {
            $arrOuShu[] = $newArr[$j];
        }
    }

    $jishu_child1 = array();    //奇数位*2 >9 的分割之后的数组个位数
    $jishu_child2 = array();    //奇数位*2 >9 的分割之后的数组十位数
    for ($h = 0; $h < count($arrJiShu2); $h++) {
        $jishu_child1[] = $arrJiShu2[$h] % 10;
        $jishu_child2[] = $arrJiShu2[$h] / 10;
    }
    $sumJiShu = 0;        //奇数位*2 < 9 的数组之和
    $sumOuShu = 0;        //偶数位数组之和
    $sumJiShuChild1 = 0;  //奇数位*2 >9 的分割之后的数组个位数之和
    $sumJiShuChild2 = 0;  //奇数位*2 >9 的分割之后的数组十位数之和
    $sumTotal = 0;
    for ($m = 0; $m < count($arrJiShu); $m++) {
        $sumJiShu += $arrJiShu[$m];
    }

    for ($n = 0; $n < count($arrOuShu); $n++) {
        $sumOuShu += $arrOuShu[$n];
    }

    for ($p = 0; $p < count($jishu_child1); $p++) {
        $sumJiShuChild1 += $jishu_child1[$p];
        $sumJiShuChild2 += $jishu_child2[$p];
    }

    //计算总和
    $sumTotal = $sumJiShu + $sumOuShu + $sumJiShuChild1 + $sumJiShuChild2;
    //计算luhn值
    $k = ($sumTotal % 10 == 0) ? 10 : ($sumTotal % 10);
    $luhn = 10 - $k;
    return $luhn;
}


// 生成订单号
if (!function_exists('order_no_create')) {
    function order_no_create()
    {
        $d = date('ymd');
        $dateStr = date('Y-m-d', time());
        $timestamp0 = strtotime($dateStr);
        $s = time() - $timestamp0;
        $s = sprintf("%05d", $s);
        $m = microtime();
        $m = substr($m, 2, 4);
        $order_str = $d . $s . $m;
        $luhn = luhn_create($order_str);
        return $d . $s . $luhn . $m;
    }
}


// 标准ajax返回数据
if (!function_exists('ajax_return')) {
    function ajax_return($code, $msg, $data = [])
    {
        $data = new ArrayObject($data);
        $rs = array('code' => $code, 'msg' => $msg, 'data' => $data, 'time' => time());
        echo json_encode($rs);
        exit();
    }
}


// 替换html富文本里面的图片相对路径地址为绝对路径
if (!function_exists('replace_rich_img_path')) {
    function replace_rich_img_path($content, $prefix = '')
    {
        if (empty($prefix)) {
            $prefix = request()->domain();
        }
        $contentAlter = preg_replace_callback('/(<[img|IMG].*?src=[\'\"])([\s\S]*?)([\'\"])[\s\S]*?/i', function ($match) use ($prefix) {
            if (strstr($match[2], 'http://') == false && strstr($match[2], 'https://') == false)
                return $match[1] . $prefix . $match[2] . $match[3];
            else
                return $match[1] . $match[2] . $match[3];
        }, $content);
        return $contentAlter;
    }
}

if (!function_exists('lb_c')) {
    function lb_c($name)
    {
        return config('custom.x_' . $name);
    }
}
if (!function_exists('date_a')) {
    function date_a($time)
    {
        return date('Y-m-d H:i:s', $time);
    }
}

if (!function_exists('sys_return')) {
    function sys_return($status, $msg = '', $data = [])
    {
        return ['status' => $status, 'msg' => $msg, 'data' => $data];
    }
}

if (!function_exists('formatter_to_key_value')) {
    function formatter_to_key_value($array, $key = 'key', $value = 'value')
    {
        $arr = [];
        foreach ($array as $index => $item) {
            $arr[] = [
                $key => $index . '',
                $value => $item
            ];
        }
        return $arr;
    }
}


//发送邮件
if (!function_exists('send_mail')) {
    function send_mail($title, $message, $to)
    {
        $obj = \app\base\service\email\Email::instance();
        $result = $obj
            ->to($to)
            ->subject($title)
            ->message($message)
            ->send();
    }
}

//生成word
if (!function_exists('create_html_to_word')) {
    function create_html_to_word($html = '', $fileName = '')
    {
        $data = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">' . $html . '</html>';

        $dir = ROOT_PATH . "public/download/";
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $fileName = $dir . $fileName;

        $writefile = fopen($fileName, 'wb') or die("创建文件失败"); //wb以二进制写入

        fwrite($writefile, $data);

        fclose($writefile);

    }
}

if (!function_exists('create_md5_params')) {
    function create_md5_params($params, $code, $codeName = 'secret')
    {
        $params[$codeName] = $code;
        ksort($params);
        $params_str = http_build_query($params);
        return strtoupper(md5($params_str));
    }
}

if (!function_exists('check_md5_params')) {
    function check_md5_params($code)
    {
        $params = input('param.');
        $sign = $params['sign'];
        unset($params['sign']);
        $md5_str = create_md5_params($params, $code);
        return strcmp($md5_str, $sign) == 0 ? true : false;
    }
}

// 是否是json
if (!function_exists('is_json')) {
    function is_json($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}

//获取出生年龄
// $date  '1994-08-05'
if (!function_exists('get_age_month_day')) {
    function get_age_month_day($date)
    {
        if (time() < strtotime($date)) {
            return [
                'year' => 0,
                'month' => 0,
                'day' => 0,
                'full_text' => '日期错误'
            ];
        }
        $bday = new DateTime($date); // 你的出生日
        $today = new Datetime(date('m.d.y'));
        $diff = $today->diff($bday);
        $full_text = '';
        if ($diff->y) {
            $full_text .= $diff->y . '岁';
        }
        if ($diff->m) {
            $full_text .= $diff->m . '月';
        }
        if ($diff->d) {
            $full_text .= $diff->d . '天';
        }
        if (strlen($full_text) == 0) {
            $full_text = '刚出生';
        }
        return [
            'year' => $diff->y,
            'month' => $diff->m,
            'day' => $diff->d,
            'full_text' => $full_text
        ];
    }
}


/**
 * 替换手机号码中间四位数字
 * @param  [type] $str [description]
 * @return [type]      [description]
 */
function hide_phone($str)
{
    $resstr = substr_replace($str, '****', 3, 4);
    return $resstr;
}

if (!function_exists('get_text_length')) {
    function get_text_length($string)
    {
        return (strlen($string) + mb_strlen($string)) / 2;
    }
}
