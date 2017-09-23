<?php

namespace Payment\Common\Weixin\Data\Charge;

use Payment\Common\PayException;
use Payment\Utils\ArrayUtil;

/**
 * Class WebChargeData
 *
 * @inheritdoc
 * @property string $auth_code  扫码支付授权码，设备读取用户微信中的条码或者二维码信息
 *
 * @package Payment\Common\Weixin\Data\Charge
 */
class BarChargeData extends ChargeBaseData
{
    /**
     * 检查传入的支付信息是否正确
     */
    protected function checkDataParam()
    {
        parent::checkDataParam();

        // 刷卡支付,必须设置auth_code
        $authCode = $this->auth_code;
        if (empty($authCode)) {
            throw new PayException('扫码支付授权码,必须设置该参数.');
        }
    }

    /**
     * 生成下单的数据
     */
    protected function buildData()
    {
        $info = $this->scene_info;
        $sceneInfo = [];
        if ($info && is_array($info)) {
            $sceneInfo['store_info'] = $info;
        }

        $signData = [
            'appid' => trim($this->appId),
            'mch_id' => trim($this->mchId),
            'device_info' => $this->terminal_id,
            'nonce_str' => $this->nonceStr,
            'sign_type' => $this->signType,
            'body' => trim($this->subject),
            //'detail' => json_encode($this->body, JSON_UNESCAPED_UNICODE),
            'attach' => trim($this->return_param),
            'out_trade_no' => trim($this->order_no),
            'total_fee' => $this->amount,
            'fee_type' => $this->feeType,
            'spbill_create_ip' => trim($this->client_ip),
            //'goods_tag' => '订单优惠标记',
            'limit_pay' => $this->limitPay,  // 指定不使用信用卡
            'auth_code' => $this->auth_code,
            'scene_info' => $sceneInfo ? json_encode($sceneInfo, JSON_UNESCAPED_UNICODE) : '',

            // 服务商
            'sub_appid' => $this->sub_appid,
            'sub_mch_id' => $this->sub_mch_id,
        ];

        // 移除数组中的空值
        $this->retData = ArrayUtil::paraFilter($signData);
    }
}
