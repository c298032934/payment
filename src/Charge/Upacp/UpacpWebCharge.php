<?php

namespace Payment\Charge\Upacp;

use Payment\Utils\FormHtml;
use Payment\Common\Upacp\UpacpBaseStrategy;
use Payment\Common\Upacp\Data\Charge\WebChargeData;

/**
 * 银联WEB支付接口 包括手机网站
 */
class UpacpWebCharge extends UpacpBaseStrategy
{

    /**
     * 获取支付对应的数据完成类
     * @return string
     */
    public function getBuildDataClass()
    {
        // 01：消费
        $this->config->txnType = '01';

        // 固定01
        $this->config->txnSubType = '01';

        // 000201：B2C 网关支付
        $this->config->bizType = '000201';

        // 渠道类型 08-手机
        $this->config->channelType = '08';
        return WebChargeData::class;
    }

    /**
     * 获取需要的url
     * @return string
     */
    protected function getReqUrl()
    {
        // 测试
        if ($this->config->useSandbox) {
            return 'https://gateway.test.95516.com/gateway/api/frontTransReq.do';
        }
        return 'https://gateway.95516.com/gateway/api/frontTransReq.do';
    }

    /**
     * 处理银联的返回值并返回给客户端
     * @param array $ret
     * @return mixed
     */
    protected function retData(array $ret)
    {
        return FormHtml::buildForm($this->getReqUrl(), $ret);
    }
}