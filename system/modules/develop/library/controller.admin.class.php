<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  开发者中心控制器管理类controller_admin
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Develop;

use Ocara\Develop;
use Ocara\Ocara;
use Ocara\Service\File;

defined('OC_PATH') or exit('Forbidden!');

class controller_admin
{

	public function __construct()
	{
		$this->tplType = ocConfig('TEMPLATE.file_type', 'html');
	}

	public function add(array $data = array())
	{
		$request = Ocara::services()->request;
		$data  = $data ? : $request->getPost();
		$cname = explode(OC_DIR_SEP, trim($data['cname'], OC_DIR_SEP));
		
		if (count($cname) >= 2) {
			$this->mdlname = strtolower(ocGet(0, $cname));
			$this->cname   = strtolower(ocGet(1, $cname));
		} else {
			$this->mdlname = false;
			$this->cname   = strtolower(ocGet(0, $cname));
		}

		$this->vtype      = $data['vtype'];
		$this->ttype      = $data['ttype'];
		$this->createview = $request->getPost('createview');
		$this->controllerType = $request->getPost('controllerType');
		$path = OC_ROOT . 'resource/conf/control';
	
		if ($this->mdlname) {
			if (is_dir($path = $path . OC_DIR_SEP . $this->mdlname)) {
				Ocara::services()->config->loadControlConfig($path);
			}
		}
		
		if (is_dir($path = $path . OC_DIR_SEP . $this->cname)) {
			Ocara::services()->config->loadControlConfig($path);
		}

		$CONF = Ocara::services()->config->get();
		$this->tplType = ocGet('TEMPLATE.file_type', $CONF, 'html');
		
		$this->createController();
	}
	
	public function createController()
	{
		$mdlname = ucfirst($this->mdlname);
		$cname = ucfirst($this->cname);
		$moduleNamespace = $mdlname ? $mdlname . OC_NS_SEP : OC_EMPTY;

		$className = $cname . 'Controller';
		$moduleClassName = $mdlname . 'Module';

		if (empty($this->cname) || empty($this->ttype)) {
			Develop::error(Develop::back('控制器名称和模板类型为必填信息！'));
		}
		
		if (!is_dir($controlPath = OC_APPLICATION_PATH . "/controller/{$mdlname}")) {
			Develop::error(Develop::back("{$this->mdlname}模块不存在.请先添加该模块。"));
		}

		$extends = $this->controllerType;
		if ($this->mdlname) {
			$extends = $mdlname . 'Module';
			$moduleClassPath = $controlPath . "/{$moduleClassName}.php";
			if (!ocFileExists($moduleClassPath)) {
				Develop::error(Develop::back("模块文件“{$moduleClassName}.php”不存在或丢失。"));
			}
			include_once($moduleClassPath);
			$moduleClass = 'Controller\\' . $moduleNamespace . $extends;
			foreach (Develop::$config['controller_actions'] as $controllerType => $controllerActions) {
				$controllerNamespace = '\Ocara\Controller\\' . $controllerType;
				$reflection = new \ReflectionClass($moduleClass);
				if ($reflection->isSubclassOf($controllerNamespace)) {
					$this->controllerType = $controllerType;
					break;
				}
			}
		}

		if (!is_dir($controlPath = $controlPath . "/{$cname}")) {
			@mkdir($controlPath);
		}
		
		if (ocFileExists($path = $controlPath . "/{$className}.php")) {
			Develop::error(Develop::back("模块或控制器文件已存在，如果需要覆盖，请先手动删除！"));
		}

		$content  = "<?php\r\n";
		$content .= "namespace Controller\\{$moduleNamespace}{$cname};\r\n";
		$content .= "use Ocara\\Request;\r\n";
		$content .= "use Ocara\\Response;\r\n";
		$content .= "use Ocara\\Error;\r\n";
		$content .= "use Controller\\$moduleNamespace{$extends};\r\n";
		$content .= "\r\n";

		$content  .= "class {$className} extends {$extends}\r\n";
		$content  .= "{\r\n";
		$content  .= "\t/**\r\n";
		$content  .= "\t * 初始化控制器\r\n";
		$content  .= "\t */\r\n";
		$content  .= "\tprotected function _control()\r\n\t{}\r\n";
		$content  .= "}";

		File::createFile($path, 'wb');
		File::writeFile($path, $content, true);
		
		$this->createAction($controlPath, $cname, $extends);
		$this->createview && $this->createView();
		die('添加成功！');
	}

