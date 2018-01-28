<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 缓存接口类Cache - 工厂类
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

defined('OC_PATH') or exit('Forbidden!');

final class Cache extends Base
{
	/**
	 * 工厂模式
	 */
	private function __clone(){}
	private function __construct(){}

	/**
	 * 新建缓存实例
	 * @param string $connectName
	 * @param bool $required
	 * @return null
	 */
	public static function create($connectName = 'main', $required = true)
	{
		if (empty($connectName)) {
			$connectName = 'main';
		}

		$object = self::_connect($connectName, $required);
		if (is_object($object) && $object instanceof CacheBase) {
			return $object;
		}

		return Error::check(
			'not_exists_cache', array($connectName), $required
		);
	}

	/**
	 * 获取配置信息
	 * @param null $connectName
	 * @return array|bool|mixed|null
	 */
	public static function getConfig($connectName = null)
	{
		if (empty($connectName)) {
			$connectName = 'main';
		}

		$config = array();

		if ($callback = ocConfig('SOURCE.cache.get_config', null)) {
			$config = Ocara::services()->call->run($callback, array($connectName));
		}

		if (empty($config)) {
			$config = ocForceArray(ocConfig("CACHE.{$connectName}"));
		}

		return $config;
	}
	
	/**
	 * 连接缓存
	 * @param string $connectName
	 * @param bool $required
	 */
	private static function _connect($connectName, $required = true)
	{
		$config = self::getConfig($connectName);
		$type = ucfirst(ocConfig('CACHE.' . $connectName . '.type'));

		$classInfo = ServiceBase::classFileExists("Cache/{$type}.php");
		if ($classInfo) {
			list($path, $namespace) = $classInfo;
			include_once($path);
			$class  = $namespace . 'Cache' . OC_NS_SEP . $type;
			if (class_exists($class, false)) {
				$config['connect_name'] = $connectName;
				$object = new $class($config, $required);
				return $object;
			}
		}

		Error::show('not_exists_cache');
	}
}