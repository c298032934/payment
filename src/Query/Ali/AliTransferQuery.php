<?php

namespace Payment\Query\Ali;

use Payment\Common\Ali\AliBaseStrategy;
use Payment\Common\Ali\Data\Query\TransferQueryData;
use Payment\Common\PayException;
use Payment\Config;
use Payment\Utils\ArrayUtil;

/**
 * 查询转账订单的情况
 * Class AliTransferQuery
 * @package Payment\Query\Ali
 */
class AliTransferQuery extends AliBaseStrategy
{
    protected $method = 'alipay.fund.trans.order.query';

    /**
     * 获取支付对应的数据完成类
     * @return string
     */
    public function getBuildDataClass()
    {
        $this->config->method = $this->method;
        return TransferQueryData::class;
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
            $ret = $this->sendReq($reqData);
        } catch (PayException $e) {
            throw $e;
        }

        if ($this->config->returnRaw) {
            $ret['channel'] = Config::ALI_TRANSFER;
            return $ret;
        }

        return $this->createBackData($ret);
    }

    /**
     * 返回数据给客户端  未完成，目前没有数据提供
     * @param array $data
     * @return array
     * @author helei
     */
    protected function createBackData(array $data)
    {
        if ($data['code'] !== '10000') {
            return ['is_success' => 'F', 'error' => $data['sub_msg'], 'channel' => Config::ALI_TRANSFER];
        }

        $retData = [
            'is_success' => 'T',
            'response' => [
                'transaction_id' => ArrayUtil::get($data, 'order_id'),// 支付宝订单号
                'status' => strtolower(ArrayUtil::get($data, 'status')),
                'pay_date' => ArrayUtil::get($data, 'pay_date'),// 转账日期
                'arrival_time_end' => ArrayUtil::get($data, 'arrival_time_end'),
                // 预计到账时间，转账到银行卡专用，格式为yyyy-MM-dd HH:mm:ss
                'amount' => ArrayUtil::get($data, 'order_fee'),// 转账金额
                'trans_no' => ArrayUtil::get($data, 'out_biz_no'),// 商户转账订单号
                'channel' => Config::ALI_TRANSFER,
            ]
        ];
        if (isset($data['error_code'])) {
            $retData['response']['error_code'] = ArrayUtil::get($data, 'error_code');
            // 查询到的订单状态为FAIL失败或REFUND退票时，返回具体的原因。
            $retData['response']['fail_reason'] = ArrayUtil::get($data, 'fail_reason');
        }

        return $retData;
    }
}