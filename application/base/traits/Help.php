<?php

namespace app\base\traits;

trait Help
{


    /** 参数检查
     * Harvey
     * [_param_require_check 必传参数检测]
     * @param  [type] $require  [必传参数集合]
     * @param  [type] $haystack [对比字段集合]
     * @param  [type] $checkEmpty [是否检查为空]
     * @return [type]           [json/NULL]
     */
    public function _param_require_check($haystack, $require, $checkEmpty = false)
    {
        $new = [];
        foreach ($require as $key => $value) {

            if (!isset($haystack[$value])) {
                ajax_return(0, $value . '参数缺失');
            }
            if ($checkEmpty) {
                if (empty($haystack[$value]) && $haystack[$value] !== 0) {
                    ajax_return(0, $value . '参数为空');
                }
            }
            $new[] = $haystack[$value];
            unset($haystack[$value]);
        }
        return array_merge($new, $haystack);
    }

    /**
     * [_param_format 格式化对象参数为数组]
     * @param  [type] $param [单一参数,obj]
     * @return [type]       [json]
     */
    public function _param_format($param)
    {
        return json_decode(htmlspecialchars_decode($param), true);
    }

    /**
     * [_param_illegal_check 必传参数检测]
     * @param  [type] $require  [必传参数集合]
     * @param  [type] $haystack [对比字段]
     * @return [type]           [json/NULL]
     * @Author Harvey
     * @Date   18/12/25
     */
    public function _param_illegal_check($require, $haystack)
    {
        foreach ($require as $key => $value) {
            if (!is_bool(stripos($haystack, $value))) {
                ajax_return(103, '不能包含`' . $value . '`');
            }
        }
    }

    /**
     * 对数组键名进行驼峰格式化
     * @param array $data
     * @return array
     */
    protected function convertHump($data)
    {
        $data = obj_to_arr($data);
        $result = [];
        foreach ($data as $key => $item) {
            if (is_array($item) || is_object($item)) {
                $result[convert_underline($key)] = $this->convertHump((array)$item);
            } else {
                $result[convert_underline($key)] = $item;
            }
        }
        return $result;
    }

    public function ajaxReturn($res, $hump = false)
    {
        if (is_bool($res)) {
            if ($res) {
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
        } elseif (is_string($res)) {
            if (empty($res)) {
                $this->success('');
            }
            $this->error($res);
        } elseif (is_array($res)) {
            if ($hump) {
                $res = $this->convertHump($res);
            }
            $this->success('success', $res);
        } elseif (is_object($res)) {
            if ($hump) {
                $res->data = $this->convertHump($res->data);
            }
            $this->result($res->msg, $res->data, $res->code);
        } else {
            $this->success('success');
        }
    }

    /**
     * @param string $requestType
     * @param array $require
     * @param bool $checkEmpty
     * @param bool $getField
     * @return array
     */
    public function checkParams($requestType = 'get', $require = [], $checkEmpty = false, $getField = false)
    {
        $param = input($requestType . '.');
        $res = $this->_param_require_check($param, $require, $checkEmpty);
        if ($getField) {
            return $res;
        } else {
            return $param;
        }
    }
}
