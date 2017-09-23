<?php

namespace Payment\Common\Weixin\Data;

/**
 * Class BackPubChargeData
 *  小程序数据也在这里处理
 * @property string $device_info   设备号
 * @property string $trade_type  交易类型
 * @property string $prepay_id   预支付交易会话标识
 *
 * @package Payment\Common\Weixin\Data
 * anthor helei
 */
class BackPubChargeData extends WxBaseData
{
    /**
     * 构建用于支付的签名相关数据
     */
    protected function buildData()
    {
        $this->retData = [
            'appId' => $this->appId,
            'timeStamp' => time() . '',
            'nonceStr' => $this->nonceStr,
            'package' => 'prepay_id=' . $this->prepay_id,
            'signType' => 'MD5',// 签名算法，暂支持MD5
        ];
    }

    /**
     * 检查传入的参数. $reqData是否正确.
     * @throws PayException
     */
    protected function checkDataParam()
    {
        // 不进行检查
    }
}
