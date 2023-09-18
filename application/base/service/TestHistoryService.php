<?php
/**
 * Created by PhpStorm.
 * User: Harvey
 * E-mail:lenziye@qq.com
 * Date: 2021/6/6
 * Time: 3:39 PM
 */

namespace app\base\service;

use app\api\model\v1\TestForm;

class TestHistoryService
{

    /**
     * 练习/真题/模拟  做题历史
     * @param $customerId
     * @param $param
     * @param TestForm $model
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function testHistoryList($customerId, $param): Array
    {
        $model = new  TestForm;
        $page = $param['page'];
        $pageSize = $param['pageSize'];
        $map = ['course_id' => $param['courseId'], 'userid' => $customerId];
        if (!empty($param['type'])) {
            $map['type'] = $param['type'];
        }
        $items = $model
            ->where($map)
            ->order('id desc')
            ->page($page, $pageSize)->select();
        $hasMore = $model
            ->where($map)
            ->order('id desc')
            ->page($page + 1, $pageSize)->count();
        $hasMore = $hasMore ? true : false;
        foreach ($items as $index => &$item) {
            $model->_formatterData($item);
        }
        return compact('items', 'hasMore');
    }

}