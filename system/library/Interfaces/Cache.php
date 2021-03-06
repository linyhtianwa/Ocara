<?php
/**
 * 缓存类接口
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Interfaces;

interface Cache
{
    /**
     * 析构函数
     * @param array $config
     * @param bool $required
     */
    public function connect($config, $required = true);

    /**
     * 设置变量值
     * @param string $name
     * @param mixed $value
     * @param int $expireTime
     * @return mixed
     */
    public function set($name, $value, $expireTime = 0);

    /**
     * 获取变量值
     * @param string $name
     * @param mixed $args
     * @return null
     */
    public function get($name, $args = null);

    /**
     * 删除KEY
     * @param string $name
     */
    public function delete($name);

    /**
     * 选择数据库
     * @param string $name
     */
    public function selectDatabase($name);
}