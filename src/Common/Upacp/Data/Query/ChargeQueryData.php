<?php

namespace Payment\Common\Upacp\Data\Query;

use Payment\Common\PayException;
use Payment\Common\Upacp\Data\UpacpBaseData;

/**
 * 银联APP支付数据处理
 * @property string $merId         商户代码
 * @property string $out_trade_no  订单号
 * @property string $txnTime       订单发送时间，格式为YYYYMMDDhhmmss
 */
class ChargeQueryData extends UpacpBaseData
{

    /**
     * 发送请求
     */
    protected function checkDataParam()
    {
        $orderNo = $this->out_trade_no;

        // 检查订单号是否合法
        if (empty($orderNo) || mb_strlen($orderNo) > 40) {
            throw new PayException('订单号不能为空，并且长度不能超过40位');
        }
    }

    /**
     * 请求数据
     */
    protected function getReqData()
    {
        $reqData = [
            'merId' => $this->merId,
            'orderId' => $this->out_trade_no,
            'txnTime' => $this->txnTime,
        ];

        return $reqData;
    }
}