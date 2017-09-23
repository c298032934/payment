<?php

namespace Payment\Notify;

use Payment\Config;
use Payment\Common\PayException;
use Payment\Utils\Upacp\Autograph;
use Payment\Common\Upacp\UpacpConfig;

/**
 * 银联回调处理
 */
class UpacpNotify extends NotifyStrategy
{

    /**
     * 构造方法
     * @param array $config
     * @throws PayException
     */
    public function __construct(array $config)
    {
        try {
            $this->config = new UpacpConfig($config);
        } catch (PayException $e) {
            throw $e;
        }
    }

    /**
     * 获取微信返回的异步通知数据
     * @return array|bool
     */
    public function getNotifyData()
    {
        $data = empty($_POST) ? $_GET : $_POST;
        if (empty($data) || !is_array($data)) {
            return false;
        }

        return $data;
    }

    /**
     * 检查微信异步通知的数据是否正确
     * @param array $data
     * @return boolean
     */
    public function checkNotifyData(array $data)
    {
        if ($data['respCode'] != '00') {
            return false;
        }

        // 检查返回数据签名是否正确
        return $this->verifySign($data);
    }

    /**
     * 检查银联返回的数据是否被篡改过
     * @param array $retData
     * @return boolean
     */
    protected function verifySign(array $retData)
    {
        if ($retData['signMethod'] == '01') {
            return Autograph::instance()->validateByCertInfo($retData, $this->config->validateCertDir,
                $this->config->middleCertPath, $this->config->rootCertPath);
        }
        return Autograph::instance()->validateBySecureKey($retData, $this->config->secureKey);
    }

    /**
     * 获取向客户端返回的数据
     * @param array $data
     * @return array
     */
    protected function getRetData(array $data)
    {
        if ($this->config->returnRaw) {
            $data['channel'] = Config::UPACP_CHARGE;
            return $data;
        }

        // 将金额处理为元
        $totalFee = bcdiv($data['txnAmt'], 100, 2);

        $retData = [
            'order_no' => $data['orderId'],
            // 支付完成时间
            'pay_time' => date('Y-m-d H:i:s', strtotime($data['traceTime'])),
            'amount' => $totalFee,
            'transaction_id' => $data['queryId'],
            // 银联的订单只会成功
            'trade_state' => Config::TRADE_STATUS_SUCC,
            'channel' => Config::UPACP_CHARGE,
            // 交易币种
            'currencyCode' => $data['currencyCode'],
            // 系统跟踪号
            'traceNo' => $data['traceNo'],
        ];

        // 检查是否存在用户自定义参数
        if (isset($data['reqReserved']) && !empty($data['reqReserved'])) {
            $retData['return_param'] = $data['reqReserved'];
        }

        return $retData;
    }

    /**
     * 银联无需任何返回
     * @param bool $flag
     * @param string $msg 通知信息，错误原因
     * @return string
     */
    protected function replyNotify($flag, $msg = 'OK')
    {
        return $msg;
    }
}
