<?php
/**
 * Created by PhpStorm.
 * User: Harvey
 * E-mail:lenziye@qq.com
 * Date: 2021/10/26
 * Time: 3:42 PM
 */

namespace app\base\service;


use app\common\controller\ApiResponse;
use app\common\model\User;

class ScoreService extends BaseService
{

    public function __construct()
    {
//        $this->model = new \app\admin\model\v1\Daka();
    }


    /**
     * 送积分
     * @param $configName
     * @param $customerId
     */
    public function addJifen($configName, $customerId)
    {
        $response = new ApiResponse();
        switch ($configName) {
            case 'fenxiang_jifen';
                $demo = '分享送积分';
                break;
            case 'fenxiang_haibao_jifen';
                $demo = '分享海报送积分';
                break;
            case 'zixunkefu_jifen';
                $demo = '咨询送积分';
                break;
            case 'zice_jifen';
                $demo = '自测送积分';
                break;
        }
        $jifen = config('site.' . $configName);
        $key = "score_tag_{$configName}_{$customerId}";
        if ($jifen > 0 && empty(cache($key))) {
            User::score($jifen, $customerId, $demo);
            cache($key, 1, 3600 * 24);
            $response->msg = '奖励成功';
        } else {
            $response->msg = '今日已奖励';
        }
        return $response;
    }

}