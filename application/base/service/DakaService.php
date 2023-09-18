<?php
/**
 * Created by PhpStorm.
 * User: Harvey
 * E-mail:lenziye@qq.com
 * Date: 2021/10/25
 * Time: 2:06 PM
 */

namespace app\base\service;


use app\common\controller\ApiResponse;
use app\common\model\User;

class DakaService extends BaseService
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
        $this->model = new \app\admin\model\v1\Daka();
    }

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            print_log('run 1');
            self::$instance = new self();
        }
        print_log('run 2');
        return self::$instance;
    }

    /**
     * 打卡
     * @param $customerId
     * @return ApiResponse
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function daka($customerId)
    {
        $apiResponse = new ApiResponse();
        $sign = $this->model->where([
            'uid' => $customerId,
            'date' => date("Y-m-d")
        ])->find();

        if ($sign) {
            $apiResponse->code = 0;
            $apiResponse->msg = '已签到过，请勿重复操作';
        } else {
            $this->model->insert(['uid' => $customerId, 'date' => date('Y-m-d'), 'create_time' => time()]);
            $apiResponse->msg = '签到成功';
            if (config('site.clockin_score') > 0) {
                User::score(config('site.clockin_score'), $customerId, date('Y-m-d') . '打卡送积分');
            }
        }
        return $apiResponse;
    }

    /**
     * 打卡面板
     */
    public function dakaDashboard($uid)
    {
        // 累计打卡天数
        $leiji = $this->model->where(['uid' => $uid])->count();
        // 今日打卡人数
        $leijiToday = $this->model->where(['date' => date('Y-m-d')])->count();
        // 今日是否已打卡
        $a = $this->model->where(['date' => date('Y-m-d'), 'uid' => $uid])->count();
        $todayIsDaka = $a == 1 ? true : false;

        return compact('leiji', 'leijiToday', 'todayIsDaka');

    }

}