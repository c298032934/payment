<?php

namespace Payment\Refund;

use Payment\Common\Cmb\CmbBaseStrategy;
use Payment\Common\Cmb\Data\RefundData;
use Payment\Common\Cmb\CmbConfig;
use Payment\Config;

/**
 * 招商退款
 * Class CmbRefund
 * @package Payment\Refund
 */
class CmbRefund extends CmbBaseStrategy
{
    /**
     * 获取支付对应的数据完成类
     * @return string
     */
    public function getBuildDataClass()
    {
        $this->config->getewayUrl = 'https://payment.ebank.cmbchina.com/NetPayment/BaseHttp.dll?DoRefund';
        if ($this->config->useSandbox) {// 测试
            $this->config->getewayUrl = 'http://121.15.180.66:801/NetPayment_dl/BaseHttp.dll?DoRefund';
        }

        return RefundData::class;
    }

    /**
     * 处理招行的返回值并返回给客户端
     * @param array $ret
     * @return mixed
     */
    protected function retData(array $ret)
    {
        $json = json_encode($ret, JSON_UNESCAPED_UNICODE);

        $postData = CmbConfig::REQ_FILED_NAME . '=' . $json;

        $rsqData = $this->sendReq($postData);

        if ($this->config->returnRaw) {
            $rsqData['channel'] = Config::CMB_REFUND;
            return $rsqData;
        }

        // 正确情况
        $retData = [
            'is_success' => 'T',
            'response' => [
                'transaction_id' => $rsqData['bankSerialNo'],// 银行的退款流水号
                'order_no' => $ret['reqData']['orderNo'],
                'date' => $ret['reqData']['date'],
                'refund_no' => trim($rsqData['refundSerialNo']),//退款流水号,商户生成
                'refund_id' => $rsqData['refundRefNo'],// 银行的退款参考号
                'currency' => $rsqData['currency'],
                'refund_fee' => $rsqData['amount'],
                'channel' => Config::CMB_REFUND,
                'refund_time' => date('Y-m-d H:i:s', strtotime($rsqData['bankDate'] . $rsqData['bankTime'])),
            ],
        ];

        return $retData;
    }
}