<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   Session处理类Session
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

use Ocara\Ocara;

defined('OC_PATH') or exit('Forbidden!');

class Session extends Base
{
	const SAVE_TYPE_FILE     = 1;
	const SAVE_TYPE_DATABASE = 2;
	const SAVE_TYPE_CACHE    = 3;

	/**
	 * Session初始化处理
	 * @param $start
	 */
	public function init($start = true)
	{
		$saveType = ocConfig('SESSION.save_type', self::SAVE_TYPE_FILE);

		if ($saveType == self::SAVE_TYPE_FILE) {
			$class = 'Ocara\Session\SessionFile';
		} elseif ($saveType == self::SAVE_TYPE_DATABASE) {
			$class  = 'Ocara\Session\SessionDB';
		} elseif ($saveType == self::SAVE_TYPE_CACHE) {
			$class  = 'Ocara\Session\SessionCache';
		} else {
			$class = ocConfig('SESSION.handler', false);
		}

		if ($class) {
			$handler = new $class();
			session_set_save_handler(
				array(&$handler, 'open'),
				array(&$handler, 'close'),
				array(&$handler, 'read'),
				array(&$handler, 'write'),
				array(&$handler, 'destroy'),
				array(&$handler, 'gc')
			);
			register_shutdown_function('session_write_close');
		}

		$this->boot($start);
	}

	/**
	 * 启动Session
	 * @param bool $start
	 */
	private function boot($start)
	{
		$saveTime = intval(ocConfig('SESSION.save_time', false));

		if ($saveTime) {
			$this->setSaveTime($saveTime);
		}

		if ($start && !isset($_SESSION)) {
			if (!headers_sent()) {
				session_start();
			}
		}
		
		if ($saveTime) {
			$this->setCookie($saveTime);
		}
	}

    /**
     * 获取session变量值（方法重写）
     * @param string $name
     * @param null $args
     * @return mixed
     */
    public function &get($name = null, $args = null)
    {
		if (func_num_args()) {
			return ocGet($name, $_SESSION);
		}
		
		return $_SESSION;
	}

	/**
	 * 设置session变量
	 * @param string|array $key
	 * @param mixed $value
	 */
	public function set($key, $value = false)
	{
		ocSet($_SESSION, $key, $value);
	}

	/**
	 * 删除session变量
	 * @param string|array $key
	 */
	public function delete($key)
	{
		ocDel($_SESSION, $key);
	}

	/**
	 * 获取session ID
	 */
	public function getId()
	{
		return session_id();
	}

	/**
	 * 获取session Name
	 */
	public function getName()
	{
		return session_name();
	}

	/**
	 * 清空session数组
	 */
	public function clear()
	{
		session_unset();
	}

	/**
	 * > PHP7 回收session
	 */
	public function gc()
	{
		session_gc();
	}

	/**
	 * 检测session是否设置
	 * @param $key
	 * @return array|bool|mixed|null
	 */
	public function exists($key)
	{
		return ocKeyExists($key, $_SESSION);
	}

	/**
	 * 释放session，删除session文件
	 */
	public function destroy()
	{
		if (session_id()) {
			return session_destroy();
		}
	}

	/**
	 * cookie保存session设置
	 * @param $saveTime
	 * @param string $path
	 * @param string $domain
	 * @param bool $secure
	 * @param bool $httponly
	 */
	public function setCookie($saveTime, $path = false, $domain = false, $secure = false, $httponly = true)
	{	
		if (session_id()) {
			Ocara::services()->cookie->create(session_name(), session_id(), $saveTime, $path, $domain, $secure, $httponly);
		}
	}

	/**
	 * 设置session有效期(单位为秒)
	 * @param integer $saveTime
	 * @return string
	 */
	public function setSaveTime($saveTime)
	{
		return @ini_set('session.gc_maxlifetime', $saveTime);
	}

	/**
	 * 序列化session数组
	 */
	public function serialize()
	{
		return session_encode();
	}

	/**
	 * 反序列化session串
	 * @param string $data
	 * @return bool
	 */
	public function unserialize($data)
	{
		return session_decode($data);
	}
}
