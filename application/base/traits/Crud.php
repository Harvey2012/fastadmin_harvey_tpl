<?php

namespace app\base\traits;

use think\Exception;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\Request;

/**
 * Trait Crud
 * @author Harvey Liu
 * @date 2020-01-09 晚上7点17
 * @package app\api\traits
 */
trait Crud
{

    // excludeFields 过滤字段

    /**
     * 排除前台提交过来的字段
     * @param $params
     * @return array
     */
    protected function preExcludeFields($params)
    {
        if (is_array($this->excludeFields)) {
            foreach ($this->excludeFields as $field) {
                if (key_exists($field, $params)) {
                    unset($params[$field]);
                }
            }
        } else {
            if (key_exists($this->excludeFields, $params)) {
                unset($params[$this->excludeFields]);
            }
        }
        return $params;
    }


    /**
     * 查看
     */
    public function index()
    {
        $page = input('get.page', 1);
        $pageSize = input('get.pageSize', 20);
        if ($this->customerId) {
            $this->indexMap['userid'] = $this->customerId;
        }
        if ($this->queryAll) {
            $data = $this->model->where($this->indexMap)->select();
        } else {
            $res = $this->model
                ->where($this->indexMap)
                ->order('id desc')
                ->page($page, $pageSize)->select();
            if (!$res) {
                $this->error('没有更多了');
            }
            $hasMore = $this->model
                ->where($this->indexMap)
                ->page($page + 1, $pageSize)->count();
            $data['hasMore'] = $hasMore ? true : false;
            $data['list'] = $res;
        }
        $this->success('ok', convert_hump($data));
    }


    /**
     * 添加
     */
    public function save(Request $request)
    {
        $params = $request->post();
        $this->_param_require_check($params, $this->requireMustField, true);
        $params = $this->preExcludeFields($params);
        $data = $params;
        if ($this->customerId) {
            $data['userid'] = $this->customerId;
        }
        $result = false;
        Db::startTrans();
        try {
            //是否采用模型验证
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                $this->model->validateFailException(true)->validate($validate);
            }
            $result = $this->model->allowField(true)->save($data);
            Db::commit();
            if ($result) {
                $this->success('添加成功', ['info' => convert_hump($this->model->where('id', $this->model->id)->find())]);
            } else {
                $this->error('添加失败');
            }
        } catch (ValidateException $e) {
            Db::rollback();
            $this->error($e->getMessage());
        } catch (PDOException $e) {
            Db::rollback();
            $this->error($e->getMessage());
        } catch (Exception $e) {
            print_log('异常', $e->getMessage());
            Db::rollback();
            $this->error('添加失败' . $e->getMessage());
        }
        if ($result !== false) {
            $this->success();
        } else {
            $this->error(__('添加失败'));
        }
    }


    /**
     * 修改
     */
    public function update()
    {
        $param = input('post.');
        $require = ['id'];
        $this->_param_require_check($param, $require, true);
        $data = $param;
        $id = $data['id'];
        $info = $this->model->where(['id' => $id, 'userid' => $this->customerId])->find();
        if (empty($info)) {
            ajax_return(403, '无权操作');
        }
        Db::startTrans();
        try {
            $re = $this->model->update($data);
            Db::commit();
            if ($re) {
                $this->success('更新成功');
            } else {
                $this->error('更新失败');
            }
        } catch (Exception $e) {
            Db::rollback();
            print_log('异常', $e->getMessage());
            $this->error('修改失败');
        }
    }


    /**
     * 删除
     *
     * @param  int $id
     * @return \think\Response
     */
    public function delete()
    {
        $param = input('post.');
        $require = ['id'];
        $this->_param_require_check($param, $require, true);
        $id = $param['id'];
        $info = $this->model->where(['id' => $id, 'userid' => $this->customerId])->find();
        if (empty($info)) {
            ajax_return(403, '无权操作');
        }
        Db::startTrans();
        try {
            $result = $info->delete();
            Db::commit();
            if ($result) {
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
        } catch (Exception $e) {
            Db::rollback();
            print_log('异常', $e->getMessage());
            $this->error('删除失败');
        }
    }
}
