<?php

namespace app\base\service;


use think\Exception;
use addons\epay\library\Service;
use addons\epay\library\Service as EpayService;

class PayService
{

    public function pay_demo()
    {
        $notifyurl = request()->domain() . '/api/v1/order/create_order_notify/paytype/wechat';
        $returnurl = '';
        $order_price = 0;
        $order_title = 0;
        $orderNo = '';
        $openid = '';
        $pay = \addons\epay\library\Service::submitOrder($order_price, $orderNo, 'wechat',
            $order_title, $notifyurl, $returnurl, 'miniapp', $openid);
        ajax_return(1, '', $pay);
    }

    /**
     * 微信申请退款
     * @param $orderNo
     * @param $out_refund_no
     * @param $notify_url
     * @param $orderMoney
     * @param $refundMoney
     * @param string $reason
     * @throws \Yansongda\Pay\Exceptions\GatewayException
     * @throws \Yansongda\Pay\Exceptions\InvalidArgumentException
     * @throws \Yansongda\Pay\Exceptions\InvalidSignException
     */
    public function wxappPayApplyRefund($orderNo, $out_refund_no, $notify_url, $orderMoney, $refundMoney, $reason = '系统退款')
    {
        $pay = \Yansongda\Pay\Pay::wechat(Service::getConfig('wechat'));
        $params =
            [
                'out_trade_no' => $orderNo,
                'out_refund_no' => $out_refund_no,
                'total_fee' => $orderMoney,
                'refund_fee' => $refundMoney
//                'notify_url' => $notify_url,
//                'amount' => [
//                    'currency' => 'CNY',
//                    'refund' => $refundMoney,
//                    'total' => $orderMoney
//                ],
//                'reason' => $reason
        ];
        return $pay->refund($params);
    }

    /**
     * 付款个微信用户
     * @param $openid
     * 插件有改动 支持transfer
     */
    public function payWechatUser($openid, $totalPay, $withdrawNotifyUrl = '')
    {
        $order_no = order_no_create() . order_no_create();
        $method = 'transfer';
        $title = '提现';
        $type = 'wechat';
        //回调链接
//        $notifyurl = request()->domain() . '/api/corntab/withdraw/wxPayNotify/paytype/' . $type;
        $notifyurl = $withdrawNotifyUrl;
        $returnurl = '';
        $paySetting = EpayService::submitOrder($totalPay, $order_no, $type,
            $title, $notifyurl, $returnurl, $method, $openid);
        return json_decode($paySetting, true);

//        {
//            "return_code": "SUCCESS",
//    "return_msg": [],
//    "mch_appid": "wx1bdd6acc28b76bf0",
//    "mchid": "1602979724",
//    "nonce_str": "JzVsULJOGzCFMMfc",
//    "result_code": "SUCCESS",
//    "partner_trade_no": "22021479054731652202147905473165",
//    "payment_no": "10101239582892202140559758711525",
//    "payment_time": "2022-02-14 21:57:35"
//}
    }
}
