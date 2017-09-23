<?php

namespace Payment\Common\Upacp\Data\Charge;

use Payment\Common\PayException;

/**
 * 银联WEB支付数据处理
 */
class WebChargeData extends ChargeBaseData
{

    /**
     * 发送请求
     */
    protected function checkDataParam()
    {
        parent::checkDataParam();

        $returnUrl = $this->returnUrl;
        // 检查同步通知地址
        if (empty($returnUrl)) {
            throw new PayException('同步通知的url必须提供.');
        }
    }

    /**
     * 请求数据
     */
    protected function getReqData()
    {
        $reqData = [
            'merId' => $this->merId,
            'orderId' => $this->order_no,
            'txnTime' => $this->txnTime,
            'txnAmt' => $this->amount,
            'payTimeout' => $this->timeout_express,
            'orderDesc' => $this->subject,
            'reqReserved' => $this->return_param,
        ];

        return $reqData;
    }
}