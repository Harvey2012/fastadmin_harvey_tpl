<?php
/**
 * Created by PhpStorm.
 * User: Harvey
 * E-mail:lenziye@qq.com
 * Date: 2021/6/16
 * Time: 10:43 PM
 */

namespace app\base\service;

use app\common\controller\Response;
use app\common\model\User;
use app\common\model\v1\Comments;
use think\Db;

class CommentService
{

    public function courseComment($param, $uid)
    {
        $courseId = $param['id'];
        $content = $param['content'];
        $star = $param['star'];
        $tagIds = $param['tag_id'];
        if (is_array($tagIds)) {
            $tagIds = implode(',', $tagIds);
        }
        $ext['star'] = $star;
        $ext['tag'] = $tagIds;
        $postable = empty($param['table']) ? 'course' : $param['table'];
        if (isset($param['comment_id'])) $ext['parent_id'] = $param['comment_id'];
        if ($param['table'] == 'articles') {
            $article = Db::name('articles')->find($param['id']);
            // 评论文章送积分
            $key = 'score_comment_article' . $uid . '_' . $param['id'];
            if (config('site.pinglun_score') && empty(cache($key))) {
                cache($key, 1, 3600 * 24);
                User::score(config('site.pinglun_score'), $uid, '评论文章' . $article['title']);
            }
        }
        return $this->comment($postable, $courseId, $uid, $content, $ext);
    }

    public function comment($postable, $postId, $uid, $content, $ext = [])
    {
        $data['post_table'] = $postable;
        $data['post_id'] = $postId;
        $data['uid'] = $uid;
        $data['content'] = $content;
        $data['create_time'] = time();
        $data = array_merge($data, $ext);
        $model = new Comments();
        return $model->insertGetId($data);
    }

    // 删除评论
    public function del($id, $uid = '')
    {
        $item = Comments::get($id);
        if ($uid) {
            if ($item->uid != $uid) return '无权操作';
        }
        $re = $item->delete();
        return $re == 1 ? true : false;
    }

    //给评论点赞
    public function commentLike($id, $uid)
    {
        $response = new Response();
        $comment = Comments::where(['id' => $id])->find();
        if (!$comment) {
            $response->msg = '评论不存在';
            return $response;
        }
        $record = Db::name('comment_like_rel')->where([
            'user_id' => $uid,
            'comment_id' => $id
        ])->find();
        if ($record) {
            Db::name('comment_like_rel')->where([
                'user_id' => $uid,
                'comment_id' => $id
            ])->delete();
            Db::name('comments')->where(['id' => $id])->setDec('like_num');
            $response->msg = '取消成功';
        } else {
            Db::name('comment_like_rel')->insert(
                ['user_id' => $uid,
                    'comment_id' => $id
                ]);
            Db::name('comments')->where(['id' => $id])->setInc('like_num');
            $response->data = '';
            $response->msg = '点赞成功';
        }
        return $response;
    }

    // 通过评论
    public function checkCommentStatus()
    {

    }

    // 展示评论列表
    public function lst($where, $page, $pageSize, $customerId = null)
    {
        $model = new Comments();
        $map = [];
//        if ($customerId) {
//            $map['uid'] = $customerId;
//        }
        $map = array_merge($map, $where);
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
            $user = Db::name('user')->find($item['uid']);
            $item['user_nickname'] = $user['nickname'];
            $item['user_avatar'] = $user['avatar'];
            if ($item['parent_id']) {
                $comment2 = Db::name('comments')->where(['id' => $item['parent_id']])->field('full_name,uid,content')->find();
                $user = Db::name('user')->find($comment2['uid']);
                $comment2['user_nickname'] = $user['nickname'];
                $comment2['user_avatar'] = $user['avatar'];
                $item['parent_comment'] = $comment2;
            }
            $item['has_like'] = false;
            if ($customerId) {
                $record = Db::name('comment_like_rel')->where([
                    'user_id' => $customerId,
                    'comment_id' => $item['id']
                ])->find();
                if ($record) $item['has_like'] = true;
            }
        }
        return compact('items', 'hasMore');
    }


    // 课程评论统计
    public function commentDashboard($courseId, $table)
    {
        $model = new Comments();
        $map = ['post_table' => $table,
            'post_id' => $courseId];
        $totalStar = $model->where($map)->avg('star');
        $totalStar = round($totalStar, 1);
        $totalComment = $model->where($map)->count();
        $cateTag = 'comment_tag';
        $catModel = model('app\common\model\Category');
        $commentTags = $catModel->order('weigh desc,id desc')->where(['type' => $cateTag])->select();
        $allTag = [];
        foreach ($commentTags as $index => $tag) {
            $count = $model->where($map)->where("FIND_IN_SET('{$tag['id']}',tag)")->count();
            $allTag[] = [
                'count' => $count,
                'id' => $tag['id']
            ];
        }
        return compact('totalStar', 'totalComment', 'allTag');
    }
}