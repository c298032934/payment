<?php

namespace Payment\Common\Ali\Data\Charge;

use Payment\Common\PayException;

/**
 * 支付宝 条码支付
 *  - 扫户扫用户的二维码，完成支付
 * Class BarChargeData
 * @package Payment\Common\Ali\Data\Charge
 * @link      https://www.gitbook.com/book/helei112g1/payment-sdk/details
 * @link      https://helei112g.github.io/
 *
 * @property string $operator_id  商户操作员编号
 * @property string $terminal_id 商户机具终端编号
 * @property string $scene  条码支付，取值：bar_code 声波支付，取值：wave_code
 * @property string $auth_code 支付授权码 二维码的数值
 *
 */
class BarChargeData extends ChargeBaseData
{
    /**
     * 业务请求参数的集合，最大长度不限，除公共参数外所有请求参数都必须放在这个参数中传递
     *
     * @return array
     */
    protected function getBizContent()
    {
        $content = [
            'out_trade_no' => strval($this->order_no),
            'scene' => $this->scene,
            'auth_code' => $this->auth_code,
            'product_code' => 'FACE_TO_FACE_PAYMENT',

            'subject' => strval($this->subject),
            // TODO 支付宝用户ID
            // 'seller_id' => $this->partner,

            'body' => strval($this->body),
            'total_amount' => strval($this->amount),
            // TODO 折扣金额
            // 'discountable_amount' => '',
            // TODO  业务扩展参数 订单商品列表信息，待支持
            // 'extend_params => '',
            // 'goods_detail' => '',

            'operator_id' => $this->operator_id,
            'store_id' => $this->store_id,
            'terminal_id' => $this->terminal_id,
        ];

        $timeExpire = $this->timeout_express;
        if (!empty($timeExpire)) {
            $express = floor(($timeExpire - strtotime($this->timestamp)) / 60);
            ($express > 0) && $content['timeout_express'] = $express . 'm';// 超时时间 统一使用分钟计算
        }

        return $content;
    }

    /**
     * 检查传入的支付业务参数是否正确
     *
     * 如果输入参数不符合规范，直接抛出异常
     */
    protected function checkDataParam()
    {
        parent::checkDataParam();

        $scene = $this->scene;
        $authCode = $this->auth_code;

        if (empty($scene) || !in_array($scene, ['bar_code', 'wave_code'])) {
            throw new PayException('支付场景 scene 必须设置 条码支付：bar_code 声波支付：wave_code');
        }

        if (empty($authCode)) {
            throw new PayException('请提供支付授权码');
        }
    }
}
