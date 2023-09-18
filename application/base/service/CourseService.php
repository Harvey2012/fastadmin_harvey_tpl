<?php
/**
 * Created by PhpStorm.
 * User: Harvey
 * E-mail:lenziye@qq.com
 * Date: 2021/6/16
 * Time: 6:01 PM
 */

namespace app\base\service;


use app\common\model\Category;
use app\common\model\User;
use app\common\model\v1\Course;
use app\common\model\v1\Order;
use think\Db;

class CourseService
{

    /**
     * 课程详情
     * @param $courseId
     */
    public function courseDetail($courseId, $uid = null)
    {
        $model = new Course();
        $item = $model->where(['id' => $courseId])->find();
        $item['content'] = replace_rich_img_path($item['content'], request()->domain());
        $cateTag = 'comment_tag';
        $catModel = model('app\common\model\Category');
        $item['commentTags'] = $catModel->order('weigh desc,id desc')->where(['type' => $cateTag])->select();
        $commentServ = new CommentService();
        $item['commentDashboard'] = $commentServ->commentDashboard($courseId, 'course');
        $orderModel = new Order();

//        $order = $orderModel->where(['course_id' => $courseId, 'status' => 1])->find();
//        $item['isBuy'] = empty($order) ? false : true;
        //todo 待删除 9-27
        $item['isBuy'] = false;

        // 浏览课程送积分
        $key = 'score_views_course' . $uid . '_' . $courseId;
        if (config('site.jifen_1') && empty(cache($key) && !empty($uid))) {
            cache($key, 1, 3600 * 24);
            User::score(config('site.jifen_1'), $uid, '浏览课程' . $item['title']);
        }

        return $item;
    }

    /**
     * 课程列表-按分类全部列出
     */
    public function courseAllListByCate($uid)
    {

        $model = new Course();
        $cateModel = new Category();
        // 分类配置
        $cateTag = 'course';
        $list = $cateModel->order('weigh desc,id desc')->where(['type' => $cateTag])->select();
        foreach ($list as &$item) {
            $item['image'] = out_net_img($item['image']);
            $map = ['category' => ['like', '%"' . $item['id'] . '"%']];
            $items = $model
                ->where($map)
                ->order('id desc')
                ->select();
            foreach ($items as &$item2) {
                $item2['cover'] = out_net_img($item2['cover']);
                $isBuy = Db::name('my_course')->where(['userid' => $uid, 'course_id' => $item2['id']])->find();
                $item2['isChecked'] = empty($isBuy) ? false : true;
            }
            $item['course_list'] = $items;
        }
        return $list;
    }


    public function addCourse($courseId, $uid)
    {
        $isBuy = Db::name('my_course')->where(['userid' => $uid, 'course_id' => $courseId])->find();
        if (!empty($isBuy)) return '请勿重复添加';
        $res = Db::name('my_course')->insert(['userid' => $uid, 'course_id' => $courseId, 'create_time' => time()]);
        return $res == 1 ? true : false;
    }

    public function removeCourse($courseId, $uid)
    {
        $isBuy = Db::name('my_course')->where(['userid' => $uid, 'course_id' => $courseId])->find();
        if (empty($isBuy)) return '数据不存在';
        $res = Db::name('my_course')->where(['userid' => $uid, 'course_id' => $courseId])->delete();
        return $res == 1 ? true : false;
    }

}