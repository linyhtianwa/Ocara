<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   表单令牌处理类FormToken
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Core;

use Ocara\Core\Base;
use Ocara\Service\Code;

defined('OC_PATH') or exit('Forbidden!');

class FormToken extends Base
{
    /**
     * 生成表单令牌
     * @param $formName
     * @param $route
     * @return mixed|string
     * @throws \Ocara\Exceptions\Exception
     */
    public function generate($formName, $route)
    {
        $routeStr = implode(OC_EMPTY, $route);

        if ($config = ocConfig('SOURCE.form.generate_token', null)) {
            $token = call_user_func_array($config, array($formName, $route));
        } else {
            $token = md5($routeStr . $formName . md5(Code::getRand(5)) . uniqid(mt_rand()));
        }

        return $token;
    }
}