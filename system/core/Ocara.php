<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  框架引导类Ocara
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

defined('OC_EXECUTE_STATR_TIME') OR define('OC_EXECUTE_STATR_TIME', microtime(true));

defined('OC_PATH') OR define(
	'OC_PATH', str_replace(DIRECTORY_SEPARATOR, '/', realpath(dirname(dirname(__DIR__)))) . '/'
);

require_once (OC_PATH . 'system/functions/utility.php');
require_once (OC_PATH . 'system/const/basic.php');
require_once (OC_CORE . 'Basis.php');
require_once (OC_CORE . 'Base.php');
require_once (OC_CORE . 'Container.php');
require_once (OC_CORE . 'Loader.php');
require_once (OC_CORE . 'Config.php');

use Ocara\Container;

final class Ocara extends Basis
{
	/**
	 * @var $OC_CONF 	框架信息
	 * @var $CONF 		公共配置 
	 * @var $OC_LANG    框架语言数据
	 */
	private static $_container;
	private static $_services;
	private static $_instance;
	private static $_info;

	private static $_language = array();
	private static $_route    = array();

	private function __clone(){}
	private function __construct(){}

	/**
	 * 单例模式引用
	 */
	public static function getInstance()
	{
		if (self::$_instance === null) {
			self::$_instance = new self();
			self::init();
		}
		return self::$_instance;
	}

	/**
	 * 初始化设置
	 */
	public static function init()
	{
        @ini_set('register_globals', 'Off');
        register_shutdown_function("ocShutdownHandler");

        define('OC_SYS_MODEL', ocConfig('SYS_MODEL', 'application'));
        self::$_language = ocConfig('LANGUAGE', 'zh_cn');

        error_reporting(self::errorReporting());
        set_exception_handler(
            ocConfig('ERROR_HANDLER.exception_error', 'ocExceptionHandler', true)
        );

        spl_autoload_register(array('\Ocara\Loader', 'autoload'));

        if (empty($_SERVER['REQUEST_METHOD'])) {
            $_SERVER['REQUEST_METHOD'] = 'GET';
        }

        ocImport(array(
            OC_SYS . 'const/config.php',
            OC_SYS . 'functions/common.php'
        ));
	}

	/**
	 * 新建应用
	 */
	public static function create()
	{
		include_once (OC_CORE . 'Application.php');
		Application::create();
	}

	/**
	 * 运行框架
	 * @param string $bootstrap
	 */
	public static function run($bootstrap = null)
	{
		self::getInstance();
		$bootstrap = self::getBootstrap($bootstrap);

		self::getRoute();
		$bootstrap->start(self::$_route);
	}

	/**
	 * 获取启动器
	 * @param $bootstrap
	 * @return string
	 */
	public static function getBootstrap($bootstrap)
	{
		$bootstrap = $bootstrap ? : '\Ocara\Bootstrap';
		$bootstrap = new $bootstrap();
		self::$_services = $bootstrap->getServiceProvider();
        self::$_services->setContainer(self::container());

		$bootstrap->register();
		$bootstrap->init();

		return $bootstrap;
	}

	/**
	 * 规定在哪个错误报告级别会显示用户定义的错误
	 * @param integer $error
	 * @return bool|int
	 */
	public static function errorReporting($error = null)
	{
		$error = $error ? : (OC_SYS_MODEL == 'develop' ? E_ALL : 0);

		set_error_handler(
			ocConfig('ERROR_HANDLER.program_error', 'ocErrorHandler', true),
			$error
		);

		return $error;
	}

	/**
	 * 获取路由信息
	 * @param string $name
	 * @return array|null
	 */
	public static function getRoute($name = null)
	{
		if (!self::$_route) {
			$_GET = self::$_services->url->parseGet();
			list($module, $controller, $action) = self::$_services->route->parseRouteInfo();
			self::$_route = compact('module', 'controller', 'action');
		}

		if (func_num_args()) {
			return isset(self::$_route[$name]) ? self::$_route[$name] : null;
		}

		return self::$_route;
	}

	/**
	 * 解析路由字符串
	 * @param string|array $route
	 * @return array
	 */
	public static function parseRoute($route)
	{
		if (is_string($route)) {
			$routeData = explode(
				OC_DIR_SEP,
				trim(str_replace(DIRECTORY_SEPARATOR, OC_DIR_SEP, $route), OC_DIR_SEP)
			);
		} elseif (is_array($route)) {
			$routeData = array_values($route);
		} else {
			return array();
		}

		switch (count($routeData)) {
			case 2:
				list($controller, $action) = $routeData;
				if ($route{0} != OC_DIR_SEP && isset(self::$_route['module'])) {
					$module = self::$_route['module'];
				}  else {
					$module = OC_EMPTY;
				}
				break;
			case 3:
				list($module, $controller, $action) = $routeData;
				break;
			default:
				return array();
		}

		return compact('module', 'controller', 'action');
	}

	/**
	 * 获取当前语言
	 */
	public static function language()
	{
		return self::$_language;
	}

	/**
	 * 获取默认服务容器
	 */
	public static function container()
	{
	    if (empty(self::$_container)) {
            Container::setDefault(new Container());
            self::$_container = Container::getDefault();
            self::$_container->bindSingleton('config', '\Ocara\Config');
        }

		return self::$_container;
	}

	/**
	 * 获取默认服务提供者
	 */
	public static function services()
	{
		return self::$_services;
	}

	/**
	 * 框架更新
	 * @param array $params
	 * @return bool
	 */
	public static function update(array $params = array())
	{
		ocImport(OC_ROOT . 'pass/Update.php');
		$args = func_get_args();
		return class_exists('Update', false) ? Update::run($args) : false;
	}

	/**
	 * 获取框架信息
	 * @param null $key
	 * @return array|bool|mixed|null
	 */
	public static function getInfo($key = null)
	{
		if (is_null(self::$_info)) {
			$path = OC_SYS . 'data/framework.php';
			if (ocFileExists($path)) {
				include($path);
			}
			if (isset($FRAMEWORK_INFO) && is_array($FRAMEWORK_INFO)) {
				self::$_info = $FRAMEWORK_INFO;
			} else {
				self::$_info = array();
			}
		}

		if (func_num_args()) {
			return ocGet($key, self::$_info);
		}

		return self::$_info;
	}
}
