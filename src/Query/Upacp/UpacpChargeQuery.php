<?php

namespace Payment\Query\Upacp;

use Payment\Common\Upacp\UpacpBaseStrategy;
use Payment\Common\Upacp\Data\Query\ChargeQueryData;
use Payment\Config;

/**
 * 银联支付订单查询接口
 */
class UpacpChargeQuery extends UpacpBaseStrategy
{

    /**
     * 获取支付对应的数据完成类
     * @return string
     */
    public function getBuildDataClass()
    {
        $this->config->txnType = '00';
        return ChargeQueryData::class;
    }

    /**
     * 获取需要的url
     * @return string
     */
    protected function getReqUrl()
    {
        // 测试
        if ($this->config->useSandbox) {
            return 'https://gateway.test.95516.com/gateway/api/queryTrans.do';
        }
        return 'https://gateway.95516.com/gateway/api/queryTrans.do';
    }

    /**
     * 返回app支付数据 已进行签名处理
     * @param array $ret
     * @return mixed
     */
    protected function retData(array $ret)
    {
        $param = parent::retData($ret);
        // 提交银联服务器进行验证
        $ret = $this->sendReq($param);

        if ($this->config->returnRaw) {
            $ret['channel'] = Config::UPACP_CHARGE;
            return $ret;
        }

        if ($ret["respCode"] != '00') {
            if (in_array($ret["respCode"], ['03', '04', '05'])) {
                //后续需发起交易状态查询交易确定交易状态
                return ['is_success' => 'F', 'error' => '处理超时，请稍后查询。'];
            } else {
                if ($ret["respCode"] != '00') {
                    //其他应答码做以失败处理
                    return ['is_success' => 'F', 'error' => $ret["respMsg"]];
                }
            }
        }

        if ($ret["origRespCode"] != '00') {
            if (in_array($ret["origRespCode"], ['03', '04', '05'])) {
                //后续需发起交易状态查询交易确定交易状态
                return ['is_success' => 'F', 'error' => '交易处理中，请稍后查询。'];
            } else {
                //其他应答码做以失败处理
                return ['is_success' => 'F', 'error' => $ret["origRespMsg"]];
            }
        }

        return $this->createBackData($ret);
    }

    /**
     * 处理支付宝返回的数据，统一处理后返回
     * @param array $data 支付宝返回的数据
     * @return array
     */
    protected function createBackData(array $data)
    {
        // 将金额处理为元
        $totalFee = bcdiv($data['txnAmt'], 100, 2);

        $retData = [
            'is_success' => 'T',
            'response' => [
                'amount' => $totalFee,
                'channel' => Config::UPACP_CHARGE,  //支付查询
                'order_no' => $data['orderId'],
                'transaction_id' => $data['queryId'],
                'trade_state' => Config::TRADE_STATUS_SUCC,// 银联的订单只会成功
                'time_end' => date('Y-m-d H:i:s', strtotime($data['txnTime'])),

                // 交易币种
                'currencyCode' => $data['currencyCode'],
                // 系统跟踪号
                'traceNo' => $data['traceNo'],
            ],
        ];

        return $retData;
    }
}
