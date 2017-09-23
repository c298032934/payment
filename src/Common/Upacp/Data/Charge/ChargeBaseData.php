<?php

namespace Payment\Common\Upacp\Data\Charge;

use Payment\Config;
use Payment\Common\PayException;
use Payment\Common\Upacp\Data\UpacpBaseData;

/**
 * 银联支付基础数据处理
 * @property string $merId           商户代码
 * @property string $order_no        订单号
 * @property string $txnTime         订单发送时间，格式为YYYYMMDDhhmmss
 * @property string $amount          金额
 * @property string $subject         商品名称
 * @property int|string $timeout_express 付款截止时间
 * @property string $return_param    返回参数
 */
abstract class ChargeBaseData extends UpacpBaseData
{

    /**
     * 发送请求
     */
    protected function checkDataParam()
    {
        $orderNo = $this->order_no;
        $amount = $this->amount;
        $subject = $this->subject;
        $notifyUrl = $this->notifyUrl;

        // 检查订单号是否合法 8-40位
        if (empty($orderNo) || mb_strlen($orderNo) > 40 || mb_strlen($orderNo) < 8) {
            throw new PayException('订单号不能为空，并且长度不能小于8位并大于40位');
        }

        // 检查金额不能低于0.01
        if (bccomp($amount, Config::PAY_MIN_FEE, 2) === -1) {
            throw new PayException('支付金额不能低于 ' . Config::PAY_MIN_FEE . ' 元');
        }

        // 检查 商品名称
        if (empty($subject)) {
            throw new PayException('必须提供商品名称');
        }

        $timeExpire = $this->timeout_express;
        if (!empty($timeExpire)) {
            $this->timeout_express = date('YmdHis', $this->timeout_express);
        } else {
            $this->timeout_express = '';
        }

        // 银联使用的单位位分.此处进行转化
        $this->amount = bcmul($amount, 100, 0);

        // 检查回调地址
        if (empty($notifyUrl)) {
            throw new PayException('异步通知的url必须提供.');
        }
    }
}