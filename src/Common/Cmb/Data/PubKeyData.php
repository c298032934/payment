<?php

namespace Payment\Common\Cmb\Data;

use Payment\Common\Cmb\CmbConfig;

/**
 * 获取招商的公钥
 * Class PubKeyData
 * @package Payment\Common\Cmb\Data
 */
class PubKeyData extends CmbBaseData
{

    /**
     * 请求数据
     *
     * @return array
     */
    protected function getReqData()
    {
        $reqData = [
            'dateTime' => $this->dateTime,
            'branchNo' => $this->branchNo,
            'merchantNo' => $this->merchantNo,
            'txCode' => CmbConfig::TRADE_CODE,
        ];

        // 这里不能进行过滤空值，招商的空值也要加入签名中
        return $reqData;
    }
}