<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  框架引导类Ocara
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

use Ocara\Basis;
use Ocara\Container;
use Ocara\Config;
use Ocara\Loader;
use Ocara\ExceptionHandler;
use Ocara\ApplicationGenerator;
use Ocara\Application;

defined('OC_EXECUTE_STATR_TIME') OR define('OC_EXECUTE_STATR_TIME', microtime(true));

//根目录
defined('OC_PATH') OR define(
    'OC_PATH', str_replace(DIRECTORY_SEPARATOR, '/', realpath(dirname(dirname(__DIR__)))) . '/'
);

require_once (OC_PATH . 'system/functions/utility.php');
require_once (OC_PATH . 'system/const/basic.php');
require_once (OC_CORE . 'Basis.php');
require_once (OC_CORE . 'Container.php');
require_once (OC_CORE . 'Config.php');
require_once (OC_CORE . 'Loader.php');
require_once (OC_CORE . 'ExceptionHandler.php');

final class Ocara extends Basis
{
	/**
	 * @var $OC_CONF 	框架信息
	 * @var $CONF 		公共配置 
	 * @var $OC_LANG    框架语言数据
	 */
    private static $_bootstrap;
	private static $_services;
	private static $_instance;
	private static $_info;
	private static $_language;

	private static $_route = array();

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
        $container = Container::getDefault()
            ->bindSingleton('config', '\Ocara\Config')
            ->bindSingleton('loader', '\Ocara\Loader')
            ->bindSingleton('path', '\Ocara\Path')
            ->bindSingleton('app', '\Ocara\Application')
            ->bindSingleton('exceptionHandler', '\Ocara\ExceptionHandler');

        $config = $container->config;
        $loader = $container->loader;
        $exceptionHandler = $container->exceptionHandler;

        spl_autoload_register(array($loader, 'autoload'));
        $config->loadGlobalConfig();

        define('OC_SYS_MODEL', $config->get('SYS_MODEL', 'application'));
        define('OC_LANGUAGE', ocService()->app->getLanguage());

        @ini_set('register_globals', 'Off');
        register_shutdown_function("ocShutdownHandler");
        error_reporting(self::errorReporting());
        set_exception_handler(array($exceptionHandler, 'run'));

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
        ApplicationGenerator::create();
	}

	/**
	 * 运行框架
	 * @param string $bootstrap
	 */
	public static function run($bootstrap = null)
	{
		self::getInstance();

        $application = ocContainer()->app;
        $application->bootstrap($bootstrap)->start($application->getRoute());
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
            ocContainer()->config->get('ERROR_HANDLER.program_error', 'ocErrorHandler'),
			$error
		);

		return $error;
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

		if (isset($key)) {
			return ocGet($key, self::$_info);
		}

		return self::$_info;
	}
}
