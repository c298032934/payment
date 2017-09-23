<?php

namespace Payment\Common\Weixin\Data;

use Payment\Utils\StrUtil;

/**
 * Class BackAppChargeData
 *
 * @property string $device_info   设备号
 * @property string $trade_type  交易类型
 * @property string $prepay_id   预支付交易会话标识
 *
 * @package Payment\Common\Weixin\Data
 * anthor helei
 *
 * @link      https://www.gitbook.com/book/helei112g1/payment-sdk/details
 * @link      https://helei112g.github.io/
 */
class BackAppChargeData extends WxBaseData
{
    /**
     * 构建用于支付的签名相关数据
     */
    protected function buildData()
    {
        $this->retData = [
            'appid' => $this->appId,
            'partnerid' => $this->mchId,
            'prepayid' => $this->prepay_id,
            'package' => 'Sign=WXPay',
            'noncestr' => StrUtil::getNonceStr(),
            'timestamp' => time(),
        ];
    }

    /**
     * 检查传入的参数. $reqData是否正确.
     * @throws PayException
     */
    protected function checkDataParam()
    {
        // 对于返回数据不做检查检查
    }
}
