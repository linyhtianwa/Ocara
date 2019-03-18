<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 缓存接口类Cache - 工厂类
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Core;

use Ocara\Core\Base;

defined('OC_PATH') or exit('Forbidden!');

final class CacheFactory extends Base
{
    /**
     * 默认服务器名
     * @var string
     */
    protected static $_defaultServer = 'defaults';

    /**
     * 新建缓存实例
     * @param string $connectName
     * @param bool $required
     * @return mixed
     * @throws \Ocara\Exceptions\Exception
     */
	public static function create($connectName = null, $required = true)
	{
		if (empty($connectName)) {
			$connectName = self::$_defaultServer;
		}

		$object = self::_connect($connectName, $required);
		if (is_object($object) && $object instanceof CacheBase) {
			return $object;
		}

		return ocService()->error
                    ->check('not_exists_cache', array($connectName), $required);
	}

    /**
     * 获取默认服务器名称
     * @return string
     */
    public static function getDefaultServer()
    {
        return self::$_defaultServer;
    }

    /**
     * 获取配置信息
     * @param string $connectName
     * @return array|mixed
     * @throws \Ocara\Exceptions\Exception
     */
	public static function getConfig($connectName = null)
	{
		if (empty($connectName)) {
			$connectName = self::$_defaultServer;
		}

		$config = array();

		if ($callback = ocConfig(array('SOURCE', 'cache', 'get_config'), null)) {
			$config = call_user_func_array($callback, array($connectName));
		}

		if (empty($config)) {
			$config = ocForceArray(ocConfig(array('CACHE', $connectName)));
		}

		return $config;
	}

    /**
     * 连接缓存
     * @param string $connectName
     * @param bool $required
     * @return mixed
     * @throws \Ocara\Exceptions\Exception
     */
	private static function _connect($connectName, $required = true)
	{
		$config = self::getConfig($connectName);
		$type = ucfirst(ocConfig(array('CACHE', $connectName, 'type')));

		$classInfo = ServiceBase::classFileExists("Caches/{$type}.php");
		if ($classInfo) {
			list($path, $namespace) = $classInfo;
			include_once($path);
			$class  = $namespace . 'Caches' . OC_NS_SEP . $type;
			if (class_exists($class, false)) {
				$config['connect_name'] = $connectName;
				$object = new $class($config, $required);
				return $object;
			}
		}

        ocService()->error->show('not_exists_cache');
	}
}