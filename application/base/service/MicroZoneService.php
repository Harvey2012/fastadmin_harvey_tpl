<?php
/**
 * Created by PhpStorm.
 * User: Harvey
 * E-mail:lenziye@qq.com
 * Date: 2021/10/23
 * Time: 4:41 PM
 */

namespace app\base\service;


use app\common\controller\Response;
use think\Db;

class MicroZoneService
{

    protected $model = null;

    public function __construct()
    {
        $this->model = new \app\admin\model\v1\ArticleCourseCollect();
    }

    /**
     * 搜藏
     * @param $thrid_id
     * @param $type
     * @param $comment
     */
    public function collect($third_id, $type, $comment, $customer)
    {
        $response = new Response();
        $param = input('post.');
        $customerId = $customer['id'];
//        $item = $this->model->where(['third_id' => $third_id, 'type' => $type])->find();
//        if (empty($item)) {
        $re = $this->model->insert([
            'type' => $type,
            'third_id' => $third_id,
            'create_time' => time(),
            'uid' => $customer['id'],
            'comment' => $comment
        ]);
//        } else {
//            $re = $this->model->where([
//                'type' => 1,
//                'problem_id' => $param['problemId'],
//            ])->delete();
//        }
        if ($re) {
            $response->msg = '操作成功';
        }
        return $response;
    }

    /**
     * 列表
     * @param $where
     * @param $page
     * @param $pageSize
     * @param $customer
     * @return array
     */
    public function lst($where, $page, $pageSize, $customer)
    {
        $map = [];
        $map = array_merge($map, $where);
        $items = $this->model
            ->where($map)
            ->order('id desc')
            ->page($page, $pageSize)->select();
        $hasMore = $this->model
            ->where($map)
            ->order('id desc')
            ->page($page + 1, $pageSize)->count();
        $hasMore = $hasMore ? true : false;
        foreach ($items as $index => &$item) {
            $user = Db::name('user')->find($item['uid']);
            $item['user_nickname'] = $user['nickname'];
            $item['user_avatar'] = $user['avatar'];
            if ($item['type'] == 1) {
                $source = Db::name('course')->find($item['third_id']);
            } else {
                $source = Db::name('articles')->find($item['third_id']);
            }
            $item['source_cover'] = out_net_img($source['cover']);
            $item['source_title'] = $source['title'];
            $item['has_dianzan'] = DianzanService::getInstance()
                ->getHasDianzan($customer['id'], $item['id'], 1);
//            if ($customer) {
//                $record = Db::name('comment_like_rel')->where([
//                    'user_id' => $customer['id'],
//                    'comment_id' => $item['id']
//                ])->find();
//                if ($record) $item['has_like'] = true;
//            }
        }
        return compact('items', 'hasMore', 'page');
    }


    // 删除
    public function del($id, $uid = '')
    {
        $response = new Response();
        $item = $this->model->find($id);
        if ($uid) {
            if ($item->uid != $uid) {
                $response->msg = '无权操作';
                $response->code = 0;
                return $response;
            }
        }
        $re = $item->delete();
        if ($re) {
            $response->msg = '操作成功';
        } else {
            $response->msg = '操作失败';
            $response->code = 0;
        }
        return $response;
    }

}