<?php

namespace Payment\Utils\Upacp;

use Payment\Utils\ArrayUtil;
use Payment\Common\PayException;


/**
 * 银联签名对象
 */
class Autograph
{

    /**
     * @var static $instance 对象实例
     */
    protected static $instance;

    /**
     * 初始化
     * @return $this
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * 构造方法
     */
    private function __construct()
    {
    }

    /**
     * 证书签名
     * @param $params
     * @param $cert_path
     * @param $cert_pwd
     * @return array
     * @throws PayException
     */
    public function signByCertInfo($params, $cert_path, $cert_pwd)
    {
        if ($params['signMethod'] != '01') {
            throw new PayException('签名类型错误！');
        }

        // 证书ID
        $params['certId'] = CertUtil::getSignCertIdFromPfx($cert_path, $cert_pwd);
        $private_key = CertUtil::getSignKeyFromPfx($cert_path, $cert_pwd);
        if (empty($private_key) || empty($params['certId'])) {
            throw new PayException('证书读取失败！');
        }
        // 排序
        $params = ArrayUtil::arraySort($params);
        // 转换成key=val&串
        $params_str = ArrayUtil::createLinkstringEncode($params);

        switch ($params['version']) {
            case '5.0.0':
                $params_sha1x16 = sha1($params_str, false);
                // 签名
                $result = openssl_sign($params_sha1x16, $signature, $private_key, OPENSSL_ALGO_SHA1);
                break;
            case '5.1.0':
                //sha256签名摘要
                $params_sha256x16 = hash('sha256', $params_str);
                // 签名
                $result = openssl_sign($params_sha256x16, $signature, $private_key, 'sha256');
                break;
            default:
                throw new PayException('版本错误！');
        }

        if (!$result) {
            throw new PayException('>>>>>签名失败<<<<<<<');
        }

        $signature_base64 = base64_encode($signature);
        $params['signature'] = $signature_base64;

        return $params;
    }

    /**
     * 非证书签名
     * @param $params
     * @param $secureKey
     * @return array
     * @throws PayException
     */
    public function signBySecureKey($params, $secureKey)
    {
        switch ($params['signMethod']) {
            case '11':
                // 转换成key=val&串
                $params = ArrayUtil::arraySort($params);
                $params_str = ArrayUtil::createLinkstringEncode($params);
                $params_before_sha256 = hash('sha256', $secureKey);
                $params_before_sha256 = $params_str . '&' . $params_before_sha256;
                $params_after_sha256 = hash('sha256', $params_before_sha256);
                $params['signature'] = $params_after_sha256;
                break;
            default:
                throw new PayException('签名类型错误！');
        }
        return $params;
    }

    /**
     * 证书验签
     * @param array $params 应答数组
     * @param       $cert_dir
     * @param       $middleCertPath
     * @param       $rootCertPath
     * @return bool
     * @throws PayException
     */
    public function validateByCertInfo($params, $cert_dir, $middleCertPath, $rootCertPath)
    {
        if ($params['signMethod'] != '01') {
            throw new PayException('签名类型错误！');
        }

        $signature_str = $params['signature'];
        unset($params['signature']);
        // 排序
        $params = ArrayUtil::arraySort($params);
        // 转换成key=val&串
        $params_str = ArrayUtil::createLinkstringEncode($params);

        switch ($params['version']) {
            case '5.0.0':
                // 公钥
                $public_key = CertUtil::getVerifyCertByCertId($params['certId'], $cert_dir);
                $params_sha1x16 = sha1($params_str, false);
                $signature = base64_decode($signature_str);
                $isSuccess = openssl_verify($params_sha1x16, $signature, $public_key, OPENSSL_ALGO_SHA1);
                break;
            case '5.1.0':
                $strCert = $params['signPubKeyCert'];
                $strCert = CertUtil::verifyAndGetVerifyCert($strCert, $middleCertPath, $rootCertPath);
                if ($strCert == null) {
                    throw new PayException('"validate cert err: ' . $params['signPubKeyCert']);
                }
                $params_sha256x16 = hash('sha256', $params_str);
                $signature = base64_decode($signature_str);
                $isSuccess = openssl_verify($params_sha256x16, $signature, $strCert, "sha256");
                break;
            default:
                throw new PayException('版本错误！');
        }

        return boolval($isSuccess);
    }

    /**
     * 非证书验签
     * @param $params
     * @param $secureKey
     * @return bool
     * @throws PayException
     */
    public function validateBySecureKey($params, $secureKey)
    {
        $signature_str = $params['signature'];
        unset($params['signature']);
        // 排序
        $params = ArrayUtil::arraySort($params);
        // 转换成key=val&串
        $params_str = ArrayUtil::createLinkstringEncode($params);
        switch ($params['signMethod']) {
            case '11':
                $params_before_sha256 = hash('sha256', $secureKey);
                $params_before_sha256 = $params_str . '&' . $params_before_sha256;
                $params_after_sha256 = hash('sha256', $params_before_sha256);
                $isSuccess = $params_after_sha256 == $signature_str;
                break;
            default:
                throw new PayException('签名类型错误！');
        }

        return $isSuccess;
    }
}