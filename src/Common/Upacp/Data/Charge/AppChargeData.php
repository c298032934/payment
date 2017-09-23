<?php

namespace Payment\Common\Upacp\Data\Charge;

/**
 * 银联APP支付数据处理
 */
class AppChargeData extends ChargeBaseData
{

    /**
     * 发送请求
     */
    protected function checkDataParam()
    {
        parent::checkDataParam();
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