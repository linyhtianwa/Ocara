<?php
/**
 * 启动器基类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core;

use Ocara\Exceptions\Exception;

abstract class BootstrapBase extends Base
{
    const EVENT_BEFORE_BOOTSTRAP = 'beforeBootstrap';

    /**
     * 注册事件
     * @throws Exception
     */
    public function registerEvents()
    {
        $this->event(self::EVENT_BEFORE_BOOTSTRAP)
            ->appendAll(ocConfig(array('EVENTS', 'bootstrap', 'beforeBootstrap'), array()));
    }

    /**
     * 初始化
     */
    public function init()
    {
        @ini_set('register_globals', 'Off');

        if (empty($_SERVER['REQUEST_METHOD'])) {
            $_SERVER['REQUEST_METHOD'] = 'GET';
        }

        date_default_timezone_set(ocConfig(array('DATE_FORMAT', 'timezone'), 'PRC'));

        if (!@ini_get('short_open_tag')) {
            ocService()->error->show('need_short_open_tag');
        }

        $this->fire(self::EVENT_BEFORE_BOOTSTRAP);
    }
}