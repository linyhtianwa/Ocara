<?php
/**
 * 表单验证插件类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Service;

use Ocara\Exceptions\Exception;

class Validate extends BaseValidate
{
    /**
     * 不能为非0的空值
     * @param mixed $value
     * @return array|bool
     * @throws Exception
     */
    public function notEmpty($value)
    {
        $result = !ocEmpty($value);
        $result = $this->validate($result, 'not_empty');
        return $result;
    }

    /**
     * 标准命名方式
     * @param mixed $value
     * @return array|false|int
     * @throws Exception
     */
    public function standardName($value)
    {
        $result = preg_match('/^[a-zA-Z_]+[a-zA-Z0-9_]*$/', $value);
        $result = $this->validate($result, 'is_not_standard_name');
        return $result;
    }

    /**
     * 最长字符
     * @param mixed $value
     * @param int $length
     * @return array|bool
     * @throws Exception
     */
    public function maxLength($value, $length = 0)
    {
        $result = strlen($value) <= $length;
        $result = $this->validate($result, 'over_max_string_length', array($length));
        return $result;
    }

    /**
     * 最短字符
     * @param mixed $value
     * @param int $length
     * @return array|bool
     * @throws Exception
     */
    public function minLength($value, $length = 0)
    {
        $result = strlen($value) >= $length;
        $result = $this->validate($result, 'less_than_min_string_length', array($length));
        return $result;
    }

    /**
     * 字符字数
     * @param mixed $value
     * @param int $min
     * @param int $max
     * @return array|bool
     * @throws Exception
     */
    public function betweenLength($value, $min = 0, $max = 1)
    {
        $len = strlen($value);
        $result = $len >= $min && $len <= $max;
        $result = $this->validate($result, 'not_in_pointed_length', array($min, $max));
        return $result;
    }

    /**
     * email验证
     * @param mixed $value
     * @return array|mixed
     * @throws Exception
     */
    public function email($value)
    {
        $result = filter_var($value, FILTER_VALIDATE_EMAIL);
        $result = $this->validate($result, 'unvalid_email');
        return $result;
    }

    /**
     * IP验证
     * @param mixed $value
     * @return array|mixed
     * @throws Exception
     */
    public function ip($value)
    {
        $result = filter_var($value, FILTER_VALIDATE_IP);
        $result = $this->validate($result, 'unvalid_ip');
        return $result;
    }

    /**
     * URL验证
     * @param mixed $value
     * @return array|mixed
     * @throws Exception
     */
    public function url($value)
    {
        $result = filter_var($value, FILTER_VALIDATE_URL);
        $result = $this->validate($result, 'unvalid_url');
        return $result;
    }

    /**
     * 正则表达式验证
     * @param mixed $value
     * @param string $expression
     * @return array|false|int
     * @throws Exception
     */
    public function regExp($value, $expression = '')
    {
        $result = preg_match($expression, $value);
        $result = $this->validate($result, 'unvalid_express_format', array($expression));
        return $result;
    }

    /**
     * 身份证验证
     * @param mixed $value
     * @return array|false|int
     * @throws Exception
     */
    public function idCard($value)
    {
        $result = preg_match('/^\d{15}|\d{18}$/', $value);
        $result = $this->validate($result, 'unvalid_id_cards');
        return $result;
    }

    /**
     * 验证手机号码
     * @param mixed $value
     * @return array|false|int
     * @throws Exception
     */
    public function mobile($value)
    {
        $result = preg_match('/^[1]\d{10}$/', $value);
        $result = $this->validate($result, 'unvalid_mobile');
        return $result;
    }

    /**
     * 验证是否全部是中文
     * @param mixed $value
     * @return array|false|int
     * @throws Exception
     */
    public function chinese($value)
    {
        $result = preg_match('/^[\x{4e00}-\x{9fa5}]+$/u', $value);
        $result = $this->validate($result, 'must_be_chinese_all');
        return $result;
    }

    /**
     * 验证是否含有中文
     * @param mixed $value
     * @return array|bool
     * @throws Exception
     */
    public function noneChinese($value)
    {
        $result = !preg_match('/[\x{4e00}-\x{9fa5}]+/u', $value);
        $result = $this->validate($result, 'cannot_have_chinese');
        return $result;
    }

    /**
     * 验证邮政编码
     * @param mixed $value
     * @return array|bool
     * @throws Exception
     */
    public function postNum($value)
    {
        $result = !preg_match('/^[1-9]\d{5}(?!\d)$/', $value);
        $result = $this->validate($result, 'unvalid_post_num');
        return $result;
    }
}
