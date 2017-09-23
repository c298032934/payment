<?php

namespace Payment\Charge\Wx;

use Payment\Common\Weixin\Data\BackPubChargeData;
use Payment\Common\Weixin\Data\Charge\PubChargeData;
use Payment\Common\Weixin\WxBaseStrategy;

/**
 * @author: helei
 * @createTime: 2016-07-14 18:28
 * @description: 微信 公众号 支付接口
 * @link      https://www.gitbook.com/book/helei112g1/payment-sdk/details
 * @link      https://helei112g.github.io/
 */
class WxPubCharge extends WxBaseStrategy
{
    /**
     * 获取支付对应的数据完成类
     * @return string
     */
    public function getBuildDataClass()
    {
        $this->config->tradeType = 'JSAPI';
        return PubChargeData::class;
    }

    /**
     * 处理公众号支付的返回值。直接返回与微信文档对应的字段
     * @param array $ret
     *
     * @return array $data  包含以下键
     *
     * @author helei
     */
    protected function retData(array $ret)
    {
        $back = new BackPubChargeData($this->config, $ret);

        $back->setSign();
        $backData = $back->getData();

        $backData['paySign'] = $backData['sign'];
        // 移除sign
        unset($backData['sign']);

        // 公众号支付返回数组结构
        return $backData;
    }
}
