<?php

namespace app\base\service;

use app\common\model\v1\Order;
use think\Controller;
use think\Db;
use think\Exception;
use think\Request;
use addons\epay\library\Service;

class OrderService
{

    //        {"appid":"wx137375c86d99374f",
//            "bank_type":"OTHERS",
//            "cash_fee":"1",
//            "fee_type":"CNY",
//            "is_subscribe":"N",
//            "mch_id":"1610500310",
//            "nonce_str":"WoVQkBTLbC9Xqs2P",
//            "openid":"oxkWB5SxZ8V5F1uYzlt9QYwon6nw",
//            "out_trade_no":"2106097997364704",
//            "result_code":"SUCCESS",
//            "return_code":"SUCCESS",
//            "sign":"3ECBF3D3E9AE8182CD0BD46ACA9E15AF",
//            "time_end":"20210609221303","total_fee":"1",
//            "trade_type":"JSAPI",
//            "transaction_id":"4200001152202106094416138522"}

    /**
     * 购买课程
     * @param $data
     *
     */
    public function payOrderOk($param)
    {
        if ($param['result_code'] == 'SUCCESS') {
            $model = new Order;
            $order = $model->where('order_no', $param['out_trade_no'])->find();
            if (!$order) {
                return false;
            }
            if ($order['status'] == 0) {
                $order->status = 1;
                $order->pay_result = json_encode($param);
                $order->save();
            }
        }
    }
}
