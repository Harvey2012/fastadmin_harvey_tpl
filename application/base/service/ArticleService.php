<?php
/**
 * Created by PhpStorm.
 * User: Harvey
 * E-mail:lenziye@qq.com
 * Date: 2021/6/16
 * Time: 6:01 PM
 */

namespace app\base\service;


use app\admin\model\v1\ArticleDianzan;
use app\admin\model\v1\Articles;
use app\common\controller\ApiResponse;
use app\common\controller\Response;
use app\common\model\Category;
use app\common\model\User;
use app\common\model\v1\Course;
use app\common\model\v1\Order;
use think\Db;

class ArticleService
{

    /**
     * 详情
     * @param $courseId
     */
    public function articleDetail($Id, $uid = null)
    {
        $model = new Articles();
        $item = $model->where(['id' => $Id])->find();
        $item['content'] = replace_rich_img_path($item['content'], request()->domain());
        $cateTag = 'comment_tag';
        $catModel = model('app\common\model\Category');
        $item['commentTags'] = $catModel->order('weigh desc,id desc')->where(['type' => $cateTag])->select();
        $commentServ = new CommentService();
        $item['commentDashboard'] = $commentServ->commentDashboard($Id, 'articles');
        $orderModel = new Order();
        $order = $orderModel->where(['course_id' => $Id, 'status' => 1])->find();
        $item['isBuy'] = empty($order) ? false : true;

        if ($uid) {
            $articleModel = new ArticleDianzan();
            $record = $articleModel->where(['article_id' => $Id, 'userid' => $uid])->find();
            $item['dianzan'] = empty($record) ? false : true;
        } else {
            $item['dianzan'] = false;
        }
        $item->setInc('views_num');

        $item['showBuyBtnJifen'] = false;
        $item['showBuyBtnVip'] = false;
        $item['showBuyBtnSingle'] = false;
        if (!empty($item['buy_types'])) {
            foreach ($item['buy_types'] as $index => $buyType) {
                if ($buyType == 1) {
                    $item['showBuyBtnJifen'] = true;
                }
                if ($buyType == 2) {
                    $item['showBuyBtnVip'] = true;
                }
                if ($buyType == 3) {
                    $item['showBuyBtnSingle'] = true;
                }
            }
        }
        return $item;
    }


    /**
     * 给文章点赞
     */
    public function dianzan($id, $userid)
    {
        $response = new Response();
        $articleModel = new ArticleDianzan();
        $model = new Articles();
        $record = $articleModel->where(['article_id' => $id, 'userid' => $userid])->find();
        if ($record) {
            $record->delete();
            $model->where(['id' => $id])->setDec('dianzan_num');
            $response->msg = '取消成功';
        } else {
            $record_id = $articleModel->insertGetId(
                ['article_id' => $id,
                    'create_time' => time(),
                    'userid' => $userid
                ]);
            $model->where(['id' => $id])->setInc('dianzan_num');
            $response->data = compact('record_id');
            $response->msg = '点赞成功';
        }
        return $response;
    }

    /**
     * 积分购买文章
     * @param $customer
     * @param $article_id
     * @param ApiResponse $response
     * @return ApiResponse
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function jifenBuy($article_id,$customer)
    {
        $response = new ApiResponse;
        $item = Db::name('articles')->find($article_id);
        if (empty($item)) {
            $response->msg = '文章不存在';
            return $response;
        }
        if ($customer['score'] < $item['dikou_jifen']) {
            $response->msg = "积分不足";
            return $response;
        }
        User::score(-$item['dikou_jifen'], $customer['id'], '积分抵扣文章《' . $item['title'] . "》");
        Db::name('buy_rel')->insert(
            ['uid' => $customer['id'],
                'obj_id' => $article_id,
                'type' => 1
            ]
        );
        return $response;
    }

}