	public function createAction($controlPath, $cname, $extends)
	{
		if (!is_dir($path = ocDir($controlPath) . 'Action')) {
			@mkdir($path);
		}
		
		$this->addAction($path, 'index', $cname, $extends);
		
		if ($this->vtype != 1) {
			$this->addAction($path, 'create', $cname, $extends);
			$this->addAction($path, 'update', $cname, $extends);
			$this->addAction($path, 'delete', $cname, $extends);
		}
	}

	public function addAction($path, $actionName, $cname, $extends)
	{
		$actions 	  = Develop::$config['controller_actions'][$this->controllerType];
		$path         = ocDir($path);
		$action       = strtolower($actionName);
		$actionName   = ucfirst($action);
		$className 	  = $actionName . 'Action';
		$controlClass = ucfirst($this->cname) . 'Controller';
		$mdlname = ucfirst($this->mdlname);
		$moduleNamespace = $mdlname ? $mdlname . OC_NS_SEP : OC_EMPTY;

		$content  = "<?php\r\n";

		$content .= "namespace Controller\\{$moduleNamespace}{$cname}\\Action;\r\n";
		$content .= "use Controller\\{$moduleNamespace}{$cname}\\{$controlClass};\r\n";
		$content .= "use Ocara\\Request;\r\n";
		$content .= "use Ocara\\Response;\r\n";
		$content .= "use Ocara\\Error;\r\n";
		$content .= "\r\n";

		$content .= "class {$className} extends {$controlClass}\r\n";
		$content .= "{\r\n";
		$content .= "\t/**\r\n";
		$content .= "\t * 初始化\r\n";
		$content .= "\t */\r\n";
		$content .= "\tprotected function _action()\r\n";
		$content .= "\t{}\r\n";

		if ($actions) {
			foreach ($actions as $actionName) {
				$actionDesc = Develop::$config['actions'][$actionName];
				$content .= "\r\n";
				$content .= "\t/**\r\n";
				$content .= "\t * {$actionDesc}\r\n";
				$content .= "\t */\r\n";
				$content .= "\tprotected function {$actionName}()\r\n";
				$content .= "\t{}\r\n";
			}
		}

		$content .= "}";

		File::createFile($path . $className . '.php', 'wb');
		File::writeFile($path . $className . '.php', $content);
	}

	public function createView()
	{
		$path = ocPath('view', $this->ttype . '/template/');
		$path = $path . ($this->mdlname ? $this->mdlname . OC_DIR_SEP : false);
		$path = $path . "{$this->cname}";

		ocCheckPath($path);
		$ttypePath = ocDir($this->ttype);
		
		$cssPath = ocPath('css', $ttypePath);
		$imagesPath = ocPath('images', $ttypePath);

		ocCheckPath(ocPath('view', $ttypePath . 'helper'));
		ocCheckPath(ocPath('view', $ttypePath . 'part'));
		ocCheckPath(ocPath('view', $ttypePath . 'layout'));
		ocCheckPath($cssPath);
		ocCheckPath($imagesPath);

		//新增css和images目录
		ocCheckPath(ocDir($cssPath) . "{$this->mdlname}/{$this->cname}");
		ocCheckPath(ocDir($imagesPath) . "{$this->mdlname}/{$this->cname}");

		$this->addTpl($path, 'index');
		if ($this->vtype != 1) {
			$this->addTpl($path, 'create');
			$this->addTpl($path, 'update');
		}
		
		return true;
	}

	public function addTpl($path, $tpl)
	{
		$path = ocDir($path) . $tpl . '.' . $this->tplType;
		$content = "Hello, I'm %s.{$this->tplType}.";
		
		File::openFile($path, 'wb');
		File::writeFile($path, sprintf($content, $tpl));
	}
}
