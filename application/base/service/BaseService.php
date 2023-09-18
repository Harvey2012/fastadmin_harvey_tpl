<?php
/**
 * Created by PhpStorm.
 * User: Harvey
 * E-mail:lenziye@qq.com
 * Date: 2021/10/24
 * Time: 5:28 PM
 */

namespace app\base\service;


class BaseService
{
    /**
     * Instance.
     *
     */
    private static $instance;

//    类型 1 微站

    protected $model = null;

    public function __construct()
    {
//        $this->model = new \app\admin\model\v1\Daka();
    }

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}