<?php

namespace Payment;

use Payment\Common\BaseStrategy;
use Payment\Common\PayException;
use Payment\Query\Ali\AliChargeQuery;
use Payment\Query\Ali\AliRefundQuery;
use Payment\Query\Ali\AliTransferQuery;
use Payment\Query\Cmb\CmbChargeQuery;
use Payment\Query\Cmb\CmbRefundQuery;
use Payment\Query\Wx\WxChargeQuery;
use Payment\Query\Wx\WxRefundQuery;
use Payment\Query\Wx\WxTransferQuery;
use Payment\Query\Upacp\UpacpChargeQuery;

/**
 * 查询上下文
 * @link      https://www.gitbook.com/book/helei112g1/payment-sdk/details
 * @link      https://helei112g.github.io/
 * Class QueryContext
 * @package Payment
 */
class QueryContext
{
    /**
     * 查询的渠道
     * @var BaseStrategy
     */
    protected $query;

    /**
     * 设置对应的查询渠道
     * @param string $channel 查询渠道
     *  - @see Config
     *
     * @param array $config 配置文件
     * @throws PayException
     * @author helei
     */
    public function initQuery($channel, array $config)
    {
        try {
            switch ($channel) {
                case Config::ALI_CHARGE:
                    $this->query = new AliChargeQuery($config);
                    break;
                case Config::ALI_REFUND:// 支付宝退款订单查询
                    $this->query = new AliRefundQuery($config);
                    break;
                case Config::ALI_TRANSFER:
                    $this->query = new AliTransferQuery($config);
                    break;

                case Config::WX_CHARGE:// 微信支付订单查询
                    $this->query = new WxChargeQuery($config);
                    break;
                case Config::WX_REFUND:// 微信退款订单查询
                    $this->query = new WxRefundQuery($config);
                    break;
                case Config::WX_TRANSFER:// 微信转款订单查询
                    $this->query = new WxTransferQuery($config);
                    break;

                case Config::CMB_CHARGE:// 招商支付查询
                    $this->query = new CmbChargeQuery($config);
                    break;
                case Config::CMB_REFUND:// 招商退款查询
                    $this->query = new CmbRefundQuery($config);
                    break;
                case Config::UPACP_CHARGE:// 银联支付订单查询
                    $this->query = new UpacpChargeQuery($config);
                    break;
                default:
                    throw new PayException('当前仅支持：当前仅支持：支付宝 微信 招商一网通 银联');
            }
        } catch (PayException $e) {
            throw $e;
        }
    }

    /**
     * 通过环境类调用支付异步通知
     *
     * @param array $data
     * @return array
     * @throws PayException
     * @author helei
     */
    public function query(array $data)
    {
        if (!$this->query instanceof BaseStrategy) {
            throw new PayException('请检查初始化是否正确');
        }

        try {
            return $this->query->handle($data);
        } catch (PayException $e) {
            throw $e;
        }
    }
}
