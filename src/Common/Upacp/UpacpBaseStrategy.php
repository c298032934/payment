<?php

namespace Payment\Common\Upacp;

use Payment\Utils\Curl;
use Payment\Utils\ArrayUtil;
use Payment\Common\BaseData;
use Payment\Common\BaseStrategy;
use Payment\Common\PayException;
use Payment\Utils\Upacp\Autograph;

/**
 * 银联接口的基类
 */
abstract class UpacpBaseStrategy implements BaseStrategy
{

    /**
     * 银联的配置文件
     * @var UpacpConfig $config
     */
    protected $config;

    /**
     * 支付数据
     * @var BaseData $reqData
     */
    protected $reqData;

    /**
     * AliBaseStrategy constructor.
     * @param array $config
     * @throws PayException
     */
    public function __construct(array $config)
    {
        /* 设置内部字符编码为 UTF-8 */
        mb_internal_encoding("UTF-8");

        try {
            $this->config = new UpacpConfig($config);
        } catch (PayException $e) {
            throw $e;
        }
    }

    /**
     * 执行方法
     * @param array $data
     * @return mixed
     * @throws PayException
     */
    public function handle(array $data)
    {
        $buildClass = $this->getBuildDataClass();

        try {
            $this->reqData = new $buildClass($this->config, $data);
        } catch (PayException $e) {
            throw $e;
        }

        $this->reqData->setSign();

        return $this->retData($this->reqData->getData());
    }

    /**
     * 发送完了请求
     * @param string $param
     * @return mixed
     * @throws PayException
     */
    protected function sendReq($param)
    {
        $url = $this->getReqUrl();
        if (is_null($url)) {
            throw new PayException('目前不支持该接口。请联系开发者添加');
        }
        $responseTxt = $this->curlPost($param, $url);
        if ($responseTxt['error']) {
            throw new PayException('网络发生错误，请稍后再试curl返回码：' . $responseTxt['message']);
        }

        $retData = ArrayUtil::parseString($responseTxt['body']);
        $flag = $this->verifySign($retData);
        if (!$flag) {
            throw new PayException('银联返回数据被篡改。请检查网络是否安全！');
        }

        return $retData;
    }

    /**
     * 父类仅提供基础的post请求，子类可根据需要进行重写
     * @param string $param
     * @param string $url
     * @return array
     */
    protected function curlPost($param, $url)
    {
        $curl = new Curl();
        $set = [
            'CURLOPT_SSL_VERIFYHOST' => false,
            'CURLOPT_HEADER' => 0,
            'CURLOPT_SSLVERSION' => 1,
            'CURLOPT_HTTPHEADER' => ['Content-type:application/x-www-form-urlencoded;charset=UTF-8'],
            'CURLOPT_POSTFIELDS' => $param,
            'CURLOPT_RETURNTRANSFER' => true,
        ];
        return $curl->set($set)->post($param)->submit($url);
    }

    /**
     * 获取需要的url
     * @return string
     */
    abstract protected function getReqUrl();

    /**
     * 返回app支付数据 已进行签名处理
     * @param array $ret
     * @return mixed
     */
    protected function retData(array $ret)
    {
        return ArrayUtil::createLinkstringEncode($ret, true);
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
}
