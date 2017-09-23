<?php

namespace Payment\Trans;

use Payment\Common\Ali\AliBaseStrategy;
use Payment\Common\Ali\Data\TransData;
use Payment\Common\PayException;
use Payment\Config;

/**
 * 支付宝转账操作
 * Class AliTransfer
 * @package Payment\Trans
 */
class AliTransfer extends AliBaseStrategy
{
    protected $method = 'alipay.fund.trans.toaccount.transfer';

    /**
     * 获取支付对应的数据完成类
     * @return string
     */
    public function getBuildDataClass()
    {
        $this->config->method = $this->method;
        return TransData::class;
    }

    /**
     * 处理支付宝的返回值并返回给客户端
     * @param array $data
     * @return array|string
     * @throws PayException
     */
    protected function retData(array $data)
    {
        $reqData = parent::retData($data);

        try {
            $retData = $this->sendReq($reqData);
        } catch (PayException $e) {
            throw $e;
        }

        return $this->createBackData($retData);
    }

    /**
     * 处理返回的数据
     * @param array $data
     * @return array
     * @author helei
     */
    protected function createBackData(array $data)
    {
        if ($this->config->returnRaw) {
            $retData['channel'] = Config::ALI_TRANSFER;
            return $retData;
        }

        if ($data['code'] !== '10000') {
            return ['is_success' => 'F', 'error' => $data['sub_msg'], 'channel' => Config::ALI_TRANSFER];
        }

        $retData = [
            'is_success' => 'T',
            'response' => [
                'trans_no' => $data['out_biz_no'],// 商户转账唯一订单号
                'transaction_id' => $data['order_id'],// 支付宝转账单据号
                'pay_date' => $data['pay_date'],// 支付时间
                'channel' => Config::ALI_TRANSFER,
            ],
        ];

        return $retData;
    }
}
