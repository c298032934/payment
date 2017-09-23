<?php

namespace Payment\Charge\Ali;

use Payment\Common\Ali\AliBaseStrategy;
use Payment\Common\Ali\Data\Charge\BarChargeData;
use Payment\Common\PayException;

/**
 * 商户扫用户的二维码
 *
 * Class AliBarCharge
 * @package Payment\Charge\Ali
 *
 * @link      https://www.gitbook.com/book/helei112g1/payment-sdk/details
 * @link      https://helei112g.github.io/
 */
class AliBarCharge extends AliBaseStrategy
{
    // app 支付接口名称
    protected $method = 'alipay.trade.pay';

    /**
     * 获取支付对应的数据完成类
     * @return string
     */
    public function getBuildDataClass()
    {
        $this->config->method = $this->method;
        return BarChargeData::class;
    }

    /**
     * 处理扫码支付的返回值
     * @param array $ret
     * @throws PayException
     * @return string  可生产二维码的uri
     * @author helei
     */
    protected function retData(array $ret)
    {
        $reqData = parent::retData($ret);

        // 发起网络请求
        try {
            $data = $this->sendReq($reqData);
        } catch (PayException $e) {
            throw $e;
        }

        return $data;
    }
}
