<?php
/**
 * Created by PhpStorm.
 * User: Harvey
 * E-mail:lenziye@qq.com
 * Date: 2021/10/24
 * Time: 5:27 PM
 */

namespace app\base\service;

use app\common\controller\Response;
use app\common\controller\ApiResponse;
use think\Db;
use Yansongda\Pay\Exceptions\InvalidArgumentException;


class DianzanService extends BaseService
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
        $this->model = new \app\admin\model\v1\Dianzan();
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
     * 点赞
     * @param $id
     * @param $userid
     * @return ApiResponse
     */
    public function add($third_id, $userid, $type, $set_inc_table = '')
    {
        $response = new ApiResponse();
        $record = $this->model->where(['third_id' => $third_id, 'uid' => $userid, 'type' => $type])->find();
        if ($record) {
            $record_id = $record['id'];
            $record->delete();
            if (!empty($set_inc_table)) {
                Db::name($set_inc_table)->where(['id' => $third_id])->setDec('dianzan_num');
            }
            $dianzan = false;

            $response->msg = '取消成功';
        } else {
            $record_id = $this->model->insertGetId(
                ['third_id' => $third_id,
                    'create_time' => time(),
                    'uid' => $userid,
                    'type' => $type
                ]);
            if (!empty($set_inc_table)) {
                Db::name($set_inc_table)->where(['id' => $third_id])->setInc('dianzan_num');
            }
            $dianzan = true;
            $response->msg = '点赞成功';
        }
        $response->data = compact('record_id', 'dianzan');
        return $response;
    }

    /**
     * 是否点赞了
     * @param $uid
     * @param $third_id
     * @param $type
     */
    public function getHasDianzan($uid, $third_id, $type)
    {
        $record = $this->model->where(['third_id' => $third_id, 'uid' => $uid, 'type' => $type])->find();
        return !empty($record) ? true : false;
    }
}