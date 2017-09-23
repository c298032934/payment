<?php

namespace Payment\Utils\Upacp;

use Payment\Common\PayException;


/**
 * 银联证书签名方法
 *
 * 内存泄漏问题说明：
 *        openssl_x509_parse疑似有内存泄漏，暂不清楚原因，可能和php、openssl版本有关，估计有bug。
 *        windows下试过php5.4+openssl0.9.8，php7.0+openssl1.0.2都有这问题。mac下试过也有问题。
 *        不过至今没人来反馈过这个问题，所以不一定真有泄漏？或者因为增长量不大所以一般都不会遇到问题？
 *        也有别人汇报过bug：https://bugs.php.net/bug.php?id=71519
 * 替代解决方案：
 *        方案1. 所有调用openssl_x509_parse的地方都是为了获取证书序列号，可以尝试把证书序列号+证书/key以别的方式保存，
 *                从其他地方（比如数据库）读序列号，而不直接从证书文件里读序列号。
 *        方案2. 代码改成执行脚本的方式执行，这样执行完一次保证能释放掉所有内存。
 *        方案3. 改用下面的CertSerialUtil取序列号，
 *                此方法仅用了几个测试和生产的证书做过测试，不保证没bug，所以默认注释掉了。如发现有bug或者可优化的地方可自行修改代码。
 *                注意用了bcmath的方法，*nix下编译时需要 --enable-bcmath。http://php.net/manual/zh/bc.installation.php
 */
class CertUtil
{

    const COMPANY = "中国银联股份有限公司";

    private static $signCerts = [];
    private static $encryptCerts = [];
    private static $verifyCerts = [];
    private static $verifyCerts510 = [];

    /**
     * @param      $certPath
     * @param      $certPwd
     * @param bool $openssl
     * @throws PayException
     */
    private static function initSignCert($certPath, $certPwd, $openssl = true)
    {
        $pkcs12certdata = file_get_contents($certPath);
        if ($pkcs12certdata === false) {
            throw new PayException($certPath . '读取失败。');
        }

        if (openssl_pkcs12_read($pkcs12certdata, $certs, $certPwd) == false) {
            throw new PayException($certPath . '读取失败。');
        }
        $x509data = $certs['cert'];

        if ($openssl) {
            $resource = openssl_x509_read($x509data);
            if (!$resource) {
                throw new PayException($certPath . '读取失败。');
            }
            $certdata = openssl_x509_parse($x509data);
            $cert['certId'] = $certdata['serialNumber'];
            openssl_x509_free($resource);
        } else {
            $certId = CertSerialUtil::getSerial($x509data, $errMsg);
            if ($certId === false) {
                throw new PayException('签名证书读取序列号失败：' . $errMsg);
            }
            $cert['certId'] = $certId;
        }

        $cert['key'] = $certs['pkey'];
        $cert['cert'] = $x509data;

        self::$signCerts[$certPath] = $cert;
    }

    /**
     * @param null $certPath
     * @param null $certPwd
     * @return mixed
     */
    public static function getSignKeyFromPfx($certPath, $certPwd)
    {
        if (!array_key_exists($certPath, self::$signCerts)) {
            self::initSignCert($certPath, $certPwd);
        }
        if (array_key_exists($certPath, self::$signCerts)) {
            return self::$signCerts[$certPath]['key'];
        }
        return null;
    }

    /**
     * @param $certPath
     * @param $certPwd
     * @return mixed
     */
    public static function getSignCertIdFromPfx($certPath, $certPwd)
    {
        if (!array_key_exists($certPath, self::$signCerts)) {
            self::initSignCert($certPath, $certPwd);
        }
        if (array_key_exists($certPath, self::$signCerts)) {
            return self::$signCerts[$certPath]['certId'];
        }
        return null;
    }

    /**
     * @param      $cert_path
     * @param bool $openssl
     * @throws PayException
     */
    private static function initEncryptCert($cert_path, $openssl = true)
    {
        $x509data = file_get_contents($cert_path);
        if ($x509data === false) {
            throw new PayException($cert_path . '读取失败。');
        }

        if ($openssl) {
            $resource = openssl_x509_read($x509data);
            if (!$resource) {
                throw new PayException($cert_path . '读取失败。');
            }
            $certdata = openssl_x509_parse($x509data);
            $cert['certId'] = $certdata['serialNumber'];
            openssl_x509_free($resource);
        } else {
            $certId = CertSerialUtil::getSerial($x509data, $errMsg);
            if ($certId === false) {
                throw new PayException('签名证书读取序列号失败：' . $errMsg);
            }
            $cert['certId'] = $certId;
        }

        $cert['key'] = $x509data;
        self::$encryptCerts[$cert_path] = $cert;
    }

