<?php

namespace Payment\Charge\Upacp;

use Payment\Common\Upacp\UpacpBaseStrategy;
use Payment\Common\Upacp\Data\Charge\AppChargeData;

/**
 * 银联APP支付接口
 */
class UpacpAppCharge extends UpacpBaseStrategy
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
        return AppChargeData::class;
    }

    /**
     * 获取需要的url
     * @return string
     */
    protected function getReqUrl()
    {
        // 测试
        if ($this->config->useSandbox) {
            return 'https://gateway.test.95516.com/gateway/api/appTransReq.do';
        }
        return 'https://gateway.95516.com/gateway/api/appTransReq.do';
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
        $data = $this->sendReq($param);
        return $data['tn'];
    }
}