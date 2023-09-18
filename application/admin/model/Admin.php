<?php

namespace app\admin\model;

use think\Model;
use think\Session;

class Admin extends Model
{

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

//    protected static function init()
//    {
//        self::beforeInsert(function ($row) {
//            $changed = $row->getChangedData();
//            if (!empty($changed['workrange1'])) {
//                $row->workrange = $changed['workrange1'] . ' - ' . $changed['workrange2'];
//            }
//
//        });
//
//
//        self::beforeUpdate(function ($row) {
//            $changed = $row->getChangedData();
//            if (!empty($changed['workrange1'])) {
//                $row->workrange = $changed['workrange1'] . ' - ' . $changed['workrange2'];
//            }
//        });
//    }

    /**
     * 重置用户密码
     * @author baiyouwen
     */
    public function resetPassword($uid, $NewPassword)
    {
        $passwd = $this->encryptPassword($NewPassword);
        $ret = $this->where(['id' => $uid])->update(['password' => $passwd]);
        return $ret;
    }

    // 密码加密
    protected function encryptPassword($password, $salt = '', $encrypt = 'md5')
    {
        return $encrypt($password . $salt);
    }

}
