<?php

namespace Payment\Common\Upacp;

use Payment\Config;
use Payment\Utils\ArrayUtil;
use Payment\Common\PayException;
use Payment\Common\ConfigInterface;

/**
 * 银联参数配置
 */
final class UpacpConfig extends ConfigInterface
{

    // 调用的接口版本，固定为：5.1.0
    public $version = '5.1.0';

    // 采用的编码
    public $charset = 'UTF-8';

    // 交易类型 默认00
    // 00：查询交易，01：消费，02：预授权，03：预授权完成，04：退货，05：圈存
    // 11：代收，12：代付，13：账单支付，14：转账（保留）
    // 21：批量交易，22：批量查询，31：消费撤
    // 32：预授权撤销，33：预授权完成撤销
    // 71：余额查询，72：实名认证-建立绑定关系，73：账单查询，74：解除绑定关系，75：查询绑定关系，77：发送短信验证码交易，78：开通查询交易，79：开通交易
    // 94：IC卡脚本通知 95：查询更新加密公钥证书
    public $txnType = '00';

    // 交易子类 默认00
    public $txnSubType = '00';

    // 业务类型 默认000000
    // 000201：B2C 网关支付 000301：认证支付 2.0 000302：评级支付
    // 000401：代付 000501：代收 000601：账单支 000801：跨行收单
    // 000901：绑定支付 001001：订购 000202：B2B
    public $bizType = '000000';

    // 渠道类型
    // 07-PC，08-手机
    public $channelType = '07';

    // 接入类型 0：商户直连接入 1：收单机构接入 2：平台商户接入
    public $accessType = '0';

    // 交易币种，境内商户固定156
    public $currencyCode = '156';

    // 商户代码
    public $merId = '';

    // 发送请求的时间，格式"yyyyMMddHHmmss"
    public $txnTime;

    // 银联的网关
    public $getewayUrl;

    // 用于同步通知的地址
    public $returnUrl;

    // 签名证书路径
    public $signCertPath;

    // 签名证书密码，测试环境固定000000，生产环境请修改为从cfca下载的正式证书的密码，正式环境证书密码位数需小于等于6位
    public $signCertPwd;

    // 验签中级证书
    public $middleCertPath;

    // 验签根证书
    public $rootCertPath;

    // 银联公钥证书
    public $validateCertDir;

    // 签名秘钥
    public $secureKey;

    // 加密证书
    public $encryptCertPath;

    /**
     * 初始化配置文件
     * @param array $config
     * @throws PayException
     */
    protected function initConfig(array $config)
    {
        $config = ArrayUtil::paraFilter($config);

        // 初始 merId
        if (key_exists('mer_id', $config) && !empty($config['mer_id'])) {
            $this->merId = $config['mer_id'];
        } else {
            throw new PayException('mer_id不能为空');
        }

        // 检查 异步通知的url
        if (key_exists('notify_url', $config) && !empty($config['notify_url'])) {
            $this->notifyUrl = trim($config['notify_url']);
        }

        // 初始 同步通知地址，可为空
        if (key_exists('return_url', $config)) {
            $this->returnUrl = $config['return_url'];
        }

        if (key_exists('return_raw', $config)) {
            $this->returnRaw = filter_var($config['return_raw'], FILTER_VALIDATE_BOOLEAN);
        }

        // 签名方式，证书方式固定01
        if (key_exists('sign_type', $config) && in_array($config['sign_type'], ['01'])) {
            $this->signType = $config['sign_type'];
        } else {
            $this->signType = '01';
        }

        if (isset($config['use_sandbox']) && $config['use_sandbox'] === true) {
            $this->useSandbox = true;
            empty($this->merId) AND $this->merId = '777290058145911';
            $this->signCertPath = $this->base_certificate_path() . 'acp_test_sign.pfx';
            $this->signCertPwd = '000000';
            $this->middleCertPath = $this->base_certificate_path() . 'acp_test_middle.cer';
            $this->rootCertPath = $this->base_certificate_path() . 'acp_test_root.cer';
            $this->encryptCertPath = $this->base_certificate_path() . 'acp_test_enc.cer';
        } else {
            $this->useSandbox = false;// 不是沙箱模式
            if (isset($config['sign_cert_pwd']) && !empty($config['sign_cert_pwd'])) {
                $this->signCertPwd = $config['sign_cert_pwd'];
            } else {
                throw new PayException('证书密码不能为空！');
            }

            if (isset($config['sign_cert_path']) && !empty($config['sign_cert_path'])) {
                $this->signCertPath = $config['sign_cert_path'];
            } else {
                throw new PayException('证书路径不能为空！');
            }

            if (isset($config['middle_cert_path']) && !empty($config['middle_cert_path'])) {
                $this->middleCertPath = $config['middle_cert_path'];
            } else {
                $this->middleCertPath = $this->base_certificate_path() . 'acp_prod_middle.cer';
            }

            if (isset($config['root_cert_path']) && !empty($config['root_cert_path'])) {
                $this->rootCertPath = $config['root_cert_path'];
            } else {
                $this->rootCertPath = $this->base_certificate_path() . 'acp_prod_root.cer';
            }

            if (isset($config['encrypt_cert_path']) && !empty($config['encrypt_cert_path'])) {
                $this->encryptCertPath = $config['encrypt_cert_path'];
            } else {
                $this->encryptCertPath = $this->base_certificate_path() . 'acp_prod_enc.cer';
            }

            if (isset($config['validate_cert_dir']) && !empty($config['validate_cert_dir'])) {
                $this->validateCertDir = $config['validate_cert_dir'];
            } else {
                $this->validateCertDir = $this->base_certificate_path() . 'acp_prod_verify_sign.cer';
            }

            if (isset($config['secure_key']) && !empty($config['secure_key'])) {
                $this->secureKey = $config['secure_key'];
            }
        }

        // 初始 银联 同步通知地址，可为空
        if (key_exists('return_url', $config)) {
            $this->returnUrl = $config['return_url'];
        }

        // 初始接入类型
        if (key_exists('access_type', $config)) {
            $this->accessType = $config['access_type'];
        }

        // 初始交易币种
        if (key_exists('currency_code', $config)) {
            $this->currencyCode = $config['currency_code'];
        }

        // 设置交易开始时间 格式为yyyyMMddHHmmss，再此之前一定要设置时区
        $this->txnTime = date('YmdHis', time());
    }

    /**
     * 获取所属类型
     * @return string
     */
    public function getChannel()
    {
        return Config::UPACP_PAY;
    }

    /**
     * 基础证书路径
     * @return string
     */
    public function base_certificate_path()
    {
        return $this->getRoot() . 'CacertFile' . DIRECTORY_SEPARATOR . 'Upacp' . DIRECTORY_SEPARATOR;
    }
}