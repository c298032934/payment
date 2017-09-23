<?php

namespace Payment\Common\Upacp\Data;

use Payment\Common\BaseData;
use Payment\Utils\ArrayUtil;
use Payment\Utils\Upacp\Autograph;

/**
 * 银联基础数据
 * @property string $version      调用的接口版本，固定为：5.1.0
 * @property string $charset      参数编码,固定为“UTF-8”
 * @property string $txnType      交易类型
 * @property string $txnSubType   交易子类
 * @property string $bizType      业务类型
 * @property string $returnUrl    前台通知地址
 * @property string $notifyUrl    后台通知地址
 * @property string $channelType  渠道类型，07-PC，08-手机
 * @property string $accessType   接入类型
 * @property string $currencyCode 交易币种，境内商户固定156
 * @property string $signCertPath 签名证书路径
 * @property string $signCertPwd  签名证书密码
 * @property string $secureKey    签名秘钥
 */
abstract class UpacpBaseData extends BaseData
{

    /**
     * 设置签名
     */
    public function setSign()
    {
        $this->buildData();

        $values = ArrayUtil::removeKeys($this->retData, ['signature']);

        $this->retData = $this->makeSign($values);
    }

    /**
     * 请求数据签名算法的实现
     * @param array $params
     * @return array
     */
    protected function makeSign($params)
    {
        if ($params['signMethod'] == '01') {
            return Autograph::instance()->signByCertInfo($params, $this->signCertPath, $this->signCertPwd);
        } else {
            return Autograph::instance()->signBySecureKey($params, $this->secureKey);
        }
    }

    /**
     * 构建数据
     */
    protected function buildData()
    {
        $signData = [
            // 公共参数
            'version' => $this->version,
            'encoding' => $this->charset,
            'txnType' => $this->txnType,
            'txnSubType' => $this->txnSubType,
            'bizType' => $this->bizType,
            'frontUrl' => $this->returnUrl,
            'backUrl' => $this->notifyUrl,
            'signMethod' => $this->signType,
            'channelType' => $this->channelType,
            'accessType' => $this->accessType,
            'currencyCode' => $this->currencyCode,
        ];

        $signData = array_merge($signData, $this->getReqData());
        // 移除数组中的空值
        $this->retData = ArrayUtil::paraFilter($signData);
    }

    /**
     * 请求数据
     *
     * @return array
     */
    abstract protected function getReqData();
}