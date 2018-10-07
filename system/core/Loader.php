<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   AJAX请求处理类Ajax
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

use \ReflectionClass;
use Ocara\Basis;
use Ocara\Container;

defined('OC_PATH') or exit('Forbidden!');

class Loader extends Basis
{
    private $_defaultPath;
    private $_autoloadMap;

    public function __construct()
    {
        $config = ocContainer()->config;
        $autoMap = $config->get('AUTOLOAD_MAP', array());
        $appAutoMap = $config->get('AUTOLOAD_MAP', array());

        $this->_defaultPath = OC_ROOT . 'application/library/';
        $this->_autoloadMap = array_merge($autoMap, $appAutoMap);
    }

    /**
     * 自动加载类
     * @param string $class
     * @return bool|mixed
     * @throws Exception
     */
    public function autoload($class)
    {
        $newClass = trim($class, OC_NS_SEP);

        if (strstr($newClass, OC_NS_SEP)) {
            $filePath = strtr($newClass, $this->_autoloadMap);
            if ($filePath == $newClass) {
                $filePath = $this->_defaultPath . $newClass;
            }
            $filePath .= '.php';
        }  else {
            $filePath = $this->_defaultPath . $newClass . '.php';
        }

        $filePath = ocCommPath($filePath);

        if (ocFileExists($filePath)) {
            include($filePath);
            if (class_exists($newClass, false)) {
                if (method_exists($newClass, 'loadLanguage')) {
                    $newClass::loadLanguage($filePath);
                }
                return true;
            }
            if (interface_exists($newClass, false)) {
                return true;
            }
        }

        $autoloads = spl_autoload_functions();
        foreach ($autoloads as $func) {
            if (is_string($func)) {
                call_user_func_array($func, array($class));
            } elseif (is_array($func)) {
                $obj = reset($func);
                if (is_object($obj)) {
                    $reflection = new ReflectionClass($obj);
                    $obj = $reflection->getName();
                }
                if ($obj === __CLASS__) continue;
                call_user_func_array($func, array($class));
            } else {
                continue;
            }
            if (class_exists($class, false) || interface_exists($newClass, false)) {
                return true;
            }
        }

        ocService('error', true)->show('not_exists_class', array($class));
    }
}