<?php

namespace Payment\Utils;

/**
 * 构建提交HTML页面
 */
class FormHtml
{
    /**
     * 建立请求，以表单HTML形式构造（默认）
     * @param string $url
     * @param array $params 请求参数数组
     * @param string $method 提交方式。两个值可选：post、get
     * @return string 提交表单HTML文本
     */
    public static function buildForm($url, $params, $method = 'POST')
    {
        //待请求参数数组
        $html = '<form id="pay_from" name="pay_from" action="' . $url . '" method="' . $method . '">';
        foreach ($params as $key => $value) {
            $html .= '<input type="hidden" name="' . $key . '" value="' . $value . '" />';
        }
        //submit按钮控件请不要含有name属性
        $html .= '<input type="submit" style="display: none;"></form>';
        $html .= "<script>document.forms['pay_from'].submit();</script>";
        return $html;
    }
}