    /**
     * @param null $cert_path
     * @return mixed
     */
    public static function getEncryptCertId($cert_path)
    {
        if (!array_key_exists($cert_path, self::$encryptCerts)) {
            self::initEncryptCert($cert_path);
        }
        if (array_key_exists($cert_path, self::$encryptCerts)) {
            return self::$encryptCerts[$cert_path]['certId'];
        }
        return null;
    }

    /**
     * @param $cert_path
     * @return mixed
     */
    public static function getEncryptKey($cert_path)
    {
        if (!array_key_exists($cert_path, self::$encryptCerts)) {
            self::initEncryptCert($cert_path);
        }
        if (array_key_exists($cert_path, self::$encryptCerts)) {
            return self::$encryptCerts[$cert_path]['key'];
        }
        return null;

    }

    /**
     * @param      $certBase64String
     * @param      $middleCertPath
     * @param      $rootCertPath
     * @param bool $test
     * @return mixed
     * @throws PayException
     */
    public static function verifyAndGetVerifyCert($certBase64String, $middleCertPath, $rootCertPath, $test = false)
    {
        if (array_key_exists($certBase64String, self::$verifyCerts510)) {
            return self::$verifyCerts510[$certBase64String];
        }

        openssl_x509_read($certBase64String);
        $certInfo = openssl_x509_parse($certBase64String);

        $cn = self::getIdentitiesFromCertficate($certInfo);

        if ($test) {
            if (self::COMPANY != $cn) {
                throw new PayException('cer owner is not CUP:' . $cn);
            }
        } else {
            if (self::COMPANY != $cn && "00040000:SIGN" != $cn) {
                throw new PayException('cer owner is not CUP:' . $cn);
            }
        }

        $from = date_create('@' . $certInfo['validFrom_time_t']);
        $to = date_create('@' . $certInfo['validTo_time_t']);
        $now = date_create(date('Ymd'));
        $interval1 = $from->diff($now);
        $interval2 = $now->diff($to);
        if ($interval1->invert || $interval2->invert) {
            throw new PayException('signPubKeyCert has expired');
        }

        $result = openssl_x509_checkpurpose($certBase64String, X509_PURPOSE_ANY, [$rootCertPath, $middleCertPath]);
        if ($result === false) {
            throw new PayException('validate signPubKeyCert by rootCert failed');
        } else {
            if ($result === true) {
                self::$verifyCerts510[$certBase64String] = $certBase64String;
                return self::$verifyCerts510[$certBase64String];
            } else {
                throw new PayException('validate signPubKeyCert by rootCert failed with error');
            }
        }
    }

    /**
     * @param $certInfo
     * @return null
     */
    public static function getIdentitiesFromCertficate($certInfo)
    {
        $cn = $certInfo['subject'];
        $cn = $cn['CN'];
        $company = explode('@', $cn);

        if (count($company) < 3) {
            return null;
        }
        return $company[2];
    }

    /**
     * @param      $cert_dir
     * @param bool $openssl
     * @throws PayException
     */
    private static function initVerifyCerts($cert_dir, $openssl = true)
    {
        $handle = opendir($cert_dir);
        if (!$handle) {
            throw new PayException('证书目录' . $cert_dir . '不正确');
        }
        while ($file = readdir($handle)) {
            clearstatcache();
            $filePath = $cert_dir . '/' . $file;
            if (is_file($filePath)) {
                if (pathinfo($file, PATHINFO_EXTENSION) == 'cer') {
                    $x509data = file_get_contents($filePath);
                    if ($x509data === false) {
                        throw new PayException($filePath . '读取失败。');
                    }
                    if ($openssl) {
                        $resource = openssl_x509_read($x509data);
                        if (!$resource) {
                            throw new PayException($filePath . '读取失败。');
                        }
                        $certdata = openssl_x509_parse($x509data);
                        $cert['certId'] = $certdata['serialNumber'];
                        openssl_x509_free($resource);
                    } else {
                        $certId = CertSerialUtil::getSerial($x509data, $errMsg);
                        if ($certId === false) {
                            throw new PayException('签名证书读取序列号失败：' . $errMsg);
                        }
                        $cert['certId'] = $certId;
                    }
                    $cert['key'] = $x509data;
                    self::$verifyCerts[$cert->certId] = $cert;
                }
            }
        }
        closedir($handle);
    }

    /**
     * @param $certId
     * @param $cert_dir
     * @return mixed
     * @throws PayException
     */
    public static function getVerifyCertByCertId($certId, $cert_dir)
    {
        if (count(self::$verifyCerts) == 0) {
            self::initVerifyCerts($cert_dir);
        }

        if (count(self::$verifyCerts) == 0) {
            throw new PayException('未读取到任何证书……');
        }

        if (array_key_exists($certId, self::$verifyCerts)) {
            return self::$verifyCerts[$certId]['key'];
        } else {
            throw new PayException('未匹配到序列号为[' . $certId . ']的证书');
        }
    }
}





    